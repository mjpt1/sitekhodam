<?php
require '_boot.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cfg = admin_config();
    $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? ''); $user = trim((string)($_POST['user'] ?? $cfg['user']));
    if (!password_verify($cur, (string)$cfg['pass_hash'])) $error = 'رمز فعلی درست نیست.';
    elseif (mb_strlen($user) < 3) $error = 'نام کاربری باید حداقل ۳ حرف باشد.';
    elseif ($new !== '' && strlen($new) < 10) $error = 'رمز جدید باید حداقل ۱۰ کاراکتر باشد.';
    elseif ($new !== ($_POST['new2'] ?? '')) $error = 'تکرار رمز جدید با خودش یکی نیست.';
    else {
        $cfg['user'] = $user;
        if ($new !== '') $cfg['pass_hash'] = password_hash($new, PASSWORD_DEFAULT);
        store_write('config', $cfg);
        session_regenerate_id(true);
        flash('تنظیمات ذخیره شد.'); redirect('settings.php');
    }
}
$cfg = admin_config();
layout_top('تنظیمات', 'set');
?>
<div class="head-row"><h1>تنظیمات</h1></div>
<?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
<form class="card narrow" method="post">
  <h2>حساب کاربری</h2>
  <?= csrf_field() ?>
  <label>نام کاربری<input id="s-user" name="user" dir="ltr" value="<?= h($cfg['user'] ?? '') ?>" autocomplete="username"></label>
  <label>رمز جدید (اگر نمی‌خواهید عوض شود خالی بگذارید)<input id="s-new" name="new" type="password" dir="ltr" autocomplete="new-password"></label>
  <label>تکرار رمز جدید<input id="s-new2" name="new2" type="password" dir="ltr" autocomplete="new-password"></label>
  <label>رمز فعلی (برای تأیید)<input id="s-cur" name="current" type="password" dir="ltr" required autocomplete="current-password"></label>
  <button class="btn primary">ذخیره</button>
</form>
<div class="card narrow">
  <h2>پشتیبان‌گیری</h2>
  <p class="muted">همهٔ محتوای پنل (مقاله‌ها، نمونه‌کارها، پیام‌ها و آمار) در پوشهٔ <code>data</code> روی هاست ذخیره می‌شود و تصاویر در پوشهٔ <code>uploads</code>. برای پشتیبان‌گیری کافی است این دو پوشه را از File Manager دانلود کنید.</p>
</div>
<?php layout_bottom();
