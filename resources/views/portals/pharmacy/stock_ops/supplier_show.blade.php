@extends('layouts.portal')
@section('title', $supplier->name)
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_stock_desk.nav_suppliers'))

@section('content')

<div class="page-head">
    <h2>{{ $supplier->name }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_stock_desk.supplier_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <span class="badge {{ $supplier->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_stock_desk.supplier_status.' . $supplier->status) }}</span>
    <a href="{{ route('portals.pharmacy.stock.suppliers') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_desk.back_to_suppliers') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<div class="ph-kpis">
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="truck"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.col_deliveries') }}</div>
        <div class="stat-card__value">{{ $deliveries->count() }}</div>
        <div class="stat-card__hint">{{ $deliveries->first()?->received_date?->translatedFormat('d M Y') ?? __('pharmacy_stock_desk.no_delivery_yet') }}</div>
    </div>
    <div class="stat-card {{ $openOrders->isEmpty() ? '' : 'stat-card--warning' }}">
        <div class="stat-card__icon"><i data-lucide="clipboard-list"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.col_open_orders') }}</div>
        <div class="stat-card__value">{{ $openOrders->count() }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__icon"><i data-lucide="package"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.items_supplied') }}</div>
        <div class="stat-card__value">{{ $items->count() }}</div>
    </div>
</div>

<div class="ph-split">
<div class="ph-stack">

{{-- ── Deliveries ───────────────────────────────────────────────────── --}}
<div class="panel" id="deliveries">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="truck"></i> {{ __('pharmacy_stock_desk.deliveries_title') }} <span class="ph-count-inline">({{ $deliveries->count() }})</span></h3>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($deliveries as $r)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'truck', 'tone' => $r->status === 'verified' ? 'green' : ''])
                            <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="fs-mono">{{ $r->receipt_number ?? '—' }}</a></div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $r->received_date?->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="td-num">{{ $r->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}"><span class="badge {{ $r->status === 'verified' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_ops.receipt_status.' . $r->status) }}</span></td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="truck"></i>
                        <p>{{ __('pharmacy_stock_desk.deliveries_empty') }}</p>
                        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Items supplied ───────────────────────────────────────────────── --}}
<div class="panel" id="items">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="package"></i> {{ __('pharmacy_stock_desk.items_supplied') }} <span class="ph-count-inline">({{ $items->count() }})</span></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_last_delivery') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($items as $row)
                @php $rName = $row->item->medicine?->name ?? $row->item->name; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $rName])
                            <div>
                                <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.items.show', $row->item->id) }}">{{ $rName }}</a></div>
                                <div class="ph-cell__sub">{{ $row->item->medicine?->strength }} {{ $row->item->medicine?->form }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="td-num">{{ $row->packs }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_last_delivery') }}">{{ $row->last_delivery?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.items.show', $row->item->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="package"></i>
                        <p>{{ __('pharmacy_stock_desk.items_supplied_empty') }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Open orders ──────────────────────────────────────────────────── --}}
<div class="panel" id="orders">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('pharmacy_stock_desk.open_orders_title') }} <span class="ph-count-inline">({{ $openOrders->count() }})</span></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_expected') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
            </tr></thead>
            <tbody>
            @forelse($openOrders as $o)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'clipboard-list', 'tone' => 'amber'])
                            <div class="ph-cell__title fs-mono">{{ $o->po_number ?? '—' }}</div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $o->order_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_expected') }}">{{ $o->expected_delivery_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="td-num">{{ $o->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}"><span class="badge badge-warning">{{ __('pharmacy_stock_desk.order_status.' . $o->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="clipboard-check"></i>
                        <p>{{ __('pharmacy_stock_desk.open_orders_empty') }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>

{{-- ── Contact card + edit (right-hand detail panel) ─────────────────── --}}
<div class="panel ph-detail" id="contact">
    <div class="ph-detail__head">
        @include('portals.pharmacy.stock_ops._avatar', ['name' => $supplier->name, 'tone' => $supplier->status === 'active' ? '' : 'grey'])
        <div>
            <div class="ph-detail__name">{{ $supplier->name }}</div>
            <div class="ph-detail__ref">{{ __('pharmacy_stock_desk.supplier_status.' . $supplier->status) }}@if($supplier->contact_person) · {{ $supplier->contact_person }}@endif</div>
        </div>
    </div>
    <div class="ph-chips">
        @if($supplier->phone)<a href="tel:{{ $supplier->phone }}" class="ph-chip fs-tel"><i data-lucide="phone"></i> {{ $supplier->phone }}</a>@endif
        @if($supplier->email)<span class="ph-chip"><i data-lucide="mail"></i> {{ $supplier->email }}</span>@endif
        @if($supplier->address)<span class="ph-chip"><i data-lucide="map-pin"></i> {{ $supplier->address }}</span>@endif
    </div>
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="contact"></i> {{ __('pharmacy_stock_desk.contact_title') }}</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.suppliers.update', $supplier->id) }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="su-name">{{ __('pharmacy_ops.lbl_name') }}</label>
                    <input type="text" name="name" id="su-name" class="form-control" maxlength="150" value="{{ old('name', $supplier->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="su-contact">{{ __('pharmacy_ops.lbl_contact') }}</label>
                    <input type="text" name="contact_person" id="su-contact" class="form-control" maxlength="100" value="{{ old('contact_person', $supplier->contact_person) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="su-phone">{{ __('pharmacy_ops.lbl_phone') }}</label>
                    <input type="text" name="phone" id="su-phone" class="form-control" maxlength="30" inputmode="tel" value="{{ old('phone', $supplier->phone) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="su-email">{{ __('pharmacy_ops.lbl_email') }}</label>
                    <input type="email" name="email" id="su-email" class="form-control" maxlength="100" value="{{ old('email', $supplier->email) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="su-address">{{ __('pharmacy_stock_desk.lbl_address') }}</label>
                    <input type="text" name="address" id="su-address" class="form-control" maxlength="500" value="{{ old('address', $supplier->address) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="su-status">{{ __('pharmacy_ops.col_status') }}</label>
                    <select name="status" id="su-status" class="form-control">
                        @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ old('status', $supplier->status) === $st ? 'selected' : '' }}>{{ __('pharmacy_stock_desk.supplier_status.' . $st) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="su-notes">{{ __('pharmacy_ops.lbl_notes') }}</label>
                    <input type="text" name="notes" id="su-notes" class="form-control" maxlength="1000" value="{{ old('notes', $supplier->notes) }}">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="check"></i> {{ __('pharmacy_stock_desk.btn_save_supplier') }}</button>
            </div>
        </form>
    </div>
</div>

</div>

@endsection
