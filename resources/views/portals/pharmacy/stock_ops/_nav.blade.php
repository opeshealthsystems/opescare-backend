{{-- Stock-operations sub-nav as contract .od-tabs pills, plus the way back to
     the public-availability page the ledger feeds. --}}
<nav class="od-tabs os-subnav" aria-label="{{ __('pharmacy_ops.nav_section') }}">
    @foreach([
        ['portals.pharmacy.stock',                  'megaphone',        'pharmacy_ops.nav_public_stock'],
        ['portals.pharmacy.stock.batches',          'layers',           'pharmacy_ops.nav_batches'],
        ['portals.pharmacy.stock.adjustments',      'arrow-left-right', 'pharmacy_ops.nav_adjustments'],
        ['portals.pharmacy.stock.reorder',          'trending-down',    'pharmacy_ops.nav_reorder'],
        ['portals.pharmacy.stock.receipts',         'truck',            'pharmacy_ops.nav_receipts'],
        ['portals.pharmacy.stock.suppliers',        'building-2',       'pharmacy_stock_desk.nav_suppliers'],
    ] as [$routeName, $icon, $key])
    @php $on = $routeName === 'portals.pharmacy.stock' ? request()->routeIs('portals.pharmacy.stock') : request()->routeIs($routeName . '*'); @endphp
    <a href="{{ route($routeName) }}" class="od-tab{{ $on ? ' is-active' : '' }}" @if($on) aria-current="page" @endif>
        <i data-lucide="{{ $icon }}"></i><span>{{ __($key) }}</span>
    </a>
    @endforeach
</nav>
