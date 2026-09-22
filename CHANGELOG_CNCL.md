# CNCL Update Log

File này dùng để ghi lại các cập nhật chức năng/kỹ thuật của hệ thống CNCL từ ngày 2026-08-29.

## Quy ước ghi log

- Mỗi lần cập nhật thêm một mục mới ở đầu file.
- Ghi rõ ngày, nhóm chức năng, file chính đã sửa, nội dung thay đổi và kết quả kiểm tra.
- Nếu có lỗi chưa xử lý xong, ghi vào phần "Ghi chú".

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
