<?php
declare(strict_types=1);

class ThongTinCaNhan
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function layThongTin(string $maTaiKhoan): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT MaTaiKhoan, HoTen, Email, NgaySinh,
                    SoDienThoai, VaiTro, BoPhan
             FROM NguoiDung
             WHERE MaTaiKhoan = ?'
        );

        $stmt->bind_param('s', $maTaiKhoan);
        $stmt->execute();

        $nguoiDung = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $nguoiDung ?: null;
    }

    public function chuanHoaDuLieu(array $duLieu): array
    {
        $ketQua = [];

        foreach (
            ['HoTen', 'Email', 'NgaySinh', 'SoDienThoai', 'VaiTro']
            as $ten
        ) {
            $giaTri = $duLieu[$ten] ?? '';

            $ketQua[$ten] = is_string($giaTri)
                ? trim($giaTri)
                : '';
        }

        return $ketQua;
    }

    public function kiemTraDuLieu(
        array $duLieu,
        array $danhSachVaiTro
    ): array {
        $loi = [];

        $doDaiTen = preg_match_all('/./us', $duLieu['HoTen']);

        if (
            $duLieu['HoTen'] === '' ||
            $doDaiTen === false ||
            $doDaiTen > 100
        ) {
            $loi['HoTen'] = 'Họ tên phải có từ 1 đến 100 ký tự hợp lệ.';
        }

        if (
            strlen($duLieu['Email']) > 254 ||
            !filter_var($duLieu['Email'], FILTER_VALIDATE_EMAIL)
        ) {
            $loi['Email'] = 'Vui lòng nhập email hợp lệ.';
        }

        if (
            $duLieu['SoDienThoai'] !== '' &&
            !preg_match(
                '/^\+?[0-9]{9,15}$/D',
                $duLieu['SoDienThoai']
            )
        ) {
            $loi['SoDienThoai'] =
                'Nhập 9–15 chữ số, có thể bắt đầu bằng +.';
        }

        if ($duLieu['NgaySinh'] !== '') {
            $ngaySinh = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $duLieu['NgaySinh']
            );

            $homNay = new DateTimeImmutable(
                'today',
                new DateTimeZone('Asia/Ho_Chi_Minh')
            );

            if (
                !$ngaySinh ||
                $ngaySinh->format('Y-m-d') !== $duLieu['NgaySinh'] ||
                $duLieu['NgaySinh'] < '1000-01-01' ||
                $duLieu['NgaySinh'] > $homNay->format('Y-m-d')
            ) {
                $loi['NgaySinh'] = 'Ngày sinh không hợp lệ.';
            }
        }

        if (
            !array_key_exists(
                $duLieu['VaiTro'],
                $danhSachVaiTro
            )
        ) {
            $loi['VaiTro'] = 'Vui lòng chọn vai trò hợp lệ.';
        }

        return $loi;
    }

    public function capNhat(
        string $maTaiKhoan,
        array $duLieu
    ): void {
        $hoTen = $duLieu['HoTen'];
        $email = $duLieu['Email'];
        $soDienThoai = $duLieu['SoDienThoai'];
        $vaiTro = $duLieu['VaiTro'];

        $ngaySinh = $duLieu['NgaySinh'] === ''
            ? null
            : $duLieu['NgaySinh'];

        $stmt = $this->conn->prepare(
            'UPDATE NguoiDung
             SET HoTen = ?,
                 Email = ?,
                 NgaySinh = ?,
                 SoDienThoai = ?,
                 VaiTro = ?
             WHERE MaTaiKhoan = ?'
        );

        $stmt->bind_param(
            'ssssss',
            $hoTen,
            $email,
            $ngaySinh,
            $soDienThoai,
            $vaiTro,
            $maTaiKhoan
        );

        $stmt->execute();
        $stmt->close();
    }

    public function dinhDangNgay(?string $ngay): string
    {
        if (!$ngay) {
            return 'Chưa cập nhật';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ngay);

        if (!$date || $date->format('Y-m-d') !== $ngay) {
            return 'Chưa cập nhật';
        }

        return $date->format('d/m/Y');
    }
}