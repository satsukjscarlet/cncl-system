# Thu hoi phien sau khi doi mat khau

## Trien khai

Cap nhat code va lam moi cache theo quy trinh VPS hien co. Khong can migration/seed, khong xoa du lieu nghiep vu.

```sh
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Khoi dong lai PHP worker/dich vu ung dung thuong tru neu dang su dung. Kiem tra cau hinh SESSION_DRIVER thuc te (ke ca config cache), SESSION_CONNECTION va SESSION_TABLE tren VPS, khong suy tu may test.

Sau khi cap nhat, doi/dat lai mat khau tai khoan bi chia se mot lan nua. Doi mat khau truoc khi trien khai khong tu dong chay lai viec thu hoi.

## Co che va gioi han

- Doi mat khau luon thu hoi cac phien khac, bo tuy chon bo qua thu hoi. Phien dang doi duoc giu lai.
- AuthenticateSession cua Laravel doi chieu dau van tay mat khau tren moi request web, truoc middleware kiem tra thiet bi. Chan phien cu ke ca request da dang ky lai session database sau khi bi xoa.
- Remember token duoc xoay khi revokeUserSessions, tranh cookie Ghi nho dang nhap khoi phuc phien cu. Viec xoa session dung dung session.connection/session.table.
- Phien file/Redis da duoc middleware gan dau van tay cung bi chan khi mat khau thay doi. Phien cu tao truoc khi trien khai, chua tung duoc gan dau van tay, khong the duoc stock middleware nhan dien hoi to. Neu VPS dung file/Redis, can len lich buoc dang nhap lai cac phien cu qua cong cu quan tri session cua he thong hosting; khong xoa Redis/cache hang loat khi chua xac dinh namespace. Database driver xoa truc tiep cac phien cua dung tai khoan khi doi/dat lai mat khau.
- PDF qua quality-certificates/{id}/pdf can auth, phan quyen va pham vi trung tam; da co no-store/no-cache. File da tai ve, dinh kem email hoac PDF da nap vao bo nho trinh duyet khong the thu hoi bang logout. Request dang xu ly truoc thoi diem doi mat khau cung khong bi huy giua chung.

## Kiem tra hai may

1. Dang nhap cung tai khoan tren A/B, bat Ghi nho tren B, mo mot trang he thong tren ca hai.
2. Tren A doi mat khau. A van vao duoc Tai khoan cua toi.
3. Tren B tai lai trang hoac mo URL PDF moi: phai yeu cau dang nhap. Khong chi kiem tra tab PDF da tai san.
4. Tren B thu thao tac gui/luu tu tab cu: khong duoc thay doi du lieu (co the nhan 419 khi CSRF token da het hieu luc).
5. Mat khau cu khong dang nhap duoc; mat khau moi dang nhap binh thuong. Thu them admin dat lai mat khau.

## Kiem thu

63 tests / 849 assertions: SessionRevocationTest, PasswordUpdateTest, AuthenticationTest, RoleWorkspaceAccessTest va CertificateWorkflowTest; chi SQLite :memory:.
Lan chay rong hon phat hien 6 test scaffold cu chua khop route hien tai: 4 test quen/reset mat khau qua email (route da tat) va 2 test tu xoa tai khoan (DELETE profile da tat). Khong mo lai cac chuc nang nay de lam test xanh.
