# Checklist nghiệm thu hệ thống CNCL

Phiên bản checklist: 2.0  
Ngày cập nhật: 18/08/2026  
Tài liệu liên quan: `docs/HUONG_DAN_SU_DUNG_CNCL.md`, `docs/test-accounts.md`

Tài liệu này dùng để kiểm thử thủ công hệ thống cấp Phiếu Chứng nhận Chất lượng theo phiên bản mới nhất, gồm phân quyền theo vai trò, yêu cầu cấp phiếu, DVKH, PTN, Trưởng PTN, ký số VNPT SmartCA, cấp lại phiếu, import Excel, thông báo, dashboard việc cần làm, email, PDF và báo cáo.

## Quy ước kết quả

- `[ ]` Chưa kiểm tra
- `[x]` Đạt
- `[!]` Có lỗi cần sửa
- `[?]` Cần xác nhận nghiệp vụ

Khi ghi lỗi cần kèm: tài khoản, vai trò, URL màn hình, mã yêu cầu/phiếu, thao tác, kết quả mong muốn, kết quả thực tế và ảnh chụp nếu có.

## 1. Chuẩn bị nghiệm thu

- [ ] Đã chạy migration mới nhất.
- [ ] Đã chạy seeder phân quyền.
- [ ] Đã có trung tâm: Nam Phương `NP`, Tam Phước `TP`, Hồng Phước `HP`, Hà Dung `HD`, Thái Hoà `TH`.
- [ ] Đã có tài khoản test theo `docs/test-accounts.md`.
- [ ] Đã có danh mục nhóm sản phẩm.
- [ ] Đã có danh mục sản phẩm đủ lớn để test import và phiếu nhiều dòng.
- [ ] Đã có danh mục tiêu chuẩn chất lượng.
- [ ] Đã có danh mục lý do yêu cầu gấp.
- [ ] Đã có dữ liệu test workflow/báo cáo ở nhiều trạng thái.
- [ ] Đã cấu hình mail test.
- [ ] Đã cấu hình VNPT SmartCA demo nếu test ký số thật.
- [ ] Đã kiểm tra `APP_URL` đúng URL đang test.
- [ ] Đã bật queue/job nếu môi trường test dùng queue thật.

## 2. Đăng nhập, đăng xuất, tài khoản

- [ ] Đăng nhập bằng `username`, không cần nhập email.
- [ ] Đăng nhập đúng tài khoản chuyển vào Dashboard.
- [ ] Đăng nhập sai mật khẩu hiển thị lỗi rõ ràng.
- [ ] Tài khoản bị khóa không đăng nhập được.
- [ ] Đăng xuất thành công.
- [ ] Sau đăng xuất không truy cập được màn hình nội bộ.
- [ ] Email user có thể trùng trong giai đoạn test.
- [ ] Username không được trùng.
- [ ] Mật khẩu reset từ Admin đăng nhập được.
- [ ] Màn hồ sơ cá nhân mở được.

## 3. Phân quyền và sidebar

### 3.1 Admin

- [ ] Admin thấy đầy đủ nhóm menu: Dashboard, Danh mục, Nghiệp vụ, Phiếu CNCL, Báo cáo, Hệ thống.
- [ ] Admin thấy màn Trung tâm phân phối.
- [ ] Admin thấy màn Nhóm sản phẩm.
- [ ] Admin thấy màn Sản phẩm.
- [ ] Admin thấy màn Khách hàng - Công trình.
- [ ] Admin thấy màn Tiêu chuẩn chất lượng.
- [ ] Admin thấy màn Lý do yêu cầu gấp.
- [ ] Admin thấy màn Yêu cầu cấp phiếu.
- [ ] Admin thấy màn DVKH kiểm tra.
- [ ] Admin thấy màn PTN lập phiếu.
- [ ] Admin thấy màn Trưởng PTN duyệt ký.
- [ ] Admin thấy màn Danh sách phiếu.
- [ ] Admin thấy màn Lịch sử in ký tươi.
- [ ] Admin thấy màn Báo cáo tổng hợp.
- [ ] Admin thấy màn Cấu hình SLA.
- [ ] Admin thấy màn Cấu hình hệ thống.
- [ ] Admin thấy màn Người dùng.
- [ ] Admin thấy màn Phân quyền.
- [ ] Admin thấy màn Log thao tác.
- [ ] Admin thấy màn Log đăng nhập.
- [ ] Admin thấy khối dữ liệu API VNPT SmartCA trong chi tiết phiếu.

### 3.2 Trung tâm phân phối

- [ ] Trung tâm chỉ thấy menu cần cho trung tâm.
- [ ] Trung tâm không thấy màn cấu hình hệ thống.
- [ ] Trung tâm không thấy màn phân quyền.
- [ ] Trung tâm không thấy dữ liệu API VNPT SmartCA.
- [ ] Trung tâm chỉ xem được dữ liệu thuộc trung tâm của mình.
- [ ] Trung tâm không truy cập được dữ liệu trung tâm khác bằng URL trực tiếp.
- [ ] Trung tâm tạo yêu cầu cấp phiếu được.
- [ ] Trung tâm tạo/import khách hàng thuộc trung tâm mình được.
- [ ] Trung tâm không ký số được.

### 3.3 DVKH

- [ ] DVKH thấy màn DVKH kiểm tra.
- [ ] DVKH thấy yêu cầu chờ DVKH.
- [ ] DVKH xác nhận chuyển PTN được.
- [ ] DVKH trả lại yêu cầu được.
- [ ] DVKH xác nhận hủy/thu hồi phiếu cũ trong luồng cấp lại được.
- [ ] DVKH không gửi ký số được.
- [ ] DVKH không sửa phân quyền được.

### 3.4 PTN

- [ ] PTN thấy màn PTN lập phiếu.
- [ ] PTN lập phiếu từ yêu cầu đã được DVKH xác nhận được.
- [ ] PTN lập phiếu trực tiếp được.
- [ ] PTN không thấy nút gửi ký số.
- [ ] PTN không thấy nút kiểm tra kết quả ký.
- [ ] PTN không thấy dữ liệu API VNPT SmartCA.

### 3.5 Trưởng PTN

- [ ] Trưởng PTN thấy màn Trưởng PTN duyệt ký.
- [ ] Trưởng PTN thấy danh sách phiếu chờ ký.
- [ ] Trưởng PTN gửi ký VNPT SmartCA được.
- [ ] Trưởng PTN kiểm tra kết quả ký được.
- [ ] Trưởng PTN kiểm tra kết quả ký hàng loạt được nếu có quyền.
- [ ] Trưởng PTN trả lại phiếu được.
- [ ] Trưởng PTN không sửa phân quyền được.

### 3.6 Lãnh đạo/Viewer

- [ ] Lãnh đạo xem được dashboard/báo cáo theo quyền.
- [ ] Viewer chỉ xem, không thấy nút thêm/sửa/xóa/import/export nếu không có quyền.
- [ ] Viewer truy cập URL thao tác trực tiếp bị chặn.

## 4. Dashboard, việc cần làm và thông báo

- [ ] Dashboard hiển thị không lỗi giao diện.
- [ ] Số liệu dashboard đúng theo dữ liệu hiện có.
- [ ] Widget việc cần làm hiển thị theo đúng vai trò đăng nhập.
- [ ] Trung tâm thấy công việc liên quan yêu cầu/phiếu của trung tâm mình.
- [ ] DVKH thấy số yêu cầu chờ kiểm tra.
- [ ] DVKH thấy số yêu cầu gấp.
- [ ] DVKH thấy số yêu cầu trùng hóa đơn.
- [ ] DVKH thấy số yêu cầu cảnh báo/quá hạn SLA.
- [ ] PTN thấy số yêu cầu chờ lập phiếu.
- [ ] PTN thấy số yêu cầu gấp/cảnh báo SLA.
- [ ] Trưởng PTN thấy số phiếu chờ ký.
- [ ] Trưởng PTN thấy số phiếu đang chờ kết quả SmartCA.
- [ ] Bấm vào từng mục việc cần làm mở đúng danh sách đã lọc.
- [ ] Không còn hiển thị trùng dòng "mở dashboard công việc".
- [ ] Dropdown thông báo hiển thị số chưa đọc.
- [ ] Bấm thông báo mở đúng màn hình liên quan.
- [ ] Bấm thông báo không còn quyền/dữ liệu đã đổi thì báo lỗi dễ hiểu.
- [ ] Đánh dấu một thông báo đã đọc được.
- [ ] Đánh dấu tất cả đã đọc được.
- [ ] Browser notification hiện khi có thông báo mới nếu trình duyệt đã cấp quyền.
- [ ] Browser notification không làm chậm màn hình chính.
- [ ] Feed thông báo và feed việc cần làm không gọi quá nặng khi refresh nhiều lần.

## 5. Giao diện chung và loading

- [ ] Sidebar không che nội dung.
- [ ] Header không lệch.
- [ ] Card bộ lọc ở các màn danh sách cân đối.
- [ ] Bảng dữ liệu không phá khung ở màn desktop.
- [ ] Select2 cùng chiều cao với input thường.
- [ ] Select2 trong màn create/update không bị nhỏ hơn các ô khác.
- [ ] Nút Thêm/Sửa/Xóa/Import/Export tự ẩn theo quyền.
- [ ] Các nút thao tác có loading khi gửi form lâu.
- [ ] Bấm Cancel trên hộp confirm không bật loading.
- [ ] Loading hiển thị tiếng Việt có dấu.
- [ ] Khi đang kiểm tra ký/gửi mail, màn hình khóa thao tác để tránh bấm lặp.
- [ ] Các màn danh sách load ở mức chấp nhận được với dữ liệu test lớn.
- [ ] Bộ lọc không bị lệch ở màn `Yêu cầu cấp phiếu`.
- [ ] Bộ lọc không bị lệch ở màn `DVKH kiểm tra`.
- [ ] Bộ lọc không bị lệch ở màn `PTN lập phiếu`.
- [ ] Bộ lọc không bị lệch ở màn `Danh sách phiếu`.
- [ ] Trạng thái rỗng hiển thị dễ hiểu.

## 6. Danh mục hệ thống

### 6.1 Trung tâm phân phối

- [ ] Xem danh sách trung tâm.
- [ ] Thêm trung tâm mới.
- [ ] Sửa thông tin trung tâm.
- [ ] Xóa trung tâm khi chưa phát sinh dữ liệu.
- [ ] Không xóa được trung tâm đã phát sinh dữ liệu quan trọng.
- [ ] Mã trung tâm không trùng.
- [ ] Email trung tâm hợp lệ nếu nhập.

### 6.2 Nhóm sản phẩm

- [ ] Xem danh sách nhóm sản phẩm.
- [ ] Import nhóm sản phẩm từ Excel.
- [ ] Export nhóm sản phẩm.
- [ ] Tải file mẫu nhóm sản phẩm.
- [ ] Mã nhóm sản phẩm tự sinh/đúng định dạng theo seeder.
- [ ] Cảnh báo lỗi import rõ ràng.

### 6.3 Sản phẩm

- [ ] Xem danh sách sản phẩm.
- [ ] Thêm sản phẩm.
- [ ] Sửa sản phẩm.
- [ ] Xóa sản phẩm khi chưa phát sinh dữ liệu.
- [ ] Import số lượng lớn sản phẩm không timeout.
- [ ] Export sản phẩm.
- [ ] Tải file mẫu sản phẩm.
- [ ] Mã sản phẩm không trùng.
- [ ] Sản phẩm liên kết đúng nhóm sản phẩm.
- [ ] Số lượng trong dữ liệu test là số nguyên.

### 6.4 Tiêu chuẩn chất lượng

- [ ] Xem danh sách tiêu chuẩn.
- [ ] Thêm/sửa/xóa tiêu chuẩn theo quyền.
- [ ] Import/export tiêu chuẩn.
- [ ] Tải mẫu import.
- [ ] Tiêu chuẩn đang hoạt động hiển thị trong chọn sản phẩm/phiếu.

### 6.5 Lý do yêu cầu gấp

- [ ] Xem danh mục lý do gấp.
- [ ] Thêm lý do gấp.
- [ ] Sửa lý do gấp.
- [ ] Khóa/ngừng sử dụng lý do gấp.
- [ ] Lý do không hoạt động không hiển thị khi tạo yêu cầu mới.
- [ ] Bật yêu cầu gấp mới bắt buộc chọn lý do.
- [ ] Không bật yêu cầu gấp thì không bắt chọn lý do.

## 7. Khách hàng - Công trình

- [ ] Admin xem được khách hàng toàn hệ thống.
- [ ] Trung tâm chỉ xem được khách hàng do trung tâm mình quản lý/tạo.
- [ ] Trung tâm tạo khách hàng mới được.
- [ ] Trung tâm không tạo khách hàng cho trung tâm khác.
- [ ] Mã khách hàng bắt buộc khi tạo mới tại màn yêu cầu cấp phiếu.
- [ ] Trùng `mã khách hàng + trung tâm` bị cảnh báo.
- [ ] Cùng mã khách hàng ở trung tâm khác được phép nếu nghiệp vụ cho phép.
- [ ] Import khách hàng bằng file mẫu thành công.
- [ ] Admin import được nhiều trung tâm bằng cột `ma_trung_tam`.
- [ ] Trung tâm import tự gán vào trung tâm của tài khoản.
- [ ] Import thiếu mã khách hàng báo lỗi.
- [ ] Import thiếu tên khách hàng báo lỗi.
- [ ] Import sai mã trung tâm báo lỗi.
- [ ] Import email sai định dạng báo lỗi.
- [ ] Import trùng mã khách hàng trong cùng file báo lỗi/cảnh báo đúng dòng.
- [ ] Import trùng `mã khách hàng + trung tâm` với dữ liệu cũ hiển thị cảnh báo trước khi cập nhật.
- [ ] Người dùng có thể hủy import ở bước cảnh báo.
- [ ] Người dùng xác nhận cập nhật thì dữ liệu được cập nhật đúng.
- [ ] Export khách hàng có đủ thông tin cần thiết.

## 8. Tạo yêu cầu cấp phiếu từ Trung tâm

- [ ] Mở được màn `Tạo yêu cầu cấp phiếu`.
- [ ] Select2 khách hàng/công trình hiển thị đúng kích thước.
- [ ] Select2 sản phẩm hiển thị đúng kích thước trong từng dòng.
- [ ] Chọn trung tâm theo tài khoản hiện tại đúng.
- [ ] Chọn khách hàng có sẵn được.
- [ ] Tạo khách hàng mới ngay trên màn yêu cầu được.
- [ ] Trường mã khách hàng bắt buộc khi tạo khách hàng mới.
- [ ] Nhập ngày xuất hàng.
- [ ] Nhập số hóa đơn.
- [ ] Nhập tên người tạo yêu cầu.
- [ ] Nhập ghi chú.
- [ ] Bật/tắt yêu cầu ký tươi.
- [ ] Bật/tắt yêu cầu cung cấp gấp.
- [ ] Bật yêu cầu gấp thì hiện chọn lý do.
- [ ] Tắt yêu cầu gấp thì ẩn/không bắt lý do.
- [ ] Thêm một dòng sản phẩm.
- [ ] Xóa một dòng sản phẩm.
- [ ] Không cho gửi nếu chưa có sản phẩm.
- [ ] Không cho gửi nếu số lượng không hợp lệ.
- [ ] Sản phẩm đã chọn hiển thị đủ mã, tên, kích thước, tiêu chuẩn.
- [ ] Lưu và gửi DVKH thành công.
- [ ] Sau khi gửi trạng thái là `Chờ DVKH kiểm tra`.
- [ ] Timeline yêu cầu có bước Trung tâm tạo yêu cầu.
- [ ] Log thao tác ghi nhận tạo yêu cầu.
- [ ] Thông báo gửi đến DVKH.

## 9. Import/copy sản phẩm trong yêu cầu

- [ ] Bấm `Tải mẫu` tải file Excel và modal loading tự tắt.
- [ ] Import trực tiếp file mẫu rỗng không gây lỗi JavaScript.
- [ ] Import file có mã sản phẩm và số lượng hợp lệ thành công.
- [ ] Import số lượng sản phẩm lớn không timeout.
- [ ] Import mã sản phẩm không tồn tại báo lỗi rõ dòng.
- [ ] Import thiếu mã sản phẩm báo lỗi rõ dòng.
- [ ] Import thiếu số lượng báo lỗi rõ dòng.
- [ ] Import số lượng không phải số báo lỗi rõ dòng.
- [ ] Import số lượng <= 0 báo lỗi rõ dòng.
- [ ] Copy dữ liệu từ Excel và dán vào modal thành công.
- [ ] Dữ liệu copy có tab/xuống dòng được phân tích đúng.
- [ ] Copy mã sản phẩm trùng trong cùng dữ liệu được cộng dồn hoặc cảnh báo theo thiết kế.
- [ ] Sau import/copy, người dùng vẫn sửa được số lượng từng dòng.
- [ ] Các lỗi hiển thị bằng tiếng Việt dễ hiểu.

## 10. Cảnh báo trùng số hóa đơn

- [ ] Khi nhập số hóa đơn đã tồn tại cùng trung tâm, màn tạo yêu cầu cảnh báo.
- [ ] Khi nhập số hóa đơn chưa tồn tại, không cảnh báo.
- [ ] Số hóa đơn trùng ở trung tâm khác không bị tính là trùng cùng trung tâm nếu nghiệp vụ đang cho phép.
- [ ] DVKH thấy cảnh báo trùng số hóa đơn ở danh sách.
- [ ] DVKH thấy cảnh báo trùng số hóa đơn ở chi tiết.
- [ ] DVKH bấm xác nhận yêu cầu trùng hóa đơn thì có confirm riêng.
- [ ] DVKH có thể trả lại yêu cầu vì trùng hóa đơn.
- [ ] Log thao tác ghi nhận cảnh báo/trả lại nếu có.
- [ ] Báo cáo/filter trùng hóa đơn hoạt động đúng.

## 11. DVKH kiểm tra yêu cầu

- [ ] Màn DVKH mặc định chỉ hiển thị yêu cầu `Chờ DVKH`.
- [ ] Bộ lọc từ khóa hoạt động.
- [ ] Bộ lọc trung tâm hoạt động.
- [ ] Bộ lọc trạng thái hoạt động.
- [ ] Bộ lọc yêu cầu gấp hoạt động.
- [ ] Bộ lọc trùng hóa đơn hoạt động.
- [ ] Bộ lọc SLA cảnh báo/quá hạn hoạt động.
- [ ] Metric đầu màn mở đúng danh sách đã lọc.
- [ ] Mở chi tiết yêu cầu được.
- [ ] Chi tiết hiển thị khách hàng/công trình.
- [ ] Chi tiết hiển thị số hóa đơn/ngày xuất hàng.
- [ ] Chi tiết hiển thị danh sách sản phẩm.
- [ ] Chi tiết hiển thị timeline.
- [ ] DVKH xác nhận chuyển PTN thành công.
- [ ] Sau xác nhận trạng thái là `Chờ PTN lập phiếu`.
- [ ] Thông báo gửi đến PTN.
- [ ] DVKH trả lại yêu cầu thành công.
- [ ] Khi trả lại bắt buộc nhập lý do.
- [ ] Trung tâm nhận thông báo yêu cầu bị trả lại.
- [ ] Trung tâm sửa lại yêu cầu bị trả và gửi lại được nếu trạng thái cho phép.

## 12. PTN lập phiếu

- [ ] Màn PTN mặc định hiển thị yêu cầu `Chờ PTN lập phiếu`.
- [ ] Bộ lọc từ khóa/trung tâm/trạng thái hoạt động.
- [ ] Metric chờ PTN/gấp/SLA hoạt động.
- [ ] Mở chi tiết yêu cầu PTN được.
- [ ] PTN lập phiếu từ yêu cầu được.
- [ ] Không tạo trùng phiếu cho cùng yêu cầu đang có phiếu hợp lệ.
- [ ] Sau lập phiếu, yêu cầu chuyển `Đã lập phiếu - Chờ Trưởng PTN ký`.
- [ ] Phiếu mới xuất hiện ở `Danh sách phiếu`.
- [ ] Phiếu mới xuất hiện ở hàng đợi Trưởng PTN ký.
- [ ] Timeline yêu cầu ghi nhận PTN lập phiếu.
- [ ] Log thao tác ghi nhận PTN lập phiếu.
- [ ] PTN lập phiếu trực tiếp được.
- [ ] Phiếu trực tiếp có badge/nhãn `PTN lập trực tiếp`.
- [ ] Phiếu trực tiếp không qua bước DVKH.

## 13. Danh sách phiếu CNCL

- [ ] Xem danh sách phiếu.
- [ ] Lọc theo từ khóa.
- [ ] Lọc theo trạng thái ký.
- [ ] Lọc theo trung tâm nếu có quyền.
- [ ] Mở chi tiết phiếu.
- [ ] Xem PDF phiếu.
- [ ] Tải PDF.
- [ ] Phiếu hiển thị khách hàng/công trình/số yêu cầu/trung tâm/người lập/ngày ký.
- [ ] Phiếu chưa ký hiển thị trạng thái chờ ký.
- [ ] Phiếu đang chờ SmartCA hiển thị trạng thái đang chờ ký.
- [ ] Phiếu đã ký hiển thị trạng thái đã phát hành/ký thành công.
- [ ] Phiếu bị trả lại hiển thị lý do.
- [ ] Phiếu hủy/thu hồi hiển thị rõ trạng thái.
- [ ] Timeline phiếu hiển thị lịch sử xử lý.
- [ ] Quan hệ phiếu cấp lại/cũ mới hiển thị được.

## 14. Trưởng PTN duyệt ký và SmartCA

- [ ] Màn Trưởng PTN hiển thị phiếu chờ ký.
- [ ] Mở chi tiết phiếu chờ ký.
- [ ] Xem PDF trước khi ký.
- [ ] Gửi yêu cầu ký VNPT SmartCA thành công.
- [ ] Sau gửi ký, trạng thái chuyển sang chờ SmartCA.
- [ ] Hiển thị dữ liệu gửi/nhận API SmartCA chỉ với Admin.
- [ ] Trưởng PTN không thấy khối dữ liệu API nếu không phải Admin.
- [ ] App SmartCA nhận yêu cầu ký.
- [ ] Xác nhận ký trên app trong 5 phút.
- [ ] Bấm kiểm tra kết quả ký có loading khóa màn hình.
- [ ] Kiểm tra kết quả ký thành công cập nhật phiếu đã ký.
- [ ] Kiểm tra kết quả ký gửi email nếu cấu hình tự gửi đang bật.
- [ ] Nếu quá 5 phút, bấm kiểm tra kết quả trước khi gửi lại.
- [ ] Nếu SmartCA đã ký nhưng hệ thống chưa cập nhật, kiểm tra kết quả lấy lại được trạng thái.
- [ ] Nếu SmartCA chưa ký/quá hạn, hệ thống cho gửi lại yêu cầu ký.
- [ ] Nút kiểm tra kết quả ký đơn lẻ có loading.
- [ ] Nút kiểm tra kết quả ký hàng loạt có loading.
- [ ] Ký hàng loạt/kiểm tra hàng loạt không làm treo màn hình.
- [ ] Trưởng PTN trả lại phiếu được.
- [ ] Khi trả lại bắt buộc nhập lý do.
- [ ] PTN nhận thông báo phiếu bị trả lại.

## 15. PDF phiếu CNCL

- [ ] PDF là khổ A4.
- [ ] Font chính là Times New Roman hoặc tương đương khi render PDF.
- [ ] Logo hiển thị đúng kích thước.
- [ ] Tên công ty không bị xuống dòng sai.
- [ ] PCN hiển thị cùng dòng, không bị vỡ dòng.
- [ ] Header cân đối.
- [ ] Nội dung giữa phiếu giữ đúng mẫu nghiệp vụ hiện tại.
- [ ] Bảng sản phẩm căn giữa cột `TT`.
- [ ] Bảng sản phẩm căn giữa cột `Kích thước danh nghĩa`.
- [ ] Bảng sản phẩm căn giữa cột `Yêu cầu kỹ thuật`.
- [ ] Phiếu ít sản phẩm không bị trống/lệch footer.
- [ ] Phiếu nhiều sản phẩm tự sang trang, không bị footer xanh che bảng.
- [ ] Footer xanh sát đáy trang.
- [ ] Website nằm phía trên khung xanh theo mẫu mới.
- [ ] Chữ trong footer xanh đủ lớn và không bị cắt.
- [ ] Địa chỉ tiếng Việt không bị xuống dòng sai.
- [ ] Địa chỉ tiếng Anh không bị xuống dòng sai.
- [ ] Phiếu ký điện tử không còn cần vùng Trưởng phòng thử nghiệm/Tổng giám đốc như mẫu ký tay.
- [ ] Chữ ký điện tử hiển thị ở trang cuối, phía trên footer xanh.
- [ ] Nếu phiếu chỉ có 1 trang, chữ ký hiển thị ở trang 1.
- [ ] Các trang không phải trang cuối có dòng/watermark tra cứu điện tử nếu thiết kế đang bật.
- [ ] PDF mở được sau khi tải từ email/link tra cứu.

## 16. Email sau khi ký

- [ ] Sau ký thành công hệ thống gửi email tự động nếu cấu hình bật.
- [ ] Email gửi đến email tài khoản Trung tâm phân phối tạo yêu cầu.
- [ ] Email không lấy email khách hàng làm người nhận chính.
- [ ] Email cc DVKH theo cấu hình.
- [ ] Email cc PTN theo cấu hình.
- [ ] Email cc khách hàng nếu khách hàng có email.
- [ ] Email cc thêm theo file config hoạt động.
- [ ] Email có mã phiếu.
- [ ] Email có thông tin khách hàng/công trình.
- [ ] Email có link tra cứu/tải phiếu.
- [ ] Link trong email mở được khi người dùng có quyền/đúng link public nếu thiết kế cho phép.
- [ ] File PDF đính kèm hoặc link tải đúng file đã ký.
- [ ] Bấm gửi lại email từ chi tiết phiếu hoạt động theo quyền.

## 17. Cấp lại, hủy/thu hồi phiếu

- [ ] Chỉ phiếu đã ký/phát hành thành công mới có nút yêu cầu cấp lại.
- [ ] Phiếu chưa ký không có nút yêu cầu cấp lại.
- [ ] Trung tâm gửi yêu cầu cấp lại từ chi tiết phiếu được.
- [ ] Yêu cầu cấp lại tạo từ dữ liệu phiếu cũ.
- [ ] Người dùng sửa được khách hàng/công trình/ngày xuất hàng/số hóa đơn/sản phẩm trước khi DVKH xác nhận.
- [ ] Phiếu cũ chưa bị hủy ngay khi mới gửi yêu cầu cấp lại.
- [ ] DVKH thấy yêu cầu cấp lại ở danh sách.
- [ ] DVKH xác nhận hủy/thu hồi phiếu cũ được.
- [ ] Sau DVKH xác nhận, yêu cầu cấp lại chuyển PTN.
- [ ] PTN lập phiếu mới từ yêu cầu cấp lại.
- [ ] Phiếu mới liên kết với phiếu cũ.
- [ ] Phiếu cũ hiển thị đã được thay thế/hủy/thu hồi theo thiết kế.
- [ ] Chi tiết phiếu cũ có link sang phiếu mới.
- [ ] Chi tiết phiếu mới có link sang phiếu cũ.
- [ ] Timeline yêu cầu/phiếu ghi nhận thao tác cấp lại.
- [ ] Chọn nhiều phiếu để tạo một yêu cầu cấp lại/gộp phiếu hoạt động.
- [ ] Không cho gộp phiếu khác trung tâm nếu nghiệp vụ không cho phép.
- [ ] Không cho tạo trùng yêu cầu cấp lại đang hoạt động cho cùng phiếu.
- [ ] Không bị lỗi memory khi mở request reissue/edit với nhiều sản phẩm.

## 18. Báo cáo, SLA và log

- [ ] Báo cáo tổng hợp mở được.
- [ ] Lọc báo cáo theo khoảng ngày.
- [ ] Lọc báo cáo theo trung tâm.
- [ ] Lọc báo cáo theo trạng thái.
- [ ] Số lượng yêu cầu/phiếu trong báo cáo khớp dữ liệu.
- [ ] Export báo cáo Excel được.
- [ ] SLA DVKH hiển thị cảnh báo/quá hạn đúng.
- [ ] SLA PTN hiển thị cảnh báo/quá hạn đúng.
- [ ] Cấu hình SLA thêm/sửa/import/export được theo quyền.
- [ ] Log thao tác ghi nhận tạo yêu cầu.
- [ ] Log thao tác ghi nhận DVKH xác nhận/trả lại.
- [ ] Log thao tác ghi nhận PTN lập phiếu.
- [ ] Log thao tác ghi nhận gửi ký/kiểm tra ký.
- [ ] Log thao tác ghi nhận cấp lại/hủy phiếu.
- [ ] Log đăng nhập ghi nhận thành công/thất bại/đăng xuất.
- [ ] Trung tâm không xem được log toàn hệ thống nếu không có quyền.

## 19. Bảo mật dữ liệu và truy cập trực tiếp URL

- [ ] Trung tâm NP không mở được yêu cầu của TP bằng URL trực tiếp.
- [ ] Trung tâm TP không mở được phiếu của HP bằng URL trực tiếp.
- [ ] PTN/DVKH chỉ thao tác được đúng màn và đúng trạng thái.
- [ ] Người không có quyền import không gọi được route import.
- [ ] Người không có quyền export không gọi được route export.
- [ ] Người không có quyền xóa không gọi được route xóa.
- [ ] Người không có quyền ký không gọi được route ký.
- [ ] Dữ liệu API SmartCA chỉ Admin xem được.
- [ ] File PDF đã ký không bị lộ qua link nội bộ không kiểm soát nếu hệ thống yêu cầu đăng nhập.
- [ ] CSRF hoạt động với các form POST/PUT/DELETE.

## 20. Hiệu năng và dữ liệu lớn

- [ ] Danh sách yêu cầu load nhanh với dữ liệu test nhiều trung tâm.
- [ ] Danh sách DVKH load nhanh.
- [ ] Danh sách PTN load nhanh.
- [ ] Danh sách phiếu load nhanh.
- [ ] Import sản phẩm lớn không timeout.
- [ ] Import khách hàng lớn không timeout hoặc có thông báo xử lý phù hợp.
- [ ] Tạo yêu cầu với 20-100 sản phẩm hoạt động.
- [ ] PDF với nhiều sản phẩm render được.
- [ ] Không bị lỗi memory khi chỉnh sửa yêu cầu cấp lại nhiều sản phẩm.
- [ ] Feed thông báo không làm chậm trang.
- [ ] Feed việc cần làm có cache/ngắn hạn, không query quá nặng.
- [ ] Các thao tác lâu có loading để người dùng không bấm lặp.

## 21. Checklist nghiệm thu nhanh theo luồng end-to-end

### Luồng chuẩn Trung tâm -> DVKH -> PTN -> Trưởng PTN -> Email

- [ ] Trung tâm tạo khách hàng mới.
- [ ] Trung tâm tạo yêu cầu mới có sản phẩm.
- [ ] DVKH nhận thông báo.
- [ ] DVKH xác nhận chuyển PTN.
- [ ] PTN nhận thông báo.
- [ ] PTN lập phiếu.
- [ ] Trưởng PTN nhận thông báo/hàng đợi ký.
- [ ] Trưởng PTN gửi ký SmartCA.
- [ ] Trưởng PTN kiểm tra kết quả ký.
- [ ] Phiếu chuyển đã ký/phát hành.
- [ ] Email gửi đến tài khoản Trung tâm.
- [ ] Link email mở được.
- [ ] PDF đã ký tải được.

### Luồng trả lại yêu cầu

- [ ] Trung tâm tạo yêu cầu thiếu/sai thông tin.
- [ ] DVKH trả lại và nhập lý do.
- [ ] Trung tâm nhận thông báo.
- [ ] Trung tâm sửa yêu cầu.
- [ ] Trung tâm gửi lại DVKH.
- [ ] DVKH xác nhận chuyển PTN.

### Luồng Trưởng PTN trả lại phiếu

- [ ] PTN lập phiếu.
- [ ] Trưởng PTN mở phiếu.
- [ ] Trưởng PTN trả lại và nhập lý do.
- [ ] PTN nhận thông báo.
- [ ] Phiếu hiển thị trạng thái trả lại.
- [ ] Timeline ghi nhận lý do trả lại.

### Luồng cấp lại phiếu

- [ ] Có phiếu đã ký thành công.
- [ ] Trung tâm bấm yêu cầu cấp lại.
- [ ] Trung tâm sửa dữ liệu yêu cầu cấp lại.
- [ ] DVKH xác nhận hủy/thu hồi phiếu cũ.
- [ ] PTN lập phiếu mới.
- [ ] Trưởng PTN ký phiếu mới.
- [ ] Phiếu cũ và phiếu mới liên kết với nhau.

### Luồng PTN lập phiếu trực tiếp

- [ ] PTN mở màn lập phiếu trực tiếp.
- [ ] PTN nhập/chọn khách hàng.
- [ ] PTN nhập sản phẩm.
- [ ] PTN tạo phiếu.
- [ ] Phiếu đi thẳng sang trạng thái chờ Trưởng PTN ký.
- [ ] Trưởng PTN ký được phiếu trực tiếp.

## 22. Kết luận nghiệm thu

Điền sau khi hoàn tất kiểm thử:

```text
Người kiểm thử:
Ngày kiểm thử:
Môi trường:
Phiên bản source/database:

Tổng số mục kiểm tra:
Số mục đạt:
Số mục lỗi:
Số mục cần xác nhận nghiệp vụ:

Kết luận:
Đủ điều kiện dùng thử / Chưa đủ điều kiện dùng thử

Các lỗi bắt buộc sửa trước khi dùng thử:
1.
2.
3.

Ghi chú thêm:
```

