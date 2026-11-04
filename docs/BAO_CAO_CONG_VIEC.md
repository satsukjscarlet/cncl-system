# Báo cáo công việc

## Truy cập và phân quyền

- Menu **Báo cáo → Báo cáo công việc**, URL `/reports/work`.
- DVKH/PTN/Trưởng PTN được mở báo cáo công việc trong phạm vi dưới đây. Tài khoản nội bộ vai trò khác cần `report.view` và cấu hình phạm vi hợp lệ; xuất Excel cần thêm `report.export`. Không tự cấp quyền xem các báo cáo khác cho DVKH.
- Tài khoản có vai trò TrungTam bị chặn cả xem và xuất, kể cả khi được gán quyền báo cáo hoặc đồng thời có vai trò khác. Menu cũng ẩn với Trung tâm.
- Nhân viên DVKH/PTN mặc định xem bản thân trong phòng mình. Không có bộ chọn tài khoản/phòng ban, không nhận số liệu người khác hoặc kết quả ký tự động. Chỉ có bảng công việc thuộc phòng được xem.
- Trưởng PTN mặc định xem toàn PTN, gồm lập phiếu, duyệt/gửi ký, kết quả ký và hoàn tất bổ sung. Không xem DVKH.
- Admin xem toàn hệ thống. Lãnh đạo hoặc người quản lý DVKH chỉ được xem toàn hệ thống/cấp phòng sau khi Admin chỉ định rõ; không suy ra quyền này từ việc có report.view.
- Tài khoản kiêm DVKH và PTN chưa gán phòng trực thuộc bị chặn phạm vi cá nhân cho đến khi cấu hình, tránh tự chọn phòng để mở rộng dữ liệu.
- Không thay đổi phạm vi của báo cáo tổng hợp/ký tươi hiện có. Có migration bổ sung cấu hình tài khoản, không cần seeder.

## Cấu hình bởi Admin

Vào **Người dùng → Sửa tài khoản → Phạm vi báo cáo công việc**:

1. **Phòng ban trực thuộc**: DVKH hoặc PTN. Để trống dùng vai trò hiện có nếu xác định được duy nhất một phòng.
2. **Quyền xem công việc**: mặc định theo vai trò, chỉ bản thân, các phòng được chỉ định hoặc toàn hệ thống.
3. Nếu chọn **Các phòng được chỉ định**, tích ít nhất một phòng được quản lý.

Ví dụ trưởng nhóm DVKH: phòng DVKH, quyền Các phòng được chỉ định, tích DVKH. Lãnh đạo được xem toàn bộ: cấp report.view và chọn Toàn hệ thống. Không phải đổi vai trò thành Admin.

Chỉ Admin được lưu các trường này. Endpoint cập nhật tài khoản chặn người không phải Admin gửi các trường phạm vi; màn hình tài khoản cá nhân không cho tự nâng quyền. Thay đổi ghi vào lịch sử người dùng. Các trường không nằm trong mass assignment của model User.

Phạm vi được cưỡng chế phía server cho bảng, tổng số, chi tiết và Excel. Sửa user_id, department, kind hoặc ô chi tiết ra ngoài phạm vi trả 403; không chỉ ẩn bộ lọc. Người quản lý chỉ nhận danh sách tài khoản trong phạm vi được quản lý (gồm người có lịch sử thực hiện công việc của phòng trong tháng).

## Sử dụng

1. Chọn tháng/năm, phòng ban phụ trách, tài khoản, nhóm công việc và Trung tâm của hồ sơ.
2. Chọn cách nhóm dòng theo Trung tâm (giống mẫu Excel) hoặc theo tài khoản.
3. Bấm Lọc báo cáo. Mỗi loại công việc có một bảng ngày riêng, có tổng dòng/ngày/tháng. Chủ nhật tô vàng, vẫn tính công việc phát sinh. Trung tâm không phát sinh vẫn có dòng 0.
4. Bấm số trong ô hoặc tổng dòng để xem chi tiết; bảng ngày vẫn giữ phạm vi tháng. Bỏ lọc ô chi tiết để xem toàn bộ dữ liệu đang lọc.
5. Xuất Excel gồm ba sheet: bảng ngày, chi tiết và định nghĩa/phạm vi. Excel xuất toàn bộ tháng theo bộ lọc chính, không chỉ trang hoặc ô đang xem.

## Đơn vị thống kê

Một lượt = một hồ sơ, một loại công việc, một tài khoản trong một ngày. Lặp lại log hoặc nhấn lại cùng bước trong cùng ngày không tăng số đếm. Cùng hồ sơ xử lý vào ngày khác hoặc bởi tài khoản khác là lượt riêng. Vì vậy tổng tháng không phải số phiếu duy nhất.

Hai tài khoản cùng xử lý một hồ sơ trong cùng ngày được tính hai lượt. Chuyển cách nhóm Trung tâm/tài khoản không thay đổi tổng của một loại công việc. Không cộng các loại công việc thành tổng số phiếu: một hồ sơ có thể đi qua nhiều bước.

Phòng ban công việc lấy theo bước nghiệp vụ: người có nhiều vai trò hoặc admin thao tác thay vẫn được ghi nhận ở bước đang thực hiện, không suy theo vai trò hiện tại. Các bước Trưởng PTN thuộc chung phòng PTN; link cũ department=MANAGER được chuyển thành PTN nhưng vẫn kiểm tra quyền. Phòng ban trực thuộc tài khoản dùng để giới hạn báo cáo cá nhân; danh sách phòng quản lý dùng để giới hạn báo cáo cấp phòng.

| Nhóm | Nguồn/mốc tính |
| --- | --- |
| Trung tâm gửi yêu cầu | Lịch sử gửi hoặc submitted_at/submitted_by; không tính lưu nháp |
| DVKH xác nhận chuyển PTN | Lịch sử approve; sent_to_ptn_at chỉ là nguồn dự phòng nếu thiếu lịch sử |
| PTN lập phiếu | Lịch sử lập phiếu hoặc created_at/created_by của phiếu |
| Duyệt/gửi ký số | Lịch sử duyệt vào hàng đợi, gửi ký hoặc gửi lại; cùng tài khoản/hồ sơ/ngày gộp một lượt |
| Ký số thành công | signed_at của phiếu, nhóm kết quả hệ thống, không gán cho người bấm kiểm tra |
| DVKH duyệt bổ sung ký tươi | Lịch sử approve hoặc approved_at/approved_by |
| PTN hoàn tất bổ sung ký tươi | Lịch sử complete hoặc completed_at/completed_by; tính yêu cầu, không nhân số bản |

Ngày dùng múi giờ ứng dụng Asia/Ho_Chi_Minh. Chỉ tính sự kiện nằm trong tháng đã chọn. Công việc vẫn được giữ khi hồ sơ về sau bị xóa mềm, trả lại, thu hồi hoặc thay thế; đây không phải báo cáo phiếu đang hiệu lực. Ví dụ 1.729 kết quả ký số có thể gồm 1.673 phiếu phát hành và 56 phiếu sau đó đã thu hồi.

## Chất lượng dữ liệu cũ

- Dùng subject_id/object_id của lịch sử, không bóc số hồ sơ từ câu mô tả tự do.
- Ưu tiên lịch sử; mốc nghiệp vụ bù dữ liệu thiếu, không tạo thêm bản ghi trong database khi xem báo cáo.
- Thiếu người xác nhận DVKH thì ghi Chưa xác định / hệ thống, không lấy người tạo yêu cầu thay thế. Không lấy signed_by dạng tên hiển thị hoặc tài khoản chạy scheduler để suy ra người ký.
- Khi lọc một tài khoản, kết quả ký số chưa xác định người ký không thuộc kết quả của tài khoản đó. Muốn xem tổng kết quả ký chọn Tất cả tài khoản.
- Các lần xử lý cũ đã mất log và bị ghi đè thời điểm không thể phục dựng chính xác; không tự tạo số liệu cho các lần đó.

## Ghi nhận mới và vận hành

Tận dụng bảng activity_log hiện có: ActivityLogger bổ sung properties.work cho các thao tác phù hợp, gồm loại công việc, bước phòng ban, ID/tên tài khoản, ID yêu cầu/Trung tâm. Đánh dấu event=work_report để đọc metadata mà không phải phân tích các JSON chứng thư cũ rất lớn. Không thay đổi mô tả, dữ liệu old/new, nguồn log hay luồng xử lý nghiệp vụ. Những vị trí đã ghi log trong transaction tiếp tục có tính chất đó; nguồn dự phòng vẫn cần thiết cho các mốc cũ thiếu log.

Không thêm job, không sửa số phiếu, không quét hay render PDF, không tự tạo hoặc cập nhật dữ liệu cũ. Chỉ đọc trường cần dùng, tránh nạp toàn bộ dữ liệu SmartCA. Dữ liệu lịch sử nhiều có thể khiến lần mở báo cáo/xuất tháng lớn chậm hơn báo cáo trạng thái; không mở endpoint công khai cho Trung tâm.

## Migration và triển khai

Migration chỉ thêm ba cột nullable vào users: work_department, work_report_scope, work_report_departments. Không backfill tài khoản, không thay role/password/session hoặc dữ liệu phiếu. Khi chưa cấu hình, dùng mặc định an toàn nêu ở trên. Lưu cấu hình tài khoản trước khi migrate sẽ nhận thông báo cần chạy migration, không ghi tài khoản dở dang.

Trên VPS, sau khi sao lưu và pull code, chạy:

```bash
php artisan migrate --path=database/migrations/2026_11_03_000001_add_work_report_scope_to_users.php --force
```

Sau đó tạo lại cache theo quy trình triển khai hiện có. Không cần PermissionSeeder cho thay đổi này. Không chạy seed data test, refresh/fresh hoặc reset database.

Đã chạy riêng migration này trên MySQL local sau khi sao lưu users và migrations tại private/backups/work-report-scope-20261103-144242-856272/users-and-migrations.sql. Hash toàn bộ trường tài khoản cũ trước/sau giống nhau; giữ nguyên 23 tài khoản, 1758 yêu cầu, 1731 phiếu và 36 bổ sung. Không sửa cấu hình của bất kỳ tài khoản thật nào; DVKH quản lý/lãnh đạo cần Admin cấu hình theo nhu cầu.
