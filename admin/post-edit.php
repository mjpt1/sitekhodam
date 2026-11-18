<?php
require '_boot.php';
require_login();

$posts = store_read('posts', []);
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$post = null;
foreach ($posts as $p) if ($p['id'] === $id) $post = $p;
$isNew = $post === null;
$post = $post ?? ['id' => '', 'title' => '', 'slug' => '', 'excerpt' => '', 'cover' => '', 'content' => '', 'status' => 'draft', 'lang' => 'fa', 'tags' => []];
$post += ['focus' => '', 'keywords' => [], 'cover_alt' => '', 'translation_of' => ''];
$splitList = fn($v) => array_values(array_filter(array_map('trim', preg_split('/[،,]/u', (string)$v))));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim((string)($_POST['title'] ?? ''));
    $slug = slugify(trim((string)($_POST['slug'] ?? '')) ?: $title);
    $content = sanitize_html((string)($_POST['content'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? '')) ?: excerpt_of($content);
    $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $lang = preg_match('/^(fa|en|ar|zh|ko|ru|es)$/', (string)($_POST['lang'] ?? '')) ? $_POST['lang'] : 'fa';
    $tags = $splitList($_POST['tags'] ?? '');
    $focus = mb_substr(trim((string)($_POST['focus'] ?? '')), 0, 80);
    $keywords = array_slice($splitList($_POST['keywords'] ?? ''), 0, 15);
    $coverAlt = mb_substr(trim((string)($_POST['cover_alt'] ?? '')), 0, 160);
    $transOf = (string)($_POST['translation_of'] ?? '');
    $ids = array_column($posts, 'id');
    if ($transOf === $post['id'] || !in_array($transOf, $ids, true)) $transOf = '';
    $cover = (string)($post['cover'] ?? '');
    if (!empty($_FILES['cover']['name'])) {
        $up = save_upload($_FILES['cover']);
        if ($up['ok']) $cover = $up['url']; else $error = $up['error'];
    }
    if (!empty($_POST['remove_cover'])) $cover = '';
    foreach ($posts as $p) if ($p['slug'] === $slug && $p['id'] !== $post['id']) $error = 'این نامک (آدرس) قبلاً برای مقالهٔ دیگری استفاده شده.';
    if (mb_strlen($title) < 3) $error = 'عنوان مقاله را بنویسید.';
    if (!$error) {
        $now = time();
        $post = array_merge($post, [
            'id' => $post['id'] ?: new_id(), 'title' => mb_substr($title, 0, 200), 'slug' => $slug, 'excerpt' => mb_substr($excerpt, 0, 300),
            'cover' => $cover, 'content' => $content, 'status' => $status, 'lang' => $lang, 'tags' => $tags,
            'focus' => $focus, 'keywords' => $keywords, 'cover_alt' => $coverAlt, 'translation_of' => $transOf,
            'created_at' => $post['created_at'] ?? $now, 'updated_at' => $now,
        ]);
        if ($status === 'published' && empty($post['published_at'])) $post['published_at'] = $now;
        store_update('posts', function ($all) use ($post) {
            foreach ($all as $i => $p) if ($p['id'] === $post['id']) { $all[$i] = $post; return $all; }
            $all[] = $post; return $all;
        });
        flash($status === 'published' ? 'مقاله منتشر شد.' : 'پیش‌نویس ذخیره شد.');
        redirect('post-edit.php?id=' . urlencode($post['id']));
    }
    $post = array_merge($post, ['title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 'content' => $content, 'status' => $status, 'lang' => $lang, 'tags' => $tags, 'focus' => $focus, 'keywords' => $keywords, 'cover_alt' => $coverAlt, 'translation_of' => $transOf]);
}

layout_top($isNew ? 'مقالهٔ جدید' : 'ویرایش مقاله', 'posts');
?>
<div class="head-row">
  <h1><?= $isNew ? 'مقالهٔ جدید' : 'ویرایش مقاله' ?></h1>
  <div class="row-gap">
    <?php if (!$isNew && $post['status'] === 'published'): ?><a class="btn" href="/blog/<?= h(rawurlencode($post['slug'])) ?>" target="_blank" rel="noopener">مشاهده در سایت ↗</a><?php endif; ?>
    <a class="btn" href="posts.php">بازگشت</a>
  </div>
</div>
<?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>

<form class="editor-layout" method="post" enctype="multipart/form-data" id="post-form">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= h($post['id']) ?>">
  <div class="card ed-main">
    <input class="title-input" id="title" name="title" placeholder="عنوان مقاله" value="<?= h($post['title']) ?>" required>
    <div class="toolbar" role="toolbar" aria-label="ابزار ویرایش">
      <button type="button" data-cmd="formatBlock" data-val="h2" title="تیتر">H2</button>
      <button type="button" data-cmd="formatBlock" data-val="h3" title="تیتر فرعی">H3</button>
      <button type="button" data-cmd="formatBlock" data-val="p" title="پاراگراف">¶</button>
      <span class="sep"></span>
      <button type="button" data-cmd="bold" title="پررنگ"><b>B</b></button>
      <button type="button" data-cmd="italic" title="کج"><i>I</i></button>
      <button type="button" data-cmd="underline" title="زیرخط"><u>U</u></button>
      <span class="sep"></span>
      <button type="button" data-cmd="insertUnorderedList" title="فهرست">• ―</button>
      <button type="button" data-cmd="insertOrderedList" title="فهرست شماره‌دار">1. ―</button>
      <button type="button" data-cmd="formatBlock" data-val="blockquote" title="نقل‌قول">❝</button>
      <button type="button" data-cmd="formatBlock" data-val="pre" title="کد">&lt;/&gt;</button>
      <span class="sep"></span>
      <button type="button" data-cmd="link" title="پیوند">🔗</button>
      <button type="button" data-cmd="image" title="تصویر">🖼</button>
      <button type="button" data-cmd="removeFormat" title="پاک کردن قالب">⌫</button>
      <input type="file" id="img-file" accept="image/*" hidden>
    </div>
    <div class="rich" id="rich" contenteditable="true" data-placeholder="متن مقاله را اینجا بنویسید…"><?= $post['content'] ?></div>
    <textarea name="content" id="content" hidden></textarea>
    <p class="muted small" id="wordcount"></p>
  </div>

  <aside class="ed-side">
    <div class="card">
      <h2>انتشار</h2>
      <label class="radio"><input type="radio" name="status" value="draft" <?= $post['status'] !== 'published' ? 'checked' : '' ?>> پیش‌نویس</label>
      <label class="radio"><input type="radio" name="status" value="published" <?= $post['status'] === 'published' ? 'checked' : '' ?>> منتشرشده</label>
      <button class="btn primary wide">ذخیره</button>
    </div>
    <div class="card">
      <h2>تصویر شاخص</h2>
      <?php if ($post['cover']): ?><img class="cover-prev" src="<?= h($post['cover']) ?>" alt=""><label class="check"><input type="checkbox" name="remove_cover" value="1"> حذف تصویر</label><?php endif; ?>
      <input type="file" id="cover" name="cover" accept="image/*">
      <label style="margin-top:10px">متن جایگزین تصویر (alt)<input id="cover_alt" name="cover_alt" maxlength="160" value="<?= h($post['cover_alt']) ?>" placeholder="توصیف تصویر؛ بهتر است کلمهٔ کلیدی را داشته باشد"></label>
      <p class="muted small">برای اشتراک در شبکه‌های اجتماعی هم استفاده می‌شود. اندازهٔ مناسب: ۱۲۰۰×۶۳۰.</p>
    </div>
    <div class="card">
      <h2>بررسی سئو <span class="seo-score" id="seo-score"></span></h2>
      <ul class="seo-check" id="seo-check"></ul>
    </div>
    <div class="card">
      <h2>سئو</h2>
      <label>کلمهٔ کلیدی اصلی<input id="focus" name="focus" maxlength="80" value="<?= h($post['focus']) ?>" placeholder="مثلاً: هوش مصنوعی با پایتون"></label>
      <label>کلمات کلیدی فرعی<input id="keywords" name="keywords" value="<?= h(implode('، ', $post['keywords'])) ?>" placeholder="با ویرگول جدا کنید"></label>
      <label>نامک (آدرس)<input id="slug" name="slug" value="<?= h($post['slug']) ?>" dir="auto" placeholder="از روی عنوان ساخته می‌شود"></label>
      <p class="muted small mono" dir="ltr">mahsen81.ir/blog/<span id="slug-prev"><?= h($post['slug']) ?></span></p>
      <label>توضیح کوتاه (برای گوگل)<textarea id="excerpt" name="excerpt" rows="3" maxlength="300" placeholder="اگر خالی بماند از ابتدای متن ساخته می‌شود"><?= h($post['excerpt']) ?></textarea></label>
      <p class="muted small"><span id="excerpt-n"><?= mb_strlen($post['excerpt']) ?></span> / ۱۶۰ حرف پیشنهادی</p>
      <label>برچسب‌ها<input id="tags" name="tags" value="<?= h(implode('، ', $post['tags'] ?? [])) ?>" placeholder="مثلاً: سئو، طراحی سایت"></label>
      <label>زبان مقاله
        <select id="lang" name="lang">
          <?php foreach (['fa' => 'فارسی', 'en' => 'English', 'ar' => 'العربية', 'zh' => '中文', 'ko' => '한국어', 'ru' => 'Русский', 'es' => 'Español'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $post['lang'] === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>ترجمهٔ کدام مقاله است؟
        <select id="translation_of" name="translation_of">
          <option value="">هیچ‌کدام (مقالهٔ اصلی)</option>
          <?php foreach ($posts as $op): if ($op['id'] === $post['id'] || !empty($op['translation_of'])) continue; ?>
            <option value="<?= h($op['id']) ?>" <?= ($post['translation_of'] ?? '') === $op['id'] ? 'selected' : '' ?>>[<?= h($op['lang'] ?? 'fa') ?>] <?= h($op['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <p class="muted small">اگر این مقاله ترجمهٔ مقالهٔ دیگری است، آن را انتخاب کنید. آن‌وقت دکمهٔ زبان در سایت مستقیم بین نسخه‌ها جابه‌جا می‌شود و گوگل هم آن‌ها را نسخه‌های یک مقاله می‌شناسد.</p>
    </div>
  </aside>
</form>
<?php layout_bottom('<script src="editor.js?v=2"></script>');
