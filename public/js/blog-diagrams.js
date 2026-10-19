/*
 * Flow diagrams (blog and /docs): the step being read lights up its cards and
 * arrows in the diagram above it. Hover, keyboard focus and scrolling the step
 * list all drive it. Motion (path draw, travelling pulse) is CSS and only runs
 * while the diagram is on screen and the reader allows motion. Without JS the
 * diagram and its numbered steps are complete and static.
 *
 * Cards and arrows carry data-steps=" 1 3 " (every step that walks them).
 */
(function () {
  'use strict';
  var hasIO = 'IntersectionObserver' in window;

  document.querySelectorAll('[data-dg-steps]').forEach(function (list) {
    var fig = document.querySelector('[data-dg="' + list.getAttribute('data-dg-steps') + '"]');
    if (!fig) return;
    var parts = fig.querySelectorAll('svg [data-steps]');
    var steps = list.querySelectorAll('.dg-step');
    var current = null;

    function show(n) {
      if (n === current) return;
      current = n;
      var token = ' ' + n + ' ';
      parts.forEach(function (p) {
        p.classList.toggle('is-active', !!n && (p.getAttribute('data-steps') || '').indexOf(token) !== -1);
      });
      steps.forEach(function (s) { s.classList.toggle('is-active', s.getAttribute('data-step') === n); });
      fig.classList.toggle('has-active', !!n);
    }

    steps.forEach(function (s) {
      s.setAttribute('tabindex', '0');
      s.addEventListener('mouseenter', function () { show(s.getAttribute('data-step')); });
      s.addEventListener('focusin', function () { show(s.getAttribute('data-step')); });
    });
    parts.forEach(function (p) {
      p.addEventListener('mouseenter', function () {
        var first = (p.getAttribute('data-steps') || '').trim().split(' ')[0];
        if (first) show(first);
      });
    });

    if (hasIO) {
      // The step crossing the middle band of the viewport is the one being read.
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) show(en.target.getAttribute('data-step')); });
      }, { rootMargin: '-45% 0px -45% 0px' });
      steps.forEach(function (s) { io.observe(s); });
    }
  });

  if (!hasIO) return;

  // Animation runs only while a diagram is on screen.
  var vis = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { en.target.classList.toggle('is-visible', en.isIntersecting); });
  });
  document.querySelectorAll('.dg').forEach(function (d) { vis.observe(d); });
})();
