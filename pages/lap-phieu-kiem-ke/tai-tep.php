<?php
require __DIR__.'/bootstrap.php';
try {
    $model=new clsLapPhieuKiemKe(kienDb());$user=kienUser($model);
    $file=$model->tepMinhChung((int)($_GET['id']??0),$user['maTaiKhoan']);
    if (!$file) {http_response_code(404);exit('Không thấy minh chứng.');}
    $path=$model->filePath($file['duongDanTep']);
    if (!is_file($path)) {http_response_code(404);exit('Tệp đã mất.');}
    $ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));
    $mime=['jpg'=>'image/jpeg','png'=>'image/png','pdf'=>'application/pdf'][$ext]??'application/octet-stream';
    $name=preg_replace('/[^a-zA-Z0-9._-]/','_',basename($file['tenMinhChung']));
    header('Content-Type: '.$mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="'.$name.'"');
    header('Content-Length: '.filesize($path));
    readfile($path);
} catch (Throwable $e) {http_response_code(500);error_log($e->getMessage());echo 'Không tải được tệp.';}
