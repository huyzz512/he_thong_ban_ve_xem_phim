# 🎬 HỆ THỐNG ĐẶT VÉ XEM PHIM TRỰC TUYẾN - EAUT CINEMA

Hệ thống Đặt Vé Xem Phim Trực Tuyến hiện đại, đầy đủ tính năng được phát triển dựa trên nền tảng **PHP MVC** thuần và **Tailwind CSS**. Ứng dụng hỗ trợ trọn vẹn luồng trải nghiệm từ khách hàng đặt vé chọn ghế trực tiếp, thanh toán chuyển khoản qua mã VietQR động, cho đến bảng quản trị thông minh (Admin Dashboard) kiểm soát giao dịch, rạp chiếu, suất chiếu tự động và báo cáo chuyên sâu.

---

## 🌟 Tính Năng Nổi Bật

### 1. Phân Hệ Khách Hàng (Client Client)
- **🎬 Trang chủ Premium:** Banner tiêu điểm tự động, danh sách phim Đang Chiếu, Sắp Chiếu với hiệu ứng kính mờ (Glassmorphic) hiện đại.
- **🔍 Bộ lọc tìm kiếm thông minh:** Tìm nhanh phim theo tên, thể loại, rạp, ngày chiếu.
- **🎟 Quy trình Đặt vé 3 bước chuyên nghiệp:**
  - **Chọn suất:** Lọc theo rạp chiếu, ngày chiếu, khung giờ.
  - **Sơ đồ ghế thông minh:** Hiển thị trực quan vị trí ghế trống, ghế đang chọn, ghế VIP (+30% phụ thu), và ghế đã bán thời gian thực.
  - **Thanh toán VietQR động:** Tự động tạo mã QR chứa số tiền, tài khoản của chính rạp đó và nội dung chuyển khoản mã vé động. Bộ đếm ngược 15 phút tự hủy đơn nếu quá giờ.
- **👤 Hồ sơ cá nhân tiện ích:**
  - Cập nhật thông tin cá nhân, đổi mật khẩu an toàn.
  - **Lịch sử đặt vé:** Theo dõi chi tiết trạng thái đơn đặt vé (Đang chờ duyệt, Đã xác nhận, Đã hủy).
  - **Vé điện tử (E-Ticket):** Tạo mã QR check-in độc quyền kèm hỗ trợ in vé trực tiếp (`@media print`).

### 2. Phân Hệ Quản Trị (Admin Panel)
- **📊 Dashboard Phân tích Chuyên Sâu:**
  - Thống kê doanh thu theo mốc thời gian thực: Hôm nay, Tuần này, Tháng này, Năm này và Tổng lũy kế.
  - Biểu đồ phân bổ doanh thu theo từng Rạp chiếu và Khung giờ vàng hoạt động.
  - Xếp hạng Top phim bán chạy nhất và thống kê tỉ lệ giao dịch thành công / thất bại.
  - **Xuất Báo Cáo Excel (.xls):** Xuất toàn bộ dữ liệu thống kê trực quan chuẩn UTF-8 không lỗi chữ tiếng Việt.
- **🏦 Quản lý Thanh toán & Giao dịch:**
  - Quản lý danh sách giao dịch toàn hệ thống hoặc lọc theo từng rạp.
  - **Xác nhận thanh toán thủ công:** Admin duyệt yêu cầu chuyển khoản của khách hàng để kích hoạt vé điện tử.
  - **Cấu hình Ngân hàng theo rạp:** Mỗi rạp chiếu có thể cấu hình tài khoản ngân hàng riêng (MB, Techcombank, Vietcombank...) để tạo mã VietQR tương ứng.
- **📅 Quản lý Suất chiếu tự động:**
  - Thuật toán tự động tính giờ kết thúc suất chiếu và **ngăn chặn trùng lịch chiếu** trong cùng một phòng chiếu.
  - Thiết lập phụ phí ngày lễ (+15%) và giờ vàng (+10%).
- **CRUD Danh mục:** Quản lý Phim, Rạp chiếu, Phòng chiếu (nhập CSV tự động), và Người dùng (phân quyền Admin/Customer).

---

## 🛠 Công Nghệ Sử Dụng

- **Backend:** PHP 7.4+ (Hướng đối tượng, Mô hình MVC).
- **Database:** MySQL / MariaDB.
- **Frontend:** Tailwind CSS, JavaScript thuần (Vanilla JS), các API bổ trợ (VietQR, QR Server).
- **Styling & Icons:** Google Fonts (Inter, Outfit), FontAwesome / SVG Icons.

---

## 📦 Hướng Dẫn Cài Đặt (XAMPP trên Windows)

### 📌 Bước 1: Sao chép dự án vào thư mục XAMPP
Hãy chắc chắn rằng thư mục code dự án được đặt trong thư mục `htdocs` của XAMPP:
```
C:\xampp\htdocs\Hệ thống bán vé xem phim\
```

### 📌 Bước 2: Khởi động Apache & MySQL
1. Mở **XAMPP Control Panel**.
2. Nhấn nút **Start** ở cả 2 dịch vụ **Apache** và **MySQL**.

### 📌 Bước 3: Tạo và Import Cơ sở dữ liệu
1. Truy cập vào trình duyệt: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Tạo mới một cơ sở dữ liệu có tên là: `movie_booking_db` (hoặc tên tùy ý của bạn).
3. Nhấp chọn cơ sở dữ liệu vừa tạo, chọn tab **Import** (Nhập).
4. Tìm và chọn file database mẫu nằm trong thư mục dự án (ví dụ `database.sql` hoặc file SQL tương ứng) và nhấn **Go** (Thực hiện).

### 📌 Bước 4: Cấu hình kết nối Database
Mở file `config/Database.php` trong code dự án và điều chỉnh cấu hình kết nối khớp với máy của bạn:
```php
private $host = "localhost";
private $db_name = "movie_booking_db"; // Tên database vừa tạo
private $username = "root";             // Tài khoản mặc định XAMPP
private $password = "";                 // Mật khẩu mặc định XAMPP (để trống)
```

### 📌 Bước 5: Chạy ứng dụng trên trình duyệt
Mở trình duyệt bất kỳ và truy cập đường dẫn:
```
http://localhost/Hệ thống bán vé xem phim/views/home.php
```

---

## 🔑 Tài Khoản Thử Nghiệm

Hệ thống đã có sẵn dữ liệu mẫu cho cả tài khoản Khách hàng và Admin:

### 👤 Tài khoản Admin (Quản trị viên)
- **Email:** `20233448@eaut.edu.vn`
- **Mật khẩu:** `huydz512`
- **Quyền:** Quản trị toàn bộ hệ thống, cấu hình ngân hàng rạp, duyệt giao dịch, thống kê.

### 👤 Tài khoản Customer (Khách hàng)
- **Email:** `customer@gmail.com`
- **Mật khẩu:** `123456`
- **Quyền:** Xem phim, đặt vé chọn ghế, thanh toán trực tuyến, xem vé điện tử.

---

## 📈 Đánh Giá Hệ Thống & Hướng Phát Triển

- **Bảo mật:** Hệ thống phân quyền chặt chẽ thông qua `admin_guard.php` ngăn chặn truy cập trái phép. Mật khẩu được băm an toàn bằng `password_hash()`.
- **Trải nghiệm UI/UX:** Màu đỏ chủ đạo tinh tế, bố cục rõ ràng, hỗ trợ đầy đủ thiết bị di động (Responsive Layout).
- **Khả năng mở rộng:** Có thể dễ dàng tích hợp các cổng thanh toán tự động khác như VNPAY, MoMo, hoặc kết nối Webhook ngân hàng để xác nhận giao dịch tự động không cần Admin duyệt thủ công.

---
**Chúc bạn có những trải nghiệm điện ảnh tuyệt vời cùng EAUT Cinema!** 🎬🍿
