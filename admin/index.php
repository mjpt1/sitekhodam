<?php
require '_boot.php';
require_login();

$range = (int)($_GET['days'] ?? 30);
if (!in_array($range, [7, 30, 90], true)) $range = 30;

// aggregate the daily stat files
$days = []; $tot = ['views' => 0, 'uniq' => 0]; $pages = []; $refs = []; $dev = []; $lang = []; $country = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $s = stats_day($d);
    $v = (int)($s['views'] ?? 0); $u = count($s['uniq'] ?? []);
    $days[] = ['d' => $d, 'v' => $v, 'u' => $u];
    $tot['views'] += $v; $tot['uniq'] += $u;
    foreach (['pages' => &$pages, 'refs' => &$refs, 'dev' => &$dev, 'lang' => &$lang, 'country' => &$country] as $k => &$bucket)
        foreach (($s[$k] ?? []) as $key => $n) $bucket[$key] = ($bucket[$key] ?? 0) + $n;
    unset($bucket);
}
$today = end($days);
$week = array_slice($days, -7);
$weekViews = array_sum(array_column($week, 'v'));
arsort($pages); arsort($refs); arsort($dev); arsort($lang); arsort($country);
$maxV = max(1, max(array_column($days, 'v')));

$posts = posts_all();
$published = count(array_filter($posts, fn($p) => ($p['status'] ?? '') === 'published'));
$work = work_all();
$unread = unread_count();

$langNames = ['fa' => 'فارسی', 'en' => 'English', 'ar' => 'العربية', 'zh' => '中文', 'ko' => '한국어', 'ru' => 'Русский', 'es' => 'Español'];
$devNames = ['mobile' => 'موبایل', 'desktop' => 'کامپیوتر', 'tablet' => 'تبلت'];

function bars(array $data, int $limit = 8, array $names = []): void {
    if (!$data) { echo '<p class="muted">هنوز داده‌ای ثبت نشده.</p>'; return; }
    $max = max($data); echo '<ul class="bars">';
    foreach (array_slice($data, 0, $limit, true) as $k => $n) {
        $label = $names[$k] ?? $k;
        echo '<li><span class="b-label" title="' . h($label) . '">' . h($label) . '</span><span class="b-track"><i style="width:' . round($n / $max * 100) . '%"></i></span><b>' . number_format($n) . '</b></li>';
    }
    echo '</ul>';
}

layout_top('داشبورد', 'dash');
?>
<div class="head-row">
  <h1>داشبورد</h1>
  <div class="seg">
    <?php foreach ([7, 30, 90] as $r): ?><a href="?days=<?= $r ?>" class="<?= $r === $range ? 'on' : '' ?>"><?= $r ?> روز</a><?php endforeach; ?>
  </div>
</div>

<section class="kpis">
  <div class="card kpi"><span>بازدید امروز</span><strong><?= number_format($today['v']) ?></strong><small><?= number_format($today['u']) ?> بازدیدکنندهٔ یکتا</small></div>
  <div class="card kpi"><span>بازدید ۷ روز اخیر</span><strong><?= number_format($weekViews) ?></strong><small><?= number_format(array_sum(array_column($week, 'u'))) ?> بازدیدکنندهٔ یکتا</small></div>
  <div class="card kpi"><span>بازدید <?= $range ?> روز</span><strong><?= number_format($tot['views']) ?></strong><small><?= number_format($tot['uniq']) ?> بازدیدکنندهٔ یکتا</small></div>
  <div class="card kpi"><span>محتوا</span><strong><?= $published ?> <em>مقاله</em> · <?= count($work) ?> <em>نمونه‌کار</em></strong><small><a href="messages.php"><?= $unread ?> پیام خوانده‌نشده</a></small></div>
</section>

<section class="card chart-card">
  <div class="card-head"><h2>بازدید روزانه</h2><span class="legend"><i class="lg-v"></i>بازدید <i class="lg-u"></i>یکتا</span></div>
  <div class="chart" role="img" aria-label="نمودار بازدید روزانه">
    <?php foreach ($days as $d): $hv = round($d['v'] / $maxV * 100); $hu = round($d['u'] / $maxV * 100); ?>
      <div class="col" title="<?= h(fa_date(strtotime($d['d']))) ?> — <?= $d['v'] ?> بازدید، <?= $d['u'] ?> یکتا">
        <span class="bar v" style="height:<?= $hv ?>%"></span><span class="bar u" style="height:<?= $hu ?>%"></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="chart-axis"><span><?= h(fa_date(strtotime($days[0]['d']))) ?></span><span>امروز</span></div>
</section>

<section class="grid2">
  <div class="card"><h2>صفحه‌های پربازدید</h2><?php bars($pages, 10); ?></div>
  <div class="card"><h2>از کجا آمده‌اند</h2><?php bars($refs, 10, ['(direct)' => 'مستقیم / بدون ارجاع']); ?></div>
  <div class="card"><h2>دستگاه</h2><?php bars($dev, 3, $devNames); ?></div>
  <div class="card"><h2>زبان سایت</h2><?php bars($lang, 7, $langNames); ?></div>
  <?php if ($country): ?><div class="card"><h2>کشور</h2><?php bars($country, 10); ?></div><?php endif; ?>
</section>
<p class="muted small">بازدیدهای خودتان (مرورگری که با آن وارد مدیریت شده‌اید) و ربات‌ها شمرده نمی‌شوند. برای حفظ حریم خصوصی، IP بازدیدکننده‌ها ذخیره نمی‌شود.</p>
<?php layout_bottom();
