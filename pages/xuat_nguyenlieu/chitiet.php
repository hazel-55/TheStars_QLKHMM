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

$makehoach = isset($_GET['makehoach'])
    ? trim($_GET['makehoach'])
    : '';

$keHoach = null;
$chiTietKeHoach = [];
$phieu = null;
$chiTietPhieu = [];
$loi = '';

if ($makehoach === '') {
    $loi = 'Không tìm thấy mã kế hoạch sản xuất.';
} else {
    try {
        // Chỉ lấy kế hoạch đã được duyệt thuộc xưởng hiện tại.
        $keHoach = $xuatNguyenLieu->layThongTinKeHoach(
            $makehoach,
            $maxuong
        );

        if (!$keHoach) {
            $loi = 'Kế hoạch không tồn tại, không thuộc xưởng của bạn hoặc chưa được duyệt.';
        } else {
            // Kiểm tra kế hoạch đã có phiếu yêu cầu chưa.
            $phieu = $xuatNguyenLieu->layPhieuTheoKeHoach(
                $makehoach
            );

            if ($phieu) {
                $chiTietPhieu = $xuatNguyenLieu->layChiTietPhieu(
                    $phieu['mayeucau']
                );

                if (empty($chiTietPhieu)) {
                    $loi = 'Phiếu yêu cầu đã tồn tại nhưng chưa có chi tiết nguyên liệu.';
                }
            } else {
                // Chỉ tải chi tiết kế hoạch khi chưa lập phiếu.
                $chiTietKeHoach = $xuatNguyenLieu->layChiTietKeHoach(
                    $makehoach
                );

                if (empty($chiTietKeHoach)) {
                    $loi = 'Kế hoạch này chưa có nguyên liệu cần xuất.';
                }
            }
        }
    } catch (Throwable $e) {
        $loi = 'Lỗi chi tiết: ' . $e->getMessage();
    }
}

renderHeader(
    'Chi tiết kế hoạch sản xuất',
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

        <a class="xnl-back" href="index.php">
            ← Quay lại danh sách kế hoạch
        </a>

        <div class="xnl-card">

            <div class="xnl-header">
                <div>
                    <h1 class="xnl-title">Chi tiết kế hoạch sản xuất</h1>
                    <p class="xnl-muted">
                        Xem thông tin kế hoạch và yêu cầu xuất nguyên liệu.
                    </p>
                </div>
            </div>

            <?php if (isset($_SESSION['thongbao_xuat'])): ?>
                <div class="<?= ($_SESSION['loai_thongbao_xuat'] ?? '') === 'success'
                                ? 'xnl-success'
                                : 'xnl-error' ?>">
                    <?= htmlspecialchars(
                        $_SESSION['thongbao_xuat'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <?php
                unset($_SESSION['thongbao_xuat']);
                unset($_SESSION['loai_thongbao_xuat']);
                ?>
            <?php endif; ?>

            <?php if ($loi !== ''): ?>

                <div class="xnl-error">
                    <?= htmlspecialchars($loi, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php else: ?>

                <!-- THÔNG TIN KẾ HOẠCH -->
                <div class="xnl-info">

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Mã kế hoạch</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars(
                                (string) $keHoach['makehoach'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Mã xưởng</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars(
                                (string) $keHoach['maxuong'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Ngày lập</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars(
                                (string) ($keHoach['ngaylap'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                    <div class="xnl-info-item">
                        <span class="xnl-info-label">Trạng thái phê duyệt</span>
                        <div class="xnl-info-value">
                            <?= htmlspecialchars(
                                (string) ($keHoach['trangthaipheduyet'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    </div>

                </div>

                <?php if ($phieu): ?>

                    <!-- TRƯỜNG HỢP ĐÃ CÓ PHIẾU -->
                    <h2 class="xnl-section-title">
                        Thông tin phiếu yêu cầu xuất nguyên liệu
                    </h2>

                    <div class="xnl-info">

                        <div class="xnl-info-item">
                            <span class="xnl-info-label">Mã yêu cầu</span>
                            <div class="xnl-info-value">
                                <?= htmlspecialchars(
                                    (string) $phieu['mayeucau'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </div>

                        <div class="xnl-info-item">
                            <span class="xnl-info-label">Ngày lập phiếu</span>
                            <div class="xnl-info-value">
                                <?= htmlspecialchars(
                                    (string) ($phieu['ngaylap'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </div>

                        <div class="xnl-info-item">
                            <span class="xnl-info-label">Trạng thái phiếu</span>
                            <div class="xnl-info-value">
                                <?= htmlspecialchars(
                                    (string) ($phieu['trangthai'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </div>

                        <div class="xnl-info-item">
                            <span class="xnl-info-label">Ghi chú</span>
                            <div class="xnl-info-value">
                                <?= htmlspecialchars(
                                    (string) ($phieu['ghichu'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?: 'Không có ghi chú' ?>
                            </div>
                        </div>

                    </div>

                    <h2 class="xnl-section-title">
                        Chi tiết nguyên liệu đã yêu cầu
                    </h2>

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
                                                (string) $chiTiet['manguyenlieu'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $chiTiet['tennguyenlieu'],
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
                                                (string) $chiTiet['soluongcanxuat'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $chiTiet['soluongyeucau'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="xnl-actions">
                        <a
                            class="xnl-button xnl-primary"
                            href="suaphieu.php?mayeucau=<?= urlencode($phieu['mayeucau']) ?>">
                            Chỉnh sửa phiếu
                        </a>

                        <form
                            method="post"
                            action="xoaphieu.php"
                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu yêu cầu này không?');">

                            <input
                                type="hidden"
                                name="mayeucau"
                                value="<?= htmlspecialchars(
                                            (string) $phieu['mayeucau'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                            <button type="submit" class="xnl-button">
                                Xóa phiếu
                            </button>
                        </form>

                        <a href="danh_sach_phieu.php" class="xnl-button">
                            Danh sách phiếu
                        </a>
                    </div>

                <?php else: ?>

                    <!-- TRƯỜNG HỢP CHƯA CÓ PHIẾU -->
                    <h2 class="xnl-section-title">
                        Danh sách nguyên liệu cần xuất
                    </h2>

                    <div class="xnl-scroll">
                        <table class="xnl-list-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã nguyên liệu</th>
                                    <th>Tên nguyên liệu</th>
                                    <th>Đơn vị tính</th>
                                    <th>Số lượng cần xuất</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($chiTietKeHoach as $index => $chiTiet): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $chiTiet['manguyenlieu'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $chiTiet['tennguyenlieu'],
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
                                                (string) $chiTiet['soluongcanxuat'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <form
                        method="post"
                        action="lap_yeu_cau.php"
                        data-xnl-form>

                        <input
                            type="hidden"
                            name="makehoach"
                            value="<?= htmlspecialchars(
                                        (string) $keHoach['makehoach'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                        <div class="xnl-form-group" style="margin-top: 24px;">
                            <label for="ghichu">Ghi chú</label>

                            <textarea
                                id="ghichu"
                                name="ghichu"
                                maxlength="500"
                                rows="4"
                                placeholder="Nhập ghi chú nếu có..."></textarea>

                            <p class="xnl-muted">
                                Ghi chú không bắt buộc, tối đa 500 ký tự.
                            </p>
                        </div>

                        <div class="xnl-actions">
                            <button
                                type="submit"
                                class="xnl-button xnl-primary"
                                data-xnl-submit>
                                Lập yêu cầu xuất
                            </button>

                            <a href="index.php" class="xnl-button">
                                Hủy
                            </a>
                        </div>

                    </form>

                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>
</div>

<script src="<?= htmlspecialchars($baseUrl . '/assets/js/xuat_nguyen_lieu.js?v=1', ENT_QUOTES, 'UTF-8') ?>"></script>

<?php require_once __DIR__ . '/../../components/layout/footer.php'; ?>
```