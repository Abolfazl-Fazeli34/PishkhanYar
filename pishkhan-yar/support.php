<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'درباره ما و پشتیبانی';
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
        flash('لطفاً تمامی فیلدهای الزامی شامل نام، ایمیل صحیح، موضوع و شرح پیام را تکمیل فرمایید.', 'danger');
        redirect('support.php');
    }

    try {
        $st = $pdo->prepare("
            INSERT INTO support_tickets (user_id, name, email, subject, message, status)
            VALUES (?, ?, ?, ?, ?, 'new')
        ");
        $st->execute([$user['id'] ?? null, $name, $email, $subject, $message]);
        flash('پیام و درخواست پشتیبانی شما با موفقیت ثبت گردید. کارشناسان ما به زودی پاسخ را از طریق حساب کاربری یا ایمیل برای شما ارسال خواهند نمود.', 'success');
        if ($user) {
            redirect('account.php?tab=tickets');
        } else {
            redirect('support.php');
        }
    } catch (Throwable $e) {
        flash('خطا در ارسال پیام؛ لطفاً مجدداً تلاش فرمایید.', 'danger');
        redirect('support.php');
    }
}

require 'includes/header.php';
?>

<div class="section-head mt-0">
    <div>
        <h1>درباره پیشخوان‌یار و پشتیبانی مراجعین</h1>
        <p>هدف ما تسهیل انتخاب شماره، شفافیت در هزینه‌ها و نوبت‌گیری آسان دفاتر پیشخوان خدمات دولت است.</p>
    </div>
</div>

<!-- معرفی پیشخوان‌یار -->
<div class="hero" style="background:linear-gradient(135deg, #1e293b 0%, #1e40af 100%);padding:36px;">
    <div class="hero-content">
        <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;">درباره سامانه یکپارچه</span>
        <h2 style="font-size:24px;font-weight:800;margin-top:12px;color:#fff;">همه خدمات سیم‌کارت و پیشخوان در یک بستر هوشمند</h2>
        <p style="font-size:14px;color:#e2e8f0;margin-top:8px;line-height:1.8;">
            «پیشخوان‌یار» بستری مستقل، سریع و قانون‌مدار برای بررسی و خرید مستقیم سیم‌کارت از مالکان حقیقی، اطلاع از آخرین دستورالعمل‌ها و مصوبات اپراتورهای مخابراتی (همراه اول، ایرانسل، رایتل) و دریافت نوبت آنلاین جهت پیشگیری از اتلاف وقت شهروندان در صف‌های طولانی دفاتر پیشخوان خدمات دولت است.
        </p>
    </div>
</div>

<div class="grid-cards" style="margin-top:28px;">
    <article class="card">
        <div class="card-icon" style="background:#eff6ff;color:#2563eb;">🎯</div>
        <h3 style="font-size:16px;font-weight:800;">ماموریت و هدف</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-top:8px;line-height:1.7;">
            حذف واسطه‌گری‌های کاذب در قیمت سیم‌کارت و شفاف‌سازی مراحل اداری، تا هر شهروند پیش از خروج از منزل از مدارک موردنیاز، هزینه دقیق و دفتر مناسب مطلع باشد.
        </p>
    </article>

    <article class="card">
        <div class="card-icon" style="background:#fef3c7;color:#d97706;">🛡️</div>
        <h3 style="font-size:16px;font-weight:800;">امنیت و رویه قانونی</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-top:8px;line-height:1.7;">
            ما هیچ‌گونه انتقال سندی را خارج از دفاتر پیشخوان رسمی و احراز هویت شاهکار توصیه نمی‌کنیم. همه مراحل نهایی معامله در بستر قانونی دفاتر معتبر انجام می‌گیرد.
        </p>
    </article>

    <article class="card">
        <div class="card-icon" style="background:#f0fdf4;color:#16a34a;">⚡</div>
        <h3 style="font-size:16px;font-weight:800;">پشتیبانی همه‌روزه</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-top:8px;line-height:1.7;">
            پاسخگویی به سوالات اداری، راهنمایی در ثبت نوبت و رسیدگی به گزارش تخلفات آگهی‌ها در کوتاه‌ترین زمان ممکن توسط تیم پشتیبانی انجام می‌شود.
        </p>
    </article>
</div>

<!-- فرم ارسال پیام پشتیبانی -->
<div class="section-head" style="margin-top:40px;">
    <div>
        <h2>ارسال پیام و ثبت تیکت پشتیبانی</h2>
        <p>پرسش، پیشنهاد یا گزارش مشکل خود را ارسال کنید تا در اسرع وقت رسیدگی شود.</p>
    </div>
</div>

<div class="panel">
    <form method="post" class="form-grid">
        <?= csrf_field() ?>

        <div class="field">
            <label>نام و نام خانوادگی *</label>
            <input type="text" name="name" value="<?= e($user['full_name'] ?? '') ?>" required placeholder="نام شما">
        </div>

        <div class="field">
            <label>ایمیل جهت دریافت پاسخ *</label>
            <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required placeholder="example@mail.com" dir="ltr">
        </div>

        <div class="field" style="grid-column:1/-1;">
            <label>موضوع تیکت *</label>
            <select name="subject" required>
                <option value="پرسش عمومی">پرسش عمومی درباره خدمات</option>
                <option value="مشکل در رزرو نوبت">مشکل یا سوال در ثبت نوبت اینترنتی</option>
                <option value="گزارش آگهی یا تخلف">گزارش آگهی سیم‌کارت مشکوک یا مغایر</option>
                <option value="درخواست همکاری دفاتر">درخواست همکاری دفاتر پیشخوان دولت</option>
                <option value="پیشنهادات و انتقادات">پیشنهاد بهبود امکانات سامانه</option>
            </select>
        </div>

        <div class="field" style="grid-column:1/-1;">
            <label>شرح کامل پیام و درخواست شما *</label>
            <textarea name="message" required placeholder="لطفاً مشخصات کامل شامل شماره آگهی یا کد پیگیری نوبت را در صورت ارتباط ذکر فرمایید..."></textarea>
        </div>

        <div style="grid-column:1/-1;">
            <button class="btn btn-primary" type="submit">ارسال تیکت پشتیبانی</button>
        </div>
    </form>
</div>

<div class="warning mt-6" style="margin-top:24px;">
    <strong>ساعات کاری مرکز پشتیبانی:</strong> شنبه تا چهارشنبه از ساعت ۸:۰۰ الی ۱۶:۰۰ - پنجشنبه از ساعت ۸:۰۰ الی ۱۳:۰۰<br>
    تیکت‌های ثبت‌شده در خارج از ساعات کاری، در ابتدای اولین روز کاری بعد بررسی خواهند شد.
</div>

<?php require 'includes/footer.php'; ?>
