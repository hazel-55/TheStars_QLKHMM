<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
ntpYeuCauVaiTro('QUAN_LY_XUONG');
$user = ntpTaiKhoan();
$search = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['trangThai'] ?? '');
$flash = ntpLayThongBao();
$rows = [];
$stats = ['Tong' => 0, 'Nhap' => 0, 'ChoKiemDinh' => 0];
$error = '';
try {
    $stats = $ntpModel->layThongKe((int) $user['MaTaiKhoan']);
    $rows = $ntpModel->layDanhSachPhieu((int) $user['MaTaiKhoan'], $search, $status);
} catch (Throwable $e) {
    error_log('[NTP list] ' . $e->getMessage());
    $error = 'Không thể tải danh sách phiếu. Hãy kiểm tra kết nối CSDL hoặc trạng thái tìm kiếm.';
}
ntpMoTrang('Yêu cầu nhập thành phẩm');
?>
<main class="app-main"><div class="ntp-wrap">
  <div class="ntp-page-head ntp-no-print">
    <div>
      <div class="ntp-eyebrow">QUẢN LÝ XƯỞNG / NHẬP THÀNH PHẨM</div>
      <h1>Yêu cầu nhập thành phẩm</h1>
      <p class="ntp-lead">Tạo và quản lý phiếu đề nghị nhập các thành phẩm đã hoàn thành sản xuất tại xưởng.</p>
    </div>
    <div class="ntp-toolbar-actions">
      <a class="ntp-btn" href="kiem-dinh.php">↗ Phiếu chờ kiểm định</a>
      <a class="ntp-btn primary" href="lap-phieu.php">＋ Lập phiếu mới</a>
    </div>
  </div>
  <?php if ($flash): ?><div class="ntp-alert <?= $flash[0] === 'error' ? 'error' : 'success' ?>" role="alert"><?= ntpHtml($flash[1]) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="ntp-alert error" role="alert"><?= ntpHtml($error) ?></div><?php endif; ?>
  <div class="ntp-stats">
    <div class="ntp-stat"><div class="ntp-stat-copy"><span class="ntp-stat-label">Tổng phiếu yêu cầu</span><small>Tất cả phiếu đã lập</small></div><strong><?= (int)$stats['Tong'] ?></strong></div>
    <div class="ntp-stat"><div class="ntp-stat-copy"><span class="ntp-stat-label">Phiếu bản nháp</span><small>Có thể sửa, hủy hoặc gửi đi</small></div><strong><?= (int)$stats['Nhap'] ?></strong></div>
    <div class="ntp-stat"><div class="ntp-stat-copy"><span class="ntp-stat-label">Chờ kiểm định</span><small>Đã gửi đến bộ phận Kiểm định</small></div><strong><?= (int)$stats['ChoKiemDinh'] ?></strong></div>
  </div>
  <section class="ntp-card">
    <div class="ntp-card-header"><div><h2>Danh sách phiếu yêu cầu</h2><p class="ntp-muted">Tìm kiếm, theo dõi và xử lý phiếu nhập thành phẩm.</p></div><span class="ntp-muted">Hiển thị tối đa 200 phiếu gần nhất</span></div>
    <form class="ntp-filters" method="get" action="index.php">
      <input aria-label="Tìm kiếm phiếu" type="search" name="q" maxlength="100" value="<?= ntpHtml($search) ?>" placeholder="⌕  Tìm mã phiếu, ghi chú hoặc tên thành phẩm...">
      <select aria-label="Lọc theo trạng thái" name="trangThai">
        <option value="">Tất cả trạng thái</option>
        <?php foreach (['Nháp', 'Chờ kiểm định'] as $s): ?><option value="<?= ntpHtml($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= ntpHtml($s) ?></option><?php endforeach; ?>
      </select>
      <div class="ntp-filter-buttons"><button class="ntp-btn primary" type="submit">Tìm kiếm</button><a class="ntp-btn" href="index.php">Xóa lọc</a></div>
    </form>
    <div class="ntp-table-scroll"><table class="ntp-table">
      <thead><tr><th>Mã phiếu</th><th>Ngày lập</th><th>Hoàn thành SX</th><th class="ntp-right">Mặt hàng</th><th class="ntp-right">Tổng số lượng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): $rowId = (int)$row['MaYeuCau']; $isDraft = $row['TrangThai'] === 'Nháp'; ?>
        <tr>
          <td><a class="ntp-code" href="chi-tiet.php?id=<?= $rowId ?>"><?= ntpHtml(ntpMaPhieu($rowId)) ?></a></td>
          <td><?= ntpHtml(ntpFmtDate($row['NgayLap'], true)) ?></td>
          <td><?= ntpHtml(ntpFmtDate($row['NgayHoanThanhSanXuat'])) ?></td>
          <td class="ntp-right"><?= (int)$row['SoMatHang'] ?></td>
          <td class="ntp-right"><strong><?= ntpHtml(ntpFmtQty($row['TongSoLuong'])) ?></strong></td>
          <td><span class="ntp-status <?= $isDraft ? 'draft' : 'waiting' ?>"><?= ntpHtml($row['TrangThai']) ?></span></td>
          <td><div class="ntp-actions"><a class="ntp-btn small" href="chi-tiet.php?id=<?= $rowId ?>">Xem</a><?php if ($isDraft): ?><a class="ntp-btn small" href="lap-phieu.php?id=<?= $rowId ?>">Sửa</a><?php endif; ?></div></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="ntp-empty"><div class="ntp-empty-icon" aria-hidden="true">▤</div><strong><?= ($search !== '' || $status !== '') ? 'Không tìm thấy phiếu phù hợp' : 'Chưa có phiếu yêu cầu nào' ?></strong><p><?= ($search !== '' || $status !== '') ? 'Hãy thử tìm kiếm với điều kiện khác.' : 'Hãy bắt đầu bằng cách lập phiếu yêu cầu nhập thành phẩm đầu tiên.' ?></p><?php if ($search === '' && $status === ''): ?><a class="ntp-btn primary" href="lap-phieu.php">＋ Lập phiếu đầu tiên</a><?php else: ?><a class="ntp-btn" href="index.php">Xem tất cả phiếu</a><?php endif; ?></td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <p class="ntp-pagination-hint">Phiếu đã gửi kiểm định chỉ có thể xem, không được sửa hoặc hủy theo quy tắc nghiệp vụ.</p>
  </section>
</div></main>
<?php ntpDongTrang(); ?>
