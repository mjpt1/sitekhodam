<?php
/* /blog/ — published articles in the visitor's language (falls back to Persian when a language has none yet) */
declare(strict_types=1);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/public.php';

$L = ui_lang();
$tag = trim((string)($_GET['tag'] ?? ''));
$all = posts_published();
$posts = array_values(array_filter($all, fn($p) => ($p['lang'] ?? 'fa') === $L));
$fallback = false;
if (!$posts && $L !== 'fa') { $posts = array_values(array_filter($all, fn($p) => ($p['lang'] ?? 'fa') === 'fa')); $fallback = (bool)$posts; }
if ($tag !== '') $posts = array_values(array_filter($posts, fn($p) => in_array($tag, $p['tags'] ?? [], true)));

$langUrl = fn($l) => SITE_URL . '/blog/' . ($l === 'fa' ? '' : '?lang=' . $l);
$alts = [];
foreach (array_keys(LANGS) as $l) $alts[$l] = $langUrl($l);

page_open([
    'lang' => $L,
    'title' => ($tag ? t('blog.tag', $L, $tag) : t('blog.title', $L)) . ' | ' . t('name', $L),
    'desc' => t('blog.desc', $L),
    'url' => $tag ? $langUrl($L) . ($L === 'fa' ? '?' : '&') . 'tag=' . rawurlencode($tag) : $langUrl($L),
    'alternates' => $tag ? [] : $alts,
    'active' => 'blog',
    'head' => $fallback ? '<meta name="robots" content="noindex, follow">' : '',
]);
?>
<section class="wrap page-head">
  <span class="eyebrow"><?= h(t('blog.eyebrow', $L)) ?></span>
  <h1><?= h($tag ? t('blog.tag', $L, $tag) : t('blog.title', $L)) ?></h1>
  <p class="sub"><?= h(t('blog.sub', $L)) ?></p>
  <?php if ($tag): ?><a class="chip" href="/blog/<?= $L === 'fa' ? '' : '?lang=' . $L ?>"><?= h(t('blog.all', $L)) ?> ×</a><?php endif; ?>
  <?php if ($fallback): ?><p class="note"><?= h(t('blog.fallback', $L)) ?></p><?php endif; ?>
</section>
<section class="wrap">
  <?php if (!$posts): ?>
    <div class="card empty"><h2><?= h(t('blog.soon', $L)) ?></h2><p class="sub"><?= h(t('blog.soon.p', $L)) ?></p><a class="btn" href="<?= $L === 'fa' ? '/' : '/?lang=' . $L ?>"><?= h(t('blog.back', $L)) ?></a></div>
  <?php else: ?>
    <div class="posts">
      <?php foreach ($posts as $p): $pl = $p['lang'] ?? 'fa'; ?>
        <a class="card post-card" href="/blog/<?= h(rawurlencode($p['slug'])) ?>" lang="<?= h($pl) ?>" dir="<?= LANGS[$pl]['dir'] ?? 'rtl' ?>">
          <div class="pc-img"><?php if ($p['cover']): ?><img src="<?= h($p['cover']) ?>" alt="<?= h(($p['cover_alt'] ?? '') ?: $p['title']) ?>" loading="lazy" decoding="async" width="1200" height="630"><?php else: ?><span class="pc-ph"><?= h(mb_substr($p['title'], 0, 1)) ?></span><?php endif; ?></div>
          <div class="pc-body">
            <div class="meta"><time datetime="<?= date('c', (int)$p['published_at']) ?>"><?= h(local_date((int)$p['published_at'], $pl)) ?></time> · <?= h(t('min', $pl, local_num(reading_minutes($p['content']), $pl))) ?></div>
            <h2><?= h($p['title']) ?></h2>
            <p><?= h($p['excerpt']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php page_close($L);
