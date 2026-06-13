# Linh Florist

## Chay website bang XAMPP

1. Mo XAMPP Manager va bat `Apache` va `MySQL`.
2. Dat ma nguon trong thu muc `htdocs` cua XAMPP.
3. Mo `http://localhost/setup.php` de tao database va tai khoan admin lan dau.
4. Sau khi thiet lap, mo `http://localhost/`.

## Cau truc MVC

```text
app/
  Controllers/  Xu ly request, validate va dieu huong
  Models/       Truy van database va nghiep vu du lieu
  Views/        Giao dien dang nhap, tai khoan va checkout
  Core/         Controller, Model va View co so
admin/          Entrypoint tuong thich URL va view quan tri
api/            Entrypoint JSON, goi API controller
includes/       Bootstrap, session, schema va layout dung chung
```

Các file `login.php`, `register.php`, `account.php`, `checkout.php` va `api/*.php`
chi la entrypoint mong, moi xu ly chinh nam trong `app/`.

## Tai khoan va phan quyen

- Tai khoan dang ky tu `register.php` luon co quyen `customer`.
- Chi `admin` truy cap duoc `admin/index.php`.
- Admin co the doi role va khoa/mo khoa tai khoan.
- Admin co the quan ly dashboard, danh muc, san pham, ton kho, don hang va khach hang.
- San pham dang ban duoc tai tu database qua `api/products.php`.
- Mat khau duoc luu bang `password_hash`, khong luu mat khau goc.

## Database

Mac dinh he thong ket noi MariaDB cua XAMPP voi:

```text
Host: 127.0.0.1
Port: 3306
Database: web_ban_hoa
User: root
Password: de trong
```

Co the thay doi bang cac bien moi truong `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

## Tich diem khach hang

- Mac dinh moi `10.000d` tien hang duoc `1 diem`.
- Diem chi duoc cong khi don hang co trang thai `Hoan thanh` va `Da thanh toan`.
- Neu don bi huy hoac hoan tien, diem cua don duoc thu hoi tu dong.
- Doi ty le bang `loyalty_vnd_per_point` trong `config/local.php` hoac bien moi truong `LOYALTY_VND_PER_POINT`.

## Thanh toan COD va MoMo

COD hoat dong ngay. De bat MoMo sandbox:

1. Sao chep `config/local.example.php` thanh `config/local.php`.
2. Dien `partner_code`, `access_key`, `secret_key` do MoMo Business cap.
3. Dien `app_url` la URL HTTPS cong khai tro den website. MoMo khong the gui IPN den `localhost`.
4. Giu endpoint sandbox `https://test-payment.momo.vn` khi kiem thu.
5. Khi MoMo duyet production, doi endpoint thanh `https://payment.momo.vn` va dung bo khoa production.

Khong commit `config/local.php` vi file nay chua Secret Key.
