@extends('layouts.portal')
@php $name = $item->medicine?->name ?? $item->name; @endphp
@section('title', __('pharmacy_stock_desk.item_title', ['name' => $name]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')

@php
    $med        = $item->medicine;
    $finderRow  = $finder['row'];
    $statePill  = match($finder['state']) { 'published' => 'ok', 'listing_issue' => 'reorder', default => 'neutral' };
    $stateIcon  = match($finder['state']) { 'published' => 'green', 'listing_issue' => 'amber', default => 'grey' };
    $under      = $rule && $usable < $rule->reorder_level;
    $usableTone = $usable <= 0 ? 'red' : ($under ? 'amber' : 'green');
    $batchCount = $locations->flatten(1)->count();
    $adjustable = $locations->flatten(1)->map(fn ($r) => $r['batch']);
@endphp

<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ $name }}</h1>
        <p class="os-head__sub">
            {{ $med?->strength }} {{ $med?->form }}
            @if($med?->atc_code) · {{ $med->atc_code }}@if($atcLabel) — {{ $atcLabel }}@endif @endif
        </p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.stock.batches') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_desk.back_to_batches') }}</a>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<div class="os-split">
<div class="os-stack">

{{-- ── Batches ─────────────────────────────────────────────────────── --}}
<section class="od-card os-card" id="batches">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.tab_batches') }} <span class="od-card__count">({{ $batchCount }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]) }}" class="od-btn od-btn--primary"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_location') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @php $n = 0; @endphp
            @forelse($locations as $locationName => $rows)
                @foreach($rows as $row)
                    @php
                        $b    = $row['batch'];
                        $pill = match($row['state']) { 'usable' => 'ok', 'expiring' => 'reorder', 'expired' => 'expired', default => 'neutral' };
                        $bt   = match($row['state']) { 'usable' => 'green', 'expiring' => 'amber', 'expired' => 'red', default => 'grey' };
                        $n++;
                    @endphp
                    <tr id="batch-{{ $b->id }}">
                        <td class="os-col-num">{{ $n }}</td>
                        <td data-label="{{ __('pharmacy_stock_desk.col_location') }}">
                            <div class="os-cell">
                                @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $bt])
                                <div class="os-cell__text">
                                    <span class="os-cell__title">{{ $locationName !== '' ? $locationName : __('pharmacy_stock_desk.location_unknown') }}</span>
                                    <span class="os-cell__sub">{{ __('pharmacy_ops.col_received') }} {{ $b->created_at?->translatedFormat('d M Y') }}</span>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="os-lot">{{ $b->lot_number ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                            @if(! $b->expiry_date)
                                @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.expiry_unknown')])
                            @else
                                {{ $b->expiry_date->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="os-num">{{ $b->quantity_available }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_status') }}">
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => $pill, 'label' => $row['state'] === 'expiring'
                                ? __('pharmacy_ops.expiring_badge', ['days' => $row['days_left']])
                                : __('pharmacy_stock_desk.batch_state.' . $row['state'])])
                        </td>
                        <td class="os-col-act">
                            <div class="os-rowact"><a href="#adjust" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.row_adjust') }}</a></div>
                        </td>
                    </tr>
                @endforeach
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 7, 'icon' => 'layers', 'text' => __('pharmacy_stock_desk.batches_empty'),
                    'href' => route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]), 'btnIcon' => 'truck', 'btn' => __('pharmacy_stock_desk.btn_receive')])
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="os-note"><i data-lucide="info"></i> {{ __('pharmacy_ops.usable_hint') }}</p>
</section>

{{-- ── Movements ───────────────────────────────────────────────────── --}}
<section class="od-card os-card" id="movements">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.tab_movements') }} <span class="od-card__count">({{ $movements->total() }})</span></h2>
        <a href="#adjust" class="od-btn od-btn--secondary"><i data-lucide="arrow-left-right"></i> {{ __('pharmacy_stock_desk.btn_adjust') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_when') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_kind') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_change') }}</th>
                <th>{{ __('pharmacy_ops.col_reason') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($movements as $m)
                @php [$code, $note] = \App\Services\Pharmacy\PharmacyStockOpsService::decodeReason($m->reason); $mw = $m->performed_at ?? $m->created_at; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_when') }}">
                        <span class="os-cell__main">{{ $mw?->translatedFormat('d M Y') }}</span>
                        <span class="os-cell__sub">{{ $mw?->translatedFormat('H:i') }} · {{ $actors[$m->performed_by] ?? '—' }}</span>
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_kind') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => $m->quantity >= 0 ? 'arrow-down-to-line' : 'arrow-up-from-line', 'tone' => $m->quantity >= 0 ? 'green' : 'red'])
                            <div class="os-cell__text">
                                <span class="os-cell__title os-nowrap">
                                    @if($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RECEIPT && $m->reference_id)
                                        {{ __('pharmacy_stock_desk.kind.receipt') }}
                                    @elseif($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RESERVATION)
                                        {{ __('pharmacy_stock_desk.kind.reservation') }}
                                    @else
                                        {{ __('pharmacy_stock_desk.kind.' . ($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_ADJUSTMENT ? 'adjustment' : 'other')) }}
                                    @endif
                                </span>
                                <span class="os-cell__sub">{{ __('pharmacy_stock_desk.movement_type.' . $m->movement_type) }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="os-lot">{{ $m->batch?->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_change') }}" class="{{ $m->quantity >= 0 ? 'os-in' : 'os-out' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_reason') }}">
                        @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.reason.' . $code)])
                        @if($note)<span class="os-cell__sub">{{ $note }}</span>@endif
                    </td>
                    <td class="os-col-act"><div class="os-rowact"><a href="{{ route('portals.pharmacy.stock.adjustments.show', $m->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a></div></td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 6, 'icon' => 'list', 'text' => __('pharmacy_stock_desk.movements_empty'),
                    'href' => route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]), 'btnIcon' => 'truck', 'btn' => __('pharmacy_stock_desk.btn_receive')])
            @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())<div class="os-card__foot">{{ $movements->fragment('movements')->links() }}</div>@endif
</section>

{{-- ── Adjust (posts to the existing movement route) ────────────────── --}}
<details class="od-card os-card os-fold" id="adjust" @if($errors->any() || old('batch_id')) open @endif>
    <summary class="od-card__head os-fold__head">
        <h2 class="od-card__title"><span class="os-fold__icon"><i data-lucide="arrow-left-right"></i></span>{{ __('pharmacy_stock_desk.adjust_title') }}</h2>
        <i data-lucide="chevron-down" class="os-fold__chev"></i>
    </summary>
    <div class="os-card__body">
        @if($adjustable->isEmpty())
            <p class="os-muted">{{ __('pharmacy_stock_desk.batches_empty') }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.adjustments.store') }}" class="os-form">
            @csrf
            <div class="os-grid os-grid--2">
                <div class="os-field">
                    <label class="os-label" for="it-batch">{{ __('pharmacy_ops.lbl_batch') }}</label>
                    <select name="batch_id" id="it-batch" class="os-input" required>
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
                <div class="os-field">
                    <label class="os-label" for="it-reason">{{ __('pharmacy_ops.lbl_reason') }}</label>
                    <select name="reason" id="it-reason" class="os-input" required>
                        @foreach($reasons as $r)
                        <option value="{{ $r }}" {{ old('reason') === $r ? 'selected' : '' }}>{{ __('pharmacy_ops.reason.' . $r) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="os-grid os-grid--2">
                <div class="os-field">
                    <label class="os-label" for="it-qty">{{ __('pharmacy_ops.lbl_quantity') }}</label>
                    <input type="number" name="quantity" id="it-qty" class="os-input" min="-1000000" max="1000000" step="1" inputmode="numeric" value="{{ old('quantity') }}" required>
                    <span class="os-hint">{{ __('pharmacy_ops.lbl_quantity_hint') }}</span>
                </div>
                <div class="os-field">
                    <label class="os-label" for="it-note">{{ __('pharmacy_ops.lbl_note') }}</label>
                    <input type="text" name="note" id="it-note" class="os-input" maxlength="500" value="{{ old('note') }}">
                </div>
            </div>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--primary" data-confirm="{{ __('pharmacy_stock_desk.confirm_adjust') }}"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_record') }}</button>
            </div>
        </form>
        @endif
    </div>
</details>

{{-- ── Reorder rule (posts to the existing rule route) ──────────────── --}}
<section class="od-card os-card" id="reorder">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.tab_reorder') }}</h2>
        @if($rule)
            @include('portals.pharmacy.stock_ops._pill', ['tone' => $under ? 'reorder' : 'ok', 'label' => __('pharmacy_ops.col_minimum') . ': ' . $rule->reorder_level])
        @else
            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.no_rule')])
        @endif
    </div>
    @if($item->medicine_id)
    <form method="POST" action="{{ route('portals.pharmacy.stock.reorder.store') }}" class="os-form">
        @csrf
        <input type="hidden" name="medicine_id" value="{{ $item->medicine_id }}">
        <div class="os-grid os-grid--2">
            <div class="os-field">
                <label class="os-label" for="it-level">{{ __('pharmacy_ops.lbl_min_level') }}</label>
                <input type="number" name="reorder_level" id="it-level" class="os-input" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_level', $rule?->reorder_level) }}" required>
            </div>
            <div class="os-field">
                <label class="os-label" for="it-suggest">{{ __('pharmacy_ops.lbl_suggest_qty') }}</label>
                <input type="number" name="reorder_quantity" id="it-suggest" class="os-input" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_quantity', $rule?->reorder_quantity) }}">
            </div>
        </div>
        <div class="os-form__actions">
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_save_rule') }}</button>
        </div>
    </form>
    <p class="os-note"><i data-lucide="info"></i> {{ __('pharmacy_ops.no_auto_order') }}</p>
    @else
    <p class="os-muted">{{ __('pharmacy_stock_desk.rule_needs_medicine') }}</p>
    @endif
</section>

{{-- ── Suppliers who delivered it ──────────────────────────────────── --}}
<section class="od-card os-card" id="suppliers">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.tab_suppliers') }} <span class="od-card__count">({{ $suppliers->count() }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.suppliers') }}" class="os-link">{{ __('pharmacy_stock_desk.view_all') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_last_delivery') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_deliveries') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($suppliers as $s)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $s->supplier->name, 'tone' => 'blue'])
                            <div class="os-cell__text"><a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->supplier->id) }}" class="os-cell__title">{{ $s->supplier->name }}</a></div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_last_delivery') }}">{{ $s->last_delivery?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_deliveries') }}" class="os-num">{{ $s->deliveries }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="os-num">{{ $s->packs }}</td>
                    <td class="os-col-act"><div class="os-rowact"><a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->supplier->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a></div></td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 5, 'icon' => 'building-2', 'text' => __('pharmacy_stock_desk.suppliers_for_item_empty'),
                    'href' => route('portals.pharmacy.stock.suppliers'), 'btnIcon' => 'building-2', 'btn' => __('pharmacy_stock_desk.nav_suppliers')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

</div>

{{-- ── Right-hand detail panel (reference: Patient & Prescription Details) ── --}}
<aside class="od-card os-card os-detail od-detail" id="item-detail" aria-label="{{ __('pharmacy_stock_desk.details_title') }}">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.details_title') }}</h2>
        @if($finder['state'] === 'published')
            <span class="os-chip-ok"><i data-lucide="shield-check"></i> {{ __('pharmacy_stock_desk.finder.published') }}</span>
        @endif
    </div>
    <div class="os-detail__who">
        @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $usableTone, 'big' => true])
        <div>
            <div class="os-detail__name">{{ $name }}</div>
            <div class="os-detail__ref">{{ $med?->strength }} {{ $med?->form }}@if($med?->atc_code) · {{ $med->atc_code }}@endif</div>
            <div class="os-facts">
                <span><i data-lucide="package"></i> {{ __('pharmacy_ops.col_usable') }}: <strong>{{ $usable }}</strong></span>
                <span><i data-lucide="layers"></i> {{ __('pharmacy_ops.sum_lots', ['count' => $batchCount]) }}</span>
                @if($atcLabel)<span><i data-lucide="tag"></i> {{ $atcLabel }}</span>@endif
            </div>
        </div>
    </div>

    <nav class="od-tabs od-tabs--inner" aria-label="{{ __('pharmacy_stock_desk.sections') }}">
        <a href="#batches" class="od-tab is-active">{{ __('pharmacy_stock_desk.tab_batches') }}</a>
        <a href="#movements" class="od-tab">{{ __('pharmacy_stock_desk.tab_movements') }}</a>
        <a href="#reorder" class="od-tab">{{ __('pharmacy_stock_desk.tab_reorder') }}</a>
        <a href="#suppliers" class="od-tab">{{ __('pharmacy_stock_desk.tab_suppliers') }}</a>
    </nav>

    <div class="os-infos">
        <div class="os-info">
            <span class="os-info__icon os-info__icon--{{ $usableTone }}">@include('portals.pharmacy.stock_ops._glyph', ['glyph' => 'pack'])</span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_ops.col_usable') }}</div>
                <div class="os-info__v">{{ $usable }}@if($rule) <span class="os-cell__sub">/ {{ __('pharmacy_ops.col_minimum') }} {{ $rule->reorder_level }}</span>@endif</div>
                <div class="os-info__note">{{ __('pharmacy_stock_desk.usable_sub') }}</div>
            </div>
        </div>
        <div class="os-info">
            <span class="os-info__icon os-info__icon--{{ $stateIcon }}"><i data-lucide="megaphone"></i></span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_stock_desk.finder_status') }}</div>
                <div class="os-info__v">@include('portals.pharmacy.stock_ops._pill', ['tone' => $statePill, 'label' => __('pharmacy_stock_desk.finder.' . $finder['state'])])</div>
                <div class="os-info__note">
                    @if($finder['state'] === 'published' && $finderRow)
                        @enum($finderRow->stock_status->value, 'stock_status') · {{ __('pharmacy_ops.sum_packs', ['count' => (int) $finderRow->packs_available]) }}
                        · {{ $finderRow->last_reported_at?->diffForHumans() ?? __('pharmacy_stock_desk.never_reported') }}
                    @else
                        {{ __('pharmacy_stock_desk.finder_reason.' . $finder['state'], ['source' => $finderRow?->source_system ?? '—']) }}
                    @endif
                </div>
            </div>
        </div>
        <div class="os-info">
            <span class="os-info__icon"><i data-lucide="coins"></i></span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_stock_desk.price') }}</div>
                <div class="os-info__v">@if($item->selling_price_xaf !== null){{ \App\Services\Billing\Xaf::format((int) $item->selling_price_xaf) }} @else — @endif</div>
                <div class="os-info__note"><a href="{{ route('portals.pharmacy.prices.history', $item->id) }}">{{ __('pharmacy_stock_desk.price_history') }}</a></div>
            </div>
        </div>
    </div>

    <div class="os-actions">
        <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]) }}" class="od-btn od-btn--primary"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.btn_receive') }}</a>
        <a href="#adjust" class="od-btn od-btn--secondary"><i data-lucide="arrow-left-right"></i> {{ __('pharmacy_stock_desk.row_adjust') }}</a>
        <a href="#reorder" class="od-btn od-btn--secondary"><i data-lucide="trending-down"></i> {{ __('pharmacy_stock_desk.btn_reorder_rule') }}</a>
    </div>
</aside>

</div>

</div>
@endsection
