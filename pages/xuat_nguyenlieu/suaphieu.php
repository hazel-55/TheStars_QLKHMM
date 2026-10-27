<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../class/common/connect.php';
require_once __DIR__ . '/../../class/leHoangYen/yeuCauXuatNguyenLieu/clsxuatnguyenlieu.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';

$menuConfig = require __DIR__ . '/../../class/common/menuconfig.php';

$baseUrl = $menuConfig['baseUrl'];
$maxuong = 'XUONG001';
$username = 'Quản lý xưởng';


$maNhanVien = 'NVX001';
$userName = 'Lê Hoàng Yến';
$role = 'QUAN_LY_XUONG';

$baseUrl = $menuConfig['baseUrl'];
$maxuong = 'XUONG001';

$roleConfig = $menuConfig['roles'][$role] ?? [
    'roleName' => 'Chưa xác định vai trò',
    'items' => [],
];

$xuatNguyenLieu = new XuatNguyenLieu($conn);

$mayeucau = trim($_GET['mayeucau'] ?? $_POST['mayeucau'] ?? '');
$makehoach = '';

$phieu = null;
$chiTietPhieu = [];
$thongBao = '';
$loaiThongBao = 'error';

if ($mayeucau === '') {
    $thongBao = 'Không tìm thấy mã phiếu yêu cầu. Vui lòng quay lại danh sách.';
} else {
    try {
        $phieu = $xuatNguyenLieu->layThongTinPhieu(
            $mayeucau,
            $maxuong
        );

        if (!$phieu) {
            $thongBao = 'Không tìm thấy phiếu hoặc phiếu không thuộc xưởng của bạn.';
        } else {
            // Lấy mã kế hoạch theo cả tên cột thường và tên cột viết hoa.
          $makehoach = trim((string) (
    $phieu['makehoach']
    ?? $phieu['MaKeHoach']
    ?? $phieu['MAKEHOACH']
    ?? ''
));
            // Xử lý lưu thay đổi.
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ghichu = trim($_POST['ghichu'] ?? '');
                $soLuongTheoChiTiet = $_POST['soLuong'] ?? [];

                $ketQua = $xuatNguyenLieu->suaYeuCauXuat(
                    $mayeucau,
                    $maxuong,
                    $ghichu,
                    $soLuongTheoChiTiet
                );

                $thongBao = $ketQua['message'] ?? 'Không có thông báo kết quả.';
                $loaiThongBao = !empty($ketQua['success'])
                    ? 'success'
                    : 'error';

                if (!empty($ketQua['success'])) {
                    $_SESSION['thongbao_xuat'] = $thongBao;
                    $_SESSION['loai_thongbao_xuat'] = 'success';

                   $makehoachSauKhiLuu = trim((string) (
    $_POST['makehoach'] ?? $makehoach
));

if ($makehoachSauKhiLuu !== '') {
    header(
        'Location: chitiet.php?makehoach='
        . urlencode($makehoachSauKhiLuu)
    );
} else {
    header('Location: index.php');
}

exit;
                    exit;
                }
            }

            // Lấy chi tiết phiếu để hiển thị.
            $chiTietPhieu = $xuatNguyenLieu->layChiTietPhieu(
                $mayeucau
            );

            if (empty($chiTietPhieu)) {
                $thongBao = 'Phiếu hiện không có chi tiết nguyên liệu để chỉnh sửa.';
            }
        }
    } catch (Throwable $e) {
        $thongBao = 'Lỗi xử lý phiếu yêu cầu: ' . $e->getMessage();
    }
}

renderHeader(
    'Chỉnh sửa phiếu yêu cầu',
    $userName,
    $roleConfig['roleName'],
    $baseUrl
);

renderSidebar(
    $roleConfig['items'],
    'xuat-nguyen-lieu',
    $roleConfig['roleName'],
    []
);
?>

<link rel="stylesheet"
    href="<?= htmlspecialchars($baseUrl . '/assets/css/xuat_nguyen_lieu.css?v=3', ENT_QUOTES, 'UTF-8') ?>">

<div class="app-main">
    <main class="xnl-page">
        <a class="xnl-back"
            href="<?= htmlspecialchars($baseUrl . '/page/xuat_nguyen_lieu/index.php', ENT_QUOTES, 'UTF-8') ?>">
            ← Quay lại danh sách kế hoạch
        </a>

        <div class="xnl-card">
            <h1 class="xnl-title">Chỉnh sửa phiếu yêu cầu xuất nguyên liệu</h1>

            <p class="xnl-muted">
                Cập nhật ghi chú và số lượng nguyên liệu cần xuất.
            </p>

            <?php if ($thongBao !== ''): ?>
                <div class="<?= $loaiThongBao === 'success' ? 'xnl-success' : 'xnl-error' ?>">
                    <?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($phieu && !empty($chiTietPhieu)): ?>
                <div class="xnl-info">
                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Mã yêu cầu</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars((string) $mayeucau, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Mã kế hoạch</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars((string) $makehoach, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Ngày lập</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars(
                                (string) ($phieu['ngaylap'] ?? $phieu['NgayLap'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Trạng thái</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars(
                                (string) ($phieu['trangthai'] ?? $phieu['TrangThai'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>
                </div>

               <form method="post"
    action="suaphieu.php?mayeucau=<?= urlencode($mayeucau) ?>"
    data-xnl-form
    data-action="edit">

    <input type="hidden" name="mayeucau"
        value="<?= htmlspecialchars($mayeucau, ENT_QUOTES, 'UTF-8') ?>">

    <input type="hidden" name="makehoach"
        value="<?= htmlspecialchars($makehoach, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="xnl-form-group">
                        <label for="ghichu">Ghi chú</label>
                        <textarea
                            id="ghichu"
                            name="ghichu"
                            maxlength="500"
                            rows="4"><?= htmlspecialchars(
                                            (string) ($phieu['ghichu'] ?? $phieu['GhiChu'] ?? ''),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?></textarea>
                    </div>

                    <h2 class="xnl-section-title">Chi tiết nguyên liệu</h2>

                    <div class="xnl-scroll">
                        <table class="xnl-list-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã nguyên liệu</th>
                                    <th>Tên nguyên liệu</th>
                                    <th>Đơn vị tính</th>
                                    <th>Số lượng theo kế hoạch</th>
                                    <th>Số lượng yêu cầu</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($chiTietPhieu as $index => $chiTiet): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($chiTiet['manguyenlieu'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($chiTiet['tennguyenlieu'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($chiTiet['donvitinh'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($chiTiet['soluongcanxuat'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <input
                                                type="number"
                                                name="soLuong[<?= htmlspecialchars(
                                                                    (string) $chiTiet['machitietkehoach'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>]"
                                                value="<?= htmlspecialchars(
                                                            (string) $chiTiet['soluongyeucau'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                min="0.01"
                                                step="0.01"
                                                required>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="xnl-actions">
                        <button
                            type="submit"
                            class="xnl-button xnl-primary"
                            data-xnl-submit>
                            Lưu thay đổi
                        </button>

                        <a class="xnl-button"
                            href="chitiet.php?makehoach=<?= urlencode((string) $makehoach) ?>">
                            Hủy
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="<?= htmlspecialchars($baseUrl . '/assets/js/xuat_nguyen_lieu.js?v=2', ENT_QUOTES, 'UTF-8') ?>"></script>

<?php require_once __DIR__ . '/../../components/layout/footer.php'; ?>