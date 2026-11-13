<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'پیشخوان‌یار';
$user = current_user();
$flashMessages = flash();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="پیشخوان‌یار؛ سامانه یکپارچه بازار سیم‌کارت، راهنمای خدمات اپراتورها و نوبت‌دهی دفاتر پیشخوان دولت">
    <title><?= e($pageTitle) ?> | پیشخوان‌یار</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="brand">
            <span class="brand-mark">پ</span>
            <span class="brand-text">
                <strong>پیشخوان‌یار</strong>
                <small>خدمات هوشمند، انتخاب آسان</small>
            </span>
        </a>

        <button class="mobile-toggle" aria-label="منوی اصلی" aria-expanded="false">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <nav class="main-nav" aria-label="منوی اصلی">
            <a class="<?= active_page('index.php') ?>" href="index.php">خانه</a>
            <a class="<?= active_page('market.php') ?>" href="market.php">بازار سیم‌کارت</a>
            <a class="<?= active_page('services.php') ?>" href="services.php">خدمات اپراتورها</a>
            <a class="<?= active_page('offices.php') ?>" href="offices.php">دفاتر پیشخوان</a>
            <a class="<?= active_page('booking.php') ?>" href="booking.php">نوبت‌دهی آنلاین</a>
            <a class="<?= active_page('support.php') ?>" href="support.php">پشتیبانی</a>
            <?php if (is_admin()): ?>
                <a class="<?= active_page('admin.php') ?>" href="admin.php" style="color:#d97706;font-weight:700">★ پنل مدیریت</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <?php if ($user): ?>
                <a class="btn btn-soft btn-sm" href="account.php">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?= e(mb_substr($user['full_name'], 0, 15)) ?></span>
                </a>
            <?php else: ?>
                <a class="btn btn-primary btn-sm" href="account.php">ورود / ثبت‌نام</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container" style="flex:1; padding-top:28px; padding-bottom:48px;">
<?php if (!empty($flashMessages)): ?>
    <div class="alerts-container">
        <?php foreach ($flashMessages as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>" role="alert">
                <span><?= e($f['message']) ?></span>
                <button type="button" class="alert-close" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;" aria-label="بستن">✕</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
