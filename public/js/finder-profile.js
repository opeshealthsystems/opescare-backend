/*
 * Public facility profile: Share (Web Share API, copy-link fallback),
 * Copy address, and hero "Directions" scrolling to the in-page map.
 * No inline handlers (CSP nonce-only).
 */
(function () {
  'use strict';

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy') ? resolve() : reject(new Error('copy')); }
      catch (e) { reject(e); }
      finally { document.body.removeChild(ta); }
    });
  }

  function flash(btn, msg) {
    var label = btn.querySelector('[data-fpp-label]');
    if (!label) return;
    var original = label.getAttribute('data-original') || label.textContent;
    label.setAttribute('data-original', original);
    label.textContent = msg;
    btn.setAttribute('aria-live', 'polite');
    window.setTimeout(function () { label.textContent = original; }, 2200);
  }

  function copyWithFeedback(btn, text) {
    copyText(text).then(
      function () { flash(btn, btn.getAttribute('data-done')); },
      function () { flash(btn, btn.getAttribute('data-fail')); }
    );
  }

  document.querySelectorAll('[data-fpp-share]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-url') || window.location.href;
      var title = btn.getAttribute('data-title') || document.title;
      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () { /* dismissed */ });
        return;
      }
      copyWithFeedback(btn, url);
    });
  });

  document.querySelectorAll('[data-fpp-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      copyWithFeedback(btn, btn.getAttribute('data-fpp-copy'));
    });
  });

  // Hero "Directions": finder-map.js draws the route; bring the map into view.
  document.querySelectorAll('[data-fpp-to-map]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var map = document.getElementById('fpp-map');
      if (map) map.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
})();
