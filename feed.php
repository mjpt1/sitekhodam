<?php
/* /feed.xml — RSS feed of the latest articles */
declare(strict_types=1);
require __DIR__ . '/inc/lib.php';
header('Content-Type: application/rss+xml; charset=utf-8');
$posts = array_slice(posts_published(), 0, 20);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title><?= h(SITE_NAME) ?> — مقالات</title>
  <link><?= SITE_URL ?>/blog/</link>
  <atom:link href="<?= SITE_URL ?>/feed.xml" rel="self" type="application/rss+xml" />
  <description>مقاله‌های برنامه‌نویسی، طراحی سایت و سئو</description>
  <language>fa</language>
<?php foreach ($posts as $p): $u = SITE_URL . '/blog/' . rawurlencode($p['slug']); ?>
  <item>
    <title><?= h($p['title']) ?></title>
    <link><?= h($u) ?></link>
    <guid isPermaLink="true"><?= h($u) ?></guid>
    <pubDate><?= date(DATE_RSS, (int)$p['published_at']) ?></pubDate>
    <description><?= h($p['excerpt']) ?></description>
  </item>
<?php endforeach; ?>
</channel>
</rss>
