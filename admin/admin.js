/* Shared admin behaviour */
(function () {
  'use strict';
  // forms that delete something ask first
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });
  // flash messages fade away on their own
  var fl = document.querySelector('.flash:not(.err)');
  if (fl) setTimeout(function () { fl.style.opacity = '0'; setTimeout(function () { fl.remove(); }, 400); }, 4000);
  // image inputs show a preview
  document.querySelectorAll('input[type=file][accept^="image"]:not([hidden])').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var f = inp.files[0]; if (!f) return;
      var img = inp.parentNode.querySelector('img.cover-prev');
      if (!img) { img = document.createElement('img'); img.className = 'cover-prev'; inp.parentNode.insertBefore(img, inp); }
      img.src = URL.createObjectURL(f);
    });
  });
})();
