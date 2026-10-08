## 2026-10-08 - Mo quyen quan ly thiet bi dang nhap cho DVKH

File da sua:
- `PermissionSeeder.php` (`database/seeders/PermissionSeeder.php`): them quyen `device.manage` mac dinh cho vai tro DVKH.
- `RoleWorkspaceAccessTest.php` (`tests/Feature/RoleWorkspaceAccessTest.php`): cap nhat ma tran phan quyen, DVKH duoc truy cap `/user-devices`.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Tai khoan DVKH duoc quan ly thiet bi dang nhap: xem danh sach, duyet, khoa, xoa thiet bi theo route co quyen `device.manage`.
- Da chay `PermissionSeeder` tren moi truong local de cap nhat quyen hien tai.

Kiem tra:
- `php -l database/seeders/PermissionSeeder.php`.
- `php -l tests/Feature/RoleWorkspaceAccessTest.php`.
- `php artisan db:seed --class=PermissionSeeder`.
- `php artisan test --filter=role_route_access_matrix_matches_workspace_permissions`.
- `php artisan test --filter=new_login_device_notifies_device_admins`.

## 2026-10-08 - Toi uu tim kiem san pham trong select2 yeu cau cap phieu

File da sua:
- `CertificateRequestController.php` (`app/Http/Controllers/CertificateRequestController.php`): toi uu API `certificate-requests/product-options`, uu tien ma san pham trung chinh xac va ma da chuan hoa.
- `ProductController.php` (`app/Http/Controllers/ProductController.php`): toi uu tim kiem danh muc san pham voi cung logic uu tien ma san pham.
- `RoleWorkspaceAccessTest.php` (`tests/Feature/RoleWorkspaceAccessTest.php`): bo sung test tim `T110` uu tien `T110`, sau do den `T-110`.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Tim san pham khong con chi sap xep theo `product_code` khi nguoi dung nhap tu khoa.
- Neu tu khoa trung ma san pham chinh xac, ket qua do duoc dua len dau.
- Neu ma san pham co dau cach, dau gach ngang, gach cheo, dau cham hoac gach duoi, he thong chuan hoa de van tim dung khi nguoi dung go lien nhu `T110`.
- Cac tim kiem theo ten san pham, kich thuoc danh nghia va tieu chuan van duoc giu lam fallback.
- Danh muc san pham cung tim duoc theo nhom san pham va tieu chuan; khi nguoi dung bam sap xep cot thu cong, he thong van ton trong sap xep cot da chon.
- Voi tu khoa dang ma san pham nhu `N27`, neu co san pham khop ma, he thong chi tra ket qua theo ma va khong de cac dong chi khop `DN27` trong ten/kich thuoc chen vao.

Kiem tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`.
- `php -l app/Http/Controllers/ProductController.php`.
- `php -l tests/Feature/RoleWorkspaceAccessTest.php`.
- `php artisan test --filter=product_options_prioritize_exact_and_normalized_product_code_matches`.
- `php artisan test --filter=product_options_do_not_keep_previous_code_when_new_code_search_matches_dn_text`.
- `php artisan test --filter=product_index_prioritizes_exact_and_normalized_product_code_matches`.
- `php artisan view:cache`.

## 2026-10-07 - Bao mat phien dang nhap khi khoa tai khoan va doi mat khau

File da sua/them:
- `SessionSecurityService.php` (`app/Services/SessionSecurityService.php`): them service thu hoi session cua mot tai khoan trong bang `sessions`.
- `EnsureLoginDeviceIsApproved.php` (`app/Http/Middleware/EnsureLoginDeviceIsApproved.php`): bo sung kiem tra tai khoan bi khoa o moi request va tu dong logout.
- `UserController.php` (`app/Http/Controllers/UserController.php`): khi khoa tai khoan hoac reset mat khau thi thu hoi cac phien dang nhap cu.
- `PasswordController.php` (`app/Http/Controllers/Auth/PasswordController.php`): khi nguoi dung tu doi mat khau co tuy chon dang xuat cac thiet bi/phien khac.
- `edit.blade.php` (`resources/views/profile/edit.blade.php`): them checkbox dang xuat khoi cac thiet bi khac sau khi doi mat khau.
- `edit.blade.php` (`resources/views/users/edit.blade.php`): cap nhat ghi chu reset mat khau se thu hoi phien cu.
- `RoleWorkspaceAccessTest.php` (`tests/Feature/RoleWorkspaceAccessTest.php`): bo sung test khoa user, reset mat khau va user bi khoa dang con session.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Khoa tai khoan se xoa cac session hien co cua tai khoan do trong bang `sessions`.
- Neu user bi khoa nhung session cu van con tren trinh duyet, request tiep theo se bi logout va chuyen ve man dang nhap.
- Reset mat khau tu man quan tri se thu hoi session cu cua user de buoc dang nhap lai bang mat khau moi.
- Doi mat khau ca nhan co tuy chon mac dinh dang xuat cac phien khac, giu lai phien hien tai.

Kiem tra:
- `php -l` cac file PHP vua sua/them.
- `php artisan view:cache`.
- `php artisan test --filter=deactivating_user_revokes_existing_sessions`.
- `php artisan test --filter=reset_password_revokes_existing_sessions`.
- `php artisan test --filter=inactive_authenticated_user_is_logged_out_on_next_request`.
- `php artisan test --filter=seeded_test_accounts_can_login_and_open_dashboard`.
- `php artisan test --filter=role_route_access_matrix_matches_workspace_permissions`.
- `php artisan test --filter=blocked_approved_device_is_logged_out_on_next_request`.

## 2026-10-07 - Toi uu quan ly thiet bi dang nhap

File da sua/them:
- `AuthenticatedSessionController.php` (`app/Http/Controllers/Auth/AuthenticatedSessionController.php`): tao log va thong bao cho admin khi co thiet bi moi cho duyet; bo qua kiem soat thiet bi cho tai khoan co quyen `device.manage`.
- `EnsureLoginDeviceIsApproved.php` (`app/Http/Middleware/EnsureLoginDeviceIsApproved.php`): tiep tuc thu hoi phien khi thiet bi bi khoa/chua duyet.
- `NotificationService.php` (`app/Services/NotificationService.php`): them gui thong bao theo quyen va thong bao `login_device_pending`.
- `PendingDeviceBadgeFilter.php` (`app/AdminLte/Menu/PendingDeviceBadgeFilter.php`): them badge so thiet bi dang cho duyet tren menu Thiet bi dang nhap.
- `adminlte.php` (`config/adminlte.php`): dang ky menu filter cho badge thiet bi cho duyet.
- `UserDeviceController.php` (`app/Http/Controllers/UserDeviceController.php`): load lich su thao tac gan nhat cua tung thiet bi.
- `index.blade.php` (`resources/views/user_devices/index.blade.php`): hien thi cot lich su gan nhat trong man quan ly thiet bi.
- `RoleWorkspaceAccessTest.php` (`tests/Feature/RoleWorkspaceAccessTest.php`): bo sung test thiet bi moi tao thong bao cho admin.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Khi tai khoan thuoc dien kiem soat dang nhap tu may/trinh duyet moi, he thong tao thiet bi cho duyet, ghi lich su va gui thong bao cho cac tai khoan co quyen quan ly thiet bi.
- Menu `Thiet bi dang nhap` hien badge so thiet bi dang cho duyet de admin thay ngay.
- Man quan ly thiet bi hien them lich su thao tac gan nhat: tao yeu cau, duyet, khoa, xoa.
- Tai khoan co quyen `device.manage` khong bi dua vao vong duyet thiet bi, tranh khoa nham admin.

Kiem tra:
- `php -l` cac file PHP vua sua/them.
- `php artisan test --filter=new_login_device_notifies_device_admins`.
- `php artisan test --filter=distribution_center_account_must_wait_for_device_approval`.
- `php artisan test --filter=blocked_approved_device_is_logged_out_on_next_request`.

## 2026-10-06 - Thu hồi phiên khi thiết bị đăng nhập bị khóa

File chính:
- `app/Http/Middleware/EnsureLoginDeviceIsApproved.php`
- `bootstrap/app.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung middleware kiểm tra thiết bị đăng nhập ở mỗi request web sau khi người dùng đã đăng nhập.
- Nếu thiết bị của tài khoản thuộc vai trò bị kiểm soát chưa được duyệt hoặc đã bị khóa, hệ thống tự logout, hủy session và chuyển về màn đăng nhập.
- Tài khoản có quyền `device.manage` được bỏ qua kiểm tra để tránh khóa nhầm admin khỏi màn duyệt thiết bị.
- Middleware cập nhật `last_used_at` tối đa mỗi 5 phút để tránh ghi database quá nhiều.
- Bổ sung test: thiết bị đã duyệt bị khóa sẽ bị đăng xuất ở request kế tiếp.

Kiểm tra:
- `php -l app/Http/Middleware/EnsureLoginDeviceIsApproved.php`
- `php -l bootstrap/app.php`
- `php -l tests/Feature/RoleWorkspaceAccessTest.php`
- `php artisan test --filter=blocked_approved_device_is_logged_out_on_next_request`
- `php artisan test --filter=distribution_center_account_must_wait_for_device_approval`
- `php artisan test --filter=device_control_can_be_disabled_for_distribution_center_login`
- `php artisan test --filter=seeded_test_accounts_can_login_and_open_dashboard`
- `php artisan test --filter=role_route_access_matrix_matches_workspace_permissions`

## 2026-10-06 - Bổ sung tên Trưởng phòng thử nghiệm trên mẫu in bộ

File chính:
- `app/Services/HardCopyBatchCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Mẫu PDF ký tươi `In bộ` bổ sung tên `Vũ Thị Diệu Thúy` ở vùng ký Trưởng phòng thử nghiệm trên trang cuối.
- Vị trí tên được căn theo mẫu `In đơn` để hai mẫu in ký tươi đồng bộ.

Kiểm tra:
- `php -l app/Services/HardCopyBatchCertificatePdfService.php`
- Render thử `HardCopyBatchCertificatePdfService` với phiếu có sản phẩm: `311:189799`

## 2026-10-05 - Chọn vai trò kiểm soát thiết bị bằng Select2

File chính:
- `app/Http/Controllers/SystemSettingController.php`
- `resources/views/system_settings/index.blade.php`
- `public/js/select2-init.js`
- `CHANGELOG_CNCL.md`

Nội dung:
- Đổi trường `Vai trò cần kiểm soát thiết bị` trong màn `Cấu hình hệ thống` từ nhập tay sang select2 chọn nhiều vai trò.
- Controller nhận danh sách vai trò dạng mảng, validate theo bảng `roles`, rồi lưu lại cấu hình theo định dạng cũ.
- Select2 dùng được `data-placeholder` để hiển thị gợi ý chọn vai trò rõ hơn.

Kiểm tra:
- `php -l app/Http/Controllers/SystemSettingController.php`
- `php artisan view:cache`
- `php artisan test --filter=device_control_can_be_disabled_for_distribution_center_login`
- `php artisan test --filter=distribution_center_account_must_wait_for_device_approval`

## 2026-10-05 - Cấu hình kiểm soát thiết bị và cảnh báo thiết bị chờ duyệt

File chính:
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/SystemSettingController.php`
- `app/Http/Controllers/DashboardController.php`
- `database/seeders/SystemSettingSeeder.php`
- `resources/views/system_settings/index.blade.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Thêm cấu hình bật/tắt kiểm soát thiết bị đăng nhập trong màn `Cấu hình hệ thống`.
- Thêm cấu hình danh sách vai trò cần kiểm soát thiết bị, mặc định `TrungTam`.
- Thêm cấu hình số thiết bị tối đa được duyệt cho mỗi tài khoản.
- Luồng đăng nhập đọc cấu hình thay vì hard-code vai trò `TrungTam`.
- Nếu tài khoản đã đạt giới hạn thiết bị được duyệt, thiết bị mới sẽ bị chặn và yêu cầu admin thu hồi thiết bị cũ.
- Dashboard admin có card `Thiết bị chờ duyệt`, mở nhanh danh sách thiết bị pending.
- Bổ sung test trường hợp tắt kiểm soát thiết bị thì tài khoản Trung tâm đăng nhập được ngay.

Kiểm tra:
- `php -l app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `php -l app/Http/Controllers/SystemSettingController.php`
- `php -l app/Http/Controllers/DashboardController.php`
- `php -l database/seeders/SystemSettingSeeder.php`
- `php artisan view:cache`
- `php artisan db:seed --class=SystemSettingSeeder`
- `php artisan test --filter=distribution_center_account_must_wait_for_device_approval`
- `php artisan test --filter=device_control_can_be_disabled_for_distribution_center_login`
- `php artisan test --filter=seeded_test_accounts_can_login_and_open_dashboard`
- `php artisan test --filter=role_route_access_matrix_matches_workspace_permissions`

## 2026-10-05 - Kiểm soát thiết bị đăng nhập cho tài khoản Trung tâm

File chính:
- `database/migrations/2026_10_05_000002_create_user_devices_table.php`
- `app/Models/UserDevice.php`
- `app/Models/User.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/UserDeviceController.php`
- `app/Http/Controllers/RolePermissionController.php`
- `routes/web.php`
- `config/adminlte.php`
- `database/seeders/PermissionSeeder.php`
- `resources/views/user_devices/index.blade.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung bảng `user_devices` để lưu thiết bị đăng nhập theo tài khoản, trạng thái `pending/approved/blocked`, IP, trình duyệt và thời gian sử dụng.
- Tài khoản vai trò `TrungTam` khi đăng nhập từ thiết bị mới sẽ bị chặn sau khi nhập đúng mật khẩu, hệ thống tạo yêu cầu thiết bị chờ admin duyệt.
- Thiết bị đã duyệt mới được đăng nhập; thiết bị bị khóa sẽ bị từ chối đăng nhập.
- Thêm màn `Thiết bị đăng nhập` trong khu vực Hệ thống để admin lọc, duyệt, khóa hoặc xóa thiết bị.
- Thêm quyền `device.manage` và nhãn tiếng Việt trên màn phân quyền.
- Cập nhật test đăng nhập/phân quyền theo nghiệp vụ thiết bị mới.

Kiểm tra:
- `php artisan migrate`
- `php artisan db:seed --class=PermissionSeeder`
- `php -l app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `php -l app/Http/Controllers/UserDeviceController.php`
- `php -l app/Http/Controllers/RolePermissionController.php`
- `php -l app/Models/UserDevice.php`
- `php -l database/migrations/2026_10_05_000002_create_user_devices_table.php`
- `php -l database/seeders/PermissionSeeder.php`
- `php -l routes/web.php`
- `php artisan view:cache`
- `php artisan route:list --name=user-devices`
- `php artisan test --filter=seeded_test_accounts_can_login_and_open_dashboard`
- `php artisan test --filter=distribution_center_account_must_wait_for_device_approval`
- `php artisan test --filter=role_route_access_matrix_matches_workspace_permissions`

## 2026-10-05 - Lọc/sắp xếp phiếu yêu cầu ký tươi và làm gọn danh sách phiếu

File chính:
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/quality_certificates/index.blade.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung bộ lọc `Yêu cầu ký tươi` trên màn danh sách phiếu CNCL.
- Tách thông tin ký tươi thành cột riêng để dễ quét danh sách, hiển thị số bản ký tươi nếu có.
- Làm lại khối lọc và bảng danh sách phiếu gọn hơn, có viền/đổ bóng nhẹ, trạng thái lọc đang áp dụng và gợi ý bấm tiêu đề cột để sắp xếp.
- Bổ sung sắp xếp tăng/giảm cho các cột chính: số phiếu, số yêu cầu, người tạo yêu cầu, khách hàng/công trình, đơn vị bán hàng, yêu cầu ký tươi, trung tâm, người lập, ngày ký và trạng thái.
- Bổ sung test lọc và sắp xếp phiếu có yêu cầu ký tươi kết hợp với dữ liệu đơn vị bán hàng.

Kiểm tra:
- `php -l app/Http/Controllers/QualityCertificateController.php`
- `php -l tests/Feature/RoleWorkspaceAccessTest.php`
- `php artisan view:cache`
- `php artisan test --filter=request_and_certificate_lists_can_filter_and_search_by_sales_unit`

## 2026-10-05 - Lọc và tìm kiếm Đơn vị bán hàng trên danh sách yêu cầu/phiếu

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/certificate_requests/index.blade.php`
- `resources/views/quality_certificates/index.blade.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Danh sách yêu cầu cấp phiếu có bộ lọc `Đơn vị bán hàng` và cột riêng hiển thị mã/tên đơn vị bán hàng.
- Danh sách phiếu CNCL có bộ lọc `Đơn vị bán hàng` và cột riêng hiển thị mã/tên đơn vị bán hàng.
- Ô từ khóa trên hai màn hình tìm được theo mã/tên đơn vị bán hàng.
- Tài khoản Trung tâm chỉ thấy danh sách đơn vị bán hàng thuộc trung tâm của mình trong bộ lọc.

Kiểm tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`
- `php -l app/Http/Controllers/QualityCertificateController.php`
- `php artisan view:cache`
- `php artisan test --filter=request_and_certificate_lists_can_filter_and_search_by_sales_unit`

## 2026-10-05 - Sửa import Đơn vị bán hàng với dữ liệu Excel thực tế

File chính:
- `app/Http/Controllers/SalesUnitController.php`
- `database/migrations/2026_10_05_000001_expand_sales_unit_bank_account_column.php`
- `tests/Feature/CertificateWorkflowTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Làm sạch dữ liệu import Đơn vị bán hàng: xử lý khoảng trắng đặc biệt/NBSP từ Excel và gom khoảng trắng nhiều dòng trong một ô.
- Mở rộng cột `sales_units.bank_account` sang `text` vì file thực tế có thể nhập cả số tài khoản, ngân hàng và chi nhánh trong cột `so_tai_khoan`.
- Tăng validate nhập tay `so_tai_khoan` lên tối đa 1000 ký tự.
- Thêm test import đúng dòng dữ liệu thực tế gồm `TP/HNHHTM/Công ty TNHH TM Thanh Mơ`.

Kiểm tra:
- `php -l app/Http/Controllers/SalesUnitController.php`
- `php -l database/migrations/2026_10_05_000001_expand_sales_unit_bank_account_column.php`
- `php -l tests/Feature/CertificateWorkflowTest.php`
- `php artisan migrate`
- `php artisan test --filter=sales_unit_import_accepts_real_world_sales_unit_row`

## 2026-10-05 - Reset cache phân quyền sau khi cập nhật role

File chính:
- `app/Http/Controllers/RolePermissionController.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Sau khi lưu phân quyền bằng màn `Phân quyền`, hệ thống gọi `PermissionRegistrar::forgetCachedPermissions()`.
- Tránh trường hợp đã cấp `report.view/report.export` cho Trưởng PTN nhưng middleware/controller vẫn đọc cache quyền cũ và trả 403.

Kiểm tra:
- `php -l app/Http/Controllers/RolePermissionController.php`
- `php artisan test --filter=report_permission_allows_truong_ptn_to_open_summary_report`

## 2026-10-05 - Sửa quyền xem báo cáo theo permission

File chính:
- `app/Http/Controllers/ReportController.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Sửa controller báo cáo không còn hard-code chỉ `Admin/LanhDao/TrungTam` được vào báo cáo.
- Tài khoản nội bộ như `Trưởng PTN` nếu được cấp `report.view` hoặc `report.export` sẽ truy cập được báo cáo theo quyền đã phân.
- Tài khoản `TrungTam` vẫn chỉ xem dữ liệu theo trung tâm của mình.
- Việt hóa thông báo 403 của báo cáo tổng hợp.

Kiểm tra:
- `php -l app/Http/Controllers/ReportController.php`
- `php -l tests/Feature/RoleWorkspaceAccessTest.php`
- `php artisan test --filter=report_permission_allows_truong_ptn_to_open_summary_report`

## 2026-10-05 - Ràng buộc số lượng sản phẩm từ 1 trở lên

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/certificate_requests/_form.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Đổi validate số lượng sản phẩm trên yêu cầu cấp phiếu từ `min:0.01` sang `min:1`.
- Áp dụng cho tạo mới, cập nhật yêu cầu, PTN lập trực tiếp, import Excel và dán Excel.
- Input số lượng trên giao diện đặt `min="1"` để chặn nhập rỗng/số âm/số nhỏ hơn 1 từ phía trình duyệt.
- Cập nhật thông báo lỗi thành `Số lượng sản phẩm phải từ 1 trở lên.`

Kiểm tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`
- `php -l app/Http/Controllers/PtnRequestController.php`
- `php artisan view:cache`
- `php artisan test --filter=request_product_excel_import_maps_product_codes_and_merges_quantities`

## 2026-10-03 - Việt hóa phần Đơn vị bán hàng trên form tạo yêu cầu

File chính:
- `resources/views/certificate_requests/_form.blade.php`
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Việt hóa nhãn, placeholder và mô tả của trường `Đơn vị bán hàng` trên màn tạo/sửa yêu cầu cấp phiếu.
- Việt hóa các thông báo backend liên quan đến khách hàng/đơn vị bán hàng không thuộc trung tâm đã chọn.

Kiểm tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`
- `php -l app/Http/Controllers/PtnRequestController.php`
- `php artisan view:cache`

## 2026-10-03 - Việt hóa giao diện danh mục Đơn vị bán hàng

File chính:
- `resources/views/sales_units/index.blade.php`
- `resources/views/sales_units/_form.blade.php`
- `resources/views/sales_units/create.blade.php`
- `resources/views/sales_units/edit.blade.php`
- `app/Http/Controllers/SalesUnitController.php`
- `app/Exports/SalesUnitsExport.php`
- `app/Exports/SalesUnitsTemplateExport.php`
- `resources/views/partials/sidebar.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Chuyển các nhãn, nút, tiêu đề, modal, cảnh báo import và thông báo thao tác của danh mục `Đơn vị bán hàng` sang tiếng Việt có dấu.
- Cập nhật trạng thái trong file Excel xuất ra và dữ liệu mẫu Excel có dấu.
- Giữ nguyên các mã cột kỹ thuật như `ma_bravo`, `ten_dvbh`, `ma_trung_tam` để không làm hỏng parser import.

Kiểm tra:
- `php -l app/Http/Controllers/SalesUnitController.php`
- `php -l app/Exports/SalesUnitsExport.php`
- `php -l app/Exports/SalesUnitsTemplateExport.php`
- `php artisan view:cache`

## 2026-10-03 - Bảo toàn phân quyền khi chạy PermissionSeeder trên server chính

File chính:
- `database/seeders/PermissionSeeder.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Đổi seeder phân quyền từ `syncPermissions()` sang cơ chế chỉ cấp bổ sung quyền còn thiếu.
- Không xóa các quyền legacy/custom đang có trong database để tránh làm hỏng phân quyền đã chỉnh trên server chính.
- Admin vẫn được bổ sung toàn bộ quyền hiện có; các role khác chỉ được thêm quyền mặc định mới nếu còn thiếu.

Kiểm tra:
- `php -l database/seeders/PermissionSeeder.php`
- `php artisan db:seed --class=PermissionSeeder`

## 2026-10-03 - Hiển thị danh mục Đơn vị bán hàng trên menu

File chính:
- `config/adminlte.php`
- `app/Http/Controllers/RolePermissionController.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Thêm mục `Đơn vị bán hàng` vào menu chính AdminLTE trong nhóm `Danh mục`.
- Bổ sung nhãn nhóm và nhãn quyền `sales_unit.*` để màn phân quyền hiển thị tiếng Việt dễ hiểu.

Kiểm tra:
- `php -l config/adminlte.php`
- `php -l app/Http/Controllers/RolePermissionController.php`
- `php artisan optimize:clear`
- `php artisan view:cache`
- `php artisan route:list --name=sales-units`

## 2026-10-02 - Hiển thị người tạo yêu cầu trên danh sách

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/certificate_requests/index.blade.php`
- `resources/views/quality_certificates/index.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Danh sách yêu cầu cấp phiếu hiển thị thêm cột `Người tạo yêu cầu` từ trường `requester_name`.
- Danh sách phiếu CNCL hiển thị thêm cột `Người tạo yêu cầu` lấy từ yêu cầu gốc của phiếu.
- Ô tìm kiếm ở hai màn hình hỗ trợ tìm theo tên người tạo yêu cầu.
- Cập nhật colspan dòng rỗng để không vỡ bảng sau khi thêm cột.

Kiểm tra:
- `php -l` controller/view/test liên quan.
- `php artisan view:cache`
- `php artisan test --filter=quality_certificate_list_and_detail_show_request_link_and_hard_copy_requirement`

## 2026-10-02 - Điều chỉnh ghi chú mẫu In đơn theo đáy bảng

File chính:
- `app/Services/HardCopyCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Mẫu `In đơn` trả về tọa độ đáy bảng sản phẩm sau khi vẽ bảng.
- Với phiếu 1 trang, trang cuối và trang kế cuối, phần ghi chú được đặt ngay dưới bảng sản phẩm.
- Các trang trước trang kế cuối vẫn giữ ghi chú theo vị trí cố định cũ để không phá bố cục phôi in.
- Vùng tên ký tươi bên dưới vẫn giữ tọa độ cố định.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`
- Render thử mẫu `In đơn` với phiếu `310`.
- Render thử mẫu `In đơn` với phiếu nhiều dòng `215`.

## 2026-10-02 - Sửa thiếu thông tin khách hàng trên mẫu in đơn/in bộ

File chính:
- `app/Services/HardCopyCertificatePdfService.php`
- `app/Services/HardCopyBatchCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Tăng biên an toàn khi tính chiều cao dòng thông tin khách hàng/công trình/địa điểm trên mẫu `In đơn` và `In bộ`.
- Tính chiều cao theo vùng chữ nhỏ hơn một chút so với vùng in thực tế để tránh TCPDF cắt dòng cuối với tên khách hàng, tên công trình hoặc địa điểm công trình dài.
- Bảng sản phẩm sẽ bắt đầu thấp hơn tương ứng khi phần thông tin phía trên cần nhiều dòng hơn.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`
- `php -l app/Services/HardCopyBatchCertificatePdfService.php`
- Render thử mẫu `In đơn` và `In bộ` với phiếu `310` và `308`.

## 2026-10-02 - Xóa hẳn danh mục chưa phát sinh liên kết

File chính:
- `routes/web.php`
- `app/Http/Controllers/ProductGroupController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/QualityStandardController.php`
- `app/Http/Controllers/UrgentReasonController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Models/Product.php`
- `app/Models/Customer.php`
- `resources/views/product_groups/index.blade.php`
- `resources/views/products/index.blade.php`
- `resources/views/quality_standards/index.blade.php`
- `resources/views/urgent_reasons/index.blade.php`
- `resources/views/customers/index.blade.php`
- `tests/Feature/MasterDataForceDeleteTest.php`

Nội dung:
- Thêm route và controller action `forceDestroy` cho nhóm sản phẩm, sản phẩm, tiêu chuẩn chất lượng, lý do yêu cầu gấp, khách hàng/công trình.
- Chỉ tài khoản `Admin` được xóa hẳn; quyền xóa mềm/đình chỉ hiện tại vẫn giữ nguyên.
- Backend chặn xóa hẳn nếu danh mục đã được liên kết: nhóm sản phẩm còn sản phẩm, sản phẩm đã vào yêu cầu/phiếu, tiêu chuẩn còn sản phẩm, lý do gấp/khách hàng đã vào yêu cầu cấp phiếu.
- Giao diện danh sách chỉ hiển thị nút `Xóa hẳn` cho Admin khi bộ đếm liên kết bằng 0.

Kiểm tra:
- `php -l` các controller/model/route liên quan.
- `php artisan view:cache`
- `php artisan test tests/Feature/MasterDataForceDeleteTest.php`

## 2026-10-01 - Hiển thị yêu cầu ký tươi và link yêu cầu trên phiếu CNCL

File chính:
- `resources/views/quality_certificates/index.blade.php`
- `resources/views/quality_certificates/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Hoàn nguyên phần thay đổi nghiệp vụ in ký tươi khẩn cấp: in ký tươi không tự chuyển phiếu sang `ISSUED` và không tự hoàn tất yêu cầu.
- Danh sách phiếu hiển thị `Số yêu cầu` dưới dạng link về chi tiết yêu cầu cấp phiếu.
- Danh sách phiếu hiển thị badge `Yêu cầu ký tươi: X bản` ngay dưới số yêu cầu nếu yêu cầu gốc có tích ký tươi.
- Chi tiết phiếu hiển thị link `Số yêu cầu` và dòng `Yêu cầu ký tươi: Có - X bản` hoặc `Không`.

Kiểm tra:
- Bổ sung test danh sách/chi tiết phiếu hiển thị link yêu cầu và thông tin yêu cầu ký tươi.

## 2026-10-01 - Dán Excel sản phẩm theo tên trong yêu cầu cấp phiếu

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `resources/views/certificate_requests/_form.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Thêm nút `Dán theo tên` cạnh nút `Dán từ Excel` tại danh sách sản phẩm đề nghị cấp phiếu.
- API dán Excel nhận `lookup_by=code|name`; chế độ theo tên yêu cầu tên sản phẩm khớp chính xác với danh mục.
- Nếu tên sản phẩm không tồn tại hoặc bị trùng nhiều mã trong danh mục, hệ thống trả lỗi rõ ràng và hướng dẫn dùng dán theo mã sản phẩm.
- Bổ sung test cho dán theo mã, dán theo tên, lỗi tên không tồn tại và lỗi tên trùng.

Kiểm tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`
- `php -l resources/views/certificate_requests/_form.blade.php`
- `php artisan view:cache`
- `php artisan test --filter=request_product_paste`
- `php artisan test --filter=CertificateWorkflowTest`
- `php artisan test --filter=RoleWorkspaceAccessTest`

## 2026-09-25 - Dùng thống nhất PDF ký số gốc và xử lý hàng đợi kiểm tra ký

- Trang xem PDF và email dùng chung `StoredSignedCertificatePdf`: đọc nguyên nội dung file đã nhúng chữ ký; không dựng PDF thay thế cho phiếu đã ký/phát hành.
- Thiếu file, thiếu trạng thái nhúng chữ ký hoặc không có cấu trúc chữ ký PDF thì báo lỗi, không gửi bản dựng lại.
- Job bỏ qua giao dịch giả lập có `smartca_response.test_data=true`, ưu tiên giao dịch đang chờ và luân phiên các phiếu đã kiểm tra để một nhóm lỗi không chặn các phiếu sau.
- Giữ nguyên thuật toán phân trang/cân bằng trang cuối theo thiết kế trước đây (cho phép giữ một dòng ở trang cuối).
- Kiểm tra PDF bản nháp của 5 phiếu mới nhất NP/TP/HP/HD/TH-0011: lần lượt 78/56/40/99/66 dòng, 9/7/5/11/7 trang; đủ thứ tự dòng, phần bảng và ghi chú nằm trước khung ký hiện tại. Không thay đổi file đã gửi ký.
- Kiểm tra file phiếu số 5 và dữ liệu đính kèm email: trùng từng byte; kiểm tra trường hợp không có PDF gốc bị chặn. Máy chủ chưa có PHPUnit; đã chạy kiểm tra PHP trực tiếp và kiểm tra cú pháp.

# CNCL Update Log

File này dùng để ghi lại các cập nhật chức năng/kỹ thuật của hệ thống CNCL từ ngày 2026-08-29.

## Quy ước ghi log

- Mỗi lần cập nhật thêm một mục mới ở đầu file.
- Ghi rõ ngày, nhóm chức năng, file chính đã sửa, nội dung thay đổi và kết quả kiểm tra.
- Nếu có lỗi chưa xử lý xong, ghi vào phần "Ghi chú".

## 2026-09-29

### Ký tươi khẩn cấp và bổ sung phiếu quá hạn vào danh sách chờ gửi ký

File chính:
- `app/Models/QualityCertificate.php`
- `app/Models/PrintLog.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/quality_certificates/show.blade.php`
- `resources/views/quality_certificates/ready_to_sign.blade.php`
- `database/migrations/2026_09_29_000001_add_print_mode_to_print_logs_table.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung điều kiện `canEmergencyPrintHardCopy()` cho phiếu chưa ký số nhưng đã vào luồng chờ ký/đang chờ ký/quá hạn ký, không áp dụng cho phiếu nháp, chờ Trưởng PTN duyệt, bị trả lại, đã hủy hoặc đã phát hành.
- Thêm chế độ `print_mode=emergency` cho in ký tươi khẩn cấp; in thường vẫn chỉ dùng cho phiếu đã ký số/phát hành.
- Màn chi tiết phiếu hiển thị `In đơn khẩn cấp` và `In bộ khẩn cấp` khi phiếu đủ điều kiện; modal có cảnh báo phiếu chưa ký số và bắt nhập lý do.
- Lịch sử in hiển thị rõ loại in `Sau ký số` hoặc `Khẩn cấp`; chi tiết phiếu có cảnh báo nếu đã từng in ký tươi khẩn cấp.
- Màn `Phiếu chờ gửi ký` lấy thêm các phiếu SmartCA quá hạn/hết hạn để Trưởng PTN có thể gửi lại đơn lẻ hoặc gửi lại hàng loạt.
- Bổ sung thống kê số phiếu quá hạn cần gửi lại và cột trạng thái ký `Chờ gửi` / `Cần gửi lại`.

Kiểm tra:
- `php -l app/Models/QualityCertificate.php`: pass.
- `php -l app/Models/PrintLog.php`: pass.
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan migrate`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- Kiểm tra dữ liệu nhanh: 5 phiếu chờ gửi ký lần đầu, 10 phiếu quá hạn/cần gửi lại, 15 phiếu đủ điều kiện in ký tươi khẩn cấp.

## 2026-09-26

### Tài liệu - hướng dẫn sử dụng cho Trung tâm phân phối

File chính:
- `docs/HUONG_DAN_TRUNG_TAM_PHAN_PHOI.md`
- `docs/KICH_BAN_VIDEO_HUONG_DAN_TRUNG_TAM.md`
- `CHANGELOG_CNCL.md`

Nội dung:
- Tạo tài liệu hướng dẫn riêng cho tài khoản Trung tâm phân phối: đăng nhập, dashboard, khách hàng - công trình, tạo yêu cầu cấp phiếu, import sản phẩm, lưu nháp/gửi DVKH, theo dõi trạng thái, tải phiếu và yêu cầu cấp lại.
- Tạo kịch bản video hướng dẫn theo từng phân cảnh, có gợi ý hình ảnh cần quay, lời thoại và checklist cảnh quay.

Kiểm tra:
- Đã rà nội dung tài liệu bằng text Markdown.

### In ký tươi - đồng bộ font thông tin khách hàng giữa In đơn và In bộ

File chính:
- `app/Services/HardCopyCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Đồng bộ phần giá trị `Tên khách hàng`, `Tên công trình`, `Địa điểm công trình`, `Ngày xuất hàng` của mẫu `In đơn` theo mẫu `In bộ`.
- Giá trị thông tin chuyển sang Times New Roman đậm, màu đen, cỡ 13; nhãn vẫn giữ Times New Roman thường, màu đen, cỡ 13.
- Chuẩn hóa text trước khi vẽ để hạn chế lỗi chữ dài/liền ký tự.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`: pass.
- Render thử PDF `In đơn` và `In bộ` bằng phiếu đã ký gần nhất: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### In ký tươi - tách In đơn và In bộ

File chính:
- `app/Http/Controllers/QualityCertificateController.php`
- `app/Services/HardCopyBatchCertificatePdfService.php`
- `app/Models/PrintLog.php`
- `database/migrations/2026_09_26_000001_add_print_template_to_print_logs_table.php`
- `resources/views/quality_certificates/show.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Đổi nút in ký tươi hiện tại thành `In đơn`, vẫn dùng mẫu cũ có vùng ghi chú/chữ ký cố định trên tất cả các trang.
- Bổ sung nút `In bộ`, dùng mẫu phân trang mới theo hướng phiếu điện tử: các trang trước tận dụng diện tích bảng, trang cuối chừa khoảng trống bên dưới để ký/in trên phôi mới.
- Bổ sung service render PDF riêng cho mẫu `In bộ` để không ảnh hưởng mẫu `In đơn`.
- Lưu loại mẫu in vào lịch sử in bằng trường `print_template` để phân biệt `In đơn` và `In bộ`.

Kiểm tra:
- `php -l` controller, model và service mới: pass.
- `php artisan migrate`: pass.
- `php artisan view:cache`: pass.
- Render thử PDF `In đơn` và `In bộ` bằng phiếu đã ký gần nhất: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Danh sách phiếu CNCL - tối ưu giao diện lọc và thao tác

File chính:
- `resources/views/quality_certificates/index.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Thiết kế lại vùng bộ lọc theo dạng lưới responsive, dễ thao tác hơn trên màn hình rộng và màn hình nhỏ.
- Bổ sung chip hiển thị nhanh các bộ lọc đang áp dụng: từ khóa, trung tâm, khoảng ngày và trạng thái.
- Tối ưu bảng danh sách phiếu: nhấn mạnh số phiếu, gom thông tin phụ thành dòng nhỏ, canh lại cụm thao tác.
- Nút `Gom cấp lại` hiển thị số phiếu đã chọn và trạng thái chọn tất cả rõ hơn.
- Giữ nguyên phân quyền: tài khoản Trung tâm không hiển thị bộ lọc Trung tâm.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

## 2026-09-25

### Danh sách phiếu CNCL - bổ sung lọc từ ngày đến ngày

File chính:
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/quality_certificates/index.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung bộ lọc `Từ ngày lập` và `Đến ngày lập` tại màn danh sách phiếu CNCL.
- Bộ lọc áp dụng theo ngày lập phiếu `quality_certificates.created_at`.
- Giữ nguyên phân quyền: tài khoản Trung tâm vẫn không hiển thị bộ lọc Trung tâm.

Kiểm tra:
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Màn tài khoản cá nhân và tự đổi mật khẩu

File chính:
- `config/adminlte.php`
- `app/Models/User.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/Auth/PasswordController.php`
- `app/Http/Requests/ProfileUpdateRequest.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/partials/topbar.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Chuyển màn `/profile` sang giao diện AdminLTE để đồng bộ với toàn hệ thống.
- Thêm mục `Tài khoản của tôi` vào menu để người dùng dễ vào tự cập nhật thông tin.
- Bổ sung `adminlte_profile_url()` cho model User để user menu góc phải của AdminLTE mở đúng màn profile.
- Người dùng tự cập nhật được họ tên, email và SmartCA User ID; tên đăng nhập, vai trò, trung tâm chỉ hiển thị đọc.
- Giữ chức năng tự đổi mật khẩu bằng mật khẩu hiện tại.
- Ghi activity log khi người dùng cập nhật hồ sơ cá nhân hoặc tự đổi mật khẩu.

Kiểm tra:
- `php -l` các file PHP đã sửa: pass.
- `php artisan view:cache`: pass.
- `php artisan route:list | rg "profile|password|login|logout|register|forgot"`: xác nhận có `GET /profile`, `PATCH /profile`, `PUT /password`, không có register/forgot public.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Tối ưu đăng nhập, đăng xuất và quản lý tài khoản

File chính:
- `routes/auth.php`
- `routes/web.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Requests/ProfileUpdateRequest.php`
- `resources/views/users/_form.blade.php`
- `resources/views/users/create.blade.php`
- `resources/views/users/edit.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/partials/update-profile-information-form.blade.php`
- `resources/views/profile/partials/update-password-form.blade.php`
- `resources/views/profile/partials/delete-user-form.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Tắt đăng ký tài khoản công khai, quên mật khẩu và reset mật khẩu qua email công khai để phù hợp hệ thống nội bộ và tránh rủi ro email trùng.
- Bỏ chức năng người dùng tự xóa tài khoản; tài khoản chỉ nên được quản trị viên khóa/xóa.
- Việt hóa màn profile và bỏ khối `Delete Account` mặc định của Breeze.
- Email hồ sơ cá nhân chuyển sang không bắt buộc, đồng bộ với cấu hình cho phép email trùng/để trống khi test.
- Mật khẩu khi tạo/reset người dùng tối thiểu 8 ký tự.
- Tài khoản vai trò Trung tâm bắt buộc phải gán Trung tâm phân phối; vai trò khác tự bỏ gán trung tâm.
- Ghi activity log cho các thao tác tạo, cập nhật, xóa, khóa/mở khóa và reset mật khẩu người dùng.

Kiểm tra:
- `php -l` các file PHP đã sửa: pass.
- `php artisan view:cache`: pass.
- `php artisan route:list | rg "register|forgot-password|reset-password|profile.destroy|login|logout|profile"`: không còn route đăng ký/quên mật khẩu/reset public/tự xóa profile.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

## 2026-09-24

### Màn hình đăng nhập - cân lại bố cục 50/50 và nền nhận diện

File chính:
- `resources/views/auth/login.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Chỉnh bố cục desktop về tỉ lệ 50/50 giữa khối thông tin và khối đăng nhập.
- Tăng kích thước card đăng nhập, input, logo và nút đăng nhập để thao tác rõ hơn.
- Giảm độ nặng phần giới thiệu bên trái, chuyển sang nền xanh nhận diện NTP thay vì toàn nền trắng.
- Dùng lại logo NTP hiện có trong hệ thống; dự án hiện không có ảnh nền login riêng ngoài logo/ảnh ISO.
- Giữ responsive mobile: ẩn phần giới thiệu, ưu tiên form đăng nhập.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- Rà chuỗi lỗi font `Ä/Ã` trong file login: không còn kết quả.

### Màn hình đăng nhập - tinh chỉnh theo form mới

File chính:
- `resources/views/auth/login.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Giữ hướng bố cục hero bên trái và form đăng nhập bên phải theo bản chỉnh mới.
- Tinh chỉnh nền, card đăng nhập, logo NTP, input, nút đăng nhập, trạng thái lỗi và responsive mobile.
- Rà lại file login để không còn chuỗi lỗi font tiếng Việt dạng `Ä/Ã`.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Màn hình đăng nhập - thiết kế lại và Việt hóa

File chính:
- `resources/views/auth/login.blade.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Thiết kế lại màn hình đăng nhập theo nhận diện CNCL NTP, dùng logo `public/images/logo.png` thay cho logo Laravel.
- Việt hóa toàn bộ nhãn, nút, ghi nhớ đăng nhập, quên mật khẩu và mô tả trên màn hình login.
- Việt hóa thông báo sai tên đăng nhập/mật khẩu, thiếu tên đăng nhập, thiếu mật khẩu và cảnh báo nhập sai quá nhiều lần.
- Giữ màn hình responsive, trên mobile chỉ hiển thị khối đăng nhập gọn.

Kiểm tra:
- `php -l app/Http/Requests/Auth/LoginRequest.php`: pass.
- `php -l app/Http/Controllers/Auth/AuthenticatedSessionController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### PDF phiếu CNCL - bỏ TIENPHONG năm và PCN khỏi header

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `resources/views/quality_certificates/pdf.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bỏ dòng `TIENPHONG : {năm}` dưới cụm ISO/QUACERT trên mẫu PDF ký số TCPDF.
- Bỏ dòng `PCN: {id}` khỏi header mẫu phiếu.
- Đồng bộ template Blade PDF cũ để không còn hiển thị hai nội dung này khi có fallback.

Kiểm tra:
- Render thử phiếu `309`: PDF sinh thành công, không còn chuỗi `TIENPHONG :` và `PCN:`.
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

## 2026-09-23

### PDF ký số - kéo dòng trang cuối lên lấp trang kế cuối

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Sửa thuật toán `balanceSparseLastPage()` để trang kế cuối được kéo thêm dòng từ trang cuối nếu còn đủ chiều cao.
- Không giới hạn chỉ xử lý trang cuối ít dòng; hệ thống kéo cho đến khi trang kế cuối đầy hoặc trang cuối chỉ còn 1 dòng.
- Trang cuối giữ tối thiểu 1 dòng sản phẩm để vẫn có trang đặt ghi chú/chữ ký số.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### PDF phiếu CNCL - chống cache khi xem lại

File chính:
- `app/Http/Controllers/QualityCertificateController.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung header `Cache-Control: no-store, no-cache` cho response PDF phiếu CNCL.
- Tránh trình duyệt/PDF viewer giữ bản PDF cũ khi mở lại cùng URL sau khi cập nhật layout PDF.

Kiểm tra:
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### PDF ký số - cân bằng trang cuối ít dòng

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung bước `balanceSparseLastPage()` sau khi phân trang.
- Nếu trang cuối chỉ còn 2-3 dòng và trang kế cuối còn đủ sức chứa, hệ thống sẽ chuyển bớt dòng đầu của trang cuối lên cuối trang kế cuối.
- Luôn giữ ít nhất 1 dòng ở trang cuối để còn vùng ghi chú/chữ ký số, tránh biến trang kế cuối thành trang cuối rồi thiếu chỗ ký.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Mô phỏng phiếu `306`: dữ liệu local hiện có trang cuối 4 dòng nên không di chuyển; logic mới sẽ áp dụng khi trang cuối chỉ có 2-3 dòng và trang trước còn chỗ.
- Mô phỏng 25 phiếu cần chừa vùng ký: `risk_count=0`.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- `php artisan view:cache`: pass.

## 2026-09-22

### PDF ký số - tận dụng khoảng trống các trang không phải trang cuối

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Tăng giới hạn đáy bảng cho các trang không phải trang cuối từ `690pt` lên `715pt`.
- Mục tiêu là kéo thêm dòng sản phẩm xuống các trang trước, giảm khoảng trắng lớn trước dòng "Còn tiếp trang sau".
- Kéo dòng "Còn tiếp trang sau" xuống `725pt` và số trang xuống `742pt` để không chồng với bảng khi trang thường tận dụng thêm chiều cao.
- Giữ nguyên giới hạn trang cuối có chữ ký `560pt` để vẫn chừa vùng ghi chú và ký số an toàn.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Mô phỏng 25 phiếu cần chừa vùng ký: `risk_count=0`, `max_table_end=713`.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- `php artisan view:cache`: pass.

### Yêu cầu cấp phiếu - thêm STT dòng sản phẩm

File chính:
- `resources/views/certificate_requests/_form.blade.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Thêm cột `STT` vào bảng danh sách sản phẩm đề nghị cấp phiếu.
- STT chỉ hiển thị trên giao diện, không gửi thêm dữ liệu lên server và không thay đổi cấu trúc database.
- Bổ sung JS tự đánh lại STT sau khi thêm dòng, xóa dòng, import Excel hoặc dán từ Excel.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### PDF ký số - đưa ghi chú sát dưới bảng trang cuối

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Sửa vị trí ghi chú trong PDF ký số để luôn nằm ngay dưới đáy bảng trang cuối.
- Bỏ mốc tối thiểu `570pt` vốn làm ghi chú bị cách xa bảng khi bảng trang cuối ít dòng.
- Giữ nguyên giới hạn đáy bảng trang cuối để vẫn chừa vùng ký số.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Mô phỏng 25 phiếu cần chừa vùng ký: `risk_count=0`, ghi chú nằm ngay sau bảng.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- `php artisan view:cache`: pass.

### PDF ký số - đồng bộ layout xem trước với layout gửi ký

File chính:
- `app/Models/QualityCertificate.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung `shouldReserveSignatureSpaceForPdf()` để xác định phiếu chưa ký nhưng sẽ/đang ký số cần chừa vùng ký khi xem PDF.
- URL `/quality-certificates/{id}/pdf` dùng layout chừa vùng ký cho các trạng thái nháp cũ/chờ trưởng PTN duyệt/chờ gửi ký/đang chờ ký/quá hạn ký.
- Tránh trường hợp màn xem trước hiện `3/3` nhưng file gửi ký đúng ra phải tách sang `4/4`.

Kiểm tra:
- `php -l app/Models/QualityCertificate.php`: pass.
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- Mô phỏng 25 phiếu cần chừa vùng ký theo logic route `/pdf`: `risk_count=0`.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- `php artisan view:cache`: pass.

### PDF ký số - sửa ghi chú bị đè lên bảng sản phẩm

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Sửa `drawNote()` để ghi chú luôn nằm sau điểm kết thúc bảng, không bị ép ngược lên `y=600`.
- Giảm giới hạn đáy bảng trang cuối không có chữ ký từ `694pt` xuống `650pt` để phần ghi chú có vùng an toàn trước số trang/footer.
- Giữ trang cuối có chữ ký ở giới hạn `560pt`.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Mô phỏng 32 phiếu chưa ký: `risk_count=0`, ghi chú luôn nằm sau bảng và bảng không vượt vùng chữ ký.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- `php artisan view:cache`: pass.

### PDF ký số - chừa vùng chữ ký khi gửi SmartCA

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Bổ sung tham số chừa vùng chữ ký khi render PDF.
- Luồng gửi ký VNPT SmartCA dùng PDF chưa vẽ chữ ký nội bộ nhưng vẫn phân trang theo vùng an toàn của trang cuối.
- Tránh trường hợp bảng sản phẩm ở trang cuối kéo xuống đè vùng chữ ký SmartCA với phiếu nhiều dòng/dòng dài.
- Hạ giới hạn đáy bảng trang cuối có chữ ký xuống `560pt` để phần ghi chú kết thúc trước vùng chữ ký SmartCA hiện tại.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- Mô phỏng 32 phiếu chưa ký với chế độ chừa vùng chữ ký: `risk_count=0`.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- `php artisan view:cache`: pass.

### SmartCA - giảm chu kỳ job kiểm tra ký tự động

File chính:
- `routes/console.php`
- `CHANGELOG_CNCL.md`

Nội dung:
- Đổi lịch `smartca:check-pending-signatures` từ mỗi 1 phút sang mỗi 15 giây.
- Giảm giới hạn mỗi lượt từ 30 phiếu xuống 10 phiếu để tránh tăng tải API VNPT SmartCA và database đột ngột.
- Giữ `withoutOverlapping()` để tránh chạy chồng job khi lượt trước chưa hoàn tất.

Kiểm tra:
- `php -l routes/console.php`: pass.
- `php artisan schedule:list`: hiển thị `15s php artisan smartca:check-pending-signatures --limit=10`.

### Tài liệu - hướng dẫn cài đặt VPS

File chính:
- `docs/HUONG_DAN_CAI_DAT_VPS.md`
- `CHANGELOG_CNCL.md`

Nội dung:
- Tạo tài liệu triển khai hệ thống CNCL trên VPS Ubuntu/Nginx/PHP-FPM/MySQL.
- Bổ sung hướng dẫn cấu hình `.env`, VNPT SmartCA test/production, queue worker, scheduler, backup, deploy và checklist kiểm tra sau cài đặt.

Kiểm tra:
- Tài liệu được tạo mới, không thay đổi logic chương trình.

### SmartCA - sửa endpoint production và xác nhận gửi ký

File chính:
- `.env`
- `.env.example`
- `app/Http/Controllers/QualityCertificateController.php`

Nội dung:
- Sửa `SMARTCA_PRODUCTION_BASE_URL` từ UAT `rmgateway.vnptit.vn` sang Production `https://gwsca.vnpt.vn/sca/sp769` theo tài liệu VNPT SmartCA v4.1.
- Sửa `SMARTCA_USER_ID_FIELD` về `smartca_user_id`; CCCD/MST người ký nằm ở `SMARTCA_PRODUCTION_DEFAULT_USER_ID`.
- `.env.example` cũng cập nhật endpoint production mẫu.
- Lưu thêm `environment` vào các nhóm dữ liệu API `get_certificate`, `calculate_hash`, `sign` cho các giao dịch SmartCA mới.
- Đã gọi thử `get_certificate` production thành công cho serial chính thức.
- Đã gửi thử yêu cầu ký phiếu test `TEST-SLA-CNCL-TH-0011` thành công; trạng thái chuyển sang `SIGN_PENDING`, `smartca_status=PENDING`.

Kiểm tra:
- `php artisan config:clear`: pass.
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### SmartCA - bổ sung ghi chú trong `.env.example`

File chính:
- `.env.example`

Nội dung:
- Đồng bộ chú thích SmartCA từ `.env` sang `.env.example`.
- Giữ trống các trường key/secret/serial trong file mẫu, chỉ để sẵn endpoint test.
- Tạo backup `.env.example.smartca-comment-backup-*`.

Kiểm tra:
- `php artisan config:clear`: pass.

### SmartCA - bổ sung ghi chú trong `.env`

File chính:
- `.env`

Nội dung:
- Thêm chú thích cho `SMARTCA_ENV`, nhóm cấu hình `SMARTCA_TEST_*`, `SMARTCA_PRODUCTION_*`, fallback legacy và các tùy chọn PAdES.
- Sửa lại định dạng `.env` sau khi thêm comment để bảo đảm các dòng vẫn đúng dạng `KEY=value`.
- Tạo backup `.env.smartca-comment-backup-*` và `.env.smartca-comment-repair-backup-*`.

Kiểm tra:
- `php artisan config:clear`: pass.
- `php artisan tinker --execute`: xác nhận SmartCA đang ở `production` và đủ base/client/secret/serial.

### SmartCA - chuyển `.env` sang môi trường production

File chính:
- `.env`

Nội dung:
- Đặt `SMARTCA_ENV=production`.
- Bổ sung nhóm `SMARTCA_TEST_*` để giữ lại cấu hình test.
- Bổ sung nhóm `SMARTCA_PRODUCTION_*` để hệ thống dùng khi ký số chính thức.
- Tạo backup `.env.smartca-backup-*`, `.env.smartca-profile2-backup-*`, `.env.smartca-repair-backup-*` trong quá trình chuyển đổi.

Kiểm tra:
- `php artisan config:clear`: pass.
- `php artisan tinker --execute`: xác nhận `services.smartca.env = production` và các trường base/client/secret/serial đều có giá trị.

### SmartCA - tách cấu hình test và chính thức

File chính:
- `config/services.php`
- `app/Services/SmartCaService.php`
- `.env.example`

Nội dung:
- Bổ sung `SMARTCA_ENV=test|production` để chọn môi trường ký số.
- Bổ sung nhóm biến `SMARTCA_TEST_*` và `SMARTCA_PRODUCTION_*` để giữ song song cấu hình test và chính thức.
- Vẫn giữ fallback cấu hình cũ `SMARTCA_BASE_URL`, `SMARTCA_CLIENT_ID`, `SMARTCA_CLIENT_SECRET`, `SMARTCA_SERIAL_NUMBER`.
- Lưu thêm `environment` trong dữ liệu API SmartCA để biết giao dịch gửi qua test hay production.

Kiểm tra:
- `php -l config/services.php`: pass.
- `php -l app/Services/SmartCaService.php`: pass.
- `php artisan config:clear`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### DVKH - tối ưu lọc và nhận diện yêu cầu cần xử lý

File chính:
- `app/Http/Controllers/DvkhRequestController.php`
- `resources/views/dvkh_requests/index.blade.php`

Nội dung:
- Bổ sung bộ lọc khoảng ngày `Gửi DVKH từ ngày` và `Gửi DVKH đến ngày`.
- Các chỉ số đầu trang bám theo bộ lọc trung tâm và khoảng ngày gửi DVKH.
- Làm nổi bật dòng yêu cầu gấp, yêu cầu gần/quá hạn SLA và yêu cầu bị PTN/Trưởng PTN trả lại về DVKH.
- Sửa nút `Xóa lọc` nhận đủ các tham số mới và tham số `returned`.
- Sắp xếp bảng rõ hơn theo mốc gửi DVKH khi không chọn sort thủ công.

Kiểm tra:
- `php -l app/Http/Controllers/DvkhRequestController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### DVKH - hiển thị ngày Trung tâm gửi yêu cầu

File chính:
- `app/Http/Controllers/DvkhRequestController.php`
- `resources/views/dvkh_requests/index.blade.php`

Nội dung:
- Bổ sung cột `Ngày gửi DVKH` trên danh sách yêu cầu DVKH, lấy theo `submitted_at`.
- Hiển thị thêm người gửi yêu cầu và đổi nhãn ngày tạo trong cột số yêu cầu cho rõ nghĩa.
- Với yêu cầu bị PTN/Trưởng PTN trả lại về DVKH, đổi nhãn thời gian thành `Nhận lại`.
- Thêm sort theo `submitted_at` và đổi thứ tự mặc định sang ưu tiên yêu cầu gửi sang DVKH cũ nhất.

Kiểm tra:
- `php -l app/Http/Controllers/DvkhRequestController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Danh sách phiếu - sửa bộ lọc tất cả trạng thái trên Select2

File chính:
- `resources/views/quality_certificates/index.blade.php`
- `app/Http/Controllers/QualityCertificateController.php`

Nội dung:
- Đổi lựa chọn `Tất cả trạng thái` từ giá trị rỗng sang `ALL` để Select2 hiển thị như một lựa chọn thật.
- Controller quy đổi `status=ALL` thành không lọc trạng thái, giữ đúng dữ liệu toàn bộ phiếu.

Kiểm tra:
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Danh sách phiếu - bổ sung trạng thái ký tất cả

File chính:
- `resources/views/quality_certificates/index.blade.php`

Nội dung:
- Đưa lựa chọn `Tất cả trạng thái` lên đầu bộ lọc `Trạng thái ký`.
- Set selected rõ ràng khi không truyền tham số `status`, tránh select2 tự hiển thị mặc định `Chờ duyệt / chờ gửi ký`.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### PTN - hiển thị rõ yêu cầu bị Trưởng PTN trả lại

File chính:
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/ptn_requests/index.blade.php`

Nội dung:
- Màn `/ptn/requests` mặc định hiển thị cả yêu cầu `WAIT_PTN` và yêu cầu `PTN_PROCESSING` có `last_returned_to = PTN`, để PTN nhìn thấy ngay các phiếu bị Trưởng PTN trả lại.
- Dòng bị Trưởng PTN trả lại được tô nền vàng nhạt và có vạch nhấn bên trái.
- Bổ sung badge “Trưởng PTN trả lại” ngay dưới số yêu cầu, ngoài badge chi tiết ở cột trạng thái.
- Nút “Xóa lọc” nhận cả filter `returned`.

Kiểm tra:
- `php -l app/Http/Controllers/PtnRequestController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass, 23 tests.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Data test - cập nhật theo luồng nghiệp vụ và SLA mới

File chính:
- `database/seeders/WorkflowReportTestDataSeeder.php`

Nội dung:
- Cập nhật dữ liệu test nút thêm/xóa data test để bao phủ các thay đổi mới nhất.
- Mỗi trung tâm có 15 yêu cầu gồm: nháp cũ chưa gửi DVKH, DVKH trả lại trung tâm, chờ DVKH bình thường/gần quá hạn/quá hạn, PTN trả lại DVKH, chờ PTN bình thường/gần quá hạn/quá hạn, chờ Trưởng PTN duyệt, chờ gửi ký số, SmartCA pending còn hạn/quá hạn, Trưởng PTN trả lại PTN.
- Bổ sung dữ liệu `submitted_at`, `sent_to_ptn_at`, `last_returned_at` để test đúng logic SLA mới thay vì tính theo ngày tạo nháp.
- Vẫn tạo thêm 5 phiếu đã ký số nhiều sản phẩm để test PDF, in ký tươi/in lại và báo cáo.
- Dọn thêm thông báo/log test khi seed lại để tránh dữ liệu cũ gây nhiễu.

Kiểm tra:
- `php -l database/seeders/WorkflowReportTestDataSeeder.php`: pass.
- `php artisan db:seed --class=WorkflowReportTestDataSeeder --force`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass, 23 tests.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.
- Kiểm tra nhanh dữ liệu sau seed: 85 yêu cầu test, gồm 10 nháp, 20 chờ DVKH, 15 chờ PTN, 30 PTN xử lý, 5 hoàn tất; phiếu test gồm READY_TO_SIGN, PENDING, REJECTED, WAIT_PTN_MANAGER_APPROVAL và ISSUED/SIGNED.

### SLA - tính từ thời điểm bắt đầu từng bước thay vì ngày tạo bản nháp

File chính:
- `app/Services/SlaClockService.php`
- `app/Models/CertificateRequest.php`
- `app/Http/Controllers/DvkhRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Exports/CertificateSummaryExport.php`
- `database/migrations/2026_09_22_000002_add_sent_to_ptn_at_to_certificate_requests.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Tách logic tính SLA vào `SlaClockService` để DVKH, PTN, dashboard, báo cáo và export dùng cùng một công thức.
- DVKH SLA tính từ `submitted_at`; nếu PTN/Trưởng PTN trả lại DVKH thì tính lại từ `last_returned_at`.
- PTN SLA tính từ mốc mới `sent_to_ptn_at`; nếu Trưởng PTN trả lại PTN xử lý lại thì tính lại từ `last_returned_at`.
- Thêm cột `sent_to_ptn_at` và ghi mốc này khi DVKH xác nhận chuyển yêu cầu sang PTN.
- Bổ sung test tự động cho lỗi: yêu cầu tạo nháp nhiều ngày trước nhưng mới gửi DVKH hôm nay không được báo quá hạn SLA.

Kiểm tra:
- `php artisan migrate`: pass.
- `php -l` các file PHP đã chỉnh: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass, 23 tests.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

## 2026-09-16

### Danh sách dữ liệu - bổ sung sắp xếp nhanh trên header bảng

File chính:
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/ProductGroupController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/QualityStandardController.php`
- `app/Http/Controllers/DistributionCenterController.php`
- `app/Http/Controllers/UrgentReasonController.php`
- `app/Http/Controllers/SlaConfigController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/DvkhRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/partials/sort_link.blade.php`
- Các màn index danh mục/nghiệp vụ tương ứng.

Nội dung:
- Tạo helper `sortInput()` dùng chung để nhận `sort`/`direction` an toàn bằng whitelist.
- Tạo partial `partials.sort_link` để bấm vào header cột và đảo chiều tăng/giảm.
- Bổ sung sort nhanh cho các màn: trung tâm phân phối, nhóm sản phẩm, sản phẩm, khách hàng - công trình, tiêu chuẩn chất lượng, lý do gấp, cấu hình SLA, người dùng, DVKH kiểm tra, PTN lập phiếu, danh sách phiếu CNCL.
- Giữ nguyên bộ lọc hiện tại khi đổi sắp xếp và tự bỏ `page` để tránh nhảy vào trang phân trang không còn phù hợp.
- Với các màn DVKH/PTN, nếu chưa bấm sort vẫn giữ thứ tự ưu tiên nghiệp vụ cũ: trạng thái cần xử lý, yêu cầu gấp, thời gian tạo.

Kiểm tra:
- `php -l` các controller đã chỉnh: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass, 21 tests.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass, 6 tests.

### Yêu cầu cấp phiếu - bắt buộc số bản ký tươi từ 1 khi đã chọn ký tươi

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/certificate_requests/_form.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Khi người dùng bật `Yêu cầu ký tươi`, hệ thống bắt buộc `Số bản ký tươi` phải là số nguyên từ 1 trở lên.
- Khi không bật ký tươi, `hard_copy_quantity = 0` vẫn hợp lệ và không bị rule `min:1` chặn nhầm.
- Form tự đồng bộ ô số bản: bật ký tươi thì bắt buộc và tự đưa giá trị tối thiểu về 1; tắt ký tươi thì cho phép 0.
- Ô nhập `Số bản ký tươi` chỉ hiển thị khi đã bật `Yêu cầu ký tươi`, tránh gây hiểu nhầm khi chưa chọn ký tươi.
- Bổ sung test tự động để chặn trường hợp tick ký tươi nhưng nhập 0 bản.

Kiểm tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`: pass.
- `php -l app/Http/Controllers/PtnRequestController.php`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass, 21 tests.

### PTN trả lại DVKH sau khi Trưởng PTN trả phiếu về PTN

File chính:
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/ptn_requests/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Chuẩn hóa nghiệp vụ: nếu Trưởng PTN trả phiếu về `PTN xử lý lại`, yêu cầu ở trạng thái `PTN_PROCESSING` vẫn được PTN trả tiếp về DVKH khi lỗi nằm ngoài phạm vi PTN.
- Nút `Trả lại DVKH` trên màn `/ptn/requests/{id}` nay hiển thị khi yêu cầu đang `WAIT_PTN` hoặc khi có phiếu `REJECTED` với `rejected_to = PTN` và chưa có phiếu CNCL hiệu lực mới.
- Backend `ptn.requests.return-to-dvkh` dùng chung điều kiện `canReturnToDvkh()` để không phụ thuộc riêng trạng thái `WAIT_PTN`.
- Bổ sung test tự động cho ca: Trung tâm gửi yêu cầu -> DVKH duyệt -> PTN lập phiếu -> Trưởng PTN trả về PTN -> PTN trả lại DVKH.

Kiểm tra:
- `php -l app/Http/Controllers/PtnRequestController.php`: pass.
- `php -l tests/Feature/CertificateWorkflowTest.php`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass, 20 tests.

### PTN chi tiết yêu cầu - bổ sung tiến trình xử lý

File chính:
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/ptn_requests/show.blade.php`

Nội dung:
- Màn chi tiết PTN `/ptn/requests/{id}` nay dùng `WorkflowStepService` để lấy tiến trình xử lý giống các màn yêu cầu/DVKH/phiếu CNCL.
- Bổ sung partial `quality_certificates.partials.workflow_steps` vào đầu màn chi tiết PTN.
- Kiểm tra thử yêu cầu `332`: trả đủ 5 bước, bước hiện tại là `PTN lập phiếu`.

Kiểm tra:
- `php -l app/Http/Controllers/PtnRequestController.php`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Tiến trình xử lý - tách service dùng chung và chuẩn hóa 5 bước

File chính:
- `app/Services/WorkflowStepService.php`
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/DvkhRequestController.php`
- `app/Http/Controllers/QualityCertificateController.php`

Nội dung:
- Tạo `WorkflowStepService` làm nguồn logic duy nhất cho phần `Tiến trình xử lý`.
- Chuẩn hóa tiến trình thành 5 bước trên các màn: tạo yêu cầu, DVKH kiểm tra, PTN lập phiếu, Trưởng PTN duyệt/ký số, phát hành/thu hồi.
- Thay màn chi tiết yêu cầu, màn DVKH và màn chi tiết phiếu CNCL sang dùng service chung.
- Xử lý rõ hơn các trạng thái: nháp, chờ DVKH, chờ PTN, chờ Trưởng PTN duyệt, chờ gửi ký, đang chờ app ký, quá hạn ký, trưởng PTN trả lại, đã phát hành, đã thu hồi.
- Luồng PTN lập trực tiếp sẽ bỏ qua bước DVKH thay vì hiển thị nhầm là DVKH đã xác nhận.
- Xóa các hàm dựng tiến trình cũ trong controller để tránh lệch logic về sau.

Kiểm tra:
- `php -l app/Services/WorkflowStepService.php`: pass.
- `php -l app/Http/Controllers/CertificateRequestController.php`: pass.
- `php -l app/Http/Controllers/DvkhRequestController.php`: pass.
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- Gọi thử service với yêu cầu `330`, `253`, `153` và phiếu `248`, `238`, `146`: pass, đều trả 5 bước.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### DVKH chi tiết yêu cầu - hiển thị đầy đủ nội dung cam kết nhận/lấy hàng

File chính:
- `resources/views/dvkh_requests/show.blade.php`

Nội dung:
- Bổ sung khung nội dung cam kết ngay dưới trạng thái `Cam kết nhận/lấy hàng` trên màn chi tiết DVKH.
- Nội dung hiển thị nguyên văn: `Chúng tôi cam kết đã nhận và lấy hàng trong yêu cầu cấp phiếu này tại Công ty...`.
- Giữ badge `Đã xác nhận` / `Chưa xác nhận` để DVKH vẫn nhìn nhanh được trạng thái tick cam kết.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.

### Yêu cầu cấp phiếu - bắt buộc công trình và tên người tạo yêu cầu

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Http/Controllers/CustomerController.php`
- `resources/views/certificate_requests/_form.blade.php`
- `resources/views/customers/_form.blade.php`

Nội dung:
- Khi nhập khách hàng mới trong màn tạo/sửa yêu cầu cấp phiếu, bắt buộc nhập `Tên công trình` và `Địa điểm công trình`.
- Khi tạo/sửa yêu cầu cấp phiếu, bắt buộc nhập `Tên người tạo yêu cầu`.
- Danh mục khách hàng - công trình cũng bắt buộc `Tên công trình` và `Địa điểm công trình`.
- Luồng PTN lập phiếu trực tiếp dùng chung form cũng được áp dụng ràng buộc tương ứng.
- Form tự bật/tắt `required` cho các ô khách hàng mới theo lựa chọn `Chọn khách hàng có sẵn` hoặc `Nhập khách hàng mới`, tránh lỗi trình duyệt chặn submit khi khối nhập mới đang ẩn.

Kiểm tra:
- `php -l app/Http/Controllers/CertificateRequestController.php`: pass.
- `php -l app/Http/Controllers/CustomerController.php`: pass.
- `php -l app/Http/Controllers/PtnRequestController.php`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Phiếu in ký tươi - bỏ kẻ dòng trống dưới bảng sản phẩm

File chính:
- `app/Services/HardCopyCertificatePdfService.php`

Nội dung:
- Bỏ logic tự kẻ thêm tối đa 3 dòng trống bên dưới danh sách sản phẩm.
- Phiếu in lại/in ký tươi giờ chỉ vẽ các dòng sản phẩm thật, phù hợp hơn với giấy phôi in sẵn.
- Giữ nguyên vùng ghi chú, số trang và tên người ký cố định theo phôi.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`: pass.
- Render thử phiếu `146`: pass, PDF 1 trang.
- Render thử phiếu `238`: pass, PDF 13 trang.
- Render thử phiếu `248`: pass, PDF 15 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số TCPDF - tách vùng "Còn tiếp trang sau" và số trang khỏi bảng sản phẩm

File chính:
- `app/Services/SignedCertificatePdfService.php`

Nội dung:
- Giảm giới hạn đáy bảng ở các trang chưa phải trang cuối từ `724pt` xuống `690pt`.
- Thêm tọa độ riêng cho dòng `Còn tiếp trang sau` tại `702pt`.
- Thêm tọa độ riêng cho số trang tại `722pt`.
- Mục tiêu là tránh dòng tiếp trang và số trang bị chồng vào vùng bảng sản phẩm khi phiếu có nhiều dòng hoặc tên sản phẩm dài.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Render thử phiếu `248`: pass, PDF 11 trang.
- Render thử phiếu `234`: pass, PDF 8 trang.
- Render thử phiếu `210`: pass, PDF 10 trang.
- Render thử phiếu `238`: pass, PDF 10 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số TCPDF - bổ sung dòng TIENPHONG theo năm dưới ảnh ISO/QUACERT

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `public/images/quacert-jas-anz-iso.png`

Nội dung:
- Khi dùng ảnh PNG ISO/QUACERT chính thức, hệ thống vẫn tự vẽ thêm dòng `TIENPHONG : {năm hiện tại}` bên dưới ảnh để không bị mất thông tin `TIENPHONG: năm`.
- Giảm chiều cao vùng ảnh ISO xuống `58pt` để chừa khoảng cho dòng năm và mã `PCN`.
- Căn lại vị trí `PCN` thấp hơn để tránh chồng lên dòng `TIENPHONG : năm`.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Render thử phiếu `248`: pass, PDF 10 trang.
- Render thử phiếu `234`: pass, PDF 7 trang.
- Render thử phiếu `210`: pass, PDF 9 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

## 2026-09-14

### PDF ký số TCPDF - dùng ảnh ISO/QUACERT và thêm chú thích căn chỉnh

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `public/images/quacert-jas-anz-iso.svg`

Nội dung:
- Thêm ảnh vector `quacert-jas-anz-iso.svg` làm ảnh ISO/QUACERT/JAS-ANZ ở header.
- Service ưu tiên dùng `public/images/quacert-jas-anz-iso.png` nếu có, để sau này thay bằng ảnh PNG chính thức dễ dàng.
- Thêm chú thích trong code về đơn vị `point`, cách chỉnh X/Y/kích thước logo, ảnh ISO, footer, vùng bảng và vùng chữ ký.
- Đưa footer xanh sát đáy A4 hơn bằng cách đặt `FOOTER_BAND_Y + FOOTER_BAND_H` gần đúng chiều cao trang A4.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Render thử phiếu `248`: pass, PDF 10 trang.
- Render thử phiếu `234`: pass, PDF 7 trang.
- Render thử phiếu `210`: pass, PDF 9 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số TCPDF - giảm cỡ header/footer theo đúng đơn vị point

File chính:
- `app/Services/SignedCertificatePdfService.php`

Nội dung:
- Giảm cỡ chữ header vì TCPDF dùng `pt`, không tương đương trực tiếp với `px` của template Dompdf cũ.
- Giảm logo từ `108pt` về `82pt` để sát kích thước render cũ hơn.
- Giảm font khối ISO/PCN và căn lại PCN để không đè chữ.
- Giảm chiều cao footer xanh từ `106pt` về `78pt`.
- Giảm font trong footer về khoảng `7.9-8.3pt`, tránh các dòng địa chỉ bị chồng lên nhau.
- Đưa footer xanh xuống thấp hơn để giống mẫu cũ và không chiếm quá nhiều chiều cao trang.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Render thử phiếu `248`: pass, PDF 11 trang.
- Render thử phiếu `234`: pass, PDF 7 trang.
- Render thử phiếu `210`: pass, PDF 10 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số TCPDF - căn lại header/footer theo mẫu cũ

File chính:
- `app/Services/SignedCertificatePdfService.php`

Nội dung:
- Căn lại logo lên `108pt`, tương ứng kích thước trong template PDF cũ.
- Căn lại vùng tên công ty, tên tiếng Anh và khối ISO/PCN theo tỷ lệ header cũ.
- Đưa footer xanh sát đáy A4 hơn, website nằm ngay phía trên khung xanh.
- Tách chữ footer thành nhãn đậm và nội dung thường để giống mẫu cũ hơn.
- Giữ cơ chế render TCPDF để tránh lỗi trang trắng của Dompdf.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- Render thử phiếu `248`: pass, PDF 11 trang.
- Render thử phiếu `234`: pass, PDF 8 trang.
- Render thử phiếu `210`: pass, PDF 10 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số - chuyển render từ Dompdf sang TCPDF

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `app/Mail/QualityCertificateIssuedMail.php`

Nội dung:
- Tạo service TCPDF riêng cho PDF phiếu CNCL ký số để tự kiểm soát phân trang, tránh Dompdf sinh trang trắng xen giữa.
- Route xem PDF chưa có file ký lưu sẵn chuyển sang render bằng `SignedCertificatePdfService`.
- Luồng gửi ký VNPT SmartCA chuyển sang lấy PDF gốc từ TCPDF trước khi calculate hash/PAdES.
- Mail fallback khi chưa có file PDF đã ký lưu sẵn cũng dùng TCPDF.
- TCPDF tự đo chiều cao dòng sản phẩm bằng `getStringHeight()`, phân trang theo chiều cao thật thay vì ước lượng unit của Blade/Dompdf.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php -l app/Mail/QualityCertificateIssuedMail.php`: pass.
- Render thử phiếu giả 55 dòng sản phẩm bằng TCPDF: pass, PDF 9 trang.
- Render thử các phiếu từng lỗi `210`, `234`, `248`, `242`, `238`, `216`, `227`, `229`: pass.
- Quét 40 phiếu gần nhất có dữ liệu sản phẩm: pass, 40/40 render thành công, tổng 297 trang, không lỗi runtime.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số - kiểm tra nguyên nhân trang trắng xen giữa

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Quét thử 40 phiếu gần nhất có dữ liệu sản phẩm để so sánh số trang logic với số trang Dompdf render thực tế.
- Xác định nguyên nhân chính: Dompdf render chiều cao thực tế của một số trang lớn hơn ước lượng, nên sinh thêm trang phụ dù logic không tạo trang rỗng.
- Bỏ kẻ dòng trống tự động ở các trang không phải trang cuối để giảm nguy cơ tràn giả.
- Chuyển khối footer/electronic-trace cố định lên trước nội dung trang, tránh để fixed element nằm cuối body.
- Giữ cấu hình an toàn nhất đã test: trang thường dùng công thức buffer `3`, trang cuối giữ `10` unit.

Kiểm tra:
- `php -l resources/views/quality_certificates/pdf.blade.php`: pass.
- Quét 40 phiếu gần nhất: còn 2 phiếu có Dompdf render nhiều hơn logic 1 trang (`234`, `210`), nguyên nhân còn lại là sai số render thực tế của Dompdf.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số - đổi sức chứa trang thường sang công thức có buffer

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Thay cách tính trang thường từ `21 - customerInfoPenalty` sang công thức rõ ràng:
  `basePageCapacity - customerInfoPenalty - footerPenalty - safetyBuffer`.
- Đặt `basePageCapacity = 21`, `footerPenalty = 1`, `safetyBuffer = 1`, giới hạn thấp nhất `17`.
- Mục tiêu là chừa vùng thực tế cho dòng tiếp trang/số trang và sai số render của Dompdf, giảm nguy cơ sinh trang trắng.
- Giữ trang cuối `10` unit để bảo vệ vùng chữ ký điện tử.

Kiểm tra:
- `php -l resources/views/quality_certificates/pdf.blade.php`: pass.
- Render thử phiếu `146`: pass, PDF 2 trang.
- Render thử phiếu `150`: pass, PDF 4 trang.
- Render thử phiếu `238`: pass, PDF 11 trang.
- Render thử phiếu `248`: pass, PDF 13 trang, số trang logic khớp số trang Dompdf.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số - cân bằng phân trang theo thông tin khách hàng

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Thêm ước lượng độ dài phần thông tin khách hàng/công trình/địa điểm để tự giảm sức chứa bảng khi phần đầu phiếu dài.
- Giữ sức chứa nền của trang thường là `21` unit, nhưng tự giảm về tối thiểu `18` unit nếu thông tin khách hàng dài.
- Sửa logic tách trang cuối: nếu trang cuối vượt vùng chữ ký thì chuyển đủ số dòng sang trang mới, không chỉ chuyển 1 dòng như trước.
- Thêm bước cân bằng để hạn chế trang kế tiếp/trang cuối chỉ có 1 dòng sản phẩm khi còn có thể kéo thêm dòng từ trang trước.

Kiểm tra:
- `php -l resources/views/quality_certificates/pdf.blade.php`: pass.
- Render thử phiếu `146`: pass, logic chia dữ liệu 5 dòng + 2 dòng.
- Render thử phiếu `150`: pass, logic chia dữ liệu 9 + 9 + 5 + 3 dòng.
- Render thử phiếu `238`: pass, không còn trang logic 1 dòng.
- Render thử phiếu `248`: pass, không còn trang logic 1 dòng.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số - tăng dòng bảng trên các trang không phải trang cuối

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Tăng sức chứa ước lượng của các trang thường từ `17` lên `19` unit để giảm khoảng trắng dưới bảng.
- Giữ trang cuối ở `10` unit để vẫn chừa vùng chữ ký điện tử phía trên footer xanh.
- Không đổi logic trang cuối, tránh bảng sản phẩm che chữ ký số.

Kiểm tra:
- `php -l resources/views/quality_certificates/pdf.blade.php`: pass.
- Render thử phiếu `238`: pass, PDF giảm từ 12 còn 11 trang.
- Render thử phiếu `248`: pass, PDF giảm từ 14 còn 13 trang.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF ký số - tăng cỡ chữ theo cỡ Excel và cân lại phân trang

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Tăng số phiếu, phần thông tin `1.`, `2.`, `3.` và thông tin khách hàng lên `13pt`.
- Tăng tiêu đề/header bảng và dữ liệu bảng sản phẩm lên `12pt`.
- Giữ tiêu đề `PHIẾU CHỨNG NHẬN CHẤT LƯỢNG` ở `18pt` để đủ nổi bật nhưng không làm vỡ bố cục A4.
- Thêm xử lý ngắt chữ tại các ký tự `/`, `&`, `;`, `,`, `:`, `-` để tiêu chuẩn dài không lồi khỏi ô.
- Giảm sức chứa ước lượng của trang thường từ `22` xuống `17` unit và trang cuối từ `14` xuống `10` unit để chừa vùng chữ ký/footer khi font lớn hơn.

Kiểm tra:
- `php -l resources/views/quality_certificates/pdf.blade.php`: pass.
- Render thử phiếu `238`: pass, PDF 12 trang.
- Render thử phiếu `248`: pass, PDF 14 trang.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - căn lại số mục thông tin với nhãn

File chính:
- `app/Services/HardCopyCertificatePdfService.php`

Nội dung:
- Đổi phần vẽ số `1.`, `2.`, `3.` về cùng cơ chế `MultiCell()` với nhãn và giá trị.
- Giữ cột số thứ tự rộng `20pt` để dấu chấm không bị xuống dòng.
- Đặt `valign = T` cho số, nhãn và giá trị để cùng bám đỉnh dòng, tránh lệch baseline giữa `Cell()` và `MultiCell()`.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`: pass.
- Render thử phiếu `238`: pass, PDF 13 trang.
- `php artisan test --filter=hard_copy_print_returns_tcpdf_pdf`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.

### PDF in ký tươi - sửa số mục thông tin bị tách dấu chấm

File chính:
- `app/Services/HardCopyCertificatePdfService.php`

Nội dung:
- Tăng cột số thứ tự phần thông tin khách hàng từ `14pt` lên `20pt`.
- Giảm cột nhãn từ `100pt` xuống `94pt` để tổng chiều rộng không đổi.
- Đổi cách vẽ số `1.`, `2.`, `3.` từ `MultiCell()` sang `Cell()` để TCPDF không tự wrap dấu chấm xuống dòng.
- Đồng bộ lại phần tính chiều cao dry-run với kích thước cột mới.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`: pass.
- Render thử phiếu `238`: pass, PDF 13 trang.
- `php artisan test --filter=hard_copy_print_returns_tcpdf_pdf`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.

### Chữ ký số PDF - thu nhỏ dấu tích xanh và đồng bộ màu footer

File chính:
- `app/Services/SmartCaService.php`
- `resources/views/system_settings/index.blade.php`

Nội dung:
- Giảm kích thước dấu tích xanh trong ảnh chữ ký số từ `118px` xuống `86px`.
- Giảm độ dày nét dấu tích để không tràn khỏi khung chữ ký.
- Đổi màu dấu tích sang `#77BF35`, cùng màu với khung footer xanh của mẫu phiếu CNCL.
- Căn lại vị trí dấu tích trong canvas để chữ vẫn nằm phía trên và dấu không vượt khung.
- Đồng bộ preview màn cấu hình chữ ký số với màu/kích thước mới.

Kiểm tra:
- `php -l app/Services/SmartCaService.php`: pass.
- Tạo thử ảnh chữ ký có dấu tích: pass, PNG `260x96`.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - sửa lỗi TCPDF dùng nhầm core font Times

File chính:
- `app/Services/HardCopyCertificatePdfService.php`

Nội dung:
- Sửa cách nạp font Times New Roman cho TCPDF.
- Không dùng trực tiếp `public/fonts/times.ttf` nữa vì TCPDF lấy tên file thành `times`, bị trùng với core font `times` không hỗ trợ Unicode tiếng Việt đầy đủ.
- Tạo alias font riêng trong `storage/app/tcpdf-font-source` trước khi đưa vào TCPDF.
- Font TCPDF sau khi nạp là `timesnewromanpdfnormal` và `timesnewromanpdfb`, tránh fallback về core font.

Kiểm tra:
- `php -l app/Services/HardCopyCertificatePdfService.php`: pass.
- Render thử phiếu `238`: pass, PDF sinh 13 trang và có nhúng font Times New Roman Unicode.
- `php artisan test --filter=hard_copy_print_returns_tcpdf_pdf`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `composer validate --no-check-publish`: pass.

### PDF in ký tươi - chuyển sang TCPDF để đo dòng và vẽ theo tọa độ

File chính:
- `composer.json`
- `composer.lock`
- `app/Services/HardCopyCertificatePdfService.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Cài thêm `tecnickcom/tcpdf` để sinh riêng bản in ký tươi trên phôi bằng tọa độ point.
- Thêm service `HardCopyCertificatePdfService` vẽ trực tiếp số phiếu, thông tin khách hàng, bảng sản phẩm, ghi chú, số trang và tên người ký.
- Dùng TCPDF `getStringHeight()` để tính chiều cao dòng theo font và chiều rộng cột thực tế, thay cho ước lượng số ký tự trong Blade/Dompdf.
- Khóa vị trí ghi chú tại `top = 490pt` theo phôi, bảng chỉ được vẽ trong vùng phía trên ghi chú.
- Đổi `printHardCopy()` sang trả file PDF từ service TCPDF mới, vẫn giữ nguyên logic ghi nhận số lần in và lịch sử thao tác.
- Bổ sung test route in ký tươi trả về PDF hợp lệ và tăng số lần in.

Kiểm tra:
- Render thử phiếu `238`: pass, 90 sản phẩm, PDF 13 trang.
- Render thử phiếu test `248`: pass, 100 sản phẩm, PDF 15 trang.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=hard_copy_print_returns_tcpdf_pdf`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - cân lại trang nhiều sản phẩm theo chiều rộng cột thật

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Tăng khoảng an toàn trước ghi chú cố định từ `10pt` lên `14pt`.
- Tăng chiều cao header bảng dự kiến từ `38pt` lên `44pt`.
- Giảm số ký tự ước lượng trên mỗi dòng theo đúng bề rộng cột thực tế, đặc biệt cột `Tên sản phẩm` và `Tiêu chuẩn sản phẩm`.
- Không kẻ dòng trống ở các trang trung gian, chỉ kẻ tối đa `4` dòng trống ở trang cuối.
- Mục tiêu là tránh trường hợp trang trước bị chừa khoảng trắng bất thường còn trang sau bị kéo dài đè vào ghi chú.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu `238`: pass, 90 sản phẩm, PDF 18 trang.
- Render thử phiếu test `248`: pass, 100 sản phẩm, PDF 20 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - tính bảng theo mốc ghi chú cố định 490pt

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Đặt `top = 490pt` của phần ghi chú làm ranh giới cứng cho bảng sản phẩm.
- Tính chiều cao thân bảng theo công thức: vị trí ghi chú - khoảng an toàn - vị trí bắt đầu bảng - chiều cao header bảng.
- Tự tính vị trí bắt đầu bảng theo chiều cao số phiếu và thông tin khách hàng/công trình/ngày xuất hàng.
- Thêm mốc bắt đầu bảng tối thiểu `214pt` theo phôi in sẵn để tránh trường hợp thông tin ngắn làm bảng kéo xuống sát ghi chú.
- Giữ dòng trống kẻ thêm trong phạm vi chiều cao bảng cho phép.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu `146`: pass, 7 sản phẩm, PDF 1 trang.
- Render thử phiếu test `248`: pass, 100 sản phẩm, PDF 15 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - chặn bảng đè vùng ghi chú phôi

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Giảm vùng chiều cao bảng tối đa còn `220pt` để dừng bảng trước vùng ghi chú cố định của giấy phôi.
- Tự trừ thêm chiều cao bảng khi thông tin khách hàng/công trình/địa điểm dài nhiều dòng.
- Giới hạn số dòng trống kẻ thêm tối đa `4` dòng/trang để không kéo bảng đè xuống ghi chú.
- Giữ ghi chú, số trang và tên người ký ở vị trí cố định trên mọi trang phôi.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu test đã ký 100 dòng sản phẩm: pass, PDF in ký tươi sinh 14 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - phân trang theo chiều cao point và kẻ dòng trống

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Thay phân trang theo `unit/trang` bằng phân trang theo chiều cao ước lượng dạng point.
- Mỗi dòng sản phẩm được tính chiều cao theo số dòng chữ ước lượng của từng cột.
- Mỗi trang dùng tối đa `272pt` cho vùng bảng để không đè lên ghi chú cố định.
- Tự thêm dòng trống trong bảng theo khoảng trống còn lại, giúp bảng kéo gần tới ghi chú và form nhìn cân bằng hơn.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu in ký tươi 100 dòng sản phẩm: pass, thuật toán dự kiến 11 trang và PDF sinh 11 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in ký tươi - giảm chữ bảng và chừa vùng ghi chú rộng hơn

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Đặt rõ cỡ chữ dữ liệu trong bảng sản phẩm là `12pt`.
- Giảm padding và line-height của ô dữ liệu để nội dung gọn hơn.
- Giảm sức chứa phân trang ký tươi xuống `7` unit/trang để bảng dừng sớm hơn, tránh đè lên vùng ghi chú cố định trên phôi.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu test ký tươi 100 dòng sản phẩm: pass, PDF sinh 26 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Data test - thêm phiếu đã ký số nhiều sản phẩm để test in lại

File chính:
- `database/seeders/WorkflowReportTestDataSeeder.php`

Nội dung:
- Bổ sung 5 phiếu CNCL test đã ký số/phát hành, mỗi trung tâm NP/TP/HP/HD/TH có 1 phiếu.
- Số dòng sản phẩm lần lượt khoảng 45, 60, 75, 90, 100 để test in ký tươi/in lại với phiếu nhiều trang.
- Các phiếu có trạng thái `ISSUED`, `smartca_status = SIGNED`, có `signed_at`, `signed_by` và thông tin SmartCA test.
- Bổ sung một số dòng tiêu chuẩn dài như `DIN 8077:2008&DIN8078:2008` để test chống tràn cột.
- Khi dọn data test, xóa thêm `print_logs` và liên kết cấp lại liên quan trước khi xóa phiếu test.

Kiểm tra:
- `php -l database/seeders/WorkflowReportTestDataSeeder.php`: pass.
- `php artisan db:seed --class=WorkflowReportTestDataSeeder`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu test đã ký 100 dòng sản phẩm: pass, PDF in ký tươi sinh 20 trang.

### PDF in ký tươi - cố định ghi chú, số trang và người ký trên từng phôi

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Thêm cột `ĐVT` riêng trong bảng sản phẩm in ký tươi.
- Cột `Số lượng` chỉ hiển thị số, không gộp đơn vị tính.
- Cố định vị trí `Ghi chú`, `Trang x/y` và tên `Vũ Thị Diệu Thúy` trên tất cả các trang để phù hợp giấy phôi in sẵn.
- Chỉnh phân trang theo vùng bảng an toàn chung cho mọi trang, tránh bảng đè xuống phần ghi chú/chữ ký của phôi.
- Bổ sung xử lý ngắt chữ trong ô bảng, đặc biệt với tiêu chuẩn dài như `DIN 8077:2008&DIN8078:2008`.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu in ký tươi 100 dòng sản phẩm: pass, phân trang dự kiến 20 trang và PDF sinh ra 20 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF phiếu CNCL - thêm cột ĐVT và phân trang động theo chiều cao dòng

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Thêm cột `ĐVT` riêng trong bảng sản phẩm của mẫu PDF ký số.
- Cột `Số lượng` chỉ còn hiển thị số, không gộp dạng `số lượng (đơn vị tính)`.
- Thay cơ chế chia trang cứng `13 dòng/trang` bằng cơ chế ước lượng chiều cao từng dòng theo độ dài nội dung các cột.
- Trang thường có sức chứa lớn hơn để tận dụng khoảng trống; trang cuối chừa vùng an toàn cho ghi chú và chữ ký điện tử.
- Nếu trang cuối không đủ vùng ký, hệ thống tự tách dòng cuối sang trang mới để tránh chữ ký đè bảng sản phẩm.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- Render thử phiếu 100 dòng sản phẩm: pass, phân trang dự kiến 9 trang và PDF sinh ra 9 trang.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Yêu cầu cấp phiếu - bắt buộc ngày xuất hàng và cam kết nhận/lấy hàng

File chính:
- `database/migrations/2026_09_14_000001_add_commitment_to_certificate_requests_table.php`
- `app/Models/CertificateRequest.php`
- `app/Http/Controllers/CertificateRequestController.php`
- `resources/views/certificate_requests/_form.blade.php`
- `resources/views/certificate_requests/show.blade.php`
- `resources/views/dvkh_requests/show.blade.php`
- `resources/views/ptn_requests/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Thêm cột `customer_commitment_confirmed` để lưu xác nhận cam kết của bên tạo yêu cầu.
- Bắt buộc nhập `delivery_date` khi tạo/cập nhật yêu cầu cấp phiếu.
- Thêm checkbox cam kết trên form tạo/sửa yêu cầu; chỉ khi gửi DVKH mới bắt buộc tích, còn lưu nháp vẫn được phép chưa tích.
- Khóa nút `Gửi DVKH` trên form cho đến khi người dùng tích xác nhận cam kết.
- Khi gửi phiếu nháp từ màn chi tiết, hiển thị modal xác nhận cam kết và chặn gửi nếu chưa tích hoặc chưa có ngày xuất hàng.
- Hiển thị trạng thái đã/chưa xác nhận cam kết tại màn chi tiết yêu cầu, màn DVKH và màn PTN.

Kiểm tra:
- `php artisan migrate`: pass.
- `php -l app/Http/Controllers/CertificateRequestController.php`: pass.
- `php -l app/Models/CertificateRequest.php`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

## 2026-08-29

### PTN - căn lại nút thao tác chi tiết yêu cầu

File chính:
- `resources/views/ptn_requests/show.blade.php`

Nội dung:
- Đưa nút `Lập phiếu CNCL` và `Trả lại DVKH` vào cùng một hàng flex.
- Bỏ khoảng cách `mt-2` làm nút trả lại bị tụt dòng so với nút lập phiếu.
- Các nút vẫn tự xuống dòng gọn khi màn hình hẹp.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php -l app\Http\Controllers\PtnRequestController.php`: pass.

### PTN - trả lại yêu cầu về DVKH

File chính:
- `routes/web.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Services/NotificationService.php`
- `resources/views/ptn_requests/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Thêm route `ptn.requests.return-to-dvkh` để PTN trả lại yêu cầu về DVKH.
- Chỉ cho phép trả lại khi yêu cầu đang ở trạng thái `WAIT_PTN` và chưa có phiếu CNCL đang hiệu lực.
- Khi trả lại, yêu cầu chuyển về `WAIT_DVKH`, giữ thông tin đã gửi quy trình, thêm lý do vào ghi chú và ghi nhật ký thao tác.
- Gửi thông báo cho tài khoản DVKH và tài khoản Trung tâm liên quan.
- Bổ sung nút `Trả lại DVKH` và modal nhập lý do trên màn chi tiết PTN.
- Nếu nhập thiếu lý do, modal tự mở lại để người dùng thấy lỗi.
- Bổ sung test cho luồng PTN trả lại DVKH và chặn trả lại khi yêu cầu đã có phiếu CNCL.

Kiểm tra:
- `php artisan route:list --name=ptn.requests.return-to-dvkh`: pass.
- `php -l app\Http\Controllers\PtnRequestController.php`: pass.
- `php -l app\Services\NotificationService.php`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Báo cáo tổng hợp - làm rõ KPI yêu cầu và phiếu

File chính:
- `app/Http/Controllers/ReportController.php`
- `resources/views/reports/summary.blade.php`

Nội dung:
- Đổi nhãn KPI `Hoàn tất` thành `Yêu cầu hoàn tất` để tránh nhầm với phiếu đã phát hành.
- Đổi nhãn KPI `Đã hủy / trả lại` thành `Yêu cầu bị trả lại / hủy`.
- Bổ sung KPI riêng `Phiếu đã hủy / thu hồi`, lấy từ trạng thái phiếu `REVOKED`.
- Điều chỉnh layout KPI báo cáo sang dạng nhiều cột rõ hơn khi có thêm số liệu phiếu hủy/thu hồi.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php -l app\Http\Controllers\ReportController.php`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Báo cáo tổng hợp - bổ sung trạng thái phiếu CNCL đầy đủ

File chính:
- `app/Http/Controllers/ReportController.php`
- `app/Exports/CertificateSummaryExport.php`
- `resources/views/reports/summary.blade.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`

Nội dung:
- Tách bộ lọc `Trạng thái yêu cầu` và `Trạng thái phiếu CNCL` trên màn báo cáo tổng hợp.
- Bổ sung các trạng thái phiếu vào báo cáo: chưa lập phiếu, chờ Trưởng PTN duyệt, chờ gửi ký số, đang chờ ký số, quá hạn ký số, đã ký/phát hành, Trưởng PTN trả lại, đã hủy/thu hồi.
- Thêm dải thống kê nhanh số lượng phiếu theo từng trạng thái.
- Dải thống kê trạng thái phiếu vẫn giữ bức tranh tổng theo ngày/trung tâm/trạng thái yêu cầu, không bị co lại theo filter `Trạng thái phiếu` đang chọn.
- Bổ sung cột `Số phiếu`, `Trạng thái yêu cầu`, `Trạng thái phiếu` trong bảng dữ liệu báo cáo.
- Đồng bộ export Excel để xuất thêm số phiếu và trạng thái phiếu, đồng thời nhận bộ lọc trạng thái phiếu.
- Bổ sung test lọc báo cáo theo trạng thái phiếu.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php -l app\Http\Controllers\ReportController.php`: pass.
- `php -l app\Exports\CertificateSummaryExport.php`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Phân quyền - Việt hóa tên quyền trên giao diện

File chính:
- `app/Http/Controllers/RolePermissionController.php`
- `resources/views/role_permissions/index.blade.php`

Nội dung:
- Bổ sung tên tiếng Việt cho nhóm quyền `Lý do yêu cầu gấp`.
- Bổ sung tên tiếng Việt cho các quyền xem/thêm/sửa/ngừng sử dụng lý do gấp.
- Bổ sung tên tiếng Việt cho các vai trò: Quản trị viên, Lãnh đạo, Trung tâm phân phối, DVKH, PTN, Trưởng PTN, Chỉ xem.
- Màn phân quyền hiển thị nhãn tiếng Việt làm chính, mã quyền kỹ thuật chỉ còn là dòng nhỏ để phục vụ kiểm tra khi cần.

Kiểm tra:
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Windows scheduler - file gọi Laravel schedule

File chính:
- `scripts/windows-scheduler-run.bat`

Nội dung:
- Thêm file `.bat` để Windows Task Scheduler gọi `php artisan schedule:run`.
- File tự xác định thư mục dự án từ vị trí file trong thư mục `scripts`.
- Ghi log mỗi lần chạy vào `storage/logs/windows-scheduler.log`.
- Phù hợp cấu hình chạy lặp mỗi phút trên Windows/XAMPP để job SmartCA tự kiểm tra kết quả ký.

Kiểm tra:
- `cmd /c scripts\windows-scheduler-run.bat`: pass, gọi được `smartca:check-pending-signatures --limit=30` và kết thúc code 0.

### Trưởng PTN - tách riêng màn phiếu chờ gửi ký

File chính:
- `routes/web.php`
- `config/adminlte.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Services/WorkQueueService.php`
- `resources/views/quality_certificates/ready_to_sign.blade.php`
- `resources/views/quality_certificates/signing_queue.blade.php`
- `resources/views/dashboard/index.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`
- `tests/Feature/RoleWorkspaceAccessTest.php`

Nội dung:
- Thêm route/màn riêng `quality-certificates/ready-to-sign` để quản lý các phiếu đã duyệt nội dung và đang chờ gửi VNPT SmartCA.
- Màn mới có bộ lọc từ khóa, trung tâm, phiếu gấp; checkbox chọn nhiều; gửi ký hàng loạt; gửi từng phiếu; xem PDF; trả lại PTN/DVKH nếu cần sửa.
- Gửi ký hàng loạt được kiểm soát tối đa 10 phiếu/lần ở cả frontend và backend.
- Menu đổi thành 2 mục riêng: `Trưởng PTN duyệt` và `Phiếu chờ gửi ký`.
- Dashboard Trưởng PTN tách card `Chờ duyệt` và `Chờ gửi ký`; link `Chờ gửi ký` trỏ sang màn mới.
- Màn `Trưởng PTN duyệt ký` không còn trộn thao tác gửi ký hàng loạt; chỉ còn quản lý phiếu chờ duyệt, đang chờ app, quá hạn và đã trả lại.
- Kiểm tra scheduler: Laravel đã nhận lịch `smartca:check-pending-signatures --limit=30` chạy mỗi phút.
- Tối ưu bộ đếm trên màn `Phiếu chờ gửi ký` dùng tổng từ paginator để tránh query đếm thừa sau khi lọc.

Kiểm tra:
- `php artisan schedule:list`: pass, hiển thị job SmartCA mỗi phút.
- `php artisan smartca:check-pending-signatures --limit=1`: pass, hiện tại `Checked: 0` do không có giao dịch pending.
- `php artisan route:list --name=quality-certificates`: pass, có route `quality-certificates.ready-to-sign`.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Sửa hiển thị tên khách hàng trên PDF ký điện tử - 01/10/2026

File chính:
- `app/Services/SignedCertificatePdfService.php`
- `resources/views/quality_certificates/show.blade.php`
- `app/Mail/QualityCertificateIssuedMail.php`
- `resources/views/emails/quality_certificate_issued.blade.php`

Nội dung:
- Sửa cách tính chiều cao dòng thông tin khách hàng/công trình trong mẫu PDF ký điện tử.
- Chiều cao dòng hiện được đo bằng đúng font đậm đang dùng để in dữ liệu và trừ phần padding nội bộ của TCPDF.
- Tránh trường hợp tên khách hàng hoặc tên công trình dài bị cắt mất dòng cuối trong ô `Tên khách hàng`.
- Tạm bỏ khối debug `Dữ liệu API VNPT SmartCA` khỏi màn chi tiết phiếu vì khối này gây lỗi ParseError trên Blade runtime, làm vỡ toàn bộ màn chi tiết.
- Dữ liệu SmartCA vẫn giữ trong DB; chỉ ẩn phần debug admin khỏi giao diện để khôi phục màn hình chính ổn định trước.
- Email phát hành phiếu hiển thị rõ khi phiếu là phiếu cấp lại: phiếu mới thay cho phiếu cũ nào, lý do cấp lại, trạng thái phiếu cũ, ngày hủy/thu hồi và lý do hủy/thu hồi.
- Mail class load thêm quan hệ `reissueOfCertificate`, `reissueCertificates`, `replacesCertificate` để template email có đủ dữ liệu cấp lại/hủy.

Kiểm tra:
- `php -l app/Services/SignedCertificatePdfService.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.
- Render thử PDF bằng dữ liệu thật: pass, đã tạo các file kiểm tra trong `storage/app/pdf-render-check-*.pdf` và `storage/app/pdf-render-long-customer-195.pdf`.
- `php artisan optimize:clear`: pass.
- Render trực tiếp view `quality_certificates.show` cho phiếu `306`: pass.
- Render thử email phiếu cấp lại `201`: pass, có thông tin cấp lại, phiếu cũ và trạng thái hủy.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Sửa hiển thị yêu cầu PTN/Trưởng PTN trả lại tại màn DVKH - 22/09/2026

File chính:
- `app/Models/CertificateRequest.php`
- `resources/views/dvkh_requests/index.blade.php`
- `resources/views/dvkh_requests/show.blade.php`
- `resources/views/certificate_requests/partials/return_badge.blade.php`
- `database/migrations/2026_09_22_000001_backfill_return_tracking_from_notes.php`

Nội dung:
- Bổ sung fallback `effectiveLastReturnedTo()` và `effectiveLastReturnedFrom()` để nhận diện dữ liệu lịch sử có ghi chú trả lại nhưng chưa có metadata `last_returned_*`.
- Màn danh sách DVKH dùng fallback này để hiển thị badge `PTN trả lại DVKH` / `Trưởng PTN trả lại DVKH`.
- Màn chi tiết DVKH cũng dùng fallback để hiển thị cảnh báo trả lại với dữ liệu cũ.
- Thêm migration backfill metadata cho các yêu cầu cũ có ghi chú `[PTN trả lại DVKH]`, `[DVKH trả lại]`, hoặc `[Trưởng PTN trả lại ...]`.
- Đã chạy migration, các yêu cầu `YC-20260917-0002`, `YC-20260917-0001`, `YC-20260916-0018`, `YC-20260916-0004` được backfill `PTN -> DVKH`.

Kiểm tra:
- `php artisan migrate`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Hiển thị rõ các yêu cầu/phiếu bị trả lại theo vai trò - 21/09/2026

File chính:
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Services/WorkQueueService.php`
- `resources/views/certificate_requests/index.blade.php`
- `resources/views/certificate_requests/show.blade.php`
- `resources/views/certificate_requests/partials/return_badge.blade.php`
- `resources/views/ptn_requests/index.blade.php`
- `resources/views/ptn_requests/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Trung tâm có tab riêng `DVKH trả lại` trên danh sách yêu cầu, lọc các yêu cầu nháp cần sửa do DVKH trả lại.
- Dashboard và "Việc cần làm" của Trung tâm có mục `DVKH trả lại cần sửa`.
- PTN có thẻ thống kê và bộ lọc `Trưởng PTN trả lại` để nhận diện các phiếu bị Trưởng PTN trả về PTN xử lý lại.
- Dashboard và "Việc cần làm" của PTN có mục `Trưởng PTN trả lại`.
- Bổ sung partial badge dùng chung để hiển thị nguồn trả lại và lý do ngắn tại danh sách/chi tiết yêu cầu.
- Bộ lọc PTN tự chuyển mặc định sang trạng thái `PTN_PROCESSING` khi lọc phiếu Trưởng PTN trả lại, tránh lọc rỗng.

Kiểm tra:
- `php -l` các controller/service liên quan: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Tối ưu lịch sử xử lý yêu cầu và vòng trả lại - 18/09/2026

File chính:
- `app/Services/WorkflowHistoryService.php`
- `app/Services/WorkflowStepService.php`
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/DvkhRequestController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/certificate_requests/show.blade.php`
- `resources/views/dvkh_requests/show.blade.php`
- `resources/views/ptn_requests/show.blade.php`
- `resources/views/quality_certificates/partials/history_timeline.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Bổ sung service gom lịch sử xử lý theo yêu cầu, bao gồm cả log của phiếu CNCL được tạo từ yêu cầu đó.
- Màn chi tiết yêu cầu của Trung tâm, DVKH và PTN hiển thị thêm `Lịch sử xử lý yêu cầu`.
- Timeline hiển thị nhãn thao tác tiếng Việt dễ hiểu thay vì action code nội bộ.
- DVKH trả lại Trung tâm sẽ lưu metadata trả lại để tiến trình hiển thị rõ lý do trong lúc yêu cầu quay về nháp.
- Khi Trung tâm gửi lại, DVKH duyệt lại hoặc PTN lập lại phiếu sau khi bị trả về, hệ thống tự xóa metadata trả lại cũ để dashboard/bộ lọc không báo nhầm.
- Tiến trình xử lý phân biệt rõ Trưởng PTN trả phiếu về PTN hay về DVKH.

Kiểm tra:
- `php -l` các controller/service liên quan: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Trưởng PTN - duyệt phiếu, chờ gửi ký và tự động kiểm tra SmartCA

File chính:
- `app/Models/QualityCertificate.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Services/WorkQueueService.php`
- `app/Console/Commands/CheckSmartCaPendingSignatures.php`
- `routes/web.php`
- `routes/console.php`
- `resources/views/quality_certificates/show.blade.php`
- `resources/views/quality_certificates/signing_queue.blade.php`
- `resources/views/quality_certificates/index.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Thêm trạng thái phiếu CNCL mới: `WAIT_PTN_MANAGER_APPROVAL`, `READY_TO_SIGN`, `SIGN_PENDING`, `SIGN_EXPIRED`.
- Phiếu do PTN lập mới chuyển sang `WAIT_PTN_MANAGER_APPROVAL` thay vì dùng `DRAFT` chung.
- Trưởng PTN có thể bấm `Đưa vào chờ gửi ký` để gom phiếu vào danh sách gửi ký hàng loạt.
- Trưởng PTN vẫn có thể `Duyệt và gửi ký SmartCA` trực tiếp từ chi tiết phiếu; thao tác gửi ký trực tiếp đồng thời được ghi nhận là đã duyệt nội dung.
- Bổ sung route `quality-certificates/{qualityCertificate}/approve-for-signing`.
- Bổ sung route `quality-certificates/bulk-sign`, giới hạn tối đa 10 phiếu/lần để giảm rủi ro quá hạn xác nhận 5 phút trên app VNPT SmartCA.
- Nâng cấp màn `Trưởng PTN duyệt ký` với tab `Chờ duyệt`, `Chờ gửi ký`, checkbox chọn phiếu và nút gửi ký hàng loạt.
- Đồng bộ bộ đếm việc cần làm cho Trưởng PTN: chờ duyệt, chờ gửi ký, đang chờ app, quá hạn ký.
- Bổ sung command `smartca:check-pending-signatures` và scheduler chạy mỗi phút để tự động kiểm tra kết quả ký VNPT SmartCA, hoàn tất phát hành/gửi email khi ký thành công.
- Giữ tương thích dữ liệu cũ: phiếu `DRAFT` chưa ký vẫn được hiểu là chờ Trưởng PTN duyệt; giao dịch SmartCA pending cũ vẫn hiển thị và được job xử lý.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan list smartca`: pass, command `smartca:check-pending-signatures` đã được nhận.
- `php artisan route:list --name=quality-certificates`: pass, đã có route duyệt chờ ký và gửi ký hàng loạt.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in phiếu ký tươi - giới hạn vùng bảng để không đè vùng ký

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Bỏ cơ chế bù dòng trống đến đủ khung bảng trong mẫu in phôi.
- Giảm sức chứa mỗi trang in phôi xuống 10 dòng quy đổi để luôn chừa vùng an toàn cho chữ ký trên giấy phôi.
- Đặt chiều cao dòng bảng cố định hơn và giảm padding nhẹ để bảng gọn, dễ căn với phôi in sẵn.
- Giữ phần tên `Vũ Thị Diệu Thúy` ở tọa độ cố định, không bị bảng phía trên kéo lệch.

Kiểm tra:
- `php artisan view:clear`: pass.
- Render thử phiếu `146`: pass, `details=7`, `pages=1`.
- Render thử phiếu `150`: pass, `details=26`, `pages=3`.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in phiếu ký tươi - sửa trang trắng và vị trí tên người ký

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Thêm `<!DOCTYPE html>` cho template in phôi để Dompdf render ổn định hơn.
- Bỏ page-break cố định ở CSS `.page`, chỉ ngắt trang bằng inline style cho các trang không phải trang cuối.
- Nới thuật toán ước tính chiều cao dòng sản phẩm trong mẫu in phôi, tránh tách trang quá sớm với tên sản phẩm độ dài trung bình.
- Điều chỉnh vị trí `Vũ Thị Diệu Thúy` xuống thấp hơn bằng tọa độ tuyệt đối, giữ vùng ký ổn định trên phôi in sẵn.

Kiểm tra:
- `php artisan view:clear`: pass.
- Render thử `quality-certificates/146/print-hard-copy`: pass, Dompdf báo `canvas_pages=1`.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF in phiếu ký tươi - tự phân trang theo độ dài sản phẩm

File chính:
- `resources/views/quality_certificates/hard_copy_print.blade.php`

Nội dung:
- Tăng cỡ chữ nội dung in phôi lên 13pt.
- Tăng chữ trong bảng sản phẩm lên 12pt.
- Thêm đơn vị tính vào cột số lượng theo dạng `45 (m)`.
- Đổi phân trang từ cố định 13 sản phẩm/trang sang ước tính độ cao dòng theo độ dài tên sản phẩm, kích thước, yêu cầu kỹ thuật và tiêu chuẩn.
- Một sản phẩm dài có thể được tính tương đương 2-3 dòng để tránh bảng bị đẩy xuống vùng ký.
- Cố định vị trí tên người ký bằng `position:absolute`, không để nội dung bảng/ghi chú phía trên kéo lệch vùng ký trên phôi in sẵn.

Kiểm tra:
- `php artisan view:cache`: pass.
- Render thử PDF in phôi phiếu `150` bằng Dompdf: pass, xuất được `1424549` bytes.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF phiếu CNCL - sửa lỗi fallback font tiếng Việt ở header

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Điều chỉnh `font-weight` tên công ty từ `900` về `700`.
- Nguyên nhân: hệ thống chỉ nhúng Times New Roman normal/bold; Dompdf có thể không map đúng weight `900`, dẫn tới fallback sang font không hỗ trợ tiếng Việt.
- Tăng nhẹ cỡ chữ để vẫn giữ header to/rõ nhưng dùng đúng font bold đã nhúng.

Kiểm tra:
- `php artisan view:cache`: pass.
- Render thử PDF phiếu `150` bằng Dompdf: pass, xuất được `2046556` bytes.

### Rà soát workflow - sửa dashboard, việc cần làm, sinh mã và loading

File chính:
- `app/Http/Controllers/DashboardController.php`
- `app/Services/WorkQueueService.php`
- `app/Http/Controllers/CertificateRequestController.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `app/Http/Controllers/PtnRequestController.php`
- `resources/views/dvkh_requests/index.blade.php`
- `resources/views/certificate_requests/show.blade.php`
- `resources/views/quality_certificates/show.blade.php`
- `resources/views/ptn_requests/show.blade.php`
- `database/migrations/2026_09_18_000001_change_certificate_request_status_to_string.php`

Nội dung:
- Đồng bộ số đếm dashboard với link bộ lọc `SIGN_READY` bằng metric tổng `sign_actionable`.
- Bổ sung mục việc cần làm `Nháp cần sửa / gửi DVKH` cho tài khoản Trung tâm để thấy yêu cầu bị DVKH trả lại về nháp.
- Giảm rủi ro trùng mã yêu cầu/phiếu khi thao tác đồng thời bằng `lockForUpdate()` khi lấy số tiếp theo.
- Chuyển cột `certificate_requests.status` sang `VARCHAR(30)` trên MySQL để không bị giới hạn enum cũ khi phát sinh trạng thái workflow mới.
- Bổ sung loading/khóa thao tác cho các form xác nhận DVKH, gửi lại email, gửi lại ký SmartCA và PTN lập phiếu.

Kiểm tra:
- `php -l` các file PHP đã sửa: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.
- `php artisan migrate`: pass.

### DVKH - đồng bộ nút trả lại Trung tâm phân phối

File chính:
- `resources/views/dvkh_requests/index.blade.php`
- `resources/views/dvkh_requests/show.blade.php`
- `resources/views/certificate_requests/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Rà soát các màn tài khoản DVKH dùng để xử lý yêu cầu.
- Đổi nhãn nút/modal từ `Trả lại` thành `Trả lại Trung tâm` để đúng nghiệp vụ.
- Bổ sung loading khi DVKH gửi trả lại yêu cầu, tránh bấm lặp trong lúc hệ thống xử lý.
- Bổ sung test xác nhận nút hiển thị ở danh sách DVKH, chi tiết DVKH và chi tiết yêu cầu chung.
- Test xác nhận sau khi trả lại, yêu cầu quay về `DRAFT`, xóa thông tin gửi DVKH và gửi thông báo cho tài khoản Trung tâm.

Kiểm tra:
- `php -l tests/Feature/CertificateWorkflowTest.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### Chi tiết phiếu CNCL - trả lại DVKH khi Trưởng PTN trả về PTN

File chính:
- `app/Http/Controllers/QualityCertificateController.php`
- `resources/views/quality_certificates/show.blade.php`
- `tests/Feature/CertificateWorkflowTest.php`

Nội dung:
- Bổ sung kiểm tra điều kiện hiển thị nút `Trả lại DVKH` ngay tại màn chi tiết phiếu CNCL.
- Nút chỉ hiển thị khi phiếu đã bị Trưởng PTN trả về PTN, yêu cầu đang ở trạng thái PTN xử lý lại, và chưa có phiếu mới thay thế đang hoạt động.
- Dùng lại route xử lý nghiệp vụ hiện có của PTN để chuyển yêu cầu về bước DVKH, tránh tách logic gây lệch trạng thái.
- Ẩn nút xám `Phiếu đã trả lại` khi người dùng PTN có quyền thực hiện hành động trả lại DVKH.

Kiểm tra:
- `php -l app/Http/Controllers/QualityCertificateController.php`: pass.
- `php -l tests/Feature/CertificateWorkflowTest.php`: pass.
- `php artisan view:clear`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF phiếu CNCL - tăng cỡ chữ tên công ty

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Tăng cỡ chữ và độ đậm cho tên công ty tiếng Việt và tiếng Anh ở header PDF.
- Nới vùng giữa header từ 64% lên 68%.
- Giảm nhẹ vùng logo/ISO hai bên để tên công ty giữ trên một dòng.
- Bổ sung `white-space: nowrap` cho dòng tên công ty tiếng Anh.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF phiếu CNCL - lấp đầy dòng sản phẩm trên các trang trước

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Đổi lại thuật toán chia trang sản phẩm theo hướng đẹp và dễ đoán hơn.
- Mỗi trang tối đa 13 dòng sản phẩm.
- Trang cuối không cần đủ 13 dòng, hệ thống tự bù dòng trống để giữ khung bảng cân đối.
- Phiếu `quality-certificates/150/pdf` có 26 sản phẩm sẽ chia `13 / 13` thay vì `9 / 8 / 9`.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF phiếu CNCL - cân bằng dòng sản phẩm giữa các trang

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Sửa thuật toán chia trang khi phiếu có nhiều sản phẩm.
- Trước đây hệ thống giữ cứng 9 dòng cho trang cuối rồi chia phần còn lại, nên có trường hợp 26 sản phẩm bị chia `13 / 4 / 9`.
- Logic mới cân bằng các trang trước trang cuối, ví dụ phiếu 150 có 26 sản phẩm sẽ chia `9 / 8 / 9`.
- Trang cuối vẫn giữ tối đa 9 dòng để chừa chỗ cho ghi chú và chữ ký điện tử.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- Kiểm tra phiếu `quality-certificates/150/pdf`: 26 sản phẩm, phân trang dự kiến `9 / 8 / 9`.

### PDF phiếu CNCL - tối ưu số dòng theo trang

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Trang cuối giữ tối đa 9 dòng sản phẩm để chừa chỗ cho ghi chú và chữ ký điện tử.
- Các trang không phải trang cuối tăng lên tối đa 13 dòng sản phẩm.
- STT sản phẩm vẫn chạy liên tục qua các trang.

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

### PDF phiếu chứng nhận chất lượng

File chính:
- `resources/views/quality_certificates/pdf.blade.php`

Nội dung:
- Cột "Số lượng" trong bảng sản phẩm hiển thị thêm đơn vị tính theo dạng `45 (m)`.
- PDF được chia trang chủ động theo danh sách sản phẩm.
- Mỗi trang lặp lại header, tiêu đề phiếu, số phiếu và thông tin khách hàng/công trình.
- Bổ sung số trang dạng `1/3`, `2/3`, `3/3`.
- Ghi chú và chữ ký điện tử chỉ hiển thị ở trang cuối.
- Các trang chưa phải cuối hiển thị dòng "Còn tiếp trang sau".

Kiểm tra:
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.

Ghi chú:
- Phiếu đã ký có file PDF lưu sẵn trong `storage` vẫn ưu tiên trả file đã ký cũ. Mẫu mới áp dụng cho phiếu chưa ký, phiếu ký mới hoặc khi regenerate PDF.
### DVKH nhận diện yêu cầu bị PTN trả lại - 18/09/2026

File chính:
- `database/migrations/2026_09_18_000002_add_return_tracking_to_certificate_requests.php`
- `app/Models/CertificateRequest.php`
- `app/Http/Controllers/PtnRequestController.php`
- `app/Http/Controllers/QualityCertificateController.php`
- `app/Http/Controllers/DvkhRequestController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Services/WorkQueueService.php`
- `app/Services/WorkflowStepService.php`
- `resources/views/dvkh_requests/index.blade.php`
- `resources/views/dvkh_requests/show.blade.php`

Nội dung:
- Bổ sung metadata lần trả lại gần nhất cho yêu cầu: nguồn trả lại, bước nhận lại, lý do, thời gian, người trả lại.
- Khi PTN trả yêu cầu về DVKH, hệ thống lưu metadata để DVKH phân biệt với yêu cầu mới.
- Khi Trưởng PTN trả phiếu về DVKH/PTN, request gốc cũng lưu metadata trả lại tương ứng.
- Màn DVKH có thẻ thống kê và bộ lọc riêng `PTN/Trưởng PTN trả lại`.
- Danh sách và chi tiết DVKH hiển thị badge/cảnh báo kèm lý do trả lại.
- Dashboard và "Việc cần làm" của DVKH có mục riêng cho yêu cầu bị trả lại.
- Tiến trình xử lý mô tả rõ khi yêu cầu quay về DVKH do bị trả lại.

Kiểm tra:
- `php artisan migrate`: pass.
- `php -l` các file PHP liên quan: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

### Import khách hàng và gửi lại email phiếu - 18/09/2026

File chính:
- `app/Http/Controllers/CustomerController.php`
- `resources/views/customers/index.blade.php`
- `resources/views/quality_certificates/show.blade.php`

Nội dung:
- Import khách hàng bắt buộc có `ten_cong_trinh` và `dia_diem_cong_trinh`, đồng bộ với rule tạo/sửa khách hàng trên giao diện.
- File import tạm khi cảnh báo trùng mã khách hàng được gắn token trong session, tránh submit lại `temp_path` không hợp lệ.
- Sau khi import thành công hoặc file lỗi, hệ thống xóa file tạm và xóa token phiên import.
- Câu xác nhận gửi lại email phiếu đổi từ "cho khách hàng" sang "cho Trung tâm phân phối" đúng nghiệp vụ gửi mail hiện tại.

Kiểm tra:
- `php -l app/Http/Controllers/CustomerController.php`: pass.
- `php artisan view:cache`: pass.
- `php artisan test --filter=CertificateWorkflowTest`: pass.
- `php artisan test --filter=RoleWorkspaceAccessTest`: pass.

## 2026-10-03 - Import/Export danh muc Don vi ban hang

File da sua/them:
- `SalesUnitsTemplateExport.php` (`app/Exports/SalesUnitsTemplateExport.php`): file mau import don vi ban hang.
- `SalesUnitsExport.php` (`app/Exports/SalesUnitsExport.php`): xuat danh muc don vi ban hang ra Excel.
- `SalesUnitController.php` (`app/Http/Controllers/SalesUnitController.php`): them import/export/template, canh bao trung `ma_bravo + trung tam` truoc khi cap nhat.
- `web.php` (`routes/web.php`): them route `sales-units-template`, `sales-units-import`, `sales-units-export`.
- `PermissionSeeder.php` (`database/seeders/PermissionSeeder.php`): them quyen `sales_unit.import`, `sales_unit.export`.
- `index.blade.php` (`resources/views/sales_units/index.blade.php`): them nut File mau, Import, Xuat Excel va modal xac nhan cap nhat du lieu trung.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Ho tro import file Excel co cot `ma_trung_tam` hoac cot trung tam dang ten nhu `Tam Phuoc`.
- Ho tro file co dong tieu de lon phia tren, he thong tu tim dong header co cot `ma_bravo`/`ma_dvbh`.
- Neu trung `ma_bravo + trung tam`, he thong hien danh sach trung de nguoi quan ly xac nhan truoc khi cap nhat.
- Trung tam khong duoc import/export theo dung yeu cau khong cho Trung tam tao don vi ban hang.

Kiem tra:
- `php -l` controller/export lien quan.
- `php artisan view:cache`.
- `php artisan db:seed --class=PermissionSeeder`.

## 2026-10-03 - Them danh muc Don vi ban hang theo Trung tam

File da sua/them:
- `2026_10_03_000001_create_sales_units_table.php` (`database/migrations/2026_10_03_000001_create_sales_units_table.php`): tao bang `sales_units`.
- `2026_10_03_000002_add_sales_unit_id_to_certificate_requests_table.php` (`database/migrations/2026_10_03_000002_add_sales_unit_id_to_certificate_requests_table.php`): them `sales_unit_id` vao yeu cau cap phieu.
- `SalesUnit.php` (`app/Models/SalesUnit.php`): model danh muc don vi ban hang.
- `CertificateRequest.php` (`app/Models/CertificateRequest.php`): them fillable va quan he `salesUnit`.
- `DistributionCenter.php` (`app/Models/DistributionCenter.php`): them quan he `salesUnits`.
- `SalesUnitController.php` (`app/Http/Controllers/SalesUnitController.php`): quan ly danh muc don vi ban hang, dinh chi va xoa han khi chua duoc dung.
- `CertificateRequestController.php` (`app/Http/Controllers/CertificateRequestController.php`): them validate/luu/chinh sua `sales_unit_id`.
- `PtnRequestController.php` (`app/Http/Controllers/PtnRequestController.php`): them `sales_unit_id` cho luong PTN lap truc tiep.
- `DvkhRequestController.php` (`app/Http/Controllers/DvkhRequestController.php`): load va hien thi don vi ban hang khi DVKH kiem tra.
- `QualityCertificateController.php` (`app/Http/Controllers/QualityCertificateController.php`): load don vi ban hang trong chi tiet phieu va giu lai khi cap lai.
- `web.php` (`routes/web.php`): them route `sales-units`.
- `PermissionSeeder.php` (`database/seeders/PermissionSeeder.php`): them quyen `sales_unit.*`; Trung tam chi xem/chon, DVKH/Admin quan ly.
- `sidebar.blade.php` (`resources/views/partials/sidebar.blade.php`): them menu Don vi ban hang theo quyen.
- `index.blade.php` (`resources/views/sales_units/index.blade.php`): man danh sach/bo loc don vi ban hang.
- `_form.blade.php` (`resources/views/sales_units/_form.blade.php`): form nhap Ma Bravo, Ten DVBH, dia chi, SDT, MST, so tai khoan, dai dien, ghi chu.
- `create.blade.php` (`resources/views/sales_units/create.blade.php`): man them moi.
- `edit.blade.php` (`resources/views/sales_units/edit.blade.php`): man cap nhat.
- `_form.blade.php` (`resources/views/certificate_requests/_form.blade.php`): them select Don vi ban hang, loc theo Trung tam.
- `show.blade.php` (`resources/views/certificate_requests/show.blade.php`): hien thi don vi ban hang.
- `show.blade.php` (`resources/views/dvkh_requests/show.blade.php`): hien thi don vi ban hang.
- `show.blade.php` (`resources/views/ptn_requests/show.blade.php`): hien thi don vi ban hang.
- `show.blade.php` (`resources/views/quality_certificates/show.blade.php`): hien thi don vi ban hang trong chi tiet phieu.
- `index.blade.php` (`resources/views/certificate_requests/index.blade.php`): hien thi nhanh don vi ban hang tren danh sach yeu cau.
- `index.blade.php` (`resources/views/dvkh_requests/index.blade.php`): hien thi nhanh don vi ban hang tren man DVKH.
- `index.blade.php` (`resources/views/ptn_requests/index.blade.php`): hien thi nhanh don vi ban hang tren man PTN.
- `index.blade.php` (`resources/views/quality_certificates/index.blade.php`): hien thi nhanh don vi ban hang tren danh sach phieu.
- `CertificateWorkflowTest.php` (`tests/Feature/CertificateWorkflowTest.php`): cap nhat test tao yeu cau voi don vi ban hang.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Them danh muc Don vi ban hang gan rieng tung Trung tam phan phoi.
- Trung tam duoc xem/chon don vi ban hang co san, khong duoc tao/sua/xoa.
- Admin va DVKH co quyen quan ly danh muc; Admin co the xoa han khi chua phat sinh lien ket.
- Khi tao/sua yeu cau cap phieu va PTN lap truc tiep, nguoi dung chon don vi ban hang tu danh sach theo Trung tam.
- Yeu cau cap lai giu lai `sales_unit_id` tu yeu cau goc.

Kiem tra:
- `php -l` cac model/controller/migration/test lien quan.
- `php artisan view:cache`.
- `php artisan test tests\Feature\CertificateWorkflowTest.php`.
- `php artisan migrate`.
- `php artisan db:seed --class=PermissionSeeder`.

## 2026-10-03 - Doi quy cach sinh so yeu cau va so phieu theo trung tam

File da sua:
- `DocumentNumberService.php` (`app/Services/DocumentNumberService.php`): service sinh so yeu cau, so phieu va ten file an toan.
- `CertificateRequestController.php` (`app/Http/Controllers/CertificateRequestController.php`): tao yeu cau trung tam theo so moi.
- `PtnRequestController.php` (`app/Http/Controllers/PtnRequestController.php`): PTN lap truc tiep va tao phieu CNCL theo so moi.
- `QualityCertificateController.php` (`app/Http/Controllers/QualityCertificateController.php`): yeu cau cap lai va ten file PDF tra ve trinh duyet theo so moi.
- `SmartCaService.php` (`app/Services/SmartCaService.php`): ten file gui SmartCA dung dang an toan.
- `QualityCertificateIssuedMail.php` (`app/Mail/QualityCertificateIssuedMail.php`): ten file PDF dinh kem email dung dang an toan.
- `CertificateWorkflowTest.php` (`tests/Feature/CertificateWorkflowTest.php`): bo sung test quy cach so moi va sequence theo trung tam.
- `CHANGELOG_CNCL.md`: ghi nhan thay doi.

Noi dung:
- Them service sinh so dung chung cho yeu cau va phieu CNCL.
- So yeu cau moi co dang `YC-YYYYMMDD-0001/TH`, sequence tach rieng theo tung trung tam trong ngay.
- So phieu CNCL moi duoc sinh tu so yeu cau tuong ung, dang `CNCL-YYYYMMDD-0001/TH`.
- Luong tao yeu cau thuong, PTN lap truc tiep va yeu cau cap lai deu dung quy cach so moi.
- Ten file PDF/email/SmartCA duoc chuyen sang dang an toan de khong loi khi so phieu co ky tu `/`.

Kiem tra:
- `php -l` cac file PHP lien quan.
- `php artisan view:cache`.
- `php artisan test --filter=standard_certificate_request_workflow_reaches_signing_queue`.
- `php artisan test --filter=request_number_sequence_is_scoped_by_distribution_center`.
