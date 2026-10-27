
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../class/common/connect.php';
require_once __DIR__ . '/../../class/leHoangYen/yeuCauXuatNguyenLieu/clsxuatnguyenlieu.php';

$maxuong = 'XUONG001';

// Chỉ xử lý khi biểu mẫu gửi bằng POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Lấy dữ liệu từ biểu mẫu
$makehoach = isset($_POST['makehoach'])
    ? trim($_POST['makehoach'])
    : '';

$ghichu = isset($_POST['ghichu'])
    ? trim($_POST['ghichu'])
    : '';

// Kiểm tra mã kế hoạch
if ($makehoach === '') {
    $_SESSION['thongbao_xuat'] = 'Không tìm thấy mã kế hoạch sản xuất.';
    $_SESSION['loai_thongbao_xuat'] = 'error';

    header('Location: index.php');
    exit;
}

// Kiểm tra độ dài ghi chú
if (mb_strlen($ghichu, 'UTF-8') > 500) {
    $_SESSION['thongbao_xuat'] = 'Ghi chú không được vượt quá 500 ký tự.';
    $_SESSION['loai_thongbao_xuat'] = 'error';

    header('Location: chitiet.php?makehoach=' . urlencode($makehoach));
    exit;
}

try {
    $xuatNguyenLieu = new XuatNguyenLieu($conn);

    $ketQua = $xuatNguyenLieu->lapYeuCauXuat(
        $makehoach,
        $maxuong,
        $ghichu
    );

    if (!empty($ketQua['success'])) {
        $_SESSION['thongbao_xuat'] = $ketQua['message'];
        $_SESSION['loai_thongbao_xuat'] = 'success';

        // Lập phiếu thành công: chuyển đến danh sách phiếu
        header('Location: danh_sach_phieu.php');
        exit;
    }

    // Lập phiếu thất bại: quay lại chi tiết kế hoạch
    $_SESSION['thongbao_xuat'] = $ketQua['message']
        ?? 'Không thể lập yêu cầu xuất nguyên liệu.';

    $_SESSION['loai_thongbao_xuat'] = 'error';

    header('Location: chitiet.php?makehoach=' . urlencode($makehoach));
    exit;
} catch (Throwable $e) {
    $_SESSION['thongbao_xuat'] = 'Có lỗi xảy ra khi lưu yêu cầu xuất nguyên liệu.';
    $_SESSION['loai_thongbao_xuat'] = 'error';

    header('Location: chitiet.php?makehoach=' . urlencode($makehoach));
    exit;
}
