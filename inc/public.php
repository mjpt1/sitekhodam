<?php
/* Shared header/footer for the PHP-rendered public pages (blog list, article), in the visitor's language. */
declare(strict_types=1);
require_once __DIR__ . '/i18n.php';

/* options: lang, title, desc, url, image, ogType, active, head, alternates (lang => absolute URL of the same page in that language) */
function page_open(array $o): void {
    $L = $o['lang'] ?? 'fa';
    $dir = LANGS[$L]['dir'];
    $title = $o['title']; $desc = $o['desc'] ?? ''; $url = $o['url']; $img = $o['image'] ?? SITE_URL . '/og.jpg';
    $alts = $o['alternates'] ?? [];
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    ?><!doctype html>
<html lang="<?= h($L) ?>" dir="<?= $dir ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<meta name="description" content="<?= h($desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= h($url) ?>">
<?php foreach ($alts as $al => $au): ?><link rel="alternate" hreflang="<?= h($al) ?>" href="<?= h($au) ?>">
<?php endforeach; if (isset($alts['fa'])): ?><link rel="alternate" hreflang="x-default" href="<?= h($alts['fa']) ?>">
<?php endif; ?>
<meta name="theme-color" content="#0a0720">
<link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="icon" href="/favicon.ico" sizes="any"><link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta property="og:type" content="<?= h($o['ogType'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= h(t('name', $L)) ?>">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($desc) ?>">
<meta property="og:url" content="<?= h($url) ?>">
<meta property="og:image" content="<?= h($img) ?>">
<meta property="og:locale" content="<?= h(LANGS[$L]['og']) ?>">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:site" content="@mjpt1">
<meta name="twitter:title" content="<?= h($title) ?>"><meta name="twitter:description" content="<?= h($desc) ?>"><meta name="twitter:image" content="<?= h($img) ?>">
<link rel="preload" as="font" type="font/woff2" href="/fonts/Vazirmatn-Black.woff2" crossorigin>
<link rel="stylesheet" href="/assets/chrome.css?v=1">
<link rel="stylesheet" href="/assets/blog.css?v=3">
<link rel="alternate" type="application/rss+xml" title="<?= h(SITE_NAME) ?>" href="<?= SITE_URL ?>/feed.xml">
<script>try{var t=localStorage.getItem('mj-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
<?= $o['head'] ?? '' ?>
</head>
<body>
<div class="orb-wrap">
  <button class="orb" id="orb" type="button" aria-label="<?= h(t('theme', $L)) ?>">
    <span class="orb-stars" aria-hidden="true"><i></i><i></i><i></i></span>
    <span class="orb-sun" aria-hidden="true"></span>
    <span class="orb-moon" aria-hidden="true"></span>
  </button>
</div>
<nav class="site-nav solid" id="nav">
  <div class="wrap">
    <a class="logo" href="<?= $L === 'fa' ? '/' : '/?lang=' . $L ?>"><span class="logo-badge">MJ</span><span class="logo-name"><?= h(t('name', $L)) ?></span></a>
    <div class="nav-right">
      <div class="nav-links">
        <?php $home = $L === 'fa' ? '/' : '/?lang=' . $L; ?>
        <a href="<?= $home ?>#skills"><?= h(t('nav.skills', $L)) ?></a>
        <a href="<?= $home ?>#code"><?= h(t('nav.code', $L)) ?></a>
        <a href="<?= $home ?>#software"><?= h(t('nav.software', $L)) ?></a>
        <a href="<?= $home ?>#sites"><?= h(t('nav.sites', $L)) ?></a>
        <a href="/blog/<?= $L === 'fa' ? '' : '?lang=' . $L ?>" class="<?= ($o['active'] ?? '') === 'blog' ? 'on' : '' ?>"><?= h(t('nav.blog', $L)) ?></a>
        <a href="/shop/" class="<?= ($o['active'] ?? '') === 'shop' ? 'on' : '' ?>"><?= h(t('nav.shop', $L)) ?></a>
        <a href="<?= $home ?>#contact"><?= h(t('nav.contact', $L)) ?></a>
      </div>
      <div class="lang">
        <button class="lang-btn" id="lang-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="lang-menu" aria-label="<?= h(t('lang', $L)) ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
          <span><?= h(LANGS[$L]['name']) ?></span>
        </button>
        <ul class="lang-menu" id="lang-menu" hidden>
          <?php foreach (LANGS as $lc => $info):
            // same page in that language when it exists (e.g. a translated article), otherwise this page with ?lang=
            $target = isset($alts[$lc]) ? parse_url($alts[$lc], PHP_URL_PATH) . (parse_url($alts[$lc], PHP_URL_QUERY) ? '?' . parse_url($alts[$lc], PHP_URL_QUERY) : '') : ($o['langFallback'][$lc] ?? $path . '?lang=' . $lc); ?>
            <li><a href="<?= h($target) ?>" data-lang="<?= $lc ?>" lang="<?= $lc ?>" hreflang="<?= $lc ?>" aria-current="<?= $lc === $L ? 'true' : 'false' ?>"><?= h($info['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</nav>
<main>
<?php
}

function page_close(string $L = 'fa'): void {
    ?></main>
<footer class="foot"><div class="wrap foot-in"><span><?= h(t('copy', $L)) ?> <?= h(t('name', $L)) ?></span><span><a href="https://github.com/mjpt1">GitHub</a> · <a href="https://x.com/mjpt1">X</a> · <a href="/feed.xml">RSS</a></span></div></footer>
<script>
(function () {
  var root = document.documentElement;
  // day/night: same switch as the home page, the new theme spreads from the orb
  var orb = document.getElementById('orb');
  orb.addEventListener('click', function () {
    var next = root.dataset.theme === 'day' ? 'night' : 'day';
    var apply = function () { root.dataset.theme = next; try { localStorage.setItem('mj-theme', next); } catch (e) {} };
    if (!document.startViewTransition || matchMedia('(prefers-reduced-motion: reduce)').matches || document.hidden) { apply(); return; }
    setTimeout(function () { if (root.dataset.theme !== next) apply(); }, 1200);
    var r = orb.getBoundingClientRect(), x = r.left + r.width / 2, y = r.top + r.height / 2, end = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
    document.startViewTransition(apply).ready.then(function () {
      root.animate({ clipPath: ['circle(0px at ' + x + 'px ' + y + 'px)', 'circle(' + end + 'px at ' + x + 'px ' + y + 'px)'] }, { duration: 750, easing: 'cubic-bezier(.65,0,.25,1)', pseudoElement: '::view-transition-new(root)' });
    });
  });
  // language menu: remember the choice for the whole site (home page reads localStorage, PHP pages read the cookie)
  var menu = document.getElementById('lang-menu'), lb = document.getElementById('lang-btn');
  lb.addEventListener('click', function (e) { e.stopPropagation(); var open = menu.hidden; menu.hidden = !open; lb.setAttribute('aria-expanded', String(open)); });
  document.addEventListener('click', function (e) { if (!menu.contains(e.target)) { menu.hidden = true; lb.setAttribute('aria-expanded', 'false'); } });
  menu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () {
    try { localStorage.setItem('mj-lang', a.dataset.lang); } catch (e) {}
    document.cookie = 'mj_lang=' + a.dataset.lang + ';path=/;max-age=31536000;samesite=lax';
  }); });
  try { navigator.sendBeacon('/track.php', JSON.stringify({ p: location.pathname, r: document.referrer, l: root.lang })); } catch (e) {}
})();
</script>
</body>
</html><?php
}
