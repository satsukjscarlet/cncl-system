# Nhận IP khách hàng qua Cloudflare

## Cơ chế

Middleware chung chỉ tin `CF-Connecting-IP` khi `REMOTE_ADDR` thuộc dải IPv4/IPv6 Cloudflare trong `config/cloudflare.php`. Chuỗi `X-Forwarded-For` gửi kèm không được dùng để chọn IP khách. Header thiếu hoặc sai định dạng thì giữ IP Cloudflare, không đoán địa chỉ.

Truy cập trực tiếp LAN/localhost/Internet giữ IP kết nối thực tế; tự gửi header Cloudflare không làm đổi IP. Không thay cách xử lý host/HTTPS. Trường hợp Cloudflare bật Pseudo IPv4 và thay header, ưu tiên IPv6 gốc hợp lệ từ `CF-Connecting-IPv6`.

Các chỗ gọi `$request->ip()` như thiết bị, lịch sử đăng nhập, nhật ký và giới hạn truy cập sẽ nhận cùng IP khách. Giới hạn truy cập theo IP vì thế cũng chuyển từ IP Cloudflare sang IP khách. Không thay cơ chế duyệt thiết bị bằng cookie.

## Cập nhật VPS

Sau khi pull code, chạy:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Không cần migration, seeder, cài thư viện hoặc chép đè `.env`. Mặc định tính năng được bật. Có thể tắt bằng `CLOUDFLARE_CLIENT_IP_ENABLED=false` rồi chạy lại `php artisan config:cache`.

## Kiểm tra thực tế

1. Mở website qua domain Cloudflare, đăng nhập và so IP trong quản lý thiết bị với IP public của chính mạng đó.
2. Thiết bị đã đăng nhập sẽ cập nhật khi có truy cập sau khoảng 5 phút kể từ lần cập nhật trước; có thể đăng nhập lại để cập nhật ngay.
3. IP nhật ký cũ không bị sửa. Không thể suy ngược WAN từ IP Cloudflare cũ.
4. Nếu vẫn hiện Cloudflare/localhost, kiểm tra có thêm proxy/load balancer/Cloudflare Tunnel giữa Cloudflare và PHP hay không. Phiên bản này chỉ chấp nhận Cloudflare là peer trực tiếp; không tự tin localhost hoặc mọi proxy. Không sửa thành tin `*` để xử lý nhanh.

Nếu web server đã khôi phục IP khách vào `REMOTE_ADDR` đúng cách thì middleware giữ IP đó. Nếu dùng thêm proxy nội bộ, phải cấu hình riêng ở hạ tầng với danh sách nguồn tin cậy, không chấp nhận header từ Internet vô điều kiện.

IP là IP public truy cập Cloudflare, có thể là IPv6, IP NAT dùng chung hoặc IP VPN. Không chứng minh danh tính, vị trí vật lý hay máy tính cụ thể. Nếu cần bảo vệ origin, giới hạn truy cập origin và dùng Authenticated Origin Pulls theo cấu hình Cloudflare của đơn vị; dải IP chỉ xác nhận mạng Cloudflare, không chứng minh request thuộc riêng zone của mình. Worker hoặc các transform thay/xóa header có thể ảnh hưởng kết quả.

## Nguồn và bảo trì

- https://www.cloudflare.com/ips-v4
- https://www.cloudflare.com/ips-v6
- https://developers.cloudflare.com/fundamentals/reference/http-headers/

Dải IP được đối chiếu ngày 06/11/2026, lưu trong config, không gọi mạng mỗi request. Khi Cloudflare đổi dải, cập nhật config và chạy lại `config:cache`.
