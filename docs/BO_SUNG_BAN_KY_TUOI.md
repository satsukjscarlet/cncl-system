# Bổ sung bản ký tươi sau phát hành

## Thao tác

1. Trung tâm mở chi tiết phiếu đã ký số/phát hành, bấm **Yêu cầu bổ sung bản ký tươi** trên thanh thao tác cố định để mở form, nhập số bản, lý do và ghi chú nhận bản giấy rồi gửi DVKH. Phiếu không cần có yêu cầu ký tươi ban đầu. Admin/DVKH/PTN thấy nút **Bổ sung bản ký tươi** để xem lịch sử; không có form gửi thay Trung tâm.
2. DVKH mở menu **Bổ sung bản ký tươi** hoặc thông báo/Việc cần làm. Xác nhận nguyên số bản để chuyển PTN, hoặc trả lại kèm lý do.
3. Trung tâm sửa yêu cầu được trả lại và bấm **Lưu và gửi DVKH**. Không tạo thêm yêu cầu khác khi phiếu đang có một yêu cầu chưa kết thúc.
4. PTN mở yêu cầu **Chờ PTN hoàn tất**, chọn In đơn/In bộ và mở file. Tổ chức in/ký theo quy trình nội bộ, rồi bấm **Xác nhận hoàn tất bản ký tươi**. Không có bước Nhận xử lý.

Mỗi yêu cầu được mở cả In đơn và In bộ khi đang chờ PTN, không tăng số bản được duyệt. Mỗi lượt mở ghi đúng mẫu và số trang vào lịch sử; báo cáo và bộ lọc mẫu dùng mẫu mở gần nhất để không cộng đôi nhu cầu. Số trang của từng mẫu được đối chiếu độc lập với lần mở trước của chính mẫu đó. Khi số trang thay đổi, hệ thống chặn mở lại mẫu đó để tránh sai số; liên hệ quản trị viên.

Hoàn tất không có nghĩa đã bàn giao. Không suy ra số bản thực in từ số lượt mở PDF. Phiếu CNCL gốc, số phiếu, nội dung, số bản ban đầu và PDF SmartCA không bị thay thế. Nghiệp vụ này không tạo phiếu CNCL mới, không gửi ký số hoặc gửi lại mail phát hành.

## Quyền và trạng thái

- Trung tâm: chỉ dữ liệu của chính Trung tâm, cần quyền tạo yêu cầu để xin bổ sung; chỉ sửa/hủy khi Chờ DVKH hoặc DVKH trả lại.
- DVKH: dùng quyền `dvkh.process`, duyệt/trả lại/hủy; không tự thay số bản hoặc hoàn tất thay PTN.
- PTN: cần `ptn.process` và `certificate.print`, mở file và hoàn tất. Trưởng PTN không phải duyệt thêm; tài khoản chỉ có quyền ký số không tự có quyền hoàn tất bổ sung.
- Admin kế thừa các quyền xử lý hiện có. Người chỉ xem phiếu được xem lịch sử; báo cáo/xuất Excel vẫn theo quyền báo cáo riêng.
- Sau khi PTN mở file, không trả lại để sửa. DVKH muốn hủy phải nhập lý do và xác nhận đã phối hợp PTN dừng bản giấy. Trung tâm không có quyền tự hủy bước này.
- Phiếu bị thu hồi/hủy/thay thế, xóa mềm hoặc đang có yêu cầu cấp lại thì chặn tạo/duyệt/in/hoàn tất. Lịch sử vẫn còn; DVKH có thể hủy yêu cầu bổ sung không còn đủ điều kiện.
- Một phiếu chỉ có một yêu cầu chưa kết thúc, kể cả đang trả lại. Hoàn tất/hủy giải phóng để tạo lần tiếp theo. Không hỗ trợ hoàn tất từng phần.

Thông báo dẫn tới đúng yêu cầu bổ sung và kiểm tra lại quyền/phạm vi khi mở. Việc cần làm đếm yêu cầu cần DVKH/PTN hoặc Trung tâm sửa, không đếm phiếu không đủ điều kiện xử lý.

Danh sách có lọc nhanh theo trạng thái và sắp xếp bằng tiêu đề các cột mã, số bản, trạng thái, ngày gửi/duyệt/hoàn tất. Bộ đếm trạng thái giữ các bộ lọc khác và phạm vi Trung tâm; không chỉ đếm trang hiện tại. Có thể tìm mã BSKT đầy đủ, số phiếu/yêu cầu, khách hàng/công trình, người gửi hoặc đơn vị bán hàng. Mở Bộ lọc nâng cao để chọn ngày, mẫu in, Trung tâm và đơn vị bán hàng.

Chi tiết hiển thị tiến trình ba bước, thông tin phiếu/khách hàng/công trình, nội dung bổ sung và lịch sử. Lý do DVKH trả lại nằm ở đầu trang. Các nút Lưu/gửi, DVKH xác nhận/trả lại, PTN hoàn tất nằm ở thanh cố định; Hủy nằm trong Thao tác khác và phải nhập lý do trong hộp thoại. Điều kiện phân quyền/nghiệp vụ không thay đổi.

## Báo cáo

Trong **Báo cáo ký tươi** có liên kết **Báo cáo bổ sung bản ký tươi**. Báo cáo cũ tiếp tục phản ánh nhu cầu ban đầu theo ngày ký số, không tự cộng bổ sung vào tháng ký số cũ. Báo cáo tổng hợp phát hành không tăng số phiếu.

Báo cáo bổ sung có bộ lọc Trung tâm (ẩn với tài khoản Trung tâm), đơn vị bán hàng, trạng thái, mẫu in, từ khóa và khoảng ngày theo Ngày gửi bổ sung / Ngày DVKH duyệt / Ngày PTN hoàn tất / Ngày ký số. Có thống kê theo Trung tâm, tháng của loại ngày đã chọn, chi tiết phân trang và Excel cùng bộ lọc.

- Bản bổ sung đã duyệt: tổng của Chờ PTN và Đã hoàn tất, chỉ phiếu hiện còn đủ điều kiện. Không cộng đang chờ DVKH, trả lại hoặc đã hủy.
- Bản hoàn tất đã nằm trong bản được duyệt; không cộng thêm lần nữa.
- Bản ban đầu chỉ cộng một lần/phiếu xuất hiện trong kết quả lọc. Đây là thông tin nền, không phải số bản phát sinh trong kỳ. Tổng nhu cầu trong kết quả = bản ban đầu + bổ sung đã duyệt trong kết quả; không phải tổng lũy kế toàn hệ thống nếu đang giới hạn kỳ/trạng thái.
- Tờ bổ sung dự kiến = số bản x số trang của mẫu mở gần nhất; tách In đơn và In bộ. Bản chưa mở file không được đoán số trang/tờ. Không cộng hai phương án cho cùng một yêu cầu; không coi đây là thống kê số bản thực in.
- Một phiếu có nhiều lần bổ sung chỉ tính một phiếu trong tổng. Số phiếu của các tháng có thể trùng nhau, không cộng số phiếu từng tháng để thay tổng phiếu không trùng.
- Lịch sử yêu cầu hủy/phiếu thu hồi vẫn xem được nhưng không cộng số bản bổ sung của chúng vào số liệu hợp lệ. Trạng thái là trạng thái hiện tại, không phải báo cáo hồi tố tại ngày quá khứ.

## Triển khai an toàn

Backup database và kiểm tra bản backup trước. Không chạy migrate:fresh/refresh/reset, không seed data test, không xóa database. Đảm bảo migration các phiên bản trước đã được triển khai.

Trong khoảng bảo trì, chạy từng lệnh; nếu lỗi thì dừng để kiểm tra, không bật ứng dụng khi schema chưa hoàn thành:

```sh
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate:status --path=database/migrations/2026_10_26_000001_create_hard_copy_supplements.php
php artisan migrate --path=database/migrations/2026_10_26_000001_create_hard_copy_supplements.php --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Không cần PermissionSeeder: dùng quyền hiện có, không chỉnh phân quyền trên VPS. Migration chỉ thêm hai bảng và cột nullable trong print_logs; các nhật ký in cũ giữ nguyên. Không có job mới; dùng thông báo và polling việc cần làm hiện tại.

Không rollback bảng mới sau khi đã phát sinh dữ liệu; nếu cần quay lại code cũ, giữ nguyên bảng/cột bổ sung. Tính năng mới cần migration trước khi người dùng vào màn hình, thông báo và feed việc cần làm.

## Nghiệm thu

- Hai Trung tâm: không xem/sửa yêu cầu của nhau kể cả đổi URL hoặc bộ lọc.
- Số bản trống/0/âm/thập phân bị chặn; bấm gửi trùng chỉ có một yêu cầu đang mở.
- DVKH trả lại có lý do; Trung tâm sửa/gửi lại; DVKH duyệt; PTN in và hoàn tất.
- In không tự hoàn tất; được mở cả hai mẫu, giữ số trang độc lập và lịch sử; bấm hoàn tất lần hai không ghi thêm sự kiện.
- Hủy/thu hồi/cấp lại giữa các bước chặn xử lý tiếp; vẫn xem lịch sử.
- Phiếu ban đầu không yêu cầu ký tươi vẫn xin bổ sung và lên báo cáo.
- Phiếu ký tháng trước, bổ sung tháng này xuất hiện khi lọc Ngày gửi tháng này; số phiếu không đếm trùng qua nhiều lần bổ sung.
- Đối chiếu Excel theo cùng bộ lọc và thử số tờ riêng In đơn/In bộ.
