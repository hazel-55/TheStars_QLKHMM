<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../class/common/connect.php';
require_once __DIR__ . '/../../class/leHoangYen/yeuCauXuatNguyenLieu/clsxuatnguyenlieu.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
require_once __DIR__ . '/../../components/layout/footer.php';

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

$danhSachKeHoach = [];
$loi = '';

try {
    $danhSachKeHoach = $xuatNguyenLieu->layDanhSachKeHoach(
        $maxuong,
        $tukhoa
    );
} catch (Throwable $e) {
    $loi = 'Không thể tải danh sách kế hoạch sản xuất. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
}

$thongBao = '';
$loaiThongBao = '';

if (isset($_SESSION['thongbao_xuat'])) {
    $thongBao = $_SESSION['thongbao_xuat'];

    $loaiThongBao = isset($_SESSION['loai_thongbao_xuat'])
        ? $_SESSION['loai_thongbao_xuat']
        : 'success';

    unset($_SESSION['thongbao_xuat']);
    unset($_SESSION['loai_thongbao_xuat']);
}


renderHeader(
    'Lập yêu cầu xuất nguyên liệu',
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
        <div class="xnl-card">

            <div class="xnl-header">
                <div>
                    <h1 class="xnl-title">Lập yêu cầu xuất nguyên liệu</h1>
                    <p class="xnl-muted">
                        Chỉ được lập yêu cầu xuất nguyên liệu từ kế hoạch sản xuất đã được duyệt.
                    </p>
                </div>

                <a class="xnl-button"
                    href="danh_sach_phieu.php">
                    Danh sách phiếu yêu cầu
                </a>
            </div>

            <?php if ($thongBao !== ''): ?>
                <div class="<?= $loaiThongBao === 'error' ? 'xnl-error' : 'xnl-success' ?>">
                    <?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($loi !== ''): ?>
                <div class="xnl-error">
                    <?= htmlspecialchars($loi, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>

                <form method="get" action="index.php" class="xnl-search">
                    <input
                        type="text"
                        name="tukhoa"
                        value="<?= htmlspecialchars($tukhoa, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nhập mã kế hoạch để tìm kiếm">

                    <button type="submit" class="xnl-button xnl-primary">
                        Tìm kiếm
                    </button>

                    <a href="index.php" class="xnl-button">
                        Làm mới
                    </a>
                </form>

                <div class="xnl-scroll">
                    <table class="xnl-list-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã kế hoạch</th>
                                <th>Mã xưởng</th>
                                <th>Ngày lập</th>
                                <th>Ngày phê duyệt</th>
                                <th>Trạng thái kế hoạch</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($danhSachKeHoach)): ?>
                                <tr>
                                    <td colspan="7" class="xnl-empty">
                                        Không tìm thấy kế hoạch sản xuất phù hợp.
                                    </td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($danhSachKeHoach as $index => $keHoach): ?>
                                    <?php
                                    $trangThai = trim(
                                        (string) ($keHoach['trangthaipheduyet'] ?? '')
                                    );

                                    $trangThaiChuanHoa = function_exists('mb_strtolower')
                                        ? mb_strtolower($trangThai, 'UTF-8')
                                        : strtolower($trangThai);

                                    $daDuyet = in_array(
                                        $trangThaiChuanHoa,
                                        ['đã duyệt', 'da duyet'],
                                        true
                                    );

                                    $daCoPhieu = !empty($keHoach['mayeucau']);
                                    ?>

                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $keHoach['makehoach'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $keHoach['maxuong'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($keHoach['ngaylap'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) ($keHoach['ngaypheduyet'] ?? ''),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="xnl-badge">
                                                <?= htmlspecialchars(
                                                    $trangThai,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if (!$daDuyet): ?>

                                                <span class="xnl-muted">
                                                    Chưa được duyệt
                                                </span>

                                            <?php elseif ($daCoPhieu): ?>

                                                <a
                                                    class="xnl-button"
                                                    href="chitiet.php?makehoach=<?= urlencode((string) $keHoach['makehoach']) ?>&xemphieu=1">
                                                    Xem chi tiết
                                                </a>

                                            <?php else: ?>

                                                <a
                                                    class="xnl-button xnl-primary"
                                                    href="chitiet.php?makehoach=<?= urlencode((string) $keHoach['makehoach']) ?>">
                                                    Xem chi tiết &amp; lập yêu cầu
                                                </a>

                                            <?php endif; ?>
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

<?php renderFooter(); ?>