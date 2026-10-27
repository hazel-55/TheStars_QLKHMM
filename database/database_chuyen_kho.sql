-- ============================================================
-- THE STARS - UC LAP YEU CAU CHUYEN KHO
-- Database DOC LAP cho UC lap yeu cau chuyen kho
-- Chi tao/nap du lieu cho module CHUYEN KHO.
-- Khong su dung/cap nhat du lieu cua cac phan he khac.
-- Tuong thich MySQL cu: dung utf8, khong dung utf8mb4/CHECK.
-- ============================================================

SET NAMES utf8;
CREATE DATABASE IF NOT EXISTS thestars_qlkhmm_db
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_general_ci;
USE thestars_qlkhmm_db;

-- Bang danh muc rieng cua UC chuyen kho, co CK_ de tranh trung
-- NguoiDung/HangHoa dang duoc module Kiem dinh su dung.
CREATE TABLE IF NOT EXISTS CK_Kho (
  maKho VARCHAR(20) NOT NULL,
  tenKho VARCHAR(100) NOT NULL,
  diaChi VARCHAR(255),
  dangHoatDong TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (maKho)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS CK_HangHoa (
  maHangHoa VARCHAR(20) NOT NULL,
  tenHangHoa VARCHAR(150) NOT NULL,
  donViTinh VARCHAR(30) NOT NULL,
  dangHoatDong TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (maHangHoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS CK_BienThe (
  maBienThe VARCHAR(20) NOT NULL,
  maHangHoa VARCHAR(20) NOT NULL,
  mauSac VARCHAR(50) NOT NULL,
  kichThuoc VARCHAR(30) NOT NULL,
  PRIMARY KEY (maBienThe),
  UNIQUE KEY uq_ck_bienthe (maHangHoa,mauSac,kichThuoc),
  CONSTRAINT fk_ck_bienthe_hanghoa
    FOREIGN KEY (maHangHoa) REFERENCES CK_HangHoa(maHangHoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS CK_TonKho (
  maKho VARCHAR(20) NOT NULL,
  maBienThe VARCHAR(20) NOT NULL,
  soLuongTon DECIMAL(12,3) NOT NULL DEFAULT 0,
  soLuongGiuCho DECIMAL(12,3) NOT NULL DEFAULT 0,
  ngayCapNhat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (maKho,maBienThe),
  CONSTRAINT fk_ck_tonkho_kho
    FOREIGN KEY (maKho) REFERENCES CK_Kho(maKho),
  CONSTRAINT fk_ck_tonkho_bienthe
    FOREIGN KEY (maBienThe) REFERENCES CK_BienThe(maBienThe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS YeuCauChuyenKho (
  maYeuCau VARCHAR(20) NOT NULL,
  maKhoXuat VARCHAR(20) NOT NULL,
  maKhoNhap VARCHAR(20) NOT NULL,
  maNguoiLap VARCHAR(20) NOT NULL,
  lyDo VARCHAR(500) NOT NULL,
  ngayLap TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngayGui DATETIME NULL,
  trangThai ENUM('NHAP','CHO_PHE_DUYET','DA_DUYET','TU_CHOI','DA_HUY')
    NOT NULL DEFAULT 'NHAP',
  PRIMARY KEY (maYeuCau),
  KEY idx_yc_trangthai_ngay (trangThai,ngayLap),
  CONSTRAINT fk_ycck_khoxuat
    FOREIGN KEY (maKhoXuat) REFERENCES CK_Kho(maKho),
  CONSTRAINT fk_ycck_khonhap
    FOREIGN KEY (maKhoNhap) REFERENCES CK_Kho(maKho)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ChiTietYeuCauChuyenKho (
  maYeuCau VARCHAR(20) NOT NULL,
  maBienThe VARCHAR(20) NOT NULL,
  soLuongYeuCau DECIMAL(12,3) NOT NULL,
  ghiChu VARCHAR(255),
  PRIMARY KEY (maYeuCau,maBienThe),
  CONSTRAINT fk_ctycck_ycck
    FOREIGN KEY (maYeuCau) REFERENCES YeuCauChuyenKho(maYeuCau),
  CONSTRAINT fk_ctycck_bienthe
    FOREIGN KEY (maBienThe) REFERENCES CK_BienThe(maBienThe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Du lieu mau: INSERT IGNORE de co the import lai ma khong loi trung khoa.
INSERT IGNORE INTO CK_Kho(maKho,tenKho,diaChi,dangHoatDong) VALUES
 ('KHO01','Kho thành phẩm A','Khu A',1),
 ('KHO02','Kho thành phẩm B','Khu B',1),
 ('KHO03','Kho thành phẩm C','Khu C',1),
 ('KHO04','Kho thành phẩm D','Khu D',1),
 ('KHO05','Kho thành phẩm E','Khu E',1),
 ('KHO06','Kho nguyên liệu A','Khu nguyên liệu A',1),
 ('KHO07','Kho nguyên liệu B','Khu nguyên liệu B',1),
 ('KHO08','Kho nguyên liệu C','Khu nguyên liệu C',1),
 ('KHO09','Kho nguyên liệu D','Khu nguyên liệu D',1),
 ('KHO10','Kho nguyên liệu E','Khu nguyên liệu E',1),
 ('KHO11','Kho hàng lỗi','Khu hàng lỗi',1);

INSERT IGNORE INTO CK_HangHoa(maHangHoa,tenHangHoa,donViTinh,dangHoatDong) VALUES
 ('HH01','Áo thun','Cái',1),
 ('HH02','Quần jean','Cái',1),
 ('HH03','Áo khoác','Cái',1);

INSERT IGNORE INTO CK_BienThe(maBienThe,maHangHoa,mauSac,kichThuoc) VALUES
 ('BT01','HH01','Trắng','M'),
 ('BT02','HH01','Đen','L'),
 ('BT03','HH02','Xanh','30'),
 ('BT04','HH02','Đen','32'),
 ('BT05','HH03','Xám','L');

INSERT IGNORE INTO CK_TonKho(maKho,maBienThe,soLuongTon,soLuongGiuCho) VALUES
 ('KHO01','BT01',100,10),
 ('KHO01','BT02',80,0),
 ('KHO01','BT03',60,5),
 ('KHO01','BT04',40,0),
 ('KHO01','BT05',30,0),
 ('KHO02','BT01',20,0),
 ('KHO02','BT02',15,0),
 ('KHO02','BT03',10,0),
 ('KHO03','BT01',35,0),
 ('KHO04','BT02',45,0),
 ('KHO05','BT05',25,0),
 ('KHO06','BT01',20,0),
 ('KHO07','BT02',20,0),
 ('KHO08','BT03',20,0),
 ('KHO09','BT04',20,0),
 ('KHO10','BT05',20,0),
 ('KHO11','BT05',5,0);

INSERT IGNORE INTO YeuCauChuyenKho
 (maYeuCau,maKhoXuat,maKhoNhap,maNguoiLap,lyDo,ngayLap,ngayGui,trangThai) VALUES
 ('YCCK260001','KHO01','KHO02','ND01','Bổ sung hàng cho kho B',
  '2026-10-27 09:00:00',NULL,'NHAP'),
 ('YCCK260002','KHO01','KHO03','ND01','Chuẩn bị hàng dự phòng',
  '2026-10-27 09:10:00','2026-10-27 09:15:00','CHO_PHE_DUYET');

INSERT IGNORE INTO ChiTietYeuCauChuyenKho
 (maYeuCau,maBienThe,soLuongYeuCau,ghiChu) VALUES
 ('YCCK260001','BT01',10,'Áo trắng cỡ M'),
 ('YCCK260001','BT03',5,NULL),
 ('YCCK260002','BT02',8,NULL);

-- Kiem tra nhanh sau khi import:
SELECT DATABASE() AS database_dang_dung;
SELECT COUNT(*) AS so_kho FROM CK_Kho;
SELECT COUNT(*) AS so_phieu_chuyen_kho FROM YeuCauChuyenKho;
