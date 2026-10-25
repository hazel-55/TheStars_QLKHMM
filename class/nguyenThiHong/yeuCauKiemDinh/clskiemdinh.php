<?php

class KiemDinh
{
    private mysqli $conn;

    // Nhận kết nối đã tạo từ index.php.
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function xemPhieu(string $maPhieu): ?array
    {
        // Tên bảng/cột là ví dụ, sửa theo DB thực tế.
        $sql = "SELECT *
                FROM PhieuYeuCauKiemDinh
                WHERE maphieu= ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $maPhieu);
        $stmt->execute();

        $phieu = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return $phieu ?: null;
    }
}
?>