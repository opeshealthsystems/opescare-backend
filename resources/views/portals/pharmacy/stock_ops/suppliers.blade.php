@extends('layouts.portal')
@section('title', __('pharmacy_stock_desk.suppliers_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_stock_desk.suppliers_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_stock_desk.suppliers_subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<div class="os-kpis">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'building-2',
        'label' => __('pharmacy_stock_desk.kpi_suppliers'), 'value' => $suppliers->count()])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'green', 'icon' => 'check-circle-2',
        'label' => __('pharmacy_stock_desk.kpi_active'), 'value' => $suppliers->where('status', 'active')->count()])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'truck',
        'label' => __('pharmacy_stock_desk.col_deliveries'), 'value' => (int) $suppliers->sum('deliveries_count')])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'amber', 'icon' => 'clipboard-list',
        'label' => __('pharmacy_stock_desk.col_open_orders'), 'value' => (int) $suppliers->sum('open_orders_count'),
        'sub' => __('pharmacy_stock_desk.kpi_with_orders') . ': ' . $suppliers->filter(fn ($s) => (int) $s->open_orders_count > 0)->count()])
</div>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.suppliers_title') }} <span class="od-card__count">({{ $suppliers->count() }})</span></h2>
        <a href="#supplier-form" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_all') }}"></th>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_contact') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_last_delivery') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_deliveries') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_open_orders') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($suppliers as $s)
                <tr>
                    <td class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_row') }}"></td>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $s->name, 'tone' => $s->status === 'active' ? 'blue' : 'grey'])
                            <div class="os-cell__text">
                                <a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->id) }}" class="os-cell__title">{{ $s->name }}</a>
                                <span class="os-cell__sub">{{ $s->email ?: '—' }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_contact') }}">
                        <span class="os-cell__main">{{ $s->contact_person ?? '—' }}</span>
                        @if($s->phone)<a href="tel:{{ $s->phone }}" class="os-cell__sub">{{ $s->phone }}</a>@endif
                    </td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_last_delivery') }}">{{ $s->last_delivery ? \Illuminate\Support\Carbon::parse($s->last_delivery)->translatedFormat('d M Y') : '—' }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_deliveries') }}" class="os-num">{{ (int) $s->deliveries_count }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_open_orders') }}">
                        @if((int) $s->open_orders_count > 0)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => (int) $s->open_orders_count])
                        @else
                            <span class="os-num">0</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => match($s->status) { 'active' => 'ok', 'blacklisted' => 'low', default => 'neutral' }, 'label' => __('pharmacy_stock_desk.supplier_status.' . $s->status)])</td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.suppliers.show', $s->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => [
                                [route('portals.pharmacy.stock.suppliers.show', $s->id) . '#contact', __('pharmacy_stock_desk.contact_title'), 'contact'],
                                [route('portals.pharmacy.stock.suppliers.show', $s->id) . '#deliveries', __('pharmacy_stock_desk.deliveries_title'), 'truck'],
                                [route('portals.pharmacy.stock.receipts.create'), __('pharmacy_ops.btn_new_receipt'), 'plus'],
                            ]])
                        </div>
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 9, 'icon' => 'building-2', 'text' => __('pharmacy_stock_desk.suppliers_empty'),
                    'href' => '#supplier-form', 'btnIcon' => 'plus', 'btn' => __('pharmacy_ops.btn_add_supplier')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<details class="od-card os-card os-fold" id="supplier-form" @if($errors->any() || old('name')) open @endif>
    <summary class="od-card__head os-fold__head">
        <h2 class="od-card__title"><span class="os-fold__icon"><i data-lucide="plus"></i></span>{{ __('pharmacy_ops.supplier_form_title') }}</h2>
        <i data-lucide="chevron-down" class="os-fold__chev"></i>
    </summary>
    <div class="os-card__body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.suppliers.store') }}" class="os-form">
            @csrf
            <div class="os-grid os-grid--4">
                <div class="os-field">
                    <label class="os-label" for="sp-name">{{ __('pharmacy_ops.lbl_name') }}</label>
                    <input type="text" name="name" id="sp-name" class="os-input" maxlength="150" value="{{ old('name') }}" required>
                </div>
                <div class="os-field">
                    <label class="os-label" for="sp-contact">{{ __('pharmacy_ops.lbl_contact') }}</label>
                    <input type="text" name="contact_person" id="sp-contact" class="os-input" maxlength="100" value="{{ old('contact_person') }}">
                </div>
                <div class="os-field">
                    <label class="os-label" for="sp-phone">{{ __('pharmacy_ops.lbl_phone') }}</label>
                    <input type="text" name="phone" id="sp-phone" class="os-input" maxlength="30" inputmode="tel" value="{{ old('phone') }}">
                </div>
                <div class="os-field">
                    <label class="os-label" for="sp-email">{{ __('pharmacy_ops.lbl_email') }}</label>
                    <input type="email" name="email" id="sp-email" class="os-input" maxlength="100" value="{{ old('email') }}">
                </div>
            </div>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</button>
            </div>
        </form>
    </div>
</details>

</div>
@endsection
