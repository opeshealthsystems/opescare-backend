<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Care Map — Find Health Services Near You | OpesCare</title>
<meta name="description" content="Find hospitals, clinics, pharmacies, laboratories and blood banks near you, listed in Cameroon's national health facility registry.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
<script src="{{ asset('js/leaflet.js') }}" defer></script>

@php
  $now          = now();
  $currentDay   = (int) $now->format('w'); // 0 = Sunday … 6 = Saturday
  $currentTime  = $now->format('H:i');

  // Pre-compute per-facility open/closed for the blade loop
  $facilityMeta = [];
  foreach ($facilities as $f) {
    $typeSlug = strtolower(str_replace(' ', '_', $f->facility_type ?? 'other'));
    $verified  = in_array($f->verification_status ?? '', ['license_verified','government_verified']);
    $govVerified = ($f->verification_status ?? '') === 'government_verified';

    // Open/closed from hours relation
    $isOpen = null; $is24h = false;
    if ($f->hours && $f->hours->isNotEmpty()) {
      $todayH = $f->hours->first(fn($h) => (int)$h->day_of_week === $currentDay);
      if ($todayH) {
        if ($todayH->is_24_hours)        { $isOpen = true;  $is24h = true; }
        elseif ($todayH->is_closed)      { $isOpen = false; }
        elseif ($todayH->opens_at && $todayH->closes_at) {
          $isOpen = $currentTime >= substr($todayH->opens_at, 0, 5)
                 && $currentTime <= substr($todayH->closes_at, 0, 5);
        }
      }
    }

    $facilityMeta[$f->id] = compact('typeSlug','verified','govVerified','isOpen','is24h');
  }

  // Stats
  $totalCount = count($facilities);
  $typeCounts = collect($facilityMeta)->groupBy('typeSlug')->map->count();
  $verifiedCount = collect($facilityMeta)->filter(fn($m) => $m['verified'])->count();
  $hasDistance   = $facilities->isNotEmpty() && isset($facilities->first()->distance);

  $typeLabels = [
    'hospital'   => ['label'=>'Hospitals',   'icon'=>'H', 'color'=>'#0891B2'],
    'clinic'     => ['label'=>'Clinics',     'icon'=>'C', 'color'=>'#059669'],
    'pharmacy'   => ['label'=>'Pharmacies',  'icon'=>'Rx','color'=>'#D97706'],
    'laboratory' => ['label'=>'Labs',        'icon'=>'L', 'color'=>'#7C3AED'],
    'blood_bank' => ['label'=>'Blood Banks', 'icon'=>'B', 'color'=>'#DC2626'],
    'diagnostic' => ['label'=>'Diagnostic',  'icon'=>'D', 'color'=>'#0F4C81'],
  ];
@endphp

<style>
/* ── Reset ───────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --brand:         #0F4C81;
  --brand-hover:   #1A6AAF;
  --brand-subtle:  #EFF6FF;
  --teal:          #0D9488;
  --teal-light:    #14B8A6;

  --bg:            #F1F5F9;
  --surface:       #FFFFFF;
  --border:        #E2E8F0;
  --border-strong: #CBD5E1;

  --text-primary:   #0F172A;
  --text-secondary: #475569;
  --text-muted:     #94A3B8;

  --radius-sm:  0.375rem;
  --radius:     0.5rem;
  --radius-lg:  0.75rem;
  --radius-xl:  1rem;
  --radius-2xl: 1.25rem;
  --radius-full: 9999px;

  --shadow-xs: 0 1px 2px rgba(0,0,0,.05);
  --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
  --shadow:    0 4px 12px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.04);
  --shadow-md: 0 8px 24px rgba(0,0,0,.10), 0 2px 6px rgba(0,0,0,.05);
  --shadow-lg: 0 16px 40px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.07);

  --hospital:   #0891B2; --hospital-bg:   #ECFEFF; --hospital-ring:   #A5F3FC;
  --clinic:     #059669; --clinic-bg:     #ECFDF5; --clinic-ring:     #A7F3D0;
  --pharmacy:   #D97706; --pharmacy-bg:   #FFFBEB; --pharmacy-ring:   #FDE68A;
  --lab:        #7C3AED; --lab-bg:        #F5F3FF; --lab-ring:        #DDD6FE;
  --blood:      #DC2626; --blood-bg:      #FEF2F2; --blood-ring:      #FECACA;
  --diagnostic: #0F4C81; --diagnostic-bg: #EFF6FF; --diagnostic-ring: #BFDBFE;
  --other:      #64748B; --other-bg:      #F8FAFC; --other-ring:      #E2E8F0;

  --nav-h:     60px;
  --search-h:  52px;
  --filter-h:  52px;
  --header-total: calc(var(--nav-h) + var(--search-h) + var(--filter-h));
  --panel-w:   420px;
}

html, body {
  height: 100%;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: var(--text-primary);
  background: var(--bg);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* ── Navigation (dark) ──────────────────────────────────────────────── */
.nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 200;
  height: var(--nav-h);
  background: #0A1628;
  border-bottom: 1px solid rgba(255,255,255,.08);
  display: flex; align-items: center; gap: 0.875rem;
  padding: 0 1.25rem;
}

.nav-logo {
  display: flex; align-items: center; gap: 0.625rem;
  text-decoration: none; flex-shrink: 0;
}
.nav-logo-mark {
  width: 32px; height: 32px; border-radius: 8px;
  background: linear-gradient(135deg, #1a6fb5 0%, #0aab9a 100%);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.nav-logo-mark svg { color: #fff; }
.nav-logo-text {
  font-family: 'Outfit', sans-serif;
  font-size: 1rem; font-weight: 700;
  color: #fff;
  line-height: 1;
}
.nav-logo-sub {
  display: block;
  font-size: 0.55rem; font-weight: 500;
  color: rgba(255,255,255,.4);
  letter-spacing: 0.07em;
  text-transform: uppercase;
  line-height: 1;
  margin-top: 2px;
}

.nav-spacer { flex: 1; }

.btn-emergency {
  display: flex; align-items: center; gap: 0.375rem;
  padding: 0.4rem 0.875rem;
  background: rgba(220,38,38,.85); color: #fff;
  border: none; border-radius: var(--radius-full);
  font-size: 0.78rem; font-weight: 700; font-family: inherit;
  cursor: pointer; text-decoration: none;
  transition: background 0.15s, transform 0.1s;
  white-space: nowrap; flex-shrink: 0;
}
.btn-emergency:hover  { background: #DC2626; }
.btn-emergency:active { transform: scale(0.97); }
.btn-emergency svg { width: 13px; height: 13px; }
.btn-emergency .em-label { display: none; }

.btn-icon {
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  border-radius: var(--radius-full);
  border: 1.5px solid rgba(255,255,255,.15);
  background: transparent;
  color: rgba(255,255,255,.65);
  cursor: pointer; transition: all 0.15s;
  flex-shrink: 0;
}
.btn-icon:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-subtle); }
.btn-icon svg { width: 15px; height: 15px; }

/* ── Search Bar (below nav on mobile) ──────────────────────────────── */
.search-bar {
  position: fixed; top: var(--nav-h); left: 0; right: 0; z-index: 190;
  height: var(--search-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
  padding: 0 1rem; gap: 0.5rem;
  box-shadow: var(--shadow-xs);
}
.search-field {
  flex: 1; position: relative;
  display: flex; align-items: center;
}
.search-field-icon {
  position: absolute; left: 0.75rem;
  color: var(--text-muted); pointer-events: none;
  display: flex;
}
.search-field input {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2.375rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-full);
  font-size: 0.875rem; font-family: inherit;
  color: var(--text-primary);
  background: var(--bg);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.search-field input:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(15,76,129,.1);
  background: var(--surface);
}
.search-field input::placeholder { color: var(--text-muted); }

.search-locate-btn {
  flex-shrink: 0;
  display: flex; align-items: center; gap: 0.375rem;
  padding: 0.4rem 0.75rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-full);
  background: var(--surface);
  font-size: 0.78rem; font-weight: 500; font-family: inherit;
  color: var(--text-secondary);
  cursor: pointer; transition: all 0.15s;
  white-space: nowrap;
}
.search-locate-btn:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-subtle); }
.search-locate-btn svg { width: 13px; height: 13px; }
.search-locate-btn .loc-label { display: none; }

/* ── Filter Strip ───────────────────────────────────────────────────── */
.filter-strip {
  position: fixed;
  top: calc(var(--nav-h) + var(--search-h));
  left: 0; right: 0; z-index: 180;
  height: var(--filter-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
  padding: 0 1rem; gap: 0.5rem;
  overflow-x: auto; scrollbar-width: none;
}
.filter-strip::-webkit-scrollbar { display: none; }

/* Category chip — matches mockup rail style */
.filter-chip {
  display: inline-flex; align-items: center; gap: 0.375rem;
  padding: 0.35rem 0.875rem;
  border: 1.5px solid transparent;
  border-radius: var(--radius-full);
  font-size: 0.8rem; font-weight: 500; font-family: inherit;
  cursor: pointer; white-space: nowrap;
  background: transparent; color: var(--text-secondary);
  transition: all 0.15s;
  flex-shrink: 0; user-select: none;
}
.filter-chip .chip-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.filter-chip:hover { color: var(--text-primary); background: var(--bg); }

/* Count badge inside chip */
.filter-chip .chip-count {
  font-size: 0.67rem; font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 50px;
  background: rgba(0,0,0,.06);
  color: inherit;
}

/* Active states — chip background + count badge tinted */
.filter-chip.active { color: #0A1628; background: #E2E8F0; }
.filter-chip.active .chip-count { background: rgba(0,0,0,.1); }

.chip-hospital.active  { color: var(--hospital);  background: var(--hospital-bg);  }
.chip-hospital.active  .chip-count { background: rgba(8,145,178,.12); }
.chip-clinic.active    { color: var(--clinic);    background: var(--clinic-bg);    }
.chip-clinic.active    .chip-count { background: rgba(5,150,105,.12); }
.chip-pharmacy.active  { color: var(--pharmacy);  background: var(--pharmacy-bg);  }
.chip-pharmacy.active  .chip-count { background: rgba(217,119,6,.12); }
.chip-laboratory.active{ color: var(--lab);       background: var(--lab-bg);       }
.chip-laboratory.active .chip-count { background: rgba(124,58,237,.12); }
.chip-blood_bank.active{ color: var(--blood);     background: var(--blood-bg);     }
.chip-blood_bank.active .chip-count { background: rgba(220,38,38,.12); }
.chip-diagnostic.active{ color: var(--diagnostic);background: var(--diagnostic-bg);}
.chip-diagnostic.active .chip-count { background: rgba(15,76,129,.12); }

.chip-all.active { color: #fff; background: #0A1628; }
.chip-all.active .chip-count { background: rgba(255,255,255,.15); color: rgba(255,255,255,.8); }

/* ── Main Layout ────────────────────────────────────────────────────── */
.main {
  margin-top: var(--header-total);
  display: flex;
  min-height: calc(100vh - var(--header-total));
}

/* ── Results Panel ──────────────────────────────────────────────────── */
.results-panel {
  width: 100%;
  display: flex; flex-direction: column;
  background: var(--bg);
  overflow: hidden;
}

.results-header {
  padding: 0.875rem 1rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
  gap: 0.75rem;
}
.results-count-text {
  font-size: 0.8rem; font-weight: 500;
  color: var(--text-muted);
  flex: 1;
}
.results-count-text strong { color: var(--text-primary); font-weight: 700; }
.results-count-text .verified-count {
  display: inline-flex; align-items: center; gap: 0.2rem;
  color: #059669; font-size: 0.72rem;
  margin-left: 0.375rem;
}
.verified-check { width: 9px; height: 9px; }

.sort-select {
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 0.3rem 0.5rem;
  font-size: 0.75rem; font-family: inherit;
  color: var(--text-secondary);
  background: var(--surface);
  cursor: pointer; outline: none;
  transition: border-color 0.15s;
}
.sort-select:focus { border-color: var(--brand); }

/* Stats chips in results header */
.stats-chips {
  display: flex; gap: 0.375rem;
  overflow-x: auto; scrollbar-width: none;
  padding: 0.625rem 1rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
}
.stats-chips::-webkit-scrollbar { display: none; }
.stat-chip {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.2rem 0.625rem;
  border-radius: var(--radius-full);
  font-size: 0.72rem; font-weight: 600;
  white-space: nowrap; flex-shrink: 0;
  border: 1.5px solid transparent;
}

/* ── Facility Cards ─────────────────────────────────────────────────── */
.results-list {
  flex: 1;
  overflow-y: auto;
  padding: 0.875rem;
  display: flex; flex-direction: column; gap: 0.625rem;
  padding-bottom: 5rem; /* space for FAB */
}
.results-list::-webkit-scrollbar { width: 4px; }
.results-list::-webkit-scrollbar-track { background: transparent; }
.results-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

.fac-card {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 1rem;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s;
  display: block; text-decoration: none; color: inherit;
  position: relative;
  overflow: hidden;
}
.fac-card::before {
  content: '';
  position: absolute; left: 0; top: 0; bottom: 0;
  width: 3px;
  background: var(--type-color, var(--brand));
  opacity: 0;
  transition: opacity 0.15s;
}
.fac-card:hover {
  border-color: var(--border-strong);
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}
.fac-card:hover::before { opacity: 1; }
.fac-card.active {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(15,76,129,.1);
}
.fac-card.active::before { opacity: 1; }

/* Card top row */
.fac-top {
  display: flex; align-items: flex-start; gap: 0.75rem;
  margin-bottom: 0.625rem;
}

.fac-icon {
  width: 44px; height: 44px;
  border-radius: var(--radius-lg);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  position: relative;
}
.fac-icon svg { width: 20px; height: 20px; }
.fac-icon.hospital   { background: var(--hospital-bg);   color: var(--hospital);   }
.fac-icon.clinic     { background: var(--clinic-bg);     color: var(--clinic);     }
.fac-icon.pharmacy   { background: var(--pharmacy-bg);   color: var(--pharmacy);   }
.fac-icon.laboratory { background: var(--lab-bg);        color: var(--lab);        }
.fac-icon.blood_bank { background: var(--blood-bg);      color: var(--blood);      }
.fac-icon.diagnostic_center,
.fac-icon.diagnostic { background: var(--diagnostic-bg); color: var(--diagnostic); }
.fac-icon.other      { background: var(--other-bg);      color: var(--other);      }

.fac-main { flex: 1; min-width: 0; }

.fac-name-row {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 0.5rem;
  margin-bottom: 0.3rem;
}
.fac-name {
  font-size: 0.9rem; font-weight: 600;
  color: var(--text-primary);
  line-height: 1.35;
  flex: 1; min-width: 0;
}
.fac-distance {
  font-size: 0.72rem; font-weight: 600;
  color: var(--brand); background: var(--brand-subtle);
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-full);
  white-space: nowrap; flex-shrink: 0;
}

/* Badge row */
.fac-badges {
  display: flex; align-items: center; gap: 0.3rem;
  flex-wrap: wrap;
}
.badge {
  display: inline-flex; align-items: center; gap: 0.2rem;
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-full);
  font-size: 0.67rem; font-weight: 600;
  letter-spacing: 0.01em; white-space: nowrap;
}
.badge svg { width: 9px; height: 9px; }
.badge-type-hospital    { background: var(--hospital-bg);   color: var(--hospital);   }
.badge-type-clinic      { background: var(--clinic-bg);     color: var(--clinic);     }
.badge-type-pharmacy    { background: var(--pharmacy-bg);   color: var(--pharmacy);   }
.badge-type-laboratory  { background: var(--lab-bg);        color: var(--lab);        }
.badge-type-blood_bank  { background: var(--blood-bg);      color: var(--blood);      }
.badge-type-diagnostic_center,
.badge-type-diagnostic  { background: var(--diagnostic-bg); color: var(--diagnostic); }
.badge-type-other       { background: var(--other-bg);      color: var(--other);      }
.badge-verified  { background: #DCFCE7; color: #15803D; }
.badge-gov       { background: #FEF3C7; color: #B45309; }
.badge-open      { background: #F0FDF4; color: #15803D; }
.badge-closed    { background: #FEF2F2; color: #DC2626; }
.badge-24h       { background: #FEF2F2; color: #DC2626; font-weight: 700; }
.badge-connected { background: #EFF6FF; color: var(--brand); }

/* Card body */
.fac-body {
  display: flex; flex-direction: column; gap: 0.3rem;
  margin-bottom: 0.75rem;
}
.fac-row {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.78rem; color: var(--text-secondary);
  line-height: 1.4;
}
.fac-row svg {
  width: 12px; height: 12px;
  flex-shrink: 0; color: var(--text-muted);
}
.fac-row a { color: inherit; text-decoration: none; }
.fac-row a:hover { color: var(--brand); text-decoration: underline; }

/* Card footer actions */
.fac-footer {
  display: flex; gap: 0.5rem; align-items: center;
  flex-wrap: wrap;
}
.btn-sm {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.4rem 0.75rem;
  border-radius: var(--radius);
  font-size: 0.77rem; font-weight: 500; font-family: inherit;
  cursor: pointer; text-decoration: none; border: none;
  transition: all 0.15s; white-space: nowrap;
}
.btn-sm svg { width: 12px; height: 12px; }
.btn-view {
  background: var(--brand); color: #fff;
  margin-left: auto;
}
.btn-view:hover { background: var(--brand-hover); }
.btn-call {
  background: var(--clinic-bg); color: var(--clinic);
  border: 1.5px solid var(--clinic-ring);
}
.btn-call:hover { background: var(--clinic); color: #fff; }
.btn-dir {
  background: var(--bg); color: var(--text-secondary);
  border: 1.5px solid var(--border);
}
.btn-dir:hover { border-color: var(--text-secondary); color: var(--text-primary); }

/* ── Map Panel ──────────────────────────────────────────────────────── */
.map-panel {
  display: none;
  position: relative;
  flex: 1;
}
#map { width: 100%; height: 100%; min-height: 400px; }

.map-controls {
  position: absolute; bottom: 1rem; right: 1rem; z-index: 500;
  display: flex; flex-direction: column; gap: 0.5rem;
}
.map-ctrl-btn {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 0.5rem 0.875rem;
  font-size: 0.78rem; font-weight: 500;
  cursor: pointer; box-shadow: var(--shadow);
  display: flex; align-items: center; gap: 0.375rem;
  font-family: inherit; color: var(--text-primary);
  transition: all 0.15s;
}
.map-ctrl-btn:hover { border-color: var(--brand); color: var(--brand); }
.map-ctrl-btn svg { width: 13px; height: 13px; }

/* ── Mobile Map Overlay ─────────────────────────────────────────────── */
.map-overlay {
  display: none;
  position: fixed;
  inset: var(--header-total) 0 0 0;
  z-index: 300;
  background: var(--surface);
  flex-direction: column;
}
.map-overlay.visible { display: flex; }
.map-overlay-header {
  padding: 0.75rem 1rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
  gap: 0.75rem;
}
.map-overlay-title { font-size: 0.875rem; font-weight: 600; flex: 1; }
.btn-close-map {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-full);
  background: var(--surface);
  font-size: 0.78rem; font-weight: 500; font-family: inherit;
  cursor: pointer; color: var(--text-secondary);
  transition: all 0.15s;
}
.btn-close-map:hover { border-color: var(--brand); color: var(--brand); }
.btn-close-map svg { width: 13px; height: 13px; }
#mapOverlayContainer { flex: 1; position: relative; }
#mapOverlay { width: 100%; height: 100%; }
.map-mobile-fill { width: 100%; height: 100%; }
.overlay-title-icon { width:14px; height:14px; vertical-align:middle; margin-right:5px; }

/* ── Floating Map Toggle (mobile) ───────────────────────────────────── */
.fab-map-toggle {
  position: fixed;
  bottom: 3.25rem; left: 50%; transform: translateX(-50%);
  z-index: 250;
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.625rem 1.375rem;
  background: var(--brand); color: #fff;
  border: none; border-radius: var(--radius-full);
  font-size: 0.82rem; font-weight: 600; font-family: inherit;
  cursor: pointer;
  box-shadow: var(--shadow-lg);
  transition: background 0.15s, transform 0.1s;
  white-space: nowrap;
}
.fab-map-toggle:hover  { background: var(--brand-hover); }
.fab-map-toggle:active { transform: translateX(-50%) scale(0.97); }
.fab-map-toggle svg { width: 15px; height: 15px; flex-shrink: 0; }
.fab-map-toggle svg { width: 15px; height: 15px; }

/* ── Empty State ────────────────────────────────────────────────────── */
.empty-state {
  display: flex; flex-direction: column; align-items: center;
  text-align: center; padding: 3rem 1.5rem; gap: 0.875rem;
}
.empty-icon {
  width: 56px; height: 56px; border-radius: var(--radius-xl);
  background: var(--surface);
  border: 1.5px solid var(--border);
  display: flex; align-items: center; justify-content: center;
}
.empty-icon svg { color: var(--text-muted); }
.empty-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); }
.empty-sub   { font-size: 0.82rem; color: var(--text-muted); max-width: 240px; line-height: 1.6; }
.btn-reset {
  padding: 0.5rem 1.25rem;
  background: var(--brand); color: #fff;
  border: none; border-radius: var(--radius-full);
  font-size: 0.82rem; font-weight: 600; font-family: inherit;
  cursor: pointer; transition: background 0.15s;
}
.btn-reset:hover { background: var(--brand-hover); }

/* ── Skeleton Loader ────────────────────────────────────────────────── */
.skeleton {
  background: linear-gradient(90deg, #F1F5F9 25%, #E2E8F0 50%, #F1F5F9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
  border-radius: var(--radius-sm);
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── Location Banner ────────────────────────────────────────────────── */
.location-banner {
  margin: 0.625rem 0.875rem;
  padding: 0.75rem 1rem;
  background: var(--brand-subtle);
  border: 1.5px solid #BFDBFE;
  border-radius: var(--radius-lg);
  display: flex; align-items: center; gap: 0.625rem;
  font-size: 0.8rem; color: var(--brand);
  cursor: pointer;
  transition: background 0.15s;
}
.location-banner:hover { background: #DBEAFE; }
.location-banner svg { width: 16px; height: 16px; flex-shrink: 0; }
.location-banner strong { font-weight: 600; display: block; }
.location-banner span { color: var(--text-secondary); font-size: 0.75rem; }

/* ── Disclaimer ─────────────────────────────────────────────────────── */
.disclaimer-bar {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
  background: #FFFBEB; border-top: 1px solid #FDE68A;
  padding: 0.4rem 1rem;
  font-size: 0.68rem; color: #92400E;
  display: flex; align-items: center; gap: 0.5rem;
  line-height: 1.5;
}
.disclaimer-bar svg { flex-shrink: 0; color: #D97706; width: 12px; height: 12px; }

/* ── Leaflet overrides ──────────────────────────────────────────────── */
.leaflet-popup-content-wrapper {
  border-radius: var(--radius-xl) !important;
  box-shadow: var(--shadow-md) !important;
  border: 1.5px solid var(--border);
  overflow: hidden;
}
.leaflet-popup-content { margin: 0 !important; font-family: 'Inter', sans-serif !important; }
.leaflet-popup-tip-container { margin-top: -1px; }
.leaflet-popup-tip { background: var(--surface) !important; }

.popup-inner { padding: 0.875rem; min-width: 200px; }
.popup-type-bar {
  display: flex; align-items: center; gap: 0.375rem;
  margin-bottom: 0.5rem;
}
.popup-type-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.popup-type-label { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.popup-name { font-size: 0.875rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem; line-height: 1.35; }
.popup-city { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.75rem; }
.popup-actions { display: flex; gap: 0.375rem; }
.popup-btn {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.25rem;
  padding: 0.4rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.72rem; font-weight: 600;
  text-decoration: none; border: none; cursor: pointer;
  font-family: inherit; transition: all 0.15s;
}
.popup-btn-view { background: var(--brand); color: #fff; }
.popup-btn-view:hover { background: var(--brand-hover); }
.popup-btn-dir { background: var(--bg); color: var(--text-secondary); border: 1.5px solid var(--border); }
.popup-btn-dir:hover { border-color: var(--text-secondary); color: var(--text-primary); }
.popup-btn svg { width: 10px; height: 10px; }

/* ── Responsive ─────────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .btn-emergency .em-label { display: inline; }
  .search-locate-btn .loc-label { display: inline; }
}

@media (min-width: 1024px) {
  :root { --nav-h: 64px; --search-h: 0px; }

  /* On desktop, search merges into nav */
  .nav { gap: 1rem; padding: 0 1.5rem; }
  .nav .nav-logo-sub { display: block; }
  .search-bar { display: none; }

  /* Inline search in nav */
  .nav-search-desktop {
    flex: 1; max-width: 460px;
    position: relative; display: flex; align-items: center;
  }
  .nav-search-desktop-icon {
    position: absolute; left: 0.75rem;
    color: rgba(255,255,255,.38); pointer-events: none; display: flex;
  }
  .nav-search-desktop input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2.375rem;
    border: 1.5px solid rgba(255,255,255,.12);
    border-radius: var(--radius-full);
    font-size: 0.82rem; font-family: inherit;
    color: rgba(255,255,255,.85); background: rgba(255,255,255,.07);
    outline: none; transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
  }
  .nav-search-desktop input:focus {
    border-color: rgba(255,255,255,.3);
    background: rgba(255,255,255,.1);
    box-shadow: 0 0 0 3px rgba(255,255,255,.06);
    color: #fff;
  }
  .nav-search-desktop input::placeholder { color: rgba(255,255,255,.38); }

  :root { --header-total: calc(var(--nav-h) + var(--filter-h)); }
  .filter-strip { top: var(--nav-h); }

  /* Split layout */
  .main {
    height: calc(100vh - var(--header-total));
    min-height: unset;
    overflow: hidden;
  }
  .results-panel {
    width: var(--panel-w);
    flex-shrink: 0;
    height: 100%;
    border-right: 1px solid var(--border);
  }
  .results-list { overflow-y: auto; padding-bottom: 1.5rem; }
  .map-panel { display: block; }
  .map-panel #map { height: 100%; }

  /* Hide mobile elements */
  .fab-map-toggle { display: none; }
  .map-overlay    { display: none !important; }
  .disclaimer-bar { display: none; }

  /* Desktop location banner inside panel */
  .location-banner { margin: 0.625rem; }
}

@media (min-width: 1280px) {
  :root { --panel-w: 460px; }
}
</style>
</head>
<body>

{{-- ── Navigation ──────────────────────────────────────────────────────── --}}
<nav class="nav">
  <a href="{{ route('public.landing') }}" class="nav-logo" aria-label="OpesCare Home">
    <div class="nav-logo-mark" aria-hidden="true">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
      </svg>
    </div>
    <div>
      <span class="nav-logo-text">OpesCare</span>
      <span class="nav-logo-sub">Care Map</span>
    </div>
  </a>

  {{-- Desktop inline search (hidden on mobile via CSS) --}}
  <div class="nav-search-desktop" aria-hidden="true">
    <span class="nav-search-desktop-icon">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
    </span>
    <input id="searchInputDesktop" type="search" placeholder="Search hospitals, pharmacies, labs…" autocomplete="off" aria-label="Search facilities">
  </div>

  <div class="nav-spacer"></div>

  <button class="btn-icon" onclick="locateMe()" title="Find facilities near me" aria-label="Locate me">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
    </svg>
  </button>

  <a href="{{ route('public.care-map.emergency') }}" class="btn-emergency" aria-label="Emergency facilities">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/>
      <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <span class="em-label">Emergency</span>
  </a>
</nav>

{{-- ── Mobile Search Bar ────────────────────────────────────────────────── --}}
<div class="search-bar" role="search" aria-label="Search health facilities">
  <div class="search-field">
    <span class="search-field-icon" aria-hidden="true">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
    </span>
    <input id="searchInputMobile" type="search" placeholder="Hospitals, pharmacies, labs…" autocomplete="off" aria-label="Search facilities">
  </div>
  <button class="search-locate-btn" onclick="locateMe()" aria-label="Find facilities near my location">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
    </svg>
    <span class="loc-label">Near me</span>
  </button>
</div>

@php
  $counts = [
    'hospital'   => $facilities->filter(fn($f) => strtolower(str_replace(' ','_',$f->facility_type??'')) === 'hospital')->count(),
    'clinic'     => $facilities->filter(fn($f) => strtolower(str_replace(' ','_',$f->facility_type??'')) === 'clinic')->count(),
    'pharmacy'   => $facilities->filter(fn($f) => strtolower(str_replace(' ','_',$f->facility_type??'')) === 'pharmacy')->count(),
    'laboratory' => $facilities->filter(fn($f) => strtolower(str_replace(' ','_',$f->facility_type??'')) === 'laboratory')->count(),
    'blood_bank' => $facilities->filter(fn($f) => strtolower(str_replace(' ','_',$f->facility_type??'')) === 'blood_bank')->count(),
    'diagnostic' => $facilities->filter(fn($f) => str_contains(strtolower($f->facility_type??''), 'diagnostic'))->count(),
  ];
@endphp
{{-- ── Category Rail ────────────────────────────────────────────────────── --}}
<div class="filter-strip" id="filterStrip" role="group" aria-label="Filter by facility type">
  <button class="filter-chip chip-all active" data-type="all" onclick="filterBy('all', this)">
    All facilities <span class="chip-count">{{ $totalCount }}</span>
  </button>
  <button class="filter-chip chip-hospital" data-type="hospital" onclick="filterBy('hospital', this)">
    <span class="chip-dot" style="background:var(--hospital)"></span>Hospitals
    @if($counts['hospital']) <span class="chip-count">{{ $counts['hospital'] }}</span> @endif
  </button>
  <button class="filter-chip chip-clinic" data-type="clinic" onclick="filterBy('clinic', this)">
    <span class="chip-dot" style="background:var(--clinic)"></span>Clinics
    @if($counts['clinic']) <span class="chip-count">{{ $counts['clinic'] }}</span> @endif
  </button>
  <button class="filter-chip chip-pharmacy" data-type="pharmacy" onclick="filterBy('pharmacy', this)">
    <span class="chip-dot" style="background:var(--pharmacy)"></span>Pharmacies
    @if($counts['pharmacy']) <span class="chip-count">{{ $counts['pharmacy'] }}</span> @endif
  </button>
  <button class="filter-chip chip-laboratory" data-type="laboratory" onclick="filterBy('laboratory', this)">
    <span class="chip-dot" style="background:var(--lab)"></span>Laboratories
    @if($counts['laboratory']) <span class="chip-count">{{ $counts['laboratory'] }}</span> @endif
  </button>
  <button class="filter-chip chip-blood_bank" data-type="blood_bank" onclick="filterBy('blood_bank', this)">
    <span class="chip-dot" style="background:var(--blood)"></span>Blood Banks
    @if($counts['blood_bank']) <span class="chip-count">{{ $counts['blood_bank'] }}</span> @endif
  </button>
  <button class="filter-chip chip-diagnostic" data-type="diagnostic" onclick="filterBy('diagnostic', this)">
    <span class="chip-dot" style="background:var(--diagnostic)"></span>Diagnostic
    @if($counts['diagnostic']) <span class="chip-count">{{ $counts['diagnostic'] }}</span> @endif
  </button>
</div>

{{-- ── Main Layout ──────────────────────────────────────────────────────── --}}
<div class="main" id="mainLayout">

  {{-- Results Panel --}}
  <aside class="results-panel" id="resultsPanel" aria-label="Facility results">

    {{-- Stats strip --}}
    @if($totalCount > 0)
    <div class="stats-chips" id="statsChips" aria-label="Facility type breakdown">
      @foreach($typeLabels as $slug => $info)
        @if(($typeCounts[$slug] ?? 0) > 0)
        <span class="stat-chip"
              style="background: {{ $info['color'] }}18; color: {{ $info['color'] }}; border-color: {{ $info['color'] }}30;">
          {{ $typeCounts[$slug] }} {{ $info['label'] }}
        </span>
        @endif
      @endforeach
      @if($verifiedCount > 0)
        <span class="stat-chip" style="background:#DCFCE7;color:#15803D;border-color:#A7F3D0;">
          {{ $verifiedCount }} Verified
        </span>
      @endif
    </div>
    @endif

    {{-- Results header --}}
    <div class="results-header">
      <p class="results-count-text" id="resultsCountText" aria-live="polite">
        <strong id="countNum">{{ $totalCount }}</strong>
        {{ Str::plural('facility', $totalCount) }} found
        @if($verifiedCount > 0)
          <span class="verified-count">
            <svg class="verified-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            {{ $verifiedCount }} verified
          </span>
        @endif
      </p>
      <select class="sort-select" id="sortSelect" onchange="sortFacilities(this.value)" aria-label="Sort results">
        <option value="name">A – Z</option>
        @if($hasDistance)
        <option value="distance">Nearest</option>
        @endif
        <option value="verified">Verified first</option>
        <option value="type">By type</option>
      </select>
    </div>

    {{-- Location prompt (shown when no geo search done) --}}
    @if(!$hasDistance)
    <div class="location-banner" onclick="locateMe()" id="locationBanner" role="button" tabindex="0" aria-label="Enable location to find nearby facilities">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
      </svg>
      <div>
        <strong>Find facilities near you</strong>
        <span>Tap to enable location and sort by distance</span>
      </div>
    </div>
    @endif

    {{-- Cards list --}}
    <div class="results-list" id="resultsList" role="list">
      @forelse($facilities as $f)
        @php
          $meta    = $facilityMeta[$f->id];
          $slug    = $meta['typeSlug'];
          $verified  = $meta['verified'];
          $govVerif  = $meta['govVerified'];
          $isOpen    = $meta['isOpen'];
          $is24h     = $meta['is24h'];
          $dist      = isset($f->distance) ? round($f->distance, 1) : null;

          $typeColor = [
            'hospital'         => '#0891B2',
            'clinic'           => '#059669',
            'pharmacy'         => '#D97706',
            'laboratory'       => '#7C3AED',
            'blood_bank'       => '#DC2626',
            'diagnostic'       => '#0F4C81',
            'diagnostic_center'=> '#0F4C81',
          ][$slug] ?? '#64748B';

          $typeLabel = ucwords(str_replace('_', ' ', $f->facility_type ?? 'Other'));

          $icons = [
            'hospital'         => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/>',
            'clinic'           => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
            'pharmacy'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M9 12h6m-3-3v6"/>',
            'laboratory'       => '<path d="M14.5 2v17.5c0 1.4-1.1 2.5-2.5 2.5s-2.5-1.1-2.5-2.5V2"/><path d="M8.5 2h7"/><path d="M14.5 16h-5s-3 0-3 3.5"/>',
            'blood_bank'       => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
            'diagnostic'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
            'diagnostic_center'=> '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
          ];
          $iconPath = $icons[$slug] ?? '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>';

          $locationStr = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
          $dirUrl = ($f->latitude && $f->longitude)
            ? 'https://www.google.com/maps/dir/?api=1&destination='.$f->latitude.','.$f->longitude
            : null;
        @endphp

        <article class="fac-card"
             role="listitem"
             data-type="{{ $slug }}"
             data-name="{{ strtolower($f->facility_name) }}"
             data-lat="{{ $f->latitude }}"
             data-lng="{{ $f->longitude }}"
             data-id="{{ $f->id }}"
             data-verified="{{ $verified ? 'true' : 'false' }}"
             data-distance="{{ $dist ?? 9999 }}"
             style="--type-color: {{ $typeColor }}"
             onclick="handleCardClick('{{ $f->id }}', '{{ route('public.care-map.profile', $f->id) }}', event)">

          <div class="fac-top">
            <div class="fac-icon {{ $slug }}" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $iconPath !!}</svg>
            </div>

            <div class="fac-main">
              <div class="fac-name-row">
                <h3 class="fac-name">{{ $f->facility_name }}</h3>
                @if($dist !== null)
                  <span class="fac-distance" aria-label="{{ $dist }} km away">{{ $dist }} km</span>
                @endif
              </div>

              <div class="fac-badges">
                <span class="badge badge-type-{{ $slug }}">{{ $typeLabel }}</span>

                @if($govVerif)
                  <span class="badge badge-gov" title="Government Verified">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Gov. Verified
                  </span>
                @elseif($verified)
                  <span class="badge badge-verified" title="License Verified">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Verified
                  </span>
                @endif

                @if($is24h)
                  <span class="badge badge-24h">24 / 7</span>
                @elseif($isOpen === true)
                  <span class="badge badge-open">Open Now</span>
                @elseif($isOpen === false)
                  <span class="badge badge-closed">Closed</span>
                @endif

                @if(($f->integration_status ?? '') === 'active')
                  <span class="badge badge-connected" title="Integrated with OpesCare">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Live
                  </span>
                @endif
              </div>
            </div>
          </div>

          <div class="fac-body">
            @if($locationStr)
              <div class="fac-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <span>{{ $locationStr }}</span>
              </div>
            @endif

            {{-- dialablePhone() nulls the literal 'N/A' the registry extract left
                 behind. Guarding on phone_primary is true for that string, which
                 rendered `tel:N/A` and a dead Call button on 1,680 of 2,465 rows. --}}
            @php $facPhone = $f->dialablePhone(); @endphp
            @if($facPhone)
              <div class="fac-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <a href="tel:{{ $facPhone }}" onclick="event.stopPropagation()">{{ $facPhone }}</a>
              </div>
            @endif

            @if($f->services && $f->services->isNotEmpty())
              <div class="fac-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span>{{ $f->services->take(3)->pluck('service_name')->implode(' · ') }}{{ $f->services->count() > 3 ? ' +'.($f->services->count()-3).' more' : '' }}</span>
              </div>
            @endif
          </div>

          <div class="fac-footer">
            @if($facPhone)
              <a href="tel:{{ $facPhone }}" class="btn-sm btn-call" onclick="event.stopPropagation()" aria-label="Call {{ $f->facility_name }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                Call
              </a>
            @endif

            @if($dirUrl)
              <a href="{{ $dirUrl }}" target="_blank" rel="noopener noreferrer" class="btn-sm btn-dir" onclick="event.stopPropagation()" aria-label="Get directions to {{ $f->facility_name }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                </svg>
                Directions
              </a>
            @endif

            <a href="{{ route('public.care-map.profile', $f->id) }}" class="btn-sm btn-view" onclick="event.stopPropagation()" aria-label="View profile for {{ $f->facility_name }}">
              View Profile
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
            </a>
          </div>
        </article>

      @empty
        <div class="empty-state" role="status">
          <div class="empty-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <p class="empty-title">No facilities found</p>
          <p class="empty-sub">Try a different search term or clear your filters to see all facilities.</p>
          <button class="btn-reset" onclick="resetAll()">Clear Filters</button>
        </div>
      @endforelse
    </div>
  </aside>

  {{-- Desktop Map Panel --}}
  <div class="map-panel" id="mapPanel" aria-label="Facility map">
    <div id="map" role="application" aria-label="Interactive map of health facilities"></div>
    <div class="map-controls">
      <button class="map-ctrl-btn" onclick="fitAllMarkers()" title="Fit all visible markers">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M15 3h6v6m-6 0 6-6M9 21H3v-6m6 0-6 6"/>
        </svg>
        Fit all
      </button>
      <button class="map-ctrl-btn" onclick="locateMe()" title="Locate me">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
        </svg>
        Locate me
      </button>
    </div>
  </div>
</div>

{{-- Mobile Map Overlay --}}
<div class="map-overlay" id="mapOverlay" role="dialog" aria-label="Facility map" aria-hidden="true">
  <div class="map-overlay-header">
    <span class="map-overlay-title">
      <svg class="overlay-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6"/></svg>
      Facility Map
    </span>
    <button class="btn-close-map" onclick="closeMobileMap()" aria-label="Close map">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      Close Map
    </button>
  </div>
  <div id="mapOverlayContainer">
    <div id="mapMobile" role="application" aria-label="Interactive map of health facilities" class="map-mobile-fill"></div>
  </div>
</div>

{{-- Mobile FAB --}}
<button class="fab-map-toggle" id="fabMapToggle" onclick="openMobileMap()" aria-label="Show map view">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6"/>
  </svg>
  Show Map
</button>

{{-- Disclaimer --}}
<div class="disclaimer-bar" role="note">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
  </svg>
  Information is provided for reference only. Availability and hours may change — always confirm with the facility before visiting. In emergencies, call your local emergency number.
</div>

@php
$_facilitiesJson = json_encode($facilities->map(function ($f) {
  return [
    'id'       => $f->id,
    'name'     => $f->facility_name,
    'type'     => strtolower(str_replace(' ', '_', $f->facility_type ?? 'other')),
    'city'     => $f->city,
    'address'  => $f->address,
    'phone'    => $f->dialablePhone(),
    'lat'      => (float)($f->latitude  ?? 0),
    'lng'      => (float)($f->longitude ?? 0),
    'verified' => in_array($f->verification_status ?? '', ['license_verified','government_verified']),
    'distance' => isset($f->distance) ? round($f->distance, 1) : null,
    'url'      => route('public.care-map.profile', $f->id),
  ];
}), JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script>
// ── Serialised Data ──────────────────────────────────────────────────────────
const FACILITIES = {!! $_facilitiesJson !!};

const TYPE_COLORS = {
  hospital:          '#0891B2',
  clinic:            '#059669',
  pharmacy:          '#D97706',
  laboratory:        '#7C3AED',
  blood_bank:        '#DC2626',
  diagnostic:        '#0F4C81',
  diagnostic_center: '#0F4C81',
  other:             '#64748B',
};

// ── Map State ────────────────────────────────────────────────────────────────
let mapDesktop = null, mapMobile = null;
let markersDesktop = {}, markersMobile = {};
let markerLayerDesktop, markerLayerMobile;
let mobileMapOpen = false;
let mobileMapInited = false;

function getTypeColor(type) {
  return TYPE_COLORS[type] || TYPE_COLORS.other;
}

function makeMarkerIcon(type, active = false) {
  const color = getTypeColor(type);
  const size  = active ? 38 : 30;
  const inner = active ? 10 : 8;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size + 8}" viewBox="0 0 ${size} ${size + 8}">
    <circle cx="${size/2}" cy="${size/2}" r="${size/2 - 1}" fill="${color}" stroke="#fff" stroke-width="2.5"/>
    <circle cx="${size/2}" cy="${size/2}" r="${inner / 2}" fill="rgba(255,255,255,.9)"/>
    <polygon points="${size/2 - 5},${size + 1} ${size/2 + 5},${size + 1} ${size/2},${size + 8}" fill="${color}"/>
  </svg>`;
  return L.divIcon({
    html: svg, className: '',
    iconSize: [size, size + 8],
    iconAnchor: [size / 2, size + 8],
    popupAnchor: [0, -(size + 6)],
  });
}

function buildPopupHtml(f) {
  const color = getTypeColor(f.type);
  const typeLabel = f.type.replace(/_/g, ' ');
  const dirUrl = f.lat && f.lng
    ? `https://www.google.com/maps/dir/?api=1&destination=${f.lat},${f.lng}`
    : null;
  return `
    <div class="popup-inner">
      <div class="popup-type-bar">
        <span class="popup-type-dot" style="background:${color}"></span>
        <span class="popup-type-label" style="color:${color}">${typeLabel}</span>
      </div>
      <div class="popup-name">${f.name}</div>
      ${f.city ? `<div class="popup-city">${f.city}</div>` : ''}
      <div class="popup-actions">
        <a href="${f.url}" class="popup-btn popup-btn-view">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
          View
        </a>
        ${dirUrl ? `<a href="${dirUrl}" target="_blank" rel="noopener" class="popup-btn popup-btn-dir">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
          Directions
        </a>` : ''}
      </div>
    </div>`;
}

function initMap(container, isDesktop) {
  const mapEl = document.getElementById(container);
  if (!mapEl) return null;

  const m = L.map(mapEl, { zoomControl: false }).setView([9.082, 8.675], 6);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd', maxZoom: 19,
  }).addTo(m);

  L.control.zoom({ position: 'topright' }).addTo(m);

  const layerGroup = L.layerGroup().addTo(m);

  if (isDesktop) {
    mapDesktop = m; markerLayerDesktop = layerGroup;
    markersDesktop = {};
    renderMarkers(FACILITIES, mapDesktop, markerLayerDesktop, markersDesktop);
  } else {
    mapMobile = m; markerLayerMobile = layerGroup;
    markersMobile = {};
    renderMarkers(getVisibleFacilities(), mapMobile, markerLayerMobile, markersMobile);
  }

  return m;
}

function renderMarkers(list, map, layer, markersObj) {
  if (!map) return;
  layer.clearLayers();
  Object.keys(markersObj).forEach(k => delete markersObj[k]);

  const valid = list.filter(f => f.lat && f.lng);
  valid.forEach(f => {
    const m = L.marker([f.lat, f.lng], { icon: makeMarkerIcon(f.type) })
      .bindPopup(buildPopupHtml(f), { maxWidth: 240, minWidth: 200 });

    m.on('click', () => {
      document.querySelectorAll('.fac-card').forEach(c => c.classList.remove('active'));
      const card = document.querySelector(`.fac-card[data-id="${f.id}"]`);
      if (card) {
        card.classList.add('active');
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });

    layer.addLayer(m);
    markersObj[f.id] = m;
  });

  if (valid.length > 0) {
    const bounds = L.latLngBounds(valid.map(f => [f.lat, f.lng]));
    map.fitBounds(bounds.pad(0.15));
  }
}

function highlightOnMap(id, mapRef, markersObj) {
  if (!mapRef || !markersObj[id]) return;
  const card = document.querySelector(`.fac-card[data-id="${id}"]`);
  const type = card?.dataset.type || 'other';

  Object.entries(markersObj).forEach(([mid, marker]) => {
    const t = document.querySelector(`.fac-card[data-id="${mid}"]`)?.dataset.type || 'other';
    marker.setIcon(makeMarkerIcon(t, false));
  });

  markersObj[id].setIcon(makeMarkerIcon(type, true));
  mapRef.setView(markersObj[id].getLatLng(), Math.max(mapRef.getZoom(), 13), { animate: true });
  markersObj[id].openPopup();
}

function fitAllMarkers() {
  const visible = getVisibleFacilities();
  const pts = visible.map(f => {
    const m = markersDesktop[f.id] || markersMobile[f.id];
    return m?.getLatLng();
  }).filter(Boolean);
  if (pts.length > 0 && mapDesktop) {
    mapDesktop.fitBounds(L.latLngBounds(pts).pad(0.1));
  }
}

// ── Mobile Map Overlay ────────────────────────────────────────────────────────
function openMobileMap() {
  const overlay = document.getElementById('mapOverlay');
  overlay.classList.add('visible');
  overlay.setAttribute('aria-hidden', 'false');
  mobileMapOpen = true;

  if (!mobileMapInited) {
    mobileMapInited = true;
    setTimeout(() => {
      initMap('mapMobile', false);
    }, 50);
  } else if (mapMobile) {
    setTimeout(() => mapMobile.invalidateSize(), 50);
  }
}

function closeMobileMap() {
  const overlay = document.getElementById('mapOverlay');
  overlay.classList.remove('visible');
  overlay.setAttribute('aria-hidden', 'true');
  mobileMapOpen = false;
}

// ── Geolocation ───────────────────────────────────────────────────────────────
function locateMe() {
  if (!navigator.geolocation) {
    alert(@json(__('public.care_map_js_geo_unsupported', [], app()->getLocale()) ?: 'Geolocation is not supported by your browser.'));
    return;
  }
  const btn = document.querySelector('.search-locate-btn');
  if (btn) { btn.style.opacity = '0.6'; btn.style.pointerEvents = 'none'; }

  navigator.geolocation.getCurrentPosition(
    pos => {
      const { latitude: lat, longitude: lng } = pos.coords;

      // Place user marker on both maps
      const userIcon = L.divIcon({
        html: `<div style="width:14px;height:14px;border-radius:50%;background:#0F4C81;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35)"></div>`,
        className: '', iconSize: [14, 14], iconAnchor: [7, 7],
      });

      [mapDesktop, mapMobile].forEach(m => {
        if (!m) return;
        L.marker([lat, lng], { icon: userIcon }).addTo(m).bindPopup('You are here').openPopup();
        m.setView([lat, lng], 13);
      });

      // Hide the location banner
      const banner = document.getElementById('locationBanner');
      if (banner) banner.style.display = 'none';

      if (btn) { btn.style.opacity = ''; btn.style.pointerEvents = ''; }
    },
    () => {
      if (btn) { btn.style.opacity = ''; btn.style.pointerEvents = ''; }
      alert(@json(__('public.care_map_js_geo_denied', [], app()->getLocale()) ?: 'Unable to retrieve your location. Please ensure location permissions are enabled.'));
    }
  );
}

// ── Card Click ────────────────────────────────────────────────────────────────
function handleCardClick(id, url, event) {
  if (event.target.closest('a, button')) return;
  event.preventDefault();

  document.querySelectorAll('.fac-card').forEach(c => c.classList.remove('active'));
  event.currentTarget.classList.add('active');

  highlightOnMap(id, mapDesktop, markersDesktop);
  if (mobileMapOpen) highlightOnMap(id, mapMobile, markersMobile);

  window.location.href = url;
}

// ── Search & Filter ───────────────────────────────────────────────────────────
let activeType   = 'all';
let searchQuery  = '';
let searchTimer;

function getVisibleFacilities() {
  const cards = document.querySelectorAll('.fac-card');
  const visible = [];
  cards.forEach(card => {
    if (card.style.display !== 'none') {
      const f = FACILITIES.find(x => x.id == card.dataset.id);
      if (f) visible.push(f);
    }
  });
  return visible;
}

function applyFilters() {
  const cards = document.querySelectorAll('.fac-card');
  let count = 0;
  const visibleFacs = [];

  cards.forEach(card => {
    const matchType  = activeType === 'all' || card.dataset.type === activeType;
    const matchQuery = !searchQuery || card.dataset.name.includes(searchQuery.toLowerCase());
    const show = matchType && matchQuery;
    card.style.display = show ? '' : 'none';
    if (show) {
      count++;
      const f = FACILITIES.find(x => x.id == card.dataset.id);
      if (f) visibleFacs.push(f);
    }
  });

  document.getElementById('countNum').textContent = count;

  // Update markers on both maps
  if (mapDesktop) renderMarkers(visibleFacs, mapDesktop, markerLayerDesktop, markersDesktop);
  if (mapMobile)  renderMarkers(visibleFacs, mapMobile,  markerLayerMobile,  markersMobile);

  // Empty state
  const list   = document.getElementById('resultsList');
  let emptyEl  = list.querySelector('.empty-state');
  if (count === 0 && !emptyEl) {
    list.insertAdjacentHTML('beforeend', `
      <div class="empty-state" role="status">
        <div class="empty-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
        </svg></div>
        <p class="empty-title">No facilities match</p>
        <p class="empty-sub">Try a different search term or clear your filters.</p>
        <button class="btn-reset" onclick="resetAll()">Clear All</button>
      </div>`);
  } else if (count > 0 && emptyEl) {
    emptyEl.remove();
  }
}

function filterBy(type, btn) {
  activeType = type;
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

function resetAll() {
  activeType  = 'all';
  searchQuery = '';
  document.querySelectorAll('#searchInputMobile, #searchInputDesktop').forEach(el => el.value = '');
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  document.querySelector('.chip-all').classList.add('active');
  applyFilters();
}

// Search inputs (mobile + desktop synced)
['searchInputMobile', 'searchInputDesktop'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchQuery = e.target.value.trim();
    // Sync the other input
    const otherId = id === 'searchInputMobile' ? 'searchInputDesktop' : 'searchInputMobile';
    const other   = document.getElementById(otherId);
    if (other) other.value = e.target.value;
    searchTimer = setTimeout(applyFilters, 250);
  });
});

// ── Sort ──────────────────────────────────────────────────────────────────────
function sortFacilities(by) {
  const list  = document.getElementById('resultsList');
  const cards = Array.from(list.querySelectorAll('.fac-card'));
  cards.sort((a, b) => {
    if (by === 'name')     return a.dataset.name.localeCompare(b.dataset.name);
    if (by === 'distance') return parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance);
    if (by === 'verified') return (b.dataset.verified === 'true') - (a.dataset.verified === 'true');
    if (by === 'type')     return a.dataset.type.localeCompare(b.dataset.type);
    return 0;
  });
  cards.forEach(c => list.appendChild(c));
}

// ── Keyboard: close map overlay with Escape ───────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && mobileMapOpen) closeMobileMap();
});

// ── Init ──────────────────────────────────────────────────────────────────────
window.addEventListener('load', () => {
  if (typeof L !== 'undefined') {
    // Init desktop map only if panel is visible
    const isDesktop = window.innerWidth >= 1024;
    if (isDesktop) initMap('map', true);
  }
});

// Reinit desktop map when viewport crosses breakpoint
let lastIsDesktop = window.innerWidth >= 1024;
window.addEventListener('resize', () => {
  const nowDesktop = window.innerWidth >= 1024;
  if (nowDesktop && !lastIsDesktop && !mapDesktop && typeof L !== 'undefined') {
    initMap('map', true);
  }
  if (mapDesktop) mapDesktop.invalidateSize();
  lastIsDesktop = nowDesktop;
});
</script>
</body>
</html>
