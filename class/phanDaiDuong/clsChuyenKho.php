<?php
/** UC Lập yêu cầu chuyển kho - tất cả xử lý trong một file cls. */

// ===== ChuyenKhoDatabase.php =====
/** Connection helper for the transfer request feature; credentials come from .env. */
class ChuyenKhoDatabase
{
    public static function connect($c)
    {
        return new PDO(
            'mysql:host=' . $c['host'] . ';port=' . $c['port'] . ';dbname=' . $c['database'] . ';charset=utf8',
            $c['user'],
            $c['password'],
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            )
        );
    }
}

// ===== ChuyenKhoRepository.php =====
/** Parameterized SQL queries for the independent transfer request tables. */
class ChuyenKhoRepository
{
    public static function query($db, $sql, $params = array())
    {
        $statement = $db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}

// ===== ChuyenKhoValidator.php =====
/** UC rules: stock availability, warehouse state, valid positive quantities. */
class ChuyenKhoValidator
{
    public static function validateLines($rows)
    {
        if (!is_array($rows) || count($rows) < 1 || count($rows) > 100)
            throw new RuntimeException('Phiếu cần từ 1 đến 100 dòng hàng.');
        $out = [];
        $seen = [];
        foreach ($rows as $r) {
            if (!is_array($r))
                throw new RuntimeException('Dòng hàng không hợp lệ.');
            $id = (string) ($r['maBienThe'] ?? '');
            $qty = trim((string) ($r['soLuongYeuCau'] ?? ''));
            $note = trim((string) ($r['ghiChu'] ?? ''));
            if ($id === '' || isset($seen[$id]))
                throw new RuntimeException('Chọn hàng cho từng dòng và không chọn trùng biến thể.');
            if (!preg_match('/^\d{1,9}(\.\d{1,3})?$/D', $qty) || (float) $qty <= 0)
                throw new RuntimeException('Số lượng phải lớn hơn 0, tối đa 3 chữ số thập phân.');
            if (mb_strlen($note) > 255)
                throw new RuntimeException('Ghi chú tối đa 255 ký tự.');
            $seen[$id] = true;
            $out[] = ['maBienThe' => $id, 'soLuongYeuCau' => $qty, 'ghiChu' => $note];
        }
        return $out;
    }
    public static function validateRequest($db, $form, $rows)
    {
        if ($form['maKhoXuat'] === $form['maKhoNhap'])
            throw new RuntimeException('Kho xuất và kho nhập phải khác nhau.');
        foreach (['maKhoXuat', 'maKhoNhap'] as $key) {
            if (!ChuyenKhoRepository::query($db, 'SELECT maKho FROM CK_Kho WHERE maKho=? AND dangHoatDong=1', [$form[$key]])->fetch())
                throw new RuntimeException('Kho không tồn tại hoặc đã ngừng hoạt động.');
        }
        if (trim($form['lyDo']) === '' || mb_strlen($form['lyDo']) > 500)
            throw new RuntimeException('Nhập lý do chuyển kho, tối đa 500 ký tự.');
        $rows = self::validateLines($rows);
        // Khoa dong ton trong transaction de kiem tra nhat quan; khong giu cho/tru ton.
        usort($rows, fn($a, $b) => strcmp($a['maBienThe'], $b['maBienThe']));
        foreach ($rows as $r) {
            $p = ChuyenKhoRepository::query($db, 'SELECT hh.tenHangHoa, hh.donViTinh FROM CK_BienThe bt JOIN CK_HangHoa hh ON hh.maHangHoa=bt.maHangHoa WHERE bt.maBienThe=? AND hh.dangHoatDong=1', [$r['maBienThe']])->fetch();
            if (!$p)
                throw new RuntimeException('Hàng hóa không tồn tại hoặc ngừng hoạt động.');
            if ($p['donViTinh'] === 'Cái' && floor((float) $r['soLuongYeuCau']) != (float) $r['soLuongYeuCau'])
                throw new RuntimeException('Hàng có đơn vị Cái phải nhập số nguyên.');
            $s = ChuyenKhoRepository::query($db, 'SELECT soLuongTon-soLuongGiuCho AS available FROM CK_TonKho WHERE maKho=? AND maBienThe=? FOR UPDATE', [$form['maKhoXuat'], $r['maBienThe']])->fetch();
            $available = $s ? (float) $s['available'] : 0;
            if ((float) $r['soLuongYeuCau'] > $available)
                throw new RuntimeException($r['maBienThe'] . ' chỉ còn ' . $available . ' khả dụng tại kho xuất.');
        }
        return $rows;
    }
}

// ===== ChuyenKhoController.php =====
/** Page controller for create, update, cancel, send, list and view. */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
$labels = ['NHAP' => 'Nháp', 'CHO_PHE_DUYET' => 'Chờ phê duyệt', 'DA_DUYET' => 'Đã duyệt', 'TU_CHOI' => 'Từ chối', 'DA_HUY' => 'Đã hủy'];
$_SESSION['ck_csrf'] = $_SESSION['ck_csrf'] ?? bin2hex(random_bytes(32));
$envPath = dirname(__DIR__, 2) . '/.env';
if (!is_readable($envPath)) {
    die('Không tìm thấy file .env ở thư mục gốc project.');
}
$c = parse_ini_file($envPath, false, INI_SCANNER_RAW);
if ($c === false) {
    die('File .env không hợp lệ.');
}
$c = [
    'host' => $c['DB_HOST'] ?? 'localhost',
    'port' => (int) ($c['DB_PORT'] ?? 3306),
    'database' => $c['DB_NAME'] ?? 'thestars_qlkhmm_db',
    'user' => $c['DB_USER'] ?? 'root',
    'password' => $c['DB_PASSWORD'] ?? '',
];
$demoUser = ['maNguoiDung' => 'ND01', 'hoTen' => 'Phan Đại Dương', 'vaiTro' => 'QUAN_LY_KHO'];
$error = '';
$db = null;
$user = null;
$warehouses = [];
$products = [];
$stock = [];
$list = [];
$edit = null;
$details = [];
$form = ['maKhoXuat' => 'KHO01', 'maKhoNhap' => 'KHO02', 'lyDo' => ''];
$inputRows = [['maBienThe' => '', 'soLuongYeuCau' => '', 'ghiChu' => '']];
try {
    $db = ChuyenKhoDatabase::connect($c);
    // Người lập phiếu demo chỉ thuộc UC chuyển kho; không đọc/sửa bảng NguoiDung của module Kiểm định.
    $user = $demoUser;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $id = (string) ($_POST['id'] ?? '');
        $form = ['maKhoXuat' => (string) ($_POST['maKhoXuat'] ?? ''), 'maKhoNhap' => (string) ($_POST['maKhoNhap'] ?? ''), 'lyDo' => trim((string) ($_POST['lyDo'] ?? ''))];
        $inputRows = is_array($_POST['rows'] ?? null) ? $_POST['rows'] : [];
        try {
            if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['ck_csrf'], $_POST['csrf']))
                throw new RuntimeException('Phiên thao tác hết hạn. Tải lại trang và thử lại.');
            if (!in_array($action, ['save', 'send', 'cancel'], true))
                throw new RuntimeException('Thao tác không hợp lệ.');
            $db->beginTransaction();
            if ($id !== '') {
                $old = ChuyenKhoRepository::query($db, 'SELECT * FROM yeucauchuyenkho WHERE maYeuCau=? FOR UPDATE', [$id])->fetch();
                if (!$old || $old['maNguoiLap'] !== $user['maNguoiDung'])
                    throw new RuntimeException('Không tìm thấy phiếu của bạn.');
                if ($old['trangThai'] !== 'NHAP')
                    throw new RuntimeException('Chỉ được sửa, gửi hoặc hủy phiếu đang Nháp.');
            } elseif ($action === 'cancel')
                throw new RuntimeException('Chưa chọn phiếu cần hủy.');
            if ($action === 'cancel') {
                ChuyenKhoRepository::query($db, "UPDATE yeucauchuyenkho SET trangThai='DA_HUY' WHERE maYeuCau=?", [$id]);
            } else {
                $rows = ChuyenKhoValidator::validateRequest($db, $form, $inputRows);
                $state = $action === 'send' ? 'CHO_PHE_DUYET' : 'NHAP';
                $sent = $action === 'send' ? date('Y-m-d H:i:s') : null;
                if ($id === '') {
                    // 20 ky tu, random de tranh trung khi nhieu phien tao dong thoi.
                    $id = 'YCCK' . date('ymd') . strtoupper(bin2hex(random_bytes(5)));
                    ChuyenKhoRepository::query($db, 'INSERT INTO yeucauchuyenkho(maYeuCau,maKhoXuat,maKhoNhap,maNguoiLap,lyDo,ngayGui,trangThai) VALUES (?,?,?,?,?,?,?)', [$id, $form['maKhoXuat'], $form['maKhoNhap'], $user['maNguoiDung'], $form['lyDo'], $sent, $state]);
                } else {
                    ChuyenKhoRepository::query($db, 'UPDATE yeucauchuyenkho SET maKhoXuat=?,maKhoNhap=?,lyDo=?,ngayGui=?,trangThai=? WHERE maYeuCau=?', [$form['maKhoXuat'], $form['maKhoNhap'], $form['lyDo'], $sent, $state, $id]);
                    ChuyenKhoRepository::query($db, 'DELETE FROM chitietyeucauchuyenkho WHERE maYeuCau=?', [$id]);
                }
                foreach ($rows as $r)
                    ChuyenKhoRepository::query($db, 'INSERT INTO chitietyeucauchuyenkho(maYeuCau,maBienThe,soLuongYeuCau,ghiChu) VALUES(?,?,?,?)', [$id, $r['maBienThe'], $r['soLuongYeuCau'], $r['ghiChu']]);
            }
            $db->commit();
            $_SESSION['ck_flash'] = $action === 'cancel' ? 'Đã hủy phiếu.' : ($action === 'send' ? 'Đã gửi yêu cầu phê duyệt. Tồn kho chưa thay đổi.' : 'Đã lưu bản nháp.');
            header('Location: index.php?view=' . rawurlencode($id));
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction())
                $db->rollBack();
            if ($e instanceof PDOException) {
                error_log($e->getMessage());
                $error = 'Không thể lưu dữ liệu. Kiểm tra cấu trúc database và log PHP.';
            } else
                $error = $e->getMessage();
        }
    }
    $warehouses = ChuyenKhoRepository::query($db, 'SELECT * FROM CK_Kho WHERE dangHoatDong=1 ORDER BY maKho')->fetchAll();
    $products = ChuyenKhoRepository::query($db, 'SELECT bt.*,hh.tenHangHoa,hh.donViTinh FROM CK_BienThe bt JOIN CK_HangHoa hh ON hh.maHangHoa=bt.maHangHoa WHERE hh.dangHoatDong=1 ORDER BY bt.maBienThe')->fetchAll();
    $stock = ChuyenKhoRepository::query($db, 'SELECT maKho,maBienThe,soLuongTon-soLuongGiuCho AS available FROM CK_TonKho')->fetchAll();
    $view = trim((string)($_GET['id'] ?? $_GET['view'] ?? ''));
    if ($view !== '') {
        $edit = ChuyenKhoRepository::query($db, 'SELECT * FROM yeucauchuyenkho WHERE maYeuCau=? AND maNguoiLap=?', [$view, $user['maNguoiDung']])->fetch();
        if (!$edit)
            throw new RuntimeException('Không tìm thấy phiếu của bạn.');
        $details = ChuyenKhoRepository::query($db, 'SELECT * FROM chitietyeucauchuyenkho WHERE maYeuCau=? ORDER BY maBienThe', [$view])->fetchAll();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $form = $edit;
            $inputRows = $details;
        }
    }
    $list = ChuyenKhoRepository::query($db, 'SELECT yc.*,kx.tenKho AS khoXuat,kn.tenKho AS khoNhap FROM yeucauchuyenkho yc JOIN CK_Kho kx ON kx.maKho=yc.maKhoXuat JOIN CK_Kho kn ON kn.maKho=yc.maKhoNhap WHERE yc.maNguoiLap=? ORDER BY yc.ngayLap DESC, yc.maYeuCau DESC', [$user['maNguoiDung']])->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = $e instanceof PDOException ? 'Không đọc được dữ liệu chuyển kho. Kiểm tra .env và import database_chuyen_kho.sql vào thestars_qlkhmm_db.' : $e->getMessage();
}
$readonly = $edit && $edit['trangThai'] !== 'NHAP';
$flash = $_SESSION['ck_flash'] ?? '';
unset($_SESSION['ck_flash']);
$counts = ['NHAP' => 0, 'CHO_PHE_DUYET' => 0, 'DA_HUY' => 0];
foreach ($list as $r)
    if (isset($counts[$r['trangThai']]))
        $counts[$r['trangThai']]++;
