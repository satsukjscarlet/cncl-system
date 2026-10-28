# Báo cáo dung lượng hệ thống

Chỉ Admin truy cập **Dung lượng hệ thống** tại `/reports/storage-usage`.
Mở trang hoặc bấm **Quét lại** để quét; không có scheduler/job mới.

## Phạm vi và phân loại

- Đếm file vật lý trong `storage`, không đọc nội dung PDF; bỏ qua symlink.
- PDF được nhóm theo thư mục và loại: đã ký, chưa ký, trung gian chuẩn bị ký, PDF khác.
- Đối chiếu `quality_certificates.pdf_path` và `signed_at` (kể cả bản ghi đã xóa mềm); kết hợp cấu trúc đường dẫn SmartCA do ứng dụng tạo. Đây không phải kiểm định chữ ký mật mã.
- File `unsigned.pdf` là chưa ký, `prepared.pdf` là trung gian. PDF không rõ loại không được tự coi là chưa ký.
- PDF render trực tiếp không lưu thành file sẽ không được tính. File ngoài storage/cloud không nằm trong báo cáo.
- Số liệu database lấy từ MySQL/MariaDB information_schema; chỉ gồm dung lượng ước tính dữ liệu/chỉ mục.

## Lịch sử trong tháng

- Lưu số tổng hợp sau mỗi lượt quét file đầy đủ vào `storage/app/private/system-metrics/<pham-vi>-YYYY-MM.jsonl`.
- Không migration, không seed, không thay đổi dữ liệu phiếu/PDF; cần quyền ghi thư mục lịch sử. Thư mục này được loại khỏi tổng storage để không làm sai số bởi chính việc quét.
- Phạm vi lịch sử phụ thuộc đường dẫn cài đặt, APP_URL và cấu hình database. Đổi các thông tin này sẽ dùng mốc mới để tránh trộn lịch sử server khác.
- Biểu đồ/bảng theo ngày sử dụng lần quét đầy đủ cuối cùng trong ngày; không nội suy ngày chưa quét.
- Chênh lệch so với lần trước chỉ xét trong tháng hiện tại; mốc đầu tiên trong tháng không đồng nghĩa dung lượng tại đầu tháng.
- Đây là biến động tồn kho file/dung lượng, không phải tổng phát sinh hay số phiếu mới. Xóa/thay thế file có thể làm chênh lệch âm.
- Database không truy cập được sẽ không có mốc database cho lần đó, không gán bằng 0.
- Quét giới hạn 20 giây cho phần file. Quét thiếu dữ liệu không được dùng làm mốc; màn hình hiển thị cảnh báo. Chống quét đồng thời vẫn áp dụng cho toàn bộ lượt quét và ghi lịch sử.
- Lịch sử chứa số liệu tổng hợp, không chứa nội dung PDF hay dữ liệu khách hàng. Được lưu trong vùng private, không có nút tải/xóa file gốc.
