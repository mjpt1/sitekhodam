<?php
/* /sitemap.xml — home page in every language plus all published articles */
declare(strict_types=1);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/i18n.php';
header('Content-Type: application/xml; charset=utf-8');

$langs = ['fa', 'en', 'ar', 'zh', 'ko', 'ru', 'es'];
$home = fn($l) => SITE_URL . '/' . ($l === 'fa' ? '' : '?lang=' . $l);
$posts = posts_published();
$lastPost = $posts ? max(array_map(fn($p) => (int)($p['updated_at'] ?? $p['published_at']), $posts)) : filemtime(__DIR__ . '/index.html');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($langs as $l) {
    echo "  <url>\n    <loc>" . h($home($l)) . "</loc>\n    <lastmod>" . date('Y-m-d', filemtime(__DIR__ . '/index.html')) . "</lastmod>\n";
    foreach ($langs as $l2) echo '    <xhtml:link rel="alternate" hreflang="' . $l2 . '" href="' . h($home($l2)) . "\" />\n";
    echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . SITE_URL . "/\" />\n  </url>\n";
}
$blogLangs = array_values(array_unique(array_map(fn($p) => $p['lang'] ?? 'fa', $posts))) ?: ['fa'];
foreach ($blogLangs as $bl) echo "  <url>\n    <loc>" . h(SITE_URL . '/blog/' . ($bl === 'fa' ? '' : '?lang=' . $bl)) . "</loc>\n    <lastmod>" . date('Y-m-d', $lastPost) . "</lastmod>\n  </url>\n";
foreach ($posts as $p) {
    echo "  <url>\n    <loc>" . h(SITE_URL . '/blog/' . rawurlencode($p['slug'])) . "</loc>\n    <lastmod>" . date('Y-m-d', (int)($p['updated_at'] ?? $p['published_at'])) . "</lastmod>\n";
    $tr = post_translations($p);
    if (count($tr) > 1) foreach ($tr as $tl => $tp) echo '    <xhtml:link rel="alternate" hreflang="' . h($tl) . '" href="' . h(SITE_URL . '/blog/' . rawurlencode($tp['slug'])) . "\" />\n";
    if ($p['cover']) echo '    <image:image><image:loc>' . h(SITE_URL . $p['cover']) . "</image:loc></image:image>\n";
    echo "  </url>\n";
}
// shop: static pages in /shop/
foreach (['/shop/', '/shop/dino-pod/'] as $sp) echo "  <url>\n    <loc>" . SITE_URL . $sp . "</loc>\n  </url>\n";
echo "</urlset>\n";
