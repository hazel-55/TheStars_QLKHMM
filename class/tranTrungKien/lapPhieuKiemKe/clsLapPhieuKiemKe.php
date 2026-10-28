<?php
require_once __DIR__ . '/tinhToan.php';

/** XL_LapPhieuKiemKe va cac Entity: PHP/MySQL, dung rieng tai may local. */
class clsLapPhieuKiemKe
{
    private mysqli $db;
    private string $storageDir;
    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->storageDir = dirname(__DIR__, 3) . '/storage/kiem-ke';
    }
    private function rows(mysqli_stmt $stmt): array
    {
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }
    public function nguoiDung(string $id): ?array
    {
        $q = $this->db->prepare('SELECT * FROM NguoiDung WHERE maTaiKhoan=? AND trangThai=\'Hoạt động\'');
        $q->bind_param('s', $id);
        return $this->rows($q)[0] ?? null;
    }
    public function kho(): array
    {
        return $this->db->query("SELECT maKho,tenKho,loaiKho,trangThai FROM Kho WHERE trangThai='Hoạt động' ORDER BY tenKho")->fetch_all(MYSQLI_ASSOC);
    }
    public function layHangTon(string $maKho, bool $lock = false): array
    {
        $sql = "SELECT tk.maBienThe, tk.maViTri, tk.soLuongTon, tk.phienBan AS phienBanTon,
                       hh.maHangHoa, hh.tenHangHoa, hh.donViTinh, hh.kieuDang,
                       dm.tenDanhMuc, bt.mauSac, bt.kichThuoc,
                       vt.day,vt.ke,vt.tang
                FROM TonKho tk
                JOIN ViTriKho vt ON vt.maViTri=tk.maViTri
                JOIN BienTheHangHoa bt ON bt.maBienThe=tk.maBienThe
                JOIN HangHoa hh ON hh.maHangHoa=bt.maHangHoa
                JOIN DanhMucHangHoa dm ON dm.maDanhMuc=hh.maDanhMuc
                WHERE vt.maKho=? AND vt.trangThai='Hoạt động' AND hh.trangThai='Hoạt động'
                ORDER BY hh.tenHangHoa,bt.maBienThe,vt.maViTri" . ($lock ? ' FOR UPDATE' : '');
        $q = $this->db->prepare($sql);
        $q->bind_param('s', $maKho);
        $arr = [];
        foreach ($this->rows($q) as $row) {
            $row['key'] = $row['maBienThe'] . '|' . $row['maViTri'];
            $arr[$row['key']] = $row;
        }
        return $arr;
    }
    public function danhSachPhieu(string $nguoi): array
    {
        $q = $this->db->prepare("SELECT p.*,k.tenKho,
                   (SELECT COUNT(*) FROM ChiTietPhieuKiemKe c WHERE c.maPhieuKiemKe=p.maPhieuKiemKe) AS soDong
                 FROM PhieuKiemKe p JOIN Kho k ON k.maKho=p.maKho
                 WHERE p.maNguoiLap=? ORDER BY p.thoiGianLap DESC,p.maPhieuKiemKe DESC");
        $q->bind_param('s', $nguoi);
        return $this->rows($q);
    }
    public function layPhieu(string $ma, string $nguoi): ?array
    {
        $q = $this->db->prepare("SELECT p.*,k.tenKho FROM PhieuKiemKe p JOIN Kho k ON k.maKho=p.maKho WHERE p.maPhieuKiemKe=? AND p.maNguoiLap=?");
        $q->bind_param('ss', $ma, $nguoi);
        $p = $this->rows($q)[0] ?? null;
        if (!$p) return null;
        $q = $this->db->prepare('SELECT * FROM ChiTietPhieuKiemKe WHERE maPhieuKiemKe=? ORDER BY maChiTiet');
        $q->bind_param('s', $ma);
        $details = [];
        foreach ($this->rows($q) as $r) {
            $r['key'] = $r['maBienThe'] . '|' . $r['maViTri'];
            $details[$r['key']] = $r;
        }
        $p['chiTiet'] = $details;
        $q = $this->db->prepare("SELECT m.maMinhChung,m.maChiTiet,m.tenMinhChung,m.loaiMinhChung
                  FROM MinhChungKiemKe m JOIN ChiTietPhieuKiemKe c ON c.maChiTiet=m.maChiTiet
                  WHERE c.maPhieuKiemKe=? ORDER BY m.maMinhChung");
        $q->bind_param('s', $ma);
        $minhChung = [];
        foreach ($this->rows($q) as $r) $minhChung[(string)$r['maChiTiet']][] = $r;
        $p['minhChung'] = $minhChung;
        $q = $this->db->prepare('SELECT maBienBan, noiDungGiaiTrinh FROM BienBanGiaiTrinh WHERE maPhieuKiemKe=?');
        $q->bind_param('s', $ma);
        $p['bienBan'] = $this->rows($q)[0] ?? null;
        return $p;
    }
    private function soLuong($v, bool $allowBlank): ?int
    {
        return kienDocSoNguyen($v, $allowBlank);
    }
    private function text($v, int $limit, string $name): string
    {
        $s = trim((string)($v ?? ''));
        if (mb_strlen($s, 'UTF-8') > $limit) {
            throw new InvalidArgumentException($name . ' vượt quá ' . $limit . ' ký tự.');
        }
        return $s;
    }
    private function taoMaPhieu(): string
    {
        // Thuc thi trong transaction; SELECT FOR UPDATE khoa bo dem theo nam.
        $year = (int)date('y');
        $q = $this->db->prepare('INSERT IGNORE INTO KiemKeSoThuTu(nam,soCuoi) VALUES (?,0)');
        $q->bind_param('i', $year); $q->execute();
        $q = $this->db->prepare('SELECT soCuoi FROM KiemKeSoThuTu WHERE nam=? FOR UPDATE');
        $q->bind_param('i', $year);
        $last = (int)$this->rows($q)[0]['soCuoi'];
        if ($last >= 9999) throw new RuntimeException('Đã hết mã phiếu trong năm.');
        $next = $last+1;
        $q = $this->db->prepare('UPDATE KiemKeSoThuTu SET soCuoi=? WHERE nam=?');
        $q->bind_param('ii', $next, $year); $q->execute();
        return 'PKK' . str_pad((string)$year,2,'0',STR_PAD_LEFT) . str_pad((string)$next,4,'0',STR_PAD_LEFT);
    }
    private function validateUploads(array $rows): array
    {
        $uploaded = [];
        if (empty($_FILES['evidence'])) return $uploaded;
        $f = $_FILES['evidence'];
        if (!isset($f['error']) || !is_array($f['error'])) return $uploaded;
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
        $fi = new finfo(FILEINFO_MIME_TYPE);
        foreach ($f['error'] as $key => $errors) {
            if (!isset($rows[$key])) continue;
            foreach ((array)$errors as $n => $err) {
                $err = (int)$err;
                if ($err === UPLOAD_ERR_NO_FILE) continue;
                if ($err !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Tải tệp thất bại, mã lỗi: '.$err);
                $size = (int)$f['size'][$key][$n];
                $tmp = $f['tmp_name'][$key][$n];
                $name = basename((string)$f['name'][$key][$n]);
                if ($size <= 0 || $size > 5*1024*1024) throw new InvalidArgumentException('Mỗi tệp minh chứng phải từ 1 byte đến 5 MB.');
                if (!is_uploaded_file($tmp)) throw new InvalidArgumentException('Tệp tải lên không hợp lệ.');
                $mime = $fi->file($tmp);
                if (!isset($allowed[$mime])) throw new InvalidArgumentException('Chỉ cho phép JPG, PNG, PDF.');
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','pdf'], true) ||
                    ($mime === 'image/jpeg' && !in_array($ext, ['jpg','jpeg'],true)) ||
                    ($mime === 'image/png' && $ext !== 'png') ||
                    ($mime === 'application/pdf' && $ext !== 'pdf')) {
                    throw new InvalidArgumentException('Định dạng tệp và phần mở rộng không khớp.');
                }
                $type = ($rows[$key]['loaiMinhChung'] ?? 'Ảnh');
                if (!in_array($type, ['Ảnh','Biên bản','Tài liệu khác'], true)) throw new InvalidArgumentException('Loại minh chứng không hợp lệ.');
                $uploaded[$key][] = compact('tmp','name','size','type','mime') + ['ext'=>$allowed[$mime]];
            }
        }
        return $uploaded;
    }
    /** Luu nhap / gui duyet; moi hanh dong deu co transaction va validation phia server. */
    public function luu(string $userId, array $form): string
    {
        $send = ($form['hanhDong'] ?? '') === 'gui';
        if (!in_array($form['hanhDong'] ?? '', ['gui','nhap'], true)) throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $ma = trim((string)($form['maPhieu'] ?? ''));
        $maKho = trim((string)($form['maKho'] ?? ''));
        $ghichu = $this->text($form['ghiChu'] ?? '', 500, 'Ghi chú');
        $giaiTrinhTongThe = $this->text($form['giaiTrinhTongThe'] ?? '', 2000, 'Biên bản giải trình tổng thể');
        if ($maKho === '') throw new InvalidArgumentException('Vui lòng chọn kho.');
        $posted = is_array($form['items'] ?? null) ? $form['items'] : [];
        $selected = [];
        foreach ($posted as $key=>$row) {
            if (is_array($row) && !empty($row['selected'])) $selected[$key] = $row;
        }
        if ($send && !$selected) throw new InvalidArgumentException('Chọn ít nhất một mặt hàng để gửi phê duyệt.');
        // Tai tep tu $_FILES: kiem tra truoc de khong luu dang do trong DB.
        $uploads = $this->validateUploads($selected);
        $newFiles = []; $oldFiles = [];
        $this->db->begin_transaction();
        try {
            $q = $this->db->prepare("SELECT maKho FROM Kho WHERE maKho=? AND trangThai='Hoạt động' FOR UPDATE");
            $q->bind_param('s', $maKho);
            if (!$this->rows($q)) throw new InvalidArgumentException('Kho không tồn tại hoặc đã ngừng hoạt động.');
            $oldDetails = [];
            if ($ma !== '') {
                $q = $this->db->prepare('SELECT maPhieuKiemKe,maKho,maNguoiLap,trangThai,phienBan FROM PhieuKiemKe WHERE maPhieuKiemKe=? FOR UPDATE');
                $q->bind_param('s',$ma); $old = $this->rows($q)[0] ?? null;
                if (!$old || $old['maNguoiLap'] !== $userId) throw new InvalidArgumentException('Không tìm thấy phiếu thuộc quyền của bạn.');
                if ($old['trangThai'] !== 'Nháp') throw new InvalidArgumentException('Phiếu đã gửi, không được sửa.');
                if ($old['maKho'] !== $maKho) throw new InvalidArgumentException('Không thể đổi kho của bản nháp hiện tại.');
                if ((int)$old['phienBan'] !== (int)($form['phienBan'] ?? 0)) throw new InvalidArgumentException('Phiếu đã được thay đổi ở phiên khác. Hãy tải lại trang.');
                $q=$this->db->prepare('SELECT * FROM ChiTietPhieuKiemKe WHERE maPhieuKiemKe=? FOR UPDATE');
                $q->bind_param('s',$ma);
                foreach ($this->rows($q) as $r) $oldDetails[$r['maBienThe'].'|'.$r['maViTri']]=$r;
            }
            $inventory = $this->layHangTon($maKho, true);
            $checked = [];
            $canGiaiTrinh = false;
            foreach ($selected as $key=>$r) {
                if (!isset($inventory[$key])) throw new InvalidArgumentException('Hàng / vị trí không thuộc kho hoặc không còn hoạt động: '.$key);
                $item = $inventory[$key];
                $oldR = $oldDetails[$key] ?? null;
                $ver = (int)($oldR['phienBanTonLucChot'] ?? ($r['phienBanTonLucChot'] ?? 0));
                if ($ver < 1) throw new InvalidArgumentException('Thiếu phiên bản tồn kho, vui lòng tải lại trang.');
                if (!$oldR && $ver !== (int)$item['phienBanTon']) throw new InvalidArgumentException('Tồn kho đã thay đổi, hãy tải lại trang và kiểm kê lại: '.$key);
                if ($send && $ver !== (int)$item['phienBanTon']) throw new InvalidArgumentException('Tồn kho thay đổi sau khi chốt; không thể gửi phiếu: '.$key);
                $actual = $this->soLuong($r['soLuongThucTe'] ?? null, !$send);
                $destroyed = $this->soLuong($r['soLuongHangLoiTieuHuy'] ?? '0', false);
                $destroyed = $destroyed ?? 0;
                if (($actual === null && $destroyed > 0) || ($actual !== null && $destroyed > $actual)) throw new InvalidArgumentException('Số hàng tiêu hủy không được lớn hơn số thực tế: '.$key);
                $condition = $this->text($r['tinhTrangHangHoa'] ?? '',255,'Tình trạng hàng hóa');
                $why = $this->text($r['lyDoChenhLech'] ?? '',500,'Lý do chênh lệch');
                $whyDestroy = $this->text($r['lyDoTieuHuy'] ?? '',500,'Lý do tiêu hủy');
                $howDestroy = $this->text($r['hinhThucTieuHuy'] ?? '',255,'Hình thức tiêu hủy');
                $explain = $this->text($r['noiDungGiaiTrinh'] ?? '',2000,'Giải trình');
                $snapshot = kienDocSoNguyen($oldR ? (string)(int)$oldR['soLuongTonHeThong'] : (string)(int)$item['soLuongTon']);
                // Nếu có tồn kho dạng số lẻ từ dữ liệu cũ: dừng, không âm thầm làm tròn.
                $sourceStock = $oldR ? $oldR['soLuongTonHeThong'] : $item['soLuongTon'];
                if ((float)$sourceStock !== (float)$snapshot) throw new InvalidArgumentException('Tồn kho có số thập phân; không thể lập phiếu số nguyên cho SKU '.$key);
                $total = ($actual === null) ? null : kienTinhTon($snapshot, $actual, $destroyed);
                if ($total !== null && ($total['chenhLech'] !== 0 || $destroyed > 0)) $canGiaiTrinh = true;
                if ($send) {
                    if ($condition === '') throw new InvalidArgumentException('Nhập tình trạng hàng hóa: '.$key);
                    if ($total['chenhLech'] != 0 && $why === '') throw new InvalidArgumentException('Phải nhập lý do chênh lệch: '.$key);
                    if ($destroyed > 0 && ($whyDestroy === '' || $howDestroy === '')) throw new InvalidArgumentException('Phải nhập lý do và hình thức tiêu hủy: '.$key);
                    if (($total['chenhLech'] != 0 || $destroyed > 0) && $explain === '') throw new InvalidArgumentException('Phải nhập nội dung giải trình: '.$key);
                }
                $checked[$key] = [
                    'maBienThe'=>$item['maBienThe'],'maViTri'=>$item['maViTri'],
                    'snapshot'=>$snapshot,'version'=>$ver,
                    'actual'=>$actual===null ? null : (string)$actual,
                    'condition'=>$condition ?: null,
                    'destroyed'=>(string)$destroyed,
                    'remaining'=>$total===null ? null : (string)$total['conLai'],
                    'difference'=>$total===null ? null : (string)$total['chenhLech'],
                    'why'=>$why ?: null,'whyDestroy'=>$whyDestroy ?: null,
                    'howDestroy'=>$howDestroy ?: null,'explain'=>$explain ?: null,
                    'oldId'=>$oldR['maChiTiet'] ?? null
                ];
            }
            if ($send && $canGiaiTrinh && $giaiTrinhTongThe === '') throw new InvalidArgumentException('Cần nhập biên bản giải trình tổng thể khi phiếu có thừa, thiếu hoặc tiêu hủy.');
            if ($ma === '') {
                $ma=$this->taoMaPhieu();
                $q=$this->db->prepare("INSERT INTO PhieuKiemKe(maPhieuKiemKe,maKho,maNguoiLap,ghiChu,trangThai) VALUES(?,?,?,?,'Nháp')");
                $q->bind_param('ssss',$ma,$maKho,$userId,$ghichu); $q->execute();
            }
            foreach ($checked as $key=>$r) {
                $a=$r['actual'];$c=$r['condition'];$d=$r['destroyed'];
                $why=$r['why'];$whyDes=$r['whyDestroy'];$howDes=$r['howDestroy'];$explain=$r['explain'];
                if ($r['oldId'] !== null) {
                    $id=(string)$r['oldId'];
                    $q=$this->db->prepare('UPDATE ChiTietPhieuKiemKe SET soLuongThucTe=?,tinhTrangHangHoa=?,soLuongHangLoiTieuHuy=?,lyDoChenhLech=?,lyDoTieuHuy=?,hinhThucTieuHuy=?,noiDungGiaiTrinh=? WHERE maChiTiet=?');
                    $q->bind_param('ssssssss',$a,$c,$d,$why,$whyDes,$howDes,$explain,$id);$q->execute();
                } else {
                    $bt=$r['maBienThe'];$vt=$r['maViTri'];$stock=(string)$r['snapshot'];$version=(string)$r['version'];
                    $q=$this->db->prepare('INSERT INTO ChiTietPhieuKiemKe(maPhieuKiemKe,maBienThe,maViTri,soLuongTonHeThong,thoiDiemChotTon,phienBanTonLucChot,soLuongThucTe,tinhTrangHangHoa,soLuongHangLoiTieuHuy,lyDoChenhLech,lyDoTieuHuy,hinhThucTieuHuy,noiDungGiaiTrinh) VALUES(?,?,?,?,NOW(),?,?,?,?,?,?,?,?)');
                    $q->bind_param('ssssssssssss',$ma,$bt,$vt,$stock,$version,$a,$c,$d,$why,$whyDes,$howDes,$explain);$q->execute();
                    $id=(string)$this->db->insert_id;
                }
                foreach ($uploads[$key] ?? [] as $file) {
                    if (!is_dir($this->storageDir)) mkdir($this->storageDir,0700,true);
                    $filename=bin2hex(random_bytes(18)).'.'.$file['ext'];
                    $full=$this->storageDir.'/'.$filename;
                    if (!move_uploaded_file($file['tmp'],$full)) throw new RuntimeException('Không lưu được tệp minh chứng.');
                    $newFiles[]=$full;
                    $name=$file['name'];$type=$file['type'];$size=(string)$file['size'];
                    $q=$this->db->prepare('INSERT INTO MinhChungKiemKe(maChiTiet,tenMinhChung,loaiMinhChung,duongDanTep,kichThuocTep,maNguoiDinhKem) VALUES(?,?,?,?,?,?)');
                    $q->bind_param('ssssss',$id,$name,$type,$filename,$size,$userId);$q->execute();
                }
            }
            foreach ($oldDetails as $key=>$r) {
                if (isset($checked[$key])) continue;
                $id=(string)$r['maChiTiet'];
                $q=$this->db->prepare('SELECT duongDanTep FROM MinhChungKiemKe WHERE maChiTiet=?');
                $q->bind_param('s',$id);
                foreach ($this->rows($q) as $file) $oldFiles[]=basename($file['duongDanTep']);
                $q=$this->db->prepare('DELETE FROM ChiTietPhieuKiemKe WHERE maChiTiet=?');
                $q->bind_param('s',$id);$q->execute();
            }
            // Biên bản giải trình được lưu cùng transaction với phiếu.
            if ($giaiTrinhTongThe !== '') {
                $maBienBan = 'BBGT' . substr($ma, 3);
                $q=$this->db->prepare('INSERT INTO BienBanGiaiTrinh(maBienBan,maPhieuKiemKe,maNguoiLap,noiDungGiaiTrinh) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE noiDungGiaiTrinh=VALUES(noiDungGiaiTrinh)');
                $q->bind_param('ssss',$maBienBan,$ma,$userId,$giaiTrinhTongThe);$q->execute();
            } else {
                $q=$this->db->prepare('DELETE FROM BienBanGiaiTrinh WHERE maPhieuKiemKe=?');
                $q->bind_param('s',$ma);$q->execute();
            }
            if ($send) {
                $q=$this->db->prepare("SELECT maTaiKhoan FROM NguoiDung WHERE vaiTro IN ('BGD','BGĐ','Ban giám đốc') AND trangThai='Hoạt động'");
                $directors=$this->rows($q);
                if (!$directors) throw new InvalidArgumentException('Chưa có BGĐ hoạt động để nhận thông báo.');
                $q=$this->db->prepare("UPDATE PhieuKiemKe SET ghiChu=?,trangThai='Chờ phê duyệt',maNguoiGui=?,thoiGianGui=NOW(),phienBan=phienBan+1 WHERE maPhieuKiemKe=?");
                $q->bind_param('sss',$ghichu,$userId,$ma);$q->execute();
                foreach ($directors as $director) {
                    $recipient=$director['maTaiKhoan'];$message='Phiếu kiểm kê '.$ma.' đang chờ phê duyệt.';
                    $q=$this->db->prepare('INSERT INTO ThongBaoKiemKe(maPhieuKiemKe,maNguoiNhan,noiDung) VALUES(?,?,?)');
                    $q->bind_param('sss',$ma,$recipient,$message);$q->execute();
                }
            } else {
                $q=$this->db->prepare('UPDATE PhieuKiemKe SET ghiChu=?,phienBan=phienBan+1 WHERE maPhieuKiemKe=?');
                $q->bind_param('ss',$ghichu,$ma);$q->execute();
            }
            $this->db->commit();
            foreach ($oldFiles as $name) @unlink($this->storageDir.'/'.$name);
            return $ma;
        } catch (Throwable $e) {
            $this->db->rollback();
            foreach ($newFiles as $name) @unlink($name);
            throw $e;
        }
    }
    public function xoaBanNhap(string $ma,string $owner,int $version): void
    {
        $this->db->begin_transaction();
        try {
            $q=$this->db->prepare('SELECT trangThai,phienBan,maNguoiLap FROM PhieuKiemKe WHERE maPhieuKiemKe=? FOR UPDATE');
            $q->bind_param('s',$ma); $p=$this->rows($q)[0]??null;
            if (!$p || $p['maNguoiLap']!==$owner || $p['trangThai']!=='Nháp' || (int)$p['phienBan']!==$version) {
                throw new InvalidArgumentException('Chỉ được xóa bản nháp của mình và đúng phiên bản.');
            }
            $q=$this->db->prepare('SELECT m.duongDanTep FROM MinhChungKiemKe m JOIN ChiTietPhieuKiemKe c ON c.maChiTiet=m.maChiTiet WHERE c.maPhieuKiemKe=?');
            $q->bind_param('s',$ma);$files=$this->rows($q);
            $q=$this->db->prepare('DELETE FROM PhieuKiemKe WHERE maPhieuKiemKe=?');
            $q->bind_param('s',$ma);$q->execute();
            $this->db->commit();
            foreach ($files as $f) @unlink($this->storageDir.'/'.basename($f['duongDanTep']));
        } catch (Throwable $e) {$this->db->rollback();throw $e;}
    }
    public function tepMinhChung(int $id,string $owner): ?array
    {
        $q=$this->db->prepare('SELECT m.* FROM MinhChungKiemKe m JOIN ChiTietPhieuKiemKe c ON c.maChiTiet=m.maChiTiet JOIN PhieuKiemKe p ON p.maPhieuKiemKe=c.maPhieuKiemKe WHERE m.maMinhChung=? AND p.maNguoiLap=?');
        $q->bind_param('is',$id,$owner); return $this->rows($q)[0]??null;
    }
    public function filePath(string $filename): string {return $this->storageDir.'/'.basename($filename);}
}
