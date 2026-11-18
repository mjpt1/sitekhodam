<?php
/* Admin bootstrap: session, login guard, CSRF, layout. Every admin page starts with require '_boot.php'. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/lib.php';
require dirname(__DIR__) . '/inc/config.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('mjadm');
session_set_cookie_params(['lifetime' => 0, 'path' => '/admin', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();

const IDLE_LIMIT = 7200;                                   // sign out after 2 hours without activity

function admin_config(): array { return store_read('config', []); }
function is_logged_in(): bool {
    if (empty($_SESSION['admin'])) return false;
    if (time() - ($_SESSION['seen'] ?? 0) > IDLE_LIMIT) { $_SESSION = []; session_destroy(); return false; }
    $_SESSION['seen'] = time();
    return true;
}
function require_login(): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? ''))) {
        http_response_code(400); exit('درخواست نامعتبر است. صفحه را دوباره باز کنید.');
    }
}
function flash(?string $msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}
function redirect(string $to): void { header('Location: ' . $to); exit; }

/* brute-force protection: 5 wrong passwords per IP lock the login for 15 minutes */
function login_locked(): bool {
    $a = store_read('login_attempts', []); $ip = hash('sha256', client_ip());
    $rec = $a[$ip] ?? ['n' => 0, 't' => 0];
    return $rec['n'] >= 5 && time() - $rec['t'] < 900;
}
function login_failed(): void {
    $ip = hash('sha256', client_ip());
    store_update('login_attempts', function ($a) use ($ip) {
        $rec = $a[$ip] ?? ['n' => 0, 't' => 0];
        if (time() - $rec['t'] > 900) $rec = ['n' => 0, 't' => 0];
        $a[$ip] = ['n' => $rec['n'] + 1, 't' => time()];
        return $a;
    });
}
function login_ok(): void {
    $ip = hash('sha256', client_ip());
    store_update('login_attempts', function ($a) use ($ip) { unset($a[$ip]); return $a; });
    session_regenerate_id(true);
    $_SESSION['admin'] = true; $_SESSION['seen'] = time();
    // mark this browser so the site does not count the owner's own visits
    setcookie('mj_owner', '1', ['expires' => time() + 31536000, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
}

function unread_count(): int { return count(array_filter(store_read('messages', []), fn($m) => empty($m['read']))); }

function layout_top(string $title, string $active = ''): void {
    $nav = [['index.php', 'داشبورد', 'dash'], ['posts.php', 'مقالات', 'posts'], ['work.php', 'نمونه‌کارها', 'work'], ['messages.php', 'پیام‌ها', 'msg'], ['settings.php', 'تنظیمات', 'set']];
    $unread = unread_count();
    ?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> | مدیریت سایت</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css?v=2">
</head>
<body>
<header class="top">
  <a class="brand" href="index.php"><span class="badge">MJ</span> مدیریت سایت</a>
  <nav>
    <?php foreach ($nav as [$href, $label, $key]): ?>
      <a href="<?= $href ?>" class="<?= $key === $active ? 'on' : '' ?>"><?= $label ?><?php if ($key === 'msg' && $unread): ?> <b class="pill"><?= $unread ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="top-end">
    <a href="/" target="_blank" rel="noopener">مشاهدهٔ سایت ↗</a>
    <form method="post" action="logout.php"><?= csrf_field() ?><button class="link">خروج</button></form>
  </div>
</header>
<main class="wrap">
<?php if ($f = flash()): ?><div class="flash"><?= h($f) ?></div><?php endif;
}
function layout_bottom(string $extraJs = ''): void {
    ?></main>
<script src="admin.js?v=1"></script><?= $extraJs ?>
</body>
</html><?php
}
