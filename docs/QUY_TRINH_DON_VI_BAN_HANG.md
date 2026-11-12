# Đơn vị bán hàng tạo yêu cầu cấp phiếu

## Quy trình

Đơn vị bán hàng lưu nháp → gửi Trung tâm kiểm tra → Trung tâm hoàn thiện / gửi DVKH → DVKH → PTN → Trưởng PTN duyệt / ký số → phát hành.

Trung tâm có thể trả lại, bắt buộc ghi lý do. Đơn vị sửa và gửi lại Trung tâm, không gửi thẳng DVKH. Nếu DVKH trả hồ sơ thì Trung tâm tiếp nhận trước, tự sửa hoặc trả tiếp cho đơn vị. Số yêu cầu không thay đổi khi gửi / trả lại. Số CNCL tương ứng với số yêu cầu theo quy tắc hiện tại.

Trung tâm tự tạo vẫn gửi thẳng DVKH như cũ. Yêu cầu cũ không được chuyển nguồn, đổi trạng thái hoặc đánh lại số bởi migration.

## Tạo tài khoản

1. Admin mở **Tài khoản → Thêm tài khoản**.
2. Chọn vai trò **Đơn vị bán hàng** (`DonViBanHang`).
3. Chọn đơn vị bán hàng đang hoạt động trong danh mục; hệ thống tự lấy Trung tâm theo đơn vị đó. Chỉ Admin được gắn tài khoản đơn vị.
4. Cấp tên đăng nhập và mật khẩu. Không tạo tài khoản thật bằng seeder dữ liệu test.

Nhiều tài khoản có thể cùng một đơn vị và cùng xem / làm việc trên hồ sơ của đơn vị. Không xem chéo đơn vị khác, kể cả cùng Trung tâm. Đơn vị không xem được bản nháp riêng do Trung tâm tạo. Hồ sơ Trung tâm đã gửi vào quy trình và gắn với đơn vị thì đơn vị được theo dõi.

Đổi vai trò / đơn vị / Trung tâm của tài khoản sẽ thu hồi phiên đăng nhập để dùng lại đúng phạm vi mới. Profile không được tự đổi các liên kết này. Đơn vị hoặc Trung tâm bị đình chỉ, đơn vị đã xóa mềm hoặc liên kết không khớp thì chặn truy cập nghiệp vụ.

## Thao tác đơn vị bán hàng

- Trung tâm và đơn vị bán hàng được cố định, không tự thay.
- Chọn khách hàng/công trình thuộc đơn vị hoặc nhập mới trong yêu cầu. Khách mới được gắn với đơn vị; không cho sửa danh mục dùng chung. Dữ liệu khách hàng cũ chưa được gắn đơn vị không tự chia sẻ cho đơn vị mới.
- Nhập ngày xuất hàng, người yêu cầu, sản phẩm và số lượng; chọn ký tươi nếu cần như biểu mẫu hiện tại.
- **Lưu nháp** để soạn tiếp; tích cam kết rồi **Gửi Trung tâm kiểm tra** để chuyển xử lý.
- Sau khi gửi, không sửa/xóa. Trung tâm trả lại thì xem lý do, sửa và gửi lại Trung tâm.
- Chỉ xem chi tiết phiếu / PDF sau ký số và phát hành. PDF được lấy từ file đã ký, không render lại chữ ký. Không tự cấp lại, in ký tươi, bổ sung ký tươi hoặc xử lý nội bộ.

Không gán thêm quyền nội bộ để vượt luồng: tài khoản đơn vị có giới hạn an toàn ở cả API quyền và middleware route, kể cả nếu cấp nhầm quyền trực tiếp.

## Thao tác Trung tâm

- Dashboard, menu công việc và danh sách yêu cầu có nhóm **Chờ Trung tâm kiểm tra**.
- Mở hồ sơ, kiểm tra thông tin. Có thể **Sửa yêu cầu**, **Lưu chỉnh sửa**, hoặc **Gửi DVKH**.
- **Trả lại đơn vị** khi cần đơn vị sửa, bắt buộc nhập lý do.
- Không sửa nháp chưa gửi của đơn vị hoặc hồ sơ đang chờ đơn vị sửa. Trung tâm không đổi đơn vị / Trung tâm của yêu cầu nguồn đơn vị.
- Sau khi gửi DVKH, tiếp tục theo quy trình hiện tại. Nếu DVKH trả lại, chỉ Trung tâm được quyết định xử lý tiếp.

Giao diện hiển thị thêm bước đơn vị gửi và Trung tâm kiểm tra. Lịch sử lưu người thực hiện, thời gian và lý do. Thao tác gửi/trả/sửa/xóa sử dụng kiểm tra trạng thái; bước mới có khóa dòng trong transaction để không ghi đè thao tác đã hoàn thành ở phiên khác.

## Báo cáo và SLA

- `sales_unit_submitted_at/by`: lần gần nhất đơn vị gửi Trung tâm. Chưa tạo mốc DVKH.
- `submitted_at/by`: lần Trung tâm gửi DVKH, vẫn là mốc SLA DVKH hiện tại.
- `center_reviewed_at/by`: Trung tâm hoàn tất kiểm tra/gửi DVKH ở vòng hiện tại; khi trả tiếp đơn vị sẽ xóa mốc vòng hiện tại, lịch sử cũ vẫn nằm trong nhật ký.
- Báo cáo công việc bổ sung riêng **Đơn vị bán hàng gửi Trung tâm**; nhóm **Trung tâm gửi yêu cầu** vẫn chỉ tính gửi sang DVKH. Không cộng hai nhóm thành số phiếu duy nhất.
- Bộ lọc trạng thái yêu cầu có thêm Chờ Trung tâm / Trung tâm trả lại đơn vị. DVKH/PTN không thấy hồ sơ trước khi Trung tâm gửi vào quy trình. Admin vẫn có thể xem toàn bộ để quản trị.
- Báo cáo nội bộ không mở cho đơn vị bán hàng. Không thay công thức in đơn/in bộ, dữ liệu phiếu đã ký hoặc nghiệp vụ SmartCA.

## Quản lý thiết bị

Khi hệ thống bật kiểm soát thiết bị và đang kiểm soát vai trò Trung tâm, tài khoản Đơn vị bán hàng cũng phải duyệt thiết bị. Có thể chọn riêng vai trò Đơn vị bán hàng trong cấu hình. Không sửa giá trị cấu hình đang lưu; công tắc tắt toàn bộ vẫn được tôn trọng.

## Triển khai an toàn

Sao lưu database và `.env` trước; dùng thời gian bảo trì. Dừng tại lệnh lỗi để kiểm tra. Migration chỉ bổ sung cột nullable và liên kết mới, không cập nhật dữ liệu hồ sơ / tài khoản cũ.

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --path=database/migrations/2026_11_11_000001_add_sales_unit_request_workflow.php --force
php artisan db:seed --class=SalesUnitAccessSeeder --force
php artisan permission:cache-reset
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Seeder chuyên biệt chỉ tạo vai trò mới / thêm các quyền cơ bản cho vai trò đó, không tạo tài khoản, không sync lại nhóm Trung tâm/DVKH/PTN và không xóa ngoại lệ quyền tài khoản. `PermissionSeeder` cũng gọi seeder mới cho cài đặt mới, nhưng khi cập nhật VPS chỉ cần chạy `SalesUnitAccessSeeder`.

Không chạy `migrate:fresh`, `migrate:refresh`, `db:wipe`, seeder dữ liệu test hoặc import lại database. Không rollback migration sau khi đã tạo tài khoản / yêu cầu mới nếu chưa có phương án bảo toàn liên kết.

Sau cập nhật: tạo một tài khoản đơn vị thử nghiệm trong phạm vi được phép; kiểm tra nháp → gửi Trung tâm → trả lại → gửi lại → Trung tâm gửi DVKH. Đối chiếu quyền truy cập bằng tài khoản đơn vị khác và một yêu cầu cũ của Trung tâm.

## Kiểm thử

`tests/Feature/SalesUnitWorkflowTest.php` dùng SQLite bộ nhớ, không dùng MySQL. Bao gồm cô lập giữa đơn vị, cố định liên kết, sửa/gửi lại, DVKH trả Trung tâm, tiếp tục đến hàng chờ ký, PDF đã ký, báo cáo, an toàn migration/seeder, khóa sửa và kiểm soát thiết bị.

## Đã cập nhật trên local

Ngày 11/11/2026 đã sao lưu các bảng liên quan tại `storage/app/private/backups/sales-unit-workflow-20261111-be9938/before.sql`, sau đó chạy riêng migration và `SalesUnitAccessSeeder` trên MySQL local `cncl_system`. `baseline.json` / `verification.json` lưu hash đối chiếu.

Hash dữ liệu cũ ở users, customers, certificate_requests, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions và các dòng migrations cũ đều không đổi. Giữ nguyên 23 tài khoản, 1.233 khách hàng, 1.758 yêu cầu, 1.731 phiếu và 36 yêu cầu bổ sung ký tươi. Chỉ thêm vai trò DonViBanHang với 6 quyền, không gán vào tài khoản thật, không tạo yêu cầu / khách hàng test. VPS chưa được cập nhật từ máy local này; cần thực hiện các lệnh triển khai ở trên.
