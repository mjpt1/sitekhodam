<?php
require '_boot.php';
require_login();

$items = work_all();
$editId = (string)($_GET['edit'] ?? $_POST['id'] ?? '');
$item = null;
foreach ($items as $w) if ($w['id'] === $editId) $item = $w;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    $id = (string)($_POST['id'] ?? '');
    if ($action === 'delete') {
        store_update('work', fn($a) => array_values(array_filter($a, fn($x) => $x['id'] !== $id)));
        flash('نمونه‌کار حذف شد.'); redirect('work.php');
    }
    $title = trim((string)($_POST['title'] ?? ''));
    $url = trim((string)($_POST['url'] ?? ''));
    if ($url !== '' && !preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) $error = 'آدرس پروژه درست نیست.';
    $image = (string)($item['image'] ?? '');
    if (!empty($_FILES['image']['name'])) {
        $up = save_upload($_FILES['image'], 1200);
        if ($up['ok']) $image = $up['url']; else $error = $up['error'];
    }
    if (mb_strlen($title) < 2) $error = 'نام پروژه را بنویسید.';
    if (!$error) {
        $rec = [
            'id' => $id ?: new_id(), 'title' => mb_substr($title, 0, 120), 'desc' => mb_substr(trim((string)($_POST['desc'] ?? '')), 0, 400),
            'tag' => mb_substr(trim((string)($_POST['tag'] ?? '')), 0, 60), 'cat' => ($_POST['cat'] ?? '') === 'site' ? 'site' : 'software',
            'url' => $url, 'image' => $image, 'order' => (int)($_POST['order'] ?? 0), 'visible' => !empty($_POST['visible']),
            'created_at' => $item['created_at'] ?? time(), 'updated_at' => time(),
        ];
        store_update('work', function ($all) use ($rec) {
            foreach ($all as $i => $w) if ($w['id'] === $rec['id']) { $all[$i] = $rec; return $all; }
            $all[] = $rec; return $all;
        });
        flash('نمونه‌کار ذخیره شد.'); redirect('work.php');
    }
    $item = array_merge($item ?? [], $_POST, ['image' => $image]);
}
$item = $item ?? ['id' => '', 'title' => '', 'desc' => '', 'tag' => '', 'cat' => 'software', 'url' => '', 'image' => '', 'order' => 0, 'visible' => true];

layout_top('نمونه‌کارها', 'work');
?>
<div class="head-row"><h1>نمونه‌کارها</h1><?php if ($item['id']): ?><a class="btn" href="work.php">+ نمونه‌کار جدید</a><?php endif; ?></div>
<p class="muted">نمونه‌کارهایی که اینجا اضافه می‌کنید، کنار نمونه‌کارهای فعلی سایت نشان داده می‌شوند: «نرم‌افزار» در بخش نرم‌افزارها و «سایت» در بخش طراحی سایت.</p>
<?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>

<div class="grid-work">
  <form class="card" method="post" enctype="multipart/form-data">
    <h2><?= $item['id'] ? 'ویرایش نمونه‌کار' : 'نمونه‌کار جدید' ?></h2>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= h($item['id']) ?>">
    <label>نام پروژه<input id="w-title" name="title" required value="<?= h($item['title']) ?>"></label>
    <label>توضیح کوتاه<textarea id="w-desc" name="desc" rows="3" maxlength="400"><?= h($item['desc']) ?></textarea></label>
    <div class="two">
      <label>بخش
        <select id="w-cat" name="cat">
          <option value="software" <?= $item['cat'] === 'software' ? 'selected' : '' ?>>نرم‌افزار</option>
          <option value="site" <?= $item['cat'] === 'site' ? 'selected' : '' ?>>طراحی سایت</option>
        </select>
      </label>
      <label>برچسب<input id="w-tag" name="tag" value="<?= h($item['tag']) ?>" placeholder="مثلاً: فروشگاهی"></label>
    </div>
    <label>آدرس (اختیاری)<input id="w-url" name="url" dir="ltr" value="<?= h($item['url']) ?>" placeholder="https://example.com"></label>
    <label>تصویر (اسکرین‌شات)
      <?php if ($item['image']): ?><img class="cover-prev" src="<?= h($item['image']) ?>" alt=""><?php endif; ?>
      <input type="file" id="w-image" name="image" accept="image/*">
    </label>
    <div class="two">
      <label>ترتیب نمایش<input id="w-order" name="order" type="number" value="<?= (int)$item['order'] ?>"></label>
      <label class="check pad"><input type="checkbox" name="visible" value="1" <?= !empty($item['visible']) ? 'checked' : '' ?>> در سایت نمایش داده شود</label>
    </div>
    <button class="btn primary">ذخیره</button>
  </form>

  <div class="card table-card">
    <?php if (!$items): ?>
      <div class="empty"><h2>هنوز نمونه‌کاری اضافه نکرده‌اید</h2><p class="muted">با فرم کنار، اولین نمونه‌کار را اضافه کنید.</p></div>
    <?php else: ?>
      <ul class="work-list">
        <?php foreach ($items as $w): ?>
          <li>
            <span class="thumb"><?php if ($w['image']): ?><img src="<?= h($w['image']) ?>" alt=""><?php endif; ?></span>
            <span class="w-meta"><b><?= h($w['title']) ?></b><small class="muted"><?= $w['cat'] === 'site' ? 'سایت' : 'نرم‌افزار' ?><?= $w['tag'] ? ' · ' . h($w['tag']) : '' ?><?= empty($w['visible']) ? ' · <em>مخفی</em>' : '' ?></small></span>
            <span class="actions">
              <a href="?edit=<?= h($w['id']) ?>">ویرایش</a>
              <form method="post" data-confirm="«<?= h($w['title']) ?>» حذف شود؟"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($w['id']) ?>"><input type="hidden" name="action" value="delete"><button class="link danger">حذف</button></form>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
<?php layout_bottom();
