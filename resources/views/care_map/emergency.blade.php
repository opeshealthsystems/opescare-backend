<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0C0A0A">
<title>Emergency Care Access — OpesCare</title>
<meta name="description" content="{{ __('public.care_map_emergency.meta_description') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
<script src="{{ asset('js/leaflet.js') }}" defer></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --em:#DC2626; --em-hover:#B91C1C;
  --em-bg:rgba(220,38,38,.08);
  --em-border:rgba(220,38,38,.18);
  --em-text:#FCA5A5;
  --em-muted:rgba(220,38,38,.5);
  --surface:#1A0808;
  --nav:#0C0A0A;
  --text:#FEE2E2;
  --muted:#FDA4AF;
  --subtle:rgba(253,164,175,.45);
  --border-strong:rgba(220,38,38,.25);
  --radius:0.5rem; --radius-lg:0.75rem; --radius-xl:1rem; --radius-full:9999px;
  --shadow:0 4px 16px rgba(220,38,38,.2);
  --panel-w:400px; --nav-h:56px; --banner-h:52px;
  --header-total:calc(var(--nav-h) + var(--banner-h));
}

html,body{height:100%;font-family:'Inter',system-ui,sans-serif;background:var(--nav);color:var(--text);-webkit-font-smoothing:antialiased}

/* ── Nav ─────────────────────────────────────────────────────────── */
.nav{
  position:fixed;top:0;left:0;right:0;z-index:200;
  height:var(--nav-h);
  background:var(--nav);
  border-bottom:1px solid var(--border-strong);
  display:flex;align-items:center;padding:0 1.25rem;gap:1rem;
}
.nav-logo{display:flex;align-items:center;gap:.625rem;text-decoration:none;flex-shrink:0}
.nav-logo-mark{width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#7F1D1D,#DC2626);display:flex;align-items:center;justify-content:center}
.nav-logo-mark svg{color:#fff}
.nav-logo-name{font-family:'Outfit',sans-serif;font-size:.9375rem;font-weight:700;color:#fff;line-height:1}
.nav-logo-tag{font-size:.55rem;color:rgba(220,38,38,.55);font-weight:600;text-transform:uppercase;letter-spacing:.07em;display:block;margin-top:1px}
.nav-dials{display:flex;align-items:center;gap:.5rem;margin-left:auto}
.dial-btn{
  display:flex;align-items:center;gap:.375rem;
  padding:.35rem .875rem;
  border:1.5px solid var(--border-strong);
  border-radius:var(--radius-full);
  font-size:.78rem;font-weight:700;color:var(--em-text);
  cursor:pointer;text-decoration:none;transition:all .15s;white-space:nowrap;
}
.dial-btn:hover{background:var(--em-bg);border-color:var(--em)}
.dial-btn svg{width:13px;height:13px}
.nav-back{
  display:flex;align-items:center;gap:.35rem;
  font-size:.77rem;font-weight:500;color:rgba(255,255,255,.45);
  text-decoration:none;transition:color .15s;white-space:nowrap;
}
.nav-back:hover{color:rgba(255,255,255,.75)}
.nav-back svg{width:13px;height:13px}

/* ── Banner ──────────────────────────────────────────────────────── */
.alert-banner{
  position:fixed;top:var(--nav-h);left:0;right:0;z-index:190;
  height:var(--banner-h);
  background:rgba(220,38,38,.07);
  border-bottom:1px solid var(--border-strong);
  display:flex;align-items:center;padding:0 1.25rem;gap:.875rem;
}
.alert-icon{width:28px;height:28px;border-radius:7px;background:rgba(220,38,38,.15);border:1px solid var(--border-strong);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.alert-icon svg{width:14px;height:14px;color:var(--em)}
.alert-text{flex:1;font-size:.77rem;color:var(--em-text);line-height:1.45}
.alert-text strong{font-weight:700}
.alert-count{font-size:.72rem;font-weight:600;color:var(--muted);white-space:nowrap;flex-shrink:0}

/* ── Main layout ─────────────────────────────────────────────────── */
.main{
  margin-top:var(--header-total);
  display:flex;
  height:calc(100vh - var(--header-total));
  overflow:hidden;
}

/* ── Panel ───────────────────────────────────────────────────────── */
.panel{
  width:var(--panel-w);flex-shrink:0;
  display:flex;flex-direction:column;
  background:var(--nav);
  border-right:1px solid var(--border-strong);
  overflow:hidden;
}
.panel-hdr{
  padding:.75rem 1rem;
  background:rgba(220,38,38,.04);
  border-bottom:1px solid var(--border-strong);
  display:flex;align-items:center;gap:.625rem;
  flex-shrink:0;
}
.panel-title{font-family:'Outfit',sans-serif;font-size:.875rem;font-weight:700;color:var(--em-text);flex:1}
.panel-sub{font-size:.72rem;color:var(--em-muted)}
.panel-note{padding:.6rem 1rem;font-size:.72rem;line-height:1.5;color:var(--muted);background:rgba(220,38,38,.04);border-bottom:1px solid rgba(220,38,38,.14);flex-shrink:0}
.ec-nophone{color:var(--subtle);font-style:italic}
.cards{flex:1;overflow-y:auto;padding:.625rem;display:flex;flex-direction:column;gap:.5rem}
.cards::-webkit-scrollbar{width:3px}
.cards::-webkit-scrollbar-thumb{background:rgba(220,38,38,.2);border-radius:2px}

/* ── Emergency card ──────────────────────────────────────────────── */
.ec{
  background:rgba(220,38,38,.05);
  border:1.5px solid var(--em-border);
  border-radius:var(--radius-xl);
  overflow:hidden;cursor:pointer;
  transition:all .18s;
  position:relative;
}
.ec:hover{border-color:rgba(220,38,38,.4);background:rgba(220,38,38,.1)}
.ec.on{border-color:var(--em);background:rgba(220,38,38,.1)}
.ec-accent{position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--em);opacity:.7}
.ec.on .ec-accent{opacity:1}
.ec-body{padding:.875rem .875rem .875rem 1.1rem}
.ec-top{display:flex;align-items:flex-start;gap:.7rem;margin-bottom:.5rem}
.ec-ico{width:40px;height:40px;border-radius:10px;background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ec-ico svg{width:18px;height:18px;color:var(--em-text)}
.ec-meta{flex:1;min-width:0}
.ec-name{font-size:.875rem;font-weight:700;color:var(--em-text);margin-bottom:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ec-badges{display:flex;gap:.275rem;flex-wrap:wrap}
.ec-badge{font-size:.62rem;font-weight:700;padding:.12rem .45rem;border-radius:50px;background:rgba(220,38,38,.12);color:var(--em-text);border:1px solid rgba(220,38,38,.2)}
.ec-dist{position:absolute;top:.875rem;right:.875rem;font-size:.68rem;font-weight:700;color:var(--em-text);background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.2);padding:.15rem .475rem;border-radius:50px;white-space:nowrap}
.ec-info{display:flex;flex-direction:column;gap:.225rem;margin-bottom:.625rem}
.ec-row{display:flex;align-items:flex-start;gap:.375rem;font-size:.75rem;color:var(--muted);line-height:1.4}
.ec-row svg{width:11px;height:11px;color:rgba(220,38,38,.4);flex-shrink:0;margin-top:1px}
.ec-acts{display:flex;gap:.4rem;padding-top:.6rem;border-top:1px solid rgba(220,38,38,.1)}
.ebtn{display:inline-flex;align-items:center;gap:.3rem;padding:.4rem .7rem;border-radius:7px;font-size:.75rem;font-weight:700;cursor:pointer;border:none;white-space:nowrap;transition:all .15s;font-family:inherit}
.ebtn svg{width:10px;height:10px}
.ebtn-call{background:var(--em);color:#fff;flex:1;justify-content:center}
.ebtn-call:hover{background:var(--em-hover)}
.ebtn-dir{background:rgba(220,38,38,.1);color:var(--em-text);border:1.5px solid rgba(220,38,38,.2)}
.ebtn-dir:hover{background:rgba(220,38,38,.18)}
.ebtn-view{background:rgba(255,255,255,.06);color:rgba(255,255,255,.55);border:1.5px solid rgba(255,255,255,.08)}
.ebtn-view:hover{background:rgba(255,255,255,.1);color:#fff}

/* ── Map ──────────────────────────────────────────────────────────── */
.map-area{flex:1;position:relative;overflow:hidden;background:#0D0D0D}
#map{width:100%;height:100%}
.map-ctrls{position:absolute;bottom:.875rem;right:.875rem;display:flex;flex-direction:column;gap:.375rem;z-index:500}
.map-ctrl{
  background:rgba(26,8,8,.85);border:1px solid rgba(220,38,38,.2);
  border-radius:8px;padding:.45rem .8rem;
  font-size:.73rem;font-weight:600;cursor:pointer;
  box-shadow:0 2px 8px rgba(0,0,0,.3);
  display:flex;align-items:center;gap:.375rem;
  color:var(--em-text);font-family:inherit;
  backdrop-filter:blur(8px);transition:all .15s;
}
.map-ctrl:hover{border-color:rgba(220,38,38,.5);background:rgba(220,38,38,.1)}
.map-ctrl svg{width:12px;height:12px}

/* ── Mobile ──────────────────────────────────────────────────────── */
.fab-map{
  display:none;
  position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);
  z-index:250;align-items:center;gap:.5rem;
  padding:.625rem 1.375rem;
  background:var(--em);color:#fff;
  border:none;border-radius:var(--radius-full);
  font-size:.82rem;font-weight:700;font-family:inherit;
  cursor:pointer;box-shadow:0 4px 20px rgba(220,38,38,.4);
  white-space:nowrap;
}
.fab-map svg{width:14px;height:14px}
.map-overlay{
  display:none;position:fixed;
  inset:var(--header-total) 0 0 0;
  z-index:300;flex-direction:column;
  background:#0D0D0D;
}
.map-overlay.visible{display:flex}
.map-overlay-hdr{
  padding:.75rem 1rem;background:var(--nav);
  border-bottom:1px solid var(--border-strong);
  display:flex;align-items:center;gap:.75rem;
}
.map-overlay-hdr span{font-size:.875rem;font-weight:700;color:var(--em-text);flex:1}
#mapMobile{flex:1}

.btn-close-map{display:flex;align-items:center;gap:.35rem;padding:.3rem .7rem;border:1.5px solid rgba(220,38,38,.2);border-radius:50px;background:transparent;font-size:.75rem;font-weight:600;color:var(--em-text);cursor:pointer;font-family:inherit;transition:border-color .15s}
.btn-close-map:hover{border-color:var(--em)}
.em-empty{text-align:center;padding:3rem 1.5rem}
.em-empty-icon{margin:0 auto .875rem;display:block}
.em-empty-title{font-size:.875rem;font-weight:600;color:var(--em-text);margin-bottom:.375rem}
.em-empty-sub{font-size:.78rem;color:var(--muted)}
.alert-link{color:var(--em);font-weight:700}

/* ── Leaflet ─────────────────────────────────────────────────────── */
.leaflet-popup-content-wrapper{border-radius:.875rem!important;background:#1A0808!important;border:1.5px solid rgba(220,38,38,.25)!important;box-shadow:0 8px 28px rgba(0,0,0,.5)!important}
.leaflet-popup-content{margin:0!important;font-family:'Inter',sans-serif!important}
.leaflet-popup-tip{background:#1A0808!important}
.popup-inner{padding:.8rem}
.popup-top{height:3px;background:var(--em);margin:-0px;border-radius:.875rem .875rem 0 0}
.popup-name{font-size:.82rem;font-weight:700;color:var(--em-text);margin-bottom:.2rem;margin-top:.8rem;padding:0 .8rem}
.popup-sub{font-size:.68rem;color:var(--muted);padding:0 .8rem;margin-bottom:.625rem}
.popup-btns{display:flex;gap:.375rem;padding:.8rem}
.popup-btn{flex:1;text-align:center;padding:.375rem;border-radius:6px;font-size:.7rem;font-weight:700;cursor:pointer;font-family:inherit}
.popup-btn-call{background:var(--em);color:#fff;border:none}
.popup-btn-dir{background:rgba(220,38,38,.1);color:var(--em-text);border:1.5px solid rgba(220,38,38,.2)}

/* ── Responsive ──────────────────────────────────────────────────── */
@media(max-width:768px){
  .panel{width:100%;border-right:none}
  .map-area{display:none}
  .main{height:auto;min-height:calc(100vh - var(--header-total));overflow-y:auto}
  .cards{overflow-y:visible;padding-bottom:5rem}
  .fab-map{display:flex}
  .map-overlay.visible{display:flex}
}
@media(min-width:1280px){
  :root{--panel-w:440px}
}
</style>
{{-- Canonical, hreflang, Open Graph and schema.org --}}
@include('partials.seo_head')

<style>.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}</style>
</head>
<body>

{{-- The page is a map, so its heading is for screen readers and crawlers
     rather than the layout. It had no h1 at all before. --}}
<h1 class="sr-only">{{ __('public.care_map_emergency.h1') }}</h1>

{{-- ── Nav ──────────────────────────────────────────────────────────────── --}}
<nav class="nav">
  <div class="nav-logo">
    <div class="nav-logo-mark">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/>
        <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>
    <div>
      <div class="nav-logo-name">OpesCare</div>
      <div class="nav-logo-tag">Emergency Access</div>
    </div>
  </div>

  <div class="nav-dials">
    <a href="tel:112" class="dial-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
      Dial 112
    </a>
    <a href="tel:199" class="dial-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
      Dial 199
    </a>
    <a href="{{ route('public.care-map') }}" class="nav-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
      Back to map
    </a>
  </div>
</nav>

{{-- ── Alert Banner ─────────────────────────────────────────────────────── --}}
<div class="alert-banner">
  <div class="alert-icon">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/>
      <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
  </div>
  <div class="alert-text">
    <strong>Life-threatening emergency?</strong>
    Call <a href="tel:112" class="alert-link">112</a> or go directly to the nearest A&amp;E — do not rely solely on this page.
  </div>
  <div class="alert-count" id="facCount">{{ count($facilities) === 1
       ? __('public.care_map_emergency.count_hospitals_one')
       : __('public.care_map_emergency.count_hospitals', ['count' => count($facilities)]) }}</div>
</div>

{{-- ── Main Layout ──────────────────────────────────────────────────────── --}}
<div class="main">

  {{-- Panel --}}
  <aside class="panel">
    <div class="panel-hdr">
      <div class="panel-title">{{ __('public.care_map_emergency.panel_title') }}</div>
      <div class="panel-sub">{{ __('public.care_map_emergency.panel_sub') }}</div>
    </div>

    {{-- Nothing in care_facilities records whether a hospital runs a working
         emergency department, so this page lists hospitals by distance and
         says plainly that emergency capability is unconfirmed. --}}
    <p class="panel-note">{{ __('public.care_map_emergency.capability_unknown') }}</p>

    <div class="cards" id="cardsList">
      @forelse($facilities as $f)
        @php
          // Resolved by CareMapSearchService: the emergency line when one
          // exists, else the main line, and null when neither is dialable
          // (most rows hold the placeholder 'N/A').
          $phone = $f->callable_phone ?? null;
          $dist  = isset($f->distance) ? round($f->distance, 1) : null;
          $addr  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
          $dirUrl = ($f->latitude && $f->longitude)
            ? 'https://www.google.com/maps/dir/?api=1&destination='.$f->latitude.','.$f->longitude
            : null;
          $verified = in_array($f->verification_status ?? '', ['license_verified','government_verified']);
        @endphp
        <div class="ec{{ $loop->first ? ' on' : '' }}"
             data-id="{{ $f->id }}"
             data-lat="{{ $f->latitude }}"
             data-lng="{{ $f->longitude }}"
             onclick="selectFacility('{{ $f->id }}', event)">
          <div class="ec-accent"></div>
          <div class="ec-body">
            <div class="ec-top">
              <div class="ec-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                  <polyline points="9,22 9,12 15,12 15,22"/>
                </svg>
              </div>
              <div class="ec-meta">
                <div class="ec-name">{{ $f->facility_name }}</div>
                <div class="ec-badges">
                  {{-- No verification badge here. Every facility in the registry
                       is verification_status = 'unverified', and appearing in the
                       national registry is a listing, not an OpesCare inspection —
                       so there is no badge this page could honestly show. --}}
                  <span class="ec-badge">{{ __('public.care_map_emergency.badge_hospital') }}</span>
                  <span class="ec-badge">{{ __('public.care_map_emergency.call_before_travelling') }}</span>
                  @if($f->emergency_contact)
                    <span class="ec-badge">{{ __('public.care_map_emergency.badge_emergency_line') }}</span>
                  @endif
                </div>
              </div>
            </div>
            @if($dist !== null)
              <div class="ec-dist">{{ $dist }} km</div>
            @endif
            <div class="ec-info">
              @if($addr)
                <div class="ec-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  {{ $addr }}
                </div>
              @endif
              <div class="ec-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                @if($phone)
                  {{ $phone }}
                @else
                  <span class="ec-nophone">{{ __('public.care_map_emergency.no_phone_on_record') }}</span>
                @endif
              </div>
            </div>
            <div class="ec-acts">
              @if($phone)
                <a href="tel:{{ $phone }}" class="ebtn ebtn-call" onclick="event.stopPropagation()">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  {{ __('public.care_map_emergency.action_call') }}
                </a>
              @endif
              @if($dirUrl)
                <a href="{{ $dirUrl }}" target="_blank" rel="noopener" class="ebtn ebtn-dir" onclick="event.stopPropagation()">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                  {{ __('public.care_map_emergency.action_directions') }}
                </a>
              @endif
              <a href="{{ route('public.care-map.profile', $f->id) }}" class="ebtn ebtn-view" onclick="event.stopPropagation()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                {{ __('public.care_map_emergency.action_profile') }}
              </a>
            </div>
          </div>
        </div>
      @empty
        <div class="em-empty">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="rgba(220,38,38,.3)" stroke-width="1.5" class="em-empty-icon"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <p class="em-empty-title">{{ __('public.care_map_emergency.empty_title') }}</p>
          <p class="em-empty-sub">{{ __('public.care_map_emergency.empty_sub') }}</p>
        </div>
      @endforelse
    </div>
  </aside>

  {{-- Desktop Map --}}
  <div class="map-area" id="mapArea">
    <div id="map" role="application" aria-label="{{ __('public.care_map_emergency.map_title') }}"></div>
    <div class="map-ctrls">
      <button class="map-ctrl" onclick="fitAll()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6m-6 0 6-6M9 21H3v-6m6 0-6 6"/></svg>
        Fit all
      </button>
      <button class="map-ctrl" onclick="locateMe()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
        Near me
      </button>
    </div>
  </div>
</div>

{{-- Mobile FAB --}}
<button class="fab-map" id="fabMap" onclick="openMobileMap()">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6"/></svg>
  Show Map
</button>

{{-- Mobile Map Overlay --}}
<div class="map-overlay" id="mapOverlay">
  <div class="map-overlay-hdr">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--em)" stroke-width="2.5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/></svg>
    <span>{{ __('public.care_map_emergency.map_title') }}</span>
    <button onclick="closeMobileMap()" class="btn-close-map">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      Close
    </button>
  </div>
  <div id="mapMobile"></div>
</div>

@php
$_emFacilitiesJson = json_encode($facilities->map(function ($f) {
  return [
    'id'    => $f->id,
    'name'  => $f->facility_name,
    'lat'   => (float)($f->latitude ?? 0),
    'lng'   => (float)($f->longitude ?? 0),
    'phone' => $f->callable_phone ?? null,
    'city'  => $f->city,
    'url'   => route('public.care-map.profile', $f->id),
    'dist'  => isset($f->distance) ? round($f->distance, 1) : null,
  ];
}), JSON_HEX_TAG | JSON_HEX_AMP);
@endphp

<script>
const FACILITIES = {!! $_emFacilitiesJson !!};
const EM_LABELS = {
  hospital:   @json(__('public.care_map_emergency.badge_hospital')),
  caution:    @json(__('public.care_map_emergency.call_before_travelling')),
  call:       @json(__('public.care_map_emergency.action_call')),
  directions: @json(__('public.care_map_emergency.action_directions')),
  noPhone:    @json(__('public.care_map_emergency.no_phone_on_record')),
};

// ── Map state ────────────────────────────────────────────────────────────
let mapDesktop = null, mapMobile = null;
let markersDk = {}, markersMb = {};
let layerDk, layerMb;
let mobileOpen = false, mobileInited = false;

function emIcon(active) {
  const s = active ? 38 : 30, r = s / 2;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${s}" height="${s+8}" viewBox="0 0 ${s} ${s+8}">
    <circle cx="${r}" cy="${r}" r="${r-1}" fill="#DC2626" stroke="rgba(255,255,255,.7)" stroke-width="2.5"/>
    <circle cx="${r}" cy="${r}" r="${(active?10:8)/2}" fill="rgba(255,255,255,.9)"/>
    <polygon points="${r-5},${s+1} ${r+5},${s+1} ${r},${s+8}" fill="#DC2626"/>
  </svg>`;
  return L.divIcon({ html: svg, className: '', iconSize:[s,s+8], iconAnchor:[r,s+8], popupAnchor:[0,-(s+6)] });
}

function popupHtml(f) {
  const dir = f.lat && f.lng ? `https://www.google.com/maps/dir/?api=1&destination=${f.lat},${f.lng}` : null;
  return `<div class="popup-top"></div>
    <div class="popup-name">${f.name}</div>
    <div class="popup-sub">${EM_LABELS.hospital}${f.city ? ' · ' + f.city : ''}${f.dist ? ' · ' + f.dist + ' km' : ''}<br>${f.phone ? EM_LABELS.caution : EM_LABELS.noPhone}</div>
    <div class="popup-btns">
      ${f.phone ? `<a href="tel:${f.phone}" class="popup-btn popup-btn-call">${EM_LABELS.call}</a>` : ''}
      ${dir ? `<a href="${dir}" target="_blank" rel="noopener" class="popup-btn popup-btn-dir">${EM_LABELS.directions}</a>` : ''}
    </div>`;
}

function initMap(containerId, isDesktop) {
  const el = document.getElementById(containerId);
  if (!el) return null;

  const tileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
  const m = L.map(el, { zoomControl: false }).setView([9.082, 8.675], 6);
  L.tileLayer(tileUrl, { attribution: '&copy; OSM &copy; CARTO', subdomains: 'abcd', maxZoom: 19 }).addTo(m);
  L.control.zoom({ position: 'topright' }).addTo(m);

  const layer = L.layerGroup().addTo(m);
  const markers = {};

  FACILITIES.forEach(f => {
    if (!f.lat || !f.lng) return;
    const mk = L.marker([f.lat, f.lng], { icon: emIcon(false) })
      .bindPopup(popupHtml(f), { maxWidth: 240, minWidth: 200, className: 'em-popup' });
    mk.on('click', () => highlightCard(f.id));
    layer.addLayer(mk);
    markers[f.id] = mk;
  });

  const valid = FACILITIES.filter(f => f.lat && f.lng);
  if (valid.length > 0) m.fitBounds(L.latLngBounds(valid.map(f => [f.lat, f.lng])).pad(.15));

  if (isDesktop) { mapDesktop = m; layerDk = layer; markersDk = markers; }
  else           { mapMobile  = m; layerMb = layer; markersMb = markers; }
  return m;
}

function highlightCard(id) {
  document.querySelectorAll('.ec').forEach(c => c.classList.remove('on'));
  const card = document.querySelector(`.ec[data-id="${id}"]`);
  if (card) { card.classList.add('on'); card.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
}

function highlightMarker(id, mapRef, markers) {
  if (!mapRef || !markers[id]) return;
  Object.values(markers).forEach(m => m.setIcon(emIcon(false)));
  markers[id].setIcon(emIcon(true));
  mapRef.setView(markers[id].getLatLng(), Math.max(mapRef.getZoom(), 13), { animate: true });
  markers[id].openPopup();
}

function selectFacility(id, event) {
  if (event.target.closest('a, button')) return;
  document.querySelectorAll('.ec').forEach(c => c.classList.remove('on'));
  event.currentTarget.classList.add('on');
  highlightMarker(id, mapDesktop, markersDk);
  if (mobileOpen) highlightMarker(id, mapMobile, markersMb);
}

function fitAll() {
  const pts = FACILITIES.filter(f => f.lat && f.lng).map(f => [f.lat, f.lng]);
  if (pts.length > 0 && mapDesktop) mapDesktop.fitBounds(L.latLngBounds(pts).pad(.1));
}

function locateMe() {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude: lat, longitude: lng } = pos.coords;
    const youIcon = L.divIcon({
      html: '<div style="width:13px;height:13px;border-radius:50%;background:#DC2626;border:2.5px solid rgba(255,255,255,.7);box-shadow:0 2px 8px rgba(220,38,38,.5)"></div>',
      className: '', iconSize: [13, 13], iconAnchor: [6.5, 6.5],
    });
    [mapDesktop, mapMobile].forEach(m => {
      if (!m) return;
      L.marker([lat, lng], { icon: youIcon }).addTo(m).bindPopup('You are here').openPopup();
      m.setView([lat, lng], 13);
    });
  });
}

function openMobileMap() {
  document.getElementById('mapOverlay').classList.add('visible');
  mobileOpen = true;
  if (!mobileInited) {
    mobileInited = true;
    setTimeout(() => initMap('mapMobile', false), 50);
  } else if (mapMobile) {
    setTimeout(() => mapMobile.invalidateSize(), 50);
  }
}

function closeMobileMap() {
  document.getElementById('mapOverlay').classList.remove('visible');
  mobileOpen = false;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape' && mobileOpen) closeMobileMap(); });

window.addEventListener('load', () => {
  if (typeof L !== 'undefined' && window.innerWidth >= 768) initMap('map', true);
});
window.addEventListener('resize', () => {
  if (window.innerWidth >= 768 && !mapDesktop && typeof L !== 'undefined') initMap('map', true);
  if (mapDesktop) mapDesktop.invalidateSize();
});
</script>
</body>
</html>
