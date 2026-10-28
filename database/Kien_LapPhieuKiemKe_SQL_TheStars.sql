--
--
CREATE DATABASE IF NOT EXISTS `thestars_qlkhmm_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `thestars_qlkhmm_db`;

-- ========== PHAN A. CAU TRUC BANG ==========
CREATE TABLE NguoiDung (
  maTaiKhoan VARCHAR(20) PRIMARY KEY,
  hoTen VARCHAR(100) NOT NULL,
  vaiTro VARCHAR(50) NOT NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động'
) ENGINE=InnoDB;
CREATE TABLE Kho (
  maKho VARCHAR(20) PRIMARY KEY,
  tenKho VARCHAR(150) NOT NULL,
  loaiKho VARCHAR(50) NOT NULL,
  sucChua DECIMAL(18,0) NOT NULL DEFAULT 0 CHECK (sucChua >= 0),
  diaChi VARCHAR(255) NULL,
  moTa VARCHAR(500) NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động'
) ENGINE=InnoDB;
CREATE TABLE ViTriKho (
  maViTri VARCHAR(30) PRIMARY KEY,
  maKho VARCHAR(20) NOT NULL,
  day VARCHAR(20) NOT NULL,
  ke VARCHAR(20) NOT NULL,
  tang INT NOT NULL DEFAULT 0 CHECK (tang >= 0),
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động',
  ghiChu VARCHAR(255) NULL,
  FOREIGN KEY (maKho) REFERENCES Kho(maKho)
) ENGINE=InnoDB;
CREATE TABLE DanhMucHangHoa (
  maDanhMuc VARCHAR(20) PRIMARY KEY,
  tenDanhMuc VARCHAR(100) NOT NULL,
  moTa VARCHAR(500) NULL
) ENGINE=InnoDB;
CREATE TABLE HangHoa (
  maHangHoa VARCHAR(20) PRIMARY KEY,
  tenHangHoa VARCHAR(150) NOT NULL,
  maDanhMuc VARCHAR(20) NOT NULL,
  donViTinh VARCHAR(30) NOT NULL,
  kieuDang VARCHAR(100) NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Hoạt động',
  FOREIGN KEY (maDanhMuc) REFERENCES DanhMucHangHoa(maDanhMuc)
) ENGINE=InnoDB;
CREATE TABLE BienTheHangHoa (
  maBienThe VARCHAR(20) PRIMARY KEY,
  maHangHoa VARCHAR(20) NOT NULL,
  mauSac VARCHAR(50) NULL,
  kichThuoc VARCHAR(50) NULL,
  FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa)
) ENGINE=InnoDB;
CREATE TABLE TonKho (
  maBienThe VARCHAR(20) NOT NULL,
  maViTri VARCHAR(30) NOT NULL,
  soLuongTon DECIMAL(18,0) NOT NULL DEFAULT 0 CHECK (soLuongTon >= 0),
  ngayCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  phienBan INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (maBienThe, maViTri),
  FOREIGN KEY (maBienThe) REFERENCES BienTheHangHoa(maBienThe),
  FOREIGN KEY (maViTri) REFERENCES ViTriKho(maViTri)
) ENGINE=InnoDB;
CREATE TABLE KiemKeSoThuTu (
  nam SMALLINT PRIMARY KEY,
  soCuoi INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
CREATE TABLE PhieuKiemKe (
  maPhieuKiemKe VARCHAR(30) PRIMARY KEY,
  maKho VARCHAR(20) NOT NULL,
  maNguoiLap VARCHAR(20) NOT NULL,
  thoiGianLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  maNguoiGui VARCHAR(20) NULL,
  thoiGianGui DATETIME NULL,
  trangThai VARCHAR(50) NOT NULL DEFAULT 'Nháp',
  ghiChu VARCHAR(500) NULL,
  phienBan INT UNSIGNED NOT NULL DEFAULT 1,
  FOREIGN KEY (maKho) REFERENCES Kho(maKho),
  FOREIGN KEY (maNguoiLap) REFERENCES NguoiDung(maTaiKhoan),
  FOREIGN KEY (maNguoiGui) REFERENCES NguoiDung(maTaiKhoan)
) ENGINE=InnoDB;
CREATE TABLE ChiTietPhieuKiemKe (
  maChiTiet BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maPhieuKiemKe VARCHAR(30) NOT NULL,
  maBienThe VARCHAR(20) NOT NULL,
  maViTri VARCHAR(30) NOT NULL,
  soLuongTonHeThong DECIMAL(18,0) NOT NULL CHECK (soLuongTonHeThong >= 0),
  thoiDiemChotTon DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  phienBanTonLucChot INT UNSIGNED NOT NULL,
  soLuongThucTe DECIMAL(18,0) NULL CHECK (soLuongThucTe IS NULL OR soLuongThucTe >= 0),
  tinhTrangHangHoa VARCHAR(255) NULL,
  soLuongHangLoiTieuHuy DECIMAL(18,0) NOT NULL DEFAULT 0,
  soLuongThucTeConLai DECIMAL(18,0) GENERATED ALWAYS AS (soLuongThucTe - soLuongHangLoiTieuHuy) STORED,
  soLuongChenhLech DECIMAL(18,0) GENERATED ALWAYS AS (soLuongThucTe - soLuongHangLoiTieuHuy - soLuongTonHeThong) STORED,
  lyDoChenhLech VARCHAR(500) NULL,
  lyDoTieuHuy VARCHAR(500) NULL,
  hinhThucTieuHuy VARCHAR(255) NULL,
  noiDungGiaiTrinh TEXT NULL,
  CONSTRAINT chk_ctkk_sl_tieu_huy CHECK (soLuongHangLoiTieuHuy >= 0 AND (soLuongThucTe IS NULL OR soLuongHangLoiTieuHuy <= soLuongThucTe)),
  UNIQUE KEY uq_chitiet_vitri (maPhieuKiemKe, maBienThe, maViTri),
  FOREIGN KEY (maPhieuKiemKe) REFERENCES PhieuKiemKe(maPhieuKiemKe) ON DELETE CASCADE,
  FOREIGN KEY (maBienThe) REFERENCES BienTheHangHoa(maBienThe),
  FOREIGN KEY (maViTri) REFERENCES ViTriKho(maViTri)
) ENGINE=InnoDB;
CREATE TABLE MinhChungKiemKe (
  maMinhChung BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maChiTiet BIGINT UNSIGNED NOT NULL,
  tenMinhChung VARCHAR(255) NOT NULL,
  loaiMinhChung VARCHAR(50) NOT NULL,
  duongDanTep VARCHAR(500) NOT NULL,
  kichThuocTep BIGINT NOT NULL CHECK (kichThuocTep > 0),
  maNguoiDinhKem VARCHAR(20) NOT NULL,
  thoiGianDinhKem DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (maChiTiet) REFERENCES ChiTietPhieuKiemKe(maChiTiet) ON DELETE CASCADE,
  FOREIGN KEY (maNguoiDinhKem) REFERENCES NguoiDung(maTaiKhoan)
) ENGINE=InnoDB;
CREATE TABLE ThongBaoKiemKe (
  maThongBao BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maPhieuKiemKe VARCHAR(30) NOT NULL,
  maNguoiNhan VARCHAR(20) NOT NULL,
  noiDung VARCHAR(500) NOT NULL,
  thoiGianTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  daDoc TINYINT(1) NOT NULL DEFAULT 0,
  thoiGianDoc DATETIME NULL,
  FOREIGN KEY (maPhieuKiemKe) REFERENCES PhieuKiemKe(maPhieuKiemKe) ON DELETE CASCADE,
  FOREIGN KEY (maNguoiNhan) REFERENCES NguoiDung(maTaiKhoan)
) ENGINE=InnoDB;
CREATE TABLE BienBanGiaiTrinh (
  maBienBan VARCHAR(30) PRIMARY KEY,
  maPhieuKiemKe VARCHAR(30) NOT NULL UNIQUE,
  ngayLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  maNguoiLap VARCHAR(20) NOT NULL,
  noiDungGiaiTrinh TEXT NOT NULL,
  tepDinhKem LONGTEXT NULL,
  FOREIGN KEY (maPhieuKiemKe) REFERENCES PhieuKiemKe(maPhieuKiemKe) ON DELETE CASCADE,
  FOREIGN KEY (maNguoiLap) REFERENCES NguoiDung(maTaiKhoan)
) ENGINE=InnoDB;


-- ========== PHAN B. TAI KHOAN & DU LIEU MAU CO BAN ==========
-- Tai khoan BKK/BGD phuc vu luong demo localhost; KHONG phai tai khoan dang nhap that.
START TRANSACTION;
INSERT INTO NguoiDung (maTaiKhoan, hoTen, vaiTro, trangThai) VALUES
 ('BKK001','Trần Trung Kiên','BKK','Hoạt động'),
 ('BKK002','Nhân viên Ban kiểm kê 2','BKK','Hoạt động'),
 ('BGD001','Giám đốc kho','BGD','Hoạt động'),
 ('BGD002','Phó giám đốc kho','BGD','Hoạt động');

-- Co 11 kho dang hoat dong: 5 NL, 5 TP, 1 kho loi.
INSERT INTO Kho (maKho,tenKho,loaiKho,sucChua,diaChi,trangThai) VALUES
 ('K001','Kho thành phẩm - Xưởng 1','Thành phẩm',80000,'Khu thành phẩm, xưởng 1','Hoạt động'),
 ('K002','Kho nguyên liệu - Xưởng 1','Nguyên liệu',80000,'Khu nguyên liệu, xưởng 1','Hoạt động');

INSERT INTO ViTriKho (maViTri,maKho,day,ke,tang,trangThai) VALUES
 ('VT001','K001','A','K01',1,'Hoạt động'),
 ('VT002','K001','A','K02',2,'Hoạt động'),
 ('VT003','K002','B','K01',1,'Hoạt động');

INSERT INTO DanhMucHangHoa (maDanhMuc,tenDanhMuc,moTa) VALUES
 ('DM001','Áo','Thành phẩm áo'),
 ('DM002','Quần','Thành phẩm quần'),
 ('DM003','Vải','Nguyên liệu vải');

INSERT INTO HangHoa (maHangHoa,tenHangHoa,maDanhMuc,donViTinh,kieuDang,trangThai) VALUES
 ('HH001','Áo sơ mi công sở','DM001','Cái','Slim fit','Hoạt động'),
 ('HH002','Quần tây nam','DM002','Cái','Ống đứng','Hoạt động'),
 ('HH003','Vải cotton','DM003','Mét','Cuộn','Hoạt động');

INSERT INTO BienTheHangHoa (maBienThe,maHangHoa,mauSac,kichThuoc) VALUES
 ('BT001','HH001','Trắng','M'),
 ('BT002','HH001','Xanh','L'),
 ('BT003','HH002','Đen','L'),
 ('BT004','HH003','Trắng','Khổ 1m5');

INSERT INTO TonKho (maBienThe,maViTri,soLuongTon) VALUES
 ('BT001','VT001',100),
 ('BT002','VT001',75),
 ('BT003','VT002',45),
 ('BT004','VT003',300);

-- ========== PHAN C. 9 KHO CON LAI, HANG HOA / SKU / TON KHO ==========
-- K003-K006: kho nguyen lieu xuong 2-5
-- K007-K010: kho thanh pham xuong 2-5
-- K011: kho hang loi tap trung
INSERT INTO Kho(maKho,tenKho,loaiKho,sucChua,diaChi,trangThai) VALUES
 ('K003','Kho nguyên liệu - Xưởng 2','Nguyên liệu',80000,'Khu nguyên liệu, xưởng 2','Hoạt động'),
 ('K004','Kho nguyên liệu - Xưởng 3','Nguyên liệu',80000,'Khu nguyên liệu, xưởng 3','Hoạt động'),
 ('K005','Kho nguyên liệu - Xưởng 4','Nguyên liệu',80000,'Khu nguyên liệu, xưởng 4','Hoạt động'),
 ('K006','Kho nguyên liệu - Xưởng 5','Nguyên liệu',80000,'Khu nguyên liệu, xưởng 5','Hoạt động'),
 ('K007','Kho thành phẩm - Xưởng 2','Thành phẩm',80000,'Khu thành phẩm, xưởng 2','Hoạt động'),
 ('K008','Kho thành phẩm - Xưởng 3','Thành phẩm',80000,'Khu thành phẩm, xưởng 3','Hoạt động'),
 ('K009','Kho thành phẩm - Xưởng 4','Thành phẩm',80000,'Khu thành phẩm, xưởng 4','Hoạt động'),
 ('K010','Kho thành phẩm - Xưởng 5','Thành phẩm',80000,'Khu thành phẩm, xưởng 5','Hoạt động'),
 ('K011','Kho hàng lỗi tập trung','Hàng lỗi',80000,'Khu phân loại hàng lỗi','Hoạt động');

INSERT INTO ViTriKho(maViTri,maKho,day,ke,tang,trangThai) VALUES
 ('KVT002','K002','B','K02',2,'Hoạt động'),
 ('KVT0031','K003','A','K01',1,'Hoạt động'),
 ('KVT0032','K003','B','K02',2,'Hoạt động'),
 ('KVT0041','K004','A','K01',1,'Hoạt động'),
 ('KVT0042','K004','B','K02',2,'Hoạt động'),
 ('KVT0051','K005','A','K01',1,'Hoạt động'),
 ('KVT0052','K005','B','K02',2,'Hoạt động'),
 ('KVT0061','K006','A','K01',1,'Hoạt động'),
 ('KVT0062','K006','B','K02',2,'Hoạt động'),
 ('KVT0071','K007','A','K01',1,'Hoạt động'),
 ('KVT0072','K007','B','K02',2,'Hoạt động'),
 ('KVT0081','K008','A','K01',1,'Hoạt động'),
 ('KVT0082','K008','B','K02',2,'Hoạt động'),
 ('KVT0091','K009','A','K01',1,'Hoạt động'),
 ('KVT0092','K009','B','K02',2,'Hoạt động'),
 ('KVT0101','K010','A','K01',1,'Hoạt động'),
 ('KVT0102','K010','B','K02',2,'Hoạt động'),
 ('KVT0111','K011','A','K01',1,'Hoạt động'),
 ('KVT0112','K011','B','K02',2,'Hoạt động');

INSERT INTO DanhMucHangHoa(maDanhMuc,tenDanhMuc,moTa) VALUES
 ('DM004','Chỉ may','Chỉ may công nghiệp'),
 ('DM005','Nút cúc','Nút và cúc áo'),
 ('DM006','Khóa kéo','Khóa kéo các loại'),
 ('DM007','Vải lót','Vải lót may mặc'),
 ('DM008','Dây thun','Phụ liệu co giãn'),
 ('DM009','Áo khoác','Thành phẩm áo khoác'),
 ('DM010','Váy','Thành phẩm váy'),
 ('DM011','Phụ kiện','Phụ kiện đóng gói'),
 ('DM012','Tem nhãn','Nhãn, mác sản phẩm'),
 ('DM013','Vải denim','Vải jeans / denim');

INSERT INTO HangHoa(maHangHoa,tenHangHoa,maDanhMuc,donViTinh,kieuDang,trangThai) VALUES
 ('KHH001','Vải kaki cotton','DM003','Mét','Vải thô','Hoạt động'),
 ('KHH002','Vải polyester chống nhăn','DM003','Mét','Vải khổ lớn','Hoạt động'),
 ('KHH003','Vải thun cotton 2 chiều','DM003','Mét','Vải thun','Hoạt động'),
 ('KHH004','Vải jean denim','DM013','Mét','Vải denim','Hoạt động'),
 ('KHH005','Chỉ may polyester 40/2','DM004','Cuộn','Chỉ ống','Hoạt động'),
 ('KHH006','Chỉ may cotton 30/3','DM004','Cuộn','Chỉ ống','Hoạt động'),
 ('KHH007','Cúc áo nhựa 4 lỗ','DM005','Cái','Cúc 4 lỗ','Hoạt động'),
 ('KHH008','Cúc áo kim loại','DM005','Cái','Cúc dập','Hoạt động'),
 ('KHH009','Khóa kéo nylon 20 cm','DM006','Cái','Khóa 20 cm','Hoạt động'),
 ('KHH010','Khóa kéo kim loại 15 cm','DM006','Cái','Khóa 15 cm','Hoạt động'),
 ('KHH011','Vải lót poly taffeta','DM007','Mét','Vải lót','Hoạt động'),
 ('KHH012','Dây thun dệt bản 2 cm','DM008','Mét','Thun dệt','Hoạt động'),
 ('KHH013','Nhãn cổ áo dệt','DM012','Cái','Nhãn dệt','Hoạt động'),
 ('KHH014','Túi bao bì PE','DM011','Cái','Bao bì','Hoạt động'),
 ('KHH015','Áo thun cổ tròn','DM001','Cái','Regular fit','Hoạt động'),
 ('KHH016','Áo sơ mi nữ','DM001','Cái','Dáng suông','Hoạt động'),
 ('KHH017','Áo polo đồng phục','DM001','Cái','Polo','Hoạt động'),
 ('KHH018','Quần kaki công sở','DM002','Cái','Ống đứng','Hoạt động'),
 ('KHH019','Quần jean nam','DM002','Cái','Slim fit','Hoạt động'),
 ('KHH020','Quần jean nữ','DM002','Cái','Ống rộng','Hoạt động'),
 ('KHH021','Áo khoác gió 2 lớp','DM009','Cái','Có mũ','Hoạt động'),
 ('KHH022','Áo khoác denim','DM009','Cái','Dáng ngắn','Hoạt động'),
 ('KHH023','Váy chữ A công sở','DM010','Cái','Chữ A','Hoạt động'),
 ('KHH024','Chân váy xếp ly','DM010','Cái','Xếp ly','Hoạt động'),
 ('KHH025','Áo sơ mi nam tay ngắn','DM001','Cái','Slim fit','Hoạt động'),
 ('KHH026','Quần short kaki','DM002','Cái','Short','Hoạt động'),
 ('KHH027','Vải kate may đồng phục','DM003','Mét','Vải kate','Hoạt động'),
 ('KHH028','Tem mác hướng dẫn giặt','DM012','Cái','Nhãn giặt','Hoạt động'),
 ('KHH029','Áo bảo hộ lao động','DM001','Cái','Dáng rộng','Hoạt động'),
 ('KHH030','Quần bảo hộ lao động','DM002','Cái','Dáng rộng','Hoạt động');

INSERT INTO BienTheHangHoa(maBienThe,maHangHoa,mauSac,kichThuoc) VALUES
 ('KBT0011','KHH001','Đen','Tiêu chuẩn'),
 ('KBT0012','KHH001','Trắng','Loại B'),
 ('KBT0021','KHH002','Đen','Tiêu chuẩn'),
 ('KBT0022','KHH002','Trắng','Loại B'),
 ('KBT0031','KHH003','Đen','Tiêu chuẩn'),
 ('KBT0032','KHH003','Trắng','Loại B'),
 ('KBT0041','KHH004','Đen','Tiêu chuẩn'),
 ('KBT0042','KHH004','Trắng','Loại B'),
 ('KBT0051','KHH005','Đen','Tiêu chuẩn'),
 ('KBT0052','KHH005','Trắng','Loại B'),
 ('KBT0061','KHH006','Đen','Tiêu chuẩn'),
 ('KBT0062','KHH006','Trắng','Loại B'),
 ('KBT0071','KHH007','Đen','Tiêu chuẩn'),
 ('KBT0072','KHH007','Trắng','Loại B'),
 ('KBT0081','KHH008','Đen','Tiêu chuẩn'),
 ('KBT0082','KHH008','Trắng','Loại B'),
 ('KBT0091','KHH009','Đen','Tiêu chuẩn'),
 ('KBT0092','KHH009','Trắng','Loại B'),
 ('KBT0101','KHH010','Đen','Tiêu chuẩn'),
 ('KBT0102','KHH010','Trắng','Loại B'),
 ('KBT0111','KHH011','Đen','Tiêu chuẩn'),
 ('KBT0112','KHH011','Trắng','Loại B'),
 ('KBT0121','KHH012','Đen','Tiêu chuẩn'),
 ('KBT0122','KHH012','Trắng','Loại B'),
 ('KBT0131','KHH013','Đen','Tiêu chuẩn'),
 ('KBT0132','KHH013','Trắng','Loại B'),
 ('KBT0141','KHH014','Đen','Tiêu chuẩn'),
 ('KBT0142','KHH014','Trắng','Loại B'),
 ('KBT0151','KHH015','Trắng','M'),
 ('KBT0152','KHH015','Xanh navy','L'),
 ('KBT0161','KHH016','Trắng','M'),
 ('KBT0162','KHH016','Xanh navy','L'),
 ('KBT0171','KHH017','Trắng','M'),
 ('KBT0172','KHH017','Xanh navy','L'),
 ('KBT0181','KHH018','Trắng','M'),
 ('KBT0182','KHH018','Xanh navy','L'),
 ('KBT0191','KHH019','Trắng','M'),
 ('KBT0192','KHH019','Xanh navy','L'),
 ('KBT0201','KHH020','Trắng','M'),
 ('KBT0202','KHH020','Xanh navy','L'),
 ('KBT0211','KHH021','Trắng','M'),
 ('KBT0212','KHH021','Xanh navy','L'),
 ('KBT0221','KHH022','Trắng','M'),
 ('KBT0222','KHH022','Xanh navy','L'),
 ('KBT0231','KHH023','Trắng','M'),
 ('KBT0232','KHH023','Xanh navy','L'),
 ('KBT0241','KHH024','Trắng','M'),
 ('KBT0242','KHH024','Xanh navy','L'),
 ('KBT0251','KHH025','Trắng','M'),
 ('KBT0252','KHH025','Xanh navy','L'),
 ('KBT0261','KHH026','Trắng','M'),
 ('KBT0262','KHH026','Xanh navy','L'),
 ('KBT0271','KHH027','Đen','Tiêu chuẩn'),
 ('KBT0272','KHH027','Trắng','Loại B'),
 ('KBT0281','KHH028','Đen','Tiêu chuẩn'),
 ('KBT0282','KHH028','Trắng','Loại B'),
 ('KBT0291','KHH029','Trắng','M'),
 ('KBT0292','KHH029','Xanh navy','L'),
 ('KBT0301','KHH030','Trắng','M'),
 ('KBT0302','KHH030','Xanh navy','L');

INSERT INTO TonKho(maBienThe,maViTri,soLuongTon) VALUES
 ('KBT0151','VT001',5000),
 ('KBT0162','VT002',3200),
 ('KBT0181','VT001',5000),
 ('KBT0192','VT002',75),
 ('KBT0211','VT001',5000),
 ('KBT0222','VT002',300),
 ('KBT0241','VT001',75),
 ('KBT0042','VT003',2400),
 ('KBT0061','KVT002',3200),
 ('KBT0072','KVT002',200),
 ('KBT0091','VT003',450),
 ('KBT0102','KVT002',75),
 ('KBT0121','KVT002',2400),
 ('KBT0132','VT003',300),
 ('KBT0081','KVT0031',5000),
 ('KBT0092','KVT0032',300),
 ('KBT0111','KVT0031',5000),
 ('KBT0122','KVT0032',75),
 ('KBT0141','KVT0031',5000),
 ('KBT0272','KVT0032',75),
 ('KBT0011','KVT0031',300),
 ('KBT0112','KVT0041',2400),
 ('KBT0131','KVT0042',1250),
 ('KBT0142','KVT0041',600),
 ('KBT0281','KVT0042',300),
 ('KBT0012','KVT0041',300),
 ('KBT0031','KVT0042',2400),
 ('KBT0042','KVT0041',120),
 ('KBT0271','KVT0051',5000),
 ('KBT0282','KVT0052',300),
 ('KBT0021','KVT0051',75),
 ('KBT0032','KVT0052',120),
 ('KBT0051','KVT0051',300),
 ('KBT0062','KVT0052',2400),
 ('KBT0081','KVT0051',75),
 ('KBT0022','KVT0061',3200),
 ('KBT0041','KVT0062',600),
 ('KBT0052','KVT0061',200),
 ('KBT0071','KVT0062',200),
 ('KBT0082','KVT0061',200),
 ('KBT0101','KVT0062',1250),
 ('KBT0112','KVT0061',200),
 ('KBT0221','KVT0071',300),
 ('KBT0232','KVT0072',2400),
 ('KBT0251','KVT0071',200),
 ('KBT0262','KVT0072',300),
 ('KBT0301','KVT0071',450),
 ('KBT0152','KVT0072',1250),
 ('KBT0171','KVT0071',1250),
 ('KBT0252','KVT0081',450),
 ('KBT0291','KVT0082',600),
 ('KBT0302','KVT0081',200),
 ('KBT0161','KVT0082',300),
 ('KBT0172','KVT0081',600),
 ('KBT0191','KVT0082',120),
 ('KBT0202','KVT0081',600),
 ('KBT0151','KVT0091',2400),
 ('KBT0162','KVT0092',1250),
 ('KBT0181','KVT0091',120),
 ('KBT0192','KVT0092',120),
 ('KBT0211','KVT0091',2400),
 ('KBT0222','KVT0092',3200),
 ('KBT0241','KVT0091',3200),
 ('KBT0182','KVT0101',450),
 ('KBT0201','KVT0102',200),
 ('KBT0212','KVT0101',75),
 ('KBT0231','KVT0102',75),
 ('KBT0242','KVT0101',450),
 ('KBT0261','KVT0102',200),
 ('KBT0292','KVT0101',3200),
 ('KBT0061','KVT0111',25),
 ('KBT0072','KVT0112',15),
 ('KBT0091','KVT0111',25),
 ('KBT0102','KVT0112',8),
 ('KBT0121','KVT0111',8),
 ('KBT0132','KVT0112',25);
COMMIT;

-- ========== PHAN D. KIEM TRA SAU IMPORT ==========
-- Mong doi: 11 kho (5 NL, 5 TP, 1 loi), 33 hang hoa, 64 SKU, 80 dong ton.
SELECT loaiKho, COUNT(*) AS soKho FROM Kho GROUP BY loaiKho ORDER BY loaiKho;
SELECT COUNT(*) AS tongKho FROM Kho;
SELECT COUNT(*) AS tongHangHoa FROM HangHoa;
SELECT COUNT(*) AS tongBienThe FROM BienTheHangHoa;
SELECT COUNT(*) AS tongDongTonKho FROM TonKho;
SELECT COUNT(*) AS tongPhieuKiemKe FROM PhieuKiemKe;
SELECT k.maKho, k.tenKho, k.loaiKho,
       COUNT(t.maBienThe) AS soDongTonKho,
       COALESCE(SUM(t.soLuongTon), 0) AS tongSoLuongMau
FROM Kho k
LEFT JOIN ViTriKho vt ON vt.maKho=k.maKho
LEFT JOIN TonKho t ON t.maViTri=vt.maViTri
GROUP BY k.maKho,k.tenKho,k.loaiKho
ORDER BY k.maKho;
