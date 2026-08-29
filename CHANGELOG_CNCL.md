# CNCL Update Log

File này dùng để ghi lại các cập nhật chức năng/kỹ thuật của hệ thống CNCL từ ngày 2026-08-29.

## Quy ước ghi log

- Mỗi lần cập nhật thêm một mục mới ở đầu file.
- Ghi rõ ngày, nhóm chức năng, file chính đã sửa, nội dung thay đổi và kết quả kiểm tra.
- Nếu có lỗi chưa xử lý xong, ghi vào phần "Ghi chú".

## 2026-08-29

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
