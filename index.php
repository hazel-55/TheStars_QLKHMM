<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hệ thống quản lý kho may mặc</title>
</head>
<body>

    <h1>Hệ thống quản lý kho may mặc</h1>

    <a href="pages/kiem-dinh/index.php">
        Chức năng kiểm định
    </a>

</body>
</html>