<?php
// Dùng kết nối chung của nhóm; không tạo kết nối/config riêng.
require_once __DIR__ . '/../../common/connect.php';
function kienDb(): mysqli
{
    global $conn;
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException('Kết nối chung chưa được khởi tạo.');
    }
    return $conn;
}
