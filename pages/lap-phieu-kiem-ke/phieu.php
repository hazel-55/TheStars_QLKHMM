<?php
require __DIR__ . '/bootstrap.php';
try {
    $model = new clsLapPhieuKiemKe(kienDb());
    $user = kienUser($model);
} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());
    exit('Không kết nối được CSDL kiểm kê. Kiểm tra WAMP, SQL và config.local.php.');
}

$error = '';
$submitted = [];
$ma = trim((string)($_GET['ma'] ?? $_POST['maPhieu'] ?? ''));
$phieu = null;
try {
    if ($ma !== '') {
        $phieu = $model->layPhieu($ma, $user['maTaiKhoan']);
        if ($phieu === null) throw new InvalidArgumentException('Phiếu không tồn tại hoặc không thuộc quyền của bạn.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        kienVerifyCsrf();
        if ($phieu !== null && $phieu['trangThai'] !== 'Nháp') throw new InvalidArgumentException('Phiếu đã gửi, không thể sửa.');
        $id = $model->luu($user['maTaiKhoan'], $_POST);
        $status = ($_POST['hanhDong'] ?? '') === 'gui' ? 'gui' : 'nhap';
        header('Location: index.php?ok=' . $status . '&ma=' . rawurlencode($id));
        exit;
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
    $submitted = $_POST;
} catch (Throwable $e) {
    error_log('Kiểm kê: '.$e->getMessage());
    $error = 'Không thể lưu phiếu kiểm kê. Dữ liệu chưa được gửi; vui lòng kiểm tra WAMP/CSDL.';
    $submitted = $_POST;
}
$readonly = $phieu !== null && $phieu['trangThai'] !== 'Nháp';
$chosenKho = (string)($submitted['maKho'] ?? ($phieu['maKho'] ?? ($_GET['kho'] ?? '')));
$allWarehouses = $model->kho();
$goods = [];
if ($chosenKho !== '') {
    try {$goods = $model->layHangTon($chosenKho);}
    catch (Throwable $e) {error_log($e->getMessage()); $error = $error ?: 'Không lấy được hàng hóa trong kho đã chọn.';}
}
$previous = $phieu['chiTiet'] ?? [];
$postedItems = is_array($submitted['items'] ?? null) ? $submitted['items'] : [];
$totalSelected = 0;
foreach ($goods as $k => $r) {
    $row = $postedItems[$k] ?? ($previous[$k] ?? []);
    if ($row && (!isset($row['selected']) || $row['selected'])) $totalSelected++;
}
$title = $phieu ? 'Chi tiết phiếu ' . $phieu['maPhieuKiemKe'] : 'Lập phiếu kiểm kê';
kienLayoutStart($title, $user, $menuConfig, $baseUrl);
?>
<main class="app-main"><div class="kk-page">
  <div class="kk-title">
    <div><p class="kk-overline">BAN KIỂM KÊ · THE STARS</p><h1><?=kienH($title)?></h1><p class="kk-muted">Đối chiếu số lượng kiểm đếm thực tế với tồn kho trên hệ thống.</p></div>
    <a href="index.php" class="kk-btn">← Danh sách phiếu</a>
  </div>
  <?php if ($error): ?><div class="kk-alert error" role="alert"><?=kienH($error)?></div><?php endif; ?>
  <?php if ($readonly): ?><div class="kk-alert info">Phiếu đang ở trạng thái <strong><?=kienH($phieu['trangThai'])?></strong>. Chỉ được xem, không được chỉnh sửa hoặc gửi lại.</div><?php endif; ?>
  <section class="kk-card">
    <div class="kk-card-head"><h2>1. Thông tin phiếu</h2><span><?= $phieu ? kienH($phieu['maPhieuKiemKe']) : 'Phiếu mới' ?></span></div>
    <?php if (!$phieu): ?>
    <form method="get" class="kk-warehouse-select"><label for="warehouse">Chọn kho cần kiểm kê <b class="kk-required">*</b></label>
      <div class="kk-inline"><select name="kho" id="warehouse" required><option value="">-- Chọn kho đang hoạt động --</option>
        <?php foreach($allWarehouses as $w): ?><option value="<?=kienH($w['maKho'])?>" <?=$chosenKho===$w['maKho']?'selected':''?>><?=kienH($w['maKho'].' — '.$w['tenKho'].' ('.$w['loaiKho'].')')?></option><?php endforeach; ?>
      </select><button class="kk-btn kk-primary" type="submit">Lấy hàng trong kho</button></div>
    </form>
    <?php else: ?><div class="kk-details"><div><span>Kho</span><strong><?=kienH($phieu['tenKho'])?></strong></div><div><span>Người lập</span><strong><?=kienH($user['hoTen'])?></strong></div><div><span>Ngày lập</span><strong><?=kienH($phieu['thoiGianLap'])?></strong></div><div><span>Trạng thái</span><strong><?=kienH($phieu['trangThai'])?></strong></div></div><?php endif; ?>
  </section>
  <?php if ($chosenKho !== '' && $goods): ?>
  <form method="post" enctype="multipart/form-data" id="kiemke-form" novalidate>
    <input type="hidden" name="csrf" value="<?=kienH(kienCsrf())?>">
    <input type="hidden" name="maKho" value="<?=kienH($chosenKho)?>">
    <input type="hidden" name="maPhieu" value="<?=kienH($phieu['maPhieuKiemKe']??'')?>">
    <input type="hidden" name="phienBan" value="<?= (int)($phieu['phienBan']??0) ?>">
    <section class="kk-card">
      <div class="kk-card-head"><h2>2. Hàng hóa và kết quả kiểm kê</h2><span><?=count($goods)?> dòng tồn kho</span></div>
      <div class="kk-help">Chỉ nhập số nguyên, không dùng dấu thập phân. Có thể nhập 1250 hoặc 1 250; hệ thống hiển thị dấu cách phân cách hàng nghìn. Chọn từng biến thể tại đúng vị trí cần kiểm kê. <strong>Số thực tế</strong> là số kiểm đếm trước tiêu hủy. Chênh lệch = (thực tế − tiêu hủy) − tồn hệ thống.</div>
      <div class="kk-toolbar"><label class="kk-search-label" for="kk-search">Tìm hàng <input id="kk-search" type="search" placeholder="Mã SKU, tên, màu, size, vị trí..." autocomplete="off"></label><span id="kk-visible-count"></span><span>Đã chọn: <b id="selected-count"><?=$totalSelected?></b> dòng</span><button type="button" class="kk-btn kk-small" id="select-all" <?=$readonly?'disabled':''?>>Chọn tất cả</button></div>
      <div class="kk-scroll"><table class="kk-table kk-goods"><thead><tr><th>Chọn</th><th>Hàng hóa / SKU</th><th>Danh mục / Kiểu dáng</th><th>Màu / Size</th><th>Vị trí</th><th>Tồn hệ thống</th><th>Thực tế</th><th>Tiêu hủy</th><th>Còn lại</th><th>Chênh lệch</th></tr></thead>
      <tbody>
      <?php foreach ($goods as $key=>$g):
        $v = $postedItems[$key] ?? ($previous[$key] ?? []);
        $isSelected = isset($submitted['items']) ? !empty($v['selected']) : (isset($previous[$key]));
        $actual = (string)($v['soLuongThucTe']??'');
        if ($actual !== '' && is_numeric($actual)) $actual = kienDinhDangSo($actual);
        $destroyed = (string)($v['soLuongHangLoiTieuHuy']??'0');
        if ($destroyed !== '' && is_numeric($destroyed)) $destroyed = kienDinhDangSo($destroyed);
        $oldEvidence = $phieu['minhChung'][(string)($previous[$key]['maChiTiet']??'')] ?? [];
        $rowVersion = (string)($previous[$key]['phienBanTonLucChot']??$g['phienBanTon']);
      ?>
        <tr class="kk-item-row <?=$isSelected?'is-selected':''?>" data-key="<?=kienH($key)?>" data-stock="<?=kienH($previous[$key]['soLuongTonHeThong']??$g['soLuongTon'])?>">
          <td><input class="kk-select" type="checkbox" name="items[<?=kienH($key)?>][selected]" value="1" <?=$isSelected?'checked':''?> <?=$readonly?'disabled':''?> aria-label="Chọn <?=kienH($g['tenHangHoa'])?>"></td>
          <td><b><?=kienH($g['tenHangHoa'])?></b><small><?=kienH($g['maHangHoa'].' / '.$g['maBienThe'])?></small></td>
          <td><?=kienH($g['tenDanhMuc'])?><small><?=kienH($g['kieuDang']?:'—')?></small></td>
          <td><?=kienH($g['mauSac']?:'—')?> / <?=kienH($g['kichThuoc']?:'—')?></td>
          <td><?=kienH($g['day'].' / '.$g['ke'].' / T'.$g['tang'])?><small><?=kienH($g['maViTri'])?></small></td>
          <td class="kk-num"><?=kienH(kienDinhDangSo($previous[$key]['soLuongTonHeThong']??$g['soLuongTon']))?> <?=kienH($g['donViTinh'])?></td>
          <td><input class="kk-number kk-actual" type="text" inputmode="numeric" maxlength="15" pattern="[0-9 ]*" name="items[<?=kienH($key)?>][soLuongThucTe]" value="<?=kienH($actual)?>" <?=$readonly?'disabled':''?> placeholder="Nhập số"></td>
          <td><input class="kk-number kk-destroyed" type="text" inputmode="numeric" maxlength="15" pattern="[0-9 ]*" name="items[<?=kienH($key)?>][soLuongHangLoiTieuHuy]" value="<?=kienH($destroyed)?>" <?=$readonly?'disabled':''?>></td>
          <td class="kk-num kk-remaining">—</td><td class="kk-num kk-difference">—</td>
        </tr>
        <tr class="kk-expanded <?=$isSelected?'is-visible':''?>"><td colspan="10">
           <input type="hidden" name="items[<?=kienH($key)?>][phienBanTonLucChot]" value="<?=kienH($rowVersion)?>">
           <div class="kk-fields">
             <label>Tình trạng hàng hóa <span>* khi gửi</span><input type="text" maxlength="255" name="items[<?=kienH($key)?>][tinhTrangHangHoa]" value="<?=kienH($v['tinhTrangHangHoa']??'')?>" placeholder="Ví dụ: còn nguyên vẹn / hư hỏng" <?=$readonly?'disabled':''?>></label>
             <label>Lý do chênh lệch <span>* nếu thừa / thiếu</span><textarea maxlength="500" name="items[<?=kienH($key)?>][lyDoChenhLech]" rows="2" <?=$readonly?'disabled':''?>><?=kienH($v['lyDoChenhLech']??'')?></textarea></label>
             <label>Lý do tiêu hủy <span>* nếu tiêu hủy &gt; 0</span><textarea maxlength="500" name="items[<?=kienH($key)?>][lyDoTieuHuy]" rows="2" <?=$readonly?'disabled':''?>><?=kienH($v['lyDoTieuHuy']??'')?></textarea></label>
             <label>Hình thức tiêu hủy <span>* nếu tiêu hủy &gt; 0</span><input maxlength="255" name="items[<?=kienH($key)?>][hinhThucTieuHuy]" placeholder="Ví dụ: xử lý theo biên bản" value="<?=kienH($v['hinhThucTieuHuy']??'')?>" <?=$readonly?'disabled':''?>></label>
             <label class="kk-field-wide">Nội dung giải trình <span>* nếu có chênh lệch / tiêu hủy</span><textarea maxlength="2000" rows="2" name="items[<?=kienH($key)?>][noiDungGiaiTrinh]" <?=$readonly?'disabled':''?>><?=kienH($v['noiDungGiaiTrinh']??'')?></textarea></label>
             <label>Loại minh chứng <select name="items[<?=kienH($key)?>][loaiMinhChung]" <?=$readonly?'disabled':''?>><option>Ảnh</option><option>Biên bản</option><option>Tài liệu khác</option></select></label>
             <label class="kk-field-wide">Tệp minh chứng (JPG / PNG / PDF, tối đa 5 MB/tệp)<input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" name="evidence[<?=kienH($key)?>][]" <?=$readonly?'disabled':''?>></label>
           </div>
           <?php if ($oldEvidence): ?><div class="kk-old-evidence"><b>Minh chứng đã lưu:</b> <?php foreach($oldEvidence as $ev): ?><a href="tai-tep.php?id=<?=(int)$ev['maMinhChung']?>" target="_blank" rel="noopener"><?=kienH($ev['tenMinhChung'])?></a><?php endforeach; ?></div><?php endif; ?>
        </td></tr>
      <?php endforeach; ?>
      </tbody></table></div>
    </section>
    <section class="kk-card"><div class="kk-card-head"><h2>3. Tổng hợp kiểm kê</h2></div>
      <div class="kk-metrics"><div><span>Số dòng được chọn</span><strong id="metric-count">0</strong></div><div><span>Số dòng kiểm kê thừa</span><strong id="metric-plus">0</strong></div><div><span>Số dòng kiểm kê thiếu</span><strong id="metric-minus">0</strong></div></div>
      <p class="kk-muted">Số dòng thừa và thiếu chỉ đếm các hàng hóa có kết quả kiểm kê hợp lệ; số lượng chênh lệch được hiển thị riêng theo từng mặt hàng.</p>
      <label class="kk-label">Biên bản giải trình tổng thể <span class="kk-muted">(bắt buộc khi có chênh lệch hoặc tiêu hủy)</span>
        <textarea name="giaiTrinhTongThe" maxlength="2000" rows="4" placeholder="Tổng hợp nguyên nhân kiểm kê thừa/thiếu và cách xử lý hàng lỗi" <?=$readonly?'disabled':''?>><?=kienH($submitted['giaiTrinhTongThe']??($phieu['bienBan']['noiDungGiaiTrinh']??''))?></textarea>
      </label>
      <label class="kk-label">Ghi chú chung<textarea name="ghiChu" maxlength="500" rows="3" placeholder="Ghi chú thêm cho phiếu kiểm kê" <?=$readonly?'disabled':''?>><?=kienH($submitted['ghiChu']??($phieu['ghiChu']??''))?></textarea></label>
      <?php if(!$readonly): ?><div class="kk-action-bar"><button class="kk-btn" type="submit" name="hanhDong" value="nhap">Lưu bản nháp</button><button class="kk-btn kk-primary" type="submit" name="hanhDong" value="gui" id="send-btn" onclick="return confirm('Gửi phiếu tới Ban giám đốc? Sau khi gửi sẽ không thể sửa phiếu.');">Gửi phê duyệt →</button></div><?php endif; ?>
    </section>
  </form>
  <?php elseif ($chosenKho !== ''): ?><div class="kk-alert info">Kho này chưa có tồn kho hoạt động để kiểm kê. Chọn kho khác hoặc thêm dữ liệu mẫu trong CSDL thử nghiệm.</div>
  <?php else: ?><div class="kk-alert info">Bắt đầu bằng việc chọn một kho hoạt động để tải danh sách hàng hóa.</div><?php endif; ?>
</div></main>
<script src="<?=kienH($baseUrl.'/assets/js/lap-phieu-kiem-ke.js?v=1')?>" defer></script>
<?php renderFooter(); ?>
