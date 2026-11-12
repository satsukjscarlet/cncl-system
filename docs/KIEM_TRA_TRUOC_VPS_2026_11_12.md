# Kiểm tra trước cập nhật VPS ngày 12/11/2026

## Kết quả tự động

- Chạy toàn bộ PHPUnit: 171 tests, 2124 assertions; 163 đạt, 8 thất bại cũ.
- Không có ca thất bại trong nhóm nghiệp vụ đơn vị bán hàng, Trung tâm, DVKH, PTN, hàng chờ ký, bổ sung ký tươi, báo cáo, phân quyền, duyệt thiết bị, phiên đăng nhập và SmartCA giả lập.
- Kiểm tra cú pháp 150 file PHP: đạt.
- Tạo route cache và view cache vào thư mục kiểm tra riêng: đạt. Kiểm tra cú pháp PHP sinh ra từ 254 Blade templates: đạt. Không thay cache cấu hình đang dùng.
- `composer check-platform-reqs --no-dev`: đạt trên máy local. Kết quả không xác nhận PHP/extensions trên VPS.
- `git diff --check`: đạt; không có file merge conflict chưa giải quyết.
- Playwright trên 26 màn hình/trạng thái từ dữ liệu kiểm thử mới: cả desktop 1440px và mobile 390px đạt, tổng 52 tổ hợp. Kiểm tra lỗi JavaScript, tràn ngang, thanh thao tác cố định, modal trả lại, quyền hiển thị và bộ lọc. Ảnh tại `%TEMP%/cncl-vps-audit-20261112/screenshots`.

## 8 kiểm thử cũ chưa đồng bộ

- `tests/Feature/Auth/PasswordResetTest.php`: 4 ca kỳ vọng quên/đặt lại mật khẩu công khai, nhưng các route đã tắt.
- `tests/Feature/Auth/RegistrationTest.php`: 2 ca kỳ vọng tự đăng ký, nhưng route đã tắt.
- `tests/Feature/ProfileTest.php`: 2 ca kỳ vọng người dùng tự xóa tài khoản, nhưng không có DELETE `/profile`.

Không mở lại các chức năng này để làm test xanh. Bộ kiểm thử đầy đủ vẫn trả exit code 1; pipeline tự động coi mọi test thất bại là chặn triển khai sẽ cần xử lý sự không đồng bộ này trước.

## Database và an toàn

Test dùng SQLite `:memory:` với chốt bảo vệ trong `tests/TestCase.php`. Lần rà này không chạy migration, seeder, import, fresh, refresh hay wipe trên MySQL. Không gửi email/SmartCA thật, không chạy scheduler/worker.

Truy vấn MySQL local chỉ đọc, có transaction READ ONLY: đã có migration ngày 11/11, các cột mới đầy đủ, vai trò DonViBanHang có 6 quyền. Tại lần đọc có 24 tài khoản, 1234 khách hàng, 1759 yêu cầu, 1731 phiếu và 36 bổ sung ký tươi. Đây là dữ liệu hiện tại, không dùng số đếm ngày trước để suy luận người dùng chưa có thay đổi.

## Điều kiện trước khi đưa lên VPS

1. Các thay đổi nghiệp vụ vẫn ở working tree, gồm file mới chưa được Git theo dõi. Phải commit/push đầy đủ, đặc biệt middleware, service, migration và SalesUnitAccessSeeder. Chỉ `git pull` sẽ không lấy được file còn nằm ngoài commit.
2. Sao lưu database và `.env` trên VPS; kiểm tra migration còn thiếu trước khi cập nhật. Không chép `.env` local lên VPS.
3. Thực hiện bảo trì và các lệnh trong `docs/QUY_TRINH_DON_VI_BAN_HANG.md`. Bắt buộc chạy migration mới trước khi cho người dùng sử dụng code mới và chạy seeder chuyên biệt để có vai trò mới.
4. Chỉ dùng `SalesUnitAccessSeeder` cho cập nhật này; không cần chạy seeder dữ liệu test hoặc đồng bộ lại toàn bộ vai trò hiện có.
5. Sau cập nhật, kiểm tra bằng một tài khoản Trung tâm cũ và tài khoản đơn vị đã được quản trị cấp hợp lệ. Đối chiếu gửi/trả/gửi lại, phân quyền chéo đơn vị, duyệt thiết bị và PDF đã ký. Kiểm tra cấu hình scheduler/queue hiện có vẫn chạy sau restart.

## Giới hạn kết luận

Chưa thao tác lên VPS, chưa kiểm tra PHP/extensions, quyền thư mục, proxy Cloudflare, email, VNPT thực tế hoặc in giấy phôi trên VPS. Không khẳng định hệ thống không còn mọi lỗi; các kiểm thử nghiệp vụ hiện có không phát hiện lỗi mới.
