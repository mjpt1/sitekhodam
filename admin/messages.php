<?php
require '_boot.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string)($_POST['id'] ?? ''); $action = $_POST['action'] ?? '';
    store_update('messages', function ($all) use ($id, $action) {
        if ($action === 'delete') return array_values(array_filter($all, fn($m) => $m['id'] !== $id));
        foreach ($all as $i => $m) if ($m['id'] === $id) $all[$i]['read'] = $action === 'read';
        return $all;
    });
    redirect('messages.php');
}

$msgs = store_read('messages', []);
usort($msgs, fn($a, $b) => $b['at'] <=> $a['at']);
layout_top('پیام‌ها', 'msg');
?>
<div class="head-row"><h1>پیام‌ها</h1></div>
<p class="muted">پیام‌های فرم تماس سایت. هر پیام هم‌زمان به ایمیل شما هم فرستاده می‌شود.</p>
<?php if (!$msgs): ?>
  <div class="card empty"><h2>هنوز پیامی نرسیده</h2><p class="muted">وقتی کسی فرم «پیام بفرستید» را پر کند، اینجا نمایش داده می‌شود.</p></div>
<?php else: foreach ($msgs as $m): ?>
  <article class="card msg <?= empty($m['read']) ? 'unread' : '' ?>">
    <header>
      <div><b><?= h($m['name']) ?></b> <?php if (empty($m['read'])): ?><span class="tag ok">جدید</span><?php endif; ?>
        <div class="muted small"><?php if ($m['email']): ?><a href="mailto:<?= h($m['email']) ?>" dir="ltr"><?= h($m['email']) ?></a> · <?php endif; ?><?= $m['phone'] ? '<a href="tel:' . h($m['phone']) . '" dir="ltr">' . h($m['phone']) . '</a>' : '' ?><?= $m['type'] ? ' · ' . h($m['type']) : '' ?></div>
      </div>
      <time class="muted small"><?= h(fa_date((int)$m['at'])) ?> · <?= date('H:i', (int)$m['at']) ?></time>
    </header>
    <p class="msg-body"><?= nl2br(h($m['message'])) ?></p>
    <footer class="actions">
      <?php if ($m['email']): ?><a class="btn" href="mailto:<?= h($m['email']) ?>?subject=<?= rawurlencode('پاسخ به پیام شما در mahsen81.ir') ?>">پاسخ با ایمیل</a>
      <?php else: ?><a class="btn" href="tel:<?= h($m['phone']) ?>">تماس تلفنی</a><?php endif; ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($m['id']) ?>"><input type="hidden" name="action" value="<?= empty($m['read']) ? 'read' : 'unread' ?>"><button class="link"><?= empty($m['read']) ? 'علامت خوانده‌شده' : 'علامت خوانده‌نشده' ?></button></form>
      <form method="post" data-confirm="این پیام حذف شود؟"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($m['id']) ?>"><input type="hidden" name="action" value="delete"><button class="link danger">حذف</button></form>
    </footer>
  </article>
<?php endforeach; endif;
layout_bottom();
