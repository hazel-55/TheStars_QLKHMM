<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../class/tranTrungKien/lapPhieuKiemKe/db.php';
require_once __DIR__ . '/../../class/tranTrungKien/lapPhieuKiemKe/clsLapPhieuKiemKe.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
require_once __DIR__ . '/../../components/layout/footer.php';
$menuConfig = require __DIR__ . '/../../class/common/menuconfig.php';
$baseUrl = $menuConfig['baseUrl'];
function kienH($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function kienCsrf(): string {
    if (empty($_SESSION['kien_csrf'])) $_SESSION['kien_csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['kien_csrf'];
}
function kienVerifyCsrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals(kiemCsrfSafe(), (string)$_POST['csrf'])) {
        throw new InvalidArgumentException('Mã xác nhận không hợp lệ, hãy tải lại trang.');
    }
}
function kiemCsrfSafe(): string {return (string)($_SESSION['kien_csrf'] ?? '');}
function kienLocal(): bool {return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true);}
function kienUser(clsLapPhieuKiemKe $model): array {
    $id = (string)($_SESSION['maTaiKhoan'] ?? ($_SESSION['user']['maTaiKhoan'] ?? ''));
    $user = $id !== '' ? $model->nguoiDung($id) : null;
    if (!$user || !in_array($user['vaiTro'], ['BKK','Ban kiểm kê'], true)) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>Đăng nhập BKK</title><div style="font:16px Arial;max-width:580px;margin:70px auto;padding:24px;border:1px solid #ccc;border-radius:12px">';
        echo '<h2>Chưa đăng nhập với quyền Ban kiểm kê</h2><p>UC này yêu cầu phiên đăng nhập BKK hợp lệ.</p>';
        if (kienLocal()) echo '<p>Để kiểm tra trên WAMP, mở <a href="dev-login.php">đăng nhập thử nghiệm localhost</a>.</p>';
        echo '</div>';
        exit;
    }
    return $user;
}
function kienLayoutStart(string $title, array $user, array $menu, string $baseUrl): void {
    $items = [['key'=>'kiem-ke','label'=>'Lập phiếu kiểm kê','url'=>$baseUrl.'/pages/lap-phieu-kiem-ke/index.php','icon'=>'▤'],
              ['key'=>'tao-phieu','label'=>'Tạo phiếu mới','url'=>$baseUrl.'/pages/lap-phieu-kiem-ke/phieu.php','icon'=>'+']];
    renderHeader($title, $user['hoTen'], 'Ban kiểm kê', $baseUrl);
    renderSidebar($items, 'kiem-ke', 'Ban kiểm kê', $menu['bottomItems'] ?? []);
    echo '<link rel="stylesheet" href="'.kienH($baseUrl.'/assets/css/lap-phieu-kiem-ke.css?v=1').'">';
}
