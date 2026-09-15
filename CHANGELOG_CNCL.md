# CNCL Update Log

File này dùng để ghi lại các cập nhật chức năng/kỹ thuật của hệ thống CNCL từ ngày 2026-08-29.

## Quy ước ghi log

- Mỗi lần cập nhật thêm một mục mới ở đầu file.
- Ghi rõ ngày, nhóm chức năng, file chính đã sửa, nội dung thay đổi và kết quả kiểm tra.
- Nếu có lỗi chưa xử lý xong, ghi vào phần "Ghi chú".

## 2026-09-14

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
