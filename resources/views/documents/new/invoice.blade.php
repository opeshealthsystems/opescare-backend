{{-- 22 Invoice. Whole francs XAF. docs/specs/documents/22-invoice.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.inv_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $xaf   = fn ($v) => \App\Services\Documents\NewDocumentRenderer::xaf($v);
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.inv_title') }} {{ $invoice->invoice_number }}</h1>
<p class="subtitle">{{ __('doc_new_docs.inv_subtitle') }}</p>

<h2>{{ __('doc_new_docs.inv_from') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facility])
<h2>{{ __('doc_new_docs.inv_to') }}</h2>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.inv_details') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.inv_number') }}</td><td class="v id">{{ $invoice->invoice_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.inv_issued') }}</td><td class="v">{{ $stamp($invoice->issued_at) ?? __('doc_new_docs.inv_draft') }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $val($invoice->status) }}</td></tr>
    @if($invoice->voided_at)<tr><td class="k">{{ __('doc_new_docs.inv_void_reason') }}</td><td class="v">{{ $invoice->void_reason ?: $dash }}</td></tr>@endif
</table>

<h2>{{ __('doc_new_docs.inv_lines') }}</h2>
@if($invoice->items->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.inv_code') }}</th><th>{{ __('doc_new_docs.inv_description') }}</th><th>{{ __('doc_new_docs.inv_qty') }}</th><th>{{ __('doc_new_docs.inv_unit') }}</th><th>{{ __('doc_new_docs.inv_discount') }}</th><th>{{ __('doc_new_docs.inv_total') }}</th></tr></thead>
    @foreach($invoice->items as $item)
    <tr><td>{{ $item->service_code ?: $dash }}</td><td>{{ $item->description }}</td><td class="num">{{ (int) $item->quantity }}</td><td class="num">{{ $xaf($item->unit_price) }}</td><td class="num">{{ $xaf($item->discount_amount) }}</td><td class="num">{{ $xaf($item->line_total_amount) }}</td></tr>
    @endforeach
</table>
@endif

<h2>{{ __('doc_new_docs.inv_totals') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.inv_subtotal') }}</td><td class="v">{{ $xaf($invoice->subtotal_amount) }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.inv_discount') }}</td><td class="v">{{ $xaf($invoice->discount_amount) }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.inv_insurance') }}</td><td class="v">{{ $xaf($invoice->insurance_covered_amount) }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.inv_patient_share') }}</td><td class="v">{{ $xaf($invoice->patient_responsibility_amount) }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.inv_paid') }}</td><td class="v">{{ $xaf($invoice->paid_amount) }}</td></tr>
    @if((float) $invoice->refunded_amount > 0)<tr><td class="k">{{ __('doc_new_docs.inv_refunded') }}</td><td class="v">{{ $xaf($invoice->refunded_amount) }}</td></tr>@endif
    <tr><td class="k">{{ __('doc_new_docs.inv_balance') }}</td><td class="v"><strong>{{ $xaf($invoice->balance_amount) }}</strong></td></tr>
</table>
<p class="note">{{ __('doc_new_docs.inv_never_gated') }}</p>
@endsection
