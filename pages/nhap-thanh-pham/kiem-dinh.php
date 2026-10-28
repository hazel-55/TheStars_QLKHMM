<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
ntpYeuCauVaiTro('KIEM_DINH');
$error = ''; $rows = [];
try { $rows = $ntpModel->layPhieuChoKiemDinh(); }
catch (Throwable $e) { error_log('[NTP KD] '.$e->getMessage()); $error = 'Không thể tải các yêu cầu chờ kiểm định.'; }
$id = isset($_GET['id']) ? ntpMaDuongDan($_GET['id']) : null;
$phieu = null; $chiTiet = [];
if ($id) {
    try { $phieu = $ntpModel->layPhieuChoKiemDinhTheoMa($id); $chiTiet = $phieu ? $ntpModel->layChiTiet($id) : []; }
    catch (Throwable $e) { error_log('[NTP KD detail] '.$e->getMessage()); $error = 'Không thể tải chi tiết phiếu.'; }
}
ntpMoTrang('Danh sách chờ kiểm định', 'nhap-tp-kiem-dinh');
?>
<main class="app-main"><div class="ntp-wrap">
  <div class="ntp-page-head"><div><div class="ntp-eyebrow">BỘ PHẬN KIỂM ĐỊNH / TIẾP NHẬN YÊU CẦU</div><h1>Phiếu nhập thành phẩm chờ kiểm định</h1><p class="ntp-lead">Danh sách các yêu cầu được Quản lý xưởng chuyển đến để kiểm tra chất lượng.</p></div><a class="ntp-btn ntp-no-print" href="index.php">← Danh sách của xưởng</a></div>
  <?php if ($error): ?><div class="ntp-alert error" role="alert"><?= ntpHtml($error) ?></div><?php endif; ?>
  <div class="ntp-stats" style="grid-template-columns:1fr"><div class="ntp-stat"><div class="ntp-stat-copy"><span class="ntp-stat-label">Yêu cầu đang chờ kiểm định</span><small>Được gửi từ chức năng Lập phiếu nhập thành phẩm</small></div><strong><?= count($rows) ?></strong></div></div>
  <section class="ntp-card"><div class="ntp-card-header"><h2>Danh sách yêu cầu đã gửi</h2><span class="ntp-muted">Trạng thái: Chờ kiểm định</span></div><div class="ntp-table-scroll"><table class="ntp-table"><thead><tr><th>Mã phiếu</th><th>Người lập</th><th>Ngày lập</th><th>Ngày hoàn thành SX</th><th class="ntp-right">Mặt hàng</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr><td><a class="ntp-code" href="kiem-dinh.php?id=<?= (int)$row['MaYeuCau'] ?>"><?= ntpHtml(ntpMaPhieu((int)$row['MaYeuCau'])) ?></a></td><td><?= ntpHtml($row['NguoiLap']) ?></td><td><?= ntpHtml(ntpFmtDate($row['NgayLap'], true)) ?></td><td><?= ntpHtml(ntpFmtDate($row['NgayHoanThanhSanXuat'])) ?></td><td class="ntp-right"><?= (int)$row['SoMatHang'] ?></td><td><a class="ntp-btn small" href="kiem-dinh.php?id=<?= (int)$row['MaYeuCau'] ?>">Xem chi tiết</a></td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="ntp-empty"><span class="ntp-empty-icon">✓</span><strong>Chưa có phiếu nào chờ kiểm định</strong><p>Phiếu sẽ hiển thị khi quản lý xưởng xác nhận gửi.</p></td></tr><?php endif; ?>
  </tbody></table></div></section>
  <?php if ($id && !$phieu): ?><div class="ntp-alert error">Không tìm thấy phiếu đang chờ kiểm định theo mã yêu cầu.</div><?php endif; ?>
  <?php if ($phieu): ?><section class="ntp-card" id="chi-tiet"><div class="ntp-card-header"><div><h2>Chi tiết phiếu <?= ntpHtml(ntpMaPhieu($id)) ?></h2><p class="ntp-muted">Người gửi: <?= ntpHtml($phieu['NguoiLap']) ?> · Ngày hoàn thành: <?= ntpHtml(ntpFmtDate($phieu['NgayHoanThanhSanXuat'])) ?></p></div><span class="ntp-status waiting">Chờ kiểm định</span></div>
    <div class="ntp-info-grid" style="margin-bottom:19px"><div class="ntp-all"><span>Ghi chú từ xưởng</span><strong><?= ntpHtml($phieu['GhiChu'] ?: 'Không có ghi chú') ?></strong></div></div>
    <div class="ntp-table-scroll"><table class="ntp-table"><thead><tr><th>STT</th><th>Thành phẩm</th><th>Đơn vị</th><th class="ntp-right">Số lượng yêu cầu</th></tr></thead><tbody>
    <?php foreach ($chiTiet as $i => $ct): ?><tr><td><?= $i + 1 ?></td><td><?= ntpHtml($ct['TenThanhPham']) ?></td><td><?= ntpHtml($ct['DonViTinh']) ?></td><td class="ntp-right"><strong><?= ntpHtml(ntpFmtQty($ct['SoLuongYeuCau'])) ?></strong></td></tr><?php endforeach; ?></tbody></table></div>
  </section><?php endif; ?>
  <p class="ntp-note">Trang này hỗ trợ xem và tiếp nhận yêu cầu của Duy. Nghiệp vụ cập nhật kết quả kiểm định Đạt / Không đạt nằm trong chức năng Kiểm định riêng của nhóm, không được tự cập nhật tồn kho tại đây.</p>
</div></main>
<?php ntpDongTrang(); ?>
