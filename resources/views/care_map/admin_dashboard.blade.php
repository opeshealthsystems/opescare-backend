<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OpesCare — Care Map Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/portal.css') }}">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --brand:#0F4C81;--brand-hover:#0a3560;--brand-subtle:#EFF6FF;
  --bg:#F1F5F9;--surface:#fff;--border:#E2E8F0;
  --text-primary:#0F172A;--text-secondary:#475569;--text-muted:#94A3B8;
  --radius:0.5rem;--radius-lg:0.75rem;--radius-xl:1rem;
  --shadow-sm:0 1px 3px rgba(0,0,0,.07);--shadow:0 4px 12px rgba(0,0,0,.08);
}
html,body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text-primary);-webkit-font-smoothing:antialiased}
.page-topbar{background:#0A1628;border-bottom:1px solid rgba(255,255,255,.08);padding:0 1.5rem;height:60px;display:flex;align-items:center;gap:1rem}
.page-topbar-logo{display:flex;align-items:center;gap:.625rem;text-decoration:none}
.page-topbar-mark{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#1a6fb5,#0aab9a);display:flex;align-items:center;justify-content:center}
.page-topbar-mark i{color:#fff;width:16px;height:16px}
.page-topbar-name{font-family:'Outfit',sans-serif;font-size:1rem;font-weight:700;color:#fff}
.page-topbar-tag{display:block;font-size:.52rem;color:rgba(255,255,255,.4);font-weight:500;text-transform:uppercase;letter-spacing:.07em}
.page-topbar-spacer{flex:1}
.page-topbar-user{font-size:.78rem;color:rgba(255,255,255,.5)}

.page-wrap{max-width:1100px;margin:0 auto;padding:1.5rem 1.25rem 3rem}

.page-head{margin-bottom:1.75rem}
.page-head h1{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:.25rem}
.page-head p{font-size:.875rem;color:var(--text-muted)}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.75rem}
.stat-card{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-xl);padding:1.25rem}
.stat-card__value{font-family:'Outfit',sans-serif;font-size:2rem;font-weight:700;color:var(--brand);margin-bottom:.25rem}
.stat-card__label{font-size:.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600}

.content-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem}

.panel{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-xl);overflow:hidden}
.panel--full{grid-column:1/-1}
.panel__head{padding:.875rem 1.125rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.panel__title{font-size:.875rem;font-weight:700;color:var(--text-primary)}
.panel__sub{font-size:.75rem;color:var(--text-muted)}
.panel__body{padding:.875rem 1.125rem}

.list-row{display:flex;align-items:center;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid var(--border);gap:.75rem}
.list-row:last-child{border-bottom:none}
.list-row__main{flex:1;min-width:0}
.list-row__name{font-size:.875rem;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.list-row__sub{font-size:.75rem;color:var(--text-muted);margin-top:.125rem}
.list-row__actions{display:flex;align-items:center;gap:.5rem;flex-shrink:0}

.badge-pill{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:999px;font-size:.68rem;font-weight:700}
.badge-pill--warn{background:rgba(245,158,11,.1);color:#D97706;border:1px solid rgba(245,158,11,.25)}
.badge-pill--danger{background:rgba(239,68,68,.12);color:#DC2626;border:1px solid rgba(239,68,68,.25)}
.badge-pill--ok{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0}

.btn-action{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .75rem;background:transparent;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.75rem;font-weight:600;color:var(--text-secondary);cursor:pointer;font-family:inherit;text-decoration:none;transition:all .15s}
.btn-action:hover{border-color:var(--brand);color:var(--brand)}
.btn-action i{width:12px;height:12px}

.empty-row{text-align:center;padding:2rem 1rem;font-size:.82rem;color:var(--text-muted)}

@media(max-width:768px){.content-grid{grid-template-columns:1fr}.panel--full{grid-column:auto}.stat-grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>

<header class="page-topbar">
  <a href="{{ route('public.care-map') }}" class="page-topbar-logo">
    <div class="page-topbar-mark"><i data-lucide="heart-pulse"></i></div>
    <div>
      <span class="page-topbar-name">OpesCare</span>
      <span class="page-topbar-tag">Care Map Admin</span>
    </div>
  </a>
  <div class="page-topbar-spacer"></div>
  <span class="page-topbar-user">Care Map Governance Officer</span>
</header>

<div class="page-wrap">

  <div class="page-head">
    <h1>Care Map Moderation Desk</h1>
    <p>Clinical Listing Verification &amp; Quality Audit</p>
  </div>

  {{-- Stat grid --}}
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-card__value">{{ $pendingClaims->count() }}</div>
      <div class="stat-card__label">Pending Claims</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value">{{ $reports->count() }}</div>
      <div class="stat-card__label">Open Reports</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__value">{{ $staleStock->count() }}</div>
      <div class="stat-card__label">Stale Stock Alerts</div>
    </div>
  </div>

  {{-- Claims + Reports --}}
  <div class="content-grid">

    <div class="panel">
      <div class="panel__head">
        <span class="panel__title">Pending Claims Queue</span>
        <span class="panel__sub">Ownership verifications</span>
      </div>
      <div class="panel__body">
        @forelse($pendingClaims as $claim)
          <div class="list-row">
            <div class="list-row__main">
              <div class="list-row__name">{{ $claim->careFacility?->facility_name ?? $claim->facility?->name ?? '—' }}</div>
              <div class="list-row__sub">Claimant: {{ $claim->claimant->name ?? 'User Request' }}</div>
            </div>
            <div class="list-row__actions">
              <span class="badge-pill badge-pill--warn">SUBMITTED</span>
              <a href="{{ route('admin.care-map.review') }}" class="btn-action"><i data-lucide="shield-check"></i> Review</a>
            </div>
          </div>
        @empty
          <div class="empty-row">No pending listing ownership claims.</div>
        @endforelse
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <span class="panel__title">Listing Correction Warnings</span>
        <span class="panel__sub">User-submitted reports</span>
      </div>
      <div class="panel__body">
        @forelse($reports as $report)
          <div class="list-row">
            <div class="list-row__main">
              <div class="list-row__name">{{ $report->facility->facility_name }}</div>
              <div class="list-row__sub">Type: {{ str_replace('_', ' ', $report->report_type) }}</div>
            </div>
            <div class="list-row__actions">
              <span class="badge-pill badge-pill--danger">WARNING</span>
              <button class="btn-action"><i data-lucide="search"></i> Investigate</button>
            </div>
          </div>
        @empty
          <div class="empty-row">No outstanding correction warnings.</div>
        @endforelse
      </div>
    </div>

  </div>{{-- /content-grid --}}

  {{-- Stale Stock --}}
  <div class="panel panel--full">
    <div class="panel__head">
      <span class="panel__title">Stale Stock Alerts</span>
      <span class="panel__sub">Pharmacy &amp; blood bank entries exceeding 72h sync window</span>
    </div>
    <div class="panel__body">
      @forelse($staleStock as $stale)
        <div class="list-row">
          <div class="list-row__main">
            <div class="list-row__name">{{ $stale->medicine_name }}</div>
            <div class="list-row__sub">
              {{ $stale->facility->facility_name }}
              @if($stale->brand_name) &bull; {{ $stale->brand_name }} @endif
            </div>
          </div>
          <div class="list-row__actions">
            <span class="panel__sub">Last sync: {{ $stale->last_updated_at ? $stale->last_updated_at->diffForHumans() : 'Never' }}</span>
            <span class="badge-pill badge-pill--danger">STALE &gt; 72H</span>
            <button class="btn-action"><i data-lucide="refresh-cw"></i> Force Re-sync</button>
          </div>
        </div>
      @empty
        <div class="empty-row">All partner stocks are fresh and within synchronization limits.</div>
      @endforelse
    </div>
  </div>

</div>{{-- /page-wrap --}}

<script>
window.addEventListener('load', () => { if (window.lucide) lucide.createIcons(); });
</script>
</body>
</html>
