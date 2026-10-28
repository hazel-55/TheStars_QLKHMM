# THE STARS — UC LẬP PHIẾU KIỂM KÊ (TRẦN TRUNG KIÊN)

> **Chỉ cài và thử trên nhánh `Kien` và cơ sở dữ liệu LOCAL RIÊNG.** Không có lệnh `git push`, `git merge` hoặc thao tác cập nhật `main` trong quy trình. Đây là phần mã nguồn bổ sung, **KHÔNG phải toàn bộ project**. Đã kiểm tra cú pháp PHP và unit test tính toán; kiểm thử tích hợp trên WAMP/MySQL cần thực hiện tại máy bạn.

## A. Yêu cầu kỹ thuật

- WampServer 64-bit với **PHP 8.0 trở lên**, Apache, **MySQL 5.7.8 trở lên (khuyên MySQL 8.x) hoặc MariaDB 10.3+**. Hướng dẫn dành cho MySQL tương đối hiện đại, không dùng MySQL 5.0.x / WAMP đời 2008.
- Bật các extension PHP: `mysqli` + mysqlnd (`mysqli_stmt::get_result()`), `mbstring`, `fileinfo`; bật hỗ trợ upload file.
- Có clone Git đúng của nhóm: `TheStars_QLKHMM` trên nhánh `Kien`; nhánh `main` đã có `components/layout/{header,sidebar,footer}.php`, `class/common/menuconfig.php`, `assets/css/layout.css`.
- URL project cần là **`http://localhost/TheStars_QLKHMM/`**, vì cấu hình chung nhóm đã gắn `baseUrl=/TheStars_QLKHMM`.

## B. Chuẩn bị code — làm trong đúng nhánh Kiên

1. Trong VS Code mở **folder clone Git** `TheStars_QLKHMM` của bạn. Mở Git Bash:
   ```bash
   git branch
   git status
   ```
   Phải có `* Kien`. Nếu không, **dừng lại** và dùng `git switch Kien` sau khi đã bảo đảm không có thay đổi chưa commit. Nếu hiện `working tree clean`, có thể tiếp tục.
2. Tải ZIP `Kien_LapPhieuKiemKe.zip` kèm câu trả lời; giải nén **ở ngoài project**. Mở thư mục vừa giải nén, **chép các folder `assets`, `class`, `pages`, `database`, `storage`, `tests`** và file README này vào **thư mục gốc** `TheStars_QLKHMM` trên nhánh `Kien`.
3. Khi Windows hỏi, chọn **Merge folders** (gộp thư mục), không xóa/ghi đè các thư mục `class/common`, `components`, `index.php`, hay code của Hồng. Trong ZIP không có những file chung đó. Không chép một folder `Kien_LapPhieuKiemKe` lồng bên trong project: cần chép **NỘI DUNG bên trong** vào thư mục gốc.
4. Kiểm tra trên VS Code phải thấy:
   ```text
   TheStars_QLKHMM/
   ├── assets/css/lap-phieu-kiem-ke.css
   ├── assets/js/lap-phieu-kiem-ke.js
   ├── class/tranTrungKien/lapPhieuKiemKe/
   │   ├── clsLapPhieuKiemKe.php
   │   ├── tinhToan.php
   │   ├── db.php
   │   └── config.example.php
   ├── pages/lap-phieu-kiem-ke/
   │   ├── index.php
   │   ├── phieu.php
   │   ├── xoa.php
   │   ├── tai-tep.php
   │   ├── dev-login.php  (localhost thôi; đã ignore trong Git)
   │   └── bootstrap.php
   ├── database/kiemke_kien_mysql.sql
   └── storage/kiem-ke/.htaccess
   ```
5. Nếu thư mục clone đang ở `C:\Users\...\TheStars_QLKHMM` mà WAMP phục vụ `C:\wamp64\www`, bạn cần **đưa nguyên project Git clone** (bao gồm thư mục ẩn `.git`) vào `C:\wamp64\www\TheStars_QLKHMM` và mở lại thư mục đó bằng VS Code. Có thể di chuyển folder từ Explorer khi WAMP dừng. Để tránh nhầm nhiều bản sao, sau khi chuyển hãy luôn làm trên bản Git ở thư mục `www`. Không đưa code về nhánh main. (Một cách nâng cao hơn là tạo Windows junction từ web root đến folder clone; không bắt buộc.)

## C. Tạo CSDL riêng và import SQL

1. Khởi động WampServer, kiểm tra **Apache** và **MySQL** chạy (icon màu xanh). Vào `http://localhost/phpmyadmin/` bằng trình duyệt.
2. Chọn tab **SQL** và dán toàn bộ file `database/kiemke_kien_mysql.sql`, bấm **Go / Thực hiện**. Bạn cũng có thể chọn **Import** với chính file SQL đó. Script tự tạo cơ sở dữ liệu tên **`thestars_kiemke_kien`**. *Không chọn database của nhóm và không chạy script này trong CSDL nhóm.*
3. Kiểm tra trong phpMyAdmin: chọn `thestars_kiemke_kien` → có các bảng `PhieuKiemKe`, `ChiTietPhieuKiemKe`, `MinhChungKiemKe`, `ThongBaoKiemKe`, `TonKho`, `Kho`, `ViTriKho`, `HangHoa`, `BienTheHangHoa`, `DanhMucHangHoa`, `NguoiDung`, `KiemKeSoThuTu`, `BienBanGiaiTrinh`.
4. Dữ liệu mẫu: tài khoản kiểm thử `BKK001`, người nhận `BGD001`; kho `K001` có 3 dòng hàng; kho `K002` có 1 dòng; kho `K003` ngưng hoạt động sẽ không xuất hiện trong danh sách.

## D. Cấu hình kết nối MySQL (không thay đổi class/common)

1. Trong `class/tranTrungKien/lapPhieuKiemKe/`, **copy** `config.example.php` và đổi tên bản copy thành `config.local.php`.
2. Nếu MySQL root không có mật khẩu và cổng `3306`, giữ:
   ```php
   <?php
   return [
       'host' => '127.0.0.1',
       'port' => 3306,
       'database' => 'thestars_kiemke_kien',
       'username' => 'root',
       'password' => '',
   ];
   ```
   Nếu cổng/mật khẩu trên máy khác, sửa **chỉ file `config.local.php`** theo WAMP local.
3. `.gitignore` bên trong folder đã tránh Git theo dõi `config.local.php`; đừng gửi mật khẩu lên GitHub.

## E. Chạy trên trình duyệt

1. Vào `http://localhost/TheStars_QLKHMM/pages/lap-phieu-kiem-ke/dev-login.php`.
2. Nhấn **Vào chức năng kiểm kê** (chỉ dùng localhost với dữ liệu mẫu; file này bị .gitignore để không push lên GitHub).
3. Ở trang danh sách, chọn **Lập phiếu kiểm kê**, chọn kho `K001`, nhấn **Lấy hàng trong kho**.
4. Chọn một hoặc nhiều SKU và nhập các trường kiểm kê. Thí dụ dòng tồn `100` nhập số thực tế `98`, số tiêu hủy `3`: số còn lại `95`, chênh lệch `−5`. Nhập tình trạng, **lý do chênh lệch**, **lý do tiêu hủy**, **hình thức tiêu hủy** và **giải trình**. Có thể chọn file JPG/PNG/PDF dưới 5MB để minh chứng.
5. **Lưu bản nháp**: phiếu có trạng thái `Nháp`, có thể mở lại sửa, tải minh chứng hoặc xóa bản nháp.
6. **Gửi phê duyệt**: bắt buộc có ít nhất một dòng, tình trạng hàng hóa, số liệu hợp lệ, giải trình và lý do khi cần. Thành công chuyển trạng thái `Chờ phê duyệt`, ghi người gửi/thời gian gửi và tạo thông báo trong bảng `ThongBaoKiemKe` cho người `BGD001`; sau đó **chỉ xem, không sửa/xóa**.
7. Gửi không làm thay đổi `TonKho`; việc duyệt và cập nhật tồn thuộc UC **Phê duyệt phiếu kiểm kê**, không nằm trong chức năng lập phiếu.

## F. Kiểm tra CSDL sau khi lưu/gửi

Trong phpMyAdmin → `thestars_kiemke_kien` → tab SQL:

```sql
SELECT maPhieuKiemKe, maKho, maNguoiLap, maNguoiGui,
       thoiGianLap, thoiGianGui, trangThai, phienBan
FROM PhieuKiemKe ORDER BY thoiGianLap DESC;

SELECT maPhieuKiemKe, maBienThe, maViTri, soLuongTonHeThong,
       soLuongThucTe, soLuongHangLoiTieuHuy,
       soLuongThucTeConLai, soLuongChenhLech
FROM ChiTietPhieuKiemKe ORDER BY maChiTiet DESC;

SELECT maPhieuKiemKe, maNguoiNhan, noiDung, daDoc
FROM ThongBaoKiemKe ORDER BY maThongBao DESC;

SELECT maBienThe, maViTri, soLuongTon FROM TonKho;
```

## G. Checklist ca kiểm thử

| Case | Làm gì? | Mong đợi |
|---|---|---|
| TC01 | Chọn kho K001 | Hiện SKU, màu, size, vị trí và số tồn |
| TC02 | Tồn 100, kiểm đếm 105, hủy 0 | Còn 105, chênh +5 |
| TC03 | Tồn 100, thực tế 95, hủy 5 | Còn 90, chênh −10 |
| TC04 | Hủy 7, thực tế 5 rồi gửi | Báo lỗi, không gửi |
| TC05 | Gửi khi có chênh lệch nhưng thiếu lý do | Báo lỗi, vẫn chưa gửi |
| TC06 | Gửi khi tiêu hủy >0 nhưng chưa có giải trình | Báo lỗi, chưa gửi |
| TC07 | Lưu phiếu chưa đủ thông tin | Vẫn lưu thành bản nháp |
| TC08 | Mở bản nháp và sửa | Lưu thay đổi thành công |
| TC09 | Xóa một bản nháp do mình lập | Xóa thành công |
| TC10 | Gửi phiếu hợp lệ | Chờ phê duyệt, có thông báo BGD001, không sửa lại |
| TC11 | Xem `TonKho` trước/sau gửi | Không thay đổi tồn kho |
| TC12 | Đính kèm JPG/PNG/PDF dưới 5MB | Tệp lưu kín trong `storage/`, tải qua `tai-tep.php` |
| TC13 | Chọn file lớn >5MB, file lạ | Chặn file, không lưu phiếu |
| TC14 | Người ngoài/không có phiên BKK | 403, không được sử dụng nghiệp vụ |
| TC15 | 2 tab sửa cùng phiếu, tab sau lưu bằng phiên bản cũ | Chặn vì xung đột phiên bản |

Có script kiểm tra công thức cơ bản trong `tests/test_tinh_toan.php`. Nếu PHP có CLI, chạy từ gốc project:

```bash
php tests/test_tinh_toan.php
```

## H. An toàn Git

Trong thư mục Git của bạn:

```bash
git branch
git status --short
```

Phải ở `* Kien`. Cần **kiểm tra đã chạy thử WAMP thành công rồi mới commit**. Hiện tại **không chạy** `git add`, `git commit`, `git push`, `git merge`, `git switch main`.

Nếu đã thử chạy thành công và sau này **bạn chủ động muốn lưu Git**, chỉ commit trên `Kien` sau khi soát file bằng `git status` (nhớ rằng không được đưa lên file `config.local.php`, `dev-login.php`, tệp upload minh chứng hoặc thông tin thật).

## I. Giới hạn và lưu ý thiết kế

- Đây là module hoàn chỉnh để **tạo, lưu nháp, sửa/xóa nháp và gửi** theo UC; không bao gồm màn hình BGĐ **phê duyệt thật** hoặc cập nhật tồn.
- `ThongBaoKiemKe` là thông báo **nội bộ trong CSDL**. Không có gửi email hay push notification bên ngoài.
- Số lượng thực tế và tình trạng được phép `NULL` khi là bản nháp (bắt buộc khi gửi); khác với ràng buộc NOT NULL trong thiết kế cuối, nhằm phục vụ alternative flow "Lưu bản nháp thiếu dữ liệu".
- Hai cột `soLuongThucTeConLai` và `soLuongChenhLech` là **generated STORED columns** trong MySQL, tương đương yêu cầu tính toán persisted trong SQL Server.
- `phienBan` của phiếu và tồn kho mô phỏng optimistic concurrency bằng `INT UNSIGNED` (khác SQL Server `ROWVERSION`).
- Khi ghép vào DB thật nhóm cần thống nhất tên khóa ngoại, bảng người dùng, phân quyền, kết nối/transaction và chính sách đồng bộ thông báo với các UC khác. Không tự động thay đổi DB của nhóm.
