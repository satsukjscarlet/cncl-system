# Hướng dẫn sử dụng hệ thống cấp Phiếu Chứng nhận Chất lượng

Phiên bản tài liệu: 1.0  
Ngày cập nhật: 18/08/2026  
Đối tượng sử dụng: Admin, Trung tâm phân phối, DVKH, PTN, Trưởng PTN, Lãnh đạo/Viewer

## 1. Mục đích hệ thống

Hệ thống dùng để quản lý quy trình cấp Phiếu Chứng nhận Chất lượng (CNCL), từ lúc Trung tâm phân phối tạo yêu cầu, DVKH kiểm tra, PTN lập phiếu, Trưởng PTN ký số VNPT SmartCA, đến khi hệ thống gửi email và lưu file PDF đã ký.

Các chức năng chính:

- Đăng nhập bằng tài khoản nội bộ, không bắt buộc nhập email để đăng nhập.
- Quản lý danh mục trung tâm, khách hàng - công trình, nhóm sản phẩm, sản phẩm, tiêu chuẩn chất lượng, lý do yêu cầu gấp.
- Tạo yêu cầu cấp phiếu, import/copy danh sách sản phẩm từ Excel.
- Cảnh báo trùng số hóa đơn.
- Theo dõi việc cần làm, thông báo trong hệ thống và thông báo trình duyệt.
- Lập phiếu CNCL, ký số VNPT SmartCA, kiểm tra kết quả ký, gửi email và tải lại PDF.
- Cấp lại phiếu, hủy/thu hồi phiếu cũ, liên kết phiếu cũ - phiếu mới.
- Báo cáo tổng hợp, SLA, lịch sử thao tác, lịch sử đăng nhập.

## 2. Đăng nhập và thiết lập ban đầu

1. Mở đường dẫn hệ thống do quản trị cung cấp.
2. Nhập `Tên đăng nhập` và `Mật khẩu`.
3. Sau khi đăng nhập, hệ thống chuyển vào Dashboard.
4. Nếu trình duyệt hỏi quyền thông báo, chọn `Cho phép` để nhận thông báo công việc mới.

Lưu ý:

- Không dùng chung tài khoản giữa nhiều người khi test quy trình.
- Nếu không thấy menu hoặc nút thao tác, kiểm tra lại vai trò tài khoản đang đăng nhập.
- Nếu bị lỗi 403, tài khoản không có quyền thực hiện chức năng đó.

## 3. Tài khoản demo nếu dùng dữ liệu seed

Mật khẩu mặc định cho các tài khoản demo: `123123123`

Email test dùng chung: `thientuantest@gmail.com`

| Vai trò | Tên đăng nhập | Ghi chú |
| --- | --- | --- |
| Admin | `admin` | Quản trị toàn hệ thống |
| Lãnh đạo | `lanhdao` | Xem dashboard, báo cáo, dữ liệu tổng hợp |
| Trung tâm Nam Phương | `trungtam_np` | Tạo yêu cầu và khách hàng thuộc NP |
| Trung tâm Tam Phước | `trungtam_tp` | Tạo yêu cầu và khách hàng thuộc TP |
| Trung tâm Hồng Phước | `trungtam_hp` | Tạo yêu cầu và khách hàng thuộc HP |
| Trung tâm Hà Dung | `trungtam_hd` | Tạo yêu cầu và khách hàng thuộc HD |
| Trung tâm Thái Hoà | `trungtam_th` | Tạo yêu cầu và khách hàng thuộc TH |
| DVKH | `dvkh` | Kiểm tra, xác nhận, trả lại yêu cầu |
| PTN | `ptn` | Lập phiếu CNCL, lập phiếu trực tiếp |
| Trưởng PTN | `truongptn` | Duyệt ký, gửi ký VNPT SmartCA, kiểm tra kết quả ký |
| Viewer | `viewer` | Chỉ xem, không thao tác nghiệp vụ |

Trước khi test ký số thật, Admin cần kiểm tra tài khoản `truongptn` đã có đúng `SmartCA User ID` do VNPT SmartCA cấp.

## 4. Vai trò và phạm vi thao tác

### Admin

Admin dùng để cấu hình và giám sát hệ thống:

- Quản lý toàn bộ danh mục.
- Quản lý người dùng, phân quyền, reset mật khẩu, khóa/mở tài khoản.
- Cấu hình hệ thống, SLA, email, chữ ký số.
- Xem log đăng nhập, log thao tác, dữ liệu API VNPT SmartCA.
- Xem và xuất báo cáo toàn hệ thống.

### Trung tâm phân phối

Trung tâm là bên tạo yêu cầu cấp phiếu chính:

- Tạo yêu cầu cấp phiếu.
- Tạo mới khách hàng - công trình thuộc trung tâm của mình.
- Import khách hàng thuộc trung tâm của mình.
- Theo dõi trạng thái yêu cầu, phiếu đã phát hành và link tra cứu.
- Gửi yêu cầu cấp lại từ phiếu đã ký số thành công.

Trung tâm chỉ xem và thao tác dữ liệu thuộc trung tâm của mình.

### DVKH

DVKH kiểm tra thông tin yêu cầu trước khi chuyển sang PTN:

- Xem danh sách yêu cầu chờ DVKH.
- Kiểm tra cảnh báo trùng số hóa đơn.
- Xác nhận chuyển PTN.
- Trả lại yêu cầu nếu thiếu/sai thông tin.
- Xác nhận hủy/thu hồi phiếu cũ trong quy trình cấp lại.

### PTN

PTN xử lý phần lập phiếu:

- Xem yêu cầu đã được DVKH xác nhận.
- Lập phiếu CNCL từ yêu cầu.
- Lập phiếu trực tiếp, không cần qua yêu cầu từ Trung tâm.
- Không được gửi ký số.

### Trưởng PTN

Trưởng PTN chịu trách nhiệm ký số:

- Xem danh sách phiếu chờ ký.
- Kiểm tra phiếu trước khi ký.
- Gửi yêu cầu ký VNPT SmartCA.
- Kiểm tra kết quả ký đơn lẻ hoặc hàng loạt.
- Trả lại phiếu nếu không đủ điều kiện ký/cấp phiếu.

## 5. Dashboard, việc cần làm và thông báo

Sau khi đăng nhập, người dùng nên kiểm tra Dashboard trước.

Khu vực `Việc cần làm` cho biết số lượng công việc đang chờ theo vai trò:

- Trung tâm: yêu cầu bị trả lại, phiếu đã phát hành, phiếu cần theo dõi.
- DVKH: yêu cầu chờ kiểm tra, yêu cầu gấp, yêu cầu trùng hóa đơn, yêu cầu sắp quá hạn SLA.
- PTN: yêu cầu chờ lập phiếu, yêu cầu gấp, yêu cầu sắp quá hạn SLA.
- Trưởng PTN: phiếu chờ ký, phiếu đang chờ kết quả SmartCA, phiếu quá 5 phút cần kiểm tra lại.

Thông báo hệ thống:

- Bấm biểu tượng chuông để xem thông báo.
- Bấm vào một thông báo để mở đúng màn hình liên quan.
- Nếu thông báo đã cũ hoặc tài khoản không còn quyền, hệ thống sẽ báo thông báo không còn mở được.
- Nếu đã bật quyền trình duyệt, hệ thống có thể hiện popup thông báo mới giống thông báo desktop.

Nếu không thấy popup trình duyệt:

- Kiểm tra trình duyệt đã cho phép notification.
- Kiểm tra đang dùng `localhost/127.0.0.1` hoặc HTTPS.
- Không nên test notification trong cửa sổ ẩn danh.

## 6. Quy trình cấp phiếu thông thường

### Bước 1: Trung tâm tạo yêu cầu

Vào `Yêu cầu cấp phiếu` -> `Tạo yêu cầu`.

Nhập các thông tin:

- Trung tâm phân phối.
- Khách hàng / Công trình.
- Ngày xuất hàng.
- Số hóa đơn.
- Tên người tạo yêu cầu.
- Ghi chú nếu có.
- Yêu cầu ký tươi nếu có.
- Yêu cầu cung cấp gấp nếu có.
- Danh sách sản phẩm và số lượng.

Với khách hàng:

- Có thể chọn khách hàng đã có.
- Có thể nhập khách hàng mới ngay trên màn tạo yêu cầu.
- Nếu tạo khách hàng mới, nên nhập đầy đủ mã khách hàng, tên khách hàng, công trình và địa điểm công trình.

Với danh sách sản phẩm:

- Có thể thêm từng dòng sản phẩm.
- Có thể tải mẫu Excel rồi import.
- Có thể copy dữ liệu từ Excel và dán trực tiếp, gồm mã sản phẩm và số lượng.

Sau khi bấm `Lưu và gửi DVKH`, yêu cầu chuyển sang trạng thái `Chờ DVKH kiểm tra`.

### Bước 2: DVKH kiểm tra

DVKH vào `DVKH kiểm tra`.

DVKH cần kiểm tra:

- Thông tin khách hàng/công trình.
- Ngày xuất hàng.
- Số hóa đơn.
- Cảnh báo trùng số hóa đơn.
- Danh sách sản phẩm.
- Yêu cầu gấp và lý do gấp nếu có.

Nếu đạt, bấm `Xác nhận chuyển PTN`.  
Nếu chưa đạt, bấm `Trả lại` và nhập lý do rõ ràng để Trung tâm sửa.

### Bước 3: PTN lập phiếu

PTN vào `PTN lập phiếu`.

PTN kiểm tra yêu cầu đã được DVKH xác nhận và bấm lập phiếu. Sau khi lập, phiếu chuyển sang trạng thái `Chờ Trưởng PTN ký`.

PTN cũng có thể dùng chức năng `PTN lập phiếu trực tiếp` trong trường hợp Phòng thử nghiệm chủ động lập và phát hành phiếu mà không cần yêu cầu từ Trung tâm.

### Bước 4: Trưởng PTN gửi ký số

Trưởng PTN vào `Trưởng PTN duyệt ký` hoặc `Danh sách phiếu`.

Thao tác:

1. Mở chi tiết phiếu.
2. Kiểm tra file PDF và thông tin phiếu.
3. Bấm gửi ký VNPT SmartCA.
4. Xác nhận trên app VNPT SmartCA trong thời gian hiệu lực.
5. Quay lại hệ thống và bấm `Kiểm tra kết quả ký`.

Khi ký thành công:

- Hệ thống lưu file PDF đã ký.
- Hệ thống cập nhật trạng thái phiếu đã ký/phát hành.
- Hệ thống gửi email cho tài khoản Trung tâm phân phối tạo yêu cầu.
- Email cc cho DVKH, PTN, email khách hàng nếu có và các email cc cấu hình thêm.
- Email có kèm đường link để tra cứu/tải lại phiếu.

## 7. Trạng thái thường gặp

| Trạng thái | Ý nghĩa |
| --- | --- |
| Nháp | Yêu cầu chưa gửi xử lý |
| Chờ DVKH kiểm tra | Trung tâm đã gửi, DVKH cần kiểm tra |
| Chờ PTN lập phiếu | DVKH đã xác nhận, PTN cần lập phiếu |
| Đã lập phiếu - Chờ Trưởng PTN ký | PTN đã lập phiếu, Trưởng PTN cần ký số |
| Đang chờ ký SmartCA | Đã gửi yêu cầu ký, chờ người ký xác nhận trên app |
| Ký thành công / Đã phát hành | Phiếu đã ký số và có thể gửi/tải PDF |
| Trưởng PTN trả lại | Phiếu chưa được ký, cần xử lý lại theo lý do trả |
| Đã hủy / Thu hồi | Phiếu cũ bị hủy trong quy trình cấp lại |

## 8. Yêu cầu gấp

Khi tạo yêu cầu, nếu cần cấp gấp:

1. Bật nút `Yêu cầu cung cấp gấp`.
2. Chọn lý do gấp từ danh mục.
3. Nhập ghi chú bổ sung nếu cần.

Chỉ khi bật yêu cầu gấp hệ thống mới yêu cầu chọn lý do. Admin có thể quản lý danh mục lý do tại màn `Lý do yêu cầu gấp`.

## 9. Cảnh báo trùng số hóa đơn

Hệ thống tự kiểm tra số hóa đơn khi lập yêu cầu và khi DVKH xác nhận.

Nguyên tắc:

- Trùng số hóa đơn trong cùng trung tâm sẽ được cảnh báo rõ.
- Có thể vẫn xác nhận nếu nghiệp vụ cho phép, nhưng người dùng phải chủ động kiểm tra.
- DVKH cần đọc cảnh báo trước khi chuyển PTN.

Khi gặp cảnh báo:

1. Mở các yêu cầu/phiếu liên quan nếu có.
2. Kiểm tra khách hàng, ngày xuất hàng, sản phẩm.
3. Nếu là nhập sai, trả lại để Trung tâm sửa.
4. Nếu là trường hợp hợp lệ, xác nhận tiếp và ghi chú nếu cần.

## 10. Import khách hàng - công trình

Vào `Khách hàng - Công trình` -> `Import`.

Nên tải file mẫu trước khi nhập dữ liệu. Các cột thường dùng:

- `ma_trung_tam`
- `ma_khach_hang`
- `ten_khach_hang`
- `dia_chi_khach_hang`
- `ma_so_thue`
- `nguoi_lien_he`
- `dien_thoai`
- `email`
- `ten_cong_trinh`
- `dia_diem_cong_trinh`
- `dang_su_dung`

Quyền import:

- Trung tâm phân phối: import vào trung tâm của chính tài khoản đó.
- Admin: có thể import cho nhiều trung tâm bằng cột `ma_trung_tam` hoặc chọn trung tâm khi import.

Cảnh báo cập nhật:

- Nếu trùng `mã khách hàng + trung tâm`, hệ thống sẽ cảnh báo trước khi cập nhật dữ liệu.
- Người dùng cần xem kỹ danh sách thay đổi rồi mới xác nhận import.

Lỗi thường gặp khi import:

- Thiếu mã khách hàng.
- Thiếu tên khách hàng.
- Sai mã trung tâm.
- Email không đúng định dạng.
- Trùng mã khách hàng nhiều dòng trong cùng file.

## 11. Import/copy sản phẩm vào yêu cầu

Tại màn `Tạo yêu cầu cấp phiếu`, trong khu vực `Danh sách sản phẩm đề nghị cấp phiếu`:

- Bấm `Tải mẫu` để lấy file Excel mẫu.
- Nhập mã sản phẩm và số lượng.
- Bấm `Import Excel` để đưa dữ liệu vào yêu cầu.
- Hoặc copy trực tiếp các dòng từ Excel rồi dùng chức năng dán dữ liệu.

Lưu ý:

- Mã sản phẩm phải tồn tại trong danh mục sản phẩm.
- Số lượng nên nhập số nguyên nếu đang test theo dữ liệu thực tế.
- Nếu có lỗi, hệ thống sẽ hiển thị dòng lỗi để người dùng sửa đúng vị trí.

## 12. Cấp lại và hủy phiếu

Cấp lại dùng khi phiếu đã phát hành nhưng bị sai, thiếu hoặc cần gộp nhiều phiếu cũ thành một phiếu mới.

Điều kiện khuyến nghị:

- Chỉ yêu cầu cấp lại từ phiếu đã ký số/phát hành thành công.
- Phiếu cũ sẽ không bị hủy ngay khi Trung tâm bấm yêu cầu cấp lại.
- DVKH cần xác nhận hủy/thu hồi phiếu cũ trước khi quy trình cấp phiếu mới hoàn tất.

Luồng cơ bản:

1. Trung tâm mở phiếu đã phát hành.
2. Bấm `Yêu cầu cấp lại`.
3. Hệ thống tạo yêu cầu mới từ dữ liệu phiếu cũ.
4. Trung tâm có thể sửa khách hàng, hóa đơn, ngày xuất hàng, sản phẩm, số lượng.
5. DVKH kiểm tra và xác nhận hủy/thu hồi phiếu cũ.
6. PTN lập phiếu mới.
7. Trưởng PTN ký số phiếu mới.

Trường hợp gộp nhiều phiếu:

- Chọn nhiều phiếu cũ cần cấp lại.
- Hệ thống tạo một yêu cầu mới để người dùng chỉnh lại danh sách sản phẩm.
- Sau khi DVKH xác nhận, các phiếu cũ được liên kết với phiếu mới để tra cứu lịch sử.

Trong chi tiết phiếu/yêu cầu có timeline và lịch sử để theo dõi phiếu cũ - phiếu mới.

## 13. Ký số VNPT SmartCA

Ký số hiện tại xử lý theo hướng ký hash/PAdES và nhúng chữ ký vào PDF.

Khi Trưởng PTN gửi ký:

1. Hệ thống tạo dữ liệu ký từ file PDF.
2. Hệ thống gửi yêu cầu sang API VNPT SmartCA.
3. Người ký xác nhận trên app VNPT SmartCA.
4. Hệ thống kiểm tra kết quả ký.
5. Nếu thành công, hệ thống tạo/lưu PDF đã ký và gửi email.

Lưu ý quan trọng:

- App VNPT SmartCA thường chỉ cho xác nhận trong khoảng 5 phút.
- Nếu quá 5 phút mà chưa bấm kiểm tra, nên bấm `Kiểm tra kết quả ký` trước khi gửi lại.
- Nếu đã ký trên app nhưng hệ thống chưa cập nhật, thao tác kiểm tra kết quả sẽ giúp lấy trạng thái mới nhất.
- Khi đang kiểm tra kết quả ký, màn hình có loading và khóa thao tác để tránh bấm lặp.

Lỗi SmartCA thường gặp:

- `no_credential_match_id`: tài khoản ký chưa khớp user id/serial SmartCA.
- `Currently we only support type hash`: cấu hình kiểu ký chưa đúng, cần dùng kiểu hash.
- Quá hạn xác nhận: cần kiểm tra kết quả trước, nếu chưa ký thì gửi lại yêu cầu ký.

## 14. File PDF và email sau khi ký

Sau khi ký thành công:

- File PDF đã ký được lưu trên server.
- Phiếu có đường link tra cứu/tải lại.
- Email được gửi cho tài khoản Trung tâm phân phối tạo yêu cầu.
- Email cc cho DVKH, PTN, email khách hàng nếu khách hàng có email và danh sách cc cấu hình thêm.

Khuyến nghị khi test:

- Kiểm tra email nhận được có đúng số phiếu, khách hàng, công trình.
- Bấm link trong email để mở tra cứu.
- Tải PDF và kiểm tra chữ ký hiển thị ở trang cuối, phía trên footer xanh.
- Nếu phiếu chỉ có một trang, chữ ký hiển thị trên trang đầu.

## 15. Danh mục và import/export

Các màn danh mục chính:

- Trung tâm phân phối.
- Nhóm sản phẩm.
- Sản phẩm.
- Khách hàng - Công trình.
- Tiêu chuẩn chất lượng.
- Lý do yêu cầu gấp.
- Cấu hình SLA.

Các nút `Thêm`, `Sửa`, `Xóa`, `Import`, `Export` sẽ tự ẩn/hiện theo quyền tài khoản. Nếu không thấy nút, cần kiểm tra phân quyền.

## 16. Báo cáo và lịch sử

Các màn nên kiểm tra khi nghiệm thu:

- Dashboard tổng quan.
- Báo cáo tổng hợp.
- Lịch sử in/ký tươi.
- Log thao tác.
- Log đăng nhập.
- Timeline trong chi tiết yêu cầu/phiếu.

Admin và Lãnh đạo thường có quyền xem rộng hơn. Trung tâm chỉ nên xem dữ liệu của trung tâm mình.

## 17. Lỗi thường gặp và cách xử lý

| Hiện tượng | Nguyên nhân thường gặp | Cách xử lý |
| --- | --- | --- |
| Không đăng nhập được | Sai username/password hoặc tài khoản bị khóa | Liên hệ Admin reset mật khẩu/mở khóa |
| Không thấy menu | Tài khoản không có quyền | Kiểm tra vai trò/phân quyền |
| Bấm chức năng báo 403 | Không đủ quyền | Dùng đúng tài khoản vai trò hoặc cấp quyền |
| Bấm link báo 404 | Dữ liệu không còn tồn tại hoặc link cũ | Mở từ danh sách trong hệ thống |
| Không thấy nút xác nhận/ký | Phiếu chưa đúng trạng thái hoặc sai vai trò | Kiểm tra trạng thái và tài khoản |
| Import Excel lỗi | Thiếu cột, sai mã, trùng dữ liệu | Xem danh sách lỗi, sửa file rồi import lại |
| Không hiện thông báo trình duyệt | Chưa cấp quyền notification | Cho phép notification trong trình duyệt |
| Ký SmartCA thất bại | Sai SmartCA User ID/serial/cấu hình ký | Kiểm tra cấu hình tài khoản ký và `.env` |
| Chờ ký quá lâu | Chưa xác nhận trên app hoặc quá hạn 5 phút | Bấm kiểm tra kết quả trước khi gửi lại |
| Email không đến | Chưa cấu hình mail hoặc email bị cc sai | Kiểm tra cấu hình mail và danh sách cc |

## 18. Checklist test nhanh theo vai trò

### Trung tâm phân phối

- Đăng nhập bằng tài khoản trung tâm.
- Kiểm tra chỉ thấy dữ liệu thuộc trung tâm mình.
- Tạo khách hàng mới.
- Import khách hàng, kiểm tra cảnh báo trùng mã khách hàng.
- Tạo yêu cầu cấp phiếu.
- Import/copy danh sách sản phẩm từ Excel.
- Tạo yêu cầu gấp và chọn lý do.
- Kiểm tra cảnh báo trùng số hóa đơn.
- Theo dõi yêu cầu sau khi DVKH/PTN/Trưởng PTN xử lý.
- Mở link tra cứu phiếu đã ký.
- Gửi yêu cầu cấp lại phiếu đã phát hành.

### DVKH

- Mở danh sách yêu cầu chờ DVKH.
- Lọc yêu cầu gấp, trùng hóa đơn, sắp quá hạn SLA.
- Xác nhận chuyển PTN.
- Trả lại yêu cầu và kiểm tra Trung tâm nhận thông báo.
- Xác nhận hủy/thu hồi phiếu cũ trong luồng cấp lại.

### PTN

- Mở danh sách yêu cầu chờ PTN lập phiếu.
- Lập phiếu từ yêu cầu đã được DVKH xác nhận.
- Lập phiếu trực tiếp.
- Kiểm tra PTN không có nút gửi ký số.

### Trưởng PTN

- Mở hàng đợi ký.
- Xem chi tiết phiếu và PDF.
- Gửi ký VNPT SmartCA.
- Kiểm tra kết quả ký.
- Kiểm tra ký quá 5 phút.
- Trả lại phiếu nếu chưa đủ điều kiện ký.
- Kiểm tra ký hàng loạt/kiểm tra kết quả hàng loạt nếu được cấp quyền.

### Admin

- Tạo/sửa/khóa người dùng.
- Gán vai trò và trung tâm cho tài khoản.
- Cập nhật phân quyền.
- Import/export danh mục.
- Cấu hình SLA, email, SmartCA.
- Xem báo cáo, log thao tác, log đăng nhập.
- Kiểm tra dữ liệu API VNPT SmartCA chỉ Admin xem được.

## 19. Ghi nhận lỗi khi dùng thử

Khi báo lỗi, vui lòng ghi đủ:

- Tài khoản đang đăng nhập.
- Vai trò tài khoản.
- Đường dẫn màn hình.
- Thao tác vừa thực hiện.
- Mã yêu cầu hoặc mã phiếu nếu có.
- Ảnh chụp màn hình lỗi.
- Thời điểm xảy ra lỗi.

Mẫu báo lỗi:

```text
Tài khoản:
Vai trò:
Màn hình:
Mã yêu cầu/phiếu:
Thao tác:
Kết quả mong muốn:
Kết quả thực tế:
Ảnh chụp:
Ghi chú:
```

## 20. Tài liệu liên quan trong repo

- `docs/test-accounts.md`: danh sách tài khoản test.
- `docs/acceptance-checklist.md`: checklist nghiệm thu chi tiết.

