<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../class/common/connect.php';
require_once __DIR__ . '/../../class/leHoangYen/yeuCauXuatNguyenLieu/clsxuatnguyenlieu.php';

$maxuong = 'XUONG001';
$xuatNguyenLieu = new XuatNguyenLieu($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$mayeucau = trim($_POST['mayeucau'] ?? '');

if ($mayeucau === '') {
    $_SESSION['thongbao_xuat'] = 'Không tìm thấy mã phiếu cần xóa.';
    $_SESSION['loai_thongbao_xuat'] = 'error';

    header('Location: index.php');
    exit;
}

try {
    // Lấy mã kế hoạch để quay lại đúng trang sau khi xóa.
    $phieu = $xuatNguyenLieu->layThongTinPhieu(
        $mayeucau,
        $maxuong
    );

    if (!$phieu) {
        throw new Exception(
            'Không tìm thấy phiếu hoặc phiếu không thuộc xưởng của bạn.'
        );
    }

    $makehoach = $phieu['makehoach'] ?? '';

    // Hàm này xóa chi tiết trước, sau đó xóa phiếu chính.
    $ketQua = $xuatNguyenLieu->xoaYeuCauXuat(
        $mayeucau,
        $maxuong
    );

    $_SESSION['thongbao_xuat'] = $ketQua['message'];
    $_SESSION['loai_thongbao_xuat'] =
        $ketQua['success'] ? 'success' : 'error';

    if ($makehoach !== '') {
        header(
            'Location: chitiet.php?makehoach=' . urlencode($makehoach)
        );
    } else {
        header('Location: index.php');
    }
    exit;
} catch (Throwable $e) {
    $_SESSION['thongbao_xuat'] = $e->getMessage();
    $_SESSION['loai_thongbao_xuat'] = 'error';

    header('Location: index.php');
    exit;
}
