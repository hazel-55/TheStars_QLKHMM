<?php
// Cau hinh rieng cho UC cua Kien, khong thay doi connect.php cua nhom.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function kienDb(): mysqli
{
    $file = __DIR__ . '/config.local.php';
    if (!is_file($file)) {
        throw new RuntimeException('Thiếu config.local.php. Hãy sao chép config.example.php rồi cấu hình WAMP.');
    }
    $c = require $file;
    $db = new mysqli($c['host'], $c['username'], $c['password'], $c['database'], (int)$c['port']);
    $db->set_charset('utf8mb4');
    return $db;
}
