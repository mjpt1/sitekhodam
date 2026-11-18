/* Article editor: a small rich-text editor (the server cleans the HTML again on save) */
(function () {
  'use strict';
  var rich = document.getElementById('rich'), form = document.getElementById('post-form'), out = document.getElementById('content');
  var csrf = form.querySelector('[name=csrf]').value, fileIn = document.getElementById('img-file');
  document.execCommand('defaultParagraphSeparator', false, 'p');

  function cmd(name, val) { rich.focus(); document.execCommand(name, false, val || null); }
  document.querySelectorAll('.toolbar [data-cmd]').forEach(function (b) {
    b.addEventListener('mousedown', function (e) { e.preventDefault(); });          // keep the selection in the editor
    b.addEventListener('click', function () {
      var c = b.dataset.cmd;
      if (c === 'link') {
        var url = prompt('آدرس پیوند (با https:// شروع شود):', 'https://');
        if (url && /^(https?:\/\/|\/|mailto:)/i.test(url)) cmd('createLink', url);
      } else if (c === 'image') {
        fileIn.click();
      } else if (c === 'formatBlock') {
        cmd('formatBlock', '<' + b.dataset.val + '>');
      } else cmd(c);
    });
  });

  // keep where the caret was, so an uploaded image lands there
  var saved = null;
  rich.addEventListener('keyup', save); rich.addEventListener('mouseup', save);
  function save() { var s = getSelection(); if (s.rangeCount) saved = s.getRangeAt(0).cloneRange(); }
  fileIn.addEventListener('change', function () {
    var f = fileIn.files[0]; if (!f) return;
    var fd = new FormData(); fd.append('image', f); fd.append('csrf', csrf);
    var note = document.getElementById('wordcount'); note.textContent = 'در حال آپلود تصویر…';
    fetch('upload.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'upload');
      rich.focus();
      if (saved) { var s = getSelection(); s.removeAllRanges(); s.addRange(saved); }
      var alt = (prompt('توضیح کوتاه تصویر (برای سئو و نابینایان):', '') || '').replace(/"/g, '');
      cmd('insertHTML', '<figure><img src="' + res.url + '" alt="' + alt + '" width="' + res.width + '" height="' + res.height + '"></figure><p><br></p>');
      count();
    }).catch(function (e) { alert('آپلود تصویر انجام نشد: ' + e.message); count(); })
      .then(function () { fileIn.value = ''; });
  });

  // paste as clean text-ish HTML (drop styles from Word / websites)
  rich.addEventListener('paste', function (e) {
    var html = e.clipboardData.getData('text/html'), text = e.clipboardData.getData('text/plain');
    e.preventDefault();
    if (html) {
      var tmp = document.createElement('div'); tmp.innerHTML = html;
      tmp.querySelectorAll('*').forEach(function (n) { n.removeAttribute('style'); n.removeAttribute('class'); n.removeAttribute('id'); });
      tmp.querySelectorAll('script,style,meta,link').forEach(function (n) { n.remove(); });
      cmd('insertHTML', tmp.innerHTML);
    } else cmd('insertText', text);
  });

  function count() {
    var words = (rich.innerText.trim().match(/\S+/g) || []).length;
    document.getElementById('wordcount').textContent = words + ' کلمه · حدود ' + Math.max(1, Math.ceil(words / 200)) + ' دقیقه مطالعه';
  }
  rich.addEventListener('input', count); count();

  // slug preview + excerpt counter
  var slug = document.getElementById('slug'), title = document.getElementById('title'), sp = document.getElementById('slug-prev');
  function slugify(s) { return s.trim().toLowerCase().replace(/[‌‍]/g, '-').replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '').slice(0, 80); }
  function slugPrev() { sp.textContent = slug.value.trim() ? slugify(slug.value) : slugify(title.value); }
  slug.addEventListener('input', slugPrev); title.addEventListener('input', slugPrev); slugPrev();
  var ex = document.getElementById('excerpt'), exn = document.getElementById('excerpt-n');
  ex.addEventListener('input', function () { exn.textContent = ex.value.length; });

  // live SEO check: the same rules an SEO plugin would apply
  var $ = function (id) { return document.getElementById(id); };
  function norm(t) { return String(t || '').toLowerCase().replace(/[\u200c\u200d]/g, ' ').replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').replace(/\s+/g, ' ').trim(); }
  function seoCheck() {
    var fk = norm($('focus').value), title = $('title').value.trim(), desc = $('excerpt').value.trim();
    var text = norm(rich.innerText), words = (text.match(/\S+/g) || []).length;
    var firstP = rich.querySelector('p'), heads = Array.prototype.map.call(rich.querySelectorAll('h2,h3'), function (h) { return norm(h.textContent); });
    var links = rich.querySelectorAll('a[href]'), internal = 0, external = 0;
    links.forEach(function (a) { var h = a.getAttribute('href'); if (/^https?:\/\//i.test(h) && h.indexOf('mahsen81.ir') < 0) external++; else internal++; });
    var imgs = rich.querySelectorAll('img'), noAlt = Array.prototype.filter.call(imgs, function (i) { return !i.getAttribute('alt'); }).length;
    var count = fk ? text.split(fk).length - 1 : 0, density = words ? count * fk.split(' ').length / words * 100 : 0;
    var slugText = norm(($('slug').value || title).replace(/-/g, ' '));
    var hasCover = !!document.querySelector('.cover-prev') || ($('cover').files && $('cover').files.length);
    var rules = [
      [!!fk, 'کلمهٔ کلیدی اصلی تعیین شده است', 'کلمهٔ کلیدی اصلی را بنویسید'],
      [fk && norm(title).indexOf(fk) > -1, 'کلمهٔ کلیدی در عنوان هست', 'کلمهٔ کلیدی را در عنوان بیاورید'],
      [title.length >= 30 && title.length <= 60, 'طول عنوان مناسب است (' + title.length + ' حرف)', 'عنوان بهتر است ۳۰ تا ۶۰ حرف باشد (الان ' + title.length + ')'],
      [fk && slugText.indexOf(fk) > -1, 'کلمهٔ کلیدی در نامک (آدرس) هست', 'کلمهٔ کلیدی را در نامک بیاورید'],
      [desc.length >= 120 && desc.length <= 160, 'طول توضیح کوتاه مناسب است', 'توضیح کوتاه بهتر است ۱۲۰ تا ۱۶۰ حرف باشد (الان ' + desc.length + ')'],
      [fk && norm(desc).indexOf(fk) > -1, 'کلمهٔ کلیدی در توضیح کوتاه هست', 'کلمهٔ کلیدی را در توضیح کوتاه بیاورید'],
      [fk && firstP && norm(firstP.textContent).indexOf(fk) > -1, 'کلمهٔ کلیدی در پاراگراف اول هست', 'کلمهٔ کلیدی را در پاراگراف اول بیاورید'],
      [fk && heads.some(function (h) { return h.indexOf(fk) > -1; }), 'کلمهٔ کلیدی در یکی از تیترها هست', 'کلمهٔ کلیدی را در یکی از تیترهای H2 یا H3 بیاورید'],
      [density >= .5 && density <= 2.5, 'تکرار کلمهٔ کلیدی مناسب است (' + density.toFixed(1) + '٪)', 'تکرار کلمهٔ کلیدی بهتر است ۰٫۵ تا ۲٫۵ درصد باشد (الان ' + density.toFixed(1) + '٪، ' + count + ' بار)'],
      [words >= 600, 'طول مقاله مناسب است (' + words + ' کلمه)', 'مقاله بهتر است حداقل ۶۰۰ کلمه باشد (الان ' + words + ')'],
      [heads.length >= 2, 'مقاله با تیتر بخش‌بندی شده', 'متن را با چند تیتر H2 بخش‌بندی کنید'],
      [internal >= 1, 'لینک داخلی دارد (' + internal + ')', 'حداقل یک لینک به صفحه‌های دیگر سایت بگذارید'],
      [external >= 1, 'لینک به منبع معتبر بیرونی دارد (' + external + ')', 'یک لینک به منبع معتبر بیرونی بگذارید'],
      [hasCover, 'تصویر شاخص دارد', 'یک تصویر شاخص انتخاب کنید'],
      [!hasCover || !!$('cover_alt').value.trim(), 'متن جایگزین تصویر شاخص نوشته شده', 'متن جایگزین (alt) تصویر شاخص را بنویسید'],
      [noAlt === 0, 'همهٔ تصاویر متن جایگزین دارند', noAlt + ' تصویر داخل متن، متن جایگزین ندارد']
    ];
    var ok = rules.filter(function (r) { return r[0]; }).length, score = Math.round(ok / rules.length * 100);
    $('seo-check').innerHTML = rules.map(function (r) { return '<li class="' + (r[0] ? 'ok' : 'bad') + '">' + (r[0] ? r[1] : r[2]) + '</li>'; }).join('');
    var sc = $('seo-score'); sc.textContent = score + '٪'; sc.className = 'seo-score ' + (score >= 80 ? 'good' : score >= 50 ? 'mid' : 'low');
  }
  ['input', 'change'].forEach(function (ev) { form.addEventListener(ev, seoCheck); });
  rich.addEventListener('input', seoCheck); seoCheck();

  // warn before leaving with unsaved changes
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  form.addEventListener('submit', function () { dirty = false; out.value = rich.innerHTML; });
})();
