<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_GET['maPhieu']) || !is_string($_GET['maPhieu']) || trim($_GET['maPhieu']) === '') {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/../../class/common/connect.php';
require_once __DIR__ . '/../../class/nguyenThiHong/yeuCauKiemDinh/clskiemdinh.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
require_once __DIR__ . '/../../components/layout/footer.php';
$menuConfig = require __DIR__ . '/../../class/common/menuconfig.php';

// Tạm dùng local; sau này thay bằng tài khoản đăng nhập.
$maNhanVien = 'NVKD001';
$role = 'KIEM_DINH';
$userName = 'Nguyễn Thị Hồng';
if (empty($_SESSION['csrf_kiem_dinh'])) {
    $_SESSION['csrf_kiem_dinh'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_kiem_dinh'];
$kiemDinh = new KiemDinh($conn);
$trang = $kiemDinh->chuanBiTrang(
    $_GET, $_POST, $_SERVER['REQUEST_METHOD'], $maNhanVien, $csrf
);
$loi = $trang['loi'];
$thongBao = $trang['thongBao'];
$danhSach = $trang['danhSach'];
$phieu = $trang['phieu'];
$duLieuDaNhap = $trang['duLieuDaNhap'];
$maPhieu = $trang['maPhieu'];
$tuKhoa = $trang['tuKhoa'];
$trangThaiLoc = $trang['trangThaiLoc'];
$thamSoRouter = $trang['thamSoRouter'];
$urlDanhSach = 'index.php?' . http_build_query([
    'tuKhoa' => $trang['tuKhoa'],
    'trangThai' => $trang['trangThaiLoc'],
]);
$chiXem = $trang['chiXem'];
$phienBanForm = $trang['phienBanForm'];
$duLieuTrang = $trang['duLieuTrang'];
$roleConfig = $menuConfig['roles'][$role] ?? [
    'roleName' => 'Chưa xác định vai trò',
    'items' => [],
];
renderHeader(
    'Chi tiết kiểm định · ' . $maPhieu,
    $userName,
    $roleConfig['roleName'],
    $menuConfig['baseUrl']
);
renderSidebar(
    $roleConfig['items'],
    'kiem-dinh',
    $roleConfig['roleName'],
    $menuConfig['bottomItems'] ?? []
);
?>
<!-- Nạp trực tiếp CSS của page: không phụ thuộc header có hỗ trợ tham số thứ 5. -->
<link rel="stylesheet" href="<?= htmlspecialchars(rtrim($menuConfig['baseUrl'], '/') . '/assets/css/kiem-dinh.css?v=4', ENT_QUOTES, 'UTF-8') ?>">
<main class="app-main">
<div class="kd-page">
    <?php if ($loi !== ''): ?>
        <div class="kd-error" role="alert">
            <?= htmlspecialchars($loi, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($maPhieu !== ''): ?>
                <p>
                    <a href="<?= htmlspecialchars(
                        '?' . http_build_query(array_merge(
                            $thamSoRouter,
                            ['maPhieu' => $maPhieu]
                        )),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">
                        Tải lại dữ liệu phiếu
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($thongBao !== ''): ?>
        <div class="kd-success" role="status">
            <?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($phieu): ?>

        <a href="<?= htmlspecialchars(
            $urlDanhSach,
            ENT_QUOTES,
            'UTF-8'
        ) ?>">
            ← Danh sách phiếu kiểm định
        </a>
        <h1 style="margin-top:20px">
            <?= htmlspecialchars(
                $phieu['MaPhieu'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>
        <span class="kd-badge">
            <?= htmlspecialchars(
                $phieu['TrangThai'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>
        <div class="kd-card kd-info">
            <div>
                Người gửi
                <strong>
                    <?= htmlspecialchars(
                        $phieu['TenNguoiGui'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
            <div>
                Ngày lập
                <strong>
                    <?= htmlspecialchars(
                        $phieu['NgayLap'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
            <div>
                Nguồn yêu cầu
                <strong>
                    <?= htmlspecialchars(
                        $phieu['MaDonNCC']
                            ?? $phieu['MaYCNhapTP']
                            ?? $phieu['MaBBHoanTra']
                            ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
            <div>
                Mục đích kiểm định
                <strong>
                    <?= htmlspecialchars(
                        $phieu['MucDichKD'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
        </div>
        <div class="kd-rule">
            Đối với phiếu kiểm định có nhiều mặt hàng, cần tách nhóm nếu kết quả khác nhau.
    Tổng số lượng các nhóm của mỗi mặt hàng phải bằng số lượng yêu cầu kiểm định.
        </div>
        <form method="post" id="kd-form">
            <input
                type="hidden"
                name="csrf"
                value="<?= htmlspecialchars(
                    $csrf,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
            <input
                type="hidden"
                name="phienBan"
                value="<?= htmlspecialchars(
                    (string) $phienBanForm,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
            <input type="hidden" name="duLieu" id="kd-payload">
            <noscript><div class="kd-error">Cần bật JavaScript để thêm nhóm và lưu kết quả. Thông tin phiếu vẫn có thể xem bên dưới.</div></noscript>
            <div id="kd-items" data-readonly="<?= $chiXem ? '1' : '0' ?>">
                <?php foreach ($phieu['HangHoa'] as $hang): ?>
                    <section class="kd-card kd-item" data-id="<?= (int) $hang['MaCTKD'] ?>" data-qty="<?= htmlspecialchars($hang['SoLuongYeuCauKiemDinh'], ENT_QUOTES, 'UTF-8') ?>" data-unit="<?= htmlspecialchars($hang['DonViTinh'], ENT_QUOTES, 'UTF-8') ?>">
                        <h2>Kiểm định: <?= htmlspecialchars($hang['TenHang'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="kd-muted"><?= htmlspecialchars($hang['MaHang'].' · '.$hang['DanhMuc'].' · '.$hang['QuyCach'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Số lượng yêu cầu: <strong><?= htmlspecialchars(
    rtrim(
        rtrim((string) $hang['SoLuongYeuCauKiemDinh'], '0'),
        '.'
    ) . ' ' . $hang['DonViTinh'],
    ENT_QUOTES,
    'UTF-8'
) ?></strong></p>
                        <div class="kd-summary">
                            <div>Đã phân nhóm<b class="kd-allocated">—</b></div>
                            <div>Đạt<b class="kd-passed"><?= htmlspecialchars($hang['SoLuongDat'], ENT_QUOTES, 'UTF-8') ?></b></div>
                            <div>Không đạt<b class="kd-failed"><?= htmlspecialchars($hang['SoLuongKhongDat'], ENT_QUOTES, 'UTF-8') ?></b></div>
                            <div>Chưa kiểm định<b class="kd-pending">—</b></div>
                        </div>
                        <p class="kd-validation kd-muted" role="status"></p>
                        <?php if (empty($hang['TieuChi'])): ?><div class="kd-error">Dòng hàng này chưa có bộ tiêu chí. Cần bổ sung tiêu chí trước khi kiểm định.</div><?php endif; ?>
                        <div class="kd-groups">
                            <?php foreach ($hang['NhomKiemDinh'] as $stt => $nhom): ?>
                                <section class="kd-group">
    <div class="kd-group-head">
        <strong class="kd-group-title">Nhóm <?= (int) $stt + 1 ?></strong>
        <label>Số lượng (<?= htmlspecialchars($hang['DonViTinh'], ENT_QUOTES, 'UTF-8') ?>)
            <input class="kd-qty" type="number" aria-label="Số lượng nhóm" min="<?= $hang['DonViTinh'] === 'Mét' ? '0.001' : '1' ?>" step="<?= $hang['DonViTinh'] === 'Mét' ? '0.001' : '1' ?>" value="<?= htmlspecialchars(
    rtrim(
        rtrim((string) $nhom['SoLuong'], '0'),
        '.'
    ),
    ENT_QUOTES,
    'UTF-8'
) ?>" <?= $chiXem ? 'disabled' : '' ?>>
        </label>
        <span class="kd-badge kd-conclusion"><?= htmlspecialchars($nhom['KetLuan'] ?? 'Chưa hoàn tất', ENT_QUOTES, 'UTF-8') ?></span>
        <?php if (!$chiXem): ?><button type="button" class="kd-button kd-remove">Xóa nhóm</button><?php endif; ?>
    </div>
    <div class="kd-scroll">
        <table class="kd-criteria-table">
            <thead><tr><th>Tiêu chí bắt buộc</th><th>Yêu cầu đối chiếu</th><th>Kết quả</th><th>Ghi nhận thực tế</th></tr></thead>
            <tbody>
                <?php foreach ($hang['TieuChi'] as $tieuChi): ?>
                    <?php $ketQua = $nhom['KetQuaTieuChi'][$tieuChi['MaTieuChiYC']] ?? []; $ketQua = is_array($ketQua) ? $ketQua : []; ?>
                    <tr class="kd-criterion" data-id="<?= (int) $tieuChi['MaTieuChiYC'] ?>">
                        <td><strong><?= htmlspecialchars($tieuChi['TenTieuChi'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($tieuChi['YeuCau'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><select class="kd-criterion-result" aria-label="<?= htmlspecialchars($tieuChi['TenTieuChi'], ENT_QUOTES, 'UTF-8') ?>" <?= $chiXem ? 'disabled' : '' ?>>
                            <option value="">Chưa đánh giá</option>
                            <option value="Đạt" <?= ($ketQua['KetQua'] ?? '') === 'Đạt' ? 'selected' : '' ?>>Đạt</option>
                            <option value="Không đạt" <?= ($ketQua['KetQua'] ?? '') === 'Không đạt' ? 'selected' : '' ?>>Không đạt</option>
                        </select></td>
                        <td><textarea class="kd-criterion-note" maxlength="500" aria-label="Ghi chú <?= htmlspecialchars($tieuChi['TenTieuChi'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Giá trị đo / mô tả lỗi" <?= $chiXem ? 'disabled' : '' ?>><?= htmlspecialchars(is_scalar($ketQua['GhiChu'] ?? '') ? (string) ($ketQua['GhiChu'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <input class="kd-group-note" aria-label="Nhãn nhận diện nhóm" placeholder="Nhãn nhận diện / ghi chú nhóm" maxlength="300" value="<?= htmlspecialchars((string) ($nhom['GhiChu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $chiXem ? 'disabled' : '' ?>>
</section>

                            <?php endforeach; ?>
                        </div>
                        <?php if (!$chiXem): ?>
                            <template class="kd-template">
                                <?php $nhom = ['SoLuong' => '', 'GhiChu' => '', 'KetQuaTieuChi' => []]; $stt = 0; ?>
<section class="kd-group">
    <div class="kd-group-head">
        <strong class="kd-group-title">Nhóm <?= (int) $stt + 1 ?></strong>
        <label>Số lượng (<?= htmlspecialchars($hang['DonViTinh'], ENT_QUOTES, 'UTF-8') ?>)
            <input class="kd-qty" type="number" aria-label="Số lượng nhóm" min="<?= $hang['DonViTinh'] === 'Mét' ? '0.001' : '1' ?>" step="<?= $hang['DonViTinh'] === 'Mét' ? '0.001' : '1' ?>" value="<?= htmlspecialchars((string) $nhom['SoLuong'], ENT_QUOTES, 'UTF-8') ?>" <?= $chiXem ? 'disabled' : '' ?>>
        </label>
        <span class="kd-badge kd-conclusion"><?= htmlspecialchars($nhom['KetLuan'] ?? 'Chưa hoàn tất', ENT_QUOTES, 'UTF-8') ?></span>
        <?php if (!$chiXem): ?><button type="button" class="kd-button kd-remove">Xóa nhóm</button><?php endif; ?>
    </div>
    <div class="kd-scroll">
        <table class="kd-criteria-table">
            <thead><tr><th>Tiêu chí bắt buộc</th><th>Yêu cầu đối chiếu</th><th>Kết quả</th><th>Ghi nhận thực tế</th></tr></thead>
            <tbody>
                <?php foreach ($hang['TieuChi'] as $tieuChi): ?>
                    <?php $ketQua = $nhom['KetQuaTieuChi'][$tieuChi['MaTieuChiYC']] ?? []; $ketQua = is_array($ketQua) ? $ketQua : []; ?>
                    <tr class="kd-criterion" data-id="<?= (int) $tieuChi['MaTieuChiYC'] ?>">
                        <td><strong><?= htmlspecialchars($tieuChi['TenTieuChi'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($tieuChi['YeuCau'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><select class="kd-criterion-result" aria-label="<?= htmlspecialchars($tieuChi['TenTieuChi'], ENT_QUOTES, 'UTF-8') ?>" <?= $chiXem ? 'disabled' : '' ?>>
                            <option value="">Chưa đánh giá</option>
                            <option value="Đạt" <?= ($ketQua['KetQua'] ?? '') === 'Đạt' ? 'selected' : '' ?>>Đạt</option>
                            <option value="Không đạt" <?= ($ketQua['KetQua'] ?? '') === 'Không đạt' ? 'selected' : '' ?>>Không đạt</option>
                        </select></td>
                        <td><textarea class="kd-criterion-note" maxlength="500" aria-label="Ghi chú <?= htmlspecialchars($tieuChi['TenTieuChi'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Giá trị đo / mô tả lỗi" <?= $chiXem ? 'disabled' : '' ?>><?= htmlspecialchars(is_scalar($ketQua['GhiChu'] ?? '') ? (string) ($ketQua['GhiChu'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <input class="kd-group-note" aria-label="Nhãn nhận diện nhóm" placeholder="Nhãn nhận diện / ghi chú nhóm" maxlength="300" value="<?= htmlspecialchars((string) ($nhom['GhiChu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $chiXem ? 'disabled' : '' ?>>
</section>

                            </template>
                            <button type="button" class="kd-button kd-add">+ Thêm nhóm kiểm định</button>
                        <?php elseif (empty($hang['NhomKiemDinh'])): ?>
                            <p class="kd-muted">Phiếu chưa ghi nhận nhóm kiểm định.</p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
                <?php if (empty($phieu['HangHoa'])): ?><div class="kd-error">Phiếu chưa có dòng hàng hóa để kiểm định.</div><?php endif; ?>
            </div>
            <p id="kd-form-error" class="kd-invalid" role="alert"></p>
            <div class="kd-actions">
                <a
                    class="kd-button"
                    href="<?= htmlspecialchars(
                        $urlDanhSach,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    Quay lại
                </a>
                <?php if (!$chiXem): ?>
                    <button
                        type="submit"
                        class="kd-button"
                        disabled data-kd-save name="hanhDong"
                        value="luu_nhap"
                    >
                        Lưu nháp
                    </button>
                    <button
                        type="submit"
                        class="kd-button kd-primary"
                        disabled data-kd-save name="hanhDong"
                        value="hoan_tat"
                    >
                        Hoàn tất kiểm định
                    </button>
                <?php endif; ?>
            </div>
        </form>

    <?php endif; ?>
</div>
</main>

<script src="<?= htmlspecialchars(rtrim($menuConfig['baseUrl'], '/') . '/assets/js/kiem-dinh.js?v=4', ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
if (window.KiemDinhPage) {
    KiemDinhPage.init();
} else {
    const error = document.getElementById('kd-form-error');
    if (error) error.textContent = 'Chưa tải được assets/js/kiem-dinh.js. Kiểm tra đường dẫn rồi tải lại trang; chức năng lưu đang tạm khóa.';
}
</script>
<?php renderFooter(); ?>
