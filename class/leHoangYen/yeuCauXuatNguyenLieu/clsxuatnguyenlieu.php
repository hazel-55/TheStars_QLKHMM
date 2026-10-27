
<?php

class XuatNguyenLieu
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function taoMaTuDong($loaiMa)
    {
        if ($loaiMa === 'YCXNL') {
            $bang = 'phieuyeucauxuatnguyenlieu';
            $cotMa = 'mayeucau';
            $doDaiTienTo = 5;
        } elseif ($loaiMa === 'CTYC') {
            $bang = 'chitietyeucauxuatnguyenlieu';
            $cotMa = 'machitietyeucau';
            $doDaiTienTo = 4;
        } else {
            throw new Exception('Loại mã không hợp lệ.');
        }

        $sql = "
            SELECT COALESCE(
                MAX(CAST(SUBSTRING($cotMa, " . ($doDaiTienTo + 1) . ") AS UNSIGNED)),
                0
            ) AS so_cuoi
            FROM $bang
            WHERE $cotMa LIKE ?
        ";

        $mauMa = $loaiMa . '%';
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Không thể kiểm tra số thứ tự mã.');
        }

        $stmt->bind_param('s', $mauMa);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('Không thể lấy số thứ tự mã.');
        }

        $dong = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $soMoi = (int) $dong['so_cuoi'] + 1;

        return $loaiMa . str_pad(
            (string) $soMoi,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /*
     * Lấy danh sách kế hoạch đã duyêt
     */

    public function layDanhSachKeHoach($maxuong, $tukhoa = '')
    {
        $sql = "
        SELECT
            kh.makehoach,
            kh.maxuong,
            kh.ngaylap,
            kh.ngaypheduyet,
            kh.trangthaipheduyet,
            py.mayeucau,
            py.trangthai AS trangthaiphieu
        FROM kehoachsanxuat kh
        LEFT JOIN phieuyeucauxuatnguyenlieu py
            ON py.makehoach = kh.makehoach
        WHERE kh.maxuong = ?
          AND LOWER(TRIM(kh.trangthaipheduyet))
              IN ('đã duyệt', 'da duyet')
    ";

        if ($tukhoa !== '') {
            $sql .= " AND kh.makehoach LIKE ?";
        }

        $sql .= " ORDER BY kh.ngaylap DESC";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Không thể chuẩn bị truy vấn kế hoạch.');
        }

        if ($tukhoa !== '') {
            $tuKhoaTim = '%' . $tukhoa . '%';
            $stmt->bind_param('ss', $maxuong, $tuKhoaTim);
        } else {
            $stmt->bind_param('s', $maxuong);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $danhSach = [];

        while ($row = $result->fetch_assoc()) {
            $danhSach[] = $row;
        }

        $stmt->close();

        return $danhSach;
    }


    /*
     * Chỉ trả về kế hoạch đã duyệt của đúng xưởng.
     */
    public function layThongTinKeHoach($makehoach, $maxuong)
    {
        $sql = "
            SELECT
                makehoach,
                maxuong,
                ngaylap,
                ngaypheduyet,
                trangthaipheduyet
            FROM kehoachsanxuat
            WHERE makehoach = ?
              AND maxuong = ?
              AND LOWER(TRIM(trangthaipheduyet))
                  IN ('đã duyệt', 'da duyet')
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Không thể truy vấn thông tin kế hoạch.');
        }

        $stmt->bind_param('ss', $makehoach, $maxuong);
        $stmt->execute();

        $keHoach = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $keHoach ?: null;
    }

    /*
     * Lấy nguyên liệu thuộc một kế hoạch.
     */
    public function layChiTietKeHoach($makehoach)
    {
        $sql = "
            SELECT
                ct.machitietkehoach,
                ct.manguyenlieu,
                nl.tennguyenlieu,
                nl.donvitinh,
                ct.soluongcanxuat,
                k.makho,
                k.tenkho
            FROM chitietkehoachsanxuat ct
            INNER JOIN nguyenlieu nl
                ON ct.manguyenlieu = nl.manguyenlieu
            LEFT JOIN kho k
                ON k.manguyenlieu = nl.manguyenlieu
            WHERE ct.makehoach = ?
            ORDER BY ct.machitietkehoach
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Không thể truy vấn nguyên liệu của kế hoạch.');
        }

        $stmt->bind_param('s', $makehoach);
        $stmt->execute();

        $result = $stmt->get_result();
        $danhSach = [];

        while ($row = $result->fetch_assoc()) {
            $danhSach[] = $row;
        }

        $stmt->close();

        return $danhSach;
    }

    /*
     * Kiểm tra kế hoạch đã có phiếu hay chưa.
     */
    public function layPhieuTheoKeHoach($makehoach)
    {
        $sql = "
            SELECT
                MaYeuCau AS mayeucau,
                MaKeHoach AS makehoach,
                NgayLap AS ngaylap,
                GhiChu AS ghichu,
                TrangThai AS trangthai
            FROM phieuyeucauxuatnguyenlieu
            WHERE MaKeHoach = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $makehoach);
        $stmt->execute();

        $phieu = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $phieu ?: null;
    }

    /*
     * Lập phiếu yêu cầu xuất nguyên liệu.
     */
    public function lapYeuCauXuat($makehoach, $maxuong, $ghichu)
    {
        $makehoach = trim($makehoach);
        $ghichu = trim($ghichu);

        if ($makehoach === '') {
            return [
                'success' => false,
                'message' => 'Mã kế hoạch không hợp lệ.'
            ];
        }

        if (mb_strlen($ghichu, 'UTF-8') > 500) {
            return [
                'success' => false,
                'message' => 'Ghi chú không được vượt quá 500 ký tự.'
            ];
        }

        try {
            $this->conn->begin_transaction();

            $sqlKhoa = "
                SELECT makehoach, maxuong, trangthaipheduyet
                FROM kehoachsanxuat
                WHERE makehoach = ? AND maxuong = ?
                FOR UPDATE
            ";

            $stmtKhoa = $this->conn->prepare($sqlKhoa);
            $stmtKhoa->bind_param('ss', $makehoach, $maxuong);
            $stmtKhoa->execute();

            $keHoach = $stmtKhoa->get_result()->fetch_assoc();
            $stmtKhoa->close();

            if (!$keHoach) {
                throw new Exception('Không tìm thấy kế hoạch thuộc xưởng này.');
            }

            $trangThaiKeHoach = mb_strtolower(
                trim($keHoach['trangthaipheduyet']),
                'UTF-8'
            );

            if (
                $trangThaiKeHoach !== 'đã duyệt'
                && $trangThaiKeHoach !== 'da duyet'
            ) {
                throw new Exception(
                    'Kế hoạch chưa được duyệt nên không thể lập phiếu.'
                );
            }

            if ($this->layPhieuTheoKeHoach($makehoach)) {
                throw new Exception(
                    'Kế hoạch này đã có phiếu yêu cầu xuất nguyên liệu.'
                );
            }

            $chiTietKeHoach = $this->layChiTietKeHoach($makehoach);

            if (empty($chiTietKeHoach)) {
                throw new Exception('Kế hoạch chưa có nguyên liệu cần xuất.');
            }

            $maYeuCau = $this->taoMaTuDong('YCXNL');

            $sqlPhieu = "
                INSERT INTO phieuyeucauxuatnguyenlieu
                    (mayeucau, makehoach, ngaylap, ghichu, trangthai)
                VALUES (?, ?, NOW(), ?, 'Chờ xử lý')
            ";

            $stmtPhieu = $this->conn->prepare($sqlPhieu);
            $stmtPhieu->bind_param(
                'sss',
                $maYeuCau,
                $makehoach,
                $ghichu
            );

            if (!$stmtPhieu->execute()) {
                throw new Exception('Không thể lưu phiếu yêu cầu.');
            }

            $stmtPhieu->close();

            $sqlChiTiet = "
                INSERT INTO chitietyeucauxuatnguyenlieu
                    (machitietyeucau, mayeucau, machitietkehoach, soluongyeucau)
                VALUES (?, ?, ?, ?)
            ";

            $stmtChiTiet = $this->conn->prepare($sqlChiTiet);

            foreach ($chiTietKeHoach as $dong) {
                $maChiTietYeuCau = $this->taoMaTuDong('CTYC');
                $maChiTietKeHoach = $dong['machitietkehoach'];
                $soLuongYeuCau = $dong['soluongcanxuat'];

                $stmtChiTiet->bind_param(
                    'sssd',
                    $maChiTietYeuCau,
                    $maYeuCau,
                    $maChiTietKeHoach,
                    $soLuongYeuCau
                );

                if (!$stmtChiTiet->execute()) {
                    throw new Exception('Không thể lưu chi tiết yêu cầu.');
                }
            }

            $stmtChiTiet->close();
            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Lập phiếu yêu cầu thành công: ' . $maYeuCau,
                'mayeucau' => $maYeuCau
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /*
     * Lấy danh sách phiếu yêu cầu.
     */
    public function layDanhSachPhieu($maxuong, $tukhoa = '')
    {
        $sql = "
            SELECT
                py.mayeucau,
                py.makehoach,
                py.ngaylap,
                py.ghichu,
                py.trangthai
            FROM phieuyeucauxuatnguyenlieu py
            INNER JOIN kehoachsanxuat kh
                ON kh.makehoach = py.makehoach
            WHERE kh.maxuong = ?
        ";

        if ($tukhoa !== '') {
            $sql .= " AND (py.mayeucau LIKE ? OR py.makehoach LIKE ?)";
        }

        $sql .= " ORDER BY py.ngaylap DESC";

        $stmt = $this->conn->prepare($sql);

        if ($tukhoa !== '') {
            $tuKhoaTim = '%' . $tukhoa . '%';
            $stmt->bind_param('sss', $maxuong, $tuKhoaTim, $tuKhoaTim);
        } else {
            $stmt->bind_param('s', $maxuong);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $danhSach = [];

        while ($row = $result->fetch_assoc()) {
            $danhSach[] = $row;
        }

        $stmt->close();

        return $danhSach;
    }

    /*
     * Lấy một phiếu theo mã phiếu và mã xưởng.
     */
    public function layThongTinPhieu($mayeucau, $maxuong)
    {
        $sql = "
            SELECT
                py.mayeucau AS mayeucau,
                py.makehoach AS makehoach,
                py.ngaylap AS ngaylap,
                py.ghichu AS ghichu,
                py.trangthai AS trangthai
            FROM phieuyeucauxuatnguyenlieu py
            INNER JOIN kehoachsanxuat kh
                ON kh.makehoach = py.makehoach
            WHERE py.mayeucau = ?
              AND kh.maxuong = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Không thể truy vấn thông tin phiếu: ' . $this->conn->error
            );
        }

        $stmt->bind_param('ss', $mayeucau, $maxuong);
        $stmt->execute();

        $phieu = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $phieu ?: null;
    }

    /*
     * Lấy các dòng chi tiết của một phiếu.
     */
    public function layChiTietPhieu($mayeucau)
    {
        $sql = "
            SELECT
                ct.machitietyeucau,
                ct.mayeucau,
                ct.machitietkehoach,
                ct.soluongyeucau,
                nl.manguyenlieu,
                nl.tennguyenlieu,
                nl.donvitinh,
                khct.soluongcanxuat
            FROM chitietyeucauxuatnguyenlieu ct
            INNER JOIN chitietkehoachsanxuat khct
                ON khct.machitietkehoach = ct.machitietkehoach
            INNER JOIN nguyenlieu nl
                ON nl.manguyenlieu = khct.manguyenlieu
            WHERE ct.mayeucau = ?
            ORDER BY ct.machitietyeucau
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $mayeucau);
        $stmt->execute();

        $result = $stmt->get_result();
        $danhSach = [];

        while ($row = $result->fetch_assoc()) {
            $danhSach[] = $row;
        }

        $stmt->close();

        return $danhSach;
    }

    /*
     * Chỉnh sửa ghi chú và số lượng yêu cầu.
     * $soLuongTheoChiTiet là mảng:
     * [MaChiTietKeHoach => SoLuongYeuCau]
     */
    public function suaYeuCauXuat(
        $mayeucau,
        $maxuong,
        $ghichu,
        $soLuongTheoChiTiet
    ) {
        $ghichu = trim($ghichu);

        if (mb_strlen($ghichu, 'UTF-8') > 500) {
            return [
                'success' => false,
                'message' => 'Ghi chú không được vượt quá 500 ký tự.'
            ];
        }

        try {
            $this->conn->begin_transaction();

            $phieu = $this->layThongTinPhieu($mayeucau, $maxuong);

            if (!$phieu) {
                throw new Exception('Không tìm thấy phiếu yêu cầu.');
            }

            $chiTietHienTai = $this->layChiTietPhieu($mayeucau);

            if (empty($chiTietHienTai)) {
                throw new Exception('Phiếu không có chi tiết để chỉnh sửa.');
            }

            $sqlGhiChu = "
                UPDATE phieuyeucauxuatnguyenlieu
                SET ghichu = ?
                WHERE mayeucau = ?
            ";

            $stmtGhiChu = $this->conn->prepare($sqlGhiChu);
            $stmtGhiChu->bind_param('ss', $ghichu, $mayeucau);
            $stmtGhiChu->execute();
            $stmtGhiChu->close();

            $sqlSoLuong = "
                UPDATE chitietyeucauxuatnguyenlieu
                SET soluongyeucau = ?
                WHERE mayeucau = ?
                  AND machitietkehoach = ?
            ";

            $stmtSoLuong = $this->conn->prepare($sqlSoLuong);

            foreach ($chiTietHienTai as $dong) {
                $maChiTietKeHoach = $dong['machitietkehoach'];

                if (!isset($soLuongTheoChiTiet[$maChiTietKeHoach])) {
                    throw new Exception('Thiếu số lượng của một nguyên liệu.');
                }

                $soLuong = $soLuongTheoChiTiet[$maChiTietKeHoach];

                if (!is_numeric($soLuong) || (float) $soLuong <= 0) {
                    throw new Exception('Số lượng yêu cầu phải lớn hơn 0.');
                }

                $soLuong = (float) $soLuong;

                $stmtSoLuong->bind_param(
                    'dss',
                    $soLuong,
                    $mayeucau,
                    $maChiTietKeHoach
                );

                if (!$stmtSoLuong->execute()) {
                    throw new Exception('Không thể cập nhật số lượng yêu cầu.');
                }
            }

            $stmtSoLuong->close();
            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Cập nhật phiếu yêu cầu thành công.'
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /*
     * Xóa phiếu và toàn bộ chi tiết liên quan.
     */
    public function xoaYeuCauXuat($mayeucau, $maxuong)
    {
        try {
            $this->conn->begin_transaction();

            $phieu = $this->layThongTinPhieu($mayeucau, $maxuong);

            if (!$phieu) {
                throw new Exception('Không tìm thấy phiếu yêu cầu.');
            }

            $sqlChiTiet = "
                DELETE FROM chitietyeucauxuatnguyenlieu
                WHERE mayeucau = ?
            ";

            $stmtChiTiet = $this->conn->prepare($sqlChiTiet);
            $stmtChiTiet->bind_param('s', $mayeucau);
            $stmtChiTiet->execute();
            $stmtChiTiet->close();

            $sqlPhieu = "
                DELETE FROM phieuyeucauxuatnguyenlieu
                WHERE mayeucau = ?
            ";

            $stmtPhieu = $this->conn->prepare($sqlPhieu);
            $stmtPhieu->bind_param('s', $mayeucau);
            $stmtPhieu->execute();
            $stmtPhieu->close();

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Đã xóa phiếu yêu cầu và các chi tiết liên quan.'
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
