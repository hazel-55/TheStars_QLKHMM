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
<link rel="stylesheet"
      href="<?= h(rtrim($menuConfig['baseUrl'], '/') . '/assets/css/style.css') ?>">
<main class="app-main">
    <div class="ck-page">
        <div class="heading">
            <div>
                <p class="eyebrow">ĐIỀU CHUYỂN NỘI BỘ</p>
                <h1>Sửa yêu cầu chuyển kho</h1>
                <p>Chỉ được sửa, hủy hoặc gửi khi phiếu đang Nháp.</p>
            </div><a class="button" href="index.php">← Danh sách phiếu</a>
        </div>
        <?php if ($error): ?>
            <div class="notice error"><?= h($error) ?></div><?php endif ?>
        <?php if (!$edit): ?>
            <div class="notice error">Không tìm thấy phiếu. <a href="index.php">Về danh sách</a></div><?php endif; ?>
        <?php if ($edit && $edit['trangThai'] !== 'NHAP'): ?>
            <div class="notice">Phiếu đã gửi không được sửa. <a
                    href="chitiet.php?id=<?= rawurlencode($edit['maYeuCau']) ?>">Xem chi tiết</a></div><?php endif; ?>
        <?php if ($user && $warehouses && $edit && $edit['trangThai'] === 'NHAP'): ?>
            <form method="post" id="requestForm" action="<?= h(basename($_SERVER['SCRIPT_NAME'])) ?>"><input type="hidden"
                    name="csrf" value="<?= h($_SESSION['ck_csrf']) ?>"><input type="hidden" name="id"
                    value="<?= h($edit['maYeuCau'] ?? '') ?>">
                <section class="card">
                    <div class="card-title">
                        <h2>Thông tin yêu cầu</h2><span
                            class="badge <?= h($edit['trangThai'] ?? 'NHAP') ?>"><?= h($labels[$edit['trangThai'] ?? 'NHAP']) ?></span>
                    </div>
                    <div class="meta"><span>Mã phiếu: <b><?= h($edit['maYeuCau'] ?? 'Tự tạo khi lưu') ?></b></span><span>Ngày
                            lập: <?= h($edit['ngayLap'] ?? date('d/m/Y')) ?></span></div>
                    <fieldset <?= $readonly ? 'disabled' : '' ?>>
                        <div class="grid"><label>Kho xuất <em>*</em><select name="maKhoXuat" id="source"
                                    required><?php foreach ($warehouses as $w): ?>
                                        <option value="<?= h($w['maKho']) ?>" <?= $form['maKhoXuat'] === $w['maKho'] ? 'selected' : '' ?>>
                                            <?= h($w['tenKho']) ?></option><?php endforeach ?>
                                </select></label><label>Kho nhập <em>*</em><select name="maKhoNhap" id="destination"
                                    required><?php foreach ($warehouses as $w): ?>
                                        <option value="<?= h($w['maKho']) ?>" <?= $form['maKhoNhap'] === $w['maKho'] ? 'selected' : '' ?>>
                                            <?= h($w['tenKho']) ?></option><?php endforeach ?>
                                </select></label></div>
                        <label>Lý do chuyển kho <em>*</em><textarea name="lyDo" maxlength="500" rows="2" required
                                placeholder="Nhập lý do điều chuyển hàng hóa…"><?= h($form['lyDo']) ?></textarea></label>
                    </fieldset>
                </section>
                <section class="card">
                    <div class="card-title">
                        <div>
                            <h2>Chi tiết hàng hóa</h2>
                            <p>Số lượng khả dụng = tồn kho − số lượng đã giữ chỗ.</p>
                        </div><?php if (!$readonly): ?><button type="button" id="addRow">+ Thêm hàng</button><?php endif ?>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Hàng hóa / Biến thể</th>
                                    <th>Đơn vị</th>
                                    <th>Khả dụng hiện tại</th>
                                    <th>Số lượng chuyển</th>
                                    <th>Ghi chú</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="lines"></tbody>
                        </table>
                    </div>
                    <div class="form-bottom"><span id="summary"></span><span>Lập yêu cầu chưa làm thay đổi tồn kho.</span>
                    </div>
                </section>
                <?php if (!$readonly): ?>
                    <div class="actions"><button type="submit" name="action" value="cancel" formnovalidate class="danger"
                            <?= ($edit && ($edit['trangThai'] ?? '') === 'NHAP') ? '' : 'hidden' ?>>Hủy phiếu nháp</button>
                        <div><button type="submit" name="action" value="save">Lưu nháp</button><button type="submit"
                                name="action" value="send" class="primary">Gửi phê duyệt →</button></div>
                    </div><?php else: ?>
                    <div class="notice">Phiếu <?= h($labels[$edit['trangThai']]) ?> chỉ được xem, không thể sửa hoặc hủy.</div>
                <?php endif ?>
            </form>
        <?php endif; ?>
        <script>const CK = <?= json_encode(['products' => $products, 'stock' => $stock, 'rows' => $inputRows, 'readonly' => (bool) $readonly], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
        <script src="<?= h(rtrim($menuConfig['baseUrl'], '/') . '/assets/js/app.js') ?>"></script>
    </div>
</main>
<?php renderFooter(); ?>