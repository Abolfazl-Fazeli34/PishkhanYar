<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'راهنمای جامع خدمات سیم‌کارت و اپراتورها';

$selectedProblem = trim($_GET['problem'] ?? '');

$services = [
    [
        'id'       => 'service-transfer',
        'title'    => 'انتقال سند و مالکیت سیم‌کارت',
        'operator' => 'کلیه اپراتورها (همراه اول، ایرانسل، رایتل)',
        'docs'     => 'اصل کارت ملی هوشمند طرفین (یا شناسنامه جدید)، حضور فیزیکی خریدار و فروشنده، سیم‌کارت فعال جهت دریافت رمز انتقال (OTP)',
        'steps'    => '۱. بررسی استعلام بدهی خط و سلب امتیاز نبودن ۲. تطبیق چهره و اسکن مدارک در سامانه شاهکار ۳. ارسال کد تایید انتقال به خط ۴. امضای الکترونیک و چاپ سند جدید بنام خریدار',
        'cost'     => 'حدود ۴۵٬۰۰۰ الی ۶۰٬۰۰۰ تومان (طبق تعرفه مصوب رگولاتوری)',
        'time'     => '۱۰ الی ۲۰ دقیقه',
        'booking'  => 'انتقال سند و مالکیت سیم‌کارت'
    ],
    [
        'id'       => 'service-sim-replace',
        'title'    => 'تعویض سیم‌کارت مفقودی، سرقتی یا سوخته',
        'operator' => 'کلیه اپراتورها',
        'docs'     => 'کارت ملی مالک خط یا وکالتنامه محضری معتبر، همراه داشتن گوشی جهت فعال‌سازی اولیه',
        'steps'    => '۱. مسدودی فوری سیم‌کارت مفقودی در سامانه اپراتور ۲. احراز هویت مالک در دفتر پیشخوان ۳. تخصیص پوکه و شماره سریال سیم‌کارت جدید (USIM) ۴. فعال‌سازی خط حداکثر ظرف ۱ ساعت',
        'cost'     => 'حدود ۲۰٬۰۰۰ الی ۳۵٬۰۰۰ تومان',
        'time'     => '۵ الی ۱۰ دقیقه',
        'booking'  => 'تعویض سیم‌کارت مفقودی یا سوخته'
    ],
    [
        'id'       => 'service-change-type',
        'title'    => 'تبدیل سیم‌کارت اعتباری به دائمی',
        'operator' => 'همراه اول و ایرانسل',
        'docs'     => 'کارت ملی مالک قانونی خط، سیم‌کارت فعال با شارژ کافی جهت تسویه تفاوت قیمت',
        'steps'    => '۱. استعلام امکان ترابرد درونی و فعال بودن حداقل ۶ ماه ۲. تسویه مانده اعتبار ۳. انتخاب بسته اولیه و پرداخت تفاوت تعرفه ۴. فعال‌سازی سیم‌کارت دائمی بدون تغییر شماره',
        'cost'     => 'متغیر با توجه به طرح اپراتور (همراه اول حدود ۱۰۰٬۰۰۰ تومان)',
        'time'     => 'آنی پس از تایید',
        'booking'  => 'تبدیل سیم‌کارت اعتباری به دائمی'
    ],
    [
        'id'       => 'service-mnp',
        'title'    => 'ترابرد شماره بین اپراتورها (MNP)',
        'operator' => 'همراه اول، ایرانسل، رایتل',
        'docs'     => 'کارت ملی مالک خط، خط بدون بدهی و فعال با حداقل سابقه ۹۰ روز در اپراتور قبلی',
        'steps'    => '۱. ارسال پیامک درخواست به سرشماره ترابرد یا ثبت در پیشخوان ۲. دریافت سیم‌کارت جدید اپراتور مقصد ۳. تایید پیامکی انتقال ۴. فعال شدن سیم‌کارت جدید ظرف ۲۴ ساعت',
        'cost'     => 'رایگان یا هزینه اندک صدور سیم‌کارت خام',
        'time'     => '۲۴ الی ۴۸ ساعت کاری',
        'booking'  => 'ترابرد شماره بین اپراتورها (MNP)'
    ],
    [
        'id'       => 'service-block',
        'title'    => 'مسدودی موقت و رفع مسدودی سیم‌کارت',
        'operator' => 'کلیه اپراتورها',
        'docs'     => 'کارت ملی مالک، اعلام شماره تلفن یا پوکه سیم‌کارت',
        'steps'    => '۱. تقاضای قطع موقت به علت سرقت یا مسافرت ۲. ثبت در سامانه مرکزی اپراتور ۳. رفع مسدودی پس از مراجعه حضوری و احراز هویت',
        'cost'     => 'رایگان',
        'time'     => 'کمتر از ۵ دقیقه',
        'booking'  => 'مسدودی موقت و رفع مسدودی خط'
    ],
    [
        'id'       => 'service-postal',
        'title'    => 'تأییدیه کد پستی و امور مالیاتی پیشخوان',
        'operator' => 'خدمات عمومی پیشخوان دولت',
        'docs'     => 'قبض تلفن ثابت، سند یا اجاره‌نامه رسمی ملک دارای کد پستی ده‌رقمی، کارت ملی',
        'steps'    => '۱. استعلام و راستی‌آزمایی نشانی در سامانه شرکت ملی پست ۲. ثبت کد مالیاتی در صورت نیاز کسب‌وکارها ۳. چاپ برگه رسمی گواهی کد پستی ممهور به مهر دفتر',
        'cost'     => 'طبق نرخ‌نامه مصوب پست',
        'time'     => '۵ الی ۱۰ دقیقه',
        'booking'  => 'تأییدیه کد پستی و امور مالیاتی'
    ]
];

$faqs = [
    [
        'q' => 'آیا برای انتقال سند سیم‌کارت، حضور هر دو نفر (خریدار و فروشنده) الزامی است؟',
        'a' => 'بله؛ طبق مصوبه سازمان تنظیم مقررات و سامانه شاهکار، حضور هم‌زمان انتقال‌گیرنده و انتقال‌دهنده به همراه اصل مدارک هویتی در دفتر پیشخوان یا ارائه وکالتنامه رسمی بلاعزل انتقال خط الزامی است.'
    ],
    [
        'q' => 'سقف تعداد سیم‌کارت به نام هر فرد در اپراتورها چقدر است؟',
        'a' => 'طبق قوانین جاری کشور، هر کد ملی حداکثر می‌تواند مجموعاً ۱۰ عدد سیم‌کارت فعال در تمام اپراتورها (همراه اول، ایرانسل، رایتل و شاتل) به نام خود داشته باشد.'
    ],
    [
        'q' => 'اگر سیم‌کارت بسوزد یا مفقود شود، شارژ یا بسته‌های فعال آن از بین می‌رود؟',
        'a' => 'خیر؛ تمامی مانده شارژ ریالی، بسته‌های اینترنت فعال و سوابق خط پس از صدور سیم‌کارت جایگزین (تعویض پوکه) عیناً روی سیم‌کارت جدید فعال خواهند ماند.'
    ],
    [
        'q' => 'چگونه از تعداد سیم‌کارت‌هایی که با کدملی من ثبت شده‌اند مطلع شوم؟',
        'a' => 'می‌توانید کدملی خود را به سرشماره ۳۰۰۰۱۵۰ یا ۳۰۰۰۵۰۵۰ پیامک کنید یا از سامانه استعلام تعداد خطوط سازمان تنظیم مقررات (Cra.ir) استعلام بگیرید.'
    ]
];

require 'includes/header.php';
?>

<div class="section-head mt-0">
    <div>
        <h1>راهنمای خدمات سیم‌کارت و پیشخوان دولت</h1>
        <p>مدارک موردنیاز، هزینه‌های مصوب، مراحل قانونی و راهنمای گام‌به‌گام خدمات اداری اپراتورها.</p>
    </div>
    <div>
        <a href="booking.php" class="btn btn-primary">دریافت نوبت اینترنتی</a>
    </div>
</div>

<!-- دستیار هوشمند انتخاب خدمت -->
<div class="panel" style="background:linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);border-color:#bfdbfe;margin-bottom:28px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <span class="card-icon" style="background:#2563eb;color:#fff;margin-bottom:0;width:38px;height:38px;">💡</span>
        <div>
            <h2 style="font-size:17px;font-weight:800;color:#1e3a8a;">دستیار هوشمند انتخاب مسیر اداری</h2>
            <p style="font-size:12.5px;color:#475569;">مشکل یا نیاز خود را انتخاب کنید تا مدارک و نزدیک‌ترین دفتر پیشنهادی نمایش داده شود.</p>
        </div>
    </div>

    <form method="get" action="services.php" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
        <select name="problem" style="flex:1;min-width:280px;border:1px solid #93c5fd;border-radius:var(--radius-sm);padding:10px 14px;background:#fff;font-family:inherit;font-size:13.5px;">
            <option value="">-- موضوع یا نیاز خود را انتخاب کنید --</option>
            <option value="transfer" <?= $selectedProblem === 'transfer' ? 'selected' : '' ?>>سیم‌کارت خریده‌ام و قصد تغییر نام قطعی و سند زدن دارم</option>
            <option value="lost" <?= $selectedProblem === 'lost' ? 'selected' : '' ?>>گوشی یا سیم‌کارتم مفقود شده و سیم‌کارت جدید می‌خواهم</option>
            <option value="change" <?= $selectedProblem === 'change' ? 'selected' : '' ?>>می‌خواهم خط اعتباری خود را دائمی کنم</option>
            <option value="mnp" <?= $selectedProblem === 'mnp' ? 'selected' : '' ?>>می‌خواهم اپراتورم را عوض کنم ولی شماره‌ام حفظ شود (ترابرد)</option>
            <option value="postal" <?= $selectedProblem === 'postal' ? 'selected' : '' ?>>برای بانک، وام یا امور اداری به تاییدیه کد پستی نیاز دارم</option>
        </select>
        <button type="submit" class="btn btn-primary">نمایش راهنمای فوری</button>
        <?php if ($selectedProblem): ?>
            <a href="services.php" class="btn btn-outline">مشاهده همه</a>
        <?php endif; ?>
    </form>

    <?php if ($selectedProblem): ?>
        <div style="background:#fff;padding:16px;border-radius:var(--radius-sm);margin-top:16px;border:1px solid #93c5fd;">
            <?php if ($selectedProblem === 'transfer'): ?>
                <h3 style="font-size:15px;font-weight:800;color:#1e3a8a;">راهکار: مراجعه حضوری برای «انتقال مالکیت قطعی»</h3>
                <p style="font-size:13px;color:#334155;margin-top:6px;">
                    هر دو طرف معامله (خریدار و فروشنده) باید با اصل کارت ملی هوشمند در دفتر پیشخوان حاضر شوند. سیم‌کارت باید فعال و در دسترس باشد تا رمز OTP صادر شود.
                </p>
                <div style="margin-top:12px;">
                    <a href="booking.php?service=<?= urlencode('انتقال سند و مالکیت سیم‌کارت') ?>" class="btn btn-primary btn-sm">رزرو نوبت برای انتقال مالکیت</a>
                </div>
            <?php elseif ($selectedProblem === 'lost'): ?>
                <h3 style="font-size:15px;font-weight:800;color:#b91c1c;">اقدام فوری: مسدودی و دریافت سیم‌کارت جایگزین</h3>
                <p style="font-size:13px;color:#334155;margin-top:6px;">
                    ابتدا با شماره پشتیبانی اپراتور تماس بگیرید یا بلافاصله به دفتر پیشخوان مراجعه نموده و با ارائه کارت ملی پوکه جدید دریافت نمایید.
                </p>
                <div style="margin-top:12px;">
                    <a href="booking.php?service=<?= urlencode('تعویض سیم‌کارت مفقودی یا سوخته') ?>" class="btn btn-primary btn-sm">رزرو نوبت تعویض سیم‌کارت</a>
                </div>
            <?php elseif ($selectedProblem === 'change'): ?>
                <h3 style="font-size:15px;font-weight:800;color:#1e3a8a;">راهکار: تبدیل خط اعتباری به دائمی</h3>
                <p style="font-size:13px;color:#334155;margin-top:6px;">
                    با پرداخت مابه‌التفاوت تعرفه، بدون کوچک‌ترین تغییری در شماره سیم‌کارت، نوع خط شما به دائمی تبدیل شده و بسته‌های مقرون‌به‌صرفه‌تری دریافت خواهید کرد.
                </p>
                <div style="margin-top:12px;">
                    <a href="booking.php?service=<?= urlencode('تبدیل سیم‌کارت اعتباری به دائمی') ?>" class="btn btn-primary btn-sm">رزرو نوبت تبدیل خط</a>
                </div>
            <?php elseif ($selectedProblem === 'mnp'): ?>
                <h3 style="font-size:15px;font-weight:800;color:#1e3a8a;">راهکار: ترابردپذیری شماره تلفن همراه (MNP)</h3>
                <p style="font-size:13px;color:#334155;margin-top:6px;">
                    بدون تغییر شماره و پیش‌شماره می‌توانید از همراه اول به ایرانسل یا بالعکس جابه‌جا شوید.
                </p>
                <div style="margin-top:12px;">
                    <a href="booking.php?service=<?= urlencode('ترابرد شماره بین اپراتورها (MNP)') ?>" class="btn btn-primary btn-sm">رزرو نوبت ترابرد</a>
                </div>
            <?php elseif ($selectedProblem === 'postal'): ?>
                <h3 style="font-size:15px;font-weight:800;color:#1e3a8a;">راهکار: صدور گواهی و تاییدیه کد پستی ده‌رقمی</h3>
                <p style="font-size:13px;color:#334155;margin-top:6px;">
                    با همراه داشتن یک قبض یا سند ملکی به دفتر پیشخوان مراجعه کرده و گواهی رسمی ممهور دریافت کنید.
                </p>
                <div style="margin-top:12px;">
                    <a href="booking.php?service=<?= urlencode('تأییدیه کد پستی و امور مالیاتی') ?>" class="btn btn-primary btn-sm">رزرو نوبت کد پستی</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- کارت‌های راهنمای خدمات اصلی -->
<div class="section-head">
    <div>
        <h2>خدمات پرکاربرد اپراتورها و پیشخوان</h2>
        <p>مشخصات کامل هر خدمت را قبل از مراجعه حضوری مطالعه فرمایید.</p>
    </div>
</div>

<div class="grid-cards">
    <?php foreach ($services as $srv): ?>
        <article class="card" id="<?= e($srv['id']) ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span class="badge badge-info"><?= e($srv['operator']) ?></span>
                <span class="badge badge-muted">⏱ <?= e($srv['time']) ?></span>
            </div>

            <h3 style="font-size:17px;font-weight:800;margin-top:14px;color:var(--text-main);"><?= e($srv['title']) ?></h3>

            <div style="margin-top:12px;font-size:12.5px;color:#334155;line-height:1.7;">
                <strong>مدارک موردنیاز:</strong>
                <p style="color:var(--text-muted);margin-top:2px;"><?= e($srv['docs']) ?></p>
            </div>

            <div style="margin-top:10px;font-size:12.5px;color:#334155;line-height:1.7;">
                <strong>مراحل انجام:</strong>
                <p style="color:var(--text-muted);margin-top:2px;"><?= e($srv['steps']) ?></p>
            </div>

            <div style="margin-top:10px;background:#f8fafc;padding:8px 12px;border-radius:6px;font-size:12px;color:#059669;font-weight:700;">
                تعرفه مصوب: <?= e($srv['cost']) ?>
            </div>

            <div style="margin-top:auto;padding-top:16px;">
                <a href="booking.php?service=<?= urlencode($srv['booking']) ?>" class="btn btn-primary btn-sm" style="width:100%;">
                    رزرو نوبت برای این خدمت
                </a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<!-- پرسش‌های متداول -->
<div class="section-head" style="margin-top:40px;">
    <div>
        <h2>پرسش‌های متداول اداری و حقوقی</h2>
        <p>پاسخ به سوالات پرتکرار پیرامون سند زدن، سامانه‌های شاهکار و احراز هویت.</p>
    </div>
</div>

<div class="panel" style="display:flex;flex-direction:column;gap:14px;">
    <?php foreach ($faqs as $faq): ?>
        <details style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 18px;cursor:pointer;">
            <summary style="font-weight:700;font-size:14px;color:var(--text-main);outline:none;">
                <?= e($faq['q']) ?>
            </summary>
            <p style="font-size:13px;color:var(--text-muted);margin-top:10px;line-height:1.8;">
                <?= e($faq['a']) ?>
            </p>
        </details>
    <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>
