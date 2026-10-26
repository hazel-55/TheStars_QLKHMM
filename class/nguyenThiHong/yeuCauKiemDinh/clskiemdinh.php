<?php
class KiemDinh
{
    private mysqli $conn;
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }
    /* ==================== HÀM DÙNG CHUNG ==================== */
    private function truyVan(
        string $sql,
        string $types = '',
        array $params = []
    ): mysqli_stmt {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Không thể chuẩn bị truy vấn.');
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new RuntimeException('Không thể thực hiện truy vấn.');
        }
        return $stmt;
    }
    private function layNhieuDong(
        string $sql,
        string $types = '',
        array $params = []
    ): array {
        $stmt = $this->truyVan($sql, $types, $params);
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
    private function layMotDong(
        string $sql,
        string $types = '',
        array $params = []
    ): ?array {
        $rows = $this->layNhieuDong($sql, $types, $params);
        return $rows[0] ?? null;
    }
    private function thucThi(
        string $sql,
        string $types = '',
        array $params = []
    ): void {
        $stmt = $this->truyVan($sql, $types, $params);
        $stmt->close();
    }
    private function kiemTraNhanVien(string $maNhanVien): void
    {
        $nhanVien = $this->layMotDong(
            "SELECT MaNguoiDung
             FROM NguoiDung
             WHERE MaNguoiDung = ?
               AND VaiTro = 'KIEM_DINH'
               AND HoatDong = 1",
            's',
            [$maNhanVien]
        );
        if (!$nhanVien) {
            throw new RuntimeException(
                'Tài khoản không có quyền thực hiện kiểm định.'
            );
        }
    }
    private function kiemTraGhiChu($giaTri, int $gioiHan): string
    {
        if (!is_scalar($giaTri)) {
            throw new InvalidArgumentException('Ghi chú không hợp lệ.');
        }
        $giaTri = trim((string) $giaTri);
        if (mb_strlen($giaTri, 'UTF-8') > $gioiHan) {
            throw new InvalidArgumentException(
                "Ghi chú không được vượt quá {$gioiHan} ký tự."
            );
        }
        return $giaTri;
    }
    /*
     * Đổi số lượng sang phần nghìn để cộng chính xác.
     * Ví dụ: 1.250 mét -> 1250.
     * Yêu cầu PHP 64-bit.
     */
    private function doiSoLuong($giaTri): int
    {
        if (
            !is_scalar($giaTri) ||
            !preg_match(
                '/^\d{1,9}(?:\.\d{1,3})?$/D',
                (string) $giaTri
            )
        ) {
            throw new InvalidArgumentException(
                'Số lượng không hợp lệ, tối đa 3 chữ số thập phân.'
            );
        }
        $phan = explode('.', (string) $giaTri);
        return ((int) $phan[0] * 1000)
            + (int) str_pad($phan[1] ?? '', 3, '0');
    }
    private function veSoThapPhan(int $giaTri): string
    {
        return intdiv($giaTri, 1000)
            . '.'
            . str_pad((string) ($giaTri % 1000), 3, '0', STR_PAD_LEFT);
    }
    /* ==================== XEM DANH SÁCH ==================== */
    public function layDanhSachPhieu(
        string $maNhanVien,
        string $tuKhoa = '',
        string $trangThai = ''
    ): array {
        $this->kiemTraNhanVien($maNhanVien);
        $sql = "
            SELECT
                p.*,
                u.HoTen AS TenNguoiGui,
                (
                    SELECT COUNT(*)
                    FROM ChiTietYeuCauKiemDinh ct
                    WHERE ct.MaYCKD = p.MaPhieu
                ) AS SoMatHang
            FROM PhieuYeuCauKiemDinh p
            JOIN NguoiDung u ON u.MaNguoiDung = p.NguoiGui
            WHERE p.NguoiThucHien = ?
        ";
        $types = 's';
        $params = [$maNhanVien];
        if (trim($tuKhoa) !== '') {
            $sql .= ' AND p.MaPhieu LIKE ?';
            $types .= 's';
            $params[] = '%' . trim($tuKhoa) . '%';
        }
        if ($trangThai !== '') {
            $sql .= ' AND p.TrangThai = ?';
            $types .= 's';
            $params[] = $trangThai;
        }
        $sql .= ' ORDER BY p.NgayLap DESC, p.MaPhieu DESC';
        return $this->layNhieuDong($sql, $types, $params);
    }
    /* ==================== XEM CHI TIẾT ==================== */
    public function layChiTietPhieu(
        string $maPhieu,
        string $maNhanVien
    ): array {
        $this->kiemTraNhanVien($maNhanVien);
        $phieu = $this->layMotDong(
            "SELECT p.*, u.HoTen AS TenNguoiGui
             FROM PhieuYeuCauKiemDinh p
             JOIN NguoiDung u ON u.MaNguoiDung = p.NguoiGui
             WHERE p.MaPhieu = ?
               AND p.NguoiThucHien = ?",
            'ss',
            [$maPhieu, $maNhanVien]
        );
        if (!$phieu) {
            throw new RuntimeException(
                'Không tìm thấy phiếu hoặc phiếu không được giao cho bạn.'
            );
        }
        $hangHoa = $this->layNhieuDong(
            "SELECT
                ct.*,
                h.TenHang,
                h.DanhMuc,
                h.QuyCach,
                h.DonViTinh
             FROM ChiTietYeuCauKiemDinh ct
             JOIN HangHoa h ON h.MaHang = ct.MaHang
             WHERE ct.MaYCKD = ?
             ORDER BY ct.MaCTKD",
            's',
            [$maPhieu]
        );
        foreach ($hangHoa as &$hang) {
            $hang['TieuChi'] = $this->layNhieuDong(
                "SELECT *
                 FROM TieuChiYeuCauKiemDinh
                 WHERE MaCTKD = ?
                 ORDER BY ThuTu, MaTieuChiYC",
                'i',
                [$hang['MaCTKD']]
            );
            $hang['NhomKiemDinh'] = $this->layNhieuDong(
                "SELECT *
                 FROM NhomKiemDinh
                 WHERE MaCTKD = ?
                 ORDER BY MaNhom",
                'i',
                [$hang['MaCTKD']]
            );
            foreach ($hang['NhomKiemDinh'] as &$nhom) {
                $ketQua = $this->layNhieuDong(
                    "SELECT *
                     FROM KetQuaTieuChiKiemDinh
                     WHERE MaNhom = ?",
                    'i',
                    [$nhom['MaNhom']]
                );
                $nhom['KetQuaTieuChi'] = [];
                foreach ($ketQua as $dong) {
                    $nhom['KetQuaTieuChi'][$dong['MaTieuChiYC']] = $dong;
                }
            }
            unset($nhom);
        }
        unset($hang);
        $phieu['HangHoa'] = $hangHoa;
        return $phieu;
    }
    public function layLichSu(
        string $maPhieu,
        string $maNhanVien
    ): array {
        // Kiểm tra quyền xem trước khi lấy lịch sử.
        $this->layChiTietPhieu($maPhieu, $maNhanVien);
        return $this->layNhieuDong(
            "SELECT ls.*, u.HoTen
             FROM LichSuKiemDinh ls
             JOIN NguoiDung u
               ON u.MaNguoiDung = ls.NguoiThucHien
             WHERE ls.MaYCKD = ?
             ORDER BY ls.ThoiDiem DESC, ls.MaLichSu DESC",
            's',
            [$maPhieu]
        );
    }
    /* ==================== KIỂM TRA KẾT QUẢ ==================== */
    private function kiemTraNhom(
        array $hang,
        array $cacNhom,
        bool $hoanTat
    ): array {
        if (empty($hang['TieuChi'])) {
            throw new RuntimeException(
                'Hàng hóa chưa được thiết lập tiêu chí kiểm định.'
            );
        }
        if (count($cacNhom) > 100) {
            throw new InvalidArgumentException(
                'Mỗi mặt hàng chỉ được tối đa 100 nhóm.'
            );
        }
        $maTieuChiHopLe = array_map(
            'strval',
            array_column($hang['TieuChi'], 'MaTieuChiYC')
        );
        $tong = 0;
        $tongDat = 0;
        $tongKhongDat = 0;
        $nhomDaKiemTra = [];
        foreach ($cacNhom as $nhom) {
            if (!is_array($nhom)) {
                throw new InvalidArgumentException('Nhóm không hợp lệ.');
            }
            $soLuong = $this->doiSoLuong($nhom['SoLuong'] ?? '');
            if ($soLuong <= 0) {
                throw new InvalidArgumentException(
                    'Số lượng mỗi nhóm phải lớn hơn 0.'
                );
            }
            if (
                $hang['DonViTinh'] !== 'Mét' &&
                $soLuong % 1000 !== 0
            ) {
                throw new InvalidArgumentException(
                    'Đơn vị đếm như cuộn/cái phải nhập số lượng nguyên.'
                );
            }
            $ketQuaGuiLen = $nhom['TieuChi'] ?? [];
            if (!is_array($ketQuaGuiLen)) {
                throw new InvalidArgumentException(
                    'Dữ liệu tiêu chí không hợp lệ.'
                );
            }
            foreach (array_keys($ketQuaGuiLen) as $maTieuChi) {
                if (
                    !in_array(
                        (string) $maTieuChi,
                        $maTieuChiHopLe,
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Tiêu chí không thuộc mặt hàng đang kiểm định.'
                    );
                }
            }
            $daDanhGiaDu = true;
            $coTieuChiKhongDat = false;
            $chiTietKetQua = [];
            foreach ($hang['TieuChi'] as $tieuChi) {
                $ma = $tieuChi['MaTieuChiYC'];
                $duLieu = $ketQuaGuiLen[$ma] ?? [];
                if (!is_array($duLieu)) {
                    throw new InvalidArgumentException(
                        'Kết quả tiêu chí không hợp lệ.'
                    );
                }
                $ketQua = $duLieu['KetQua'] ?? '';
                if (
                    !in_array(
                        $ketQua,
                        ['', 'Đạt', 'Không đạt'],
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Kết quả chỉ được là Đạt hoặc Không đạt.'
                    );
                }
                if ($ketQua === '') {
                    $daDanhGiaDu = false;
                }
                if ($ketQua === 'Không đạt') {
                    $coTieuChiKhongDat = true;
                }
                $chiTietKetQua[] = [
                    'MaTieuChiYC' => $ma,
                    'KetQua' => $ketQua === '' ? null : $ketQua,
                    'GhiChu' => $this->kiemTraGhiChu(
                        $duLieu['GhiChu'] ?? '',
                        500
                    ),
                ];
            }
            if ($hoanTat && !$daDanhGiaDu) {
                throw new RuntimeException(
                    'Cần đánh giá đủ tiêu chí của ' . $hang['TenHang'] . '.'
                );
            }
            $ketLuan = 'Chưa hoàn tất';
            if ($daDanhGiaDu) {
                $ketLuan = $coTieuChiKhongDat ? 'Không đạt' : 'Đạt';
            }
            $tong += $soLuong;
            if ($ketLuan === 'Đạt') {
                $tongDat += $soLuong;
            } elseif ($ketLuan === 'Không đạt') {
                // Một nhóm có nhiều lỗi vẫn chỉ cộng số lượng một lần.
                $tongKhongDat += $soLuong;
            }
            $nhomDaKiemTra[] = [
                'SoLuong' => $this->veSoThapPhan($soLuong),
                'KetLuan' => $ketLuan,
                'GhiChu' => $this->kiemTraGhiChu(
                    $nhom['GhiChu'] ?? '',
                    300
                ),
                'TieuChi' => $chiTietKetQua,
            ];
        }
        $soLuongYeuCau = $this->doiSoLuong(
            $hang['SoLuongYeuCauKiemDinh']
        );
        if ($tong > $soLuongYeuCau) {
            throw new RuntimeException(
                'Tổng nhóm vượt số lượng yêu cầu của '
                . $hang['TenHang'] . '.'
            );
        }
        if (
            $hoanTat &&
            (empty($cacNhom) || $tong !== $soLuongYeuCau)
        ) {
            throw new RuntimeException(
                'Tổng số lượng nhóm phải bằng số lượng yêu cầu của '
                . $hang['TenHang'] . '.'
            );
        }
        return [
            'Nhom' => $nhomDaKiemTra,
            'SoLuongDat' => $this->veSoThapPhan($tongDat),
            'SoLuongKhongDat' => $this->veSoThapPhan($tongKhongDat),
        ];
    }
    /* ==================== LƯU KẾT QUẢ ==================== */
    public function luuNhap(
        string $maPhieu,
        string $maNhanVien,
        int $phienBan,
        array $duLieu
    ): void {
        $this->luuKetQua(
            $maPhieu,
            $maNhanVien,
            $phienBan,
            $duLieu,
            false
        );
    }
    public function hoanTatKiemDinh(
        string $maPhieu,
        string $maNhanVien,
        int $phienBan,
        array $duLieu
    ): void {
        $this->luuKetQua(
            $maPhieu,
            $maNhanVien,
            $phienBan,
            $duLieu,
            true
        );
    }
    private function luuKetQua(
        string $maPhieu,
        string $maNhanVien,
        int $phienBan,
        array $duLieu,
        bool $hoanTat
    ): void {
        $this->kiemTraNhanVien($maNhanVien);
        $this->conn->begin_transaction();
        try {
            // Khóa phiếu để tránh hai yêu cầu cập nhật đồng thời.
            $phieu = $this->layMotDong(
                "SELECT *
                 FROM PhieuYeuCauKiemDinh
                 WHERE MaPhieu = ?
                 FOR UPDATE",
                's',
                [$maPhieu]
            );
            if (
                !$phieu ||
                $phieu['NguoiThucHien'] !== $maNhanVien
            ) {
                throw new RuntimeException(
                    'Phiếu không được phân công cho bạn.'
                );
            }
            if (
                !in_array(
                    $phieu['TrangThai'],
                    ['Chờ kiểm định', 'Đang kiểm định'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Phiếu đã hoàn tất hoặc đã hủy, không thể cập nhật.'
                );
            }
            if ((int) $phieu['PhienBan'] !== $phienBan) {
                throw new RuntimeException(
                    'Phiếu đã thay đổi ở phiên khác. Hãy tải lại trang.'
                );
            }
            $chiTiet = $this->layChiTietPhieu(
                $maPhieu,
                $maNhanVien
            );
            if (empty($chiTiet['HangHoa'])) {
                throw new RuntimeException('Phiếu chưa có hàng hóa.');
            }
            $maChiTietHopLe = array_map(
                'strval',
                array_column($chiTiet['HangHoa'], 'MaCTKD')
            );
            foreach (array_keys($duLieu) as $maCTKD) {
                if (
                    !in_array(
                        (string) $maCTKD,
                        $maChiTietHopLe,
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Dòng hàng không thuộc phiếu.'
                    );
                }
            }
            // Kiểm tra toàn bộ dữ liệu trước khi ghi.
            $ketQuaDaKiemTra = [];
            foreach ($chiTiet['HangHoa'] as $hang) {
                $maCTKD = $hang['MaCTKD'];
                $cacNhom = $duLieu[$maCTKD] ?? [];
                if (!is_array($cacNhom)) {
                    throw new InvalidArgumentException(
                        'Danh sách nhóm không hợp lệ.'
                    );
                }
                $ketQuaDaKiemTra[$maCTKD] = $this->kiemTraNhom(
                    $hang,
                    $cacNhom,
                    $hoanTat
                );
            }
            foreach ($ketQuaDaKiemTra as $maCTKD => $ketQua) {
                /*
                 * Thay lại nhóm khi phiếu còn mở.
                 * FK ON DELETE CASCADE xóa kết quả tiêu chí của nhóm cũ.
                 */
                $this->thucThi(
                    'DELETE FROM NhomKiemDinh WHERE MaCTKD = ?',
                    'i',
                    [$maCTKD]
                );
                foreach ($ketQua['Nhom'] as $nhom) {
                    $this->thucThi(
                        "INSERT INTO NhomKiemDinh
                            (MaCTKD, SoLuong, KetLuan, GhiChu)
                         VALUES (?, ?, ?, ?)",
                        'isss',
                        [
                            $maCTKD,
                            $nhom['SoLuong'],
                            $nhom['KetLuan'],
                            $nhom['GhiChu'],
                        ]
                    );
                    $maNhom = $this->conn->insert_id;
                    foreach ($nhom['TieuChi'] as $tieuChi) {
                        $this->thucThi(
                            "INSERT INTO KetQuaTieuChiKiemDinh
                                (
                                    MaNhom,
                                    MaCTKD,
                                    MaTieuChiYC,
                                    KetQua,
                                    GhiChu
                                )
                             VALUES (?, ?, ?, ?, ?)",
                            'iiiss',
                            [
                                $maNhom,
                                $maCTKD,
                                $tieuChi['MaTieuChiYC'],
                                $tieuChi['KetQua'],
                                $tieuChi['GhiChu'],
                            ]
                        );
                    }
                }
                $this->thucThi(
                    "UPDATE ChiTietYeuCauKiemDinh
                     SET SoLuongDat = ?,
                         SoLuongKhongDat = ?
                     WHERE MaCTKD = ?",
                    'ssi',
                    [
                        $ketQua['SoLuongDat'],
                        $ketQua['SoLuongKhongDat'],
                        $maCTKD,
                    ]
                );
            }
            $trangThai = $hoanTat
                ? 'Hoàn tất kiểm định'
                : 'Đang kiểm định';
            $this->thucThi(
                "UPDATE PhieuYeuCauKiemDinh
                 SET TrangThai = ?,
                     NgayCapNhat = CURRENT_TIMESTAMP,
                     NgayHoanTat = CASE
                         WHEN ? = 1 THEN CURRENT_TIMESTAMP
                         ELSE NULL
                     END,
                     PhienBan = PhienBan + 1
                 WHERE MaPhieu = ?",
                'sis',
                [$trangThai, (int) $hoanTat, $maPhieu]
            );
            $this->thucThi(
                "INSERT INTO LichSuKiemDinh
                    (MaYCKD, NguoiThucHien, HanhDong)
                 VALUES (?, ?, ?)",
                'sss',
                [
                    $maPhieu,
                    $maNhanVien,
                    $hoanTat ? 'Hoàn tất kiểm định' : 'Lưu nháp',
                ]
            );
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /** Chuẩn bị dữ liệu giao diện và xử lý lưu; không xuất HTML. */
    public function chuanBiTrang(
        array $get,
        array $post,
        string $requestMethod,
        string $maNhanVien,
        string $csrf
    ): array {
        $loi = '';
        $thongBao = '';
        $danhSach = [];
        $phieu = null;
        $duLieuDaNhap = null;
        // Các tên biến này chỉ là dữ liệu hiển thị, không chứa hàm nghiệp vụ.
        $maPhieu = is_string($get['maPhieu'] ?? null)
            ? trim($get['maPhieu'])
            : '';
        $tuKhoa = is_string($get['tuKhoa'] ?? null)
            ? trim($get['tuKhoa'])
            : '';
        $trangThaiLoc = is_string($get['trangThai'] ?? null)
            ? $get['trangThai']
            : '';
        // Giữ bộ lọc / tham số URL khi chuyển giữa danh sách và chi tiết.
        $thamSoRouter = $get;
        unset(
            $thamSoRouter['maPhieu'],
            $thamSoRouter['tuKhoa'],
            $thamSoRouter['trangThai']
        );
        $urlDanhSach = '?' . http_build_query($thamSoRouter);
        try {
            if (
                $maPhieu !== '' &&
                !preg_match('/^[A-Za-z0-9_-]{1,20}$/D', $maPhieu)
            ) {
                throw new InvalidArgumentException('Mã phiếu không hợp lệ.');
            }
            if ($maPhieu !== '') {
                $phieu = $this->layChiTietPhieu(
                    $maPhieu,
                    $maNhanVien
                );
            }
            if ($requestMethod === 'POST') {
                if (
                    !is_string($post['csrf'] ?? null) ||
                    !hash_equals($csrf, $post['csrf'])
                ) {
                    throw new RuntimeException(
                        'Phiên gửi biểu mẫu không hợp lệ. Hãy tải lại trang.'
                    );
                }
                if (!$phieu) {
                    throw new RuntimeException('Không xác định được phiếu.');
                }
                $hanhDong = $post['hanhDong'] ?? '';
                if (!in_array($hanhDong, ['luu_nhap', 'hoan_tat'], true)) {
                    throw new InvalidArgumentException('Thao tác không hợp lệ.');
                }
                $phienBanGuiLen = $post['phienBan'] ?? '';
                if (
                    !is_string($phienBanGuiLen) ||
                    !preg_match('/^\d+$/D', $phienBanGuiLen)
                ) {
                    throw new InvalidArgumentException(
                        'Phiên bản phiếu không hợp lệ.'
                    );
                }
                $json = $post['duLieu'] ?? null;
                if (!is_string($json) || strlen($json) > 2000000) {
                    throw new InvalidArgumentException(
                        'Dữ liệu biểu mẫu không hợp lệ hoặc quá lớn.'
                    );
                }
                $duLieuDaNhap = json_decode(
                    $json,
                    true,
                    64,
                    JSON_THROW_ON_ERROR
                );
                if (!is_array($duLieuDaNhap)) {
                    throw new InvalidArgumentException(
                        'Dữ liệu kiểm định không hợp lệ.'
                    );
                }
                if ($hanhDong === 'luu_nhap') {
                    $this->luuNhap(
                        $maPhieu,
                        $maNhanVien,
                        (int) $phienBanGuiLen,
                        $duLieuDaNhap
                    );
                    $thongBao = 'Đã lưu nháp. Phiếu đang kiểm định.';
                } else {
                    $this->hoanTatKiemDinh(
                        $maPhieu,
                        $maNhanVien,
                        (int) $phienBanGuiLen,
                        $duLieuDaNhap
                    );
                    $thongBao = 'Đã hoàn tất kiểm định.';
                }
                $duLieuDaNhap = null;
                $phieu = $this->layChiTietPhieu(
                    $maPhieu,
                    $maNhanVien
                );
            }
            if ($maPhieu === '') {
                $danhSach = $this->layDanhSachPhieu(
                    $maNhanVien,
                    $tuKhoa,
                    $trangThaiLoc
                );
            }
        } catch (mysqli_sql_exception $e) {
            error_log('KiemDinh database: ' . $e->getMessage());
            $loi = 'Không thể xử lý database. Hãy kiểm tra nhật ký PHP.';
        } catch (JsonException $e) {
            $loi = 'Dữ liệu gửi lên không đúng định dạng.';
        } catch (RuntimeException | InvalidArgumentException $e) {
            $loi = $e->getMessage();
        } catch (Throwable $e) {
            error_log('KiemDinh: ' . $e->getMessage());
            $loi = 'Không thể xử lý yêu cầu kiểm định.';
        }
        $chiXem = $phieu && !in_array(
            $phieu['TrangThai'],
            ['Chờ kiểm định', 'Đang kiểm định'],
            true
        );
        /*
         * Khi POST bị lỗi, giữ nguyên phiên bản cũ.
         * Không âm thầm dùng phiên bản mới để ghi đè thay đổi từ tab khác.
         */
        $phienBanForm = $duLieuDaNhap !== null
            ? ($post['phienBan'] ?? '')
            : ($phieu['PhienBan'] ?? '');
        $duLieuTrang = [
            'hangHoa' => $phieu['HangHoa'] ?? [],
            'duLieuDaNhap' => $duLieuDaNhap,
            'chiXem' => (bool) $chiXem,
        ];

        // Chuẩn bị hàng hóa để PHP render ngay, kể cả khi JS chưa tải.
        if ($phieu) {
            foreach ($phieu['HangHoa'] as &$hang) {
                $maCT = $hang['MaCTKD'];
                if (!$chiXem && is_array($duLieuDaNhap) && isset($duLieuDaNhap[$maCT]) && is_array($duLieuDaNhap[$maCT])) {
                    $hang['NhomKiemDinh'] = [];
                    foreach ($duLieuDaNhap[$maCT] as $nhomNhap) {
                        if (!is_array($nhomNhap)) continue;
                        $hang['NhomKiemDinh'][] = [
                            'SoLuong' => is_scalar($nhomNhap['SoLuong'] ?? '') ? $nhomNhap['SoLuong'] : '',
                            'GhiChu' => is_scalar($nhomNhap['GhiChu'] ?? '') ? $nhomNhap['GhiChu'] : '',
                            'KetQuaTieuChi' => is_array($nhomNhap['TieuChi'] ?? null) ? $nhomNhap['TieuChi'] : [],
                        ];
                    }
                } elseif (!$chiXem && empty($hang['NhomKiemDinh'])) {
                    $hang['NhomKiemDinh'] = [[
                        'SoLuong' => $hang['SoLuongYeuCauKiemDinh'],
                        'GhiChu' => '', 'KetQuaTieuChi' => [],
                    ]];
                }
            }
            unset($hang);
        }
        return compact(
            'loi', 'thongBao', 'danhSach', 'phieu', 'duLieuDaNhap',
            'maPhieu', 'tuKhoa', 'trangThaiLoc', 'thamSoRouter',
            'urlDanhSach', 'chiXem', 'phienBanForm', 'duLieuTrang'
        );
    }
}
