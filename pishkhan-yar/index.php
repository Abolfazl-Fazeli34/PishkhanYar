<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'صفحه اصلی';

$pdo = db();

// آمار کلی برای نمایش در هیرو
$stats = [
    'listings' => 0,
    'offices' => 0,
    'appointments' => 0,
];
try {
    $stats['listings'] = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn();
    $stats['offices'] = (int)$pdo->query("SELECT COUNT(*) FROM offices WHERE is_active=1")->fetchColumn();
    $stats['appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='confirmed'")->fetchColumn();
} catch (Throwable) {}

// آخرین آگهی‌های فعال
$latestListings = [];
try {
    $st = $pdo->query("SELECT id, operator, phone_number, sim_type, number_type, price, is_featured, seller_name, seller_phone FROM listings WHERE status='active' ORDER BY is_featured DESC, created_at DESC LIMIT 6");
    $latestListings = $st->fetchAll();
} catch (Throwable) {}

// دفاتر برگزیده
$offices = [];
try {
    $st = $pdo->query("SELECT id, name, city, province, address, phone, working_hours FROM offices WHERE is_active=1 ORDER BY id ASC LIMIT 3");
    $offices = $st->fetchAll();
} catch (Throwable) {}

require 'includes/header.php';
?>

<!-- هیرو و جستجوی اصلی -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-pill">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            سامانه تخصصی بازار سیم‌کارت و خدمات پیشخوان دولت
        </span>
        <h1>قبل از مراجعه، همه‌چیز را بدانید؛<br>سیم‌کارتتان را هوشمندانه انتخاب کنید.</h1>
        <p>خرید و فروش شفاف سیم‌کارت، آگاهی از مدارک و مراحل قانونی انتقال خط و نوبت‌دهی آنلاین دفاتر پیشخوان در سراسر کشور.</p>

        <form class="hero-search" action="market.php" method="get">
            <input type="search" name="q" placeholder="شماره، پیش‌شماره (مثلاً 0912 یا 935) یا اپراتور را جستجو کنید..." aria-label="جستجوی سیم‌کارت">
            <button type="submit">جستجوی سیم‌کارت</button>
        </form>

        <div class="hero-stats">
            <div class="hero-stat-item">
                <span class="hero-stat-number"><?= number_format($stats['listings']) ?>+</span>
                <span class="hero-stat-label">سیم‌کارت فعال در بازار</span>
            </div>
            <div class="hero-stat-item">
                <span class="hero-stat-number"><?= number_format($stats['offices']) ?></span>
                <span class="hero-stat-label">دفتر همکار در کشور</span>
            </div>
            <div class="hero-stat-item">
                <span class="hero-stat-number"><?= number_format($stats['appointments']) ?>+</span>
                <span class="hero-stat-label">نوبت رزرو شده موفق</span>
            </div>
        </div>
    </div>
</section>

<!-- دسترسی‌های سریع اصلی -->
<div class="section-head">
    <div>
        <h2>دسترسی سریع به سامانه‌ها</h2>
        <p>خدمت مورد نظر خود را برای دسترسی سریع‌تر انتخاب کنید.</p>
    </div>
</div>

<div class="grid-four">
    <a class="quick-link" href="market.php">
        <span class="card-icon" style="background:#eff6ff;color:#2563eb;margin-bottom:0;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
        </span>
        <div>
            <div class="quick-link-title">بازار سیم‌کارت</div>
            <div class="quick-link-desc">خرید و فروش خطوط رند و معمولی</div>
        </div>
    </a>

    <a class="quick-link" href="services.php">
        <span class="card-icon" style="background:#f0fdf4;color:#16a34a;margin-bottom:0;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </span>
        <div>
            <div class="quick-link-title">راهنمای خدمات اپراتورها</div>
            <div class="quick-link-desc">مدارک و مراحل تغییر مالکیت و تعویض</div>
        </div>
    </a>

    <a class="quick-link" href="offices.php">
        <span class="card-icon" style="background:#fef3c7;color:#d97706;margin-bottom:0;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </span>
        <div>
            <div class="quick-link-title">دفاتر پیشخوان دولت</div>
            <div class="quick-link-desc">نشانی، تلفن و ساعات کاری دفاتر</div>
        </div>
    </a>

    <a class="quick-link" href="booking.php">
        <span class="card-icon" style="background:#f3e8ff;color:#9333ea;margin-bottom:0;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </span>
        <div>
            <div class="quick-link-title">نوبت‌دهی آنلاین</div>
            <div class="quick-link-desc">رزرو تاریخ و ساعت بدون انتظار در صف</div>
        </div>
    </a>
</div>

<!-- ویجت پیگیری سریع نوبت -->
<div class="panel" style="margin-top:24px;background:linear-gradient(to right, #eff6ff, #f8fafc);border-color:#bfdbfe;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <h3 style="font-size:16px;font-weight:800;color:#1e3a8a;">پیگیری سریع نوبت اینترنتی</h3>
            <p style="font-size:13px;color:#475569;margin-top:2px;">کد پیگیری اختصاصی خود (مثلاً PY...) را وارد کنید تا وضعیت نوبت و مشخصات دفتر را فوراً مشاهده نمایید.</p>
        </div>
        <form action="booking.php" method="get" style="display:flex;gap:8px;flex:1;max-width:440px;">
            <input type="text" name="track" placeholder="کد رهگیری نوبت (مثلاً PY4F8B...)" required style="flex:1;border:1px solid #93c5fd;border-radius:var(--radius-sm);padding:9px 14px;font-family:monospace;direction:ltr;text-align:right;">
            <button type="submit" class="btn btn-primary" style="white-space:nowrap;">استعلام نوبت</button>
        </form>
    </div>
</div>

<!-- آخرین آگهی‌های سیم‌کارت -->
<div class="section-head">
    <div>
        <h2>ویترین جدیدترین سیم‌کارت‌ها</h2>
        <p>شماره‌های رند و قیمت مناسب ثبت‌شده توسط فروشندگان معتبر.</p>
    </div>
    <a class="btn btn-soft" href="market.php">مشاهده همه آگهی‌ها (<?= count($latestListings) ?>)</a>
</div>

<div class="grid-cards">
    <?php if (!empty($latestListings)): ?>
        <?php foreach ($latestListings as $item): ?>
            <?php 
                $analysis = calculate_number_score($item['phone_number']);
                $badgeClass = operator_badge_class($item['operator']);
            ?>
            <article class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="badge <?= $badgeClass ?>"><?= e($item['operator']) ?></span>
                    <?php if ($item['is_featured']): ?>
                        <span class="badge" style="background:#fef08a;color:#854d0e;">★ ویژه</span>
                    <?php endif; ?>
                    <span class="badge badge-muted"><?= e($item['sim_type']) ?></span>
                </div>

                <div class="sim-number" title="برای کپی کلیک کنید" data-copy="<?= e($item['phone_number']) ?>" style="cursor:pointer;">
                    <?= format_phone($item['phone_number']) ?>
                </div>

                <div style="font-size:12px;color:var(--text-muted);display:flex;justify-content:space-between;">
                    <span>نوع خط: <?= e($item['number_type']) ?></span>
                    <span style="color:#0284c7;font-weight:700;"><?= e($analysis['label']) ?></span>
                </div>

                <div class="score-bar-wrap" title="امتیاز رند بودن: <?= $analysis['score'] ?>%">
                    <div class="score-bar-fill" style="width:<?= $analysis['score'] ?>%;"></div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:12px;border-top:1px solid var(--border);">
                    <div>
                        <div style="font-size:11px;color:var(--text-muted);">قیمت نهایی:</div>
                        <div class="sim-price"><?= toman($item['price']) ?></div>
                    </div>
                    <?php if (!empty($item['seller_phone'])): ?>
                        <a href="tel:<?= e($item['seller_phone']) ?>" class="btn btn-soft btn-sm" title="تماس با فروشنده">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>تماس</span>
                        </a>
                    <?php else: ?>
                        <a href="market.php?q=<?= urlencode($item['phone_number']) ?>" class="btn btn-outline btn-sm">جزئیات</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="panel" style="grid-column:1/-1;text-align:center;padding:40px 20px;">
            <h3>هنوز آگهی سیم‌کارتی ثبت نشده است</h3>
            <p class="muted" style="margin-top:6px;">شما می‌توانید اولین آگهی سیم‌کارت خود را در بازار ثبت کنید.</p>
            <a class="btn btn-primary" style="margin-top:14px;" href="market.php#new-listing">ثبت اولین آگهی</a>
        </div>
    <?php endif; ?>
</div>

<!-- دفاتر منتخب و نوبت‌دهی -->
<div class="section-head">
    <div>
        <h2>دفاتر پیشخوان دولت منتخب</h2>
        <p>یافتن اطلاعات دفاتر، ساعات پذیرش و ثبت نوبت اینترنتی.</p>
    </div>
    <a class="btn btn-soft" href="offices.php">مشاهده تمامی دفاتر</a>
</div>

<div class="grid-cards">
    <?php if (!empty($offices)): ?>
        <?php foreach ($offices as $office): ?>
            <article class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="badge badge-info"><?= e($office['city']) ?> (<?= e($office['province']) ?>)</span>
                    <span class="badge badge-success">فعال</span>
                </div>

                <h3 style="font-size:16px;font-weight:800;color:var(--text-main);margin-top:14px;"><?= e($office['name']) ?></h3>
                <p style="font-size:12.5px;color:var(--text-muted);margin-top:6px;line-height:1.6;"><?= e($office['address']) ?></p>

                <?php if (!empty($office['working_hours'])): ?>
                    <div style="font-size:12px;color:#475569;margin-top:10px;background:#f8fafc;padding:6px 10px;border-radius:6px;">
                        ⏰ <?= e($office['working_hours']) ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top:auto;padding-top:16px;display:flex;gap:8px;">
                    <a href="booking.php?office=<?= (int)$office['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;">
                        دریافت نوبت اینترنتی
                    </a>
                    <?php if (!empty($office['phone'])): ?>
                        <a href="tel:<?= e($office['phone']) ?>" class="btn btn-outline btn-sm" title="تماس با دفتر">
                            تلفن
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- چرا پیشخوان‌یار -->
<div class="section-head">
    <div>
        <h2>چرا پیشخوان‌یار؟</h2>
        <p>ویژگی‌ها و ابزارهایی برای تصمیم‌گیری امن، ارزان و شفاف.</p>
    </div>
</div>

<div class="grid-four">
    <article class="card">
        <div class="card-icon" style="background:#e0f2fe;color:#0284c7;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </div>
        <h3 style="font-size:15px;font-weight:700;">معاملات مستقیم و بی‌واسطه</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin-top:6px;">تماس بی‌واسطه با فروشنده حقیقی سیم‌کارت و حذف کمیسیون‌ها و دلالان واسطه.</p>
    </article>

    <article class="card">
        <div class="card-icon" style="background:#fef3c7;color:#d97706;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
        </div>
        <h3 style="font-size:15px;font-weight:700;">ارزیابی و امتیاز هوشمند رند</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin-top:6px;">الگوریتم تخصصی سنجش تقارن، تکرار و پله‌ای بودن ارقام برای ارزش‌گذاری منصفانه.</p>
    </article>

    <article class="card">
        <div class="card-icon" style="background:#dcfce7;color:#15803d;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <h3 style="font-size:15px;font-weight:700;">نوبت‌دهی آنلاین بدون صف</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin-top:6px;">انتخاب روز و ساعت دقیق مراجعه حضوری به دفتر پیشخوان و صدور رسید رهگیری معتبر.</p>
    </article>

    <article class="card">
        <div class="card-icon" style="background:#f3e8ff;color:#7e22ce;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
        </div>
        <h3 style="font-size:15px;font-weight:700;">راهنمای جامع مدارک و قوانین</h3>
        <p style="font-size:12.5px;color:var(--text-muted);margin-top:6px;">اطلاع دقیق از مدارک موردنیاز سامانه شاهکار، هزینه‌های مصوب و فرایند احراز هویت.</p>
    </article>
</div>

<?php require 'includes/footer.php'; ?>
