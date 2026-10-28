<?php
require __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);exit;}
try {
    kienVerifyCsrf();
    $model=new clsLapPhieuKiemKe(kienDb());$user=kienUser($model);
    $model->xoaBanNhap((string)($_POST['ma']??''),$user['maTaiKhoan'],(int)($_POST['phienBan']??0));
    header('Location: index.php?ok=xoa');exit;
} catch (InvalidArgumentException $e) {$_SESSION['kien_error']=$e->getMessage();}
catch (Throwable $e) {error_log($e->getMessage());$_SESSION['kien_error']='Không thể xóa bản nháp, kiểm tra cấu hình CSDL.';}
header('Location: index.php');exit;
