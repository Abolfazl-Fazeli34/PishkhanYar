<?php
declare(strict_types=1);

/**
 * پاکسازی ورودی برای خروجی HTML ایمن
 */
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * انتقال به نشانی دیگر
 */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/**
 * تبدیل ارقام فارسی و عربی به انگلیسی و پاکسازی کاراکترهای اضافه
 */
function normalize_number(string $input): string {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    $str = str_replace($persian, $english, $input);
    $str = str_replace($arabic, $english, $str);
    return preg_replace('/[^\d+]/', '', trim($str)) ?? '';
}

/**
 * فرمت‌بندی خوانا برای شماره موبایل (مثلاً 0912 345 6789)
 */
function format_phone(string $phone): string {
    $clean = normalize_number($phone);
    if (str_starts_with($clean, '+98')) {
        $clean = '0' . substr($clean, 3);
    } elseif (str_starts_with($clean, '98') && strlen($clean) === 12) {
        $clean = '0' . substr($clean, 2);
    }
    if (strlen($clean) === 11) {
        return substr($clean, 0, 4) . ' ' . substr($clean, 4, 3) . ' ' . substr($clean, 7);
    }
    return $phone;
}

/**
 * دریافت توکن امنیتی CSRF
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * فیلد پنهان توکن امنیتی برای فرم‌ها
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * اعتبارسنجی توکن CSRF در درخواست‌های POST
 */
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        flash('نشست شما منقضی شده است یا فرم نامعتبر است؛ لطفاً صفحه را تازه‌سازی کنید.', 'danger');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

/**
 * دریافت اطلاعات کاربر وارد شده
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * بررسی اینکه کاربر جاری مدیر است یا خیر
 */
function is_admin(): bool {
    $u = current_user();
    return $u !== null && ($u['role'] ?? '') === 'admin';
}

/**
 * الزام ورود کاربر برای مشاهده صفحه
 */
function require_login(): void {
    if (!current_user()) {
        flash('برای دسترسی به این بخش لطفاً وارد حساب کاربری خود شوید.', 'warning');
        redirect('account.php');
    }
}

/**
 * الزام دسترسی مدیریتی
 */
function require_admin(): void {
    if (!is_admin()) {
        flash('دسترسی مجاز نیست؛ این بخش مختص مدیریت سامانه است.', 'danger');
        redirect('account.php');
    }
}

/**
 * پیام‌های گذرا و هشدارهای موقت
 */
function flash(?string $message = null, string $type = 'success'): ?array {
    if ($message !== null) {
        $_SESSION['flash_messages'][] = [
            'message' => $message,
            'type'    => in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info',
        ];
        return null;
    }
    $msgs = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $msgs;
}

/**
 * نمایش مبلغ به تومان با جداکننده سه رقمی
 */
function toman(int|float|string $amount): string {
    return number_format((float)$amount) . ' تومان';
}

/**
 * تشخیص صفحه فعال در منو
 */
function active_page(string $file): string {
    return basename($_SERVER['PHP_SELF']) === $file ? 'nav-link active' : 'nav-link';
}

/**
 * کلاس ظاهری نشانگر برند اپراتور
 */
function operator_badge_class(string $op): string {
    return match (trim($op)) {
        'همراه اول' => 'badge-mci',
        'ایرانسل'   => 'badge-mtn',
        'رایتل'     => 'badge-rightel',
        default     => 'badge-other',
    };
}

/**
 * نشانگر وضعیت به فارسی با استایل مناسب
 */
function status_badge(string $status): string {
    return match ($status) {
        'active'      => '<span class="badge badge-success">فعال</span>',
        'pending'     => '<span class="badge badge-warning">در انتظار</span>',
        'sold'        => '<span class="badge badge-danger">فروخته شد</span>',
        'hidden'      => '<span class="badge badge-muted">مخفی</span>',
        'confirmed'   => '<span class="badge badge-success">تأیید شده</span>',
        'cancelled'   => '<span class="badge badge-danger">لغو شده</span>',
        'completed'   => '<span class="badge badge-info">انجام شده</span>',
        'open'        => '<span class="badge badge-success">در حال جستجو</span>',
        'matched'     => '<span class="badge badge-info">پیشنهاد یافت شد</span>',
        'closed'      => '<span class="badge badge-muted">بسته شده</span>',
        'new'         => '<span class="badge badge-warning">جدید</span>',
        'in_progress' => '<span class="badge badge-info">در حال بررسی</span>',
        'answered'    => '<span class="badge badge-success">پاسخ داده شد</span>',
        default       => '<span class="badge badge-muted">' . e($status) . '</span>',
    };
}

/**
 * محاسبه امتیاز رند بودن شماره سیم‌کارت و تحلیل الگوی عددی
 */
function calculate_number_score(string $phone): array {
    $clean = normalize_number($phone);
    // فقط ۷ رقم آخر شماره ملاک اصلی رند بودن است (مثلاً در 0912 345 6789، ارقام 3456789)
    $digits = substr($clean, -7);
    if (strlen($digits) < 7) {
        $digits = preg_replace('/\D/', '', $clean);
    }
    if (strlen($digits) < 4) {
        return ['score' => 25, 'label' => 'معمولی', 'type' => 'normal'];
    }

    $score = 20;
    $tags = [];

    // فراوانی بیشترین رقم
    $counts = count_chars($digits, 1);
    $maxRepeat = !empty($counts) ? max($counts) : 0;
    if ($maxRepeat >= 6) {
        $score += 55;
        $tags[] = 'رند تکرار فوق‌العاده';
    } elseif ($maxRepeat === 5) {
        $score += 40;
        $tags[] = 'رند ۵ تکرار';
    } elseif ($maxRepeat === 4) {
        $score += 28;
        $tags[] = 'رند ۴ تکرار';
    } elseif ($maxRepeat === 3) {
        $score += 15;
    }

    // تکرار پشت سر هم (پله‌ای یا متوالی مثلاً 1111 یا 555)
    if (preg_match('/(\d)\1{3,}/', $digits)) {
        $score += 25;
        $tags[] = 'تکرار متوالی ارقام';
    } elseif (preg_match('/(\d)\1{2}/', $digits)) {
        $score += 15;
    }

    // رند آینه‌ای / تقارن
    if ($digits === strrev($digits)) {
        $score += 35;
        $tags[] = 'فوق رند آینه‌ای (پالیندروم)';
    } elseif (substr($digits, 0, 3) === strrev(substr($digits, -3))) {
        $score += 20;
        $tags[] = 'تقارن ابتدا و انتها';
    }

    // رند پله‌ای صعودی یا نزولی (1234 یا 9876)
    if (preg_match('/0123|1234|2345|3456|4567|5678|6789/', $digits)) {
        $score += 28;
        $tags[] = 'رند پله‌ای صعودی';
    } elseif (preg_match('/9876|8765|7654|6543|5432|4321|3210/', $digits)) {
        $score += 26;
        $tags[] = 'رند پله‌ای نزولی';
    }

    // خاتمه رند هزاری (مثلاً 000 یا 5000)
    if (preg_match('/000$/', $digits)) {
        $score += 22;
        $tags[] = 'خاتمه سه صفر (هزاری)';
    }

    // رند جفتی دو به دو (مثلاً 20 20 یا 45 45)
    if (preg_match('/(\d{2})\1/', $digits)) {
        $score += 18;
        $tags[] = 'رند جفتی دو به دو';
    }

    $finalScore = min(100, max(20, $score));
    $label = !empty($tags) ? implode(' · ', array_slice($tags, 0, 2)) : ($finalScore >= 70 ? 'رند با ارزش بالا' : ($finalScore >= 50 ? 'رند خوش‌آوا' : 'معمولی'));

    return [
        'score' => $finalScore,
        'label' => $label,
        'is_rond' => $finalScore >= 55,
    ];
}
