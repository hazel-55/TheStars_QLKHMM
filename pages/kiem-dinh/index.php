<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../class/common/connect.php';

require_once __DIR__
    . '/../../class/nguyenThiHong/yeuCauKiemDinh/clskiemdinh.php';

require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
require_once __DIR__ . '/../../components/layout/footer.php';

$menuConfig = require __DIR__
    . '/../../class/common/menuconfig.php';

// Tạm thời chạy thử local.
$maNhanVien = 'NVKD001';
$userName = 'Nguyễn Thị Hồng';
$role = 'KIEM_DINH';

$roleConfig = $menuConfig['roles'][$role] ?? [
    'roleName' => 'Chưa xác định vai trò',
    'items' => [],
];

$tuKhoa = is_string($_GET['tuKhoa'] ?? null)
    ? trim($_GET['tuKhoa'])
    : '';

$trangThaiLoc = is_string($_GET['trangThai'] ?? null)
    ? $_GET['trangThai']
    : '';

$loi = '';
$danhSach = [];

$kiemDinh = new KiemDinh($conn);

try {
    $danhSach = $kiemDinh->layDanhSachPhieu(
        $maNhanVien,
        $tuKhoa,
        $trangThaiLoc
    );
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $loi = 'Không thể tải danh sách. Kiểm tra nhật ký PHP.';
} catch (RuntimeException $e) {
    $loi = $e->getMessage();
}

renderHeader(
    'Phiếu yêu cầu kiểm định',
    $userName,
    $roleConfig['roleName'],
    $menuConfig['baseUrl']
);

renderSidebar(
    $roleConfig['items'],
    'kiem-dinh',
    $roleConfig['roleName'],
    $menuConfig['bottomItems'] ?? []
);
?>

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        rtrim($menuConfig['baseUrl'], '/')
            . '/assets/css/kiem-dinh.css?v=4',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<main class="app-main">
    <div class="kd-page">
        <h1>Phiếu yêu cầu kiểm định</h1>

        <p class="kd-muted">
            Danh sách phiếu được phân công cho bạn.
        </p>

        <?php if ($loi !== ''): ?>
            <div class="kd-error" role="alert">
                <?= htmlspecialchars($loi, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="kd-card">
            <form
                method="get"
                action="index.php"
                class="kd-filters"
            >
                <input
                    type="text"
                    name="tuKhoa"
                    placeholder="Tìm mã phiếu..."
                    value="<?= htmlspecialchars(
                        $tuKhoa,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <select name="trangThai">
                    <option value="">Tất cả trạng thái</option>

                    <?php foreach ([
                        'Chờ kiểm định',
                        'Đang kiểm định',
                        'Hoàn tất kiểm định',
                        'Đã hủy'
                    ] as $trangThai): ?>
                        <option
                            value="<?= htmlspecialchars(
                                $trangThai,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $trangThaiLoc === $trangThai
                                ? 'selected'
                                : '' ?>
                        >
                            <?= htmlspecialchars(
                                $trangThai,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button
                    type="submit"
                    class="kd-button kd-primary"
                >
                    Tìm kiếm
                </button>

                <a class="kd-button" href="index.php">
                    Xóa bộ lọc
                </a>
            </form>

            <div class="kd-scroll">
                <table class="kd-list-table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Ngày lập</th>
                            <th>Người gửi</th>
                            <th>Mục đích kiểm định</th>
                            <th>Số mặt hàng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($danhSach as $dong): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $dong['MaPhieu'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $dong['NgayLap'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $dong['TenNguoiGui'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $dong['MucDichKD'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int) $dong['SoMatHang'] ?>
                                </td>

                                <td>
                                    <span class="kd-badge">
                                        <?= htmlspecialchars(
                                            $dong['TrangThai'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        class="kd-button"
                                        href="<?= htmlspecialchars(
                                            'chitiet.php?' . http_build_query([
                                                'maPhieu' => $dong['MaPhieu'],
                                                'tuKhoa' => $tuKhoa,
                                                'trangThai' => $trangThaiLoc,
                                            ]),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        Xem chi tiết →
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($danhSach)): ?>
                            <tr>
                                <td colspan="7">
                                    Không có phiếu phù hợp.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php renderFooter(); ?>