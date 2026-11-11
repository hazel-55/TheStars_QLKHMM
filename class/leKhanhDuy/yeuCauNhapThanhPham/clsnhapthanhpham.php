<?php
/**
 * Mô hình nghiệp vụ: Lập phiếu yêu cầu nhập thành phẩm (Lê Khánh Duy).
 * Bám theo 4 bảng của Lapphieuyeucaunhapthanhpham.docx.
 * Lớp không xuất HTML, không tự mở kết nối và không tự đăng nhập.
 */

declare(strict_types=1);

final class NhapThanhPham
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    private function truyVan(string $sql, string $types = '', array $params = []): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    private function danhSach(string $sql, string $types = '', array $params = []): array
    {
        $stmt = $this->truyVan($sql, $types, $params);
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    private function motDong(string $sql, string $types = '', array $params = []): ?array
    {
        return $this->danhSach($sql, $types, $params)[0] ?? null;
    }

    private function thucThi(string $sql, string $types = '', array $params = []): void
    {
        $stmt = $this->truyVan($sql, $types, $params);
        $stmt->close();
    }

    public function layThanhPham(): array
    {
        return $this->danhSach('SELECT MaThanhPham, TenThanhPham, DonViTinh FROM ThanhPham ORDER BY TenThanhPham');
    }

    public function layThongKe(int $maQuanLyXuong): array
    {
        $row = $this->motDong(
            "SELECT COUNT(*) AS Tong,
                    COALESCE(SUM(TrangThai='Nháp'), 0) AS Nhap,
                    COALESCE(SUM(TrangThai='Chờ kiểm định'), 0) AS ChoKiemDinh
             FROM PhieuYeuCauNhapThanhPham WHERE MaQuanLyXuong=?",
            'i', [$maQuanLyXuong]
        );
        return $row ?? ['Tong' => 0, 'Nhap' => 0, 'ChoKiemDinh' => 0];
    }

    public function layDanhSachPhieu(int $maQuanLyXuong, string $tuKhoa = '', string $trangThai = ''): array
    {
        $sql = "SELECT p.MaYeuCau, p.NgayLap, p.NgayHoanThanhSanXuat,
                       p.GhiChu, p.TrangThai,
                       COUNT(ct.MaChiTietYeuCau) AS SoMatHang,
                       COALESCE(SUM(ct.SoLuongYeuCau),0) AS TongSoLuong
                FROM PhieuYeuCauNhapThanhPham p
                LEFT JOIN ChiTietYeuCauNhapThanhPham ct ON ct.MaYeuCau=p.MaYeuCau
                WHERE p.MaQuanLyXuong=?";
        $types = 'i';
        $params = [$maQuanLyXuong];
        if ($trangThai !== '') {
            if (!in_array($trangThai, ['Nháp', 'Chờ kiểm định'], true)) {
                throw new InvalidArgumentException('Trạng thái lọc không hợp lệ.');
            }
            $sql .= ' AND p.TrangThai=?';
            $types .= 's';
            $params[] = $trangThai;
        }
        if ($tuKhoa !== '') {
            $sql .= " AND (CAST(p.MaYeuCau AS CHAR) LIKE ? OR p.GhiChu LIKE ? OR EXISTS
                       (SELECT 1 FROM ChiTietYeuCauNhapThanhPham x
                        INNER JOIN ThanhPham t ON t.MaThanhPham=x.MaThanhPham
                        WHERE x.MaYeuCau=p.MaYeuCau AND t.TenThanhPham LIKE ?))";
            $types .= 'sss';
            $params[] = '%' . $tuKhoa . '%';
            $params[] = '%' . $tuKhoa . '%';
            $params[] = '%' . $tuKhoa . '%';
        }
        $sql .= ' GROUP BY p.MaYeuCau, p.NgayLap, p.NgayHoanThanhSanXuat, p.GhiChu, p.TrangThai ORDER BY p.MaYeuCau DESC LIMIT 200';
        return $this->danhSach($sql, $types, $params);
    }

    public function layPhieu(int $maYeuCau, int $maQuanLyXuong): ?array
    {
        return $this->motDong(
            'SELECT p.*, tk.TenDangNhap AS NguoiLap FROM PhieuYeuCauNhapThanhPham p
             JOIN TaiKhoan tk ON tk.MaTaiKhoan=p.MaQuanLyXuong
             WHERE p.MaYeuCau=? AND p.MaQuanLyXuong=?',
            'ii', [$maYeuCau, $maQuanLyXuong]
        );
    }

    public function layChiTiet(int $maYeuCau): array
    {
        return $this->danhSach(
            'SELECT ct.MaThanhPham, tp.TenThanhPham, tp.DonViTinh, ct.SoLuongYeuCau
             FROM ChiTietYeuCauNhapThanhPham ct
             INNER JOIN ThanhPham tp ON tp.MaThanhPham=ct.MaThanhPham
             WHERE ct.MaYeuCau=? ORDER BY ct.MaChiTietYeuCau',
            'i', [$maYeuCau]
        );
    }

    /** Chỉ nhận các trường trong đặc tả; kiểm tra lại tất cả ở phía máy chủ. */
    private function kiemTraDuLieu(array $data): array
    {
        $ngay = trim((string) ($data['NgayHoanThanhSanXuat'] ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ngay);
        if (!$date || $date->format('Y-m-d') !== $ngay || $ngay > date('Y-m-d')) {
            throw new InvalidArgumentException('Ngày hoàn thành sản xuất không hợp lệ hoặc nằm trong tương lai.');
        }
        $ghiChu = trim((string) ($data['GhiChu'] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($ghiChu, 'UTF-8') : preg_match_all('/./us', $ghiChu);
        if ($length > 500) {
            throw new InvalidArgumentException('Ghi chú tối đa 500 ký tự.');
        }
        $ma = $data['MaThanhPham'] ?? null;
        $soLuong = $data['SoLuongYeuCau'] ?? null;
        if (!is_array($ma) || !is_array($soLuong) || count($ma) === 0 || count($ma) > 50 || count($ma) !== count($soLuong)) {
            throw new InvalidArgumentException('Phải chọn từ 1 đến 50 dòng thành phẩm hợp lệ.');
        }
        $sanPham = [];
        foreach (array_values($ma) as $i => $rawMa) {
            $rawSoLuong = array_values($soLuong)[$i] ?? null;
            if (!is_scalar($rawMa) || !is_scalar($rawSoLuong)) {
                throw new InvalidArgumentException('Dữ liệu dòng ' . ($i + 1) . ' không hợp lệ.');
            }
            $maInt = filter_var((string) $rawMa, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $luong = trim((string) $rawSoLuong);
            if ($maInt === false) {
                throw new InvalidArgumentException('Vui lòng chọn thành phẩm ở dòng ' . ($i + 1) . '.');
            }
            // Đúng DECIMAL(18,2): tối đa 16 chữ số phần nguyên và 2 chữ số phần lẻ.
            if (!preg_match('/^\d{1,16}(?:\.\d{1,2})?$/D', $luong) || !preg_match('/[1-9]/', $luong)) {
                throw new InvalidArgumentException('Số lượng dòng ' . ($i + 1) . ' phải lớn hơn 0 và tối đa 2 chữ số thập phân.');
            }
            if (isset($sanPham[$maInt])) {
                throw new InvalidArgumentException('Không được chọn một thành phẩm hai lần.');
            }
            if ($this->motDong('SELECT MaThanhPham FROM ThanhPham WHERE MaThanhPham=?', 'i', [$maInt]) === null) {
                throw new InvalidArgumentException('Thành phẩm dòng ' . ($i + 1) . ' không còn tồn tại.');
            }
            $sanPham[$maInt] = $luong;
        }
        return [$ngay, $ghiChu, $sanPham];
    }

    /**
     * $guiKiemDinh=false: Nháp; =true: Chờ kiểm định.
     * Không thay đổi tồn kho vì UC này mới gửi yêu cầu, chưa nhập kho.
     */
    public function luuPhieu(int $nguoiLap, array $data, bool $guiKiemDinh, ?int $maSua = null): int
    {
        [$ngay, $ghiChu, $hang] = $this->kiemTraDuLieu($data);
        $this->db->begin_transaction();
        try {
            if ($maSua !== null) {
                $p = $this->motDong(
                    'SELECT TrangThai FROM PhieuYeuCauNhapThanhPham WHERE MaYeuCau=? AND MaQuanLyXuong=? FOR UPDATE',
                    'ii', [$maSua, $nguoiLap]
                );
                if (!$p || $p['TrangThai'] !== 'Nháp') {
                    throw new RuntimeException('Chỉ được sửa phiếu Nháp của chính mình.');
                }
                $this->thucThi(
                    'UPDATE PhieuYeuCauNhapThanhPham SET NgayHoanThanhSanXuat=?, GhiChu=?, TrangThai=? WHERE MaYeuCau=?',
                    'sssi', [$ngay, $ghiChu, $guiKiemDinh ? 'Chờ kiểm định' : 'Nháp', $maSua]
                );
                $this->thucThi('DELETE FROM ChiTietYeuCauNhapThanhPham WHERE MaYeuCau=?', 'i', [$maSua]);
                $id = $maSua;
            } else {
                $this->thucThi(
                    'INSERT INTO PhieuYeuCauNhapThanhPham (MaQuanLyXuong, NgayLap, NgayHoanThanhSanXuat, GhiChu, TrangThai)
                     VALUES (?, NOW(), ?, ?, ?)',
                    'isss', [$nguoiLap, $ngay, $ghiChu, $guiKiemDinh ? 'Chờ kiểm định' : 'Nháp']
                );
                $id = (int) $this->db->insert_id;
            }
            foreach ($hang as $maTp => $luong) {
                $this->thucThi(
                    'INSERT INTO ChiTietYeuCauNhapThanhPham (MaYeuCau, MaThanhPham, SoLuongYeuCau) VALUES (?, ?, ?)',
                    'iis', [$id, $maTp, $luong]
                );
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function guiPhieuNhap(int $maYeuCau, int $nguoiLap): void
    {
        $this->doiTrangThaiPhieu($maYeuCau, $nguoiLap, 'Chờ kiểm định');
    }

    public function huyPhieuNhap(int $maYeuCau, int $nguoiLap): void
    {
        // Đặc tả: phiếu Nháp mới được xóa; với FK CASCADE chi tiết xóa cùng.
        $this->db->begin_transaction();
        try {
            $p = $this->motDong(
                'SELECT TrangThai FROM PhieuYeuCauNhapThanhPham WHERE MaYeuCau=? AND MaQuanLyXuong=? FOR UPDATE',
                'ii', [$maYeuCau, $nguoiLap]
            );
            if (!$p || $p['TrangThai'] !== 'Nháp') {
                throw new RuntimeException('Phiếu không tồn tại hoặc đã gửi, không thể hủy.');
            }
            $this->thucThi('DELETE FROM PhieuYeuCauNhapThanhPham WHERE MaYeuCau=?', 'i', [$maYeuCau]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function doiTrangThaiPhieu(int $maYeuCau, int $nguoiLap, string $trangThaiMoi): void
    {
        $this->db->begin_transaction();
        try {
            $p = $this->motDong(
                'SELECT TrangThai FROM PhieuYeuCauNhapThanhPham WHERE MaYeuCau=? AND MaQuanLyXuong=? FOR UPDATE',
                'ii', [$maYeuCau, $nguoiLap]
            );
            if (!$p || $p['TrangThai'] !== 'Nháp') {
                throw new RuntimeException('Chỉ có thể gửi phiếu Nháp của chính mình.');
            }
            $this->thucThi('UPDATE PhieuYeuCauNhapThanhPham SET TrangThai=? WHERE MaYeuCau=?',
                'si', [$trangThaiMoi, $maYeuCau]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /** Danh sách được gửi từ QLX, người có vai trò KIEM_DINH có thể đọc. */
    public function layPhieuChoKiemDinh(): array
    {
        return $this->danhSach(
            "SELECT p.MaYeuCau, p.NgayLap, p.NgayHoanThanhSanXuat, p.GhiChu,
                    tk.TenDangNhap AS NguoiLap, COUNT(ct.MaChiTietYeuCau) AS SoMatHang
             FROM PhieuYeuCauNhapThanhPham p
             JOIN TaiKhoan tk ON tk.MaTaiKhoan=p.MaQuanLyXuong
             JOIN ChiTietYeuCauNhapThanhPham ct ON ct.MaYeuCau=p.MaYeuCau
             WHERE p.TrangThai='Chờ kiểm định'
             GROUP BY p.MaYeuCau, p.NgayLap, p.NgayHoanThanhSanXuat, p.GhiChu, tk.TenDangNhap
             ORDER BY p.NgayLap DESC, p.MaYeuCau DESC"
        );
    }

    public function layPhieuChoKiemDinhTheoMa(int $maYeuCau): ?array
    {
        return $this->motDong(
            "SELECT p.*, tk.TenDangNhap AS NguoiLap FROM PhieuYeuCauNhapThanhPham p
             JOIN TaiKhoan tk ON tk.MaTaiKhoan=p.MaQuanLyXuong
             WHERE p.MaYeuCau=? AND p.TrangThai='Chờ kiểm định'", 'i', [$maYeuCau]
        );
    }
}
