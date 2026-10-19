/*
 * Blog diagrams: scroll-synced step highlight, path-draw on enter, and flowing
 * dashes only while a diagram is on screen. Loaded only on posts that embed a
 * diagram. Everything here is enhancement: without JS (or with reduced motion)
 * the diagrams and their step lists are complete and static.
 */
(function () {
  'use strict';
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hasIO = 'IntersectionObserver' in window;

  document.querySelectorAll('[data-dg-steps]').forEach(function (list) {
    var fig = document.querySelector('[data-dg="' + list.getAttribute('data-dg-steps') + '"]');
    if (!fig) return;
    var parts = fig.querySelectorAll('svg [data-step]');
    var steps = list.querySelectorAll('.dg-step');
    function show(n) {
      parts.forEach(function (m) { m.classList.toggle('is-active', m.getAttribute('data-step') === n); });
      steps.forEach(function (s) { s.classList.toggle('is-active', s.getAttribute('data-step') === n); });
      fig.classList.toggle('has-active', !!n);
    }
    steps.forEach(function (s) {
      s.setAttribute('tabindex', '0');
      s.addEventListener('mouseenter', function () { show(s.getAttribute('data-step')); });
      s.addEventListener('focusin', function () { show(s.getAttribute('data-step')); });
    });
    parts.forEach(function (m) {
      m.addEventListener('mouseenter', function () { show(m.getAttribute('data-step')); });
    });
    if (hasIO) {
      // The step crossing the middle band of the viewport is the one being read.
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) show(en.target.getAttribute('data-step')); });
      }, { rootMargin: '-45% 0px -45% 0px' });
      steps.forEach(function (s) { io.observe(s); });
    }
  });

  if (reduceMotion || !hasIO) return;

  // Board diagrams draw themselves once, the first time they come into view.
  var boards = document.querySelectorAll('.dg-board');
  var draw = new IntersectionObserver(function (entries, obs) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      en.target.classList.add('is-drawn');
      obs.unobserve(en.target);
    });
  }, { threshold: 0.2 });
  boards.forEach(function (b) {
    // Arm only diagrams still below the fold, so nothing already visible blinks.
    if (b.getBoundingClientRect().top > window.innerHeight) b.classList.add('is-armed');
    draw.observe(b);
  });

  // Flowing dashes run only while the diagram is on screen.
  var vis = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { en.target.classList.toggle('is-visible', en.isIntersecting); });
  });
  document.querySelectorAll('.bl-prose .dg').forEach(function (d) { vis.observe(d); });
})();
