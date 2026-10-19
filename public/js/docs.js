/*
 * OpesCare developer docs — shell behaviour.
 * Loaded as an external, nonce-carrying script (script-src is nonce-only;
 * no inline handlers). Everything here is progressive: the docs read fine
 * with JavaScript off.
 */
(function () {
  'use strict';

  var body = document.body;

  function store(key, value) {
    try {
      if (value === undefined) return window.localStorage.getItem(key);
      window.localStorage.setItem(key, value);
    } catch (e) { return null; }
    return null;
  }

  /* ---- Mobile sidebar ---------------------------------------------- */
  var toggle = document.getElementById('docsMenuToggle');
  var sidebar = document.getElementById('docsSidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      var open = sidebar.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* ---- Language tabs on code blocks -------------------------------- */
  var LANG_KEY = 'docs_lang';
  function activateLang(lang) {
    document.querySelectorAll('.docs-code-block').forEach(function (block) {
      if (!block.querySelector('.docs-code-tab[data-lang="' + lang + '"]')) return;
      block.querySelectorAll('.docs-code-tab').forEach(function (t) { t.classList.toggle('active', t.dataset.lang === lang); });
      block.querySelectorAll('.docs-code-pane').forEach(function (p) { p.classList.toggle('active', p.dataset.lang === lang); });
    });
    store(LANG_KEY, lang);
  }
  document.querySelectorAll('.docs-code-block').forEach(function (block) {
    if (!block.querySelector('.docs-code-tab.active')) {
      var first = block.querySelector('.docs-code-tab');
      if (first) {
        first.classList.add('active');
        var pane = block.querySelector('.docs-code-pane[data-lang="' + first.dataset.lang + '"]');
        if (pane) pane.classList.add('active');
      }
    }
  });
  var saved = store(LANG_KEY);
  if (saved && document.querySelector('.docs-code-tab[data-lang="' + saved + '"]')) activateLang(saved);

  document.addEventListener('click', function (e) {
    var tab = e.target.closest('.docs-code-tab');
    if (!tab || !tab.dataset.lang) return;
    var lang = tab.dataset.lang;
    var block = tab.closest('.docs-code-block');
    if (['json', 'html', 'text', 'url'].indexOf(lang) !== -1) {
      block.querySelectorAll('.docs-code-tab').forEach(function (t) { t.classList.toggle('active', t === tab); });
      block.querySelectorAll('.docs-code-pane').forEach(function (p) { p.classList.toggle('active', p.dataset.lang === lang); });
    } else {
      activateLang(lang);
    }
  });

  /* ---- Copy buttons on every code sample --------------------------- */
  var copyLabel = body.getAttribute('data-copy-label') || 'Copy';
  var copiedLabel = body.getAttribute('data-copied-label') || 'Copied';
  document.querySelectorAll('#docsContent pre').forEach(function (pre) {
    if (pre.closest('.dg')) return;
    var wrap = document.createElement('div');
    wrap.className = 'dx-pre-wrap';
    pre.parentNode.insertBefore(wrap, pre);
    wrap.appendChild(pre);
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'dx-copy';
    btn.textContent = copyLabel;
    btn.addEventListener('click', function () {
      var text = pre.innerText;
      var done = function () {
        btn.textContent = copiedLabel;
        btn.classList.add('is-done');
        setTimeout(function () { btn.textContent = copyLabel; btn.classList.remove('is-done'); }, 1600);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () {});
      } else {
        var ta = document.createElement('textarea');
        ta.value = text; ta.setAttribute('readonly', '');
        ta.className = 'dx-offscreen';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); done(); } catch (err) { /* ignore */ }
        document.body.removeChild(ta);
      }
    });
    wrap.appendChild(btn);
  });

  /* ---- "On this page" table of contents + scroll spy --------------- */
  var toc = document.getElementById('docsToc');
  var content = document.getElementById('docsContent');
  if (toc && content) {
    var heads = content.querySelectorAll('h2, h3');
    var links = [];
    heads.forEach(function (h) {
      if (h.closest('.dg')) return;
      if (!h.id) {
        var base = (h.textContent || '').toLowerCase().trim().replace(/[^a-z0-9À-ſ]+/g, '-').replace(/^-|-$/g, '') || 'section';
        var id = base, i = 2;
        while (document.getElementById(id)) { id = base + '-' + i++; }
        h.id = id;
      }
      var a = document.createElement('a');
      a.href = '#' + h.id;
      a.textContent = h.textContent;
      a.className = 'dx-toc-link' + (h.tagName === 'H3' ? ' is-sub' : '');
      toc.appendChild(a);
      links.push({ h: h, a: a });
    });
    if (!links.length) {
      var t = document.querySelector('.dx-toc');
      if (t) t.classList.add('is-empty');
    }
    if ('IntersectionObserver' in window && links.length) {
      var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          links.forEach(function (l) { l.a.classList.toggle('is-active', l.h === en.target); });
        });
      }, { rootMargin: '-80px 0px -70% 0px' });
      links.forEach(function (l) { spy.observe(l.h); });
    }
  }

  /* Diagrams: public/js/blog-diagrams.js (shared with the blog). */

  if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
})();
