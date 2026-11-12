# Hướng dẫn khôi phục database test từ file SQL

Ngày cập nhật: **02/11/2026**.

## 1. Kết quả lần khôi phục này

- File nguồn: `C:\Users\thien\Downloads\cncl_20261102_023001.sql`.
- File thực tế là GZIP dù có đuôi `.sql`. Không đưa trực tiếp file nén vào MySQL như SQL văn bản.
- Đã nhập thử vào **`cncl_restore_20261102`** để kiểm tra, sau đó theo xác nhận của người dùng đã nhập vào đúng **`cncl_system`**.
- Ứng dụng tiếp tục dùng `DB_DATABASE=cncl_system`, không cần sửa `.env`; hiện đang dùng dữ liệu vừa nhập.
- Nguồn là MySQL 8.0.45; đã nhập thành công trên MariaDB 10.4.32 của XAMPP.
- Có 37 bảng: 23 tài khoản, 14.864 sản phẩm, 1.233 khách hàng, 1.758 yêu cầu cấp phiếu, 1.731 phiếu CNCL, 36 yêu cầu bổ sung ký tươi, 961 nhật ký mở file in.
- Backup database cũ: `storage/app/private/backups/restore-20261102-164019-dcf549/before-restore.sql`.
- Bản backup mới ngay trước khi thay dữ liệu `cncl_system`: `storage/app/private/backups/cncl-system-replace-20261102-164652/cncl_system_before_replace.sql`.
- Kết quả nhập vào database chính local nằm tại `storage/app/private/backups/cncl-system-replace-20261102-164652/result.json`. Bảng và số bản ghi chính đã đối chiếu với database nhập thử, đều khớp; queue có 0 job. Đã kiểm tra migration, làm mới cache phân quyền và đưa ứng dụng ra khỏi bảo trì. Không chạy seeder hay khởi động worker/scheduler.
- Kết quả, mã kiểm tra SHA-256 và log nằm trong cùng thư mục, file `restore-result.json`.

Đây là khôi phục một bản sao lưu đầy đủ, không phải gộp thêm dữ liệu vào database đang có. File nguồn có lệnh DROP TABLE, do đó không nhập trực tiếp vào database cần giữ.

## 2. Cách làm cho lần sau trên máy local

Script chỉ hỗ trợ **APP_ENV=local**, MySQL/MariaDB trên `127.0.0.1`, `localhost` hoặc `::1`. Không dùng script này trên server chính. Không đổi APP_ENV của server chính để vượt kiểm tra.

1. Mở XAMPP, bật MySQL.
2. Mở PowerShell, vào thư mục dự án:

```powershell
cd D:\xampp\htdocs\cncl-system
```

3. Kiểm tra file nguồn, database đích mới và cấu hình bằng chế độ xem trước:

```powershell
php scripts/restore-test-database.php --source="C:\Users\thien\Downloads\ten-file-backup.sql" --target=cncl_restore_20261103
```

Thay đường dẫn nguồn và tên database cho đúng. Tên đích phải bắt đầu bằng `cncl_restore_`, chỉ gồm chữ thường, số, dấu gạch dưới. Chưa có `--execute` thì script chưa sao lưu hay nhập dữ liệu.

4. Đối chiếu `current_database`, `new_database`, `source`, dung lượng và mã SHA-256. Nếu đúng, chạy:

```powershell
php scripts/restore-test-database.php --source="C:\Users\thien\Downloads\ten-file-backup.sql" --target=cncl_restore_20261103 --execute
```

Script tự thực hiện:

- Từ chối nếu tên database đích đã tồn tại, kể cả đang trống. Dùng tên mới, không xóa database để chạy lại cho tiện.
- Sao lưu database đang được cấu hình trong ứng dụng vào thư mục private.
- Kiểm tra bản backup đã có dấu kết thúc thành công.
- Giải nén GZIP nếu cần, hoặc đọc SQL văn bản; không thay nội dung dữ liệu.
- Kiểm tra một số chỉ thị chuyển database/quản lý server không được hỗ trợ và dấu kết thúc dump. Đây không phải trình kiểm định SQL không tin cậy; chỉ dùng bản backup của đơn vị, không dùng file SQL lạ.
- Tạo database mới rồi nhập bằng MySQL client. Nếu có lỗi, dừng và giữ database nhập dở cùng log để kiểm tra; không tiếp tục bằng cách bỏ qua lỗi.
- Kiểm tra số bảng, in số bản ghi các bảng chính, lưu `restore-result.json`.

Script mặc định tìm MySQL tại `D:\xampp\mysql\bin`. Nếu XAMPP nằm nơi khác, thêm tham số:

```powershell
php scripts/restore-test-database.php --source="C:\backup\data.sql" --target=cncl_restore_20261103 --mysql-bin="C:\xampp\mysql\bin" --execute
```

Không nhập mật khẩu database vào câu lệnh. Script dùng kết nối Laravel hiện tại và truyền mật khẩu riêng cho tiến trình MySQL, không in ra màn hình. Đảm bảo cấu hình kết nối đang đúng; nếu từng cache config rồi sửa `.env`, chạy `php artisan config:clear` trên máy local trước khi xem trước.

## 3. Kiểm tra sau khi nhập

1. Chỉ coi đã nhập thành công khi lệnh trả về mã 0, có thông tin database mới, đủ số bảng và không báo lỗi.
2. Mở phpMyAdmin, chọn database mới, đối chiếu số dòng các bảng `users`, `products`, `certificate_requests`, `quality_certificates`, `hard_copy_supplements`, `print_logs`.
3. Giữ file backup cũ và `restore-result.json` trước khi quyết định chuyển ứng dụng.
4. Không chạy migrate:fresh, migrate:refresh, db:wipe, không seed data test sau khi nhập.
5. Nếu script báo database đích đã có, hãy chọn tên mới. Nếu báo lỗi nhập, đọc `import.err.log` trong thư mục backup, không dùng tùy chọn bỏ qua lỗi hoặc tự xóa bảng để thử lại.

## 4. Khi muốn ứng dụng local dùng database mới

**Chỉ làm khi chủ động muốn đổi ứng dụng sang database nhập thử. Lần này đã nhập vào `cncl_system`, không cần áp dụng bước đổi tên database dưới đây.**

1. Dừng scheduler và queue worker trên máy test trước khi chuyển để tránh xử lý giao dịch ký/mail/job đã sao chép từ nguồn.
2. Kiểm tra cấu hình SmartCA và email của local. Không để dữ liệu sao chép gửi yêu cầu ký hoặc email thật ngoài ý muốn.
3. Trong `.env` của máy local, thay đúng dòng:

```dotenv
DB_DATABASE=cncl_restore_20261102
```

4. Xóa cache cấu hình, sau đó kiểm tra migration, chưa tự động chạy migration:

```powershell
php artisan config:clear
php artisan migrate:status
```

5. Nếu có migration Pending, đọc nội dung và sao lưu database mới trước khi chạy migration cộng thêm cần thiết. Không chạy lệnh tái tạo database.
6. Khởi động lại dev server, dùng cửa sổ ẩn danh hoặc đăng xuất/đăng nhập lại. Kiểm tra danh sách yêu cầu, phiếu, khách hàng, sản phẩm và báo cáo.
7. Chỉ bật lại worker/scheduler khi đã xác nhận môi trường test không tác động thật ra bên ngoài.

Nếu cần quay lại database cũ: dừng worker/scheduler, đổi `DB_DATABASE=cncl_system`, chạy `php artisan config:clear` rồi khởi động lại dev server. Database cũ được giữ nguyên nên không cần nhập lại bản backup chỉ để chuyển về.

## 5. Lưu ý về PDF và dữ liệu nhạy cảm

- SQL chỉ chứa database, không tự khôi phục các file PDF ký số, ảnh chữ ký, logo hoặc file khác trong storage.
- Khi cần kiểm tra PDF từ bản sao lưu server khác, phải sao chép thêm đúng thư mục file riêng tư tương ứng và giữ đường dẫn liên kết. Không đưa PDF riêng tư ra thư mục public.
- Database sao chép có thể chứa tài khoản, session, thông tin khách hàng và giao dịch SmartCA. Chỉ dùng trong môi trường kiểm soát, không chia sẻ file SQL/log ra bên ngoài.
- Backup được lưu tại `storage/app/private/backups`, không đặt trong `public` hoặc `storage/app/public` và không đưa lên Git.

## 6. Khi cần thay dữ liệu trong đúng cncl_system

Đây là thao tác thay thế các bảng có trong dump, không phải gộp thêm dữ liệu. Chỉ làm sau khi đã xác nhận muốn thay dữ liệu hiện tại. Hướng dẫn này dành cho máy local, không tự áp dụng trên VPS đang phục vụ người dùng.

1. Nhập thử và kiểm tra theo mục 2–3 trước; lấy đường dẫn `source-expanded.sql` từ kết quả. Không truyền file GZIP trực tiếp cho mysql.
2. Dừng worker/scheduler. Từ thư mục dự án, chạy `php artisan down`.
3. Mở **Command Prompt (CMD)**, tạo một bản backup mới. Tên file backup phải chưa tồn tại; đường dẫn thư mục phải đã có. Thay thông tin kết nối cho đúng cấu hình local:

```cmd
D:\xampp\mysql\bin\mysqldump.exe --host=127.0.0.1 --port=3306 --user=root --password --single-transaction --quick --hex-blob --routines --events --triggers --default-character-set=utf8mb4 --result-file="D:\backup\cncl_system_truoc_khoi_phuc_20261102.sql" cncl_system
```

Nhập mật khẩu khi được hỏi; nếu tài khoản không có mật khẩu thì Enter. Không ghi mật khẩu vào câu lệnh/tài liệu. Kiểm tra mã thoát `echo %ERRORLEVEL%` bằng 0, file có nội dung và cuối file có `Dump completed`. Nếu lỗi thì dừng, chưa nhập dữ liệu.

4. Trong **CMD**, nhập bản SQL đã giải nén vào đúng database:

```cmd
D:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 --user=root --password --default-character-set=utf8mb4 --binary-mode=1 --max-allowed-packet=512M cncl_system < "C:\Users\thien\Downloads\ten-file-backup.sql"
```

Cú pháp `<` ở trên dùng trong CMD, không dùng trực tiếp trong PowerShell. Phải thay đường dẫn bằng file SQL thật. Kiểm tra `echo %ERRORLEVEL%` bằng 0. Không dùng `--force` để bỏ qua lỗi. Nếu nhập lỗi thì giữ chế độ bảo trì và kiểm tra log/backup, không tiếp tục sử dụng dữ liệu nhập dở.

5. So sánh số bảng và số bản ghi chính với bản nhập thử; kiểm tra các bảng yêu cầu, phiếu, bổ sung, sản phẩm và khách hàng.
6. Quay về thư mục dự án, chạy từng lệnh; nếu có lỗi phải dừng kiểm tra:

```cmd
php artisan migrate:status
php artisan permission:cache-reset
php artisan up
```

Không chạy migration nếu chưa kiểm tra phần Pending; không chạy seeder/refresh/fresh. Chỉ đưa ứng dụng lên sau khi nhập và kiểm tra thành công.

7. Mở trang đăng nhập, đăng nhập lại và kiểm tra. Chỉ bật worker/scheduler khi cấu hình mail/SmartCA đã bảo đảm đúng môi trường test.

Script `restore-test-database.php` vẫn cố ý chỉ cho database mới, không cho ghi đè `cncl_system`; phần nhập đè cần quyết định riêng và backup mới như mục này.
