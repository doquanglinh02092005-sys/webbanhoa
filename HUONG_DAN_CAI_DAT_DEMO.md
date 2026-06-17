# Huong dan tai va cai dat website Linh Florist de demo

Tai lieu nay dung de gui cho ban be/thay co tai project ve may va chay demo bang XAMPP.

## 1. Yeu cau truoc khi cai

Can cai san:

- XAMPP, gom Apache va MySQL/MariaDB.
- Trinh duyet Chrome, Edge hoac Safari.
- Neu dung cach clone code: cai them Git.

Project la PHP thuan MVC tu xay, khong can cai Laravel, Node.js, React hay Composer.

## 2. Tai project ve may

Co 2 cach:

### Cach 1: Tai ZIP tu GitHub

1. Mo repo:

```text
https://github.com/doquanglinh02092005-sys/webbanhoa.git
```

2. Bam `Code` -> `Download ZIP`.
3. Giai nen file ZIP.
4. Doi ten thu muc sau khi giai nen thanh:

```text
Webbanhoa
```

### Cach 2: Dung Git clone

Mo Terminal hoac Git Bash va chay:

```bash
git clone https://github.com/doquanglinh02092005-sys/webbanhoa.git Webbanhoa
```

## 3. Dua project vao thu muc htdocs

Copy thu muc `Webbanhoa` vao thu muc `htdocs` cua XAMPP.

Vi du tren macOS:

```text
/Applications/XAMPP/xamppfiles/htdocs/Webbanhoa
```

Vi du tren Windows:

```text
C:\xampp\htdocs\Webbanhoa
```

Neu ban dat ten thu muc khac `Webbanhoa`, URL khi mo web cung phai doi theo ten thu muc do.

## 4. Bat Apache va MySQL

1. Mo XAMPP Control Panel hoac XAMPP Manager.
2. Start:

```text
Apache
MySQL
```

Neu Apache hoac MySQL khong start duoc, thu tat cac ung dung dang chiem cong 80/3306, vi du Skype, MySQL khac, Docker.

## 5. Khoi tao database va tai khoan admin

Mo trinh duyet va truy cap:

```text
http://localhost/Webbanhoa/setup.php
```

Trang setup se tu dong:

- Tao database `web_ban_hoa`.
- Tao cac bang can thiet.
- Them du lieu san pham mau.
- Tao tai khoan admin dau tien.

Nhap thong tin admin, vi du:

```text
Ho ten: Admin Demo
Email: admin@example.com
Mat khau: 12345678
```

Sau khi tao thanh cong, website se chuyen sang trang dang nhap.

## 6. Mo website de demo

Trang chu:

```text
http://localhost/Webbanhoa/
```

Dang nhap:

```text
http://localhost/Webbanhoa/login.php
```

Dang ky tai khoan khach:

```text
http://localhost/Webbanhoa/register.php
```

Trang tai khoan khach:

```text
http://localhost/Webbanhoa/account.php
```

Trang quan tri admin:

```text
http://localhost/Webbanhoa/admin/index.php
```

## 7. Cac chuc nang co the demo

Phia khach hang:

- Xem danh sach san pham.
- Tim kiem, loc, sap xep san pham.
- Xem chi tiet san pham.
- Them san pham vao gio hang.
- Dang ky, dang nhap.
- Dat hang bang COD.
- Dat hang bang chuyen khoan ngan hang.
- Xem tai khoan, diem thuong, lich su don hang.
- Xem chi tiet don hang.
- Sua thong tin nhan hang neu don con `Cho xac nhan` va `Chua thanh toan`.
- Huy don neu don con `Cho xac nhan` va `Chua thanh toan`.

Phia admin:

- Dang nhap admin.
- Quan ly san pham.
- Them, sua, xoa, an/hien san pham.
- Quan ly danh muc.
- Quan ly don hang.
- Xac nhan thanh toan cho don chuyen khoan.
- Cap nhat trang thai don hang.
- Huy don va hoan ton kho/diem thuong.
- Quan ly khach hang.

## 8. Thanh toan chuyen khoan demo

Website hien dung chuyen khoan ngan hang thu cong.

Thong tin hien thi sau khi khach dat don:

```text
Ngan hang: MB Bank
So tai khoan: 0981028774
Chu tai khoan: Do Quang Linh
Noi dung chuyen khoan: Ma don hang
```

Vi du ma don la:

```text
LF2606124BBEB3F3
```

Khach can chuyen khoan voi noi dung:

```text
LF2606124BBEB3F3
```

Admin sau khi kiem tra tai khoan ngan hang thi vao chi tiet don va doi trang thai thanh toan sang `Da thanh toan`.

## 9. Luu y ve database

Mac dinh project ket noi MySQL cua XAMPP voi thong tin:

```text
Host: 127.0.0.1
Port: 3306
Database: web_ban_hoa
User: root
Password: de trong
```

Neu MySQL cua may ban co mat khau root, can sua file:

```text
config/database.php
```

Hoac cau hinh bang bien moi truong:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
```

## 10. Loi thuong gap

### Loi 404 Not Found

Kiem tra lai:

- Project da nam trong thu muc `htdocs` chua.
- Ten thu muc co dung la `Webbanhoa` khong.
- URL co dung `http://localhost/Webbanhoa/` khong.

### Loi khong ket noi database

Kiem tra:

- MySQL trong XAMPP da start chua.
- User/password database co dung khong.
- Thu mo lai:

```text
http://localhost/Webbanhoa/setup.php
```

### Trang chu khong load san pham tu database

Chay lai setup:

```text
http://localhost/Webbanhoa/setup.php
```

Neu da co admin va database, trang setup se bao he thong da khoi tao.

### Khong upload duoc anh san pham

Kiem tra thu muc:

```text
uploads/products
```

Can dam bao Apache co quyen ghi vao thu muc nay.

## 11. Cach reset de demo lai tu dau

Neu muon xoa database va tao lai tu dau:

1. Mo phpMyAdmin:

```text
http://localhost/phpmyadmin
```

2. Xoa database:

```text
web_ban_hoa
```

3. Mo lai:

```text
http://localhost/Webbanhoa/setup.php
```

4. Tao lai tai khoan admin.

## 12. Ghi chu khi nop/demo

- Nen tao san tai khoan admin de demo nhanh.
- Nen tao 1 tai khoan khach de demo dat hang.
- Nen test truoc cac luong: them gio hang, checkout COD, checkout chuyen khoan, admin cap nhat don.
- Khong can cau hinh MoMo vi checkout hien da thay bang chuyen khoan ngan hang thu cong.
