-- The Stars | Lap yeu cau chuyen kho | CSDL rieng theo dac ta
-- TAO MOI: import vao mot database CHUA CO bang. KHONG dung tren DB co du lieu cu.
-- MySQL 5.0.51b+: utf8, InnoDB; ràng buộc so luong kiem tra tai PHP.
SET NAMES utf8;
CREATE DATABASE IF NOT EXISTS thestars_qlkhmm_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE thestars_qlkhmm_db;

CREATE TABLE Kho (
 maKho VARCHAR(20) NOT NULL PRIMARY KEY,
 tenKho VARCHAR(100) NOT NULL,
 diaChi VARCHAR(255) NULL,
 trangThai VARCHAR(30) NOT NULL DEFAULT 'HOAT_DONG',
 ghiChu VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE HangHoa (
 maHangHoa VARCHAR(20) NOT NULL PRIMARY KEY,
 tenHangHoa VARCHAR(150) NOT NULL,
 donViTinh VARCHAR(30) NOT NULL,
 trangThai VARCHAR(30) NOT NULL DEFAULT 'HOAT_DONG',
 moTa VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE BienTheHangHoa (
 maBienThe VARCHAR(20) NOT NULL PRIMARY KEY,
 maHangHoa VARCHAR(20) NOT NULL,
 mauSac VARCHAR(50) NOT NULL,
 kichThuoc VARCHAR(50) NOT NULL,
 trangThai VARCHAR(30) NOT NULL DEFAULT 'HOAT_DONG',
 UNIQUE KEY uq_bienthe (maHangHoa,mauSac,kichThuoc),
 CONSTRAINT fk_bienthe_hh FOREIGN KEY (maHangHoa) REFERENCES HangHoa(maHangHoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE NguoiDung (
 maTaiKhoan VARCHAR(20) NOT NULL PRIMARY KEY,
 tenDangNhap VARCHAR(50) NOT NULL UNIQUE,
 matKhauHash VARCHAR(255) NOT NULL,
 hoTen VARCHAR(100) NOT NULL,
 vaiTro VARCHAR(30) NOT NULL,
 trangThai VARCHAR(30) NOT NULL DEFAULT 'HOAT_DONG'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE TonKho (
 maKho VARCHAR(20) NOT NULL,
 maBienThe VARCHAR(20) NOT NULL,
 soLuongTon DECIMAL(18,3) NOT NULL DEFAULT 0,
 soLuongGiuCho DECIMAL(18,3) NOT NULL DEFAULT 0,
 ngayCapNhat DATETIME NOT NULL DEFAULT '2026-10-27 00:00:00',
 PRIMARY KEY (maKho,maBienThe),
 CONSTRAINT fk_tonkho_kho FOREIGN KEY (maKho) REFERENCES Kho(maKho),
 CONSTRAINT fk_tonkho_bt FOREIGN KEY (maBienThe) REFERENCES BienTheHangHoa(maBienThe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE YeuCauChuyenKho (
 maYeuCau VARCHAR(30) NOT NULL PRIMARY KEY,
 maKhoXuat VARCHAR(20) NOT NULL,
 maKhoNhap VARCHAR(20) NOT NULL,
 maNguoiLap VARCHAR(20) NOT NULL,
 lyDoChuyen VARCHAR(500) NOT NULL,
 ngayLap DATETIME NOT NULL DEFAULT '2026-10-27 00:00:00',
 ngayGui DATETIME NULL,
 trangThai VARCHAR(50) NOT NULL DEFAULT 'NHAP',
 ghiChu VARCHAR(500) NULL,
 CONSTRAINT fk_yc_kx FOREIGN KEY (maKhoXuat) REFERENCES Kho(maKho),
 CONSTRAINT fk_yc_kn FOREIGN KEY (maKhoNhap) REFERENCES Kho(maKho),
 CONSTRAINT fk_yc_user FOREIGN KEY (maNguoiLap) REFERENCES NguoiDung(maTaiKhoan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE ChiTietYeuCauChuyenKho (
 maYeuCau VARCHAR(30) NOT NULL,
 maBienThe VARCHAR(20) NOT NULL,
 soLuongChuyen DECIMAL(18,3) NOT NULL,
 ghiChu VARCHAR(255) NULL,
 PRIMARY KEY(maYeuCau,maBienThe),
 CONSTRAINT fk_ct_yc FOREIGN KEY(maYeuCau) REFERENCES YeuCauChuyenKho(maYeuCau),
 CONSTRAINT fk_ct_bt FOREIGN KEY(maBienThe) REFERENCES BienTheHangHoa(maBienThe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO NguoiDung(maTaiKhoan,tenDangNhap,matKhauHash,hoTen,vaiTro,trangThai) VALUES ('ND01','duong_demo','!KHONG_DUNG_DE_DANG_NHAP!','Phan Đại Dương','QUAN_LY_KHO','HOAT_DONG');

INSERT IGNORE INTO Kho(maKho,tenKho,diaChi,trangThai) VALUES
 ('KHO01','Kho thành phẩm A','Khu A','HOAT_DONG'),
 ('KHO02','Kho thành phẩm B','Khu B','HOAT_DONG'),
 ('KHO03','Kho thành phẩm C','Khu C','HOAT_DONG'),
 ('KHO04','Kho thành phẩm D','Khu D','HOAT_DONG'),
 ('KHO05','Kho thành phẩm E','Khu E','HOAT_DONG'),
 ('KHO06','Kho nguyên liệu A','Khu nguyên liệu A','HOAT_DONG'),
 ('KHO07','Kho nguyên liệu B','Khu nguyên liệu B','HOAT_DONG'),
 ('KHO08','Kho nguyên liệu C','Khu nguyên liệu C','HOAT_DONG'),
 ('KHO09','Kho nguyên liệu D','Khu nguyên liệu D','HOAT_DONG'),
 ('KHO10','Kho nguyên liệu E','Khu nguyên liệu E','HOAT_DONG'),
 ('KHO11','Kho hàng lỗi','Khu hàng lỗi','HOAT_DONG');

INSERT IGNORE INTO HangHoa(maHangHoa,tenHangHoa,donViTinh,trangThai) VALUES
 ('HH01','Áo thun','Cái','HOAT_DONG'),
 ('HH02','Quần jean','Cái','HOAT_DONG'),
 ('HH03','Áo khoác','Cái','HOAT_DONG');

INSERT IGNORE INTO BienTheHangHoa(maBienThe,maHangHoa,mauSac,kichThuoc) VALUES
 ('BT01','HH01','Trắng','M'),
 ('BT02','HH01','Đen','L'),
 ('BT03','HH02','Xanh','30'),
 ('BT04','HH02','Đen','32'),
 ('BT05','HH03','Xám','L');

INSERT IGNORE INTO TonKho(maKho,maBienThe,soLuongTon,soLuongGiuCho) VALUES
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
 (maYeuCau,maKhoXuat,maKhoNhap,maNguoiLap,lyDoChuyen,ngayLap,ngayGui,trangThai) VALUES
 ('YCCK260001','KHO01','KHO02','ND01','Bổ sung hàng cho kho B',
  '2026-10-27 09:00:00',NULL,'NHAP'),
 ('YCCK260002','KHO01','KHO03','ND01','Chuẩn bị hàng dự phòng',
  '2026-10-27 09:10:00','2026-10-27 09:15:00','CHO_PHE_DUYET');

INSERT IGNORE INTO ChiTietYeuCauChuyenKho
 (maYeuCau,maBienThe,soLuongChuyen,ghiChu) VALUES
 ('YCCK260001','BT01',10,'Áo trắng cỡ M'),
 ('YCCK260001','BT03',5,NULL),
 ('YCCK260002','BT02',8,NULL);


SELECT COUNT(*) AS soKho FROM Kho;
