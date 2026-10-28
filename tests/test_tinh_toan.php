<?php
require __DIR__ . '/../class/tranTrungKien/lapPhieuKiemKe/tinhToan.php';
function eq($actual, $expect) { if ($actual !== $expect) throw new RuntimeException('FAIL: '.var_export($actual,true).' != '.var_export($expect,true)); }
eq(kienTinhTon(100,105,0), ['conLai'=>105,'chenhLech'=>5]);
eq(kienTinhTon(100,95,5), ['conLai'=>90,'chenhLech'=>-10]);
eq(kienTinhTon(1200,1250,3), ['conLai'=>1247,'chenhLech'=>47]);
eq(kienDocSoNguyen('1 234'),1234);
eq(kienDocSoNguyen('1234'),1234);
eq(kienDocSoNguyen('',true),null);

eq(kienDinhDangSo('1234567.000'), '1 234 567');
foreach (['1.234','1,234','1 23','-1','3.5','1 234,5'] as $bad) {
    try {kienDocSoNguyen($bad);throw new RuntimeException('FAIL: accepted '.$bad);} catch (InvalidArgumentException $e) { /* expected */ }
}
try {kienTinhTon(100,9,10);throw new RuntimeException('FAIL: invalid destroy');} catch (InvalidArgumentException $e) {}
echo "PASS: so nguyen, dinh dang hang nghin, phan biet so le, chenh lech.\n";
