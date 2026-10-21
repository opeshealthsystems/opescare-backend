/* /download — copy the SHA-256 fingerprint. External file: CSP is nonce-only. */
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('[data-dl-copy]');
        if (!btn || !navigator.clipboard || !navigator.clipboard.writeText) { return; }
        var target = document.getElementById(btn.getAttribute('data-dl-copy'));
        var label = btn.querySelector('span');
        if (!target || !label) { return; }
        var original = label.textContent;
        btn.hidden = false;
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(target.textContent.trim()).then(function () {
                label.textContent = btn.getAttribute('data-copied') || original;
                setTimeout(function () { label.textContent = original; }, 2000);
            }, function () {});
        });
    });
})();
