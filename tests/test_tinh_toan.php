<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../class/tranTrungKien/lapPhieuKiemKe/tinhToan.php';
function checkKien($a, $b): void { if ($a !== $b) throw new RuntimeException('Sai kết quả: '.var_export($a,true)); }
checkKien(kienDocSoLuong('100.125'),100125);
checkKien(kienDocSoLuong('1 250.001'),1250001);
checkKien(kienDocSoLuong('',true),null);
checkKien(kienSoLuongSql(kienDocSoLuong('999999999999999.999')),'999999999999999.999');
checkKien(kienDinhDangSo('1250.120'),'1 250.12');
$t=kienTinhTon(kienDocSoLuong('100.125'),kienDocSoLuong('99.875'),kienDocSoLuong('0.125'));
checkKien(kienSoLuongSql($t['conLai']),'99.750');
checkKien(kienSoLuongSql($t['chenhLech']),'-0.375');
foreach (['-1','1.2345','1,25','1e3','1 23','1000000000000000',''] as $bad) {
    try { kienDocSoLuong($bad); throw new RuntimeException('Chấp nhận dữ liệu sai: '.$bad); }
    catch (InvalidArgumentException $e) {}
}
try { kienTinhTon(1000,500,501); throw new RuntimeException('Tiêu hủy vượt thực tế'); }
catch (InvalidArgumentException $e) {}
echo "PASS: so luong DECIMAL(18,3), so lon, so trong, tieu huy va chenh lech.\n";
