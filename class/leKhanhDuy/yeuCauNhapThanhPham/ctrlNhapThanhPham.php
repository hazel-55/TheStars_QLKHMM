<?php
/** Controller nhận POST các thao tác; trang UI trong pages/nhap-thanh-pham. */
declare(strict_types=1);

require_once __DIR__ . '/../../../pages/nhap-thanh-pham/_init.php';

function xuLyNhapThanhPham(): never
{
    global $ntpModel;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Chỉ hỗ trợ POST.');
    }
    ntpYeuCauVaiTro('QUAN_LY_XUONG');
    $userId = (int) (ntpTaiKhoan()['MaTaiKhoan'] ?? 0);
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $id = 0;
    try {
        ntpCheckCsrf();
        if (in_array($action, ['luu_nhap', 'gui_phieu'], true)) {
            $id = $ntpModel->luuPhieu($userId, [
                'NgayHoanThanhSanXuat' => $_POST['ngayHoanThanh'] ?? '',
                'GhiChu' => $_POST['ghiChu'] ?? '',
                'MaThanhPham' => $_POST['maThanhPham'] ?? [],
                'SoLuongYeuCau' => $_POST['soLuong'] ?? [],
            ], $action === 'gui_phieu', null);
            unset($_SESSION['thestars_nhap_tp_old']);
            ntpThongBao($action === 'gui_phieu'
                ? 'Đã gửi phiếu sang Bộ phận Kiểm định (trạng thái Chờ kiểm định).'
                : 'Lưu phiếu Nháp thành công.');
            ntpDiChuyen('chi-tiet.php?id=' . $id);
        }
        if (in_array($action, ['cap_nhat_nhap', 'cap_nhat_gui'], true)) {
            $id = ntpMaDuongDan($_POST['id'] ?? null);
            $ntpModel->luuPhieu($userId, [
                'NgayHoanThanhSanXuat' => $_POST['ngayHoanThanh'] ?? '',
                'GhiChu' => $_POST['ghiChu'] ?? '',
                'MaThanhPham' => $_POST['maThanhPham'] ?? [],
                'SoLuongYeuCau' => $_POST['soLuong'] ?? [],
            ], $action === 'cap_nhat_gui', $id);
            unset($_SESSION['thestars_nhap_tp_old']);
            ntpThongBao($action === 'cap_nhat_gui'
                ? 'Cập nhật và gửi phiếu sang Bộ phận Kiểm định thành công.'
                : 'Đã cập nhật bản Nháp.');
            ntpDiChuyen('chi-tiet.php?id=' . $id);
        }
        if ($action === 'gui_nhap') {
            $id = ntpMaDuongDan($_POST['id'] ?? null);
            $ntpModel->guiPhieuNhap($id, $userId);
            ntpThongBao('Đã gửi yêu cầu đến Bộ phận Kiểm định.');
            ntpDiChuyen('chi-tiet.php?id=' . $id);
        }
        if ($action === 'huy_nhap') {
            $id = ntpMaDuongDan($_POST['id'] ?? null);
            $ntpModel->huyPhieuNhap($id, $userId);
            ntpThongBao('Đã hủy phiếu Nháp.');
            ntpDiChuyen('index.php');
        }
        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (InvalidArgumentException | RuntimeException $e) {
        ntpThongBao($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('[Duy NTP] ' . $e->getMessage());
        ntpThongBao('Không thể lưu phiếu. Dữ liệu không thay đổi. Kiểm tra CSDL và thử lại.', 'error');
    }
    if (in_array($action, ['luu_nhap', 'gui_phieu', 'cap_nhat_nhap', 'cap_nhat_gui'], true)) {
        $_SESSION['thestars_nhap_tp_old'] = [
            'NgayHoanThanhSanXuat' => is_string($_POST['ngayHoanThanh'] ?? null) ? $_POST['ngayHoanThanh'] : '',
            'GhiChu' => is_string($_POST['ghiChu'] ?? null) ? $_POST['ghiChu'] : '',
            'MaThanhPham' => is_array($_POST['maThanhPham'] ?? null) ? $_POST['maThanhPham'] : [],
            'SoLuongYeuCau' => is_array($_POST['soLuong'] ?? null) ? $_POST['soLuong'] : [],
        ];
        ntpDiChuyen('lap-phieu.php' . ($id ? '?id=' . $id : ''));
    }
    ntpDiChuyen($id ? 'chi-tiet.php?id=' . $id : 'index.php');
}
