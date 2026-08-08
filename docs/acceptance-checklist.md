# Checklist nghiệm thu hệ thống CNCL

Tài liệu này dùng để kiểm thử thủ công theo từng vai trò tài khoản và theo luồng nghiệp vụ thật của hệ thống cấp Phiếu Chứng nhận Chất lượng.

Quy ước kết quả:

- `[ ]` Chưa kiểm tra
- `[x]` Đạt
- `[!]` Có lỗi cần sửa
- `[?]` Cần xác nhận nghiệp vụ

## 1. Phạm vi nghiệm thu

- Đăng nhập, đăng xuất, tài khoản không cần email để đăng nhập.
- Dashboard và sidebar theo đúng vai trò.
- Quyền xem, thêm, sửa, xóa, import, export theo từng vai trò.
- Dữ liệu Trung tâm phân phối được giới hạn đúng phạm vi.
- Luồng yêu cầu cấp phiếu từ Trung tâm -> DVKH -> PTN -> Trưởng PTN -> ký số -> gửi mail.
- Luồng PTN cấp phiếu trực tiếp.
- Luồng cấp lại, hủy phiếu cũ, trả lại phiếu.
- Cảnh báo trùng số hóa đơn.
- Yêu cầu gấp và danh mục lý do gấp.
- Ký số VNPT SmartCA, kiểm tra kết quả ký đơn lẻ/hàng loạt, quá hạn 5 phút.
- PDF phiếu CNCL, link tra cứu, gửi mail.
- Log thao tác, log đăng nhập, báo cáo tổng hợp.

## 2. Vai trò Admin

### 2.1 Đăng nhập và dashboard

- [ ] Admin đăng nhập bằng username, không cần email.
- [ ] Sau đăng nhập vào được dashboard.
- [ ] Dashboard hiển thị tổng quan toàn hệ thống.
- [ ] Sidebar hiển thị đầy đủ nhóm: Danh mục, Nghiệp vụ, Phiếu CNCL, Báo cáo, Hệ thống.

### 2.2 Quản trị danh mục

- [ ] Xem được Trung tâm phân phối.
- [ ] Thêm/sửa/xóa được Trung tâm phân phối.
- [ ] Xem được Nhóm sản phẩm.
- [ ] Import/export được Nhóm sản phẩm.
- [ ] Xem được Sản phẩm.
- [ ] Import/export được Sản phẩm số lượng lớn.
- [ ] Xem được Khách hàng - Công trình toàn hệ thống.
- [ ] Import/export được Khách hàng.
- [ ] Xem được Tiêu chuẩn chất lượng.
- [ ] Import/export được Tiêu chuẩn chất lượng.
- [ ] Xem được Danh mục lý do yêu cầu gấp.
- [ ] Thêm/sửa/xóa được lý do yêu cầu gấp.

### 2.3 Quản trị người dùng và phân quyền

- [ ] Xem được danh sách người dùng.
- [ ] Tạo được tài khoản mới cho từng vai trò.
- [ ] Gán được Trung tâm phân phối cho tài khoản TrungTam.
- [ ] Sửa được thông tin tài khoản.
- [ ] Reset được mật khẩu.
- [ ] Bật/tắt trạng thái tài khoản.
- [ ] Vào được màn Phân quyền.
- [ ] Cập nhật quyền cho vai trò không phải Admin.
- [ ] Không thể làm mất toàn bộ quyền quản trị của Admin.

### 2.4 Cấu hình và nhật ký

- [ ] Xem/sửa được cấu hình hệ thống.
- [ ] Xem/sửa được cấu hình SLA.
- [ ] Import/export được SLA nếu có quyền.
- [ ] Xem được nhật ký hệ thống.
- [ ] Xem được chi tiết log thao tác.
- [ ] Xem được nhật ký đăng nhập.
- [ ] Thấy dữ liệu API VNPT SmartCA ở chi tiết phiếu.

### 2.5 Báo cáo

- [ ] Xem được báo cáo tổng hợp toàn hệ thống.
- [ ] Lọc được báo cáo theo ngày, trạng thái, Trung tâm.
- [ ] Export được báo cáo tổng hợp Excel.

## 3. Vai trò Lãnh đạo

### 3.1 Dashboard và báo cáo

- [ ] Lãnh đạo đăng nhập thành công.
- [ ] Dashboard hiển thị số liệu tổng quan toàn hệ thống.
- [ ] Xem được danh sách yêu cầu.
- [ ] Xem được danh sách phiếu CNCL.
- [ ] Xem được báo cáo tổng hợp.
- [ ] Export được báo cáo tổng hợp.
- [ ] Xem được nhật ký hệ thống.

### 3.2 Giới hạn thao tác

- [ ] Không thấy nút thêm/sửa/xóa danh mục nếu không có quyền.
- [ ] Không thấy nút ký số SmartCA.
- [ ] Không thấy nút xử lý DVKH/PTN nếu không được cấp quyền.
- [ ] Không thấy màn quản lý người dùng/phân quyền nếu không có quyền.

## 4. Vai trò Trung tâm phân phối

### 4.1 Đăng nhập và dashboard

- [ ] Trung tâm đăng nhập thành công.
- [ ] Dashboard chỉ ưu tiên việc của Trung tâm đó.
- [ ] Sidebar không hiển thị nhóm Hệ thống.
- [ ] Sidebar không hiển thị Báo cáo tổng hợp.
- [ ] Sidebar không hiển thị DVKH kiểm tra.
- [ ] Sidebar không hiển thị PTN lập phiếu.
- [ ] Sidebar không hiển thị Trưởng PTN duyệt ký.

### 4.2 Phạm vi dữ liệu

- [ ] Trung tâm chỉ thấy yêu cầu do Trung tâm mình tạo.
- [ ] Trung tâm không thấy yêu cầu của Trung tâm khác bằng danh sách.
- [ ] Trung tâm không mở được URL chi tiết yêu cầu của Trung tâm khác.
- [ ] Trung tâm chỉ thấy khách hàng thuộc Trung tâm mình.
- [ ] Trung tâm không thấy khách hàng do Trung tâm khác tạo.
- [ ] Trung tâm chỉ thấy phiếu CNCL thuộc Trung tâm mình.
- [ ] Trung tâm không mở được URL phiếu của Trung tâm khác.

### 4.3 Khách hàng - Công trình

- [ ] Trung tâm tạo được khách hàng/công trình.
- [ ] Khách hàng mới tự gắn với Trung tâm đang đăng nhập.
- [ ] Trung tâm sửa được khách hàng của mình.
- [ ] Trung tâm xóa được khách hàng của mình nếu được cấp quyền.
- [ ] Trung tâm không chọn được khách hàng của Trung tâm khác khi tạo yêu cầu.

### 4.4 Yêu cầu cấp phiếu

- [ ] Trung tâm tạo được yêu cầu cấp phiếu.
- [ ] Trung tâm chọn được khách hàng có sẵn.
- [ ] Trung tâm nhập được khách hàng mới ngay trên form yêu cầu.
- [ ] Trung tâm chọn được sản phẩm và nhập số lượng.
- [ ] Trung tâm bật được yêu cầu gấp.
- [ ] Khi bật yêu cầu gấp mới bắt buộc chọn lý do gấp.
- [ ] Nhập được tên người tạo yêu cầu.
- [ ] Nhập số hóa đơn bị trùng thì hệ thống cảnh báo.
- [ ] Lưu yêu cầu thành công và trạng thái là Chờ DVKH.
- [ ] Trung tâm sửa được yêu cầu khi còn ở trạng thái cho phép.
- [ ] Trung tâm xóa được yêu cầu khi còn ở trạng thái cho phép.
- [ ] Trung tâm không sửa/xóa được yêu cầu đã qua bước không cho phép.

### 4.5 Phiếu đã cấp và cấp lại

- [ ] Trung tâm xem được phiếu đã ký/phát hành.
- [ ] Trung tâm tải được PDF phiếu.
- [ ] Trung tâm dùng được link tra cứu trong email.
- [ ] Trung tâm chỉ thấy nút yêu cầu cấp lại với phiếu đã ký thành công.
- [ ] Gửi yêu cầu cấp lại thành công và chuyển về DVKH.

## 5. Vai trò DVKH

### 5.1 Dashboard và sidebar

- [ ] DVKH đăng nhập thành công.
- [ ] Dashboard hiển thị các việc DVKH cần xử lý.
- [ ] Sidebar có màn DVKH kiểm tra.
- [ ] Sidebar không có màn PTN lập phiếu nếu không có quyền.
- [ ] Sidebar không có màn Trưởng PTN duyệt ký.
- [ ] Sidebar không có Báo cáo tổng hợp.
- [ ] Sidebar không có Hệ thống.

### 5.2 Màn DVKH kiểm tra

- [ ] Xem được danh sách yêu cầu chờ DVKH.
- [ ] Lọc được theo từ khóa, Trung tâm, trạng thái.
- [ ] Lọc được yêu cầu trùng số hóa đơn.
- [ ] Yêu cầu gấp có dấu hiệu dễ nhận biết.
- [ ] Mở được chi tiết yêu cầu chờ DVKH.
- [ ] Không mở được URL yêu cầu không thuộc phạm vi màn DVKH.

### 5.3 Xử lý yêu cầu

- [ ] DVKH thấy cảnh báo trùng số hóa đơn trên màn chi tiết.
- [ ] DVKH duyệt yêu cầu hợp lệ sang PTN.
- [ ] Sau duyệt, trạng thái chuyển sang Chờ PTN.
- [ ] DVKH trả lại yêu cầu kèm lý do.
- [ ] Sau trả lại, Trung tâm nhìn thấy trạng thái bị trả lại.
- [ ] DVKH xử lý được yêu cầu cấp lại phiếu.
- [ ] Khi duyệt cấp lại, phiếu cũ được hủy/thu hồi đúng quy trình.

## 6. Vai trò PTN

### 6.1 Dashboard và sidebar

- [ ] PTN đăng nhập thành công.
- [ ] Dashboard hiển thị việc PTN cần xử lý.
- [ ] Sidebar có màn PTN lập phiếu.
- [ ] Sidebar không có Trưởng PTN duyệt ký.
- [ ] Sidebar không có Báo cáo tổng hợp.
- [ ] Sidebar không có Hệ thống.

### 6.2 Màn PTN lập phiếu từ yêu cầu

- [ ] Xem được danh sách yêu cầu Chờ PTN/PTN xử lý.
- [ ] Mở được chi tiết yêu cầu thuộc hàng đợi PTN.
- [ ] Không mở được URL yêu cầu không thuộc hàng đợi PTN.
- [ ] Tiếp nhận được yêu cầu Chờ PTN.
- [ ] Lập được phiếu CNCL từ yêu cầu.
- [ ] Phiếu sinh ra có đúng khách hàng, công trình, sản phẩm, số lượng.
- [ ] PTN không thấy nút gửi ký SmartCA.
- [ ] PTN không tự ký số được bằng URL.

### 6.3 PTN cấp phiếu trực tiếp

- [ ] PTN mở được màn cấp phiếu trực tiếp.
- [ ] Chọn được Trung tâm.
- [ ] Chọn được khách hàng có sẵn hoặc nhập khách hàng mới.
- [ ] Chọn được sản phẩm và số lượng.
- [ ] Nhập số hóa đơn trùng thì có cảnh báo.
- [ ] Tạo phiếu trực tiếp thành công.
- [ ] Phiếu chuyển sang trạng thái chờ Trưởng PTN ký.

## 7. Vai trò Trưởng PTN

### 7.1 Dashboard và sidebar

- [ ] Trưởng PTN đăng nhập thành công.
- [ ] Dashboard hiển thị phiếu sẵn sàng ký, đang chờ app, quá hạn ký.
- [ ] Sidebar có màn Trưởng PTN duyệt ký.
- [ ] Sidebar không có Báo cáo tổng hợp.
- [ ] Sidebar không có Hệ thống.
- [ ] Có quyền xem lịch sử in ký tươi nếu cần.

### 7.2 Màn duyệt ký

- [ ] Xem được danh sách phiếu cần ký.
- [ ] Tab Sẵn sàng ký hiển thị phiếu DRAFT chưa ký.
- [ ] Tab Đang chờ app hiển thị giao dịch SmartCA còn hạn.
- [ ] Tab Quá hạn hiển thị giao dịch SmartCA quá 5 phút.
- [ ] Nút kiểm tra giao dịch SmartCA hàng loạt hoạt động.
- [ ] Không thấy dữ liệu API VNPT SmartCA nếu không phải Admin.

### 7.3 Ký số SmartCA

- [ ] Trưởng PTN gửi yêu cầu ký SmartCA thành công.
- [ ] App VNPT SmartCA nhận được yêu cầu ký.
- [ ] Kiểm tra kết quả khi chưa xác nhận app trả về trạng thái còn chờ.
- [ ] Kiểm tra kết quả sau khi xác nhận app thì phiếu chuyển Đã ký.
- [ ] PDF đã ký được lưu lại.
- [ ] Mail được gửi cho email tài khoản Trung tâm tạo yêu cầu.
- [ ] Mail CC cho DVKH/PTN/config và khách hàng nếu có email.
- [ ] Mail có đường link tra cứu.
- [ ] Quá 5 phút chưa ký thì hệ thống đánh dấu hết hạn.
- [ ] Có thể gửi lại yêu cầu ký khi giao dịch cũ hết hạn.

### 7.4 Trả lại phiếu

- [ ] Trưởng PTN trả lại phiếu về PTN kèm lý do.
- [ ] Trưởng PTN trả lại phiếu về DVKH kèm lý do.
- [ ] Phiếu bị trả lại không còn ở hàng đợi ký.
- [ ] Yêu cầu gốc quay về đúng bước xử lý.
- [ ] Log thao tác trả lại được ghi nhận.

## 8. Vai trò Viewer

### 8.1 Phạm vi xem

- [ ] Viewer đăng nhập thành công.
- [ ] Viewer xem được dashboard dạng chỉ đọc.
- [ ] Viewer xem được danh sách yêu cầu nếu có quyền.
- [ ] Viewer xem được danh sách phiếu nếu có quyền.
- [ ] Viewer không thấy nút thêm/sửa/xóa/import/export.
- [ ] Viewer không thấy màn DVKH/PTN/Trưởng PTN.
- [ ] Viewer không thấy báo cáo tổng hợp.
- [ ] Viewer không thấy log hệ thống.

## 9. Kiểm tra route lách quyền

- [ ] Trung tâm truy cập URL DVKH bị chặn.
- [ ] Trung tâm truy cập URL PTN bị chặn.
- [ ] Trung tâm truy cập URL Trưởng PTN bị chặn.
- [ ] Trung tâm truy cập URL báo cáo bị chặn nếu không có quyền.
- [ ] DVKH truy cập URL PTN bị chặn.
- [ ] DVKH truy cập URL ký số bị chặn.
- [ ] PTN truy cập URL ký số bị chặn.
- [ ] Trưởng PTN truy cập URL quản trị người dùng bị chặn.
- [ ] Viewer truy cập URL thao tác POST bị chặn.

## 10. Kiểm tra UI chung

- [ ] Select2 hiển thị đều chiều cao ở các màn create/edit.
- [ ] Bộ lọc danh sách không bị lệch layout desktop.
- [ ] Bộ lọc danh sách không bị vỡ layout mobile/tablet.
- [ ] Nút Thêm/Sửa/Xóa/Import/Export tự ẩn theo quyền.
- [ ] Bảng dữ liệu có trạng thái rỗng rõ ràng.
- [ ] Badge trạng thái dễ hiểu.
- [ ] Cảnh báo lỗi tiếng Việt không bị lỗi font.

## 11. Kiểm tra dữ liệu và hiệu năng

- [ ] Dashboard tải nhanh với dữ liệu thật.
- [ ] Danh sách yêu cầu tải nhanh.
- [ ] Danh sách phiếu tải nhanh.
- [ ] Báo cáo tổng hợp tải nhanh.
- [ ] Import sản phẩm số lượng lớn không timeout.
- [ ] Các màn không phát sinh lỗi N+1 rõ rệt.
- [ ] Các cột lọc chính đã có index phù hợp.

## 12. Kết luận nghiệm thu

- [ ] Đạt nghiệm thu phân quyền theo vai trò.
- [ ] Đạt nghiệm thu phạm vi dữ liệu theo Trung tâm.
- [ ] Đạt nghiệm thu luồng cấp phiếu chuẩn.
- [ ] Đạt nghiệm thu luồng PTN cấp trực tiếp.
- [ ] Đạt nghiệm thu luồng cấp lại/hủy phiếu.
- [ ] Đạt nghiệm thu ký số VNPT SmartCA.
- [ ] Đạt nghiệm thu gửi mail và link tra cứu.
- [ ] Đạt nghiệm thu PDF mẫu phiếu.
- [ ] Đạt nghiệm thu import/export.
- [ ] Đạt nghiệm thu log và báo cáo.

## 13. Kết quả kiểm thử tự động

### 13.1 Phần 3 - Đăng nhập, dashboard và route theo vai trò

- [x] Đã tạo test `tests/Feature/RoleWorkspaceAccessTest.php`.
- [x] Kiểm tra 11 tài khoản test đăng nhập được và mở dashboard thành công.
- [x] Kiểm tra ma trận route theo vai trò: Admin, LanhDao, TrungTam, DVKH, PTN, TruongPTN, Viewer.
- [x] Kiểm tra Trung tâm NP không mở được yêu cầu của Trung tâm TP.
- [x] Kiểm tra Trung tâm NP không mở được phiếu CNCL của Trung tâm TP.
- [x] Đã sửa dashboard để biểu đồ theo tháng chạy được trên SQLite test và MySQL.
- [x] `php artisan test tests/Feature/RoleWorkspaceAccessTest.php`: 3 test pass, 159 assertions.
- [x] `php artisan test`: 28 test pass, 221 assertions.

### 13.2 Phần 4 - Luồng nghiệp vụ cấp phiếu chuẩn

- [x] Đã tạo test `tests/Feature/CertificateWorkflowTest.php`.
- [x] Kiểm tra Trung tâm tạo yêu cầu cấp phiếu thành công.
- [x] Kiểm tra yêu cầu mới chuyển trạng thái `WAIT_DVKH`.
- [x] Kiểm tra DVKH duyệt yêu cầu sang `WAIT_PTN`.
- [x] Kiểm tra PTN tiếp nhận và lập phiếu CNCL.
- [x] Kiểm tra phiếu sinh ra ở trạng thái `DRAFT`, chưa ký.
- [x] Kiểm tra dữ liệu chi tiết phiếu lấy đúng kích thước danh nghĩa và tiêu chuẩn sản phẩm từ danh mục.
- [x] Kiểm tra Trưởng PTN thấy phiếu trong hàng đợi ký.
- [x] Kiểm tra Trưởng PTN trả lại phiếu về DVKH kèm lý do.
- [x] Kiểm tra phiếu bị trả lại chuyển trạng thái `REJECTED`.
- [x] Kiểm tra yêu cầu gốc quay về trạng thái `WAIT_DVKH`.
- [x] `php artisan test tests/Feature/CertificateWorkflowTest.php`: 2 test pass, 24 assertions.
- [x] `php artisan test`: 30 test pass, 245 assertions.
