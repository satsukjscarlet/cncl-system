# Kiểm tra lỗi 419 khi đăng nhập

419 xảy ra trước bước kiểm tra mật khẩu: token CSRF trên form không khớp session của request. Không tắt CSRF và không thêm `/login` vào danh sách miễn kiểm tra.

## Các điểm đã kiểm tra trên máy local ngày 03/11/2026

- Form có `@csrf`.
- Session dùng database, thời hạn 120 phút, bảng `sessions` hiện có.
- APP_NAME là Laravel nên tên cookie mặc định là `laravel-session`, có thể trùng ứng dụng Laravel khác chạy cùng host, kể cả khác cổng. Đây là rủi ro, chưa phải bằng chứng đã xảy ra xung đột.
- Sau khi người dùng xác nhận lỗi ở localhost, kiểm tra các dự án cạnh CNCL thấy nhiều dự án cũng dùng APP_NAME=Laravel và không có SESSION_COOKIE riêng. Đã đặt SESSION_COOKIE=cncl_session trong env local và mẫu env để cách ly. Phiên CNCL local cần đăng nhập lại một lần. Chưa quan sát được cookie trong trình duyệt người dùng nên không khẳng định mọi lỗi 419 đều do xung đột.
- APP_URL local là `http://localhost`. Không dùng cấu hình này để kết luận cấu hình trên VPS.
- Log cũ có lỗi thiếu bảng sessions vào 02/11 trước khi khôi phục DB. Đây là lỗi 500 cũ, không chứng minh nguyên nhân của 419 hiện tại.
- Trang login trước đây chưa đặt no-store, chưa xử lý Back/Forward cache và chưa có thông báo riêng cho CSRF hết phiên.

## Bản sửa

- GET login không lưu cache; khi trình duyệt khôi phục trang từ Back/Forward cache sẽ tải lại form.
- Form POST dùng URL tương đối `/login`, tránh chuyển sang host khác do URL sinh ra.
- POST login nhận 419 sẽ chuyển về login bằng 303, giữ duy nhất tên đăng nhập và thông báo nhập lại mật khẩu. Không tự đăng nhập, không gửi lại mật khẩu, không nới thời hạn session.
- Request JSON giữ mã 419; các màn hình khác vẫn giữ nguyên kiểm tra CSRF.
- Log `auth.login_csrf_mismatch` chỉ có host, scheme, có/không cookie session, driver và thời hạn; không ghi mật khẩu, token hoặc giá trị cookie.

## Nếu vẫn lặp lại trên VPS

1. Ghi lại thời điểm, địa chỉ truy cập, ngay khi mở hay sau khi để trang lâu; tra log theo `auth.login_csrf_mismatch`.
2. Kiểm tra cookie session trong DevTools: domain/path, có bị chặn, có nhiều cookie cùng tên, request POST có gửi cookie như GET không. Không chia sẻ giá trị cookie/token hoặc APP_KEY.
3. Dùng một địa chỉ truy cập thống nhất. `localhost`, `127.0.0.1`, IP LAN và domain là các host khác nhau, không chia sẻ cookie.
4. VPS HTTPS nên đặt APP_URL đúng domain HTTPS; SESSION_DOMAIN để null nếu chỉ dùng một host; SESSION_SECURE_COOKIE=true chỉ khi dùng HTTPS. Local HTTP không đặt secure=true.
5. Nếu có nhiều ứng dụng cùng host, cân nhắc SESSION_COOKIE=cncl_session riêng cho CNCL. Đổi tên cookie khiến phiên hiện tại phải đăng nhập lại một lần; không tự đổi trên server đang hoạt động.
6. Sau khi sửa cấu hình, tạo lại config cache theo quy trình triển khai. APP_KEY phải ổn định giữa các lần deploy và các instance. Không chạy key:generate để chữa 419.
7. Không cache HTML `/login` ở reverse proxy/CDN. Nếu có nhiều instance, dùng cùng session backend và cùng APP_KEY; kiểm tra cấu hình trusted proxy theo hạ tầng thật, không mở tin cậy mọi proxy tùy tiện.
8. Không xóa bảng sessions, không migrate:fresh, không seed/reset database để chữa lỗi.

Không có quyền truy cập cấu hình/runtime VPS trong lần kiểm tra này; cần log và cấu hình session đã che thông tin nhạy cảm để xác định nguyên nhân tại đó.
