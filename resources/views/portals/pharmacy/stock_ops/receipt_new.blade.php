@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipt_new_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))

@section('head')
@include('portals.pharmacy._supply_styles')
<style>
.gr-lines{width:100%;border-collapse:collapse}
.gr-lines th,.gr-lines td{padding:.35rem .35rem;vertical-align:top}
.gr-lines .form-control{min-height:44px}
.gr-lines .fs-num{max-width:8rem}
@media (max-width:760px){
  .gr-lines thead{display:none}
  .gr-lines tr{display:block;border:1px solid var(--p-border,#E2E8F0);border-radius:.5rem;padding:.5rem;margin-bottom:.6rem}
  .gr-lines td{display:block}
  .gr-lines td::before{content:attr(data-label);display:block;font-size:.75rem;font-weight:700;color:var(--p-text-muted,#64748B);margin-bottom:.15rem}
  .gr-lines .fs-num{max-width:none}
}
</style>
@endsection

@php
    $oldLines = old('lines', [['medicine_id' => '', 'quantity' => '', 'lot_number' => '', 'expiry_date' => '', 'unit_cost' => '']]);
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.receipt_new_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.receipt_new_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_ops.back_to_receipts') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

<form method="POST" action="{{ route('portals.pharmacy.stock.receipts.store') }}" id="gr-form">
    @csrf
    <div class="panel">
        <div class="panel-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="gr-supplier">{{ __('pharmacy_ops.lbl_supplier') }}</label>
                    <select name="supplier_id" id="gr-supplier" class="form-control">
                        <option value="">{{ __('pharmacy_ops.lbl_no_supplier') }}</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="gr-order">{{ __('pharmacy_ops.lbl_order') }}</label>
                    <select name="purchase_order_id" id="gr-order" class="form-control">
                        <option value="">{{ __('pharmacy_ops.lbl_no_order') }}</option>
                        @foreach($orders as $o)
                        <option value="{{ $o->id }}" {{ old('purchase_order_id') === $o->id ? 'selected' : '' }}>{{ $o->po_number ?? $o->id }} · {{ $o->order_date?->translatedFormat('d M Y') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="gr-date">{{ __('pharmacy_ops.lbl_received_date') }}</label>
                    <input type="date" name="received_date" id="gr-date" class="form-control" max="{{ $today }}" value="{{ old('received_date', $today) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="gr-number">{{ __('pharmacy_ops.lbl_receipt_number') }}</label>
                    <input type="text" name="receipt_number" id="gr-number" class="form-control" maxlength="50" value="{{ old('receipt_number') }}">
                    <div class="td-muted">{{ __('pharmacy_ops.lbl_receipt_number_hint') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="gr-notes">{{ __('pharmacy_ops.lbl_notes') }}</label>
                    <input type="text" name="notes" id="gr-notes" class="form-control" maxlength="1000" value="{{ old('notes') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="package-plus"></i> {{ __('pharmacy_ops.lines_title') }}</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="gr-add"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_line') }}</button>
        </div>
        <div class="panel-body">
            <table class="gr-lines" id="gr-lines">
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
                        <select name="lines[{{ $i }}][medicine_id]" class="form-control" required>
                            <option value="">{{ __('pharmacy_ops.choose_medicine') }}</option>
                            @foreach($catalog as $m)
                            <option value="{{ $m->id }}" {{ ($line['medicine_id'] ?? '') === $m->id ? 'selected' : '' }}>{{ $m->name }}@if($m->form) · {{ $m->form }}@endif</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_qty') }}"><input type="number" name="lines[{{ $i }}][quantity]" class="form-control fs-num" min="1" max="1000000" inputmode="numeric" value="{{ $line['quantity'] ?? '' }}" required></td>
                    <td data-label="{{ __('pharmacy_ops.col_line_lot') }}"><input type="text" name="lines[{{ $i }}][lot_number]" class="form-control" maxlength="80" value="{{ $line['lot_number'] ?? '' }}"></td>
                    <td data-label="{{ __('pharmacy_ops.col_line_expiry') }}"><input type="date" name="lines[{{ $i }}][expiry_date]" class="form-control" value="{{ $line['expiry_date'] ?? '' }}"></td>
                    <td data-label="{{ __('pharmacy_ops.col_line_cost') }}"><input type="number" name="lines[{{ $i }}][unit_cost]" class="form-control fs-num" min="0" max="100000000" step="1" inputmode="numeric" value="{{ $line['unit_cost'] ?? '' }}" placeholder="{{ __('pharmacy_ops.xaf') }}"></td>
                    <td><button type="button" class="btn btn-ghost btn-sm gr-remove" aria-label="{{ __('pharmacy_ops.btn_remove_line') }}"><i data-lucide="x"></i></button></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <p class="td-muted mt-6">{{ __('pharmacy_ops.cost_hint') }}</p>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_receive') }}</button>
            </div>
        </div>
    </div>
</form>

<details class="panel">
    <summary class="panel-header" style="cursor:pointer;">
        <i data-lucide="building-2"></i> {{ __('pharmacy_ops.supplier_form_title') }}
    </summary>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.suppliers.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sp-name">{{ __('pharmacy_ops.lbl_name') }}</label>
                    <input type="text" name="name" id="sp-name" class="form-control" maxlength="150" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sp-contact">{{ __('pharmacy_ops.lbl_contact') }}</label>
                    <input type="text" name="contact_person" id="sp-contact" class="form-control" maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sp-phone">{{ __('pharmacy_ops.lbl_phone') }}</label>
                    <input type="text" name="phone" id="sp-phone" class="form-control" maxlength="30" inputmode="tel">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sp-email">{{ __('pharmacy_ops.lbl_email') }}</label>
                    <input type="email" name="email" id="sp-email" class="form-control" maxlength="100">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-secondary fs-tap"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_add_supplier') }}</button>
            </div>
        </form>
    </div>
</details>

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
