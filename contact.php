<?php
/*
 * Contact form endpoint: receives the popup form (POST) and emails it to Mohsen.
 * Works on any normal PHP host (cPanel / DirectAdmin) using PHP's mail().
 * Answers with JSON: {"ok":true} or {"ok":false,"error":"..."}
 */
require __DIR__ . '/inc/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$TO   = 'mohsen7957@gmail.com';          // where messages are delivered
$FROM = 'no-reply@mahsen81.ir';          // must be an address on this domain, or mail is often rejected

function out($ok, $error = '', $code = 200) {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}
function clean_line($s, $max) {                       // single line: no header injection, trimmed, length-capped
    $s = trim(preg_replace('/[\r\n\t]+/', ' ', (string)$s));
    return mb_substr($s, 0, $max, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'method', 405);

// spam trap: real visitors never see or fill this field
if (!empty($_POST['website'])) out(true);

// simple rate limit: at most 5 messages per hour per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rlFile = sys_get_temp_dir() . '/mj_contact_' . md5($ip);
$now = time();
$hits = array_filter(@json_decode(@file_get_contents($rlFile), true) ?: [], function ($t) use ($now) { return $t > $now - 3600; });
if (count($hits) >= 5) out(false, 'rate', 429);

$name    = clean_line($_POST['name'] ?? '', 100);
$email   = clean_line($_POST['email'] ?? '', 150);
$phone   = clean_line($_POST['phone'] ?? '', 30);
$types   = ['web' => 'طراحی سایت', 'seo' => 'سئو', 'soft' => 'نرم‌افزار و برنامه‌نویسی', 'ai' => 'هوش مصنوعی', 'other' => 'سایر'];
$type    = $types[$_POST['type'] ?? ''] ?? '';
$lang    = clean_line($_POST['lang'] ?? '', 5);
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 5000, 'UTF-8'));

if (mb_strlen($name, 'UTF-8') < 2)                     out(false, 'name', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))         out(false, 'email', 422);
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $phone)) out(false, 'phone', 422);
if (mb_strlen($message, 'UTF-8') < 10)                  out(false, 'message', 422);

$subject = 'پیام جدید از سایت mahsen81.ir — ' . $name;
$body  = "نام: $name\n";
$body .= "ایمیل: $email\n";
$body .= 'تلفن: ' . ($phone !== '' ? $phone : '-') . "\n";
$body .= 'نوع پروژه: ' . ($type !== '' ? $type : '-') . "\n";
$body .= "زبان سایت: $lang\n";
$body .= "\n----- پیام -----\n$message\n----------------\n\n";
$body .= 'زمان: ' . gmdate('Y-m-d H:i') . " UTC\nIP: $ip\n";

$headers  = 'From: =?UTF-8?B?' . base64_encode('فرم سایت محسن جباره‌اصل') . "?= <$FROM>\r\n";
$headers .= "Reply-To: $email\r\n";                   // pressing Reply answers the visitor directly
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

// keep a copy in the admin panel inbox, so nothing is lost even if email delivery fails
$saved = false;
try {
    store_update('messages', function ($all) use ($name, $email, $phone, $type, $message, $lang) {
        $all[] = ['id' => new_id(), 'name' => $name, 'email' => $email, 'phone' => $phone, 'type' => $type, 'message' => $message, 'lang' => $lang, 'at' => time(), 'read' => false];
        return $all;
    });
    $saved = true;
} catch (Throwable $e) {}

$sent = @mail($TO, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-f' . $FROM);
if (!$sent && !$saved) out(false, 'send', 500);

$hits[] = $now;
@file_put_contents($rlFile, json_encode(array_values($hits)));
out(true);
