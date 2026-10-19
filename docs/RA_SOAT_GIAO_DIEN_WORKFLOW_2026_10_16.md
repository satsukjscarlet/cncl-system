# Rà soát giao diện và quy trình ngày 16/10/2026

## Phạm vi

- Trung tâm: dashboard, tạo/sửa yêu cầu, danh sách yêu cầu, chi tiết nháp, chờ xử lý, trả lại và phát hành.
- DVKH: dashboard, danh sách yêu cầu, chi tiết chờ xử lý và yêu cầu PTN trả lại.
- PTN và trưởng PTN: lập phiếu, trả lại, chờ duyệt, chờ SmartCA, phiếu đã phát hành.
- Kiểm tra giao diện trên viewport 1440 x 900 và 390 x 844, với tên khách hàng/công trình dài và 35 dòng sản phẩm.

## Các điểm đã xử lý

1. Dùng chung thanh thao tác cho bốn màn chi tiết. Thanh tự căn theo sidebar và chừa khoảng đệm theo chiều cao thực tế; nằm dưới modal/loading.
2. Đồng bộ bảng sản phẩm: đơn vị tính, tìm mã/tên, số dòng tìm thấy, thông báo không có kết quả, tiêu đề bám khi cuộn. Trên màn hình hẹp, cuộn ngang trong bảng để tên sản phẩm không bị ép thành cột quá nhỏ.
3. Chi tiết yêu cầu Trung tâm: làm nổi bật lý do DVKH trả lại, thời gian gửi và tên người tạo yêu cầu; giữ nút sửa/gửi nháp và liên kết phiếu sau phát hành.
4. Chi tiết DVKH: giữ cam kết nhận/lấy hàng, cảnh báo hóa đơn trùng, thông tin trả lại; thêm ngày gửi/chuyển và tên người tạo yêu cầu.
5. Sửa cách nhúng nội dung confirm chuyển PTN bằng `@js` ở danh sách và chi tiết DVKH. Cách nhúng JSON thô trước đây tạo dấu nháy kép bên trong thuộc tính HTML, làm hỏng handler xác nhận. Kiểm tra Cancel không gửi POST và không giữ loading.
6. Sửa bộ lọc danh sách yêu cầu bị tràn ngang ở desktop; căn lại tiêu đề danh sách.
7. Dashboard dùng Chart.js 4.5.1 lưu tại `public/vendor/chartjs/`, kèm giấy phép MIT, thay vì tải bản không cố định từ CDN. Trình duyệt kiểm tra biểu đồ đã khởi tạo và canvas có nội dung.

## Kiểm tra nghiệp vụ

Kết quả cuối: 51 test với 743 assertions thành công; 34/34 trường hợp trình duyệt thành công trên desktop/mobile. Có kiểm tra Cancel không phát sinh POST và không giữ loading, sidebar/thanh thao tác căn đúng và biểu đồ có nội dung.

- Nháp nhiều ngày không làm SLA bắt đầu trước khi gửi DVKH.
- Gửi DVKH, DVKH chuyển PTN, PTN lập phiếu, trưởng PTN trả PTN, PTN trả DVKH, DVKH trả Trung tâm.
- Trung tâm sửa/gửi lại; DVKH duyệt; PTN lập phiếu mới; phiếu đã trả lại vẫn giữ lịch sử.
- Trưởng PTN đưa vào chờ ký, gửi ký, nhận kết quả và phát hành.
- Trung tâm không xem PDF chưa ký qua đường dẫn trực tiếp; sau phát hành được xem bản đã lưu.
- Phân quyền giữa các Trung tâm, việc ẩn nháp với bộ phận nội bộ, phê duyệt thiết bị đăng nhập và thu hồi phiên đăng nhập.

Phần SmartCA dùng mock và chặn HTTP ngoài trong test, kiểm tra luồng phần mềm chứ không xác minh chữ ký mật mã hoặc kết nối VNPT thật. Mail và lưu trữ PDF dùng fake trong test. Database là SQLite `:memory:` theo `phpunit.xml`, có chốt bảo vệ trong `tests/TestCase.php`.

## Chạy lại trên máy phát triển

```powershell
$env:CNCL_UI_SNAPSHOT_DIR = Join-Path $env:TEMP 'cncl-workflow-ui-review'
php vendor/phpunit/phpunit/phpunit tests/Feature/CertificateWorkflowTest.php tests/Feature/RoleWorkspaceAccessTest.php
```

Nếu Laravel đang có config cache, bộ bảo vệ test sẽ dừng và yêu cầu xử lý cache trên máy phát triển trước khi chạy.

Để kiểm tra trình duyệt, có thể cài công cụ riêng trong thư mục tạm, không thay đổi dependency của ứng dụng:

```powershell
npm install --prefix "$env:TEMP\cncl-ui-review-tools" --no-save --package-lock=false playwright
$env:CNCL_PLAYWRIGHT_MODULE = Join-Path $env:TEMP 'cncl-ui-review-tools\node_modules\playwright'
$env:CNCL_BROWSER_BIN = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
node scripts/check-workflow-ui.cjs
```

Điều chỉnh `CNCL_BROWSER_BIN` theo Chrome/Edge đã cài. Script chỉ phục vụ HTML test và asset public qua Playwright route; chặn mọi request gửi dữ liệu và không truy cập server ứng dụng. Ảnh nằm tại thư mục `screenshots`, kết quả tại `browser-results.json` trong thư mục snapshot. Có thể giới hạn các trang bằng biến `CNCL_UI_PAGES`, phân tách tên bằng dấu phẩy.

## Cập nhật server

Phần giao diện này không có migration hay seeder mới. Cần đưa đầy đủ các partial/component và `public/vendor/chartjs/` lên server cùng code, sau đó chạy `php artisan view:clear`. Không cần chạy seeder tổng hoặc tạo lại dữ liệu test trên server online.
