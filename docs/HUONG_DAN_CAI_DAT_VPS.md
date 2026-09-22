# Hướng Dẫn Cài Đặt Hệ Thống CNCL Trên VPS

Tài liệu này dùng để triển khai hệ thống cấp phiếu chứng nhận chất lượng CNCL trên một máy chủ mới. Môi trường khuyến nghị là Ubuntu Server 22.04/24.04, Nginx, PHP-FPM, MySQL/MariaDB.

## 1. Cấu hình VPS khuyến nghị

Tối thiểu để chạy thử:

- 2 vCPU.
- 4 GB RAM.
- 80 GB SSD.
- Ubuntu Server 22.04 LTS hoặc 24.04 LTS.

Khuyến nghị chạy thật:

- 4 vCPU.
- 8 GB RAM.
- 120 GB SSD trở lên nếu lưu PDF ký số lâu dài trên VPS.
- Backup tự động hằng ngày.

Dung lượng cần lưu chủ yếu nằm ở:

- Database MySQL/MariaDB.
- File PDF đã ký trong `storage/app/private/quality-certificates`.
- File upload, ảnh chữ ký, file mẫu/import/export.
- Log hệ thống trong `storage/logs`.

## 2. Cài phần mềm nền

Đăng nhập VPS bằng user có quyền sudo, sau đó cài các gói chính:

```bash
sudo apt update
sudo apt upgrade -y

sudo apt install -y nginx mysql-server unzip git curl supervisor cron \
    php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
    php8.3-intl
```

Nếu VPS Ubuntu 22.04 chưa có sẵn PHP 8.2/8.3 trong repository mặc định, cần cài thêm repository PHP phù hợp trước khi chạy lệnh trên.

Cài Composer:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

Cài Node.js LTS:

```bash
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 3. Tạo database

Đăng nhập MySQL:

```bash
sudo mysql
```

Tạo database và user:

```sql
CREATE DATABASE cncl_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cncl_user'@'localhost' IDENTIFIED BY 'doi_mat_khau_manh_o_day';
GRANT ALL PRIVILEGES ON cncl_system.* TO 'cncl_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 4. Đưa source code lên VPS

Ví dụ đặt source tại `/var/www/cncl-system`:

```bash
sudo mkdir -p /var/www/cncl-system
sudo chown -R $USER:www-data /var/www/cncl-system
cd /var/www/cncl-system
```

Nếu dùng Git:

```bash
git clone <repo-url> .
```

Nếu copy file thủ công, upload toàn bộ source lên thư mục này, nhưng không upload các file không cần thiết như `node_modules`, `vendor`, file backup `.env.*`, log cũ.

## 5. Cài thư viện PHP và frontend

```bash
cd /var/www/cncl-system

composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 6. Cấu hình `.env`

Tạo file môi trường:

```bash
cp .env.example .env
php artisan key:generate
```

Các biến bắt buộc cần sửa:

```env
APP_NAME="CNCL NTP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ten-mien-cua-ban.vn

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cncl_system
DB_USERNAME=cncl_user
DB_PASSWORD=doi_mat_khau_manh_o_day

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-mail@example.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-mail@example.com
MAIL_FROM_NAME="CNCL NTP"
```

Email CC nhận phiếu có thể cấu hình trong màn hình quản trị hệ thống. Nếu cần cấu hình nhanh bằng `.env`, dùng các biến:

```env
CNCL_CERT_MAIL_CC_DVKH=
CNCL_CERT_MAIL_CC_PTN=
CNCL_CERT_MAIL_CC_EXTRA=
```

## 7. Cấu hình VNPT SmartCA

Hệ thống đang hỗ trợ tách môi trường test và production. Khi chạy thật:

```env
SMARTCA_ENV=production

SMARTCA_TEST_BASE_URL=https://rmgateway.vnptit.vn/sca/sp769
SMARTCA_TEST_SIGNATURE_BASE_URL=
SMARTCA_TEST_CLIENT_ID=
SMARTCA_TEST_CLIENT_SECRET=
SMARTCA_TEST_SERIAL_NUMBER=
SMARTCA_TEST_DEFAULT_USER_ID=

SMARTCA_PRODUCTION_BASE_URL=https://gwsca.vnpt.vn/sca/sp769
SMARTCA_PRODUCTION_SIGNATURE_BASE_URL=
SMARTCA_PRODUCTION_CLIENT_ID=client_id_vnpt_cap
SMARTCA_PRODUCTION_CLIENT_SECRET=client_secret_vnpt_cap
SMARTCA_PRODUCTION_SERIAL_NUMBER=serial_chung_thu_so
SMARTCA_PRODUCTION_DEFAULT_USER_ID=cccd_mst_nguoi_ky

SMARTCA_USER_ID_FIELD=smartca_user_id
SMARTCA_SIGN_TYPE=hash
SMARTCA_PADES_ENABLED=true
SMARTCA_PADES_PROVIDER=vnpt
SMARTCA_REQUIRE_SIGNED_PDF=false
SMARTCA_PENDING_TTL_MINUTES=5
SMARTCA_TIMEOUT=30
```

Lưu ý:

- `SMARTCA_ENV=test` dùng để quay về môi trường thử nghiệm.
- `SMARTCA_ENV=production` dùng thông tin chính thức.
- `SMARTCA_USER_ID_FIELD=smartca_user_id` nghĩa là hệ thống lấy CCCD/MST người ký từ trường `smartca_user_id` của tài khoản trưởng PTN. Nếu tài khoản chưa có giá trị này thì dùng `SMARTCA_PRODUCTION_DEFAULT_USER_ID`.
- Không commit `.env` lên Git.

Nếu dùng tự nhúng chữ ký PAdES bằng Python, cài thêm Python và thư viện theo yêu cầu của script `scripts/smartca_pades.py`, sau đó trỏ:

```env
SMARTCA_PYTHON_BIN=/usr/bin/python3
```

## 8. Chạy migrate và seed dữ liệu nền

Chạy migrate:

```bash
php artisan migrate --force
```

Seed dữ liệu nền:

```bash
php artisan db:seed --force
```

Seeder mặc định hiện gồm quyền, trung tâm, user mẫu, tiêu chuẩn, nhóm sản phẩm, lý do gấp, SLA và cấu hình hệ thống.

Nếu chỉ muốn tạo riêng tài khoản admin:

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

Không chạy các seeder dữ liệu test trên production, trừ khi đang dựng môi trường demo:

- `AcceptanceTestDataSeeder`
- `WorkflowReportTestDataSeeder`

## 9. Phân quyền thư mục

```bash
sudo chown -R www-data:www-data /var/www/cncl-system/storage /var/www/cncl-system/bootstrap/cache
sudo chmod -R 775 /var/www/cncl-system/storage /var/www/cncl-system/bootstrap/cache

php artisan storage:link
```

File PDF ký số được lưu trên disk `local`, đường dẫn vật lý thường là:

```text
/var/www/cncl-system/storage/app/private/quality-certificates
```

Thư mục này phải được backup định kỳ.

## 10. Cấu hình Nginx

Tạo file:

```bash
sudo nano /etc/nginx/sites-available/cncl-system
```

Nội dung mẫu:

```nginx
server {
    listen 80;
    server_name ten-mien-cua-ban.vn;

    root /var/www/cncl-system/public;
    index index.php index.html;

    client_max_body_size 50M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Kích hoạt site:

```bash
sudo ln -s /etc/nginx/sites-available/cncl-system /etc/nginx/sites-enabled/cncl-system
sudo nginx -t
sudo systemctl reload nginx
```

Cài HTTPS bằng Certbot nếu có domain:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ten-mien-cua-ban.vn
```

Sau khi có HTTPS, sửa lại:

```env
APP_URL=https://ten-mien-cua-ban.vn
```

## 11. Queue worker

Hệ thống dùng `QUEUE_CONNECTION=database`, nên cần worker chạy nền để xử lý job gửi mail, thông báo và các tác vụ chờ.

Tạo file Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/cncl-worker.conf
```

Nội dung:

```ini
[program:cncl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cncl-system/artisan queue:work database --sleep=3 --tries=3 --timeout=180
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/cncl-system/storage/logs/worker.log
stopwaitsecs=3600
```

Khởi động:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Khi deploy code mới:

```bash
php artisan queue:restart
```

## 12. Scheduler

Scheduler hiện đang chạy lệnh tự kiểm tra các yêu cầu ký SmartCA đang chờ:

```text
smartca:check-pending-signatures --limit=30
```

Thêm cron:

```bash
sudo crontab -e
```

Thêm dòng:

```cron
* * * * * cd /var/www/cncl-system && php artisan schedule:run >> /dev/null 2>&1
```

Kiểm tra lịch:

```bash
php artisan schedule:list
```

Có thể chạy thử bằng tay:

```bash
php artisan smartca:check-pending-signatures --limit=5
```

## 13. Tối ưu cache production

Sau khi cấu hình xong:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Sau mỗi lần sửa `.env`, cần chạy:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 14. Checklist kiểm tra sau cài đặt

1. Mở trang đăng nhập theo `APP_URL`.
2. Đăng nhập admin.
3. Kiểm tra dashboard không lỗi font tiếng Việt.
4. Kiểm tra tạo yêu cầu cấp phiếu nháp.
5. Gửi yêu cầu sang DVKH.
6. DVKH xác nhận sang PTN.
7. PTN lập phiếu.
8. Trưởng PTN gửi ký SmartCA một phiếu test.
9. Xác nhận yêu cầu ký trên app VNPT SmartCA.
10. Chạy hoặc chờ scheduler kiểm tra kết quả ký.
11. Kiểm tra file PDF đã ký tải được.
12. Kiểm tra email gửi về trung tâm phân phối và CC đúng cấu hình.
13. Kiểm tra import/export Excel.
14. Kiểm tra báo cáo tổng hợp.

## 15. Backup

Cần backup ít nhất các thành phần sau:

- Database `cncl_system`.
- File `.env`.
- Thư mục `storage/app/private`.
- Thư mục `storage/app/public`.
- Nếu có ảnh/cấu hình upload trong storage thì backup toàn bộ `storage/app`.

Ví dụ backup database:

```bash
mkdir -p /backup/cncl
mysqldump -u cncl_user -p cncl_system > /backup/cncl/cncl_system_$(date +%F).sql
```

Ví dụ backup file:

```bash
tar -czf /backup/cncl/cncl_storage_$(date +%F).tar.gz /var/www/cncl-system/storage/app
cp /var/www/cncl-system/.env /backup/cncl/env_$(date +%F)
```

Nên cấu hình cron backup hằng ngày và đồng bộ ra nơi khác như Google Drive, S3, NAS hoặc server backup riêng.

## 16. Quy trình deploy bản cập nhật

```bash
cd /var/www/cncl-system

git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

sudo supervisorctl restart cncl-worker:*
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
```

Sau deploy, kiểm tra nhanh:

```bash
php artisan about
php artisan schedule:list
sudo supervisorctl status
tail -n 100 storage/logs/laravel.log
```

## 17. Các lỗi thường gặp

### Màn hình 500 sau khi upload source

Thường do thiếu quyền ghi:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
```

### Sửa `.env` nhưng hệ thống không nhận

Chạy:

```bash
php artisan optimize:clear
php artisan config:cache
```

### Không gửi mail

Kiểm tra:

- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`.
- Queue worker có chạy không.
- `storage/logs/laravel.log`.

### Ký SmartCA không tự chuyển trạng thái sau khi đã xác nhận trên app

Kiểm tra:

- Cron scheduler có chạy không.
- `php artisan schedule:list`.
- `php artisan smartca:check-pending-signatures --limit=5`.
- Queue worker có hoạt động không.
- `SMARTCA_ENV` đang là `test` hay `production`.

### Không tải được PDF đã ký

Kiểm tra:

- File có nằm trong `storage/app/private/quality-certificates` không.
- Quyền thư mục `storage`.
- Bản ghi `quality_certificates.pdf_path` trong database.

## 18. Ghi chú vận hành

- Không bật `APP_DEBUG=true` trên production.
- Không để mật khẩu mặc định cho tài khoản admin.
- Không chạy seeder dữ liệu test trên môi trường thật.
- Cần có backup trước khi migrate dữ liệu thật.
- Nên kiểm tra log định kỳ: `storage/logs/laravel.log`, `storage/logs/worker.log`.
- Với VPS 80 GB, nên giữ file PDF đã ký cuối cùng, backup định kỳ ra storage ngoài, và dọn log cũ theo chu kỳ.
