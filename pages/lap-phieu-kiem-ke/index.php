<?php
require __DIR__.'/bootstrap.php';
try {$model=new clsLapPhieuKiemKe(kienDb());$user=kienUser($model);$sheets=$model->danhSachPhieu($user['maTaiKhoan']);}
catch (Throwable $e) {http_response_code(500); error_log($e->getMessage());exit('Không thể tải dữ liệu kiểm kê. Kiểm tra MySQL, SQL và config.local.php.');}
kienLayoutStart('Danh sách phiếu kiểm kê',$user,$menuConfig,$baseUrl);
?>
<main class="app-main"><div class="kk-page">
  <div class="kk-title"><div><p class="kk-overline">BAN KIỂM KÊ · THE STARS</p><h1>Phiếu kiểm kê kho</h1><p class="kk-muted">Lập phiếu, lưu nháp, gửi Ban giám đốc xét duyệt.</p></div><a class="kk-btn kk-primary" href="phieu.php">+ Lập phiếu kiểm kê</a></div>
  <?php if(isset($_GET['ok'])): ?><div class="kk-alert success"><?=kienH($_GET['ok']==='gui'?'Gửi phiếu kiểm kê phê duyệt thành công.':($_GET['ok']==='xoa'?'Đã xóa bản nháp.':'Lưu bản nháp thành công.'))?></div><?php endif; ?>
  <?php if(!empty($_SESSION['kien_error'])): ?><div class="kk-alert error"><?=kienH($_SESSION['kien_error'])?></div><?php unset($_SESSION['kien_error']);endif; ?>
  <section class="kk-card"><div class="kk-card-head"><h2>Danh sách phiếu đã lập</h2><span><?=count($sheets)?> phiếu</span></div>
  <div class="kk-scroll"><table class="kk-table"><thead><tr><th>Mã phiếu</th><th>Kho</th><th>Ngày lập</th><th>Số dòng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
  <?php foreach($sheets as $p): ?><tr>
     <td><b><?=kienH($p['maPhieuKiemKe'])?></b></td><td><?=kienH($p['tenKho'])?></td><td><?=kienH($p['thoiGianLap'])?></td><td><?= (int)$p['soDong'] ?></td>
     <td><span class="kk-status <?=$p['trangThai']==='Nháp'?'draft':'pending'?>"><?=kienH($p['trangThai'])?></span></td>
     <td><div class="kk-actions"><a class="kk-btn kk-small" href="phieu.php?ma=<?=rawurlencode($p['maPhieuKiemKe'])?>"><?=$p['trangThai']==='Nháp'?'Sửa / gửi':'Xem chi tiết'?></a>
     <?php if($p['trangThai']==='Nháp'): ?><form method="post" action="xoa.php" onsubmit="return confirm('Xóa bản nháp này? Không thể khôi phục.');"><input type="hidden" name="csrf" value="<?=kienH(kienCsrf())?>"><input type="hidden" name="ma" value="<?=kienH($p['maPhieuKiemKe'])?>"><input type="hidden" name="phienBan" value="<?= (int)$p['phienBan'] ?>"><button class="kk-btn kk-small kk-danger" type="submit">Xóa</button></form><?php endif; ?></div></td>
  </tr><?php endforeach;?>
  <?php if(!$sheets):?><tr><td colspan="6" class="kk-empty">Chưa có phiếu nào. Nhấn “Lập phiếu kiểm kê” để bắt đầu.</td></tr><?php endif; ?>
  </tbody></table></div></section>
</div></main>
<?php renderFooter(); ?>
