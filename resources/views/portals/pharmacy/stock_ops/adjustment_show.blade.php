@extends('layouts.portal')
@section('title', __('pharmacy_stock_desk.adj_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')

@php
    $m    = $movement;
    $name = $m->item?->medicine?->name ?? $m->item?->name ?? '—';
    $when = $m->performed_at ?? $m->created_at;
    $in   = $m->quantity >= 0;
@endphp

<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_stock_desk.adj_title') }}</h1>
        <p class="os-head__sub">{{ $name }} · {{ $when?->translatedFormat('d M Y H:i') }}</p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_desk.back_to_log') }}</a>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')

<div class="os-kpis{{ $beforeAfter ? ' os-kpis--3' : ' os-kpis--2' }}">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => $in ? 'green' : 'red', 'icon' => $in ? 'arrow-down-to-line' : 'arrow-up-from-line',
        'label' => __('pharmacy_ops.col_change'), 'value' => ($m->quantity > 0 ? '+' : '') . $m->quantity,
        'sub' => __('pharmacy_stock_desk.movement_type.' . $m->movement_type)])
    @if($beforeAfter)
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'pack',
        'label' => __('pharmacy_stock_desk.col_before'), 'value' => $beforeAfter[0], 'sub' => __('pharmacy_stock_desk.lot_packs')])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'package-check',
        'label' => __('pharmacy_stock_desk.col_after'), 'value' => $beforeAfter[1], 'sub' => __('pharmacy_stock_desk.lot_packs')])
    @endif
</div>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.adj_facts') }}</h2>
        @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.reason.' . $reasonCode)])
    </div>
    <p class="os-note"><i data-lucide="lock"></i> {{ __('pharmacy_ops.adj_append_only') }}</p>
    <dl class="os-dl">
        <dt>{{ __('pharmacy_ops.col_medicine') }}</dt>
        <dd>
            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $in ? 'green' : 'red'])
            @if($m->inventory_item_id)
                <a href="{{ route('portals.pharmacy.stock.items.show', $m->inventory_item_id) }}" class="os-cell__title">{{ $name }}</a>
            @else
                <span class="os-cell__title">{{ $name }}</span>
            @endif
        </dd>
        <dt>{{ __('pharmacy_ops.col_by') }}</dt>
        <dd>{{ $actor ?? '—' }}</dd>
        <dt>{{ __('pharmacy_ops.col_when') }}</dt>
        <dd>{{ $when?->translatedFormat('d M Y H:i') }}</dd>
        <dt>{{ __('pharmacy_ops.col_lot') }}</dt>
        <dd>
            @if($m->batch)
                <a href="{{ route('portals.pharmacy.stock.items.show', $m->inventory_item_id) }}#batch-{{ $m->batch->id }}" class="os-lot">{{ $m->batch->lot_number ?? '—' }}</a>
                <span class="os-cell__sub">· {{ $m->batch->expiry_date?->translatedFormat('d M Y') ?? __('pharmacy_ops.expiry_unknown') }}</span>
            @else
                —
            @endif
        </dd>
        <dt>{{ __('pharmacy_stock_desk.col_location') }}</dt>
        <dd>{{ $m->toLocation?->name ?? $m->fromLocation?->name ?? '—' }}</dd>
        <dt>{{ __('pharmacy_ops.col_reason') }}</dt>
        <dd>@include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.reason.' . $reasonCode)])</dd>
        <dt>{{ __('pharmacy_ops.col_note') }}</dt>
        <dd>{{ $note ?? '—' }}</dd>
        <dt>{{ __('pharmacy_stock_desk.col_reference') }}</dt>
        <dd>
            @if($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RECEIPT && $m->reference_id)
                <a href="{{ route('portals.pharmacy.stock.receipts.show', $m->reference_id) }}">{{ __('pharmacy_stock_desk.kind.receipt') }}</a>
            @elseif($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RESERVATION)
                {{ __('pharmacy_stock_desk.kind.reservation') }}
            @elseif($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_ADJUSTMENT)
                {{ __('pharmacy_stock_desk.kind.adjustment') }}
            @else
                {{ $m->reference_type ?? '—' }}
            @endif
        </dd>
        @if($m->unit_cost !== null)
        <dt>{{ __('pharmacy_ops.col_line_cost') }}</dt>
        <dd class="os-num">{{ number_format((int) $m->unit_cost, 0, ',', ' ') }} FCFA</dd>
        @endif
    </dl>
</section>

</div>
@endsection
