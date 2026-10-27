<?php
require_once __DIR__ . '/../../class/phanDaiDuong/clsChuyenKho.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
require_once __DIR__ . '/../../components/layout/footer.php';
$menuConfig = require __DIR__ . '/../../class/common/menuconfig.php';
// Chỉ điều chỉnh đường dẫn tại trang chuyển kho; không thay đổi menu chung.
$menuConfig['baseUrl'] = '/' . basename(dirname(__DIR__, 2));
$roleConfig = $menuConfig['roles']['QUAN_LY_KHO'];
// Thêm menu cục bộ, KHÔNG sửa menuconfig.php của main.
$roleConfig['items'][] = [
    'key' => 'chuyen-kho',
    'label' => 'Lập yêu cầu chuyển kho',
    'url' => rtrim($menuConfig['baseUrl'], '/') . '/pages/chuyen-kho/index.php',
    'icon' => '⇄'
];
renderHeader(
    'Lập yêu cầu chuyển kho',
    $user['hoTen'] ?? 'Quản lý kho',
    $roleConfig['roleName'],
    $menuConfig['baseUrl']
);
renderSidebar(
    $roleConfig['items'],
    'chuyen-kho',
    $roleConfig['roleName'],
    $menuConfig['bottomItems'] ?? []
);
?>
<!-- CSS riêng của chức năng Lập yêu cầu chuyển kho -->
<link rel="stylesheet"
      href="<?= h(rtrim($menuConfig['baseUrl'], '/') . '/assets/css/style.css') ?>">
<main class="app-main">
    <div class="ck-page">
        <div class="heading">
            <div>
                <p class="eyebrow">ĐIỀU CHUYỂN NỘI BỘ</p>
                <h1>Danh sách yêu cầu chuyển kho</h1>
                <p>Theo dõi, tìm kiếm và quản lý các phiếu chuyển kho.</p>
            </div><a class="button primary" href="them.php">+ Tạo yêu cầu mới</a>
        </div>
        <?php if ($flash): ?>
            <div class="notice success"><?= h($flash) ?></div><?php endif ?>
        <?php if ($error): ?>
            <div class="notice error"><?= h($error) ?></div><?php endif ?>
        <div class="stats">
            <section><span>Tổng yêu cầu</span><strong><?= count($list) ?></strong></section>
            <section><span>Bản nháp</span><strong><?= $counts['NHAP'] ?></strong></section>
            <section><span>Chờ phê duyệt</span><strong><?= $counts['CHO_PHE_DUYET'] ?></strong></section>
        </div>
        <section class="card history">
            <div class="card-title">
                <h2>Danh sách yêu cầu của tôi</h2><input id="search" placeholder="Tìm mã phiếu, kho…"
                    aria-label="Tìm yêu cầu">
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mã yêu cầu</th>
                            <th>Kho xuất → Kho nhập</th>
                            <th>Ngày lập</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="history"><?php foreach ($list as $r): ?>
                            <tr>
                                <td><b><?= h($r['maYeuCau']) ?></b></td>
                                <td><?= h($r['khoXuat']) ?> → <?= h($r['khoNhap']) ?></td>
                                <td><?= h($r['ngayLap']) ?></td>
                                <td><span class="badge <?= h($r['trangThai']) ?>"><?= h($labels[$r['trangThai']]) ?></span></td>
                                <td><a
                                        href="<?= $r['trangThai'] === 'NHAP' ? 'sua.php' : 'chitiet.php' ?>?id=<?= rawurlencode($r['maYeuCau']) ?>"><?= $r['trangThai'] === 'NHAP' ? 'Xem / Sửa' : 'Chi tiết' ?></a>
                                </td>
                            </tr><?php endforeach ?>
                    </tbody>
                </table><?php if (!$list): ?>
                    <p class="empty">Chưa có yêu cầu nào.</p><?php endif ?>
                <p id="noResults" class="empty" hidden>Không có yêu cầu phù hợp.</p>
            </div>
        </section>
    </div>
</main>
<?php renderFooter(); ?>
<script>const CK = <?= json_encode(['products' => $products, 'stock' => $stock, 'rows' => $inputRows, 'readonly' => (bool) $readonly], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= h(rtrim($menuConfig['baseUrl'], '/') . '/assets/js/app.js') ?>"></script>