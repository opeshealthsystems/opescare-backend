@extends('layouts.portal')
@php $name = $item->medicine?->name ?? $item->name; @endphp
@section('title', __('pharmacy_stock_desk.item_title', ['name' => $name]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_batches'))

@section('content')

@php
    $med        = $item->medicine;
    $finderRow  = $finder['row'];
    $stateBadge = match($finder['state']) {
        'published'     => 'badge-success',
        'listing_issue' => 'badge-warning',
        default         => 'badge-neutral',
    };
    $stateCard  = match($finder['state']) {
        'published'     => 'ph-infocard--success',
        'listing_issue' => 'ph-infocard--warning',
        default         => 'ph-infocard--neutral',
    };
    $usableTone = $usable <= 0 ? 'red' : ($rule && $usable < $rule->reorder_level ? 'amber' : 'green');
    $usableCard = $usable <= 0 ? 'ph-infocard--danger' : ($rule && $usable < $rule->reorder_level ? 'ph-infocard--warning' : 'ph-infocard--success');
    $batchCount = $locations->flatten(1)->count();
@endphp

<div class="page-head">
    <h2>{{ $name }}</h2>
    <p class="page-subtitle">
        {{ $med?->strength }} {{ $med?->form }}
        @if($med?->atc_code)
            · <span class="fs-mono">{{ $med->atc_code }}</span>@if($atcLabel) — {{ $atcLabel }}@endif
        @endif
    </p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.stock.batches') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_desk.back_to_batches') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<div class="ph-split">
<div class="ph-stack">

{{-- ── Batches ──────────────────────────────────────────────────────── --}}
<div class="panel" id="batches">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="layers"></i> {{ __('pharmacy_stock_desk.tab_batches') }} <span class="ph-count-inline">({{ $batchCount }})</span></h3>
        <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]) }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_stock_desk.col_location') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th>{{ __('pharmacy_ops.col_received') }}</th>
            </tr></thead>
            <tbody>
            @forelse($locations as $locationName => $rows)
                @foreach($rows as $row)
                    @php
                        $b   = $row['batch'];
                        $cls = match($row['state']) { 'usable' => 'badge-success', 'expiring' => 'badge-warning', 'expired' => 'badge-danger', default => 'badge-neutral' };
                        $bt  = match($row['state']) { 'usable' => 'green', 'expiring' => 'amber', 'expired' => 'red', default => 'grey' };
                    @endphp
                    <tr id="batch-{{ $b->id }}">
                        <td data-label="{{ __('pharmacy_stock_desk.col_location') }}">
                            <div class="ph-cell">
                                @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'map-pin', 'tone' => $bt])
                                <div class="ph-cell__title">{{ $locationName !== '' ? $locationName : __('pharmacy_stock_desk.location_unknown') }}</div>
                            </div>
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono">{{ $b->lot_number ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                            @if(! $b->expiry_date)
                                <span class="badge badge-neutral">{{ __('pharmacy_ops.expiry_unknown') }}</span>
                            @else
                                {{ $b->expiry_date->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="td-num">{{ $b->quantity_available }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_status') }}">
                            <span class="badge {{ $cls }}">
                                @if($row['state'] === 'expiring')
                                    {{ __('pharmacy_ops.expiring_badge', ['days' => $row['days_left']]) }}
                                @else
                                    {{ __('pharmacy_stock_desk.batch_state.' . $row['state']) }}
                                @endif
                            </span>
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_received') }}" class="td-muted">{{ $b->created_at?->translatedFormat('d M Y') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="layers"></i>
                        <p>{{ __('pharmacy_stock_desk.batches_empty') }}</p>
                        <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]) }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-foot">{{ __('pharmacy_ops.usable_hint') }}</div>
</div>

{{-- ── Adjust (posts to the existing movement route) ─────────────────── --}}
<details class="panel" id="adjust" @if($errors->any() || old('batch_id')) open @endif>
    <summary class="panel-header fs-tap">
        <i data-lucide="arrow-left-right"></i> {{ __('pharmacy_stock_desk.adjust_title') }}
    </summary>
    <div class="panel-body">
        @php $adjustable = $locations->flatten(1)->map(fn ($r) => $r['batch']); @endphp
        @if($adjustable->isEmpty())
            <p class="td-muted">{{ __('pharmacy_stock_desk.batches_empty') }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.adjustments.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="it-batch">{{ __('pharmacy_ops.lbl_batch') }}</label>
                    <select name="batch_id" id="it-batch" class="form-control" required>
                        <option value="">{{ __('pharmacy_ops.choose_batch') }}</option>
                        @foreach($adjustable as $b)
                        <option value="{{ $b->id }}" {{ old('batch_id') === $b->id ? 'selected' : '' }}>
                            {{ __('pharmacy_ops.lot_option', [
                                'medicine' => $name,
                                'lot'      => $b->lot_number ?: __('pharmacy_ops.lot_none'),
                                'expiry'   => $b->expiry_date?->format('Y-m-d') ?? __('pharmacy_ops.expiry_unknown'),
                                'packs'    => $b->quantity_available,
                            ]) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="it-reason">{{ __('pharmacy_ops.lbl_reason') }}</label>
                    <select name="reason" id="it-reason" class="form-control" required>
                        @foreach($reasons as $r)
                        <option value="{{ $r }}" {{ old('reason') === $r ? 'selected' : '' }}>{{ __('pharmacy_ops.reason.' . $r) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="it-qty">{{ __('pharmacy_ops.lbl_quantity') }}</label>
                    <input type="number" name="quantity" id="it-qty" class="form-control fs-num" min="-1000000" max="1000000" step="1" inputmode="numeric" value="{{ old('quantity') }}" required>
                    <div class="td-muted">{{ __('pharmacy_ops.lbl_quantity_hint') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="it-note">{{ __('pharmacy_ops.lbl_note') }}</label>
                    <input type="text" name="note" id="it-note" class="form-control" maxlength="500" value="{{ old('note') }}">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap" data-confirm="{{ __('pharmacy_stock_desk.confirm_adjust') }}"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_record') }}</button>
            </div>
        </form>
        @endif
    </div>
</details>

{{-- ── Movements ────────────────────────────────────────────────────── --}}
<div class="panel" id="movements">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="list"></i> {{ __('pharmacy_stock_desk.tab_movements') }} <span class="ph-count-inline">({{ $movements->total() }})</span></h3>
        <a href="#adjust" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left-right"></i> {{ __('pharmacy_stock_desk.btn_adjust') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_when') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_kind') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_change') }}</th>
                <th>{{ __('pharmacy_ops.col_reason') }}</th>
                <th>{{ __('pharmacy_ops.col_by') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($movements as $m)
                @php [$code, $note] = \App\Services\Pharmacy\PharmacyStockOpsService::decodeReason($m->reason); @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_when') }}" class="td-muted"><a href="{{ route('portals.pharmacy.stock.adjustments.show', $m->id) }}">{{ ($m->performed_at ?? $m->created_at)?->translatedFormat('d M Y H:i') }}</a></td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_kind') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => $m->quantity >= 0 ? 'arrow-down-to-line' : 'arrow-up-from-line', 'tone' => $m->quantity >= 0 ? 'green' : 'red'])
                            <div>
                                <div class="ph-cell__title">
                                    @if($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RECEIPT && $m->reference_id)
                                        <a href="{{ route('portals.pharmacy.stock.receipts.show', $m->reference_id) }}">{{ __('pharmacy_stock_desk.kind.receipt') }}</a>
                                    @elseif($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RESERVATION)
                                        {{ __('pharmacy_stock_desk.kind.reservation') }}
                                    @else
                                        {{ __('pharmacy_stock_desk.kind.' . ($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_ADJUSTMENT ? 'adjustment' : 'other')) }}
                                    @endif
                                </div>
                                <div class="ph-cell__sub">{{ __('pharmacy_stock_desk.movement_type.' . $m->movement_type) }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono">{{ $m->batch?->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_change') }}" class="fs-mono {{ $m->quantity >= 0 ? 'fs-delta--in' : 'fs-delta--out' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_reason') }}"><span class="badge badge-neutral">{{ __('pharmacy_ops.reason.' . $code) }}</span> <span class="td-muted">{{ $note ?? '' }}</span></td>
                    <td data-label="{{ __('pharmacy_ops.col_by') }}" class="td-muted">{{ $actors[$m->performed_by] ?? '—' }}</td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.adjustments.show', $m->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="list"></i>
                        <p>{{ __('pharmacy_stock_desk.movements_empty') }}</p>
                        <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]) }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $movements->fragment('movements')->links() }}</div>
</div>

{{-- ── Reorder rule (posts to the existing rule route) ───────────────── --}}
<div class="panel" id="reorder">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="trending-down"></i> {{ __('pharmacy_stock_desk.tab_reorder') }}</h3>
        @if($rule)
            <span class="badge {{ $usable < $rule->reorder_level ? 'badge-danger' : 'badge-success' }}">{{ __('pharmacy_ops.col_minimum') }}: {{ $rule->reorder_level }}</span>
        @else
            <span class="badge badge-neutral">{{ __('pharmacy_ops.no_rule') }}</span>
        @endif
    </div>
    <div class="panel-body">
        @if($item->medicine_id)
        <form method="POST" action="{{ route('portals.pharmacy.stock.reorder.store') }}">
            @csrf
            <input type="hidden" name="medicine_id" value="{{ $item->medicine_id }}">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="it-level">{{ __('pharmacy_ops.lbl_min_level') }}</label>
                    <input type="number" name="reorder_level" id="it-level" class="form-control fs-num" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_level', $rule?->reorder_level) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="it-suggest">{{ __('pharmacy_ops.lbl_suggest_qty') }}</label>
                    <input type="number" name="reorder_quantity" id="it-suggest" class="form-control fs-num" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_quantity', $rule?->reorder_quantity) }}">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_save_rule') }}</button>
            </div>
        </form>
        <p class="td-muted mt-6">{{ __('pharmacy_ops.no_auto_order') }}</p>
        @else
        <p class="td-muted">{{ __('pharmacy_stock_desk.rule_needs_medicine') }}</p>
        @endif
    </div>
</div>

{{-- ── Suppliers who delivered it ───────────────────────────────────── --}}
<div class="panel" id="suppliers">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('pharmacy_stock_desk.tab_suppliers') }} <span class="ph-count-inline">({{ $suppliers->count() }})</span></h3>
        <a href="{{ route('portals.pharmacy.stock.suppliers') }}" class="btn btn-secondary btn-sm">{{ __('pharmacy_stock_desk.nav_suppliers') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_last_delivery') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_deliveries') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($suppliers as $s)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $s->supplier->name])
                            <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->supplier->id) }}">{{ $s->supplier->name }}</a></div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_last_delivery') }}">{{ $s->last_delivery?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_deliveries') }}" class="td-num">{{ $s->deliveries }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="td-num">{{ $s->packs }}</td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->supplier->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="building-2"></i>
                        <p>{{ __('pharmacy_stock_desk.suppliers_for_item_empty') }}</p>
                        <a href="{{ route('portals.pharmacy.stock.suppliers') }}" class="btn btn-secondary btn-sm">{{ __('pharmacy_stock_desk.nav_suppliers') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>

{{-- ── Right-hand detail panel: who this medicine is, at a glance ───────── --}}
<div class="panel ph-detail" id="item-detail">
    <div class="ph-detail__head">
        @include('portals.pharmacy.stock_ops._avatar', ['name' => $name, 'tone' => $usableTone])
        <div>
            <div class="ph-detail__name">{{ $name }}</div>
            <div class="ph-detail__ref">
                {{ $med?->strength }} {{ $med?->form }}
                @if($med?->atc_code) · <span class="fs-mono">{{ $med->atc_code }}</span>@endif
            </div>
        </div>
    </div>
    <div class="ph-chips">
        <span class="ph-chip"><i data-lucide="package"></i> {{ __('pharmacy_ops.col_usable') }}: <strong>{{ $usable }}</strong></span>
        <span class="ph-chip"><i data-lucide="layers"></i> {{ __('pharmacy_ops.sum_lots', ['count' => $batchCount]) }}</span>
        @if($atcLabel)<span class="ph-chip"><i data-lucide="tag"></i> {{ $atcLabel }}</span>@endif
        @if($rule)<span class="ph-chip"><i data-lucide="trending-down"></i> {{ __('pharmacy_ops.col_minimum') }}: {{ $rule->reorder_level }}</span>@endif
    </div>
    {{-- Section links (anchored, no JS) --}}
    <nav class="ph-tabs" aria-label="{{ __('pharmacy_stock_desk.sections') }}">
        <a href="#batches" class="ph-pill active"><i data-lucide="layers"></i><span>{{ __('pharmacy_stock_desk.tab_batches') }}</span></a>
        <a href="#movements" class="ph-pill"><i data-lucide="list"></i><span>{{ __('pharmacy_stock_desk.tab_movements') }}</span></a>
        <a href="#reorder" class="ph-pill"><i data-lucide="trending-down"></i><span>{{ __('pharmacy_stock_desk.tab_reorder') }}</span></a>
        <a href="#suppliers" class="ph-pill"><i data-lucide="building-2"></i><span>{{ __('pharmacy_stock_desk.tab_suppliers') }}</span></a>
    </nav>
    <div class="ph-detail__body">
        <div class="ph-infocard {{ $usableCard }}">
            <div class="ph-infocard__icon"><i data-lucide="package"></i></div>
            <div>
                <div class="ph-infocard__k">{{ __('pharmacy_ops.col_usable') }}</div>
                <div class="ph-infocard__v fs-mono">{{ $usable }}</div>
                <div class="ph-infocard__note">{{ __('pharmacy_stock_desk.usable_sub') }}</div>
            </div>
        </div>
        <div class="ph-infocard {{ $stateCard }}">
            <div class="ph-infocard__icon"><i data-lucide="megaphone"></i></div>
            <div>
                <div class="ph-infocard__k">{{ __('pharmacy_stock_desk.finder_status') }}</div>
                <div class="ph-infocard__v"><span class="badge {{ $stateBadge }}">{{ __('pharmacy_stock_desk.finder.' . $finder['state']) }}</span></div>
                <div class="ph-infocard__note">
                    @if($finder['state'] === 'published' && $finderRow)
                        @enum($finderRow->stock_status->value, 'stock_status') · {{ __('pharmacy_ops.sum_packs', ['count' => (int) $finderRow->packs_available]) }}
                        · {{ $finderRow->last_reported_at?->diffForHumans() ?? __('pharmacy_stock_desk.never_reported') }}
                    @else
                        {{ __('pharmacy_stock_desk.finder_reason.' . $finder['state'], ['source' => $finderRow?->source_system ?? '—']) }}
                    @endif
                </div>
            </div>
        </div>
        <div class="ph-infocard">
            <div class="ph-infocard__icon"><i data-lucide="coins"></i></div>
            <div>
                <div class="ph-infocard__k">{{ __('pharmacy_stock_desk.price') }}</div>
                <div class="ph-infocard__v fs-mono">
                    @if($item->selling_price_xaf !== null) {{ number_format($item->selling_price_xaf, 0, ',', ' ') }} FCFA @else — @endif
                </div>
                <div class="ph-infocard__note"><a href="{{ route('portals.pharmacy.prices.history', $item->id) }}">{{ __('pharmacy_stock_desk.price_history') }}</a></div>
            </div>
        </div>
    </div>
    <div class="fs-act">
        <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]) }}" class="btn btn-primary fs-tap"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a>
        <a href="#adjust" class="btn btn-secondary fs-tap"><i data-lucide="arrow-left-right"></i> {{ __('pharmacy_stock_desk.btn_adjust') }}</a>
        <a href="#reorder" class="btn btn-secondary fs-tap"><i data-lucide="trending-down"></i> {{ __('pharmacy_stock_desk.btn_reorder_rule') }}</a>
    </div>
</div>

</div>

@endsection
