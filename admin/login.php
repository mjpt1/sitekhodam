<?php
require '_boot.php';
if (is_logged_in()) redirect('index.php');

$cfg = admin_config();
$setup = empty($cfg['pass_hash']);           // first visit: create the admin account
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_locked()) {
        $error = 'به‌خاطر تلاش‌های ناموفق زیاد، ورود ۱۵ دقیقه قفل شده است.';
    } elseif ($setup) {
        $code = trim((string)($_POST['code'] ?? ''));
        $user = trim((string)($_POST['user'] ?? ''));
        $pass = (string)($_POST['pass'] ?? '');
        if (!hash_equals(SETUP_CODE, strtoupper($code))) { login_failed(); $error = 'کد راه‌اندازی درست نیست.'; }
        elseif (mb_strlen($user) < 3) $error = 'نام کاربری باید حداقل ۳ حرف باشد.';
        elseif (strlen($pass) < 10) $error = 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.';
        elseif ($pass !== ($_POST['pass2'] ?? '')) $error = 'تکرار رمز عبور با خودش یکی نیست.';
        else {
            store_write('config', ['user' => $user, 'pass_hash' => password_hash($pass, PASSWORD_DEFAULT), 'created_at' => time()]);
            login_ok(); flash('حساب مدیریت ساخته شد. خوش آمدید!'); redirect('index.php');
        }
    } else {
        $user = trim((string)($_POST['user'] ?? ''));
        $pass = (string)($_POST['pass'] ?? '');
        if (hash_equals((string)$cfg['user'], $user) && password_verify($pass, (string)$cfg['pass_hash'])) {
            if (password_needs_rehash($cfg['pass_hash'], PASSWORD_DEFAULT)) { $cfg['pass_hash'] = password_hash($pass, PASSWORD_DEFAULT); store_write('config', $cfg); }
            login_ok(); redirect('index.php');
        }
        login_failed(); usleep(400000);
        $error = 'نام کاربری یا رمز عبور درست نیست.';
    }
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>ورود به مدیریت</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css?v=2">
</head>
<body class="login-page">
<form class="card login" method="post" autocomplete="on">
  <div class="brand"><span class="badge">MJ</span> مدیریت سایت</div>
  <h1><?= $setup ? 'ساخت حساب مدیریت' : 'ورود' ?></h1>
  <?php if ($setup): ?><p class="muted">این اولین ورود است. کد راه‌اندازی را وارد کنید و نام کاربری و رمز عبور خودتان را بسازید.</p><?php endif; ?>
  <?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <?php if ($setup): ?>
    <label>کد راه‌اندازی<input id="code" name="code" required dir="ltr" autocomplete="off"></label>
  <?php endif; ?>
  <label>نام کاربری<input id="user" name="user" required dir="ltr" autocomplete="username" value="<?= h($_POST['user'] ?? '') ?>"></label>
  <label>رمز عبور<input id="pass" name="pass" type="password" required dir="ltr" autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>"></label>
  <?php if ($setup): ?>
    <label>تکرار رمز عبور<input id="pass2" name="pass2" type="password" required dir="ltr" autocomplete="new-password"></label>
  <?php endif; ?>
  <button class="btn primary"><?= $setup ? 'ساخت حساب و ورود' : 'ورود' ?></button>
</form>
</body>
</html>
