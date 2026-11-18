/* Shop pages: day/night switch, product gallery and the card-to-card order box.
   Settings come from window.SHOP, set in the page right before this script. */
(function () {
  'use strict';
  var root = document.documentElement;
  var faDigits = function (s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  var enDigits = function (s) { return String(s).replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); }); };
  var toman = function (n) { return faDigits(n.toLocaleString('en-US')).replace(/,/g, '٬') + ' تومان'; };
  function legacyCopy(text) {
    var t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0';
    document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (e) {} t.remove();
  }
  // clipboard API when allowed, otherwise the old select-and-copy way; always resolves
  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text).catch(function () { legacyCopy(text); });
    legacyCopy(text); return Promise.resolve();
  }

  /* day/night: same switch as the rest of the site */
  var orb = document.getElementById('orb');
  if (orb) orb.addEventListener('click', function () {
    var next = root.dataset.theme === 'day' ? 'night' : 'day';
    var apply = function () { root.dataset.theme = next; try { localStorage.setItem('mj-theme', next); } catch (e) {} };
    if (!document.startViewTransition || matchMedia('(prefers-reduced-motion: reduce)').matches || document.hidden) { apply(); return; }
    setTimeout(function () { if (root.dataset.theme !== next) apply(); }, 1200);
    var r = orb.getBoundingClientRect(), x = r.left + r.width / 2, y = r.top + r.height / 2, end = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
    document.startViewTransition(apply).ready.then(function () {
      root.animate({ clipPath: ['circle(0px at ' + x + 'px ' + y + 'px)', 'circle(' + end + 'px at ' + x + 'px ' + y + 'px)'] }, { duration: 750, easing: 'cubic-bezier(.65,0,.25,1)', pseudoElement: '::view-transition-new(root)' });
    });
  });

  /* gallery */
  var main = document.getElementById('main-img');
  document.querySelectorAll('.thumb').forEach(function (b) {
    b.addEventListener('click', function () {
      main.src = b.dataset.src; main.alt = b.dataset.alt;
      document.querySelectorAll('.thumb').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
    });
  });

  /* order box */
  var box = document.getElementById('order-box');
  if (!box || !window.SHOP) return;
  var S = window.SHOP, $ = function (id) { return document.getElementById(id); };
  var status = $('order-status');
  function say(msg, ok) { status.textContent = msg; status.className = 'form-status' + (ok ? ' ok' : ''); }

  var card = (S.card || '').replace(/\D/g, '');
  $('card-number').textContent = card ? faDigits(card.replace(/(\d{4})(?=\d)/g, '$1 ')) : 'به‌زودی اعلام می‌شود';
  $('card-owner').textContent = S.owner || '';
  $('copy-card').hidden = !card;
  $('copy-card').addEventListener('click', function () { copy(card).then(function () { $('copy-card').textContent = 'کپی شد ✓'; setTimeout(function () { $('copy-card').textContent = 'کپی شماره کارت'; }, 2000); }); });

  var qty = $('qty');
  function color() { var c = box.querySelector('input[name="color"]:checked'); return c ? c.value : ''; }
  function total() { return S.price * (+qty.value || 1); }
  function refresh() { $('total').textContent = toman(total()); }
  qty.addEventListener('change', refresh); refresh();

  function orderText() {
    var name = $('f-name').value.trim(), phone = enDigits($('f-phone').value).replace(/[\s-]/g, ''), addr = $('f-address').value.trim();
    if (name.length < 2) { say('نام را کامل بنویسید.'); $('f-name').focus(); return null; }
    if (!/^(\+98|0098|0)?9\d{9}$/.test(phone)) { say('شمارهٔ موبایل درست نیست.'); $('f-phone').focus(); return null; }
    if (addr.length < 10) { say('آدرس کامل پستی را بنویسید.'); $('f-address').focus(); return null; }
    say('');
    return 'سفارش ربات دینو پاد\n' +
      'رنگ: ' + color() + '\nتعداد: ' + faDigits(qty.value) + '\nمبلغ واریزی: ' + toman(total()) + '\n\n' +
      'نام: ' + name + '\nموبایل: ' + phone + '\nآدرس: ' + addr + '\n\n(رسید واریز پیوست است)';
  }

  var wa = $('send-wa'), tg = $('send-tg');
  wa.hidden = !S.whatsapp; tg.hidden = !S.telegram;
  wa.addEventListener('click', function (e) {
    var t = orderText(); if (!t) { e.preventDefault(); return; }
    wa.href = 'https://wa.me/' + S.whatsapp + '?text=' + encodeURIComponent(t);
  });
  tg.addEventListener('click', function (e) {
    var t = orderText(); if (!t) { e.preventDefault(); return; }
    // Telegram links cannot carry text to a person: copy it, then open the chat
    copy(t); tg.href = 'https://t.me/' + S.telegram;
    say('متن سفارش کپی شد؛ در تلگرام بچسبانید و عکس رسید را هم بفرستید.', true);
  });
  $('copy-order').addEventListener('click', function () {
    var t = orderText(); if (!t) return;
    copy(t).then(function () { say('متن سفارش کپی شد. آن را همراه عکس رسید برای ما بفرستید.', true); });
  });
})();
