<?php
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$pageTitle = 'دفاتر پیشخوان خدمات دولت';
$pdo = db();

$city = trim($_GET['city'] ?? '');
$province = trim($_GET['province'] ?? '');
$service = trim($_GET['service'] ?? '');

$sql = "SELECT * FROM offices WHERE is_active = 1";
$params = [];

if ($province !== '') {
    $sql .= " AND province LIKE ?";
    $params[] = '%' . $province . '%';
}
if ($city !== '') {
    $sql .= " AND (city LIKE ? OR name LIKE ? OR address LIKE ?)";
    $like = '%' . $city . '%';
    array_push($params, $like, $like, $like);
}
if ($service !== '') {
    $sql .= " AND services LIKE ?";
    $params[] = '%' . $service . '%';
}

$sql .= " ORDER BY province, city, name LIMIT 100";
$st = $pdo->prepare($sql);
$st->execute($params);
$offices = $st->fetchAll();

// لیست شهرهای موجود جهت فیلتر سریع
$cities = [];
try {
    $cities = $pdo->query("SELECT DISTINCT city FROM offices WHERE is_active=1 ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable) {}

require 'includes/header.php';
?>

<div class="section-head mt-0">
    <div>
        <h1>دفاتر پیشخوان خدمات دولت</h1>
        <p>جستجو و یافتن نشانی، تلفن و ساعات کاری دفاتر پیشخوان رسمی جهت مراجعه حضوری و دریافت نوبت.</p>
    </div>
    <div>
        <a href="booking.php" class="btn btn-primary">دریافت نوبت اینترنتی</a>
    </div>
</div>

<!-- فرم فیلتر و جستجوی پیشرفته -->
<div class="panel mb-6" style="margin-bottom:24px;">
    <form method="get" class="form-grid">
        <div class="field">
            <label>شهر، منطقه یا نام دفتر</label>
            <input type="text" name="city" value="<?= e($city) ?>" placeholder="مثلاً: تهران، ولیعصر، مشهد، اصفهان...">
        </div>

        <div class="field">
            <label>نوع خدمت موردنظر</label>
            <input type="text" name="service" value="<?= e($service) ?>" placeholder="مثلاً: انتقال مالکیت، تعویض سیم‌کارت، مالیات...">
        </div>

        <div style="grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <!-- فیلتر سریع شهرها -->
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="offices.php" class="badge <?= $city === '' ? 'badge-info' : 'badge-muted' ?>">همه شهرها</a>
                <?php foreach ($cities as $c): ?>
                    <a href="offices.php?city=<?= urlencode($c) ?>" class="badge <?= $city === $c ? 'badge-info' : 'badge-muted' ?>">
                        <?= e($c) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:8px;">
                <?php if ($city !== '' || $service !== ''): ?>
                    <a href="offices.php" class="btn btn-outline btn-sm">حذف فیلترها</a>
                <?php endif; ?>
                <button class="btn btn-primary btn-sm" type="submit">جستجوی دفاتر</button>
            </div>
        </div>
    </form>
</div>

<!-- نتایج جستجو -->
<div class="section-head">
    <div>
        <h2>فهرست دفاتر یافته‌شده <span style="font-size:14px;color:var(--text-muted);">(<?= count($offices) ?> دفتر)</span></h2>
    </div>
</div>

<div class="grid-cards">
    <?php if (!empty($offices)): ?>
        <?php foreach ($offices as $o): ?>
            <article class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="badge badge-info"><?= e($o['city']) ?> (<?= e($o['province']) ?>)</span>
                    <span class="badge badge-success">آماده پذیرش</span>
                </div>

                <h3 style="font-size:17px;font-weight:800;color:var(--text-main);margin-top:14px;">
                    <?= e($o['name']) ?>
                </h3>

                <p style="font-size:12.5px;color:var(--text-muted);margin-top:8px;line-height:1.7;">
                    📍 <?= e($o['address']) ?>
                </p>

                <?php if (!empty($o['working_hours'])): ?>
                    <div style="font-size:12px;color:#475569;margin-top:10px;background:#f8fafc;padding:6px 10px;border-radius:6px;">
                        ⏰ <?= e($o['working_hours']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($o['services'])): ?>
                    <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:4px;">
                        <?php foreach (explode('،', $o['services']) as $srvItem): ?>
                            <span class="badge badge-muted" style="font-size:11px;"><?= e(trim($srvItem)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top:auto;padding-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="booking.php?office=<?= (int)$o['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;">
                        رزرو نوبت در این دفتر
                    </a>
                    <?php if (!empty($o['phone'])): ?>
                        <a href="tel:<?= e($o['phone']) ?>" class="btn btn-outline btn-sm" title="تماس تلفنی">
                            📞 تماس
                        </a>
                    <?php endif; ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode('دفتر پیشخوان دولت ' . $o['name'] . ' ' . $o['city']) ?>" target="_blank" rel="noopener" class="btn btn-soft btn-sm" title="مسیریابی در نقشه">
                        🗺️ نقشه
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="panel" style="grid-column:1/-1;text-align:center;padding:50px 20px;">
            <h3>دفتری مطابق با جستجوی شما یافت نشد</h3>
            <p class="muted" style="margin-top:8px;">لطفاً نام شهر یا عنوان فیلتر را تغییر دهید.</p>
            <a href="offices.php" class="btn btn-primary" style="margin-top:16px;">مشاهده تمام دفاتر پیشخوان</a>
        </div>
    <?php endif; ?>
</div>

<div class="warning mt-6" style="margin-top:32px;">
    <strong>راهنمایی مراجعین:</strong> قبل از مراجعه حضوری، توصیه می‌شود نوبت خود را به صورت اینترنتی ثبت نموده و مدارک هویتی خود را طبق بخش راهنمای خدمات همراه داشته باشید تا در سریع‌ترین زمان خدمات شما انجام گیرد.
</div>

<?php require 'includes/footer.php'; ?>
