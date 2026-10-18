{{-- 23 Payment receipt. Whole francs XAF. docs/specs/documents/23-payment-receipt.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.rcp_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $xaf   = fn ($v) => \App\Services\Documents\NewDocumentRenderer::xaf($v);
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.rcp_title') }} {{ $receipt->receipt_number }}</h1>
<p class="subtitle">{{ __('doc_new_docs.rcp_subtitle') }}</p>

@include('pdf.layouts.secure-facility', ['facility' => $facility])
<h2>{{ __('doc_new_docs.inv_to') }}</h2>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.rcp_payment') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.rcp_number') }}</td><td class="v id">{{ $receipt->receipt_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.rcp_amount') }}</td><td class="v"><strong>{{ $xaf($receipt->amount) }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_new_docs.rcp_date') }}</td><td class="v">{{ $stamp($receipt->issued_at ?? $payment?->confirmed_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.rcp_method') }}</td><td class="v">{{ $val($payment?->method) ?: $dash }}@if($payment?->gateway) — {{ $val($payment->gateway) }}@endif</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.rcp_reference') }}</td><td class="v id">{{ $payment?->payment_reference ?: $dash }}</td></tr>
    @if($payment?->gateway_transaction_id)<tr><td class="k">{{ __('doc_new_docs.rcp_transaction') }}</td><td class="v id">{{ $payment->gateway_transaction_id }}</td></tr>@endif
    <tr><td class="k">{{ __('doc_new_docs.rcp_payer') }}</td><td class="v">{{ $payment?->payer_name ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.rcp_invoice') }}</td><td class="v id">{{ $invoice->invoice_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.inv_balance') }}</td><td class="v">{{ $xaf($invoice->balance_amount) }}</td></tr>
</table>
@include('pdf.layouts.secure-signature', ['name' => $cashier?->name, 'role' => __('doc_new_docs.rcp_cashier')])
@endsection
