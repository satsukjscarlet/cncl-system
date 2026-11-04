# Phân quyền chức năng theo tài khoản

## Cách sử dụng

Admin vào **Người dùng**, bấm nút biểu tượng khiên **Phân quyền riêng** trên dòng tài khoản; hoặc vào Sửa tài khoản rồi bấm Phân quyền riêng. Màn hình Phân quyền nhóm cũng có liên kết sang danh sách tài khoản.

Mỗi quyền chức năng trong danh mục quyền hiện có có ba lựa chọn:

| Lựa chọn | Ý nghĩa |
| --- | --- |
| Kế thừa nhóm | Không có ngoại lệ; dùng quyền của các nhóm hiện tại |
| Cấp riêng | Có quyền dù nhóm không cấp quyền đó |
| Chặn riêng | Không có quyền đó, kể cả nhóm hoặc quyền trực tiếp đã cấp |

Thứ tự ưu tiên: **ràng buộc nghiệp vụ/an toàn → chặn riêng → cấp riêng → quyền nhóm**. Cột Quyền từ nhóm cho biết nguồn kế thừa; cột Quyền chức năng sau lưu giúp kiểm tra kết quả. Tìm theo tên tiếng Việt hoặc mã quyền; lọc dòng trên màn hình không làm mất các thiết lập đang có.

Ví dụ hai người cùng nhóm PTN: người A kế thừa product.update nên sửa được sản phẩm; người B chọn Chặn riêng product.update nên không sửa được. Người B có thể được Cấp riêng device.manage mà không cần đổi sang nhóm DVKH hoặc Admin. Chọn Kế thừa nhóm để bỏ cả chặn và cấp riêng đối với quyền đó.

Bấm **Lưu quyền riêng**. Thay đổi có hiệu lực từ request tiếp theo, không yêu cầu người dùng đăng xuất. Trang đã mở hoặc file đã tải về không thể thu hồi nội dung đã hiển thị/tải xuống. Khi chặn riêng dashboard.view, lần đăng nhập mới không có URL đích sẽ về Tài khoản của tôi thay vì dashboard bị cấm.

## Phạm vi và giới hạn

- Danh mục quyền chức năng dùng guard web của Spatie; các quyền được tạo mới sau này mặc định kế thừa, không cần tạo ngoại lệ cho từng người. Không sửa trực tiếp vendor.
- Các chức năng có quyền riêng biệt vẫn độc lập: quyền xem không tự cấp quyền sửa, xuất Excel hoặc ký. Nếu cần cấm xem lẫn xuất, kiểm tra cả quyền xem và quyền xuất tương ứng.
- Không đổi vai trò, không thay quyền nhóm, không thay phạm vi Trung tâm/phòng, không bỏ qua trạng thái phiếu, quyền sở hữu hoặc điều kiện ký/in/cấp lại.
- Trung tâm không được nhận nhóm chức năng nội bộ DVKH/PTN, lập/ký/từ chối/gửi email phiếu, quản trị tài khoản, phân quyền, thiết bị, cấu hình, SLA, nhật ký hoặc quản lý Trung tâm. Chặn cả giao diện cấp riêng và kiểm tra quyền phía server để tránh mở dữ liệu ngoài Trung tâm.
- Báo cáo công việc vẫn áp dụng phạm vi cá nhân/phòng đã cấu hình. Chặn report.view sẽ chặn cả báo cáo công việc, kể cả tài khoản có vai trò DVKH/PTN/Trưởng PTN được xem mặc định.
- Tài khoản Admin được bảo vệ: trang quyền riêng chỉ xem, không lưu ngoại lệ cho Admin. Chức năng đặc biệt vốn chỉ dành cho Admin (ví dụ xóa hẳn dữ liệu danh mục chưa dùng, quản lý dữ liệu test, báo cáo dung lượng, cấu hình phạm vi báo cáo) vẫn giữ ràng buộc đó; cấp quyền chức năng thông thường không biến người dùng thành Admin.
- Chỉ Admin đồng thời có role_permission.manage được sửa quyền riêng tài khoản. Người được cấp quản lý nhóm nhưng không phải Admin không được dùng endpoint quyền riêng để tự nâng quyền.
- Các quyền quản lý người dùng/phân quyền rất nhạy cảm, chỉ cấp cho người được tin cậy và ủy quyền. Không dùng tài khoản quản lý dùng chung cho đại lý.

## An toàn và lịch sử

- Có nhật ký **Phân quyền tài khoản**, lưu tài khoản được sửa và trạng thái trước/sau từng quyền, không lưu mật khẩu.
- Kiểm tra phiên bản trước khi lưu: nếu nhóm/quyền/tài khoản đã đổi sau khi mở trang, yêu cầu tải lại để tránh ghi đè.
- Thiếu danh sách quyền, có mã lạ hoặc giá trị không hợp lệ sẽ bị từ chối toàn bộ; không coi form bị cắt là yêu cầu xóa quyền còn lại.
- Cập nhật quyền trực tiếp và danh sách chặn trong cùng transaction; chỉ tác động quyền web của tài khoản đó, giữ nguyên guard khác.
- Không cho tự sửa qua profile hoặc form người dùng chung. permission_denials không nằm trong mass assignment.
- Menu AdminLTE, middleware permission, Gate/can, hasPermissionTo/hasAnyPermission/hasAllPermissions và danh sách quyền hiệu lực đều tôn trọng chặn riêng. Dashboard/work queue lọc liên kết theo quyền thực tế; quyền được cấp riêng cho nghiệp vụ cũng được nhận diện khi chọn workspace. Lịch sử in dùng quyền in thực tế, không chỉ tên nhóm; Trung tâm vẫn giới hạn Trung tâm của mình.
- Thông báo gửi theo quyền loại bỏ người bị chặn, không chỉ dựa trên nhóm. Truy vấn User::permission là nguồn ứng viên Spatie, không được xem là kết quả quyền cuối cùng; dùng can/hasPermissionTo trước khi giao công việc hoặc gửi dữ liệu.
- PermissionSeeder hiện tại không xóa ngoại lệ theo người dùng. Sửa quyền nhóm chỉ ảnh hưởng các quyền đang kế thừa; không gỡ cấp riêng hoặc chặn riêng.

## Triển khai

Migration thêm cột JSON nullable permission_denials vào users; các tài khoản cũ có null nên không tự sinh ngoại lệ. Quyền cấp riêng dùng bảng model_has_permissions sẵn có. Không cần PermissionSeeder hay seed data test.

Sau khi sao lưu và cập nhật code, chạy trên VPS:

```bash
php artisan migrate --path=database/migrations/2026_11_03_000002_add_permission_denials_to_users.php --force
php artisan permission:cache-reset
```

Tạo lại config/route/view cache theo quy trình triển khai đang dùng. Nếu chưa chạy migration phạm vi báo cáo công việc ở bản trước, cần chạy thêm migration 2026_11_03_000001_add_work_report_scope_to_users.php. Không dùng migrate:fresh, migrate:refresh, db:wipe hoặc seeder dữ liệu test.

Đã chạy riêng migration phân quyền trên local sau khi sao lưu users, permissions, roles, các bảng liên kết và migrations tại private/backups/user-permissions-20261103-160414-0f3062/users-permissions-and-migrations.sql. Hash dữ liệu tài khoản cũ và các bảng quyền/nhóm/liên kết trước/sau đều giống nhau, 0 tài khoản có chặn riêng được tự tạo. Giữ nguyên 1758 yêu cầu, 1731 phiếu và 36 bổ sung. Không chỉnh quyền thực tế của tài khoản nào.
