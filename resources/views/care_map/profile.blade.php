<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $facility->facility_name }} — OpesCare Care Map</title>
<meta name="description" content="{{ $facility->description ?? ($facility->facility_name . ' — ' . ucwords(str_replace('_',' ',$facility->facility_type ?? ''))) }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
<script src="{{ asset('js/leaflet.js') }}" defer></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --brand: #0F4C81; --brand-light: #1A6AAF;
    --teal: #0D9488; --teal-light: #14B8A6;
    --bg: #F8FAFC; --surface: #FFFFFF;
    --border: #E2E8F0; --border-soft: #F1F5F9;
    --text-primary: #0F172A; --text-secondary: #475569; --text-muted: #94A3B8;
    --radius: 0.625rem; --radius-lg: 1rem;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.07); --shadow: 0 4px 12px rgba(0,0,0,.08); --shadow-md: 0 8px 24px rgba(0,0,0,.10);
    --hospital: #0891B2; --hospital-bg: #ECFEFF;
    --clinic: #059669; --clinic-bg: #ECFDF5;
    --pharmacy: #D97706; --pharmacy-bg: #FFFBEB;
    --lab: #7C3AED; --lab-bg: #F5F3FF;
    --blood: #DC2626; --blood-bg: #FEF2F2;
    --diagnostic: #0F4C81; --diagnostic-bg: #EFF6FF;
    --other: #64748B; --other-bg: #F8FAFC;
  }

  @php
    $type      = strtolower(str_replace(' ', '_', $facility->facility_type ?? 'other'));
    $typeLabel = ucwords(str_replace('_', ' ', $type));
    $tc = [
      'hospital'   => '#0891B2',
      'clinic'     => '#059669',
      'pharmacy'   => '#D97706',
      'laboratory' => '#7C3AED',
      'blood_bank' => '#DC2626',
      'diagnostic' => '#0F4C81',
    ][$type] ?? '#64748B';
    $heroGradientEnd = [
      'hospital'   => '#0B7285',
      'clinic'     => '#065F46',
      'pharmacy'   => '#92400E',
      'laboratory' => '#4C1D95',
      'blood_bank' => '#7F1D1D',
      'diagnostic' => '#0B3D6E',
    ][$type] ?? '#1E293B';
    $verified    = in_array($facility->verification_status ?? '', ['license_verified','government_verified']);
    $govVerified = ($facility->verification_status ?? '') === 'government_verified';
    $hasLat      = (bool)$facility->latitude && (bool)$facility->longitude;
    $today       = strtolower(now()->format('l'));
    $dayMap      = ['sunday'=>0,'monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6];
    $todayNum    = $dayMap[$today] ?? 0;
  @endphp

  html, body { font-family: 'Inter', system-ui, sans-serif; color: var(--text-primary); background: var(--bg); }

  /* ── Back nav ───────────────────────────────────────── */
  .topbar {
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 0 1.25rem; height: 52px;
    display: flex; align-items: center; gap: 1rem;
    position: sticky; top: 0; z-index: 100;
    box-shadow: var(--shadow-sm);
  }
  .back-link {
    display: flex; align-items: center; gap: 0.375rem;
    text-decoration: none; color: var(--text-secondary);
    font-size: 0.83rem; font-weight: 500;
    padding: 0.35rem 0.625rem; border-radius: 0.5rem;
    transition: all 0.15s;
  }
  .back-link:hover { background: var(--bg); color: var(--text-primary); }
  .back-link svg { width: 14px; height: 14px; }
  .topbar-title { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .topbar-emergency-btn {
    display: flex; align-items: center; gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    background: #FEF2F2; color: #DC2626;
    border: 1.5px solid #FECACA; border-radius: 50px;
    font-size: 0.75rem; font-weight: 600;
    text-decoration: none; flex-shrink: 0;
  }
  .topbar-emergency-btn:hover { background: #DC2626; color: #fff; border-color: #DC2626; }

  /* ── Hero ───────────────────────────────────────────── */
  .hero {
    background: linear-gradient(135deg, {{ $tc }} 0%, {{ $heroGradientEnd }} 100%);
    padding: 2rem 1.5rem 1.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }
  .hero-inner { position: relative; max-width: 900px; margin: 0 auto; }
  .hero-type-badge {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 50px;
    font-size: 0.75rem; font-weight: 600;
    margin-bottom: 0.75rem;
    backdrop-filter: blur(4px);
  }
  .hero-name {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(1.4rem, 4vw, 2rem);
    font-weight: 700; line-height: 1.15;
    margin-bottom: 0.625rem;
  }
  .hero-meta { display: flex; flex-wrap: wrap; gap: 0.875rem; opacity: 0.9; font-size: 0.83rem; margin-bottom: 1.25rem; }
  .hero-meta-item { display: flex; align-items: center; gap: 0.4rem; }
  .hero-meta-item svg { width: 13px; height: 13px; opacity: 0.8; }

  /* verification */
  .hero-verified {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.25rem 0.625rem;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 50px; font-size: 0.72rem; font-weight: 600;
  }

  /* action bar */
  .action-bar {
    display: flex; gap: 0.625rem; flex-wrap: wrap;
  }
  .action-btn {
    display: flex; align-items: center; gap: 0.4rem;
    padding: 0.5rem 1rem;
    border-radius: 0.625rem;
    font-size: 0.82rem; font-weight: 600;
    cursor: pointer; text-decoration: none;
    border: 1.5px solid rgba(255,255,255,.4);
    background: rgba(255,255,255,.15);
    color: #fff; transition: all 0.15s;
    backdrop-filter: blur(4px);
  }
  .action-btn:hover { background: rgba(255,255,255,.28); border-color: rgba(255,255,255,.6); }
  .action-btn svg { width: 14px; height: 14px; }
  .action-btn.btn-call-hero { background: #fff; color: {{ $tc }}; border-color: #fff; }
  .action-btn.btn-call-hero:hover { background: rgba(255,255,255,.9); }

  /* ── Tab Nav ────────────────────────────────────────── */
  .tab-nav {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex; overflow-x: auto; scrollbar-width: none;
    padding: 0 1.25rem;
    position: sticky; top: 52px; z-index: 90;
  }
  .tab-nav::-webkit-scrollbar { display: none; }
  .tab-btn {
    display: flex; align-items: center; gap: 0.4rem;
    padding: 0.875rem 1rem;
    font-size: 0.82rem; font-weight: 500;
    color: var(--text-muted); cursor: pointer;
    border: none; background: transparent;
    border-bottom: 2.5px solid transparent;
    font-family: inherit; white-space: nowrap;
    transition: all 0.15s; margin-bottom: -1px;
    text-decoration: none;
  }
  .tab-btn:hover { color: var(--text-primary); }
  .tab-btn.active { color: var(--brand); border-bottom-color: var(--brand); font-weight: 600; }
  .tab-count {
    background: var(--bg); color: var(--text-muted);
    border-radius: 50px; padding: 0.1rem 0.4rem;
    font-size: 0.7rem; font-weight: 600; min-width: 18px; text-align: center;
  }
  .tab-btn.active .tab-count { background: #EFF6FF; color: var(--brand); }

  /* ── Content ─────────────────────────────────────────── */
  .content-wrap { max-width: 900px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
  .content-grid { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start; }

  .section { margin-bottom: 1.75rem; }
  .section-title {
    font-size: 0.78rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--text-muted);
    margin-bottom: 0.875rem;
    display: flex; align-items: center; gap: 0.5rem;
  }
  .section-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

  /* card base */
  .card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
  }
  .card-header {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border);
    font-size: 0.83rem; font-weight: 600; color: var(--text-primary);
    display: flex; align-items: center; justify-content: space-between;
  }
  .card-body { padding: 1rem; }

  /* info rows */
  .info-row {
    display: flex; align-items: flex-start; gap: 0.75rem;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--border-soft);
    font-size: 0.83rem;
  }
  .info-row:last-child { border-bottom: none; }
  .info-icon { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; color: var(--text-muted); }
  .info-label { color: var(--text-muted); font-size: 0.75rem; font-weight: 500; min-width: 80px; flex-shrink: 0; margin-top: 1px; }
  .info-value { color: var(--text-primary); font-weight: 500; flex: 1; line-height: 1.4; }
  .info-value a { color: var(--brand); text-decoration: none; }
  .info-value a:hover { text-decoration: underline; }

  /* services */
  .service-grid { display: flex; flex-direction: column; gap: 0.5rem; }
  .service-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.625rem 0.875rem;
    background: var(--bg); border-radius: 0.5rem;
    border: 1px solid var(--border);
    gap: 0.75rem;
  }
  .service-name { font-size: 0.83rem; font-weight: 500; color: var(--text-primary); flex: 1; }
  .service-meta { display: flex; align-items: center; gap: 0.4rem; }

  /* availability cards */
  .avail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.625rem; }
  .avail-card {
    padding: 0.75rem; border-radius: 0.625rem;
    border: 1.5px solid var(--border); background: var(--surface);
  }
  .avail-name { font-size: 0.82rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem; }
  .avail-detail { font-size: 0.73rem; color: var(--text-muted); margin-bottom: 0.5rem; }
  .avail-status { font-size: 0.72rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.3rem; }

  /* hours */
  .hours-grid { display: flex; flex-direction: column; gap: 0; }
  .hours-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.5rem 0; border-bottom: 1px solid var(--border-soft);
    font-size: 0.82rem;
  }
  .hours-row:last-child { border-bottom: none; }
  .hours-day { font-weight: 500; color: var(--text-secondary); min-width: 90px; }
  .hours-day.today { color: var(--brand); font-weight: 700; }
  .hours-time { color: var(--text-primary); font-weight: 500; }
  .hours-closed { color: var(--text-muted); font-style: italic; }
  .hours-24 { color: var(--clinic); font-weight: 600; }

  /* blood type grid */
  .blood-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
  .blood-item {
    display: flex; flex-direction: column; align-items: center;
    padding: 0.625rem 0.25rem;
    border-radius: 0.625rem; border: 1.5px solid var(--border);
    font-size: 0.78rem; gap: 0.3rem;
  }
  .blood-type { font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 700; }
  .blood-avail { font-size: 0.7rem; font-weight: 500; }
  .avail-available   { color: #16A34A; }
  .avail-low         { color: #D97706; }
  .avail-unavailable { color: #DC2626; }
  .avail-unknown     { color: var(--text-muted); }
  .blood-item.available   { background: #F0FDF4; border-color: #BBF7D0; }
  .blood-item.low_stock   { background: #FFFBEB; border-color: #FDE68A; }
  .blood-item.out_of_stock { background: #FEF2F2; border-color: #FECACA; }
  .blood-item.unknown     { background: var(--bg); }

  /* insurance */
  .insurance-grid { display: flex; flex-direction: column; gap: 0.5rem; }
  .ins-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.6rem 0.875rem; border-radius: 0.5rem;
    border: 1px solid var(--border); background: var(--bg);
    font-size: 0.82rem; gap: 0.75rem;
  }
  .ins-name { font-weight: 600; color: var(--text-primary); flex: 1; }
  .ins-badges { display: flex; gap: 0.3rem; flex-wrap: wrap; }

  /* badges */
  .badge {
    display: inline-flex; align-items: center; gap: 0.25rem;
    padding: 0.15rem 0.5rem; border-radius: 50px;
    font-size: 0.7rem; font-weight: 600;
  }
  .badge-success { background: #ECFDF5; color: #059669; }
  .badge-warning { background: #FFFBEB; color: #D97706; }
  .badge-neutral { background: var(--bg); color: var(--text-muted); border: 1px solid var(--border); }
  .badge-verified-full { background: #ECFDF5; color: #059669; }
  .badge-gov { background: #EFF6FF; color: var(--brand); }

  /* map */
  #facilityMap { width: 100%; height: 220px; border-radius: 0.625rem; overflow: hidden; }

  /* sidebar sticky */
  .sidebar-sticky { position: sticky; top: calc(52px + 46px + 1.5rem); }

  /* share dropdown */
  .share-wrap { position: relative; }
  .share-dropdown {
    display: none; position: absolute; right: 0; top: 100%; margin-top: 0.375rem;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); box-shadow: var(--shadow-md);
    min-width: 160px; overflow: hidden; z-index: 200;
  }
  .share-dropdown.open { display: block; }
  .share-item {
    display: flex; align-items: center; gap: 0.625rem;
    padding: 0.625rem 1rem; font-size: 0.82rem;
    color: var(--text-secondary); cursor: pointer;
    transition: background 0.1s; text-decoration: none;
  }
  .share-item:hover { background: var(--bg); color: var(--text-primary); }
  .share-item svg { width: 14px; height: 14px; }

  /* disclaimer */
  .disclaimer-bar {
    background: #FFFBEB; border: 1px solid #FDE68A;
    border-radius: 0.5rem; padding: 0.625rem 0.875rem;
    font-size: 0.75rem; color: #92400E;
    display: flex; align-items: flex-start; gap: 0.5rem;
    margin-bottom: 1.5rem; line-height: 1.45;
  }
  .disclaimer-bar svg { flex-shrink: 0; width: 14px; height: 14px; color: #D97706; margin-top: 1px; }

  /* tab panels */
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  /* empty */
  .empty { text-align: center; padding: 2rem 1rem; color: var(--text-muted); font-size: 0.83rem; }
  .empty svg { width: 32px; height: 32px; margin-bottom: 0.5rem; opacity: 0.35; display: block; margin-left: auto; margin-right: auto; }

  /* freshness */
  .freshness-fresh  { color: #059669; }
  .freshness-recent { color: #D97706; }
  .freshness-stale  { color: #DC2626; }

  @media (max-width: 700px) {
    .content-grid { grid-template-columns: 1fr; }
    .sidebar-sticky { position: static; }
    .hero { padding: 1.5rem 1rem 1.25rem; }
    .hero-name { font-size: 1.3rem; }
    .blood-grid { grid-template-columns: repeat(4, 1fr); }
    .avail-grid { grid-template-columns: 1fr 1fr; }
  }
</style>
</head>
<body>

@php
  $type     = strtolower(str_replace(' ', '_', $facility->facility_type ?? 'other'));
  $typeLabel = ucwords(str_replace('_', ' ', $type));
  $verified = in_array($facility->verification_status ?? '', ['license_verified','government_verified']);
  $govVerified = ($facility->verification_status ?? '') === 'government_verified';
  // 'N/A' sits in phone_primary on 1,571 of 1,863 rows — a NOT NULL placeholder
  // from the registry extract, not a number. Never render it, and never offer a
  // tel: link to it.
  $phone      = \App\Models\CareFacility::realValue($facility->phone_primary);
  $phone2     = \App\Models\CareFacility::realValue($facility->phone_secondary);
  // Claimed is its own, weaker fact than verified — see CareFacility::isClaimed().
  $claimed    = $facility->isClaimed();
  $typeColors = [
    'hospital'   => '#0891B2','clinic'=>'#059669','pharmacy'=>'#D97706',
    'laboratory' => '#7C3AED','blood_bank'=>'#DC2626','diagnostic'=>'#0F4C81','other'=>'#64748B',
  ];
  $tc = $typeColors[$type] ?? '#64748B';
  $today = strtolower(now()->format('l'));
  $dayMap = ['sunday'=>0,'monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6];
  $todayNum = $dayMap[$today] ?? 0;
  $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  $servicesCount = $facility->services?->count() ?? 0;
  $hasStock = ($facility->pharmacyStock?->count() ?? 0) > 0;
  $hasLab   = ($facility->labTests?->count() ?? 0) > 0;
  $hasBlood = ($facility->bloodAvailability?->count() ?? 0) > 0;
  $hasAvail = $hasStock || $hasLab || $hasBlood;
  $hasHours = ($facility->hours?->count() ?? 0) > 0;
  $hasIns   = ($facility->insurances?->count() ?? 0) > 0;
@endphp

{{-- ── Nav (dark, matches directory) ───────────────────────────────────── --}}
<style>
.prof-nav{background:#0A1628;border-bottom:1px solid rgba(255,255,255,.08);height:60px;display:flex;align-items:center;padding:0 1.375rem;gap:1rem;position:sticky;top:0;z-index:100}
.prof-nav-logo{display:flex;align-items:center;gap:.625rem;text-decoration:none;flex-shrink:0}
.prof-nav-mark{width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#1a6fb5,#0aab9a);display:flex;align-items:center;justify-content:center}
.prof-nav-mark svg{color:#fff}
.prof-nav-name{font-family:'Outfit',sans-serif;font-size:.9375rem;font-weight:700;color:#fff;line-height:1}
.prof-nav-sub{font-size:.55rem;color:rgba(255,255,255,.4);font-weight:500;text-transform:uppercase;letter-spacing:.07em;display:block}
.prof-nav-spacer{flex:1}
.prof-nav-back{display:flex;align-items:center;gap:.375rem;padding:.35rem .8rem;border:1.5px solid rgba(255,255,255,.14);border-radius:50px;font-size:.77rem;font-weight:500;color:rgba(255,255,255,.65);text-decoration:none;transition:border-color .15s}
.prof-nav-back:hover{border-color:rgba(255,255,255,.35);color:#fff}
.prof-nav-back svg{width:13px;height:13px}
.prof-nav-emergency{display:flex;align-items:center;gap:.375rem;padding:.375rem .875rem;background:rgba(220,38,38,.85);border-radius:50px;font-size:.77rem;font-weight:700;color:#fff;text-decoration:none;transition:background .15s}
.prof-nav-emergency:hover{background:#DC2626}
.prof-nav-emergency svg{width:12px;height:12px}
/* sidebar action buttons */
.sidebar-action-btn{width:100%;padding:.5rem .75rem;border:1.5px solid var(--border);border-radius:.5rem;background:transparent;cursor:pointer;font-size:.8rem;font-family:inherit;color:var(--text-secondary);display:flex;align-items:center;gap:.4rem;transition:all .15s}
.sidebar-action-btn:hover.report-btn{border-color:#D97706;color:#D97706}
.sidebar-action-btn:hover.claim-btn{border-color:var(--brand);color:var(--brand)}
/* today hours color classes */
.hours-open{color:#059669}.hours-closed-today{color:#DC2626}
/* hero badges (inline style was data-driven type colors — keep a class approach) */
.hero-badge-gov{display:inline-flex;align-items:center;gap:.25rem;padding:.22rem .65rem;border-radius:50px;font-size:.7rem;font-weight:700;background:rgba(253,224,71,.15);border:1px solid rgba(253,224,71,.3);color:#FDE047}
.hero-badge-verified{display:inline-flex;align-items:center;gap:.25rem;padding:.22rem .65rem;border-radius:50px;font-size:.7rem;font-weight:700;background:rgba(52,211,153,.15);border:1px solid rgba(52,211,153,.3);color:#6EE7B7}
.hero-badge-emergency{display:inline-flex;align-items:center;gap:.25rem;padding:.22rem .65rem;border-radius:50px;font-size:.7rem;font-weight:800;background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.35);color:#FCA5A5}
.hero-badge-claimed{display:inline-flex;align-items:center;gap:.25rem;padding:.22rem .65rem;border-radius:50px;font-size:.7rem;font-weight:700;background:rgba(255,255,255,.12);border:1px dashed rgba(255,255,255,.4);color:rgba(255,255,255,.9)}
.hero-badge-live{display:inline-flex;align-items:center;gap:.25rem;padding:.22rem .65rem;border-radius:50px;font-size:.7rem;font-weight:700;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);color:rgba(255,255,255,.85)}
.hero-badges-row{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem}
.hero-icon-wrap{width:62px;height:62px;border-radius:15px;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;backdrop-filter:blur(8px)}
.hero-info-wrap{display:flex;align-items:flex-start;gap:1.125rem;flex:1;min-width:0}
.hero-actions-col{display:flex;flex-direction:column;gap:.5rem;flex-shrink:0;align-items:stretch;min-width:160px}
.hero-inner-row{display:flex;align-items:flex-start;gap:1.25rem;flex-wrap:wrap}
.about-body{font-size:.85rem;line-height:1.65;color:var(--text-secondary)}
.sidebar-card-body-col{display:flex;flex-direction:column;gap:.5rem}
.info-value-red{color:#DC2626;font-weight:600}
.info-value-red a{color:#DC2626}
.lab-row-wrap{flex:1}
.lab-tat{font-size:.72rem;color:var(--text-muted);margin-top:.15rem}
.avail-price{font-size:.75rem;font-weight:600;color:var(--text-primary);margin-bottom:.35rem}
.freshness-tag{margin-left:.25rem;font-size:.68rem}
.badge-block{display:flex;gap:.4rem;padding:.5rem .75rem;border-radius:.5rem;font-size:.8rem}
.sidebar-card{margin-bottom:1rem}
.verified-ago{font-size:.72rem;color:var(--text-muted);margin-top:.5rem}
.card-body-hours{font-size:.9rem;font-weight:600;color:var(--text-primary)}
.ins-plan-name{font-weight:400;color:var(--text-muted)}
</style>
<nav class="prof-nav">
  <a href="{{ route('public.care-map') }}" class="prof-nav-logo">
    <div class="prof-nav-mark">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </div>
    <div>
      <span class="prof-nav-name">OpesCare</span>
      <span class="prof-nav-sub">Care Map</span>
    </div>
  </a>
  <div class="prof-nav-spacer"></div>
  <a href="{{ route('public.care-map') }}" class="prof-nav-back">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
    Back to map
  </a>
  <a href="{{ route('public.care-map.emergency') }}" class="prof-nav-emergency">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Emergency
  </a>
</nav>

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
@php
  $iconPaths = [
    'hospital'   => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/>',
    'clinic'     => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    'pharmacy'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M9 12h6m-3-3v6"/>',
    'laboratory' => '<path d="M14.5 2v17.5c0 1.4-1.1 2.5-2.5 2.5s-2.5-1.1-2.5-2.5V2"/><path d="M8.5 2h7"/>',
    'blood_bank' => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
    'diagnostic' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
  ];
  $heroIcon = $iconPaths[$type] ?? '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>';
@endphp
<div class="hero" style="background:linear-gradient(135deg,{{ $tc }} 0%,{{ $heroGradientEnd }} 100%)">
  <div class="hero-inner hero-inner-row">

    {{-- Icon + Info --}}
    <div class="hero-info-wrap">
      <div class="hero-icon-wrap">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">{!! $heroIcon !!}</svg>
      </div>
      <div style="flex:1;min-width:0">
        <div class="hero-type-badge">
          {{ $typeLabel }}
          @if($verified) &nbsp;·&nbsp;<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          {{ $govVerified ? 'Government Verified' : 'Verified' }}
          @endif
        </div>
        <h1 class="hero-name">{{ $facility->facility_name }}</h1>
        <div class="hero-meta">
          @if($facility->city || $facility->address)
            <div class="hero-meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              {{ collect([$facility->address, $facility->city, $facility->region])->filter()->implode(', ') }}
            </div>
          @endif
          @if($phone)
            <div class="hero-meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              {{ $phone }}
            </div>
          @endif
          @if($facility->license_number)
            <div class="hero-meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/></svg>
              Lic: {{ $facility->license_number }}
            </div>
          @endif
        </div>
        {{-- Verification + status badges --}}
        <div class="hero-badges-row">
          @if($govVerified)
            <span class="hero-badge-gov">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Gov. Verified
            </span>
          @elseif($verified)
            <span class="hero-badge-verified">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified
            </span>
          @endif
          @if($claimed)
            <span class="hero-badge-claimed" title="{{ __('caremap_claim.not_verified_note') }}">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>{{ __('caremap_claim.badge_claimed') }}
            </span>
          @endif
          @if($facility->emergency_contact)
            <span class="hero-badge-emergency">24 / 7 Emergency</span>
          @endif
          @if(($facility->integration_status ?? '') === 'active')
            <span class="hero-badge-live">OpesCare Live</span>
          @endif
        </div>
      </div>
    </div>

    {{-- Hero action buttons --}}
    <div class="action-bar hero-actions-col">
      @if($phone)
        <a href="tel:{{ $phone }}" class="action-btn btn-call-hero">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Call facility
        </a>
      @endif
      @if($facility->latitude && $facility->longitude)
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $facility->latitude }},{{ $facility->longitude }}" target="_blank" rel="noopener" class="action-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
          Get directions
        </a>
      @endif
      @if($facility->website)
        <a href="{{ $facility->website }}" target="_blank" rel="noopener" class="action-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          Website
        </a>
      @endif
      <div class="share-wrap">
        <button class="action-btn" onclick="toggleShare(this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
          Share
        </button>
        <div class="share-dropdown" id="shareDropdown">
          <a class="share-item" href="https://wa.me/?text={{ urlencode($facility->facility_name . ' — ' . url()->current()) }}" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            WhatsApp
          </a>
          <div class="share-item" onclick="copyLink()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Copy link
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Tab nav --}}
<nav class="tab-nav" id="tabNav">
  <button class="tab-btn active" data-tab="overview" onclick="switchTab('overview', this)">Overview</button>
  @if($servicesCount > 0)
    <button class="tab-btn" data-tab="services" onclick="switchTab('services', this)">
      Services <span class="tab-count">{{ $servicesCount }}</span>
    </button>
  @endif
  @if($hasAvail)
    <button class="tab-btn" data-tab="availability" onclick="switchTab('availability', this)">
      Availability
      @php $availCount = ($facility->pharmacyStock?->count() ?? 0) + ($facility->labTests?->count() ?? 0) + ($facility->bloodAvailability?->count() ?? 0); @endphp
      <span class="tab-count">{{ $availCount }}</span>
    </button>
  @endif
  @if($hasHours)
    <button class="tab-btn" data-tab="hours" onclick="switchTab('hours', this)">Hours</button>
  @endif
  @if($hasIns)
    <button class="tab-btn" data-tab="insurance" onclick="switchTab('insurance', this)">
      Insurance <span class="tab-count">{{ $facility->insurances?->count() }}</span>
    </button>
  @endif
</nav>

{{-- Content --}}
<div class="content-wrap">

  <div class="disclaimer-bar">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Information is provided in good faith by the facility or submitted by users. Verify hours, services, and availability directly with the facility before your visit.
  </div>

  <div class="content-grid">
    {{-- Main column --}}
    <div>

      {{-- OVERVIEW TAB --}}
      <div class="tab-panel active" id="panel-overview">

        @if($facility->description)
          <div class="section">
            <div class="section-title">About</div>
            <div class="card">
              <div class="card-body about-body">
                {{ $facility->description }}
              </div>
            </div>
          </div>
        @endif

        <div class="section">
          <div class="section-title">Contact & Location</div>
          <div class="card">
            <div class="card-body">
              @if($phone)
                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.29 6.29l.61-.61a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  <span class="info-label">Phone</span>
                  <span class="info-value"><a href="tel:{{ $phone }}">{{ $phone }}</a>
                    @if($phone2) · <a href="tel:{{ $phone2 }}">{{ $phone2 }}</a>@endif
                  </span>
                </div>
              @endif
              @if($facility->email)
                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  <span class="info-label">Email</span>
                  <span class="info-value"><a href="mailto:{{ $facility->email }}">{{ $facility->email }}</a></span>
                </div>
              @endif
              @if($facility->website)
                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                  <span class="info-label">Website</span>
                  <span class="info-value"><a href="{{ $facility->website }}" target="_blank" rel="noopener">{{ $facility->website }}</a></span>
                </div>
              @endif
              @if($facility->address || $facility->city)
                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <span class="info-label">Address</span>
                  <span class="info-value">{{ collect([$facility->address, $facility->city, $facility->region, $facility->country_code])->filter()->implode(', ') }}</span>
                </div>
              @endif
              @if($facility->emergency_contact)
                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <span class="info-label">Emergency</span>
                  <span class="info-value info-value-red"><a href="tel:{{ $facility->emergency_contact }}">{{ $facility->emergency_contact }}</a></span>
                </div>
              @endif
            </div>
          </div>
        </div>

        @if($facility->latitude && $facility->longitude)
          <div class="section">
            <div class="section-title">Location</div>
            <div id="facilityMap"></div>
          </div>
        @endif
      </div>

      {{-- SERVICES TAB --}}
      @if($servicesCount > 0)
      <div class="tab-panel" id="panel-services">
        <div class="section">
          <div class="section-title">Services Offered</div>
          @php $grouped = $facility->services->groupBy('service_category'); @endphp
          @foreach($grouped as $cat => $services)
            <div class="section">
              <div class="section-title">{{ ucwords($cat ?? 'General') }}</div>
              <div class="service-grid">
                @foreach($services as $svc)
                  <div class="service-item">
                    <span class="service-name">{{ $svc->service_name }}</span>
                    <div class="service-meta">
                      @if($svc->telemedicine_available)
                        <span class="badge badge-neutral" title="Telemedicine available">
                          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                          Telemedicine
                        </span>
                      @endif
                      @if($svc->walk_in_allowed)
                        <span class="badge badge-success">Walk-in</span>
                      @endif
                      @if($svc->appointment_required)
                        <span class="badge badge-neutral">Appointment</span>
                      @endif
                      @if($svc->price_range)
                        <span class="badge badge-neutral">{{ ucfirst($svc->price_range) }} cost</span>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
      @endif

      {{-- AVAILABILITY TAB --}}
      @if($hasAvail)
      <div class="tab-panel" id="panel-availability">

        @if($hasStock)
          <div class="section">
            <div class="section-title">Medicine / Pharmacy Stock</div>
            <div class="avail-grid">
              @foreach($facility->pharmacyStock as $item)
                @php
                  $statusClass = ['reported_available'=>'avail-available','low_stock'=>'avail-low','out_of_stock'=>'avail-unavailable'][$item->availability_status] ?? 'avail-unknown';
                  $statusLabel = ['reported_available'=>'In stock','low_stock'=>'Low stock','out_of_stock'=>'Out of stock','unknown'=>'Unknown'][$item->availability_status] ?? 'Unknown';
                  $freshClass  = ['fresh'=>'freshness-fresh','recent'=>'freshness-recent','stale'=>'freshness-stale'][$item->freshness_status] ?? '';
                @endphp
                <div class="avail-card">
                  <div class="avail-name">{{ $item->medicine_name }}</div>
                  <div class="avail-detail">
                    {{ $item->generic_name ? $item->generic_name . ' · ' : '' }}{{ $item->strength ?? '' }}
                    @if($item->form) · {{ $item->form }}@endif
                  </div>
                  @if($item->price)
                    <div class="avail-price">{{ $item->currency ?? 'XAF' }} {{ number_format($item->price, 0) }}</div>
                  @endif
                  <div class="avail-status {{ $statusClass }}">
                    <svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                    {{ $statusLabel }}
                    @if($item->freshness_status)
                      <span class="{{ $freshClass }} freshness-tag">· {{ ucfirst($item->freshness_status) }}</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        @if($hasLab)
          <div class="section">
            <div class="section-title">Laboratory Tests</div>
            <div class="service-grid">
              @foreach($facility->labTests as $test)
                @php $tsClass = ['reported_available'=>'avail-available','unavailable'=>'avail-unavailable'][$test->availability_status] ?? 'avail-unknown'; @endphp
                <div class="service-item">
                  <div class="lab-row-wrap">
                    <div class="service-name">{{ $test->test_name }}</div>
                    @if($test->turnaround_time)
                      <div class="lab-tat">TAT: {{ $test->turnaround_time }}
                        @if($test->home_sample_collection_available) · Home collection available @endif
                      </div>
                    @endif
                  </div>
                  <div class="service-meta">
                    @if($test->price)
                      <span class="avail-price">{{ $test->currency ?? 'XAF' }} {{ number_format($test->price, 0) }}</span>
                    @endif
                    <span class="avail-status {{ $tsClass }}">
                      <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                      {{ $test->availability_status === 'reported_available' ? 'Available' : ucfirst($test->availability_status ?? 'Unknown') }}
                    </span>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        @if($hasBlood)
          <div class="section">
            <div class="section-title">Blood Availability</div>
            <div class="blood-grid">
              @foreach($facility->bloodAvailability as $blood)
                @php $bClass = ['reported_available'=>'available','low_stock'=>'low_stock','out_of_stock'=>'out_of_stock'][$blood->availability_status] ?? 'unknown'; @endphp
                <div class="blood-item {{ $bClass }}">
                  <span class="blood-type">{{ $blood->blood_group }}</span>
                  <span class="avail-status avail-{{ $bClass === 'available' ? 'available' : ($bClass === 'low_stock' ? 'low' : ($bClass === 'out_of_stock' ? 'unavailable' : 'unknown')) }}" style="font-size:.68rem">
                    {{ $bClass === 'available' ? 'Available' : ($bClass === 'low_stock' ? 'Low' : ($bClass === 'out_of_stock' ? 'Out' : '—')) }}
                  </span>
                </div>
              @endforeach
            </div>
          </div>
        @endif

      </div>
      @endif

      {{-- HOURS TAB --}}
      @if($hasHours)
      <div class="tab-panel" id="panel-hours">
        <div class="section">
          <div class="section-title">Operating Hours</div>
          <div class="card">
            <div class="card-body">
              @php $hoursMap = $facility->hours->keyBy('day_of_week'); @endphp
              <div class="hours-grid">
                @for($d = 0; $d < 7; $d++)
                  @php $h = $hoursMap->get($d); $isToday = $d === $todayNum; @endphp
                  <div class="hours-row">
                    <span class="hours-day{{ $isToday ? ' today' : '' }}">{{ $days[$d] }}{{ $isToday ? ' (today)' : '' }}</span>
                    @if(!$h)
                      <span class="hours-closed">No data</span>
                    @elseif($h->is_24_hours)
                      <span class="hours-24">Open 24 hours</span>
                    @elseif($h->is_closed)
                      <span class="hours-closed">Closed</span>
                    @else
                      <span class="hours-time">{{ \Carbon\Carbon::parse($h->opens_at)->format('g:i A') }} – {{ \Carbon\Carbon::parse($h->closes_at)->format('g:i A') }}</span>
                    @endif
                  </div>
                @endfor
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- INSURANCE TAB --}}
      @if($hasIns)
      <div class="tab-panel" id="panel-insurance">
        <div class="section">
          <div class="section-title">Accepted Insurance & Coverage</div>
          <div class="insurance-grid">
            @foreach($facility->insurances as $ins)
              <div class="ins-item">
                <div class="ins-name">
                  {{ $ins->insurance_name }}
                  @if($ins->plan_name) <span class="ins-plan-name">· {{ $ins->plan_name }}</span>@endif
                </div>
                <div class="ins-badges">
                  @if($ins->cashless_available) <span class="badge badge-success">Cashless</span>@endif
                  @if($ins->preauthorization_required) <span class="badge badge-warning">Pre-auth required</span>@endif
                  @if($ins->claim_supported) <span class="badge badge-neutral">Claims supported</span>@endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
      @endif

    </div>{{-- /main col --}}

    {{-- Sidebar --}}
    <div class="sidebar-sticky">

      {{-- Verification card --}}
      <div class="card sidebar-card">
        <div class="card-header">Verification Status</div>
        <div class="card-body">
          @if($govVerified)
            <div class="badge badge-gov badge-block">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Government Verified
            </div>
          @elseif($verified)
            <div class="badge badge-verified-full badge-block">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              License Verified
            </div>
          @else
            <div class="badge badge-neutral badge-block">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Self-Reported
            </div>
          @endif
          @if($facility->last_verified_at)
            <div class="verified-ago">
              Last verified {{ \Carbon\Carbon::parse($facility->last_verified_at)->diffForHumans() }}
            </div>
          @endif
        </div>
      </div>

      {{-- Today's hours quick view --}}
      @if($hasHours)
        @php $todayHours = $facility->hours->firstWhere('day_of_week', $todayNum); @endphp
        @if($todayHours)
          <div class="card sidebar-card">
            <div class="card-header">Today's Hours</div>
            <div class="card-body card-body-hours">
              @if($todayHours->is_24_hours)
                <span class="hours-open">Open 24 hours</span>
              @elseif($todayHours->is_closed)
                <span class="hours-closed-today">Closed today</span>
              @else
                {{ \Carbon\Carbon::parse($todayHours->opens_at)->format('g:i A') }} – {{ \Carbon\Carbon::parse($todayHours->closes_at)->format('g:i A') }}
              @endif
            </div>
          </div>
        @endif
      @endif

      {{-- Report / Claim --}}
      <div class="card">
        <div class="card-header">This Listing</div>
        <div class="card-body sidebar-card-body-col">
          <button onclick="showReport()" class="sidebar-action-btn report-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Report inaccurate information
          </button>
          @unless($claimed)
            <a href="{{ route('public.care-map.claim', $facility->id) }}" class="sidebar-action-btn claim-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              {{ __('caremap_claim.claim_heading') }}
            </a>
          @endunless
        </div>
      </div>

    </div>
  </div>{{-- /grid --}}
</div>{{-- /content-wrap --}}

<script>
// Tab switching
function switchTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  const panel = document.getElementById('panel-' + id);
  if (panel) panel.classList.add('active');
  btn.classList.add('active');
  if (id === 'overview' && facilityMap) setTimeout(() => facilityMap.invalidateSize(), 50);
}

// Share
function toggleShare(btn) {
  const dd = document.getElementById('shareDropdown');
  dd.classList.toggle('open');
  document.addEventListener('click', function close(e) {
    if (!btn.parentElement.contains(e.target)) { dd.classList.remove('open'); document.removeEventListener('click', close); }
  }, { once: false });
}
function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(() => {
    const el = document.createElement('div');
    el.textContent = @json(__('public.care_map_js_link_copied', [], app()->getLocale()) ?: 'Link copied!');
    el.style.cssText = 'position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);background:#0F172A;color:#fff;padding:.5rem 1rem;border-radius:.5rem;font-size:.82rem;z-index:9999;font-family:Inter,sans-serif';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2200);
  });
  document.getElementById('shareDropdown').classList.remove('open');
}

// ── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  el.style.display = 'flex';
  requestAnimationFrame(() => el.classList.add('modal-visible'));
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  const el = document.getElementById(id);
  el.classList.remove('modal-visible');
  el.addEventListener('transitionend', () => { el.style.display = 'none'; document.body.style.overflow = ''; }, { once: true });
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeModal('modalReport');
  }
});

// ── Report ────────────────────────────────────────────────────────────────
function showReport() { openModal('modalReport'); }

document.getElementById('formReport').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn  = document.getElementById('reportSubmitBtn');
  const data = Object.fromEntries(new FormData(this));
  btn.disabled = true; btn.textContent = 'Submitting…';

  try {
    const res = await fetch('/api/v1/facilities/{{ $facility->id }}/report', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '', 'Accept': 'application/json' },
      body: JSON.stringify(data),
    });
    const json = await res.json();
    if (res.ok) {
      document.getElementById('reportFormBody').innerHTML = `
        <div style="text-align:center;padding:2rem 0">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5" style="margin:0 auto 1rem;display:block"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
          <p style="font-weight:700;color:#0F172A;margin-bottom:.375rem">Report submitted</p>
          <p style="font-size:.82rem;color:#64748B">Our team will review the listing. Thank you for helping keep Care Map accurate.</p>
        </div>`;
      setTimeout(() => closeModal('modalReport'), 2800);
    } else {
      btn.disabled = false; btn.textContent = @json(__('public.care_map_js_submit_report', [], app()->getLocale()) ?: 'Submit report');
      alert(json.message ?? @json(__('public.care_map_js_generic_error', [], app()->getLocale()) ?: 'Something went wrong. Please try again.'));
    }
  } catch {
    btn.disabled = false; btn.textContent = @json(__('public.care_map_js_submit_report', [], app()->getLocale()) ?: 'Submit report');
    alert(@json(__('public.care_map_js_network_error', [], app()->getLocale()) ?: 'Network error. Please check your connection and try again.'));
  }
});

// Claim is now a page, not a modal — see route('public.care-map.claim').

// Map
let facilityMap;
@if($facility->latitude && $facility->longitude)
window.addEventListener('load', () => {
  if (typeof L === 'undefined') return;
  facilityMap = L.map('facilityMap', { zoomControl: true, scrollWheelZoom: false })
    .setView([{{ $facility->latitude }}, {{ $facility->longitude }}], 15);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OSM &copy; CARTO', subdomains: 'abcd', maxZoom: 19
  }).addTo(facilityMap);

  const color = '{{ $tc }}';
  const icon = L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" width="34" height="42" viewBox="0 0 34 42">
      <circle cx="17" cy="17" r="16" fill="${color}" stroke="#fff" stroke-width="3"/>
      <circle cx="17" cy="17" r="7" fill="#fff" opacity=".9"/>
      <polygon points="13,34 21,34 17,42" fill="${color}"/>
    </svg>`,
    className: '', iconSize: [34, 42], iconAnchor: [17, 42], popupAnchor: [0, -42]
  });

  L.marker([{{ $facility->latitude }}, {{ $facility->longitude }}], { icon })
    .addTo(facilityMap)
    .bindPopup('<strong>{{ addslashes($facility->facility_name) }}</strong><br><small>{{ addslashes($facility->city ?? '') }}</small>')
    .openPopup();
});
@endif
</script>

{{-- ── Modal shared styles ──────────────────────────────────────────────── --}}
<style>
.modal-backdrop {
  display: none;
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(15,23,42,.55);
  backdrop-filter: blur(4px);
  align-items: center; justify-content: center;
  padding: 1rem;
  opacity: 0; transition: opacity 0.2s;
}
.modal-backdrop.modal-visible { opacity: 1; }
.modal-box {
  background: #fff; border-radius: 1rem;
  width: 100%; max-width: 480px;
  box-shadow: 0 20px 48px rgba(0,0,0,.22), 0 0 0 1px rgba(0,0,0,.05);
  overflow: hidden;
  transform: translateY(12px); transition: transform 0.2s;
}
.modal-backdrop.modal-visible .modal-box { transform: translateY(0); }
.modal-hdr {
  display: flex; align-items: center; gap: .75rem;
  padding: 1.125rem 1.375rem;
  border-bottom: 1px solid #E2E8F0;
}
.modal-hdr-icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.modal-hdr-icon svg { width: 17px; height: 17px; }
.modal-title { font-size: .9375rem; font-weight: 700; color: #0F172A; flex: 1; }
.modal-close {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: #F1F5F9; border: none; cursor: pointer;
  color: #64748B; transition: all .15s;
}
.modal-close:hover { background: #E2E8F0; color: #0F172A; }
.modal-close svg { width: 14px; height: 14px; }
.modal-body { padding: 1.25rem 1.375rem; }
.modal-label { display: block; font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: .375rem; }
.modal-select, .modal-textarea, .modal-input {
  width: 100%; font-family: 'Inter', sans-serif; font-size: .85rem;
  border: 1.5px solid #E2E8F0; border-radius: .5rem;
  color: #0F172A; background: #F8FAFC;
  outline: none; transition: border-color .15s, box-shadow .15s;
}
.modal-select { padding: .5rem .75rem; cursor: pointer; }
.modal-textarea { padding: .625rem .75rem; resize: vertical; min-height: 90px; line-height: 1.5; }
.modal-input { padding: .5rem .75rem; }
.modal-select:focus, .modal-textarea:focus, .modal-input:focus {
  border-color: #0F4C81; box-shadow: 0 0 0 3px rgba(15,76,129,.1); background: #fff;
}
.modal-field { margin-bottom: 1rem; }
.modal-hint { font-size: .72rem; color: #94A3B8; margin-top: .3rem; }
.modal-ftr {
  display: flex; gap: .625rem; justify-content: flex-end;
  padding: 1rem 1.375rem;
  border-top: 1px solid #F1F5F9;
  background: #F8FAFC;
}
.modal-btn {
  padding: .5rem 1.25rem; border-radius: .5rem;
  font-size: .82rem; font-weight: 600; font-family: 'Inter', sans-serif;
  cursor: pointer; border: none; transition: all .15s;
}
.modal-btn-cancel { background: #fff; color: #64748B; border: 1.5px solid #E2E8F0; }
.modal-btn-cancel:hover { border-color: #94A3B8; color: #0F172A; }
.modal-btn-report { background: #D97706; color: #fff; }
.modal-btn-report:hover { background: #B45309; }
.modal-btn-claim { background: #0F4C81; color: #fff; }
.modal-btn-claim:hover { background: #1A6AAF; }
.modal-btn:disabled { opacity: .6; cursor: not-allowed; }
.modal-auth-gate {
  text-align: center; padding: 1.5rem 1.375rem 1.25rem;
}
.modal-auth-gate svg { width: 44px; height: 44px; margin: 0 auto .875rem; display: block; }
.modal-auth-gate h3 { font-size: .9375rem; font-weight: 700; color: #0F172A; margin-bottom: .375rem; }
.modal-auth-gate p { font-size: .82rem; color: #64748B; line-height: 1.55; margin-bottom: 1.125rem; }
.modal-auth-gate .auth-btns { display: flex; gap: .5rem; justify-content: center; }
.modal-auth-gate .auth-btn {
  padding: .5rem 1.25rem; border-radius: .5rem;
  font-size: .82rem; font-weight: 600;
  cursor: pointer; text-decoration: none;
  display: inline-flex; align-items: center; gap: .35rem;
  transition: all .15s;
}
.auth-btn-primary { background: #0F4C81; color: #fff; border: none; }
.auth-btn-primary:hover { background: #1A6AAF; }
.auth-btn-secondary { background: #fff; color: #475569; border: 1.5px solid #E2E8F0; }
.auth-btn-secondary:hover { border-color: #94A3B8; color: #0F172A; }
.modal-hdr-icon--report { background:#FFFBEB; color:#D97706; }
.modal-hdr-icon--claim  { background:#EFF6FF; color:#0F4C81; }
</style>

{{-- ── Report Modal ─────────────────────────────────────────────────────── --}}
<div id="modalReport" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle"
     onclick="if(event.target===this)closeModal('modalReport')">
  <div class="modal-box">
    <div class="modal-hdr">
      <div class="modal-hdr-icon modal-hdr-icon--report">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <h2 class="modal-title" id="reportModalTitle">Report inaccurate information</h2>
      <button class="modal-close" onclick="closeModal('modalReport')" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div id="reportFormBody">
      <form id="formReport">
        @csrf
        <div class="modal-body">
          <p style="font-size:.82rem;color:#64748B;margin-bottom:1rem;line-height:1.55">
            Found something wrong with <strong style="color:#0F172A">{{ $facility->facility_name }}</strong>?
            Tell us what needs correcting and we'll review it.
          </p>

          <div class="modal-field">
            <label class="modal-label" for="report_type">What's incorrect?</label>
            <select id="report_type" name="report_type" class="modal-select" required>
              <option value="">Select a reason…</option>
              <option value="wrong_phone">Wrong phone number</option>
              <option value="wrong_address">Wrong address or location</option>
              <option value="wrong_hours">Incorrect operating hours</option>
              <option value="wrong_services">Services listed are inaccurate</option>
              <option value="closed_permanently">Facility is permanently closed</option>
              <option value="other">Other issue</option>
            </select>
          </div>

          <div class="modal-field">
            <label class="modal-label" for="description">Tell us more <span style="font-weight:400;color:#94A3B8">(optional)</span></label>
            <textarea id="description" name="description" class="modal-textarea" placeholder="Describe what's wrong and what the correct information should be…"></textarea>
            <div class="modal-hint">Your report is anonymous unless you are signed in.</div>
          </div>
        </div>
        <div class="modal-ftr">
          <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('modalReport')">Cancel</button>
          <button type="submit" id="reportSubmitBtn" class="modal-btn modal-btn-report">Submit report</button>
        </div>
      </form>
    </div>
  </div>
</div>


</body>
</html>
