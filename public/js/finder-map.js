/*
 * finder-map.js — embedded, in-app maps for the OpesCare Finder.
 *
 * No external map app, ever. Every "Directions" action stays on the page: it
 * shows the facility and, if the visitor allows location, their own position,
 * the straight-line between them, and the distance. Tiles are OpenStreetMap
 * (already allowed by img-src); routing needs no external service.
 *
 * Usage: drop a <div data-finder-map> with a sibling
 *   <script type="application/json" class="finder-map-data">{single, facilities:[…]}</script>
 * where each facility is {id,name,lat,lng,city,type,url,phone,distance}. Any
 * element with data-finder-directions="<facilityId>" becomes an in-app
 * "show me the way" trigger. Depends only on the global L (leaflet.js).
 */
(function () {
  'use strict';

  var TYPE_COLORS = {
    hospital: '#0F4C81', clinic: '#2563EB', health_center: '#0EA5E9',
    pharmacy: '#059669', laboratory: '#7C3AED', blood_bank: '#DC2626',
    dispensary: '#0D9488', maternity: '#DB2777', other: '#64748B',
  };

  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // Haversine great-circle distance in km.
  function distanceKm(a, b) {
    var R = 6371, dLat = (b[0] - a[0]) * Math.PI / 180, dLng = (b[1] - a[1]) * Math.PI / 180;
    var s = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(a[0] * Math.PI / 180) * Math.cos(b[0] * Math.PI / 180) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
  }

  function markerIcon(color) {
    return L.divIcon({
      html: '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">' +
        '<circle cx="14" cy="14" r="12.5" fill="' + color + '" stroke="#fff" stroke-width="2.5"/>' +
        '<circle cx="14" cy="14" r="4" fill="rgba(255,255,255,.9)"/>' +
        '<polygon points="9,27 19,27 14,35" fill="' + color + '"/></svg>',
      className: '', iconSize: [28, 36], iconAnchor: [14, 35], popupAnchor: [0, -34],
    });
  }

  function userIcon() {
    return L.divIcon({
      html: '<div class="finder-user-dot"><span></span></div>',
      className: '', iconSize: [22, 22], iconAnchor: [11, 11],
    });
  }

  var I18N = (window.FINDER_MAP_I18N || {});
  function t(key, fallback) { return I18N[key] || fallback; }

  function FinderMapInstance(container, config) {
    this.container = container;
    this.single = !!config.single;
    this.facilities = (config.facilities || []).filter(function (f) { return f.lat && f.lng; });
    this.markers = {};
    this.user = null;      // {latlng}
    this.userMarker = null;
    this.routeLine = null;
    this.map = null;
    this._built = false;
  }

  FinderMapInstance.prototype.build = function () {
    if (this._built || typeof L === 'undefined') return;
    this._built = true;

    var center = this.facilities.length ? [this.facilities[0].lat, this.facilities[0].lng] : [5.5, 12.0];
    this.map = L.map(this.container, { zoomControl: true, scrollWheelZoom: !this.single }).setView(center, this.single ? 14 : 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(this.map);

    var self = this, bounds = [];
    this.facilities.forEach(function (f) {
      var color = TYPE_COLORS[f.type] || TYPE_COLORS.other;
      var m = L.marker([f.lat, f.lng], { icon: markerIcon(color) }).addTo(self.map);
      m.bindPopup(self.popupHtml(f), { maxWidth: 250 });
      self.markers[f.id] = m;
      bounds.push([f.lat, f.lng]);
    });
    if (bounds.length > 1) this.map.fitBounds(L.latLngBounds(bounds).pad(0.2));

    // A "locate me" control drops the user pin on THIS map (no page reload).
    var Locate = L.Control.extend({
      options: { position: 'topleft' },
      onAdd: function () {
        var btn = L.DomUtil.create('button', 'finder-map-locate');
        btn.type = 'button';
        btn.title = t('locate', 'Show my location');
        btn.setAttribute('aria-label', t('locate', 'Show my location'));
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>';
        L.DomEvent.on(btn, 'click', function (e) { L.DomEvent.stop(e); self.locate(); });
        return btn;
      },
    });
    this.map.addControl(new Locate());
    setTimeout(function () { self.map.invalidateSize(); }, 60);
  };

  FinderMapInstance.prototype.popupHtml = function (f) {
    var dist = (typeof f.distance === 'number') ? f.distance.toFixed(1) + ' km' : '';
    var h = '<div class="finder-map-popup">';
    h += '<div class="fmp-name">' + esc(f.name) + '</div>';
    if (f.city) h += '<div class="fmp-city">' + esc(f.city) + (dist ? ' · ' + dist : '') + '</div>';
    else if (dist) h += '<div class="fmp-city">' + dist + '</div>';
    h += '<div class="fmp-actions">';
    if (f.url) h += '<a href="' + esc(f.url) + '" class="fmp-link">' + t('view', 'View') + ' &rarr;</a>';
    if (f.phone) h += '<a href="tel:' + esc(f.phone) + '" class="fmp-link">' + t('call', 'Call') + '</a>';
    h += '<button type="button" class="fmp-link fmp-dir" data-finder-directions="' + esc(f.id) + '">' + t('directions', 'Directions') + '</button>';
    h += '</div></div>';
    return h;
  };

  // Get the user's location once, cache it, then run cb(latlng) — or cb(null).
  FinderMapInstance.prototype.withUser = function (cb) {
    if (this.user) { cb(this.user); return; }
    if (!navigator.geolocation) { cb(null); return; }
    var self = this;
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        self.user = [pos.coords.latitude, pos.coords.longitude];
        if (self.userMarker) self.map.removeLayer(self.userMarker);
        self.userMarker = L.marker(self.user, { icon: userIcon(), zIndexOffset: 1000 })
          .addTo(self.map).bindPopup(t('you', 'You are here'));
        cb(self.user);
      },
      function () { cb(null); },
      { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
    );
  };

  FinderMapInstance.prototype.locate = function () {
    var self = this;
    this.withUser(function (u) {
      if (!u) { window.alert(t('geoDenied', 'Location unavailable. Please allow location access.')); return; }
      self.map.setView(u, 14);
      self.userMarker.openPopup();
    });
  };

  // In-app "directions": show the facility + the user, draw the line, fit both,
  // and label the straight-line distance. Never leaves the page.
  FinderMapInstance.prototype.directionsTo = function (facilityId) {
    var f = this.facilities.filter(function (x) { return String(x.id) === String(facilityId); })[0];
    if (!f) return;
    var self = this, dest = [f.lat, f.lng];
    this.container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    this.withUser(function (u) {
      if (self.routeLine) { self.map.removeLayer(self.routeLine); self.routeLine = null; }
      if (!u) { self.map.setView(dest, 15); if (self.markers[f.id]) self.markers[f.id].openPopup(); return; }
      self.routeLine = L.polyline([u, dest], { color: '#0F4C81', weight: 3, opacity: 0.75, dashArray: '6 6' }).addTo(self.map);
      self.map.fitBounds(L.latLngBounds([u, dest]).pad(0.25));
      var km = distanceKm(u, dest).toFixed(1);
      self.routeLine.bindPopup(t('distance', 'Distance') + ': ' + km + ' km (' + t('straightLine', 'straight line') + ')').openPopup();
    });
  };

  var instances = [];

  function initAll() {
    if (typeof L === 'undefined') { setTimeout(initAll, 120); return; }
    document.querySelectorAll('[data-finder-map]').forEach(function (el) {
      if (el._finderMapDone) return;
      el._finderMapDone = true;
      var dataEl = el.parentNode.querySelector('.finder-map-data') || document.querySelector('#' + el.id + '-data');
      var cfg = {};
      try { cfg = JSON.parse(dataEl ? dataEl.textContent : (el.getAttribute('data-facilities') || '{}')); } catch (e) { cfg = {}; }
      var inst = new FinderMapInstance(el, cfg);
      // Only the single-facility map builds eagerly; list maps build when shown
      // (so a hidden mobile panel doesn't mis-size), but also on first need.
      inst.build();
      instances.push(inst);
    });
  }

  // Event delegation so "Directions" works from cards, popups, anywhere.
  document.addEventListener('click', function (e) {
    var trg = e.target.closest ? e.target.closest('[data-finder-directions]') : null;
    if (!trg) return;
    e.preventDefault();
    var id = trg.getAttribute('data-finder-directions');
    var inst = instances[0];
    if (inst) inst.directionsTo(id);
  });

  window.FinderMap = { initAll: initAll, instances: instances };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
  else initAll();
})();
