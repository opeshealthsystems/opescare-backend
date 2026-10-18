@extends('layouts.portal')
@section('title', __('pharmacy_ops.adj_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_adjustments'))

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.adj_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.adj_subtitle') }}</p>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<details class="panel" id="adj-form" @if($errors->any() || old('batch_id')) open @endif>
    <summary class="panel-header">
        <i data-lucide="plus-circle"></i> {{ __('pharmacy_ops.adj_form_title') }}
    </summary>
    <div class="panel-body">
        @if($batches->isEmpty())
            <p class="td-muted">{{ __('pharmacy_ops.batches_empty') }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.adjustments.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="mv-batch">{{ __('pharmacy_ops.lbl_batch') }}</label>
                    <select name="batch_id" id="mv-batch" class="form-control" required>
                        <option value="">{{ __('pharmacy_ops.choose_batch') }}</option>
                        @foreach($batches as $b)
                        <option value="{{ $b->id }}" {{ old('batch_id') === $b->id ? 'selected' : '' }}>
                            {{ __('pharmacy_ops.lot_option', [
                                'medicine' => $b->item?->medicine?->name ?? $b->item?->name,
                                'lot'      => $b->lot_number ?: __('pharmacy_ops.lot_none'),
                                'expiry'   => $b->expiry_date?->format('Y-m-d') ?? __('pharmacy_ops.expiry_unknown'),
                                'packs'    => $b->quantity_available,
                            ]) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mv-reason">{{ __('pharmacy_ops.lbl_reason') }}</label>
                    <select name="reason" id="mv-reason" class="form-control" required>
                        @foreach($reasons as $r)
                        <option value="{{ $r }}" {{ old('reason') === $r ? 'selected' : '' }}>{{ __('pharmacy_ops.reason.' . $r) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="mv-qty">{{ __('pharmacy_ops.lbl_quantity') }}</label>
                    <input type="number" name="quantity" id="mv-qty" class="form-control fs-num" min="-1000000" max="1000000" step="1" inputmode="numeric" value="{{ old('quantity') }}" required>
                    <div class="td-muted">{{ __('pharmacy_ops.lbl_quantity_hint') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mv-note">{{ __('pharmacy_ops.lbl_note') }}</label>
                    <input type="text" name="note" id="mv-note" class="form-control" maxlength="500" value="{{ old('note') }}">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_record') }}</button>
            </div>
        </form>
        @endif
    </div>
</details>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="arrow-left-right"></i> {{ __('pharmacy_ops.nav_adjustments') }} <span class="ph-count-inline">({{ $movements->total() }})</span></h3>
        <a href="#adj-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_record') }}</a>
    </div>
    <div class="ph-toolbar">
        <div class="ph-pills" role="tablist">
            <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="ph-pill{{ $filter === '' ? ' active' : '' }}" role="tab">{{ __('pharmacy_ops.filter_all') }}</a>
            @foreach($reasons as $r)
            <a href="{{ route('portals.pharmacy.stock.adjustments', ['reason' => $r]) }}" class="ph-pill{{ $filter === $r ? ' active' : '' }}" role="tab">{{ __('pharmacy_ops.reason.' . $r) }}</a>
            @endforeach
        </div>
        <form method="GET" class="fs-search" role="search">
            <select name="reason" class="filter-select" data-autosubmit aria-label="{{ __('pharmacy_ops.lbl_reason') }}">
                <option value="">{{ __('pharmacy_ops.filter_all') }}</option>
                @foreach($reasons as $r)
                <option value="{{ $r }}" {{ $filter === $r ? 'selected' : '' }}>{{ __('pharmacy_ops.reason.' . $r) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm fs-tap"><i data-lucide="filter"></i> {{ __('pharmacy_ops.btn_filter') }}</button>
            @if($filter !== '')
                <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="btn btn-ghost btn-sm fs-tap">{{ __('pharmacy_ops.btn_clear') }}</a>
            @endif
        </form>
    </div>
    <div class="fs-note"><i data-lucide="lock"></i><div>{{ __('pharmacy_ops.adj_append_only') }}</div></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_when') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_change') }}</th>
                <th>{{ __('pharmacy_ops.col_reason') }}</th>
                <th>{{ __('pharmacy_ops.col_note') }}</th>
                <th>{{ __('pharmacy_ops.col_by') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($movements as $m)
                @php
                    [$code, $note] = \App\Services\Pharmacy\PharmacyStockOpsService::decodeReason($m->reason);
                    $mName = $m->item?->medicine?->name ?? $m->item?->name ?? '—';
                @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_when') }}" class="td-muted"><a href="{{ route('portals.pharmacy.stock.adjustments.show', $m->id) }}">{{ ($m->performed_at ?? $m->created_at)?->translatedFormat('d M Y H:i') }}</a></td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $mName, 'tone' => $m->quantity >= 0 ? 'green' : 'red'])
                            <div>
                                <div class="ph-cell__title">
                                    @if($m->inventory_item_id)
                                        <a href="{{ route('portals.pharmacy.stock.items.show', $m->inventory_item_id) }}">{{ $mName }}</a>
                                    @else
                                        {{ $mName }}
                                    @endif
                                </div>
                                <div class="ph-cell__sub">{{ __('pharmacy_stock_desk.movement_type.' . $m->movement_type) }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono">{{ $m->batch?->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_change') }}" class="fs-mono {{ $m->quantity >= 0 ? 'fs-delta--in' : 'fs-delta--out' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_reason') }}"><span class="badge badge-neutral">{{ __('pharmacy_ops.reason.' . $code) }}</span></td>
                    <td data-label="{{ __('pharmacy_ops.col_note') }}" class="td-muted">{{ $note ?? '' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_by') }}" class="td-muted">{{ $actors[$m->performed_by] ?? '—' }}</td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.adjustments.show', $m->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="arrow-left-right"></i>
                        <p>{{ __('pharmacy_ops.adj_empty') }}</p>
                        @if(! $batches->isEmpty())
                        <a href="#adj-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_record') }}</a>
                        @endif
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $movements->links() }}</div>
</div>

@endsection
