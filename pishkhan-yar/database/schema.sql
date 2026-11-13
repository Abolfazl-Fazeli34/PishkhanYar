CREATE DATABASE IF NOT EXISTS pishkhan_yar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pishkhan_yar;

-- جدول کاربران
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول آگهی‌های سیم‌کارت
CREATE TABLE IF NOT EXISTS listings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  operator VARCHAR(40) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  sim_type VARCHAR(20) NOT NULL DEFAULT 'اعتباری',
  number_type VARCHAR(30) NOT NULL DEFAULT 'معمولی',
  prefix VARCHAR(12) NOT NULL,
  price BIGINT UNSIGNED NOT NULL DEFAULT 0,
  seller_name VARCHAR(120) NULL,
  seller_phone VARCHAR(20) NULL,
  description TEXT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','pending','sold','hidden') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_listing_search (operator, sim_type, status),
  INDEX idx_listing_phone (phone_number),
  INDEX idx_listing_prefix (prefix),
  INDEX idx_listing_price (price),
  CONSTRAINT fk_listing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول درخواست‌های خرید معکوس
CREATE TABLE IF NOT EXISTS buy_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  desired_pattern VARCHAR(60) NOT NULL,
  budget BIGINT UNSIGNED NOT NULL DEFAULT 0,
  operator VARCHAR(40) NOT NULL DEFAULT 'فرقی ندارد',
  contact_phone VARCHAR(20) NULL,
  notes TEXT NULL,
  status ENUM('open','matched','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_buy_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول دفاتر پیشخوان
CREATE TABLE IF NOT EXISTS offices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  city VARCHAR(100) NOT NULL,
  province VARCHAR(100) NOT NULL DEFAULT 'تهران',
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(40) NULL,
  working_hours VARCHAR(100) NULL,
  services TEXT NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول نوبت‌دهی
CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  office_id INT UNSIGNED NOT NULL,
  service VARCHAR(100) NOT NULL,
  visit_date DATE NOT NULL,
  visit_time TIME NOT NULL,
  notes TEXT NULL,
  tracking_code VARCHAR(20) NOT NULL UNIQUE,
  status ENUM('confirmed','cancelled','completed') NOT NULL DEFAULT 'confirmed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_office_slot (office_id, visit_date, visit_time, status),
  CONSTRAINT fk_appointment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appointment_office FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول علاقه‌مندی‌ها
CREATE TABLE IF NOT EXISTS favorites (
  user_id INT UNSIGNED NOT NULL,
  listing_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, listing_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تیکت‌های پشتیبانی
CREATE TABLE IF NOT EXISTS support_tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  reply TEXT NULL,
  status ENUM('new','in_progress','answered','closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- کاربران نمونه
-- مدیر: admin@pishkhan.ir / رمز: admin12345
-- کاربر دمو: user@demo.ir / رمز: password123
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
(1, 'مدیر ارشد پیشخوان‌یار', 'admin@pishkhan.ir', '09120000000', '$2y$10$7GkGF76XBgprzT9ZR2JVbO1bqhmj9OmZxRXGl1wzvdYd/Rcvei4Gi', 'admin'),
(2, 'علی رضایی', 'user@demo.ir', '09121112233', '$2y$10$KCSuUmOtVFGiUkmMkWN6OesjaewK8Aq55aY1PUNirn7pv9BCtiZOa', 'user'),
(3, 'سارا محمدی', 'sara@demo.ir', '09351234567', '$2y$10$KCSuUmOtVFGiUkmMkWN6OesjaewK8Aq55aY1PUNirn7pv9BCtiZOa', 'user')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- دفاتر نمونه
INSERT INTO offices (id, name, city, province, address, phone, working_hours, services) VALUES
(1, 'دفتر پیشخوان دولت کد ۷۲۱۴۱', 'تهران', 'تهران', 'میدان ولیعصر، ابتدای بلوار کشاورز، پلاک ۱۸', '021-88901234', 'شنبه تا چهارشنبه ۸:۰۰ الی ۱۶:۳۰ - پنجشنبه ۸:۰۰ الی ۱۳:۰۰', 'انتقال مالکیت، تعویض سیم‌کارت، ترابرد، خدمات ثبت احوال، عوارض خودرو'),
(2, 'دفتر پیشخوان خدمات دولت کد ۷۲۱۸۵', 'تهران', 'تهران', 'صادقیه، فلکه دوم، مجتمع طلا، طبقه همکف', '021-44223344', 'شنبه تا چهارشنبه ۸:۰۰ الی ۱۷:۰۰ - پنجشنبه ۸:۰۰ الی ۱۳:۳۰', 'خدمات همراه اول، خدمات ایرانسل، رایتل، تأییدیه پستی، امور مالیاتی'),
(3, 'دفتر پیشخوان مرکزی آزادی کد ۷۲۳۰۲', 'تهران', 'تهران', 'خیابان آزادی، بعد از دانشگاه شریف، جنب بانک ملی', '021-66005511', 'شنبه تا پنجشنبه ۸:۰۰ الی ۱۵:۳۰', 'انتقال مالکیت سیم‌کارت، تعویض فوری سیم‌کارت، صدور کارت بهداشت'),
(4, 'دفتر پیشخوان دولت کد ۴۰۱۱', 'مشهد', 'خراسان رضوی', 'بلوار سجاد، نبش بهارستان ۳، ساختمان سامان', '051-37654321', 'شنبه تا چهارشنبه ۸:۰۰ الی ۱۶:۰۰ - پنجشنبه ۸:۰۰ الی ۱۲:۳۰', 'خدمات کلیه اپراتورها، احراز هویت سجام، کارت سوخت، امور شهرداری'),
(5, 'دفتر پیشخوان دولت کد ۳۰۴۲', 'اصفهان', 'اصفهان', 'خیابان چهارباغ بالا، مجتمع کوثر، واحد ۱۲', '031-36209988', 'شنبه تا چهارشنبه ۸:۰۰ الی ۱۵:۳۰', 'انتقال مالکیت، تعویض پوکه، استعلام شاهکار، ثبت‌نام ثنا'),
(6, 'دفتر پیشخوان دولت کد ۵۰۲۰', 'شیراز', 'فارس', 'خیابان زند، روبه‌روی بیمارستان سعدی، کوچه ۲۴', '071-32334455', 'شنبه تا چهارشنبه ۸:۰۰ الی ۱۶:۰۰', 'انتقال مالکیت سیم‌کارت، خدمات مالیاتی، تاییدیه کد پستی'),
(7, 'دفتر پیشخوان دولت کد ۲۱۵۵', 'تبریز', 'آذربایجان شرقی', 'خیابان امام خمینی، چهارراه آبرسان، برج بلور', '041-33355667', 'شنبه تا چهارشنبه ۸:۰۰ الی ۱۵:۰۰', 'خدمات همراه اول، ایرانسل و رایتل، ثبت اسناد و املاک'),
(8, 'دفتر پیشخوان دولت کد ۳۱۱۰', 'کرج', 'البرز', 'میدان شهدا، ابتدای خیابان مظاهری، پلاک ۴۵', '026-32211445', 'شنبه تا پنجشنبه ۸:۰۰ الی ۱۶:۰۰', 'تعویض سیم‌کارت، انتقال مالکیت، پرداخت قبوض و جرایم')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- آگهی‌های نمونه سیم‌کارت
INSERT INTO listings (id, user_id, operator, phone_number, sim_type, number_type, prefix, price, seller_name, seller_phone, description, is_featured, status) VALUES
(1, 2, 'همراه اول', '09121112233', 'دائمی', 'رند', '0912', 185000000, 'علی رضایی', '09121112233', 'خط دائمی کد ۱ تهران، سند تک‌برگ و صفر خشک، انتقال مالکیت در دفتر پیشخوان رسمی.', 1, 'active'),
(2, 2, 'همراه اول', '09123456789', 'دائمی', 'رند', '0912', 320000000, 'علی رضایی', '09121112233', 'کد ۳ تهران، رند پله‌ای صعودی استثنایی و کم‌نظیر. آماده انتقال فوری.', 1, 'active'),
(3, 3, 'ایرانسل', '09355555555', 'اعتباری', 'رند', '0935', 95000000, 'سارا محمدی', '09351234567', 'شماره ۵ رقمی تکرار پشت سر هم، فوق‌العاده مناسب برای برندینگ و تبلیغات محیطی.', 1, 'active'),
(4, 2, 'همراه اول', '09128901234', 'دائمی', 'معمولی', '0912', 45000000, 'علی رضایی', '09121112233', 'سیم‌کارت کد ۸ تهران، کارکرده اما کاملاً زنگ‌خور صفر، تخفیف پای معامله.', 0, 'active'),
(5, 3, 'ایرانسل', '09361234000', 'دائمی', 'رند', '0936', 18000000, 'سارا محمدی', '09351234567', 'دائمی طرح آی‌سیم، بدون بدهی و خوش‌قیمت، خاتمه سه صفر.', 0, 'active'),
(6, 2, 'رایتل', '09210001212', 'دائمی', 'رند', '0921', 12500000, 'علی رضایی', '09121112233', 'رایتل رند آینه‌ای و زوج، با بسته سالانه اینترنت فعال.', 0, 'active'),
(7, 3, 'همراه اول', '09194005060', 'اعتباری', 'رند', '0919', 8500000, 'سارا محمدی', '09351234567', 'رند دو به دو خوش‌آوا و خوانا، بنام و بدون زنگ‌خور.', 0, 'active'),
(8, 2, 'ایرانسل', '09021234567', 'اعتباری', 'رند', '0902', 15000000, 'علی رضایی', '09121112233', 'رند سریالی متوالی ۷ رقم پشت سر هم، قیمت مناسب.', 0, 'active')
ON DUPLICATE KEY UPDATE phone_number=VALUES(phone_number);

-- درخواست‌های خرید نمونه
INSERT INTO buy_requests (id, user_id, desired_pattern, budget, operator, contact_phone, notes, status) VALUES
(1, 2, '0912*222****', 70000000, 'همراه اول', '09121112233', 'خریدار نقدی کد ۲ همراه اول با ارقام تکرار ۲، انتقال همان روز.', 'open'),
(2, 3, '0935*777*', 25000000, 'ایرانسل', '09351234567', 'به دنبال شماره رند ایرانسل برای فروشگاه آنلاین هستم.', 'open')
ON DUPLICATE KEY UPDATE desired_pattern=VALUES(desired_pattern);

-- تیکت‌های پشتیبانی نمونه
INSERT INTO support_tickets (id, user_id, name, email, subject, message, reply, status) VALUES
(1, 2, 'علی رضایی', 'user@demo.ir', 'پرسش عمومی', 'آیا امکان رزرو نوبت برای چند خدمت هم‌زمان در یک مراجعه وجود دارد؟', 'سلام و درود، بله شما می‌توانید در هنگام رزرو یا در قسمت توضیحات خدمات مد نظرتان را قید نمایید.', 'answered')
ON DUPLICATE KEY UPDATE subject=VALUES(subject);
