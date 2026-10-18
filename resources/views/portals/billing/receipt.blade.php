@extends('layouts.portal')

@section('title', __('billing_desk.receipt.title') . ' ' . $receipt->receipt_number)
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', $receipt->receipt_number)

@php
    use App\Services\Billing\BillingDeskService;
    use App\Services\Billing\Xaf;

    $pName  = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $method = $payment->method === BillingDeskService::METHOD_CASH
        ? __('billing_desk.method_cash')
        : (\App\Services\Payments\Domain\PaymentMethod::tryFrom($payment->method)?->label() ?? $payment->method);
@endphp

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page">

<div class="od-page__head odc-no-print">
    <div>
        <h1 class="od-page__title">{{ __('billing_desk.receipt.title') }} {{ $receipt->receipt_number }}</h1>
        <p class="od-page__sub">{{ __('billing_desk.receipt.html_note') }}</p>
    </div>
    <div class="odc-head-actions">
        <a href="{{ route('portals.billing.show', $invoice->id) }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.receipt.back') }}</a>
        <button type="button" class="od-btn od-btn--primary" data-print><i data-lucide="printer"></i> {{ __('billing_desk.receipt.print') }}</button>
    </div>
</div>

<section class="od-card odc-receipt">
    <div class="od-card__head">
        <div>
            <div class="odc-receipt__brand">OpesCare · {{ $facility?->name ?? '' }}</div>
            <div class="odc-receipt__meta">{{ __('billing_desk.receipt.number') }} <strong>{{ $receipt->receipt_number }}</strong></div>
            <div class="odc-receipt__meta">{{ __('billing_desk.receipt.issued_at') }} {{ $receipt->issued_at?->translatedFormat('d M Y H:i') }}</div>
        </div>
    </div>
    <div class="od-card__body">
        <table class="odc-receipt__table">
            <tr><td>{{ __('billing_desk.receipt.received') }}</td><td>{{ $pName !== '' ? $pName : '—' }}<br><span class="od-muted">{{ $patient?->health_id }}</span></td></tr>
            <tr><td>{{ __('billing_desk.receipt.for') }}</td><td>{{ $invoice->invoice_number }}</td></tr>
            <tr><td>{{ __('billing_desk.receipt.method') }}</td><td>{{ $method }}</td></tr>
            <tr><td>{{ __('billing_desk.col_reference') }}</td><td>{{ $payment->payment_reference }}</td></tr>
            <tr><td>{{ __('billing_desk.receipt.by') }}</td><td>{{ $cashier?->name ?? '—' }}</td></tr>
            <tr class="odc-receipt__total"><td>{{ __('billing_desk.receipt.amount') }}</td><td class="odc-money">{{ Xaf::format(Xaf::int($receipt->amount)) }}</td></tr>
            <tr><td>{{ __('billing_desk.receipt.remaining') }}</td><td class="odc-money">{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</td></tr>
        </table>
    </div>
</section>

</div>
@endsection
