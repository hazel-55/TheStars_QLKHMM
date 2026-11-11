<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
ntpYeuCauVaiTro('QUAN_LY_XUONG');
$user = ntpTaiKhoan();
$uid = (int)$user['MaTaiKhoan'];
$id = isset($_GET['id']) ? ntpMaDuongDan($_GET['id']) : 0;
$flash = ntpLayThongBao();
$phieu = null; $rows = []; $error = ''; $thanhPham = [];
try {
    $thanhPham = $ntpModel->layThanhPham();
    if ($id) {
        $phieu = $ntpModel->layPhieu($id, $uid);
        if (!$phieu) { http_response_code(404); exit('Không tìm thấy phiếu thuộc quản lý xưởng hiện tại.'); }
        if ($phieu['TrangThai'] !== 'Nháp') { http_response_code(403); exit('Phiếu đã gửi kiểm định không được sửa.'); }
        $rows = $ntpModel->layChiTiet($id);
    }
} catch (Throwable $e) {
    error_log('[NTP form] ' . $e->getMessage());
    $error = 'Không thể tải danh sách thành phẩm. Hãy kiểm tra bảng ThanhPham.';
}
$ngay = $phieu['NgayHoanThanhSanXuat'] ?? date('Y-m-d');
$ghiChu = $phieu['GhiChu'] ?? '';
$old = $_SESSION['thestars_nhap_tp_old'] ?? null;
unset($_SESSION['thestars_nhap_tp_old']);
if (is_array($old)) {
    $ngay = is_string($old['NgayHoanThanhSanXuat'] ?? null) ? $old['NgayHoanThanhSanXuat'] : $ngay;
    $ghiChu = is_string($old['GhiChu'] ?? null) ? $old['GhiChu'] : $ghiChu;
    $rows = [];
    foreach (($old['MaThanhPham'] ?? []) as $index => $itemId) {
        $rows[] = ['MaThanhPham' => $itemId, 'SoLuongYeuCau' => $old['SoLuongYeuCau'][$index] ?? ''];
    }
}
if (!$rows) $rows = [['MaThanhPham' => '', 'SoLuongYeuCau' => '']];
ntpMoTrang($id ? 'Chỉnh sửa phiếu Nháp' : 'Lập phiếu yêu cầu nhập thành phẩm');
?>
<main class="app-main"><div class="ntp-wrap">
  <a class="ntp-back" href="<?= $id ? 'chi-tiet.php?id=' . $id : 'index.php' ?>">← Quay lại danh sách yêu cầu</a>
  <div class="ntp-page-head"><div><div class="ntp-eyebrow">QUẢN LÝ XƯỞNG / <?= $id ? 'CHỈNH SỬA BẢN NHÁP' : 'TẠO PHIẾU MỚI' ?></div>
    <h1><?= $id ? 'Chỉnh sửa ' . ntpHtml(ntpMaPhieu($id)) : 'Lập phiếu yêu cầu nhập thành phẩm' ?></h1>
    <p class="ntp-lead">Điền thông tin lô sản xuất và danh sách thành phẩm cần đề nghị nhập kho.</p></div></div>
  <div class="ntp-stepper"><div class="ntp-step active"><span class="ntp-step-num">1</span>Thông tin yêu cầu</div><span class="ntp-step-sep"></span><div class="ntp-step active"><span class="ntp-step-num">2</span>Thành phẩm</div><span class="ntp-step-sep"></span><div class="ntp-step"><span class="ntp-step-num">3</span>Gửi kiểm định</div></div>
  <?php if ($flash): ?><div class="ntp-alert <?= $flash[0] === 'error' ? 'error' : 'success' ?>" role="alert"><?= ntpHtml($flash[1]) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="ntp-alert error" role="alert"><?= ntpHtml($error) ?></div><?php endif; ?>
  <?php if (!$thanhPham): ?><div class="ntp-alert error">Danh mục thành phẩm chưa có dữ liệu. Vui lòng import các dòng mẫu trong bảng <strong>ThanhPham</strong> trước khi lập phiếu.</div><?php endif; ?>
  <div class="ntp-alert error" id="ntp-client-error" hidden role="alert"></div>
  <form method="post" action="xu-ly.php" id="ntp-form">
    <input type="hidden" name="csrf" value="<?= ntpHtml(ntpCsrf()) ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <section class="ntp-card"><div class="ntp-section-kicker"><span class="ntp-section-number">01</span><h2>Thông tin chung</h2></div>
      <div class="ntp-grid">
        <label>Quản lý xưởng / Người lập<input type="text" value="<?= ntpHtml($user['TenDangNhap']) ?>" readonly><small class="ntp-label-sub">Tự lấy theo tài khoản quản lý xưởng mẫu (chế độ demo không đăng nhập).</small></label>
        <label>Ngày hoàn thành sản xuất <span style="color:#c04452">*</span><input type="date" name="ngayHoanThanh" value="<?= ntpHtml($ngay) ?>" max="<?= date('Y-m-d') ?>" required><small class="ntp-label-sub">Ngày sản xuất hoàn tất, không được sau ngày hôm nay.</small></label>
      </div>
      <label>Ghi chú (không bắt buộc)<textarea name="ghiChu" rows="3" maxlength="500" placeholder="Ví dụ: Lô thành phẩm ca sáng, đã đóng gói và sẵn sàng kiểm định..."><?= ntpHtml($ghiChu) ?></textarea><small class="ntp-label-sub">Tối đa 500 ký tự.</small></label>
    </section>
    <section class="ntp-card"><div class="ntp-card-header"><div class="ntp-section-kicker" style="margin-bottom:0"><span class="ntp-section-number">02</span><div><h2>Danh sách thành phẩm</h2><p class="ntp-muted">Chọn thành phẩm và điền số lượng yêu cầu nhập.</p></div></div><button type="button" id="ntp-add" class="ntp-btn">＋ Thêm thành phẩm</button></div>
      <div class="ntp-table-scroll"><table class="ntp-table ntp-entry-table"><thead><tr><th>STT</th><th>Thành phẩm <span style="color:#ba4653">*</span></th><th>Đơn vị tính</th><th>Số lượng <span style="color:#ba4653">*</span></th><th>Thao tác</th></tr></thead><tbody id="ntp-lines">
      <?php foreach ($rows as $i => $item): ?><tr>
        <td class="ntp-number"><?= $i + 1 ?></td>
        <td><select name="maThanhPham[]" class="ntp-product" aria-label="Thành phẩm dòng <?= $i + 1 ?>" required><option value="">— Chọn thành phẩm —</option>
          <?php foreach ($thanhPham as $tp): ?><option value="<?= (int)$tp['MaThanhPham'] ?>" data-unit="<?= ntpHtml($tp['DonViTinh']) ?>" <?= (string)$tp['MaThanhPham'] === (string)$item['MaThanhPham'] ? 'selected' : '' ?>><?= ntpHtml($tp['TenThanhPham']) ?></option><?php endforeach; ?></select></td>
        <td class="ntp-unit">—</td>
        <td><input type="number" name="soLuong[]" min="0.01" max="9999999999999999" step="0.01" inputmode="decimal" placeholder="Nhập số lượng" value="<?= ntpHtml($item['SoLuongYeuCau']) ?>" aria-label="Số lượng dòng <?= $i + 1 ?>" required></td>
        <td><button type="button" class="ntp-btn small danger ntp-remove">Xóa dòng</button></td>
      </tr><?php endforeach; ?></tbody></table></div>
      <div class="ntp-form-summary"><span>Số thành phẩm đã chọn <strong id="ntp-count">0</strong></span><span>Tổng số lượng <strong id="ntp-total">0</strong></span></div>
      <div class="ntp-form-tip"><span aria-hidden="true">ⓘ</span><span>Một phiếu từ 1 đến 50 thành phẩm; không chọn trùng thành phẩm. Số lượng phải lớn hơn 0, tối đa 2 chữ số thập phân. Nếu lưu nháp, bạn có thể quay lại chỉnh sửa sau.</span></div>
    </section>
    <div class="ntp-form-actions ntp-no-print"><a class="ntp-btn" href="<?= $id ? 'chi-tiet.php?id=' . $id : 'index.php' ?>">← Hủy thao tác</a>
      <div class="ntp-toolbar-actions"><button type="submit" name="action" value="<?= $id ? 'cap_nhat_nhap' : 'luu_nhap' ?>" class="ntp-btn" <?= !$thanhPham ? 'disabled' : '' ?>>▤ Lưu bản nháp</button>
      <button type="submit" name="action" value="<?= $id ? 'cap_nhat_gui' : 'gui_phieu' ?>" class="ntp-btn primary" data-confirm="Gửi phiếu sang Bộ phận Kiểm định? Sau khi gửi sẽ không được sửa hoặc hủy." <?= !$thanhPham ? 'disabled' : '' ?>>✓ Xác nhận &amp; gửi kiểm định</button></div>
    </div>
  </form>
  <template id="ntp-row-template"><tr><td class="ntp-number"></td><td><select name="maThanhPham[]" class="ntp-product" required><option value="">— Chọn thành phẩm —</option>
    <?php foreach ($thanhPham as $tp): ?><option value="<?= (int)$tp['MaThanhPham'] ?>" data-unit="<?= ntpHtml($tp['DonViTinh']) ?>"><?= ntpHtml($tp['TenThanhPham']) ?></option><?php endforeach; ?></select></td>
    <td class="ntp-unit">—</td><td><input type="number" name="soLuong[]" min="0.01" max="9999999999999999" step="0.01" inputmode="decimal" placeholder="Nhập số lượng" required></td>
    <td><button type="button" class="ntp-btn small danger ntp-remove">Xóa dòng</button></td></tr></template>
</div></main>
<script src="<?= ntpHtml(rtrim($ntpMenu['baseUrl'], '/')) ?>/assets/js/nhap-thanh-pham.js?v=3" defer></script>
<?php ntpDongTrang(); ?>
