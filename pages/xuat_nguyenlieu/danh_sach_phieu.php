```php
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

$tukhoa = isset($_GET['tukhoa'])
    ? trim($_GET['tukhoa'])
    : '';

$dsPhieu = [];
$loi = '';

try {
    $dsPhieu = $xuatNguyenLieu->layDanhSachPhieu(
        $maxuong,
        $tukhoa
    );
} catch (Throwable $e) {
    $loi = 'Không thể tải danh sách phiếu yêu cầu. Vui lòng kiểm tra cơ sở dữ liệu.';
}

renderHeader(
    'Danh sách phiếu yêu cầu xuất nguyên liệu',
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
    href="<?= htmlspecialchars($baseUrl . '/assets/css/xuat_nguyen_lieu.css?v=4', ENT_QUOTES, 'UTF-8') ?>">

<div class="app-main">
    <main class="xnl-page">

        <a class="xnl-back" href="index.php">
            ← Quay lại danh sách kế hoạch
        </a>

        <div class="xnl-card">

            <div class="xnl-header">
                <div>
                    <h1 class="xnl-title">
                        Danh sách phiếu yêu cầu xuất nguyên liệu
                    </h1>

                    <p class="xnl-muted">
                        Theo dõi các phiếu yêu cầu xuất nguyên liệu của xưởng.
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

                <form method="get" action="danh_sach_phieu.php">
                    <div class="xnl-form-group">
                        <label for="tukhoa">Tìm kiếm phiếu</label>

                        <input
                            type="text"
                            id="tukhoa"
                            name="tukhoa"
                            value="<?= htmlspecialchars($tukhoa, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Nhập mã phiếu hoặc mã kế hoạch">

                        <button type="submit" class="xnl-button xnl-primary">
                            Tìm kiếm
                        </button>

                        <a href="danh_sach_phieu.php" class="xnl-button">
                            Làm mới
                        </a>
                    </div>
                </form>

                <div class="xnl-scroll">
                    <table class="xnl-list-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã yêu cầu</th>
                                <th>Mã kế hoạch</th>
                                <th>Ngày lập</th>
                                <th>Ghi chú</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($dsPhieu)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">
                                        Chưa có phiếu yêu cầu xuất nguyên liệu.
                                    </td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($dsPhieu as $index => $phieu): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $phieu['mayeucau'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $phieu['makehoach'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($phieu['ngaylap'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($phieu['ghichu'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($phieu['trangthai'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <div class="xnl-actions">
                                                <a
                                                    class="xnl-button"
                                                    href="chitiet.php?makehoach=<?= urlencode((string) $phieu['makehoach']) ?>&amp;xemphieu=1">
                                                    Xem
                                                </a>

                                                <a
                                                    class="xnl-button xnl-primary"
                                                    href="sua_yeu_cau.php?mayeucau=<?= urlencode((string) $phieu['mayeucau']) ?>">
                                                    Sửa
                                                </a>

                                                <form
                                                    method="post"
                                                    action="xoa_yeu_cau.php"
                                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu này không?');">

                                                    <input
                                                        type="hidden"
                                                        name="mayeucau"
                                                        value="<?= htmlspecialchars(
                                                                    (string) $phieu['mayeucau'],
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">

                                                    <button type="submit" class="xnl-button">
                                                        Xóa
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../components/layout/footer.php'; ?>
```