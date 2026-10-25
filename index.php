<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Đọc cấu hình từ file .env ở thư mục gốc.
    $envPath = __DIR__ . '/.env';

    if (!is_readable($envPath)) {
        throw new RuntimeException('Không tìm thấy hoặc không đọc được .env.');
    }

    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

    if ($env === false) {
        throw new RuntimeException('File .env không hợp lệ.');
    }

    $db_host = $env['DB_HOST'] ?? 'localhost';
    $db_name = $env['DB_NAME'] ?? '';
    $db_user = $env['DB_USER'] ?? '';
    $db_password = $env['DB_PASSWORD'] ?? '';

    $db_port = filter_var(
        $env['DB_PORT'] ?? '3306',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 65535]]
    );

    if ($db_name === '' || $db_user === '') {
        throw new RuntimeException('Thiếu tên database hoặc tài khoản kết nối.');
    }

    if ($db_port === false) {
        throw new RuntimeException('Cổng database không hợp lệ.');
    }

    // Kết nối database bằng MySQLi.
    $conn = new mysqli(
        $db_host,
        $db_user,
        $db_password,
        $db_name,
        $db_port
    );

    // Đồng nhất với bộ mã utf8 của database.
    $conn->set_charset('utf8');
} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit('Không thể kết nối database. Kiểm tra cấu hình .env và log PHP.');
}
// Test kết nối và kiểm tra đúng database.
// $result = $conn->query('SELECT DATABASE() AS db_name');
// $row = $result->fetch_assoc();

// echo 'Kết nối thành công! Database: '
//     . htmlspecialchars($row['db_name'], ENT_QUOTES, 'UTF-8');

// Viết phần điều hướng và nạp trang ở bên dưới.