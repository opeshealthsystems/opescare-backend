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
<style>
    @media print {
        .portal-sidebar, .portal-topbar, .page-header, #sidebar-overlay, .no-print { display: none !important; }
        .receipt-sheet { box-shadow: none; border: 0; }
    }
    .receipt-sheet { max-width: 640px; margin: 0 auto; border: 1px solid #d7dde5; border-radius: 8px; padding: 32px; background: #fff; }
    .receipt-sheet .brand { color: #0F4C81; font-weight: 700; font-size: 1.15rem; letter-spacing: .02em; }
    .receipt-sheet table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .receipt-sheet td { padding: 6px 0; vertical-align: top; }
    .receipt-sheet td:last-child { text-align: right; }
    .receipt-sheet .total td { border-top: 2px solid #0F4C81; font-weight: 700; padding-top: 10px; }
</style>
@endsection

@section('content')

<div class="page-header no-print">
    <div><h1 class="page-title">{{ __('billing_desk.receipt.title') }} {{ $receipt->receipt_number }}</h1>
        <p class="page-subtitle">{{ __('billing_desk.receipt.html_note') }}</p></div>
    <div class="row-actions-inline">
        <a href="{{ route('portals.billing.show', $invoice->id) }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.receipt.back') }}</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i data-lucide="printer"></i> {{ __('billing_desk.receipt.print') }}</button>
    </div>
</div>

<div class="receipt-sheet">
    <div class="brand">OpesCare · {{ $facility?->name ?? '' }}</div>
    <div class="td-muted">{{ __('billing_desk.receipt.number') }} <strong>{{ $receipt->receipt_number }}</strong></div>
    <div class="td-muted">{{ __('billing_desk.receipt.issued_at') }} {{ $receipt->issued_at?->format('d M Y H:i') }}</div>

    <table>
        <tr><td>{{ __('billing_desk.receipt.received') }}</td><td>{{ $pName !== '' ? $pName : '—' }}<br><span class="td-muted">{{ $patient?->health_id }}</span></td></tr>
        <tr><td>{{ __('billing_desk.receipt.for') }}</td><td>{{ $invoice->invoice_number }}</td></tr>
        <tr><td>{{ __('billing_desk.receipt.method') }}</td><td>{{ $method }}</td></tr>
        <tr><td>{{ __('billing_desk.col_reference') }}</td><td>{{ $payment->payment_reference }}</td></tr>
        <tr><td>{{ __('billing_desk.receipt.by') }}</td><td>{{ $cashier?->name ?? '—' }}</td></tr>
        <tr class="total"><td>{{ __('billing_desk.receipt.amount') }}</td><td>{{ Xaf::format(Xaf::int($receipt->amount)) }}</td></tr>
        <tr><td>{{ __('billing_desk.receipt.remaining') }}</td><td>{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</td></tr>
    </table>
</div>

@endsection
