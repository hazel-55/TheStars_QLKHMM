<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

// Đường dẫn thư mục gốc dự án.
$rootPath = dirname(__DIR__, 2);

require_once $rootPath . '/class/common/connect.php';

require_once $rootPath
    . '/class/nguyenTuanKiet/quanLyThongTinCaNhan/ThongTinCaNhan.php';

$menuConfig = require $rootPath . '/class/common/menuconfig.php';

$quanLyCaNhan = new ThongTinCaNhan($conn);

// Tài khoản mẫu để chạy thử không cần đăng nhập.
$maTaiKhoan = 'ND001';

$baseUrl = rtrim($menuConfig['baseUrl'], '/');

$pageUrl = $baseUrl
    . '/pages/thong-tin-ca-nhan/thong-tin-ca-nhan.php';

$viewUrl = $pageUrl . '?mode=view';
$editUrl = $pageUrl . '?mode=edit';

function caNhanEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Mặc định hiển thị trang chủ.
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

$mode = $_GET['mode'] ?? 'home';

if (
    !is_string($mode) ||
    !in_array($mode, ['home', 'view', 'edit'], true)
) {
    $mode = 'home';
}

if ($isPost) {
    $mode = 'edit';
}

// Lấy thông tin cá nhân qua class.
try {
    $nguoiDung = $quanLyCaNhan->layThongTin($maTaiKhoan);

    if (!$nguoiDung) {
        http_response_code(404);
        exit('Không tìm thấy người dùng mẫu ND001.');
    }
} catch (Throwable $e) {
    error_log((string) $e);

    http_response_code(500);
    exit('Không thể tải thông tin cá nhân.');
}

$form = $quanLyCaNhan->chuanHoaDuLieu($nguoiDung);
$loi = [];

$thongBao = $_SESSION['thong_bao_ca_nhan'] ?? '';
unset($_SESSION['thong_bao_ca_nhan']);

if (
    empty($_SESSION['csrf_ca_nhan']) ||
    !is_string($_SESSION['csrf_ca_nhan'])
) {
    $_SESSION['csrf_ca_nhan'] = bin2hex(random_bytes(32));
}

// Xử lý lưu thông tin.
if ($isPost) {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_ca_nhan'], $token)
    ) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }

    $form = $quanLyCaNhan->chuanHoaDuLieu($_POST);

    $loi = $quanLyCaNhan->kiemTraDuLieu(
        $form,
        $menuConfig['roles']
    );

    if (!$loi) {
        try {
            $quanLyCaNhan->capNhat($maTaiKhoan, $form);

            $_SESSION['thong_bao_ca_nhan'] =
                'Cập nhật thông tin cá nhân thành công.';

            $_SESSION['csrf_ca_nhan'] =
                bin2hex(random_bytes(32));

            header('Location: ' . $viewUrl, true, 303);
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log((string) $e);

            $loi['Chung'] = $e->getCode() === 1062
                ? 'Thông tin cập nhật trùng với dữ liệu đã được sử dụng.'
                : 'Không thể lưu thông tin. Vui lòng thử lại sau.';
        }
    }
}

// Lấy menu của vai trò đã lưu trong database.
$roleCode = (string) ($nguoiDung['VaiTro'] ?? '');

$roleConfig = $menuConfig['roles'][$roleCode] ?? [
    'roleName' => $roleCode,
    'items' => [],
];

$roleName = (string) $roleConfig['roleName'];

$tieuDe = [
    'home' => 'Trang chủ',
    'view' => 'Thông tin cá nhân',
    'edit' => 'Chỉnh sửa thông tin cá nhân',
][$mode];

// Gọi các thành phần giao diện chung.
require_once $rootPath . '/components/layout/header.php';
require_once $rootPath . '/components/layout/sidebar.php';
require_once $rootPath . '/components/layout/footer.php';

renderHeader(
    $tieuDe,
    (string) $nguoiDung['HoTen'],
    $roleName,
    $baseUrl
);
?>

<link
    rel="stylesheet"
    href="<?= caNhanEscape(
        $baseUrl . '/assets/css/thong-tin-ca-nhan.css'
    ) ?>"
>

<script
    src="<?= caNhanEscape(
        $baseUrl . '/assets/js/thong-tin-ca-nhan.js'
    ) ?>"
    defer
></script>

<div class="personal-layout">
    <?php
    renderSidebar(
        $roleConfig['items'] ?? [],
        $mode === 'home' ? '' : 'thong-tin-ca-nhan',
        $roleName,
        $menuConfig['bottomItems'] ?? []
    );
    ?>

    <main class="personal-content">
        <section class="personal-card" aria-labelledby="personal-title">
            <h1 id="personal-title">
                <?= caNhanEscape($tieuDe) ?>
            </h1>

            <?php if ($thongBao !== ''): ?>
                <p class="personal-success" role="status">
                    <?= caNhanEscape($thongBao) ?>
                </p>
            <?php endif; ?>

            <?php if ($mode === 'home'): ?>

                <!-- TRANG CHỦ -->
                <p class="personal-description">
                    Quản lý hồ sơ và cập nhật thông tin cá nhân của bạn.
                </p>

                <div class="personal-welcome">
                    <div
                        class="personal-welcome-icon"
                        aria-hidden="true"
                    >
                        ○
                    </div>

                    <h2>
                        Xin chào,
                        <?= caNhanEscape($nguoiDung['HoTen']) ?>!
                    </h2>

                    <p>
                        Xem thông tin tài khoản, kiểm tra thông tin
                        liên hệ và cập nhật hồ sơ của bạn tại đây.
                    </p>

                    <div class="personal-actions">
                        <a
                            class="personal-button"
                            href="<?= caNhanEscape($viewUrl) ?>"
                        >
                            Xem thông tin cá nhân →
                        </a>
                    </div>
                </div>

            <?php elseif ($mode === 'view'): ?>

                <!-- XEM THÔNG TIN -->
                <p class="personal-description">
                    Thông tin tài khoản và hồ sơ hiện tại của bạn.
                </p>

                <?php
                $thongTin = [
                    'Mã tài khoản' => $nguoiDung['MaTaiKhoan'],
                    'Họ và tên' => $nguoiDung['HoTen'],
                    'Email' => $nguoiDung['Email'],
                    'Ngày sinh' => $quanLyCaNhan->dinhDangNgay(
                        $nguoiDung['NgaySinh']
                    ),
                    'Số điện thoại' => $nguoiDung['SoDienThoai'],
                    'Vai trò' => $roleName,
                    'Bộ phận' => $nguoiDung['BoPhan'],
                ];
                ?>

                <dl class="personal-details">
                    <?php foreach ($thongTin as $nhan => $giaTri): ?>
                        <div class="personal-detail">
                            <dt><?= caNhanEscape($nhan) ?></dt>

                            <dd>
                                <?= caNhanEscape(
                                    $giaTri === null || $giaTri === ''
                                        ? 'Chưa cập nhật'
                                        : $giaTri
                                ) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>

                <div class="personal-actions">
                    <a
                        class="personal-button"
                        href="<?= caNhanEscape($editUrl) ?>"
                    >
                        Chỉnh sửa thông tin
                    </a>

                    <a
                        class="personal-cancel"
                        href="<?= caNhanEscape($pageUrl) ?>"
                    >
                        Về trang chủ
                    </a>
                </div>

            <?php else: ?>

                <!-- CHỈNH SỬA THÔNG TIN -->
                <p class="personal-description">
                    Cập nhật thông tin bên dưới.
                    Các trường có dấu * là bắt buộc.
                </p>

                <?php if (isset($loi['Chung'])): ?>
                    <p class="personal-error" role="alert">
                        <?= caNhanEscape($loi['Chung']) ?>
                    </p>
                <?php endif; ?>

                <form
                    id="personal-form"
                    method="post"
                    action="<?= caNhanEscape($editUrl) ?>"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= caNhanEscape(
                            $_SESSION['csrf_ca_nhan']
                        ) ?>"
                    >

                    <div class="personal-field">
                        <label for="MaTaiKhoan">Mã tài khoản</label>

                        <input
                            id="MaTaiKhoan"
                            readonly
                            value="<?= caNhanEscape(
                                $nguoiDung['MaTaiKhoan']
                            ) ?>"
                        >
                    </div>

                    <?php
                    $fields = [
                        'HoTen' => [
                            'label' => 'Họ và tên',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 100,
                            'autocomplete' => 'name',
                        ],
                        'Email' => [
                            'label' => 'Email',
                            'type' => 'email',
                            'required' => true,
                            'maxlength' => 254,
                            'autocomplete' => 'email',
                        ],
                        'NgaySinh' => [
                            'label' => 'Ngày sinh',
                            'type' => 'date',
                            'required' => false,
                            'maxlength' => null,
                            'autocomplete' => 'bday',
                        ],
                        'SoDienThoai' => [
                            'label' => 'Số điện thoại',
                            'type' => 'tel',
                            'required' => false,
                            'maxlength' => 16,
                            'autocomplete' => 'tel',
                        ],
                    ];

                    $homNay = new DateTimeImmutable(
                        'today',
                        new DateTimeZone('Asia/Ho_Chi_Minh')
                    );
                    ?>

                    <?php foreach ($fields as $name => $field): ?>
                        <div class="personal-field">
                            <label for="<?= caNhanEscape($name) ?>">
                                <?= caNhanEscape($field['label']) ?>
                                <?= $field['required'] ? ' *' : '' ?>
                            </label>

                            <input
                                id="<?= caNhanEscape($name) ?>"
                                name="<?= caNhanEscape($name) ?>"
                                type="<?= caNhanEscape($field['type']) ?>"
                                value="<?= caNhanEscape($form[$name]) ?>"
                                autocomplete="<?= caNhanEscape(
                                    $field['autocomplete']
                                ) ?>"
                                <?= $field['required'] ? 'required' : '' ?>

                                <?php if ($field['maxlength'] !== null): ?>
                                    maxlength="<?= (int) $field['maxlength'] ?>"
                                <?php endif; ?>

                                <?php if ($name === 'NgaySinh'): ?>
                                    min="1000-01-01"
                                    max="<?= caNhanEscape(
                                        $homNay->format('Y-m-d')
                                    ) ?>"
                                <?php endif; ?>

                                <?php if (isset($loi[$name])): ?>
                                    aria-invalid="true"
                                    aria-describedby="<?= caNhanEscape($name) ?>-error"
                                <?php endif; ?>
                            >

                            <?php if (isset($loi[$name])): ?>
                                <p
                                    class="personal-error"
                                    id="<?= caNhanEscape($name) ?>-error"
                                    role="alert"
                                >
                                    <?= caNhanEscape($loi[$name]) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="personal-field">
                        <label for="VaiTro">Vai trò *</label>

                        <select
                            id="VaiTro"
                            name="VaiTro"
                            required

                            <?php if (isset($loi['VaiTro'])): ?>
                                aria-invalid="true"
                                aria-describedby="VaiTro-error"
                            <?php endif; ?>
                        >
                            <option value="">-- Chọn vai trò --</option>

                            <?php foreach ($menuConfig['roles'] as $ma => $config): ?>
                                <option
                                    value="<?= caNhanEscape($ma) ?>"
                                    <?= $form['VaiTro'] === (string) $ma
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= caNhanEscape($config['roleName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (isset($loi['VaiTro'])): ?>
                            <p
                                class="personal-error"
                                id="VaiTro-error"
                                role="alert"
                            >
                                <?= caNhanEscape($loi['VaiTro']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="personal-field">
                        <label for="BoPhan">Bộ phận</label>

                        <input
                            id="BoPhan"
                            readonly
                            value="<?= caNhanEscape(
                                $nguoiDung['BoPhan']
                            ) ?>"
                        >
                    </div>

                    <div class="personal-actions">
                        <button
                            id="personal-save"
                            class="personal-button"
                            type="submit"
                        >
                            Lưu thay đổi
                        </button>

                        <a
                            id="personal-cancel"
                            class="personal-cancel"
                            href="<?= caNhanEscape($viewUrl) ?>"
                        >
                            Hủy
                        </a>
                    </div>
                </form>

            <?php endif; ?>
        </section>
    </main>
</div>

<?php renderFooter(); ?>