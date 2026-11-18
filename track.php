<?php
/* Visit counter. Pages send a small beacon here; nothing personal is stored (daily salted hash only). */
declare(strict_types=1);
require __DIR__ . '/inc/lib.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || is_bot($ua) || !empty($_COOKIE['mj_owner'])) { http_response_code(204); exit; }

$in = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
$path = (string)($in['p'] ?? '/');
$path = parse_url($path, PHP_URL_PATH) ?: '/';
$path = mb_substr(preg_replace('#[^\p{L}\p{N}/._-]#u', '', rawurldecode($path)), 0, 120) ?: '/';
$ref = (string)($in['r'] ?? '');
$refHost = '(direct)';
if ($ref !== '') {
    $hst = strtolower((string)parse_url($ref, PHP_URL_HOST));
    $self = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($hst !== '' && $hst !== $self && $hst !== 'www.' . $self) $refHost = preg_replace('/^www\./', '', $hst);
    elseif ($hst === $self || $hst === 'www.' . $self) $refHost = null;          // internal navigation
}
$lang = preg_match('/^[a-z]{2}$/', (string)($in['l'] ?? '')) ? $in['l'] : 'fa';
$country = preg_match('/^[A-Z]{2}$/', (string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null;

// a fresh random salt every day: visitors can be counted once per day but never followed across days
$salts = store_read('salts', []);
$day = date('Y-m-d');
if (empty($salts[$day])) { $salts = [$day => bin2hex(random_bytes(16))]; store_write('salts', $salts); }
$vid = substr(hash('sha256', $salts[$day] . client_ip() . $ua), 0, 16);

store_update('stats/' . $day, function ($s) use ($vid, $path, $refHost, $ua, $lang, $country) {
    $s['views'] = ($s['views'] ?? 0) + 1;
    $s['uniq'][$vid] = 1;
    $s['pages'][$path] = ($s['pages'][$path] ?? 0) + 1;
    if ($refHost !== null) $s['refs'][$refHost] = ($s['refs'][$refHost] ?? 0) + 1;
    $d = device_of($ua); $s['dev'][$d] = ($s['dev'][$d] ?? 0) + 1;
    $s['lang'][$lang] = ($s['lang'][$lang] ?? 0) + 1;
    if ($country) $s['country'][$country] = ($s['country'][$country] ?? 0) + 1;
    return $s;
});
http_response_code(204);
