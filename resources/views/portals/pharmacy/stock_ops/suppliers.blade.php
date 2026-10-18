@extends('layouts.portal')
@section('title', __('pharmacy_stock_desk.suppliers_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_stock_desk.nav_suppliers'))

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_stock_desk.suppliers_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_stock_desk.suppliers_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <a href="#supplier-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<details class="panel" id="supplier-form" @if($errors->any() || old('name')) open @endif>
    <summary class="panel-header fs-tap">
        <i data-lucide="plus-circle"></i> {{ __('pharmacy_ops.supplier_form_title') }}
    </summary>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.suppliers.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sp-name">{{ __('pharmacy_ops.lbl_name') }}</label>
                    <input type="text" name="name" id="sp-name" class="form-control" maxlength="150" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sp-contact">{{ __('pharmacy_ops.lbl_contact') }}</label>
                    <input type="text" name="contact_person" id="sp-contact" class="form-control" maxlength="100" value="{{ old('contact_person') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sp-phone">{{ __('pharmacy_ops.lbl_phone') }}</label>
                    <input type="text" name="phone" id="sp-phone" class="form-control" maxlength="30" inputmode="tel" value="{{ old('phone') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sp-email">{{ __('pharmacy_ops.lbl_email') }}</label>
                    <input type="email" name="email" id="sp-email" class="form-control" maxlength="100" value="{{ old('email') }}">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</button>
            </div>
        </form>
    </div>
</details>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('pharmacy_stock_desk.suppliers_title') }} <span class="ph-count-inline">({{ $suppliers->count() }})</span></h3>
        <a href="#supplier-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_contact') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_last_delivery') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_deliveries') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_open_orders') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($suppliers as $s)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $s->name, 'tone' => $s->status === 'active' ? '' : 'grey'])
                            <div>
                                <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->id) }}">{{ $s->name }}</a></div>
                                @if($s->email)<div class="ph-cell__sub">{{ $s->email }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_contact') }}">
                        {{ $s->contact_person ?? '—' }}
                        @if($s->phone)<div class="td-muted"><a href="tel:{{ $s->phone }}" class="fs-tel"><i data-lucide="phone"></i> {{ $s->phone }}</a></div>@endif
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_last_delivery') }}">{{ $s->last_delivery ? \Illuminate\Support\Carbon::parse($s->last_delivery)->translatedFormat('d M Y') : '—' }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_deliveries') }}" class="td-num">{{ (int) $s->deliveries_count }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_open_orders') }}">
                        @if((int) $s->open_orders_count > 0)
                            <span class="badge badge-warning">{{ (int) $s->open_orders_count }}</span>
                        @else
                            <span class="td-num">0</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}"><span class="badge {{ $s->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_stock_desk.supplier_status.' . $s->status) }}</span></td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="building-2"></i>
                        <p>{{ __('pharmacy_stock_desk.suppliers_empty') }}</p>
                        <a href="#supplier-form" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
