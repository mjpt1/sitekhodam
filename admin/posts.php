<?php
require '_boot.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string)($_POST['id'] ?? '');
    if (($_POST['action'] ?? '') === 'delete') {
        store_update('posts', fn($p) => array_values(array_filter($p, fn($x) => $x['id'] !== $id)));
        flash('مقاله حذف شد.');
    }
    redirect('posts.php');
}

$posts = posts_all();
layout_top('مقالات', 'posts');
?>
<div class="head-row">
  <h1>مقالات</h1>
  <a class="btn primary" href="post-edit.php">+ مقالهٔ جدید</a>
</div>
<?php if (!$posts): ?>
  <div class="card empty">
    <h2>هنوز مقاله‌ای ننوشته‌اید</h2>
    <p class="muted">مقاله‌ها در صفحهٔ «وبلاگ» سایت و بخش «مقاله‌های تازه» صفحهٔ اصلی نمایش داده می‌شوند و به نقشهٔ سایت برای گوگل اضافه می‌شوند.</p>
    <a class="btn primary" href="post-edit.php">نوشتن اولین مقاله</a>
  </div>
<?php else: ?>
  <div class="card table-card">
    <table class="tbl">
      <thead><tr><th>عنوان</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($posts as $p): $pub = ($p['status'] ?? '') === 'published'; ?>
        <tr>
          <td><a href="post-edit.php?id=<?= h($p['id']) ?>" class="t-title"><?= h($p['title']) ?></a><small class="muted mono">/blog/<?= h($p['slug']) ?></small></td>
          <td><span class="tag <?= $pub ? 'ok' : '' ?>"><?= $pub ? 'منتشرشده' : 'پیش‌نویس' ?></span></td>
          <td class="muted"><?= h(fa_date((int)($p['published_at'] ?? $p['created_at']))) ?></td>
          <td class="actions">
            <?php if ($pub): ?><a href="/blog/<?= h(rawurlencode($p['slug'])) ?>" target="_blank" rel="noopener">مشاهده ↗</a><?php endif; ?>
            <a href="post-edit.php?id=<?= h($p['id']) ?>">ویرایش</a>
            <form method="post" data-confirm="«<?= h($p['title']) ?>» حذف شود؟ این کار برگشت‌پذیر نیست.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="action" value="delete">
              <button class="link danger">حذف</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif;
layout_bottom();
