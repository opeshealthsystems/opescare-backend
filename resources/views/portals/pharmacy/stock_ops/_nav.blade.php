{{-- Tab strip shared by the four stock-operations screens, plus the way back
     to the public-availability page the ledger feeds. --}}
<div class="fs-tabs" role="tablist">
    @foreach([
        ['portals.pharmacy.stock',                  'megaphone',        'pharmacy_ops.nav_public_stock'],
        ['portals.pharmacy.stock.batches',          'layers',           'pharmacy_ops.nav_batches'],
        ['portals.pharmacy.stock.adjustments',      'arrow-left-right', 'pharmacy_ops.nav_adjustments'],
        ['portals.pharmacy.stock.reorder',          'trending-down',    'pharmacy_ops.nav_reorder'],
        ['portals.pharmacy.stock.receipts',         'truck',            'pharmacy_ops.nav_receipts'],
    ] as [$routeName, $icon, $key])
    @php $on = $routeName === 'portals.pharmacy.stock' ? request()->routeIs('portals.pharmacy.stock') : request()->routeIs($routeName . '*'); @endphp
    <a href="{{ route($routeName) }}" class="fs-tab {{ $on ? 'fs-tab--on' : '' }}" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}">
        <i data-lucide="{{ $icon }}"></i><span>{{ __($key) }}</span>
    </a>
    @endforeach
</div>
<style>
.fs-tabs{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:var(--p-space-6,1.5rem);border-bottom:1px solid var(--p-border,#E2E8F0);padding-bottom:.5rem}
.fs-tab{display:inline-flex;align-items:center;gap:.4rem;min-height:40px;padding:.35rem .8rem;border-radius:999px;font-size:.85rem;font-weight:600;color:var(--p-text-2,#334155);text-decoration:none;border:1.5px solid transparent}
.fs-tab i,.fs-tab svg{width:16px;height:16px}
.fs-tab:hover{background:var(--p-surface-2,#F1F5F9)}
.fs-tab--on{color:var(--f-brand,var(--p-primary,#0F4C81));border-color:var(--f-brand,var(--p-primary,#0F4C81));background:var(--f-wash,rgba(15,76,129,.08))}
.fs-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;margin-bottom:var(--p-space-6,1.5rem)}
.fs-kpi{background:var(--p-surface,#fff);border:1px solid var(--p-border,#E2E8F0);border-radius:var(--p-radius-lg,.75rem);padding:.9rem 1.1rem;border-left:4px solid var(--p-border,#E2E8F0)}
.fs-kpi--warn{border-left-color:var(--f-warn,var(--p-warning,#B45309))}
.fs-kpi--danger{border-left-color:var(--f-danger,var(--p-danger,#B91C1C))}
.fs-kpi--neutral{border-left-color:var(--p-text-muted,#64748B)}
.fs-kpi__label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--p-text-muted,#64748B)}
.fs-kpi__value{font-size:1.5rem;font-weight:750;color:var(--p-text,#0F172A);margin-top:.15rem}
.fs-kpi__sub{font-size:.8rem;color:var(--p-text-muted,#64748B)}
.fs-delta--in{color:var(--f-good,var(--p-success,#15803D));font-weight:700}
.fs-delta--out{color:var(--f-danger,var(--p-danger,#B91C1C));font-weight:700}
.fs-mono{font-variant-numeric:tabular-nums}
.fs-note{display:flex;gap:.6rem;align-items:flex-start;font-size:.85rem;color:var(--p-text-2,#334155);background:var(--p-surface-2,#F1F5F9);border-radius:var(--p-radius,.5rem);padding:.7rem .9rem;margin-bottom:var(--p-space-4,1rem)}
.fs-note i,.fs-note svg{flex:0 0 auto;width:18px;height:18px;color:var(--f-brand,var(--p-primary,#0F4C81))}
</style>
