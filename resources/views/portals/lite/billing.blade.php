@extends('layouts.lite')
@section('title', __('public.lite_portal.action_billing', [], app()->getLocale()) ?: 'Billing')
@php $l = app()->getLocale(); @endphp

@section('content')

<h1 class="lite-page-title">{{ __('public.lite_portal.billing_heading', [], $l) ?: 'Quick billing' }}</h1>
<p class="lite-page-sub">{{ __('public.lite_portal.billing_subtitle', [], $l) ?: 'Issue a basic receipt for services rendered' }}</p>

@if($patient)
<div class="lite-card lite-mb">
    <div class="lite-card__body lite-patient-chip">
        <div class="lite-patient-chip__avatar"><i data-lucide="user"></i></div>
        <div>
            <div class="lite-td-strong">{{ $patient->first_name }} {{ $patient->last_name }}</div>
            <div class="lite-mono--accent">{{ $patient->health_id }}</div>
        </div>
        <a href="{{ route('portals.lite.lookup') }}" class="lite-muted-link lite-ml-auto">{{ __('public.lite_portal.lnk_change', [], $l) ?: 'Change' }}</a>
    </div>
</div>
@else
<div class="lite-alert lite-alert--info">
    <i data-lucide="info"></i>
    <span>{{ __('public.lite_portal.billing_no_patient', [], $l) ?: 'No patient selected.' }} <a href="{{ route('portals.lite.lookup') }}" class="lite-alert__link">{{ __('public.lite_portal.lnk_select_patient', [], $l) ?: 'Select patient →' }}</a></span>
</div>
@endif

<form id="billingForm" method="POST" action="{{ route('portals.staff.billing.store') }}">
    @csrf
    @if($patient)
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
    @endif

    <div class="lite-card">
        <div class="lite-card__head lite-card__head--row">
            <span>{{ __('public.lite_portal.billing_line_items', [], $l) ?: 'Line items' }}</span>
            <button type="button" onclick="addLineItem()" class="lite-btn lite-btn--outline lite-btn--sm">+ {{ __('public.lite_portal.billing_add_item', [], $l) ?: 'Add item' }}</button>
        </div>
        <div class="lite-card__body lite-card__body--flush">
            <table class="lite-table" id="lineItemsTable">
                <thead><tr>
                    <th>{{ __('public.lite_portal.billing_col_desc', [], $l) ?: 'Description' }}</th>
                    <th>{{ __('public.lite_portal.billing_col_qty', [], $l) ?: 'Qty' }}</th>
                    <th>{{ __('public.lite_portal.billing_col_amount', [], $l) ?: 'Amount (FCFA)' }}</th>
                    <th></th>
                </tr></thead>
                <tbody id="lineItems">
                    <tr id="row-0">
                        <td><input type="text" name="items[0][description]" class="lite-input lite-input--cell" placeholder="{{ __('public.lite_portal.billing_ph_service', [], $l) ?: 'Service…' }}"></td>
                        <td class="lite-col-narrow"><input type="number" name="items[0][quantity]" class="lite-input lite-input--cell" value="1" min="1" onchange="calcTotal()"></td>
                        <td class="lite-col-amt"><input type="number" name="items[0][unit_price]" class="lite-input lite-input--cell" placeholder="0" step="1" onchange="calcTotal()"></td>
                        <td class="lite-col-x">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="lite-card">
        <div class="lite-card__body">
            <div class="lite-row lite-row--between lite-mb">
                <span class="lite-td-strong">{{ __('public.lite_portal.billing_lbl_total', [], $l) ?: 'Total' }}</span>
                <span class="lite-total">FCFA <span id="totalAmt">0</span></span>
            </div>
            <div class="lite-form-group">
                <label class="lite-label">{{ __('public.lite_portal.billing_lbl_payment_mode', [], $l) ?: 'Payment mode' }}</label>
                <select name="payment_mode" class="lite-input">
                    <option value="cash">{{ __('public.lite_portal.billing_opt_cash', [], $l) ?: 'Cash' }}</option>
                    <option value="pos">{{ __('public.lite_portal.billing_opt_pos', [], $l) ?: 'POS / Card' }}</option>
                    <option value="transfer">{{ __('public.lite_portal.billing_opt_transfer', [], $l) ?: 'Bank transfer' }}</option>
                    <option value="wallet">{{ __('public.lite_portal.billing_opt_wallet', [], $l) ?: 'Wallet' }}</option>
                    <option value="nhis">{{ __('public.lite_portal.billing_opt_nhis', [], $l) ?: 'NHIS' }}</option>
                </select>
            </div>
            <div class="lite-form-group">
                <label class="lite-label">{{ __('public.lite_portal.billing_lbl_note', [], $l) ?: 'Note (optional)' }}</label>
                <input type="text" name="note" class="lite-input" placeholder="{{ __('public.lite_portal.billing_ph_note', [], $l) ?: 'e.g. consultation fee…' }}">
            </div>
        </div>
    </div>

    <button type="submit" class="lite-btn lite-btn--success lite-btn--full lite-mt">
        <i data-lucide="receipt"></i> {{ __('public.lite_portal.billing_btn_issue', [], $l) ?: 'Issue receipt' }}
    </button>
    <div class="lite-empty lite-mt">
        <a href="{{ route('portals.lite.dashboard') }}" class="lite-muted-link">{{ __('public.lite_portal.billing_btn_cancel', [], $l) ?: '← Cancel' }}</a>
    </div>
</form>

@endsection

@section('scripts')
<script>
let rowCount = 1;
const phService = @json(__('public.lite_portal.billing_ph_service') ?: 'Service…');

function addLineItem() {
    const tbody = document.getElementById('lineItems');
    const i = rowCount++;
    const tr = document.createElement('tr');
    tr.id = 'row-' + i;
    tr.innerHTML = `
        <td><input type="text" name="items[${i}][description]" class="lite-input lite-input--cell" placeholder="${phService}"></td>
        <td class="lite-col-narrow"><input type="number" name="items[${i}][quantity]" class="lite-input lite-input--cell" value="1" min="1" onchange="calcTotal()"></td>
        <td class="lite-col-amt"><input type="number" name="items[${i}][unit_price]" class="lite-input lite-input--cell" placeholder="0" step="1" onchange="calcTotal()"></td>
        <td class="lite-col-x">
            <button type="button" onclick="this.closest('tr').remove();calcTotal()" class="lite-btn--icon"><i data-lucide="x"></i></button>
        </td>`;
    tbody.appendChild(tr);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function calcTotal() {
    let total = 0;
    document.querySelectorAll('#lineItems tr').forEach(tr => {
        const qty = parseFloat(tr.querySelector('input[name*="[quantity]"]')?.value || 1);
        const amt = parseFloat(tr.querySelector('input[name*="[unit_price]"]')?.value || 0);
        if (!isNaN(qty) && !isNaN(amt)) total += qty * amt;
    });
    document.getElementById('totalAmt').textContent = total.toLocaleString('fr-FR');
}
</script>
@endsection
