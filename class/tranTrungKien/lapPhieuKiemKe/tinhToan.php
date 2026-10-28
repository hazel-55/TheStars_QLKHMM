<?php
/** Các số lượng kiểm kê chỉ dùng số nguyên không âm (đơn vị cơ bản của từng SKU). */
function kienDocSoNguyen($value, bool $allowBlank = false): ?int
{
    if ($value === null || trim((string)$value) === '') {
        if ($allowBlank) return null;
        throw new InvalidArgumentException('Chưa nhập số lượng của hàng đã chọn.');
    }
    $raw = trim((string)$value);
    // Cho phép 1234 hoặc 1 234 hoặc 1 234 567; KHÔNG nhận dấu chấm/phẩy thập phân.
    $raw = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $raw);
    if (!preg_match('/^(?:[0-9]{1,12}|[0-9]{1,3}(?: [0-9]{3}){1,3})$/D', $raw)) {
        throw new InvalidArgumentException('Chỉ nhập số nguyên không âm, ví dụ: 1200 hoặc 1 200.');
    }
    $clean = str_replace(' ', '', $raw);
    if (strlen($clean) > 12 || (float)$clean > 999999999999) {
        throw new InvalidArgumentException('Số lượng vượt giới hạn 999 999 999 999.');
    }
    return (int)$clean;
}
function kienDinhDangSo($value): string
{
    if ($value === null || $value === '') return '';
    if (!is_numeric($value) || floor((float)$value) !== (float)$value) {
        throw new InvalidArgumentException('Dữ liệu tồn kho có số lẻ; phải xử lý dữ liệu nguồn trước khi kiểm kê nguyên đơn vị.');
    }
    return number_format((float)$value, 0, '', ' ');
}
function kienTinhTon(int $heThong, int $thucTe, int $tieuHuy): array
{
    if ($heThong < 0 || $thucTe < 0 || $tieuHuy < 0 || $tieuHuy > $thucTe) {
        throw new InvalidArgumentException('Số lượng không hợp lệ: tiêu hủy không được lớn hơn thực tế.');
    }
    $conLai = $thucTe - $tieuHuy;
    return ['conLai'=>$conLai, 'chenhLech'=>$conLai - $heThong];
}
