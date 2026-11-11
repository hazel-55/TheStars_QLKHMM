<?php
/** Số lượng được tính bằng số nguyên phần nghìn để tránh sai số float. PHP 64-bit. */
function kienDocSoLuong($value, bool $allowBlank = false): ?int
{
    if ($value === null || trim((string)$value) === '') {
        if ($allowBlank) return null;
        throw new InvalidArgumentException('Chưa nhập số lượng của hàng đã chọn.');
    }
    if (!is_scalar($value)) throw new InvalidArgumentException('Số lượng không hợp lệ.');
    $raw = trim((string)$value);
    if (!preg_match('/^([0-9]{1,15}|[0-9]{1,3}(?: [0-9]{3}){1,4})(?:\.([0-9]{1,3}))?$/D', $raw, $m)) {
        throw new InvalidArgumentException('Nhập số không âm, tối đa 3 chữ số thập phân; dùng dấu chấm, ví dụ 1250.125.');
    }
    if (PHP_INT_SIZE < 8) throw new RuntimeException('Cần PHP 64-bit.');
    return (int)str_replace(' ', '', $m[1]) * 1000 + (int)str_pad($m[2] ?? '', 3, '0');
}
function kienSoLuongSql(int $milli): string
{
    $sign = $milli < 0 ? '-' : '';
    $n = abs($milli);
    return $sign . intdiv($n, 1000) . '.' . str_pad((string)($n % 1000), 3, '0', STR_PAD_LEFT);
}
function kienDinhDangSo($value): string
{
    if ($value === null || $value === '') return '';
    $raw = kienSoLuongSql(kienDocSoLuong($value));
    list($whole, $fraction) = explode('.', $raw);
    $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $whole);
    $fraction = rtrim($fraction, '0');
    return $whole . ($fraction !== '' ? '.' . $fraction : '');
}
function kienTinhTon(int $heThong, int $thucTe, int $tieuHuy): array
{
    if ($heThong < 0 || $thucTe < 0 || $tieuHuy < 0 || $tieuHuy > $thucTe) {
        throw new InvalidArgumentException('Số lượng không hợp lệ: tiêu hủy không được lớn hơn thực tế.');
    }
    $conLai = $thucTe - $tieuHuy;
    return ['conLai'=>$conLai, 'chenhLech'=>$conLai-$heThong];
}
