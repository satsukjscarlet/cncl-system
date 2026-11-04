# Kiểm tra hệ thống ngày 04/11/2026

## Phạm vi và an toàn

- PHPUnit chạy SQLite `:memory:` theo `phpunit.xml`, có chốt bảo vệ trong `tests/TestCase.php`.
- Không migrate, refresh, seed, import hoặc sửa dữ liệu MySQL.
- Không gửi ký SmartCA thật, gửi email thật hoặc chạy scheduler/worker.
- Giao diện được kiểm tra bằng HTML sinh từ kiểm thử, Playwright chặn yêu cầu ghi và mạng ngoài.

## Kết quả tự động

- Toàn bộ: 152 tests, 1964 assertions; 144 đạt, 8 thất bại.
- Kiểm tra cú pháp 143 file PHP trong `app`, `routes`, `bootstrap`, `config`: đạt. Kết quả này không thay thế kiểm tra Blade hoặc render PDF.
- Chạy lại nhóm tạo dữ liệu giao diện: 58 tests, 1153 assertions, đạt.
- Playwright: 19 màn hình/trạng thái trên desktop 1440px và mobile 390px, tổng 38 tổ hợp đạt; kiểm tra tràn ngang, lỗi JavaScript và các điều kiện riêng của từng màn hình. Ảnh tại `%TEMP%/cncl-audit-20261104/screenshots`.
- Các nhóm nghiệp vụ, phân quyền, báo cáo, bổ sung ký tươi, phiên đăng nhập, SmartCA giả lập và lưu PDF ký đều không có ca thất bại trong lần chạy đầy đủ.

## Các ca thất bại cần xử lý

1. `tests/Feature/Auth/PasswordResetTest.php`: 4 ca vẫn kỳ vọng luồng quên mật khẩu công khai, nhưng `routes/auth.php` không mở `/forgot-password` và `/reset-password`. Nhận 404 hoặc không có thông báo reset.
2. `tests/Feature/Auth/RegistrationTest.php`: 2 ca vẫn kỳ vọng tự đăng ký, nhưng `/register` không được mở.
3. `tests/Feature/ProfileTest.php`: 2 ca vẫn kỳ vọng tự xóa tài khoản, nhưng `routes/web.php` chỉ mở GET/PATCH `/profile`, không mở DELETE. Nhận 405 và không có lỗi xác thực mật khẩu như test kỳ vọng.

Các route này cũng không được mở trong bản `HEAD` hiện tại. Đây là sự không đồng bộ giữa kiểm thử cũ và tính năng đang cung cấp; không nên mở lại các route chỉ để test xanh. Nên đổi thành kiểm thử xác nhận thao tác công khai bị chặn và không làm thay đổi tài khoản.

## Giới hạn

Không kết luận toàn bộ hệ thống không còn lỗi từ kết quả trên. Chưa kiểm tra ký thật VNPT, email thực tế, cấu hình VPS, in giấy phôi và bố cục mọi PDF với toàn bộ dữ liệu MySQL. Giao diện kiểm thử dùng dữ liệu SQLite, không phải phiên người dùng thật trên VPS.
