<?php
/* Public read-only data for the home page: latest articles and the portfolio items added in the admin panel. */
declare(strict_types=1);
require __DIR__ . '/inc/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$posts = array_map(fn($p) => [
    'title' => $p['title'], 'url' => '/blog/' . rawurlencode($p['slug']), 'excerpt' => $p['excerpt'], 'cover' => $p['cover'],
    'date' => fa_date((int)$p['published_at']), 'minutes' => reading_minutes($p['content']), 'lang' => $p['lang'],
], array_slice((function () {
    $l = preg_match('/^(fa|en|ar|zh|ko|ru|es)$/', (string)($_GET['lang'] ?? '')) ? $_GET['lang'] : 'fa';
    $mine = array_values(array_filter(posts_published(), fn($p) => ($p['lang'] ?? 'fa') === $l));
    return $mine ?: array_values(array_filter(posts_published(), fn($p) => ($p['lang'] ?? 'fa') === 'fa'));
})(), 0, 3));

$work = array_values(array_map(fn($w) => [
    'title' => $w['title'], 'desc' => $w['desc'], 'tag' => $w['tag'], 'cat' => $w['cat'], 'url' => $w['url'], 'image' => $w['image'],
], array_filter(work_all(), fn($w) => !empty($w['visible']))));

echo json_encode(['posts' => $posts, 'work' => $work], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
