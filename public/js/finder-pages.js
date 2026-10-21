/*
 * Public finder pages (directory, medicine, blood): phone map toggle,
 * desktop filter panel, and "use my location". No inline handlers (CSP).
 */
(function () {
  'use strict';

  // Phone: map sits behind a "Show map" toggle. Desktop: always visible.
  document.querySelectorAll('[data-fp-map-toggle]').forEach(function (btn) {
    var panel = document.getElementById(btn.getAttribute('aria-controls'));
    var label = btn.querySelector('[data-fp-label]');
    if (!panel) return;
    btn.addEventListener('click', function () {
      var open = !panel.classList.contains('is-open');
      panel.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (label) label.textContent = open ? btn.getAttribute('data-hide') : btn.getAttribute('data-show');
      if (open) {
        document.dispatchEvent(new CustomEvent('finder-map:shown'));
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // The filter panel is a <details>: open on desktop, collapsed on phones.
  var filters = document.querySelector('[data-fp-filters]');
  if (filters && window.matchMedia('(min-width: 1024px)').matches) filters.open = true;

  // Cards' "Directions" on phones: open the map first so the line is visible.
  document.addEventListener('click', function (e) {
    var trg = e.target.closest ? e.target.closest('[data-finder-directions]') : null;
    if (!trg) return;
    var panel = document.querySelector('.fp-mapcol');
    var toggle = document.querySelector('[data-fp-map-toggle]');
    if (panel && toggle && !panel.classList.contains('is-open') && !window.matchMedia('(min-width: 1024px)').matches) {
      toggle.click();
    }
  }, true);

  // "Use my location": fill the form's coordinate inputs and resubmit.
  document.querySelectorAll('[data-fp-locate]').forEach(function (btn) {
    var form = document.getElementById(btn.getAttribute('data-fp-locate'));
    var status = document.getElementById(btn.getAttribute('data-status'));
    if (!form) return;
    btn.addEventListener('click', function () {
      function say(t) { if (status) status.textContent = t || ''; }
      if (!navigator.geolocation) { say(btn.getAttribute('data-failed')); return; }
      say(btn.getAttribute('data-locating'));
      navigator.geolocation.getCurrentPosition(function (pos) {
        form.querySelectorAll('[data-fp-lat]').forEach(function (i) { i.value = pos.coords.latitude.toFixed(5); i.disabled = false; });
        form.querySelectorAll('[data-fp-lng]').forEach(function (i) { i.value = pos.coords.longitude.toFixed(5); i.disabled = false; });
        form.querySelectorAll('[data-fp-radius]').forEach(function (i) { i.disabled = false; });
        say(btn.getAttribute('data-active'));
        if (form.checkValidity()) form.submit();
      }, function () { say(btn.getAttribute('data-failed')); }, { timeout: 10000, maximumAge: 300000 });
    });
  });
})();
