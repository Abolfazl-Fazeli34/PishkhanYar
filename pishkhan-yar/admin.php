<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'پیشخوان مدیریت سیستم';
$pdo = db();

// بررسی دسترسی ادمین
require_admin();
$adminUser = current_user();

// پردازش عملیات‌های مدیریتی
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // تغییر وضعیت یا ویژگی آگهی
    if ($action === 'update_listing') {
        $listingId = (int)($_POST['listing_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;

        $st = $pdo->prepare("UPDATE listings SET status = ?, is_featured = ? WHERE id = ?");
        $st->execute([$status, $isFeatured, $listingId]);
        flash('وضعیت آگهی با موفقیت به‌روز شد.', 'success');
        redirect('admin.php?tab=listings');
    }

    // حذف آگهی
    if ($action === 'delete_listing') {
        $listingId = (int)($_POST['listing_id'] ?? 0);
        $st = $pdo->prepare("DELETE FROM listings WHERE id = ?");
        $st->execute([$listingId]);
        flash('آگهی با موفقیت حذف گردید.', 'info');
        redirect('admin.php?tab=listings');
    }

    // تغییر وضعیت نوبت
    if ($action === 'update_appointment') {
        $appId = (int)($_POST['appointment_id'] ?? 0);
        $status = $_POST['status'] ?? 'confirmed';
        $st = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $st->execute([$status, $appId]);
        flash('وضعیت نوبت به‌روزرسانی شد.', 'success');
        redirect('admin.php?tab=bookings');
    }

    // افزودن دفتر پیشخوان جدید
    if ($action === 'add_office') {
        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? 'تهران');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $hours = trim($_POST['working_hours'] ?? '');
        $services = trim($_POST['services'] ?? '');

        if ($name === '' || $city === '' || $address === '') {
            flash('نام دفتر، شهر و نشانی الزامی هستند.', 'danger');
        } else {
            $st = $pdo->prepare("
                INSERT INTO offices (name, city, province, address, phone, working_hours, services, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $st->execute([$name, $city, $province, $address, $phone, $hours, $services]);
            flash('دفتر پیشخوان جدید با موفقیت اضافه شد.', 'success');
        }
        redirect('admin.php?tab=offices');
    }

    // فعال / غیرفعال کردن دفتر
    if ($action === 'toggle_office') {
        $officeId = (int)($_POST['office_id'] ?? 0);
        $st = $pdo->prepare("UPDATE offices SET is_active = NOT is_active WHERE id = ?");
        $st->execute([$officeId]);
        flash('وضعیت فعالیت دفتر تغییر یافت.', 'info');
        redirect('admin.php?tab=offices');
    }

    // حذف دفتر
    if ($action === 'delete_office') {
        $officeId = (int)($_POST['office_id'] ?? 0);
        $st = $pdo->prepare("DELETE FROM offices WHERE id = ?");
        $st->execute([$officeId]);
        flash('دفتر پیشخوان حذف گردید.', 'info');
        redirect('admin.php?tab=offices');
    }

    // پاسخ به تیکت پشتیبانی
    if ($action === 'reply_ticket') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $reply = trim($_POST['reply'] ?? '');
        $status = $_POST['status'] ?? 'answered';

        $st = $pdo->prepare("UPDATE support_tickets SET reply = ?, status = ? WHERE id = ?");
        $st->execute([$reply, $status, $ticketId]);
        flash('پاسخ به تیکت ارسال و وضعیت آن به‌روزرسانی شد.', 'success');
        redirect('admin.php?tab=tickets');
    }

    // تغییر نقش کاربر
    if ($action === 'toggle_user_role') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] === 'admin' ? 'admin' : 'user';
        if ($targetUserId !== $adminUser['id']) {
            $st = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $st->execute([$newRole, $targetUserId]);
            flash('نقش کاربر تغییر یافت.', 'success');
        }
        redirect('admin.php?tab=users');
    }
}

$tab = $_GET['tab'] ?? 'overview';

// شمارش‌های آماری
$counts = [
    'users'        => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'listings'     => (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn(),
    'bookings'     => (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
    'requests'     => (int)$pdo->query("SELECT COUNT(*) FROM buy_requests")->fetchColumn(),
    'tickets'      => (int)$pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn(),
    'open_tickets' => (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status='new'")->fetchColumn(),
    'offices'      => (int)$pdo->query("SELECT COUNT(*) FROM offices")->fetchColumn(),
];

// دریافت داده‌ها برای تب‌ها
$allListings = $pdo->query("
    SELECT l.*, u.full_name as owner_name 
    FROM listings l 
    JOIN users u ON u.id = l.user_id 
    ORDER BY l.created_at DESC 
    LIMIT 100
")->fetchAll();

$allBookings = $pdo->query("
    SELECT a.*, o.name as office_name, o.city as office_city, u.full_name as user_name, u.phone as user_phone
    FROM appointments a
    JOIN offices o ON o.id = a.office_id
    JOIN users u ON u.id = a.user_id
    ORDER BY a.visit_date DESC, a.visit_time DESC
    LIMIT 100
")->fetchAll();

$allOffices = $pdo->query("SELECT * FROM offices ORDER BY province, city, name")->fetchAll();

$allTickets = $pdo->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 100")->fetchAll();

$allUsers = $pdo->query("SELECT id, full_name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT 100")->fetchAll();

require 'includes/header.php';
?>

<div class="section-head mt-0">
    <div>
        <h1>پیشخوان مدیریت سامانه پیشخوان‌یار</h1>
        <p>مدیریت جامع کاربران، آگهی‌ها، نوبت‌های حضوری، دفاتر پیشخوان و پاسخگویی به تیکت‌ها.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline">مشاهده وب‌سایت</a>
    </div>
</div>

<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-btn <?= $tab === 'overview' ? 'active' : '' ?>" data-target="tab-adm-overview">داشبورد آمار</button>
        <button class="tab-btn <?= $tab === 'listings' ? 'active' : '' ?>" data-target="tab-adm-listings">مدیریت آگهی‌ها (<?= count($allListings) ?>)</button>
        <button class="tab-btn <?= $tab === 'bookings' ? 'active' : '' ?>" data-target="tab-adm-bookings">نوبت‌های رزرو شده (<?= count($allBookings) ?>)</button>
        <button class="tab-btn <?= $tab === 'offices' ? 'active' : '' ?>" data-target="tab-adm-offices">دفاتر پیشخوان (<?= count($allOffices) ?>)</button>
        <button class="tab-btn <?= $tab === 'tickets' ? 'active' : '' ?>" data-target="tab-adm-tickets">
            تیکت‌های پشتیبانی
            <?php if ($counts['open_tickets'] > 0): ?>
                <span class="badge badge-danger" style="font-size:10px;padding:2px 6px;margin-right:4px;"><?= $counts['open_tickets'] ?> جدید</span>
            <?php endif; ?>
        </button>
        <button class="tab-btn <?= $tab === 'users' ? 'active' : '' ?>" data-target="tab-adm-users">کاربران (<?= count($allUsers) ?>)</button>
    </div>

    <!-- تب آمار داشبورد -->
    <div id="tab-adm-overview" class="tab-pane <?= $tab === 'overview' ? 'active' : '' ?>">
        <div class="grid-four" style="margin-bottom:24px;">
            <div class="card">
                <span class="muted" style="font-size:12px;">کاربران عضو سامانه</span>
                <div style="font-size:26px;font-weight:800;color:#2563eb;margin-top:6px;"><?= number_format($counts['users']) ?></div>
            </div>
            <div class="card">
                <span class="muted" style="font-size:12px;">کل آگهی‌های سیم‌کارت</span>
                <div style="font-size:26px;font-weight:800;color:#10b981;margin-top:6px;"><?= number_format($counts['listings']) ?></div>
            </div>
            <div class="card">
                <span class="muted" style="font-size:12px;">نوبت‌های رزرو شده</span>
                <div style="font-size:26px;font-weight:800;color:#f59e0b;margin-top:6px;"><?= number_format($counts['bookings']) ?></div>
            </div>
            <div class="card">
                <span class="muted" style="font-size:12px;">تیکت‌های باز / کل</span>
                <div style="font-size:26px;font-weight:800;color:#ef4444;margin-top:6px;">
                    <?= $counts['open_tickets'] ?> <span style="font-size:14px;color:var(--text-muted);font-weight:normal;">از <?= $counts['tickets'] ?></span>
                </div>
            </div>
        </div>

        <div class="panel">
            <h3 style="font-size:16px;font-weight:800;margin-bottom:8px;">راهنمای مدیریت سامانه</h3>
            <p style="font-size:13px;color:var(--text-muted);line-height:1.8;">
                از این پنل می‌توانید به تمامی پایگاه‌های داده پیشخوان‌یار دسترسی داشته باشید. تغییر وضعیت آگهی‌ها به حالت ویژه، تایید یا لغو نوبت‌ها، افزودن و غیرفعال‌سازی دفاتر پیشخوان، و پاسخ به پیام‌های کاربران از طریق تب‌های بالا انجام می‌گیرد.
            </p>
        </div>
    </div>

    <!-- تب مدیریت آگهی‌ها -->
    <div id="tab-adm-listings" class="tab-pane <?= $tab === 'listings' ? 'active' : '' ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>شماره سیم‌کارت</th>
                        <th>فروشنده</th>
                        <th>اپراتور</th>
                        <th>قیمت</th>
                        <th>وضعیت</th>
                        <th>ویژه؟</th>
                        <th>اقدامات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allListings as $l): ?>
                        <tr>
                            <td>
                                <strong dir="ltr" style="font-size:14.5px;color:#1e40af;"><?= format_phone($l['phone_number']) ?></strong>
                                <div style="font-size:11px;color:var(--text-muted);"><?= e($l['number_type']) ?> · <?= e($l['sim_type']) ?></div>
                            </td>
                            <td>
                                <?= e($l['owner_name']) ?>
                                <?php if (!empty($l['seller_phone'])): ?><div style="font-size:11px;color:var(--text-muted);" dir="ltr"><?= e($l['seller_phone']) ?></div><?php endif; ?>
                            </td>
                            <td><span class="badge <?= operator_badge_class($l['operator']) ?>"><?= e($l['operator']) ?></span></td>
                            <td><span style="font-weight:700;color:#059669;"><?= toman($l['price']) ?></span></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_listing">
                                    <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                    <input type="hidden" name="is_featured" value="<?= $l['is_featured'] ? '1' : '0' ?>">
                                    <select name="status" onchange="this.form.submit()" style="font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid var(--border);">
                                        <option value="active" <?= $l['status'] === 'active' ? 'selected' : '' ?>>فعال</option>
                                        <option value="sold" <?= $l['status'] === 'sold' ? 'selected' : '' ?>>فروخته شد</option>
                                        <option value="hidden" <?= $l['status'] === 'hidden' ? 'selected' : '' ?>>مخفی</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_listing">
                                    <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                    <input type="hidden" name="status" value="<?= e($l['status']) ?>">
                                    <input type="hidden" name="is_featured" value="<?= $l['is_featured'] ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="<?= $l['is_featured'] ? 'background:#fef08a;color:#854d0e;border-color:#facc15;' : '' ?>">
                                        <?= $l['is_featured'] ? '★ ویژه' : '☆ عادی' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('آیا از حذف این آگهی اطمینان دارید؟');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_listing">
                                    <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- تب مدیریت نوبت‌ها -->
    <div id="tab-adm-bookings" class="tab-pane <?= $tab === 'bookings' ? 'active' : '' ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>کد رهگیری</th>
                        <th>کاربر متقاضی</th>
                        <th>دفتر و خدمت</th>
                        <th>تاریخ و ساعت</th>
                        <th>وضعیت</th>
                        <th>تغییر وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allBookings as $b): ?>
                        <tr>
                            <td>
                                <strong style="font-family:monospace;color:#2563eb;"><?= e($b['tracking_code']) ?></strong>
                            </td>
                            <td>
                                <?= e($b['user_name']) ?>
                                <?php if (!empty($b['user_phone'])): ?><div style="font-size:11px;color:var(--text-muted);" dir="ltr"><?= e($b['user_phone']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($b['office_name']) ?></strong> (<?= e($b['office_city']) ?>)
                                <div style="font-size:11px;color:var(--text-muted);"><?= e($b['service']) ?></div>
                            </td>
                            <td>
                                <?= e($b['visit_date']) ?>
                                <span style="font-size:11px;color:#d97706;"><?= substr($b['visit_time'], 0, 5) ?></span>
                            </td>
                            <td><?= status_badge($b['status']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_appointment">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$b['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" style="font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid var(--border);">
                                        <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>تأیید شده</option>
                                        <option value="completed" <?= $b['status'] === 'completed' ? 'selected' : '' ?>>مراجعه و انجام شد</option>
                                        <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>لغو شده</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- تب مدیریت دفاتر -->
    <div id="tab-adm-offices" class="tab-pane <?= $tab === 'offices' ? 'active' : '' ?>">
        <!-- افزودن دفتر جدید -->
        <div class="panel mb-6" style="margin-bottom:24px;">
            <h3 style="font-size:16px;font-weight:800;margin-bottom:14px;">+ افزودن دفتر پیشخوان جدید</h3>
            <form method="post" class="form-grid">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_office">

                <div class="field">
                    <label>نام دفتر (به همراه کد) *</label>
                    <input type="text" name="name" required placeholder="مثلاً: دفتر پیشخوان دولت کد ۷۲۱۴۱">
                </div>

                <div class="field">
                    <label>شهر *</label>
                    <input type="text" name="city" required placeholder="مثلاً: تهران یا اصفهان">
                </div>

                <div class="field">
                    <label>استان</label>
                    <input type="text" name="province" value="تهران" placeholder="نام استان">
                </div>

                <div class="field">
                    <label>تلفن دفتر</label>
                    <input type="text" name="phone" placeholder="021-xxxxxxxx">
                </div>

                <div class="field" style="grid-column:1/-1;">
                    <label>نشانی کامل *</label>
                    <input type="text" name="address" required placeholder="خیابان، میدان، پلاک...">
                </div>

                <div class="field">
                    <label>ساعات کاری</label>
                    <input type="text" name="working_hours" value="شنبه تا چهارشنبه ۸:۰۰ الی ۱۶:۰۰" placeholder="مثلاً: شنبه تا چهارشنبه ۸ تا ۱۶">
                </div>

                <div class="field">
                    <label>خدمات قابل ارائه (با کاما جدا کنید)</label>
                    <input type="text" name="services" value="انتقال مالکیت، تعویض سیم‌کارت، ترابرد، امور مالیاتی">
                </div>

                <div style="grid-column:1/-1;">
                    <button class="btn btn-primary btn-sm" type="submit">ثبت دفتر پیشخوان</button>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>نام دفتر</th>
                        <th>شهر / استان</th>
                        <th>نشانی و تلفن</th>
                        <th>وضعیت</th>
                        <th>اقدامات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allOffices as $o): ?>
                        <tr>
                            <td><strong><?= e($o['name']) ?></strong></td>
                            <td><span class="badge badge-info"><?= e($o['city']) ?> (<?= e($o['province']) ?>)</span></td>
                            <td>
                                <div style="font-size:12.5px;"><?= e($o['address']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);" dir="ltr"><?= e($o['phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $o['is_active'] ? 'badge-success' : 'badge-muted' ?>">
                                    <?= $o['is_active'] ? 'فعال' : 'غیرفعال' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <form method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_office">
                                        <input type="hidden" name="office_id" value="<?= (int)$o['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            <?= $o['is_active'] ? 'غیرفعال‌سازی' : 'فعال‌سازی' ?>
                                        </button>
                                    </form>

                                    <form method="post" style="display:inline;" onsubmit="return confirm('آیا از حذف این دفتر اطمینان دارید؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_office">
                                        <input type="hidden" name="office_id" value="<?= (int)$o['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- تب تیکت‌های پشتیبانی -->
    <div id="tab-adm-tickets" class="tab-pane <?= $tab === 'tickets' ? 'active' : '' ?>">
        <div style="display:flex;flex-direction:column;gap:18px;">
            <?php if (empty($allTickets)): ?>
                <div class="panel" style="text-align:center;padding:30px;">تیکت پشتیبانی وجود ندارد.</div>
            <?php else: foreach ($allTickets as $t): ?>
                <div class="panel" style="<?= $t['status'] === 'new' ? 'border-color:#f59e0b;background:#fffbeb;' : '' ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                        <div>
                            <strong style="font-size:16px;color:var(--text-main);"><?= e($t['subject']) ?></strong>
                            <div style="font-size:12.5px;color:var(--text-muted);margin-top:2px;">
                                از طرف: <strong><?= e($t['name']) ?></strong> (<?= e($t['email']) ?>) · تاریخ: <?= substr($t['created_at'], 0, 16) ?>
                            </div>
                        </div>
                        <div><?= status_badge($t['status']) ?></div>
                    </div>

                    <div style="background:#fff;padding:12px;border-radius:var(--radius-sm);margin-top:12px;font-size:13px;line-height:1.7;border:1px solid var(--border);">
                        <?= nl2br(e($t['message'])) ?>
                    </div>

                    <!-- فرم پاسخ مدیریت -->
                    <form method="post" style="margin-top:14px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reply_ticket">
                        <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">

                        <div class="field">
                            <label style="font-size:12px;">پاسخ به کاربر:</label>
                            <textarea name="reply" style="min-height:70px;" placeholder="پاسخ را در اینجا بنویسید..."><?= e($t['reply'] ?? '') ?></textarea>
                        </div>

                        <div style="display:flex;gap:10px;margin-top:10px;align-items:center;">
                            <select name="status" style="font-size:12px;padding:6px 12px;border-radius:6px;border:1px solid var(--border);">
                                <option value="answered" <?= $t['status'] === 'answered' ? 'selected' : '' ?>>علامت‌گذاری به عنوان پاسخ‌داده‌شده</option>
                                <option value="closed" <?= $t['status'] === 'closed' ? 'selected' : '' ?>>بستن تیکت</option>
                                <option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>در حال بررسی</option>
                            </select>
                            <button class="btn btn-primary btn-sm" type="submit">ثبت و ارسال پاسخ</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- تب مدیریت کاربران -->
    <div id="tab-adm-users" class="tab-pane <?= $tab === 'users' ? 'active' : '' ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>نام کاربر</th>
                        <th>ایمیل</th>
                        <th>تلفن</th>
                        <th>نقش کاربری</th>
                        <th>تاریخ عضویت</th>
                        <th>تغییر سطح دسترسی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td><strong><?= e($u['full_name']) ?></strong></td>
                            <td><span dir="ltr"><?= e($u['email']) ?></span></td>
                            <td><span dir="ltr"><?= e($u['phone'] ?: '---') ?></span></td>
                            <td><?= status_badge($u['role'] === 'admin' ? 'مدیر ارشد' : 'کاربر عادی') ?></td>
                            <td style="font-size:12px;color:var(--text-muted);"><?= substr($u['created_at'], 0, 10) ?></td>
                            <td>
                                <?php if ($u['id'] !== $adminUser['id']): ?>
                                    <form method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_user_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            <?= $u['role'] === 'admin' ? 'تبدیل به کاربر عادی' : 'ارتقا به مدیر' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted" style="font-size:12px;">حساب جاری شما</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
