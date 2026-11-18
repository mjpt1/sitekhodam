<?php
/* /blog/{slug} — a single article, with links to its translations */
declare(strict_types=1);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/public.php';

$slug = (string)($_GET['slug'] ?? '');
$post = $slug !== '' ? post_by_slug($slug) : null;
if (!$post || ($post['status'] ?? '') !== 'published') {
    $L = ui_lang();
    http_response_code(404);
    page_open(['lang' => $L, 'title' => t('404', $L) . ' | ' . t('name', $L), 'desc' => '', 'url' => SITE_URL . '/blog/', 'head' => '<meta name="robots" content="noindex">']);
    echo '<section class="wrap page-head"><h1>' . h(t('404', $L)) . '</h1><p class="sub">' . h(t('404.p', $L)) . '</p><a class="btn" href="/blog/' . ($L === 'fa' ? '' : '?lang=' . $L) . '">' . h(t('blog.all', $L)) . '</a></section>';
    page_close($L); exit;
}

$L = isset(LANGS[$post['lang'] ?? '']) ? $post['lang'] : 'fa';      // the page speaks the article's language
$postUrl = fn($p) => SITE_URL . '/blog/' . rawurlencode($p['slug']);
$url = $postUrl($post);
$trans = post_translations($post);                                  // lang => post (includes this one)
$alts = array_map($postUrl, $trans);
$fallbacks = [];
foreach (array_keys(LANGS) as $l) if (!isset($trans[$l])) $fallbacks[$l] = '/blog/?lang=' . $l;

$img = $post['cover'] ? SITE_URL . $post['cover'] : SITE_URL . '/og.jpg';
$alt = trim((string)($post['cover_alt'] ?? '')) ?: $post['title'];
$kw = array_values(array_unique(array_filter(array_merge([(string)($post['focus'] ?? '')], $post['keywords'] ?? [], $post['tags'] ?? []))));
$blogUrl = SITE_URL . '/blog/' . ($L === 'fa' ? '' : '?lang=' . $L);
$ld = [
    '@context' => 'https://schema.org', '@type' => 'BlogPosting',
    'headline' => $post['title'], 'description' => $post['excerpt'], 'url' => $url,
    'mainEntityOfPage' => $url, 'inLanguage' => $L,
    'datePublished' => date('c', (int)$post['published_at']), 'dateModified' => date('c', (int)($post['updated_at'] ?? $post['published_at'])),
    'author' => ['@type' => 'Person', 'name' => t('name', $L), 'url' => SITE_URL . '/'],
    'publisher' => ['@type' => 'Person', 'name' => t('name', $L), 'url' => SITE_URL . '/'],
    'keywords' => implode(', ', $kw),
    'image' => ['@type' => 'ImageObject', 'url' => $img, 'caption' => $alt],
];
if (count($trans) > 1) $ld['workTranslation'] = array_values(array_map(fn($p) => ['@type' => 'BlogPosting', 'url' => $postUrl($p), 'inLanguage' => $p['lang']], array_filter($trans, fn($p) => $p['id'] !== $post['id'])));
$crumbs = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => t('home', $L), 'item' => SITE_URL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => t('blog.title', $L), 'item' => $blogUrl],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $url],
]];
$head = ($kw ? '<meta name="keywords" content="' . h(implode(', ', $kw)) . '">' : '') . '<meta property="og:image:alt" content="' . h($alt) . '">'
      . '<meta property="article:published_time" content="' . date('c', (int)$post['published_at']) . '">'
      . '<meta property="article:modified_time" content="' . date('c', (int)($post['updated_at'] ?? $post['published_at'])) . '">'
      . '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>'
      . '<script type="application/ld+json">' . json_encode($crumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

page_open(['lang' => $L, 'title' => $post['title'] . ' | ' . t('name', $L), 'desc' => $post['excerpt'], 'url' => $url, 'image' => $img,
           'ogType' => 'article', 'active' => 'blog', 'head' => $head, 'alternates' => count($trans) > 1 ? $alts : [], 'langFallback' => $fallbacks]);

// related: other articles in the same language, preferring shared tags
$others = array_values(array_filter(posts_published(), fn($p) => post_group_key($p) !== post_group_key($post) && ($p['lang'] ?? 'fa') === $L));
usort($others, fn($a, $b) => count(array_intersect($b['tags'] ?? [], $post['tags'] ?? [])) <=> count(array_intersect($a['tags'] ?? [], $post['tags'] ?? [])));
$others = array_slice($others, 0, 3);
$blogPath = '/blog/' . ($L === 'fa' ? '' : '?lang=' . $L);
?>
<article class="wrap article">
  <nav class="crumbs" aria-label="breadcrumb"><a href="<?= $L === 'fa' ? '/' : '/?lang=' . $L ?>"><?= h(t('home', $L)) ?></a> / <a href="<?= $blogPath ?>"><?= h(t('blog.title', $L)) ?></a></nav>
  <header>
    <h1><?= h($post['title']) ?></h1>
    <div class="meta">
      <span><?= h(t('name', $L)) ?></span> ·
      <time datetime="<?= date('c', (int)$post['published_at']) ?>"><?= h(local_date((int)$post['published_at'], $L)) ?></time> ·
      <span><?= h(t('min', $L, local_num(reading_minutes($post['content']), $L))) ?></span>
    </div>
    <?php if (!empty($post['tags'])): ?><div class="tags"><?php foreach ($post['tags'] as $tg): ?><a class="chip" href="<?= $blogPath . ($L === 'fa' ? '?' : '&') ?>tag=<?= h(rawurlencode($tg)) ?>"><?= h($tg) ?></a><?php endforeach; ?></div><?php endif; ?>
    <?php if (count($trans) > 1): ?>
      <div class="trans"><span><?= h(t('translations', $L)) ?></span>
        <?php foreach ($trans as $tl => $tp): if ($tp['id'] === $post['id']) continue; ?><a class="chip" href="/blog/<?= h(rawurlencode($tp['slug'])) ?>" hreflang="<?= h($tl) ?>" lang="<?= h($tl) ?>"><?= h(LANGS[$tl]['name']) ?></a><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </header>
  <?php if ($post['cover']): ?><img class="cover" src="<?= h($post['cover']) ?>" alt="<?= h($alt) ?>" width="1200" height="630" fetchpriority="high"><?php endif; ?>
  <div class="prose"><?= auto_internal_links($post['content'], $post) ?></div>
  <aside class="card cta">
    <h2><?= h(t('cta.h2', $L)) ?></h2>
    <p class="sub"><?= h(t('cta.p', $L)) ?></p>
    <a class="btn" href="<?= $L === 'fa' ? '/' : '/?lang=' . $L ?>#contact"><?= h(t('cta.btn', $L)) ?></a>
  </aside>
</article>
<?php if ($others): ?>
<section class="wrap">
  <h2 class="h2"><?= h(t('more', $L)) ?></h2>
  <div class="posts">
    <?php foreach ($others as $p): ?>
      <a class="card post-card" href="/blog/<?= h(rawurlencode($p['slug'])) ?>">
        <div class="pc-img"><?php if ($p['cover']): ?><img src="<?= h($p['cover']) ?>" alt="<?= h(($p['cover_alt'] ?? '') ?: $p['title']) ?>" loading="lazy" width="1200" height="630"><?php else: ?><span class="pc-ph"><?= h(mb_substr($p['title'], 0, 1)) ?></span><?php endif; ?></div>
        <div class="pc-body"><h2><?= h($p['title']) ?></h2><p><?= h($p['excerpt']) ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif;
page_close($L);
