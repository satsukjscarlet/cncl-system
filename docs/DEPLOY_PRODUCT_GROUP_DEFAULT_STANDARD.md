# Cap nhat tieu chuan mac dinh cua nhom san pham

## Trien khai VPS

- Backup database va kiem tra ban backup truoc khi cap nhat.
- Chon gio it nguoi dung. Khong chay migration fresh/refresh/reset, khong seed data test.
- Trong thu muc ung dung, chay tung lenh; neu lenh loi thi dung de kiem tra, khong bat ung dung lai khi migration chua thanh cong.

```sh
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Kiem tra cac migration pending truoc khi chay migrate. Migration cua tinh nang nay:
`2026_10_21_000001_add_default_quality_standard_to_product_groups.php`.
Chi them cot nullable va khoa ngoai, khong cap nhat san pham/phieu cu.
Khong can PermissionSeeder. Khoi dong lai scheduler chay thuong tru theo cach quan ly dich vu hien tai neu co.

## Cau hinh va kiem tra

1. Mo Danh muc Nhom san pham > Sua > Tieu chuan chat luong mac dinh.
2. Doi chieu tieu chuan dang ghi trong ghi chu va chon thu cong. Khong tu dong tach ghi chu, khong xoa ghi chu cu.
3. Tao san pham: chon nhom, kiem tra tieu chuan tu dien; thu doi sang tieu chuan rieng.
4. Mo san pham cu: tieu chuan cu phai giu nguyen. Doi mac dinh cua nhom khong sua san pham cu.
5. Import san pham: ma tieu chuan phai ton tai va dang su dung. He thong khong tu tao tieu chuan tu ma Excel nua.
6. Excel de trong tieu chuan: san pham moi lay mac dinh dang su dung cua nhom; san pham cu giu gia tri cu (ke ca dang trong).

File mau va xuat Excel nhom co cot Ma tieu chuan mac dinh, Ghi chu va Trang thai. Co the sua file xuat roi import lai theo ma nhom. Ma tieu chuan de trong giu cau hinh cu; khong dung o trong de xoa lien ket. Trang thai 1/0; de trong giu trang thai cu. Neu co loi, toan bo file khong duoc luu; xem thong bao dong loi tren man danh sach. File cu co cot tieu_chuan van duoc doc nhu ghi chu, khong tu gan lien ket mac dinh. Tieu chuan ngung su dung chi duoc giu lai khi da la mac dinh cua chinh nhom do, khong gan moi cho nhom khac.
Neu can quay lai code cu, giu cot moi trong database; khong rollback database hang loat.
