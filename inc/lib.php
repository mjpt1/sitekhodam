<?php
/*
 * Shared helpers for the public pages and the admin panel.
 * Storage is plain JSON files in /data (locked from the web by data/.htaccess), so no database is needed.
 */
declare(strict_types=1);

const SITE_URL  = 'https://mahsen81.ir';
const SITE_NAME = 'محسن جباره‌اصل';
define('ROOT', dirname(__DIR__));
define('DATA', ROOT . '/data');
define('UPLOADS', ROOT . '/uploads');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tehran');

function ensure_dirs(): void {
    foreach ([DATA, DATA . '/stats', UPLOADS] as $d) if (!is_dir($d)) @mkdir($d, 0755, true);
    if (!file_exists(DATA . '/.htaccess')) @file_put_contents(DATA . '/.htaccess', "Require all denied\nDeny from all\n");
    if (!file_exists(UPLOADS . '/.htaccess')) @file_put_contents(UPLOADS . '/.htaccess', "Options -Indexes\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phar\nRemoveType .php .phtml .phar\n<FilesMatch \"\\.(php|phtml|phar|php\\d)$\">\n  Require all denied\n  Deny from all\n</FilesMatch>\n");
}
ensure_dirs();

/* ---------- JSON storage with file locks ---------- */
function store_read(string $name, $default = []) {
    $f = DATA . "/$name.json";
    if (!is_file($f)) return $default;
    $fp = fopen($f, 'r'); if (!$fp) return $default;
    flock($fp, LOCK_SH); $raw = stream_get_contents($fp); flock($fp, LOCK_UN); fclose($fp);
    $v = json_decode((string)$raw, true);
    return is_array($v) ? $v : $default;
}
function store_write(string $name, $value): void {
    $f = DATA . "/$name.json";
    $fp = fopen($f, 'c+'); if (!$fp) throw new RuntimeException('cannot write ' . $name);
    flock($fp, LOCK_EX); ftruncate($fp, 0); rewind($fp);
    fwrite($fp, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
}
/* read-modify-write in one locked step (used for counters and lists) */
function store_update(string $name, callable $fn, $default = []) {
    $f = DATA . "/$name.json";
    $fp = fopen($f, 'c+'); if (!$fp) throw new RuntimeException('cannot write ' . $name);
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $v = json_decode((string)$raw, true); if (!is_array($v)) $v = $default;
    $v = $fn($v);
    ftruncate($fp, 0); rewind($fp);
    fwrite($fp, json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
    return $v;
}

/* ---------- small helpers ---------- */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function new_id(): string { return bin2hex(random_bytes(6)); }
function client_ip(): string { return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[\x{200C}\x{200D}]/u', '-', $s);                  // zero-width joiners used in Persian
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    $s = trim((string)$s, '-');
    return mb_substr($s, 0, 80) ?: new_id();
}
function fa_date(int $ts): string {                                         // Jalali date for Persian readers
    if (class_exists('IntlDateFormatter')) {
        $f = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL);
        $r = $f->format($ts); if ($r) return $r;
    }
    return date('Y-m-d', $ts);
}
function plain_text(string $html): string {                                 // keep a space where block tags end
    $html = preg_replace('#</(p|h[1-6]|li|blockquote|pre|figcaption|td|th|div)>|<br\s*/?>#i', ' ', $html);
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$html), ENT_QUOTES, 'UTF-8')));
}
function reading_minutes(string $html): int { return max(1, (int)ceil(count(preg_split('/\s+/u', plain_text($html))) / 200)); }
function excerpt_of(string $html, int $len = 160): string {
    $t = plain_text($html);
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len - 1) . '…' : $t;
}

/* ---------- posts & work ---------- */
function posts_all(): array {
    $p = store_read('posts');
    usort($p, fn($a, $b) => ($b['published_at'] ?? $b['created_at'] ?? 0) <=> ($a['published_at'] ?? $a['created_at'] ?? 0));
    return $p;
}
function posts_published(): array { return array_values(array_filter(posts_all(), fn($p) => ($p['status'] ?? '') === 'published')); }
function post_by_slug(string $slug): ?array { foreach (posts_all() as $p) if ($p['slug'] === $slug) return $p; return null; }
function work_all(): array {
    $w = store_read('work');
    usort($w, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0) ?: ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
    return $w;
}

/* ---------- HTML sanitising for article bodies (allow-list) ---------- */
function sanitize_html(string $html): string {
    $allowed = ['p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'title'], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'img' => ['src', 'alt', 'width', 'height'], 'figure' => [], 'figcaption' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => []];
    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) return '';
    $walk = function (DOMNode $node) use (&$walk, $allowed, $doc) {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $c = $node->childNodes->item($i);
            if ($c instanceof DOMElement) {
                $tag = strtolower($c->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'svg', 'math'], true)) { $node->removeChild($c); continue; }
                if (!isset($allowed[$tag])) {                                // unwrap unknown tags, keep their text
                    $walk($c);
                    while ($c->firstChild) $node->insertBefore($c->firstChild, $c);
                    $node->removeChild($c); continue;
                }
                foreach (iterator_to_array($c->attributes) as $attr) {
                    $n = strtolower($attr->name);
                    if (!in_array($n, $allowed[$tag], true)) { $c->removeAttribute($attr->name); continue; }
                    if (in_array($n, ['href', 'src'], true)) {
                        $v = trim($attr->value);
                        $ok = preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $v) && !preg_match('#^\s*javascript:#i', $v);
                        if ($n === 'src' && !preg_match('#^(https://|/uploads/)#i', $v)) $ok = false;
                        if (!$ok) $c->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'a' && preg_match('#^https?://#i', $c->getAttribute('href')) && strpos($c->getAttribute('href'), SITE_URL) !== 0) {
                    $c->setAttribute('target', '_blank'); $c->setAttribute('rel', 'noopener nofollow');
                }
                if ($tag === 'img') { $c->setAttribute('loading', 'lazy'); $c->setAttribute('decoding', 'async'); }
                $walk($c);
            } elseif ($c instanceof DOMComment) {
                $node->removeChild($c);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}

/* ---------- image uploads ---------- */
function save_upload(array $file, int $maxW = 1600): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'آپلود انجام نشد.'];
    if ($file['size'] > 6 * 1024 * 1024) return ['ok' => false, 'error' => 'حجم تصویر باید کمتر از ۶ مگابایت باشد.'];
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($types[$info[2]])) return ['ok' => false, 'error' => 'فقط تصویر JPG، PNG، WebP یا GIF قابل قبول است.'];
    $dir = UPLOADS . '/' . date('Y/m');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $base = bin2hex(random_bytes(8));
    [$w, $hgt] = $info;
    // re-encode to WebP when GD can: strips anything hidden in the file and makes it lighter
    if (function_exists('imagewebp') && $info[2] !== IMAGETYPE_GIF) {
        $src = match ($info[2]) { IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']), IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']), IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']), default => false };
        if ($src) {
            if ($w > $maxW) { $nh = (int)round($hgt * $maxW / $w); $dst = imagecreatetruecolor($maxW, $nh); imagealphablending($dst, false); imagesavealpha($dst, true); imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $hgt); imagedestroy($src); $src = $dst; $w = $maxW; $hgt = $nh; }
            else { imagepalettetotruecolor($src); imagealphablending($src, false); imagesavealpha($src, true); }
            $path = "$dir/$base.webp";
            imagewebp($src, $path, 82); imagedestroy($src);
            return ['ok' => true, 'url' => '/uploads/' . date('Y/m') . "/$base.webp", 'width' => $w, 'height' => $hgt];
        }
    }
    $path = "$dir/$base." . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $path)) return ['ok' => false, 'error' => 'ذخیرهٔ فایل ممکن نشد.'];
    return ['ok' => true, 'url' => '/uploads/' . date('Y/m') . "/$base." . $types[$info[2]], 'width' => $w, 'height' => $hgt];
}

/* ---------- visit statistics (privacy friendly: daily salted hashes, no raw IPs stored) ---------- */
function is_bot(string $ua): bool {
    return $ua === '' || (bool)preg_match('/bot|crawl|spider|slurp|bing|facebookexternalhit|preview|headless|lighthouse|pingdom|monitor|curl|wget|python|java\/|go-http|axios|node-fetch|scrapy/i', $ua);
}
function device_of(string $ua): string {
    if (preg_match('/ipad|tablet/i', $ua)) return 'tablet';
    if (preg_match('/mobi|android|iphone/i', $ua)) return 'mobile';
    return 'desktop';
}
function stats_day(string $day): array { return store_read('stats/' . $day, []); }
function fa_num($n): string { return strtr((string)$n, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']); }

/* ---------- automatic internal links between articles ----------
   The first mention of another published article's focus keyword (same language) becomes a link to it.
   Skips headings, existing links and code, never links an article to itself, at most 4 new links per article. */
function auto_internal_links(string $html, array $post, int $max = 4): string {
    $lang = $post['lang'] ?? 'fa';
    $targets = [];
    foreach (posts_published() as $p) {
        if ($p['id'] === $post['id'] || ($p['lang'] ?? 'fa') !== $lang) continue;
        if (($p['translation_of'] ?? '') === $post['id'] || ($post['translation_of'] ?? '') === $p['id']) continue;
        $url = '/blog/' . rawurlencode($p['slug']);
        if (strpos($html, 'href="' . $url . '"') !== false || strpos($html, 'href="/blog/' . $p['slug'] . '"') !== false) continue;   // already linked by hand
        // phrases that point to this article: its focus keyword, then its multi-word secondary keywords
        $phrases = array_merge([(string)($p['focus'] ?? '')], (array)($p['keywords'] ?? []));
        $alts = [];
        foreach ($phrases as $kw) {
            $kw = trim((string)$kw);
            $words = preg_split('/[\s\x{200C}]+/u', $kw, -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) < 2 || mb_strlen($kw) < 6) continue;              // single words are too generic to link
            // a space also matches a zero-width non-joiner (برنامه نویسی = برنامه‌نویسی); Arabic ye/kaf count as Persian
            $parts = array_map(fn($w) => strtr(preg_quote($w, '/'), ['ی' => '[یي]', 'ک' => '[کك]']), $words);
            $alts[mb_strlen($kw)][] = implode('[\s\x{200C}]+', $parts);
        }
        if (!$alts) continue;
        krsort($alts);                                                           // longer phrases are tried first
        $re = '/(?<![\p{L}\p{N}])(' . implode('|', array_merge(...array_values($alts))) . ')(?![\p{L}\p{N}])/iu';
        $targets[] = ['re' => $re, 'url' => $url, 'title' => $p['title'], 'len' => max(array_keys($alts))];
    }
    if (!$targets) return $html;
    usort($targets, fn($a, $b) => $b['len'] <=> $a['len']);              // longer phrases first

    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) return $html;
    $xp = new DOMXPath($doc);
    $added = 0;
    foreach ($targets as $t) {
        if ($added >= $max) break;
        // text inside paragraphs and list items, not inside links, headings, code or quotes of code
        $nodes = $xp->query('.//text()[ancestor::p or ancestor::li or ancestor::td][not(ancestor::a)][not(ancestor::h2)][not(ancestor::h3)][not(ancestor::h4)][not(ancestor::pre)][not(ancestor::code)]', $root);
        foreach ($nodes as $node) {
            if (!preg_match($t['re'], $node->nodeValue, $m, PREG_OFFSET_CAPTURE)) continue;
            $text = $node->nodeValue;
            $before = substr($text, 0, $m[1][1]); $match = $m[1][0]; $after = substr($text, $m[1][1] + strlen($match));
            $a = $doc->createElement('a'); $a->setAttribute('href', $t['url']); $a->setAttribute('title', $t['title']);
            $a->appendChild($doc->createTextNode($match));
            $parent = $node->parentNode;
            if ($before !== '') $parent->insertBefore($doc->createTextNode($before), $node);
            $parent->insertBefore($a, $node);
            if ($after !== '') $parent->insertBefore($doc->createTextNode($after), $node);
            $parent->removeChild($node);
            $added++;
            break;                                                       // only the first mention of each article
        }
    }
    if (!$added) return $html;
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return $out;
}
