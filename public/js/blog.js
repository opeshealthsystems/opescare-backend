/* OpesCare blog — progressive enhancement only (no third-party code).
 * Copy-link, Web Share API, and "Reply" buttons that point the comment form
 * at a parent comment. Every share link works without this file. */
(function () {
    'use strict';

    var copy = document.querySelector('[data-blog-copy]');
    if (copy && navigator.clipboard) {
        copy.hidden = false;
        copy.addEventListener('click', function () {
            var url = copy.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(function () {
                var label = copy.querySelector('[data-label]');
                var done = copy.getAttribute('data-done');
                if (label && done) { var old = label.textContent; label.textContent = done; setTimeout(function () { label.textContent = old; }, 2000); }
            });
        });
    }

    var native = document.querySelector('[data-blog-share]');
    if (native && navigator.share) {
        native.hidden = false;
        native.addEventListener('click', function () {
            navigator.share({ title: native.getAttribute('data-title'), url: native.getAttribute('data-url') }).catch(function () {});
        });
    }

    var parent = document.getElementById('bl-parent');
    var heading = document.getElementById('bl-form-title');
    document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
        btn.hidden = false;
        btn.addEventListener('click', function () {
            if (!parent) return;
            parent.value = btn.getAttribute('data-reply-to');
            if (heading) heading.textContent = btn.getAttribute('data-reply-title');
            var body = document.getElementById('bl-body');
            if (body) body.focus();
        });
    });
})();
