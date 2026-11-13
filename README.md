# سامانه هوشمند پیشخوان‌یار 🏢

سامانه یکپارچه بازار سیم‌کارت، راهنمای خدمات اپراتورها و نوبت‌دهی آنلاین دفاتر پیشخوان دولت.

## ویژگی‌ها

- **بازار سیم‌کارت**: خرید، فروش و درخواست خرید معکوس
- **راهنمای خدمات اپراتورها**: انتقال مالکیت، تعویض، ترابرد و...
- **دفاتر پیشخوان دولت**: اطلاعات کامل دفاتر در سراسر کشور
- **نوبت‌دهی آنلاین**: رزرو تاریخ و ساعت مراجعه با کد رهگیری
- **پنل مدیریت**: مدیریت کامل آگهی‌ها، نوبت‌ها، دفاتر و تیکت‌ها
- **پشتیبانی تیکتی**: سیستم ثبت و پیگیری تیکت پشتیبانی

## راه‌اندازی با Docker

### پیش‌نیازها
- Docker Engine 20.10+
- Docker Compose v2.0+

### نصب سریع

```bash
# ۱. کلون پروژه
git clone https://github.com/Abolfazl-Fazeli34/PishkhanYar.git
cd PishkhanYar

# ۲. ساخت فایل محیطی
cp .env.example .env

# ۳. راه‌اندازی
./pishkhanyar.sh start
```

سایت روی آدرس زیر در دسترس است:
```
http://localhost:8670
```

### پورت‌ها (فقط localhost)

| سرویس      | پورت  | آدرس                        |
|------------|-------|------------------------------|
| برنامه اصلی | 8670 | http://localhost:8670        |
| MySQL      | 8671  | localhost:8671               |
| phpMyAdmin | 8672  | http://localhost:8672        |

## ساختار پروژه

```
PishkhanYar/
├── docker/
│   └── php/
│       ├── Dockerfile       # تصویر PHP + Apache
│       ├── php.ini          # تنظیمات PHP
│       └── apache.conf      # تنظیمات Apache
├── pishkhan-yar/
│   ├── admin/               # پنل مدیریت
│   ├── assets/              # CSS و JavaScript
│   ├── database/
│   │   └── schema.sql       # طرح پایگاه داده + داده‌های نمونه
│   ├── includes/
│   │   ├── functions.php    # توابع مشترک
│   │   ├── header.php       # سربرگ مشترک
│   │   └── footer.php       # پابرگ مشترک
│   ├── config.php           # تنظیمات اتصال به پایگاه داده
│   ├── index.php            # صفحه اصلی
│   ├── market.php           # بازار سیم‌کارت
│   ├── services.php         # راهنمای خدمات
│   ├── offices.php          # دفاتر پیشخوان
│   ├── booking.php          # نوبت‌دهی آنلاین
│   ├── support.php          # پشتیبانی
│   ├── account.php          # حساب کاربری
│   └── admin.php            # پنل مدیریت
├── docker-compose.yml       # تنظیمات Docker Compose
├── .env.example             # نمونه متغیرهای محیطی
├── .gitignore
├── .dockerignore
└── pishkhanyar.sh           # اسکریپت مدیریت پروژه
```

## دستورات مدیریتی

```bash
./pishkhanyar.sh start          # راه‌اندازی تمام سرویس‌ها
./pishkhanyar.sh stop           # توقف سرویس‌ها
./pishkhanyar.sh restart        # راه‌اندازی مجدد
./pishkhanyar.sh status         # وضعیت کانتینرها
./pishkhanyar.sh logs           # مشاهده لاگ‌ها
./pishkhanyar.sh logs app       # لاگ سرویس خاص
./pishkhanyar.sh shell          # ورود به shell اپلیکیشن
./pishkhanyar.sh db-backup      # پشتیبان‌گیری از پایگاه داده
./pishkhanyar.sh db-import      # بازیابی پایگاه داده
```

## اطلاعات ورود (نمونه)

| نقش  | ایمیل                | رمز عبور    |
|------|----------------------|-------------|
| مدیر | admin@pishkhan.ir    | admin12345  |
| کاربر | user@demo.ir        | password123 |

## استقرار روی سرور

برای استقرار روی سرور با ساب‌دامین `pishkhanyar.ip3.ir`، یک reverse proxy (مثلاً Nginx یا Caddy) روی سرور تنظیم کنید که ترافیک را به پورت `127.0.0.1:8670` هدایت کند.

نمونه تنظیمات Nginx:

```nginx
server {
    listen 80;
    server_name pishkhanyar.ip3.ir;

    location / {
        proxy_pass http://127.0.0.1:8670;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## تکنولوژی‌ها

- **Backend**: PHP 8.3
- **وب‌سرور**: Apache 2.4
- **پایگاه داده**: MySQL 8.0
- **مدیریت پایگاه داده**: phpMyAdmin 5
- **کانتینرسازی**: Docker + Docker Compose
