<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
ntpYeuCauVaiTro('QUAN_LY_XUONG');
$id = ntpMaDuongDan($_GET['id'] ?? null);
try {
    $phieu = $ntpModel->layPhieu($id, (int)ntpTaiKhoan()['MaTaiKhoan']);
    if (!$phieu) { http_response_code(404); exit('Không tìm thấy phiếu yêu cầu.'); }
    $chiTiet = $ntpModel->layChiTiet($id);
} catch (Throwable $e) {
    error_log('[NTP detail] ' . $e->getMessage());
    http_response_code(500); exit('Không thể tải chi tiết phiếu.');
}
$nhap = $phieu['TrangThai'] === 'Nháp';
$flash = ntpLayThongBao();
$tong = array_sum(array_map(static fn($item) => (float)$item['SoLuongYeuCau'], $chiTiet));
ntpMoTrang('Chi tiết ' . ntpMaPhieu($id));
?>
<main class="app-main"><div class="ntp-wrap">
  <a class="ntp-back ntp-no-print" href="index.php">← Danh sách yêu cầu</a>
  <div class="ntp-page-head">
    <div><div class="ntp-eyebrow">CHI TIẾT PHIẾU YÊU CẦU</div><h1><?= ntpHtml(ntpMaPhieu($id)) ?></h1><p class="ntp-lead">Thông tin lô thành phẩm đề nghị nhập kho.</p></div>
    <div class="ntp-toolbar-actions ntp-no-print"><button class="ntp-btn" type="button" onclick="window.print()">⎙ In phiếu</button><?php if ($nhap): ?><a href="lap-phieu.php?id=<?= $id ?>" class="ntp-btn primary">✎ Chỉnh sửa phiếu</a><?php endif; ?></div>
  </div>
  <?php if ($flash): ?><div class="ntp-alert <?= $flash[0] === 'error' ? 'error' : 'success' ?>" role="alert"><?= ntpHtml($flash[1]) ?></div><?php endif; ?>
  <div class="ntp-status-hero <?= $nhap ? 'is-draft' : '' ?>">
    <div><strong><?= $nhap ? 'Phiếu đang ở trạng thái bản nháp' : 'Phiếu đã gửi sang Bộ phận Kiểm định' ?></strong><p><?= $nhap ? 'Bạn vẫn có thể sửa, xóa hoặc gửi phiếu đến bộ phận Kiểm định.' : 'Phiếu đã được chuyển sang danh sách chờ kiểm định và không thể sửa hoặc hủy.' ?></p></div>
    <span class="ntp-status <?= $nhap ? 'draft' : 'waiting' ?>"><?= ntpHtml($phieu['TrangThai']) ?></span>
  </div>
  <section class="ntp-card"><div class="ntp-card-header"><h2>Thông tin chung</h2><span class="ntp-muted">Phiếu nhập thành phẩm</span></div>
    <div class="ntp-info-grid">
      <div><span>Mã phiếu yêu cầu</span><strong><?= ntpHtml(ntpMaPhieu($id)) ?></strong></div>
      <div><span>Người lập (Quản lý xưởng)</span><strong><?= ntpHtml($phieu['NguoiLap']) ?></strong></div>
      <div><span>Ngày lập phiếu</span><strong><?= ntpHtml(ntpFmtDate((string)$phieu['NgayLap'], true)) ?></strong></div>
      <div><span>Ngày hoàn thành sản xuất</span><strong><?= ntpHtml(ntpFmtDate((string)$phieu['NgayHoanThanhSanXuat'])) ?></strong></div>
      <div><span>Số mặt hàng</span><strong><?= count($chiTiet) ?></strong></div>
      <div><span>Trạng thái</span><strong><?= ntpHtml($phieu['TrangThai']) ?></strong></div>
      <div class="ntp-all"><span>Ghi chú</span><strong><?= ntpHtml($phieu['GhiChu'] ?: 'Không có ghi chú') ?></strong></div>
    </div>
  </section>
  <section class="ntp-card"><div class="ntp-card-header"><h2>Danh sách thành phẩm yêu cầu nhập</h2><span class="ntp-muted"><?= count($chiTiet) ?> mặt hàng</span></div>
    <div class="ntp-table-scroll"><table class="ntp-table"><thead><tr><th>STT</th><th>Mã thành phẩm</th><th>Tên thành phẩm</th><th>Đơn vị tính</th><th class="ntp-right">Số lượng yêu cầu</th></tr></thead><tbody>
      <?php foreach ($chiTiet as $i => $line): ?><tr><td><?= $i + 1 ?></td><td><strong>TP<?= str_pad((string)$line['MaThanhPham'], 4, '0', STR_PAD_LEFT) ?></strong></td><td><?= ntpHtml($line['TenThanhPham']) ?></td><td><?= ntpHtml($line['DonViTinh']) ?></td><td class="ntp-right"><strong><?= ntpHtml(ntpFmtQty($line['SoLuongYeuCau'])) ?></strong></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="ntp-summary-strip"><span>Tổng số lượng yêu cầu &nbsp;<strong><?= ntpHtml(ntpFmtQty($tong)) ?></strong></span></div>
  </section>
  <section class="ntp-card ntp-no-print"><div class="ntp-card-header"><h2><?= $nhap ? 'Thao tác với bản nháp' : 'Tiến trình xử lý' ?></h2></div>
    <?php if ($nhap): ?>
      <div class="ntp-action-surface"><p class="ntp-muted" style="margin:0 0 15px">Bạn có thể gửi kiểm định để hoàn tất use case, hoặc chỉnh sửa / hủy phiếu khi còn Nháp.</p>
      <div class="ntp-toolbar-actions"><a class="ntp-btn" href="lap-phieu.php?id=<?= $id ?>">✎ Sửa nội dung</a>
        <form method="post" action="xu-ly.php" onsubmit="return confirm('Xác nhận gửi phiếu đến Bộ phận Kiểm định? Sau khi gửi sẽ không được sửa/hủy.');"><input type="hidden" name="csrf" value="<?= ntpHtml(ntpCsrf()) ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="gui_nhap"><button class="ntp-btn primary" type="submit">✓ Gửi kiểm định</button></form>
        <form method="post" action="xu-ly.php" onsubmit="return confirm('Bạn có chắc muốn hủy phiếu nháp này? Thao tác không thể hoàn tác.');"><input type="hidden" name="csrf" value="<?= ntpHtml(ntpCsrf()) ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="huy_nhap"><button class="ntp-btn danger" type="submit">Xóa bản nháp</button></form>
      </div></div>
    <?php else: ?><div class="ntp-activity"><div class="ntp-activity-bullet">✓</div><div><strong>Đã gửi yêu cầu đến Bộ phận Kiểm định</strong><p>Bộ phận Kiểm định xem phiếu trong trang “Danh sách chờ kiểm định”. Việc gửi phiếu chưa cộng hàng vào tồn kho.</p><p><a class="ntp-back" style="margin-top:9px" href="kiem-dinh.php?id=<?= $id ?>">Xem trong danh sách kiểm định ↗</a></p></div></div><?php endif; ?>
  </section>
  <p class="ntp-note ntp-print-only">Phiếu yêu cầu nhập thành phẩm chỉ mang tính đề nghị nhập kho; cần hoàn tất kiểm định và nghiệp vụ nhập kho để cập nhật tồn.</p>
</div></main>
<?php ntpDongTrang(); ?>
