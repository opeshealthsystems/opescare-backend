@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipt_new_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    $oldLines = old('lines', [['medicine_id' => $prefillMedicineId ?? '', 'quantity' => '', 'lot_number' => '', 'expiry_date' => '', 'unit_cost' => '']]);
@endphp

@section('content')

<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_ops.receipt_new_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_ops.receipt_new_subtitle') }}</p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_ops.back_to_receipts') }}</a>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')

@include('portals.pharmacy.stock_ops._alerts')

<form method="POST" action="{{ route('portals.pharmacy.stock.receipts.store') }}" id="gr-form" class="os-stack">
    @csrf
    <section class="od-card os-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('pharmacy_ops.receipt_new_title') }}</h2>
        </div>
        <div class="os-card__body">
            <div class="os-grid os-grid--3">
                <div class="os-field">
                    <label class="os-label" for="gr-supplier">{{ __('pharmacy_ops.lbl_supplier') }}</label>
                    <select name="supplier_id" id="gr-supplier" class="os-input">
                        <option value="">{{ __('pharmacy_ops.lbl_no_supplier') }}</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="gr-order">{{ __('pharmacy_ops.lbl_order') }}</label>
                    <select name="purchase_order_id" id="gr-order" class="os-input">
                        <option value="">{{ __('pharmacy_ops.lbl_no_order') }}</option>
                        @foreach($orders as $o)
                        <option value="{{ $o->id }}" {{ old('purchase_order_id') === $o->id ? 'selected' : '' }}>{{ $o->po_number ?? $o->id }} · {{ $o->order_date?->translatedFormat('d M Y') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="os-grid os-grid--3">
                <div class="os-field">
                    <label class="os-label" for="gr-date">{{ __('pharmacy_ops.lbl_received_date') }}</label>
                    <input type="date" name="received_date" id="gr-date" class="os-input" max="{{ $today }}" value="{{ old('received_date', $today) }}" required>
                </div>
                <div class="os-field">
                    <label class="os-label" for="gr-number">{{ __('pharmacy_ops.lbl_receipt_number') }}</label>
                    <input type="text" name="receipt_number" id="gr-number" class="os-input" maxlength="50" value="{{ old('receipt_number') }}">
                    <div class="os-hint">{{ __('pharmacy_ops.lbl_receipt_number_hint') }}</div>
                </div>
                <div class="os-field">
                    <label class="os-label" for="gr-notes">{{ __('pharmacy_ops.lbl_notes') }}</label>
                    <input type="text" name="notes" id="gr-notes" class="os-input" maxlength="1000" value="{{ old('notes') }}">
                </div>
            </div>
        </div>
    </section>

    <section class="od-card os-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('pharmacy_ops.lines_title') }}</h2>
            <button type="button" class="od-btn od-btn--secondary" id="gr-add"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_line') }}</button>
        </div>
        <div class="os-card__body">
            <div class="os-table-wrap"><table class="od-table os-table os-lines gr-lines" id="gr-lines">
                <thead><tr>
                    <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                    <th>{{ __('pharmacy_ops.col_line_qty') }}</th>
                    <th>{{ __('pharmacy_ops.col_line_lot') }}</th>
                    <th>{{ __('pharmacy_ops.col_line_expiry') }}</th>
                    <th>{{ __('pharmacy_ops.col_line_cost') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($oldLines as $i => $line)
                <tr class="gr-line">
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <select name="lines[{{ $i }}][medicine_id]" class="os-input" required>
                            <option value="">{{ __('pharmacy_ops.choose_medicine') }}</option>
                            @foreach($catalog as $m)
                            <option value="{{ $m->id }}" {{ ($line['medicine_id'] ?? '') === $m->id ? 'selected' : '' }}>{{ $m->name }}@if($m->form) · {{ $m->form }}@endif</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_qty') }}"><input type="number" name="lines[{{ $i }}][quantity]" class="os-input" min="1" max="1000000" inputmode="numeric" value="{{ $line['quantity'] ?? '' }}" required></td>
                    <td data-label="{{ __('pharmacy_ops.col_line_lot') }}"><input type="text" name="lines[{{ $i }}][lot_number]" class="os-input" maxlength="80" value="{{ $line['lot_number'] ?? '' }}"></td>
                    <td data-label="{{ __('pharmacy_ops.col_line_expiry') }}"><input type="date" name="lines[{{ $i }}][expiry_date]" class="os-input" value="{{ $line['expiry_date'] ?? '' }}"></td>
                    <td data-label="{{ __('pharmacy_ops.col_line_cost') }}"><input type="number" name="lines[{{ $i }}][unit_cost]" class="os-input" min="0" max="100000000" step="1" inputmode="numeric" value="{{ $line['unit_cost'] ?? '' }}" placeholder="{{ __('pharmacy_ops.xaf') }}"></td>
                    <td><button type="button" class="os-icon-btn gr-remove" aria-label="{{ __('pharmacy_ops.btn_remove_line') }}"><i data-lucide="x"></i></button></td>
                </tr>
                @endforeach
                </tbody>
            </table></div>
            <p class="os-note">{{ __('pharmacy_ops.cost_hint') }}</p>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_receive') }}</button>
            </div>
        </div>
    </section>
</form>

<details class="od-card os-card os-fold">
    <summary class="od-card__head os-fold__head">
        <h2 class="od-card__title"><span class="os-fold__icon"><i data-lucide="building-2"></i></span>{{ __('pharmacy_ops.supplier_form_title') }}</h2>
        <i data-lucide="chevron-down" class="os-fold__chev"></i>
    </summary>
    <div class="os-card__body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.suppliers.store') }}" class="os-form">
            @csrf
            <input type="hidden" name="return" value="receipt">
            <div class="os-grid os-grid--3">
                <div class="os-field">
                    <label class="os-label" for="sp-name">{{ __('pharmacy_ops.lbl_name') }}</label>
                    <input type="text" name="name" id="sp-name" class="os-input" maxlength="150" required>
                </div>
                <div class="os-field">
                    <label class="os-label" for="sp-contact">{{ __('pharmacy_ops.lbl_contact') }}</label>
                    <input type="text" name="contact_person" id="sp-contact" class="os-input" maxlength="100">
                </div>
                <div class="os-field">
                    <label class="os-label" for="sp-phone">{{ __('pharmacy_ops.lbl_phone') }}</label>
                    <input type="text" name="phone" id="sp-phone" class="os-input" maxlength="30" inputmode="tel">
                </div>
                <div class="os-field">
                    <label class="os-label" for="sp-email">{{ __('pharmacy_ops.lbl_email') }}</label>
                    <input type="email" name="email" id="sp-email" class="os-input" maxlength="100">
                </div>
            </div>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</button>
            </div>
        </form>
    </div>
</details>

</div>
@endsection

@section('scripts')
<script @cspNonce>
(function () {
    var tbody = document.querySelector('#gr-lines tbody');
    var add   = document.getElementById('gr-add');
    if (!tbody || !add) return;

    function renumber() {
        Array.prototype.forEach.call(tbody.querySelectorAll('tr.gr-line'), function (row, i) {
            Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (el) {
                el.name = el.name.replace(/lines\[\d+\]/, 'lines[' + i + ']');
            });
        });
    }

    add.addEventListener('click', function () {
        var first = tbody.querySelector('tr.gr-line');
        var clone = first.cloneNode(true);
        Array.prototype.forEach.call(clone.querySelectorAll('input'), function (el) { el.value = ''; });
        Array.prototype.forEach.call(clone.querySelectorAll('select'), function (el) { el.selectedIndex = 0; });
        tbody.appendChild(clone);
        renumber();
        if (window.lucide) { window.lucide.createIcons(); }
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.gr-remove');
        if (!btn) return;
        if (tbody.querySelectorAll('tr.gr-line').length <= 1) return;
        btn.closest('tr').remove();
        renumber();
    });
})();
</script>
@endsection
