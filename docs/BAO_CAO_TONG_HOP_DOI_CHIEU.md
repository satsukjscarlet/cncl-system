# Đối chiếu báo cáo tổng hợp CNCL

## Phạm vi

- Mỗi yêu cầu lấy phiếu có ID lớn nhất chưa xóa. Phiếu mới nhất trả lại/hủy vẫn là phiếu hiện tại; không lấy ngược phiếu đã phát hành cũ.
- Danh sách mặc định bỏ phiên bản lịch sử. Chọn **Gồm phiếu lịch sử** để tra cứu đầy đủ; lúc đó tổng danh sách có thể lớn hơn số yêu cầu có phiếu.
- Yêu cầu đã xóa không xuất hiện trong báo cáo/danh sách.
- Bộ lọc ngày mặc định theo **Ngày tạo yêu cầu** trên cả hai màn hình. Có thể đổi sang **Ngày lập phiếu** hoặc **Ngày ký số**. Ngày kết thúc bao gồm cả ngày đó.
- Nếu lọc ngày lập/ký, yêu cầu chưa lập/chưa ký không có ngày phù hợp nên không được tính.

## Cách đọc số liệu

Tổng yêu cầu = yêu cầu chưa lập phiếu + yêu cầu có phiếu hiện tại.

Phiếu hiện tại = đã phát hành + hủy/thu hồi + trạng thái khác.

Yêu cầu hoàn tất là trạng thái của yêu cầu, không đồng nghĩa tất cả phiếu còn hiệu lực: yêu cầu hoàn tất có thể đã có phiếu bị thu hồi.

Các ô trạng thái phiếu là nhóm để chuyển bộ lọc: giữ ngày/Trung tâm/trạng thái yêu cầu, nhưng không tự giới hạn bởi trạng thái phiếu đang chọn. Các KPI và bảng chi tiết thì áp dụng trạng thái phiếu đang chọn.

SLA **Chưa bật** không có nghĩa là không có yêu cầu quá hạn; hệ thống chưa có cấu hình theo dõi đang bật cho các bước đó.

## Đối chiếu

1. Chọn cùng loại ngày, khoảng ngày, Trung tâm và trạng thái yêu cầu.
2. Bấm **Xem danh sách** trong KPI phát hành/hủy: đường dẫn mang đúng bộ lọc và chỉ lấy phiếu hiện tại.
3. Không bật lịch sử khi so sánh với báo cáo tổng hợp.
4. Excel xuất toàn bộ dòng yêu cầu khớp bộ lọc, không chỉ trang đang xem; số phiếu và trạng thái lấy cùng phiên bản với màn hình.
5. Bảng thống kê tháng/năm là bảng riêng: cả năm theo ngày ký số, Trung tâm đang chọn, phiếu hiện tại đã phát hành. Không áp dụng khoảng ngày/trạng thái phía trên. Bấm số trong từng ô để mở đúng tháng và Trung tâm.

## Triển khai

Không cần migration hoặc seeder. Sau khi cập nhật code, xóa và tạo lại cache theo quy trình triển khai hiện có. Không chạy lệnh làm mới hoặc xóa database.
