<?php
/** Bootstrap riêng cho UC nhập thành phẩm, không ảnh hưởng module Kiểm định cũ. */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../../class/common/connect.php';
require_once __DIR__ . '/../../class/leKhanhDuy/yeuCauNhapThanhPham/clsnhapthanhpham.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
require_once __DIR__ . '/../../components/layout/footer.php';

$ntpMenu = require __DIR__ . '/../../class/common/menuconfig.php';
$ntpModel = new NhapThanhPham($conn);

function ntpHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
/**
 * Chế độ trình diễn KHÔNG ĐĂNG NHẬP: lấy QLX có sẵn trong bảng TaiKhoan.
 * Giữ MaQuanLyXuong là khóa ngoại hợp lệ theo dữ liệu gốc.
 * Không được sử dụng chế độ này khi đưa hệ thống lên Internet.
 */
function ntpTaiKhoan(): array
{
    global $conn;
    static $taiKhoan = null;
    if ($taiKhoan !== null) {
        return $taiKhoan;
    }
    try {
        $result = $conn->query(
            "SELECT MaTaiKhoan, TenDangNhap, VaiTro
             FROM TaiKhoan
             WHERE VaiTro = 'QUAN_LY_XUONG'
             ORDER BY CASE WHEN TenDangNhap = 'qlx_duy' THEN 0 ELSE 1 END, MaTaiKhoan
             LIMIT 1"
        );
        $row = $result->fetch_assoc();
        $result->free();
    } catch (Throwable $e) {
        error_log('[NTP no-login] ' . $e->getMessage());
        http_response_code(500);
        exit('Không đọc được bảng TaiKhoan. Kiểm tra CSDL thestars_qlkhmm_db.');
    }
    if (!$row) {
        http_response_code(500);
        exit('Chưa có tài khoản QUAN_LY_XUONG trong bảng TaiKhoan. Hãy import dữ liệu mẫu của Duy.');
    }
    $taiKhoan = [
        'MaTaiKhoan' => (int) $row['MaTaiKhoan'],
        'TenDangNhap' => (string) $row['TenDangNhap'],
        'VaiTro' => 'QUAN_LY_XUONG',
    ];
    return $taiKhoan;
}

/** Bỏ kiểm tra đăng nhập cho các trang demo; giữ tên hàm để không phá mã cũ. */
function ntpYeuCauVaiTro(string $vaiTro): void
{
    if ($vaiTro === 'QUAN_LY_XUONG') {
        ntpTaiKhoan(); // Cần MaQuanLyXuong tồn tại trong DB để lập phiếu.
    }
}

function ntpCsrf(): string
{
    if (!isset($_SESSION['thestars_nhap_tp_csrf'])) {
        $_SESSION['thestars_nhap_tp_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['thestars_nhap_tp_csrf'];
}
function ntpCheckCsrf(): void
{
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals(ntpCsrf(), $token)) {
        throw new RuntimeException('Phiên thao tác không hợp lệ. Hãy tải lại trang.');
    }
}
function ntpThongBao(string $message, string $type = 'ok'): void
{
    $_SESSION['thestars_nhap_tp_flash'] = [$type, $message];
}
function ntpLayThongBao(): ?array
{
    $flash = $_SESSION['thestars_nhap_tp_flash'] ?? null;
    unset($_SESSION['thestars_nhap_tp_flash']);
    return is_array($flash) ? $flash : null;
}
function ntpDiChuyen(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}
function ntpMaPhieu(int $ma): string
{
    return 'YCNTP' . str_pad((string) $ma, 6, '0', STR_PAD_LEFT);
}
function ntpFmtDate(mixed $date, bool $dateTime = false): string
{
    if (!$date) return '—';
    try { return (new DateTimeImmutable((string) $date))->format($dateTime ? 'd/m/Y H:i' : 'd/m/Y'); }
    catch (Throwable $e) { return (string) $date; }
}
function ntpFmtQty(mixed $val): string
{
    return number_format((float) $val, 2, ',', '.');
}
function ntpMoTrang(string $tieuDe, string $active = 'nhap-thanh-pham'): void
{
    global $ntpMenu;
    $isKiemDinh = $active === 'nhap-tp-kiem-dinh';
    $roleName = $isKiemDinh ? 'Bộ phận Kiểm định (demo)' : 'Quản lý xưởng (demo)';
    $username = $isKiemDinh ? 'Xem yêu cầu kiểm định' : ntpTaiKhoan()['TenDangNhap'];
    $baseUrl = rtrim($ntpMenu['baseUrl'], '/');
    // Menu riêng cho UC, không thay đổi menu/role của các thành viên khác.
    $items = [
        ['key'=>'trang-chu', 'label'=>'Trang chủ', 'url'=>$baseUrl.'/index.php', 'icon'=>'⌂'],
        ['key'=>'nhap-thanh-pham', 'label'=>'Yêu cầu nhập thành phẩm', 'url'=>$baseUrl.'/pages/nhap-thanh-pham/index.php', 'icon'=>'▤'],
        ['key'=>'nhap-tp-kiem-dinh', 'label'=>'Danh sách chờ kiểm định', 'url'=>$baseUrl.'/pages/nhap-thanh-pham/kiem-dinh.php', 'icon'=>'✓'],
    ];
    $cssPath = dirname(__DIR__, 2) . '/assets/css/nhap-thanh-pham.css';
    $cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '3';
    // CSS được nạp qua link như thông thường, đồng thời nhúng trực tiếp
    // vào <head> từ file trên máy chủ để tránh lỗi 404, rewrite hoặc cache
    // khiến nội dung mất hết kiểu dáng dù header/sidebar vẫn hiển thị.
    ob_start();
    renderHeader($tieuDe, (string) $username, $roleName, $ntpMenu['baseUrl'], [
        $baseUrl . '/assets/css/nhap-thanh-pham.css?v=' . $cssVersion,
    ]);
    $header = (string) ob_get_clean();
    if (is_readable($cssPath)) {
        $inlineCss = file_get_contents($cssPath);
        if ($inlineCss !== false) {
            $inlineCss = str_ireplace('</style', '<\/style', $inlineCss);
            $header = str_ireplace('</head>', "<style id=\"ntp-inline-css\">\n" . $inlineCss . "\n</style>\n</head>", $header);
        }
    } else {
        error_log('[NTP CSS] Không tìm thấy file stylesheet: ' . $cssPath);
    }
    echo $header;
    renderSidebar($items, $active, $roleName, []);
}
function ntpDongTrang(): void
{
    renderFooter();
}
function ntpMaDuongDan(mixed $raw): int
{
    $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(400);
        exit('Mã phiếu không hợp lệ.');
    }
    return $id;
}
