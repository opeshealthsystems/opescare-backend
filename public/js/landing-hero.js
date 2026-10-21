/*
 * Landing hero: three slides (Health ID, interoperability, master patient
 * index) that crossfade every 7 s with the title typed in, and the Health ID
 * card's living anatomy — the app's own art: the heart beats lub-dub at
 * 72 bpm, the current sheet (24 frames, 6x4) runs once per beat, and the
 * figure eases round front-to-back, then changes man → woman → boy → girl.
 * Everything is still when the visitor asks for reduced motion.
 */
(function () {
  'use strict';
  var stage = document.querySelector('[data-hx-stage]');
  if (!stage) return;

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var slides = [], figures = [];
  try { slides = JSON.parse(stage.getAttribute('data-hx-slides') || '[]'); } catch (e) {}
  try { figures = JSON.parse(stage.getAttribute('data-hx-figures') || '[]'); } catch (e) {}

  var vis = Array.prototype.slice.call(stage.querySelectorAll('[data-hx-vis]'));
  var title = document.querySelector('[data-hx-title]');
  var lead = document.querySelector('[data-hx-lead]');
  var kicker = document.querySelector('[data-hx-kicker]');

  // Narrow screens keep the old card footprint; scale the 440x390 visual to fit.
  function fit() {
    var r = stage.getBoundingClientRect();
    var s = Math.min(1, r.height / 390, r.width / 440);
    stage.style.setProperty('--hx-s', String(s > 0 ? s : 1));
  }
  // Keep the hero exactly as tall on every slide: reserve the tallest lead.
  function reserveLead() {
    if (!lead || !slides.length) return;
    var keep = lead.textContent, max = 0;
    lead.style.minHeight = '';
    slides.forEach(function (s) { lead.textContent = s.l; max = Math.max(max, lead.offsetHeight); });
    lead.textContent = keep;
    lead.style.minHeight = max + 'px';
    if (title) {
      var keepT = title.innerHTML, maxT = 0;
      title.style.minHeight = '';
      slides.forEach(function (s) { title.innerHTML = esc(s.a) + '<br><span>' + esc(s.b) + '</span>'; maxT = Math.max(maxT, title.offsetHeight); });
      title.innerHTML = keepT;
      title.style.minHeight = maxT + 'px';
    }
  }

  fit();
  reserveLead();
  window.addEventListener('resize', function () { fit(); reserveLead(); });

  function esc(t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  var typing = null;
  function type(a, b) {
    if (!title) return;
    clearInterval(typing);
    var part = 0, n = 0;
    function render(caret) {
      var html = part ? esc(a) + '<br><span>' + esc(b.slice(0, n)) + '</span>' : esc(a.slice(0, n));
      title.innerHTML = html + (caret ? '<span class="hx-caret" aria-hidden="true"></span>' : '');
    }
    typing = setInterval(function () {
      n++;
      if (n > (part ? b : a).length) {
        if (!part) { part = 1; n = 0; } else { clearInterval(typing); render(false); return; }
      }
      render(true);
    }, 50);
  }

  var current = 0;
  function show(i) {
    current = i;
    vis.forEach(function (v, k) {
      var on = k === i;
      v.classList.toggle('is-on', on);
      if (k > 0 || !on) v.setAttribute('aria-hidden', on ? 'false' : 'true');
    });
    var s = slides[i];
    if (!s) return;
    if (kicker) kicker.textContent = s.k;
    if (lead) lead.textContent = s.l;
    type(s.a, s.b);
  }

  if (!reduced && vis.length > 1 && slides.length === vis.length) {
    setInterval(function () { show((current + 1) % vis.length); }, 7000);
  }

  // ── Living anatomy ────────────────────────────────────────────────────
  var spin = stage.querySelector('[data-hx-spin]');
  var label = stage.querySelector('[data-hx-view]');
  if (!spin || reduced) return;

  var currents = Array.prototype.slice.call(spin.querySelectorAll('.hx3-current'));
  var frame = 0;
  setInterval(function () {
    frame = (frame + 1) % 24;
    var pos = (frame % 6) * 20 + '% ' + Math.floor(frame / 6) * (100 / 3) + '%';
    currents.forEach(function (c) { c.style.backgroundPosition = pos; });
  }, 833 / 24);

  var hearts = Array.prototype.slice.call(spin.querySelectorAll('.hx3-heart'));
  hearts.forEach(function (h) { h.style.animation = 'hx3lub .833s linear infinite'; });

  function setFigure(f) {
    spin.querySelectorAll('.hx3-face').forEach(function (face) {
      face.querySelector('.hx3-base').src = f.base;
      face.querySelector('.hx3-current').style.backgroundImage = "url('" + f.current + "')";
      var h = face.querySelector('.hx3-heart');
      h.src = f.heart;
      h.style.left = f.box.x * 100 + '%';
      h.style.top = f.box.y * 100 + '%';
      h.style.width = f.box.w * 100 + '%';
      h.style.height = f.box.h * 100 + '%';
    });
    if (label) label.textContent = f.label;
  }

  // Hold front, ease round to the back, hold, ease round to the front, change figure.
  var fig = 0;
  function cycle() {
    if (!spin.animate) return;
    var anim = spin.animate([
      { transform: 'rotateY(0deg)', offset: 0 },
      { transform: 'rotateY(0deg)', offset: 0.28, easing: 'cubic-bezier(.45,0,.2,1)' },
      { transform: 'rotateY(180deg)', offset: 0.52 },
      { transform: 'rotateY(180deg)', offset: 0.66, easing: 'cubic-bezier(.45,0,.2,1)' },
      { transform: 'rotateY(360deg)', offset: 0.9 },
      { transform: 'rotateY(360deg)', offset: 1 }
    ], { duration: 10000 });
    anim.onfinish = function () {
      if (figures.length > 1) {
        spin.classList.add('is-swapping');
        setTimeout(function () {
          fig = (fig + 1) % figures.length;
          setFigure(figures[fig]);
          spin.classList.remove('is-swapping');
          cycle();
        }, 600);
      } else {
        cycle();
      }
    };
  }
  figures.forEach(function (f) { var i = new Image(); i.src = f.base; });
  cycle();
})();
