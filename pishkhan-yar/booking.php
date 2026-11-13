<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'نوبت‌دهی آنلاین دفاتر پیشخوان';
$pdo = db();
$user = current_user();

// پردازش فرم ثبت نوبت
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_login();

    $officeId = (int)($_POST['office_id'] ?? 0);
    $service = trim($_POST['service'] ?? '');
    $date = trim($_POST['visit_date'] ?? '');
    $time = trim($_POST['visit_time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // اعتبارسنجی تاریخ و ساعت
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
        flash('تاریخ یا ساعت انتخاب‌شده نامعتبر است.', 'danger');
        redirect('booking.php');
    }

    // بررسی اینکه تاریخ گذشته نباشد
    if ($date < date('Y-m-d')) {
        flash('امکان ثبت نوبت برای تاریخ‌های گذشته وجود ندارد.', 'danger');
        redirect('booking.php');
    }

    // بررسی وجود دفتر
    $checkOffice = $pdo->prepare("SELECT id, name FROM offices WHERE id=? AND is_active=1");
    $checkOffice->execute([$officeId]);
    if (!$checkOffice->fetch()) {
        flash('دفتر پیشخوان انتخابی یافت نشد یا غیرفعال است.', 'danger');
        redirect('booking.php');
    }

    $pdo->beginTransaction();
    try {
        // قفل کردن بازه زمانی جهت جلوگیری از ثبت همزمان دو نوبت در یک اسلات
        $checkSlot = $pdo->prepare("
            SELECT id FROM appointments 
            WHERE office_id=? AND visit_date=? AND visit_time=? AND status='confirmed' 
            FOR UPDATE
        ");
        $checkSlot->execute([$officeId, $date, $time]);
        if ($checkSlot->fetch()) {
            $pdo->rollBack();
            flash('این بازه زمانی متأسفانه توسط مراجعه‌کننده دیگری رزرو شده است. لطفاً ساعت یا تاریخ دیگری را انتخاب فرمایید.', 'warning');
            redirect('booking.php?office=' . $officeId);
        }

        // تولید کد پیگیری یکتا
        $code = 'PY' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));

        $ins = $pdo->prepare("
            INSERT INTO appointments (user_id, office_id, service, visit_date, visit_time, notes, tracking_code, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        $ins->execute([$user['id'], $officeId, $service, $date, $time, $notes, $code]);
        $pdo->commit();

        flash('نوبت شما با موفقیت رزرو گردید. کد پیگیری شما: ' . $code, 'success');
        redirect('booking.php?track=' . urlencode($code));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('متأسفانه در فرآیند ثبت نوبت خطایی رخ داد؛ لطفاً مجدداً تلاش فرمایید.', 'danger');
        redirect('booking.php');
    }
}

// اگر کد پیگیری از طریق URL ارسال شده باشد
$trackedCode = trim($_GET['track'] ?? '');
$trackedAppointment = null;
if ($trackedCode !== '') {
    try {
        $stTrack = $pdo->prepare("
            SELECT a.*, o.name as office_name, o.city as office_city, o.address as office_address, o.phone as office_phone, u.full_name as user_name
            FROM appointments a
            JOIN offices o ON o.id = a.office_id
            JOIN users u ON u.id = a.user_id
            WHERE a.tracking_code = ?
        ");
        $stTrack->execute([$trackedCode]);
        $trackedAppointment = $stTrack->fetch();
        if (!$trackedAppointment) {
            flash('نوبتی با کد پیگیری واردشده یافت نشد.', 'warning');
        }
    } catch (Throwable) {}
}

// فهرست دفاتر فعال
$offices = [];
try {
    $offices = $pdo->query("SELECT id, name, city, province FROM offices WHERE is_active=1 ORDER BY province, city, name")->fetchAll();
} catch (Throwable) {}

$selectedOffice = (int)($_GET['office'] ?? 0);
$selectedService = trim($_GET['service'] ?? '');

$servicesList = [
    'انتقال سند و مالکیت سیم‌کارت',
    'تعویض سیم‌کارت مفقودی یا سوخته',
    'تبدیل سیم‌کارت اعتباری به دائمی',
    'ترابرد شماره بین اپراتورها (MNP)',
    'مسدودی موقت و رفع مسدودی خط',
    'خدمات احراز هویت شاهکار و ثنا',
    'تأییدیه کد پستی و امور مالیاتی',
    'سایر خدمات پیشخوان و اپراتورها',
];

$timeSlots = [
    '08:30', '09:00', '09:30', '10:00', '10:30', '11:00',
    '11:30', '12:00', '12:30', '13:00', '13:30', '14:00',
    '14:30', '15:00', '15:30', '16:00'
];

require 'includes/header.php';
?>

<div class="section-head mt-0">
    <div>
        <h1>سامانه آنلاین نوبت‌دهی دفاتر پیشخوان</h1>
        <p>بدون اتلاف وقت و ایستادن در صف؛ دفتر، خدمت و زمان مراجعه خود را انتخاب کنید.</p>
    </div>
    <div>
        <a class="btn btn-outline" href="#tracking-section">پیگیری نوبت قبلی</a>
    </div>
</div>

<!-- نمایش رسید نوبت در صورت جستجوی کد رهگیری یا پس از ثبت موفق -->
<?php if ($trackedAppointment): ?>
    <div class="receipt-card">
        <div class="receipt-header">
            <span class="badge badge-success" style="font-size:13px;padding:6px 14px;">نوبت رسمی تأیید شده</span>
            <h2 style="font-size:20px;font-weight:800;margin-top:12px;color:var(--text-main);">رسید الکترونیکی رزرو نوبت پیشخوان</h2>
            <div class="tracking-badge" title="برای کپی کلیک کنید" data-copy="<?= e($trackedAppointment['tracking_code']) ?>" style="cursor:pointer;">
                <?= e($trackedAppointment['tracking_code']) ?>
            </div>
            <p style="font-size:11.5px;color:var(--text-muted);margin-top:6px;">کد رهگیری فوق را هنگام مراجعه به باجه ارائه دهید.</p>
        </div>

        <div style="margin-top:20px;">
            <div class="receipt-row">
                <span class="label">نام متقاضی:</span>
                <span class="value"><?= e($trackedAppointment['user_name']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">نام دفتر پیشخوان:</span>
                <span class="value"><?= e($trackedAppointment['office_name']) ?> (<?= e($trackedAppointment['office_city']) ?>)</span>
            </div>
            <div class="receipt-row">
                <span class="label">نشانی دقیق دفتر:</span>
                <span class="value" style="font-weight:500;font-size:12.5px;"><?= e($trackedAppointment['office_address']) ?></span>
            </div>
            <?php if (!empty($trackedAppointment['office_phone'])): ?>
                <div class="receipt-row">
                    <span class="label">تلفن دفتر:</span>
                    <span class="value" dir="ltr"><?= e($trackedAppointment['office_phone']) ?></span>
                </div>
            <?php endif; ?>
            <div class="receipt-row">
                <span class="label">خدمت موردنظر:</span>
                <span class="value" style="color:#0284c7;"><?= e($trackedAppointment['service']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">تاریخ مراجعه:</span>
                <span class="value"><?= e($trackedAppointment['visit_date']) ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">ساعت حضور:</span>
                <span class="value" style="color:#d97706;"><?= substr($trackedAppointment['visit_time'], 0, 5) ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">وضعیت نوبت:</span>
                <span class="value"><?= status_badge($trackedAppointment['status']) ?></span>
            </div>
            <?php if (!empty($trackedAppointment['notes'])): ?>
                <div class="receipt-row">
                    <span class="label">توضیحات متقاضی:</span>
                    <span class="value" style="font-size:12px;font-weight:normal;"><?= e($trackedAppointment['notes']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div style="background:#f8fafc;padding:14px;border-radius:var(--radius-sm);margin-top:20px;font-size:12.5px;color:#475569;line-height:1.7;">
            <strong>مدارک ضروری همراه:</strong>
            <ul style="padding-right:18px;margin-top:6px;">
                <li>اصل کارت ملی هوشمند یا شناسنامه جدید عکس‌دار</li>
                <li>سیم‌کارت فعال و گوشی موبایل جهت احراز هویت پیامکی</li>
                <li>در صورت انتقال خط، حضور فیزیکی هم‌زمان خریدار و فروشنده الزامی است.</li>
            </ul>
        </div>

        <div style="display:flex;gap:10px;justify-content:center;margin-top:24px;" class="no-print">
            <button type="button" class="btn btn-primary btn-print">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                چاپ رسید نوبت
            </button>
            <a href="booking.php" class="btn btn-outline">ثبت نوبت جدید</a>
        </div>
    </div>
<?php endif; ?>

<!-- فرم رزرو نوبت -->
<div class="panel" style="margin-top:24px;">
    <h2 style="font-size:18px;font-weight:800;color:var(--text-main);margin-bottom:16px;">فرم درخواست نوبت حضوری</h2>

    <?php if (!$user): ?>
        <div style="text-align:center;padding:30px;">
            <p style="font-size:15px;color:var(--text-muted);">برای رزرو نوبت و دریافت کد پیگیری رسمی، ابتدا باید وارد حساب کاربری خود شوید.</p>
            <a class="btn btn-primary" style="margin-top:14px;" href="account.php">ورود / ایجاد حساب کاربری</a>
        </div>
    <?php else: ?>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>

            <div class="field">
                <label>دفتر پیشخوان دولت مقصد *</label>
                <select name="office_id" required>
                    <option value="">-- انتخاب دفتر پیشخوان --</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?= (int)$o['id'] ?>" <?= $selectedOffice === (int)$o['id'] ? 'selected' : '' ?>>
                            <?= e($o['city']) ?> — <?= e($o['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>نوع خدمت اداری *</label>
                <select name="service" required>
                    <?php foreach ($servicesList as $s): ?>
                        <option value="<?= e($s) ?>" <?= $selectedService === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>تاریخ مراجعه *</label>
                <input type="date" name="visit_date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" required value="<?= date('Y-m-d') ?>">
                <span class="field-help">امکان انتخاب تا ۳۰ روز آینده فراهم است.</span>
            </div>

            <div class="field">
                <label>ساعت بازه مراجعه *</label>
                <select name="visit_time" required>
                    <option value="">-- انتخاب ساعت حضور --</option>
                    <?php foreach ($timeSlots as $slot): ?>
                        <option value="<?= $slot ?>"><?= $slot ?> الی <?= date('H:i', strtotime($slot . ' +30 minutes')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="grid-column:1/-1;">
                <label>توضیحات تکمیلی (اختیاری)</label>
                <textarea name="notes" placeholder="اگر نیاز به توضیح خاصی یا تعداد سیم‌کارت‌های مورد نظر دارید در اینجا قید فرمایید..."></textarea>
            </div>

            <div style="grid-column:1/-1;">
                <button class="btn btn-primary" type="submit">تأیید نهایی و رزرو نوبت اینترنتی</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- بخش پیگیری نوبت -->
<div class="panel" id="tracking-section" style="margin-top:36px;background:#f8fafc;border-color:#cbd5e1;">
    <h3 style="font-size:16px;font-weight:800;color:var(--text-main);">پیگیری و استعلام وضعیت نوبت</h3>
    <p style="font-size:13px;color:var(--text-muted);margin-top:4px;">اگر قبلاً نوبتی ثبت کرده‌اید، با وارد کردن کد رهگیری می‌توانید جزئیات و رسید آن را بررسی فرمایید.</p>

    <form method="get" action="booking.php" style="display:flex;gap:10px;margin-top:14px;max-width:500px;">
        <input type="text" name="track" placeholder="کد رهگیری (مثلاً PY7A9C2)" value="<?= e($trackedCode) ?>" required style="flex:1;border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 14px;font-family:monospace;direction:ltr;text-align:right;">
        <button type="submit" class="btn btn-primary">استعلام نوبت</button>
    </form>
</div>

<div class="warning mt-6" style="margin-top:24px;">
    <strong>راهنمای حضور در دفتر:</strong> لطفاً حداقل ۱۰ دقیقه پیش از موعد مقرر در دفتر حاضر شوید و کد رهگیری خود را به همراه اصل مدارک هویتی به متصدی باجه اعلام فرمایید.
</div>

<?php require 'includes/footer.php'; ?>
