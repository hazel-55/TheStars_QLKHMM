<?php
// Nạp file kết nối ở thư mục gốc.
require_once __DIR__ . '/../../index.php';
require_once __DIR__
    . '/../../class/NguyenThiHong/yeuCauKiemDinh/clskiemdinh.php';

// Kiểm tra đăng nhập và quyền xem phiếu tại đây.

$kiemDinh = new KiemDinh($conn);

$maPhieu = $_GET['maphieu'] ?? '';

if (!is_string($maPhieu) || trim($maPhieu) === '') {
    http_response_code(400);
    exit('Thiếu hoặc sai mã phiếu.');
}

$phieu = $kiemDinh->xemPhieu($maPhieu);

if ($phieu === null) {
    http_response_code(404);
    exit('Không tìm thấy phiếu.');
}
?>

<h1>Chi tiết phiếu yêu cầu kiểm định</h1>

<p>
    Mã phiếu:
    <?= htmlspecialchars($phieu['maphieu'], ENT_QUOTES, 'UTF-8') ?>
    Mục đích:
    <?= htmlspecialchars($phieu['mucdich'], ENT_QUOTES, 'UTF-8') ?>
</p>