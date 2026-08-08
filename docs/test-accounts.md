# Tài khoản test nghiệm thu

Các tài khoản này được tạo bởi `database/seeders/UserSeeder.php`.

Mật khẩu mặc định cho tất cả tài khoản test:

```text
123123123
```

Email test dùng chung:

```text
thientuantest@gmail.com
```

## Tài khoản quản trị và giám sát

| Vai trò | Username | Trung tâm | Mục đích test |
| --- | --- | --- | --- |
| Admin | `admin` | Toàn hệ thống | Quản trị danh mục, người dùng, phân quyền, cấu hình, log, báo cáo, dữ liệu API VNPT SmartCA |
| Lãnh đạo | `lanhdao` | Toàn hệ thống | Dashboard tổng, báo cáo tổng hợp, log hệ thống, không thao tác nghiệp vụ |
| Viewer | `viewer` | Toàn hệ thống | Kiểm tra chế độ chỉ xem, không có nút thao tác |

## Tài khoản Trung tâm phân phối

| Vai trò | Username | Mã trung tâm | Tên trung tâm | Mục đích test |
| --- | --- | --- | --- | --- |
| Trung tâm | `trungtam_np` | NP | Nam Phương | Tạo khách hàng/yêu cầu, kiểm tra chỉ thấy dữ liệu NP |
| Trung tâm | `trungtam_tp` | TP | Tam Phước | Kiểm tra cô lập dữ liệu giữa TP và trung tâm khác |
| Trung tâm | `trungtam_hp` | HP | Hồng Phước | Kiểm tra tạo yêu cầu, cấp lại phiếu |
| Trung tâm | `trungtam_hd` | HD | Hà Dung | Kiểm tra dữ liệu riêng theo trung tâm |
| Trung tâm | `trungtam_th` | TH | Thái Hoà | Kiểm tra dữ liệu riêng theo trung tâm |

## Tài khoản nghiệp vụ nội bộ

| Vai trò | Username | Trung tâm | Mục đích test |
| --- | --- | --- | --- |
| DVKH | `dvkh` | Toàn hệ thống | Kiểm tra, duyệt, trả lại yêu cầu; cảnh báo trùng số hóa đơn |
| PTN | `ptn` | Toàn hệ thống | Tiếp nhận yêu cầu, lập phiếu CNCL, cấp phiếu trực tiếp |
| Trưởng PTN | `truongptn` | Toàn hệ thống | Duyệt ký, gửi ký VNPT SmartCA, kiểm tra kết quả ký, trả lại phiếu |

## Lưu ý khi test SmartCA

- Tài khoản `truongptn` chưa điền `smartca_user_id` trong seeder.
- Trước khi test ký số thật, vào màn Người dùng và nhập đúng định danh VNPT SmartCA thật cho tài khoản `truongptn`.
- `smartca_user_id` phải là CCCD/MST/SĐT theo tài khoản VNPT SmartCA, không dùng username nội bộ nếu VNPT không cấp định danh đó.

## Checklist sử dụng

1. Chạy seeder tài khoản:

```bash
php artisan db:seed --class=UserSeeder
```

2. Mở checklist nghiệm thu:

```text
docs/acceptance-checklist.md
```

3. Đăng nhập lần lượt từng tài khoản, kiểm tra dashboard/sidebar/dữ liệu/nút thao tác theo checklist.
