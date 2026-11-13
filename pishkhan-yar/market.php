<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'بازار سیم‌کارت';
$pdo = db();
$user = current_user();

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ثبت آگهی فروش سیم‌کارت
    if ($action === 'listing') {
        require_login();
        $rawPhone = trim($_POST['phone_number'] ?? '');
        $phone = normalize_number($rawPhone);
        
        // اصلاح شماره در صورت تایپ بدون صفر اولیه
        if (strlen($phone) === 10 && str_starts_with($phone, '9')) {
            $phone = '0' . $phone;
        }

        if (!preg_match('/^09\d{9}$/', $phone)) {
            flash('شماره واردشده نامعتبر است؛ شماره موبایل باید ۱۱ رقمی و با 09 آغاز شود.', 'danger');
            redirect('market.php#new-listing');
        }

        $operator = trim($_POST['operator'] ?? 'همراه اول');
        $simType = in_array($_POST['sim_type'] ?? '', ['دائمی', 'اعتباری']) ? $_POST['sim_type'] : 'اعتباری';
        $numberType = in_array($_POST['number_type'] ?? '', ['رند', 'معمولی']) ? $_POST['number_type'] : 'معمولی';
        
        $prefix = trim($_POST['prefix'] ?? '');
        if ($prefix === '') {
            $prefix = substr($phone, 0, 4);
        }

        $price = (int)normalize_number((string)($_POST['price'] ?? 0));
        if ($price < 0) $price = 0;

        $sellerName = trim($_POST['seller_name'] ?? '') ?: ($user['full_name'] ?? 'فروشنده');
        $sellerPhone = normalize_number($_POST['seller_phone'] ?? '') ?: ($user['phone'] ?? $phone);
        $description = trim($_POST['description'] ?? '');

        try {
            $stmt = $pdo->prepare("
                INSERT INTO listings (user_id, operator, phone_number, sim_type, number_type, prefix, price, seller_name, seller_phone, description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $user['id'], $operator, $phone, $simType, $numberType, $prefix, $price, $sellerName, $sellerPhone, $description
            ]);
            flash('آگهی سیم‌کارت شما با موفقیت ثبت شد و در دسترس خریداران قرار گرفت.', 'success');
        } catch (Throwable $e) {
            flash('خطا در ثبت آگهی: لطفاً مجدداً تلاش نمایید.', 'danger');
        }
        redirect('market.php');
    }

    // ثبت درخواست خرید معکوس
    if ($action === 'buy_request') {
        require_login();
        $desiredPattern = trim($_POST['desired_pattern'] ?? '');
        $budget = (int)normalize_number((string)($_POST['budget'] ?? 0));
        $operator = trim($_POST['operator'] ?? 'فرقی ندارد');
        $contactPhone = normalize_number($_POST['contact_phone'] ?? '') ?: ($user['phone'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($desiredPattern === '') {
            flash('الگوی شماره یا رند موردنظر را وارد نمایید.', 'warning');
            redirect('market.php#buy-request');
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO buy_requests (user_id, desired_pattern, budget, operator, contact_phone, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, 'open')
            ");
            $stmt->execute([$user['id'], $desiredPattern, $budget, $operator, $contactPhone, $notes]);
            flash('درخواست خرید شما ثبت شد و به فروشندگان نمایش داده می‌شود.', 'success');
        } catch (Throwable $e) {
            flash('خطا در ثبت درخواست خرید.', 'danger');
        }
        redirect('market.php?tab=requests');
    }

    // نشان کردن / حذف از نشان‌شده‌ها (علاقه‌مندی‌ها)
    if ($action === 'toggle_favorite') {
        require_login();
        $listingId = (int)($_POST['listing_id'] ?? 0);
        if ($listingId > 0) {
            $check = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id=? AND listing_id=?");
            $check->execute([$user['id'], $listingId]);
            if ($check->fetch()) {
                $del = $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND listing_id=?");
                $del->execute([$user['id'], $listingId]);
                flash('آگهی از نشان‌شده‌ها حذف شد.', 'info');
            } else {
                $ins = $pdo->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
                $ins->execute([$user['id'], $listingId]);
                flash('آگهی به نشان‌شده‌های شما اضافه شد.', 'success');
            }
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? 'market.php';
        redirect($ref);
    }
}

// پارامترهای فیلتر و جستجو
$q = trim($_GET['q'] ?? '');
$operator = trim($_GET['operator'] ?? '');
$simType = trim($_GET['sim_type'] ?? '');
$numberType = trim($_GET['number_type'] ?? '');
$maxPrice = (int)normalize_number((string)($_GET['max_price'] ?? 0));
$sort = $_GET['sort'] ?? 'newest';
$activeTab = $_GET['tab'] ?? 'listings';

// لیست شناسه‌های نشان‌شده توسط کاربر
$userFavs = [];
if ($user) {
    try {
        $stFav = $pdo->prepare("SELECT listing_id FROM favorites WHERE user_id=?");
        $stFav->execute([$user['id']]);
        $userFavs = $stFav->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable) {}
}

// پرس‌وجوی آگهی‌ها
$sql = "SELECT l.*, u.full_name as owner_name FROM listings l JOIN users u ON u.id = l.user_id WHERE l.status='active'";
$params = [];

if ($q !== '') {
    $normQ = normalize_number($q);
    $searchPattern = '%' . ($normQ !== '' ? $normQ : $q) . '%';
    $sql .= " AND (l.phone_number LIKE ? OR l.prefix LIKE ? OR l.description LIKE ?)";
    array_push($params, $searchPattern, $searchPattern, '%' . $q . '%');
}
if ($operator !== '') {
    $sql .= " AND l.operator = ?";
    $params[] = $operator;
}
if ($simType !== '') {
    $sql .= " AND l.sim_type = ?";
    $params[] = $simType;
}
if ($numberType !== '') {
    $sql .= " AND l.number_type = ?";
    $params[] = $numberType;
}
if ($maxPrice > 0) {
    $sql .= " AND l.price <= ?";
    $params[] = $maxPrice;
}

// مرتب‌سازی
$sql .= match ($sort) {
    'price_asc'  => " ORDER BY l.price ASC, l.created_at DESC",
    'price_desc' => " ORDER BY l.price DESC, l.created_at DESC",
    default      => " ORDER BY l.is_featured DESC, l.created_at DESC",
};
$sql .= " LIMIT 120";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

// دریافت درخواست‌های خرید فعال
$buyRequests = [];
try {
    $buyRequests = $pdo->query("
        SELECT r.*, u.full_name 
        FROM buy_requests r 
        JOIN users u ON u.id=r.user_id 
        WHERE r.status='open' 
        ORDER BY r.created_at DESC 
        LIMIT 50
    ")->fetchAll();
} catch (Throwable) {}

require 'includes/header.php';
?>

<div class="section-head mt-0">
    <div>
        <h1>بازار تخصصی سیم‌کارت</h1>
        <p>خرید و فروش بی‌واسطه خطوط رند، کد قدیمی، دائمی و اعتباری همراه اول، ایرانسل و رایتل.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="btn btn-primary" href="#new-listing">ثبت آگهی فروش</a>
        <a class="btn btn-outline" href="#buy-request">درخواست خرید معکوس</a>
    </div>
</div>

<!-- زبانه‌های اصلی بازار -->
<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-btn <?= $activeTab === 'listings' ? 'active' : '' ?>" data-target="tab-listings">
            ویترین آگهی‌های فروش (<?= count($listings) ?>)
        </button>
        <button class="tab-btn <?= $activeTab === 'requests' ? 'active' : '' ?>" data-target="tab-requests">
            درخواست‌های خریداران (<?= count($buyRequests) ?>)
        </button>
    </div>

    <!-- تب ۱: لیست آگهی‌ها و فیلترها -->
    <div id="tab-listings" class="tab-pane <?= $activeTab === 'listings' ? 'active' : '' ?>">
        <!-- فیلتر و جستجو -->
        <div class="panel mb-6" style="margin-bottom:24px;">
            <form method="get" class="form-grid">
                <div class="field">
                    <label>جستجوی شماره یا پیش‌شماره</label>
                    <input type="text" name="q" value="<?= e($q) ?>" placeholder="مثلاً 0912 یا 0935 یا 777...">
                </div>

                <div class="field">
                    <label>اپراتور</label>
                    <select name="operator">
                        <option value="">همه اپراتورها</option>
                        <?php foreach (['همراه اول', 'ایرانسل', 'رایتل', 'شاتل موبایل', 'سایر'] as $op): ?>
                            <option value="<?= e($op) ?>" <?= $operator === $op ? 'selected' : '' ?>><?= e($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>نوع سیم‌کارت</label>
                    <select name="sim_type">
                        <option value="">همه</option>
                        <option value="دائمی" <?= $simType === 'دائمی' ? 'selected' : '' ?>>دائمی</option>
                        <option value="اعتباری" <?= $simType === 'اعتباری' ? 'selected' : '' ?>>اعتباری</option>
                    </select>
                </div>

                <div class="field">
                    <label>نوع شماره</label>
                    <select name="number_type">
                        <option value="">همه شماره‌ها</option>
                        <option value="رند" <?= $numberType === 'رند' ? 'selected' : '' ?>>فقط شماره‌های رند</option>
                        <option value="معمولی" <?= $numberType === 'معمولی' ? 'selected' : '' ?>>شماره‌های معمولی</option>
                    </select>
                </div>

                <div class="field">
                    <label>حداکثر بودجه (تومان)</label>
                    <input type="number" name="max_price" min="0" value="<?= $maxPrice ?: '' ?>" placeholder="مثلاً 50000000">
                </div>

                <div class="field">
                    <label>مرتب‌سازی بر اساس</label>
                    <select name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>جدیدترین آگهی‌ها</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>ارزان‌ترین قیمت</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>بالاترین قیمت</option>
                    </select>
                </div>

                <div style="grid-column:1/-1;display:flex;gap:10px;justify-content:flex-end;">
                    <a href="market.php" class="btn btn-outline">پاکسازی فیلترها</a>
                    <button class="btn btn-primary" type="submit">اعمال فیلتر و جستجو</button>
                </div>
            </form>
        </div>

        <!-- نمایش آگهی‌ها -->
        <div class="grid-cards">
            <?php if (!empty($listings)): ?>
                <?php foreach ($listings as $item): ?>
                    <?php 
                        $analysis = calculate_number_score($item['phone_number']);
                        $isFav = in_array((int)$item['id'], $userFavs, true);
                        $badgeClass = operator_badge_class($item['operator']);
                    ?>
                    <article class="card">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span class="badge <?= $badgeClass ?>"><?= e($item['operator']) ?></span>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <?php if ($item['is_featured']): ?>
                                    <span class="badge badge-rond">★ ویژه</span>
                                <?php endif; ?>
                                <span class="badge badge-muted"><?= e($item['sim_type']) ?></span>
                            </div>
                        </div>

                        <div class="sim-number" title="برای کپی کلیک کنید" data-copy="<?= e($item['phone_number']) ?>" style="cursor:pointer;">
                            <?= format_phone($item['phone_number']) ?>
                        </div>

                        <div style="font-size:12px;color:var(--text-muted);display:flex;justify-content:space-between;align-items:center;">
                            <span>نوع: <?= e($item['number_type']) ?> (کد <?= e($item['prefix']) ?>)</span>
                            <span style="color:#0284c7;font-weight:700;"><?= e($analysis['label']) ?></span>
                        </div>

                        <div class="score-bar-wrap" title="شاخص رند بودن: <?= $analysis['score'] ?>%">
                            <div class="score-bar-fill" style="width:<?= $analysis['score'] ?>%;"></div>
                        </div>

                        <div style="margin-top:14px;background:#f8fafc;padding:10px;border-radius:var(--radius-sm);border:1px solid var(--border);">
                            <div style="display:flex;justify-content:space-between;align-items:baseline;">
                                <span style="font-size:12px;color:var(--text-muted);">قیمت فروش:</span>
                                <span class="sim-price"><?= toman($item['price']) ?></span>
                            </div>
                            <?php if (!empty($item['description'])): ?>
                                <p style="font-size:12px;color:#475569;margin-top:6px;line-height:1.5;"><?= e($item['description']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:16px;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <?php if ($user): ?>
                                    <form method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_favorite">
                                        <input type="hidden" name="listing_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" title="<?= $isFav ? 'حذف از نشان‌ها' : 'نشان کردن' ?>" style="<?= $isFav ? 'color:#ef4444;border-color:#fca5a5;' : '' ?>">
                                            <?= $isFav ? '❤️ نشان‌شده' : '🤍 نشان' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="account.php" class="btn btn-outline btn-sm" title="ورود برای ذخیره">🤍</a>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex;gap:6px;">
                                <?php if (!empty($item['seller_phone'])): ?>
                                    <a href="tel:<?= e($item['seller_phone']) ?>" class="btn btn-primary btn-sm">
                                        تماس (<?= e($item['seller_name'] ?: 'فروشنده') ?>)
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="panel" style="grid-column:1/-1;text-align:center;padding:50px 20px;">
                    <h3>آگهی مطابق با فیلترهای انتخابی یافت نشد</h3>
                    <p class="muted" style="margin-top:8px;">می‌توانید فیلترها را حذف کنید یا درخواست خرید معکوس خود را ثبت نمایید.</p>
                    <a href="market.php" class="btn btn-primary" style="margin-top:16px;">مشاهده تمام آگهی‌ها</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- تب ۲: درخواست‌های خریداران (خرید معکوس) -->
    <div id="tab-requests" class="tab-pane <?= $activeTab === 'requests' ? 'active' : '' ?>">
        <div class="panel mb-6">
            <h2 style="font-size:17px;font-weight:800;color:var(--text-main);">درخواست‌های فعال خریداران</h2>
            <p style="font-size:13px;color:var(--text-muted);margin-top:4px;">
                اگر سیم‌کارتی با این مشخصات دارید، می‌توانید مستقیماً با خریدار تماس بگیرید و پیشنهاد خود را مطرح کنید.
            </p>
        </div>

        <div class="grid-cards">
            <?php if (!empty($buyRequests)): ?>
                <?php foreach ($buyRequests as $req): ?>
                    <article class="card">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span class="badge badge-info"><?= e($req['operator']) ?></span>
                            <?= status_badge($req['status']) ?>
                        </div>

                        <div style="margin-top:12px;">
                            <span style="font-size:12px;color:var(--text-muted);">الگوی شماره درخواستی:</span>
                            <div style="font-size:18px;font-weight:800;direction:ltr;text-align:center;background:#eff6ff;padding:8px;border-radius:8px;margin-top:4px;color:#1e40af;">
                                <?= e($req['desired_pattern']) ?>
                            </div>
                        </div>

                        <div style="margin-top:12px;display:flex;justify-content:space-between;">
                            <span style="font-size:12px;color:var(--text-muted);">حداکثر بودجه:</span>
                            <span style="font-weight:800;color:#059669;"><?= toman($req['budget']) ?></span>
                        </div>

                        <?php if (!empty($req['notes'])): ?>
                            <p style="font-size:12.5px;color:#475569;margin-top:10px;background:#f8fafc;padding:8px 12px;border-radius:6px;">
                                💬 <?= e($req['notes']) ?>
                            </p>
                        <?php endif; ?>

                        <div style="margin-top:auto;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:12px;color:var(--text-muted);">خریدار: <?= e($req['full_name']) ?></span>
                            <?php if (!empty($req['contact_phone'])): ?>
                                <a href="tel:<?= e($req['contact_phone']) ?>" class="btn btn-soft btn-sm">
                                    تماس با خریدار
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="panel" style="grid-column:1/-1;text-align:center;padding:40px;">
                    <h3>هنوز درخواست خریدی ثبت نشده است.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- بخش ثبت آگهی فروش جدید -->
<div class="section-head" id="new-listing">
    <div>
        <h2>ثبت آگهی رایگان سیم‌کارت</h2>
        <p>مشخصات خط خود را با دقت ثبت کنید تا در سریع‌ترین زمان توسط خریداران مشاهده شود.</p>
    </div>
</div>

<div class="panel">
    <?php if (!$user): ?>
        <div style="text-align:center;padding:24px;">
            <p style="font-size:15px;color:var(--text-muted);">برای ثبت آگهی سیم‌کارت، ابتدا باید وارد حساب کاربری خود شوید یا حساب جدید بسازید.</p>
            <a class="btn btn-primary" style="margin-top:14px;" href="account.php">ورود / ثبت‌نام در پیشخوان‌یار</a>
        </div>
    <?php else: ?>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="listing">

            <div class="field">
                <label>اپراتور *</label>
                <select name="operator" required>
                    <option value="همراه اول">همراه اول</option>
                    <option value="ایرانسل">ایرانسل</option>
                    <option value="رایتل">رایتل</option>
                    <option value="شاتل موبایل">شاتل موبایل</option>
                    <option value="سایر">سایر اپراتورها</option>
                </select>
            </div>

            <div class="field">
                <label>شماره کامل سیم‌کارت (۱۱ رقم با 09) *</label>
                <input type="text" name="phone_number" required maxlength="14" dir="ltr" placeholder="09123456789">
                <span class="field-help">شماره را بدون فاصله یا خط تیره وارد کنید.</span>
            </div>

            <div class="field">
                <label>نوع خط *</label>
                <select name="sim_type" required>
                    <option value="دائمی">دائمی</option>
                    <option value="اعتباری" selected>اعتباری</option>
                </select>
            </div>

            <div class="field">
                <label>دسته‌بندی شماره</label>
                <select name="number_type">
                    <option value="معمولی">معمولی</option>
                    <option value="رند">رند</option>
                </select>
            </div>

            <div class="field">
                <label>پیش‌شماره (اختیاری)</label>
                <input type="text" name="prefix" placeholder="مثلاً 0912 یا 0935">
            </div>

            <div class="field">
                <label>قیمت فروش به تومان *</label>
                <input type="number" name="price" min="0" required placeholder="مثلاً 15000000">
            </div>

            <div class="field">
                <label>نام فروشنده جهت تماس</label>
                <input type="text" name="seller_name" value="<?= e($user['full_name']) ?>">
            </div>

            <div class="field">
                <label>شماره تماس برای خریداران *</label>
                <input type="text" name="seller_phone" dir="ltr" value="<?= e($user['phone'] ?? '') ?>" placeholder="09xxxxxxxxx">
            </div>

            <div class="field" style="grid-column:1/-1;">
                <label>توضیحات تکمیلی و وضعیت سند</label>
                <textarea name="description" placeholder="درباره صفر یا کارکرده بودن، زنگ‌خور خط، نحوه انتقال مالکیت در دفتر پیشخوان یا تخفیف بنویسید..."></textarea>
            </div>

            <div style="grid-column:1/-1;">
                <button class="btn btn-primary" type="submit">ثبت و انتشار آگهی سیم‌کارت</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- بخش ثبت درخواست خرید معکوس -->
<div class="section-head" id="buy-request">
    <div>
        <h2>ثبت درخواست خرید معکوس</h2>
        <p>به دنبال شماره خاص یا رند مشخصی هستید؟ مشخصات و بودجه خود را اعلام کنید تا فروشندگان پیشنهاد دهند.</p>
    </div>
</div>

<div class="panel">
    <?php if (!$user): ?>
        <div style="text-align:center;padding:24px;">
            <p style="font-size:15px;color:var(--text-muted);">برای ثبت درخواست خرید، لطفاً وارد حساب خود شوید.</p>
            <a class="btn btn-primary" style="margin-top:14px;" href="account.php">ورود به حساب</a>
        </div>
    <?php else: ?>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="buy_request">

            <div class="field">
                <label>الگوی شماره دلخواه *</label>
                <input type="text" name="desired_pattern" placeholder="مثلاً 0912*444**** یا خاتمه 1212" required>
            </div>

            <div class="field">
                <label>حداکثر بودجه (تومان) *</label>
                <input type="number" name="budget" min="0" required placeholder="مثلاً 30000000">
            </div>

            <div class="field">
                <label>اپراتور ترجیحی</label>
                <select name="operator">
                    <option value="فرقی ندارد">فرقی ندارد</option>
                    <option value="همراه اول">همراه اول</option>
                    <option value="ایرانسل">ایرانسل</option>
                    <option value="رایتل">رایتل</option>
                </select>
            </div>

            <div class="field">
                <label>شماره تماس شما برای دریافت پیشنهاد</label>
                <input type="text" name="contact_phone" dir="ltr" value="<?= e($user['phone'] ?? '') ?>" placeholder="09xxxxxxxxx">
            </div>

            <div class="field" style="grid-column:1/-1;">
                <label>توضیحات و اولویت‌ها</label>
                <input type="text" name="notes" placeholder="مثلاً: حتماً کد ۱ باشد، یا فقط خطوط صفر مد نظرم است.">
            </div>

            <div style="grid-column:1/-1;">
                <button class="btn btn-primary" type="submit">ثبت درخواست خرید</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="warning mt-6" style="margin-top:24px;">
    <strong>نکته امنیتی و حقوقی:</strong> انتقال قطعی سند کلیه سیم‌کارت‌ها باید صرفاً با حضور طرفین یا وکیل قانونی در یکی از دفاتر پیشخوان رسمی و احراز هویت در سامانه شاهکار صورت پذیرد. از پرداخت وجه پیش از احراز هویت و تغییر سند رسمی خودداری فرمایید.
</div>

<?php require 'includes/footer.php'; ?>
