@extends('layouts.portal')

@section('title', __('billing_desk.invoice') . ' ' . $invoice->invoice_number . ' — OpesCare')

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

{{-- Read-only invoice for facility administrators. Deliberately no issue,
     pay, void or refund control: those stay on the billing desk
     (capability:billing). Nothing here gates care on payment. --}}
@php
    use App\Services\Billing\BillingDeskService;
    use App\Services\Billing\Xaf;
    $pName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $methodLabel = fn (?string $m) => $m === BillingDeskService::METHOD_CASH
        ? __('billing_desk.method_cash')
        : (\App\Services\Payments\Domain\PaymentMethod::tryFrom((string) $m)?->label() ?? ($m ?? '—'));
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('billing_desk.invoice') }} {{ $invoice->invoice_number }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--{{ BillingDeskService::invoiceStatusPill($invoice->status) }}">{{ __('billing_desk.status.' . $invoice->status) }}</span>
            · {{ $pName !== '' ? $pName : __('facility_admin.invoice_patient_unknown') }}
            · {{ __('billing_desk.raised_on') }} {{ $invoice->created_at?->translatedFormat('d M Y H:i') }}
        </p>
    </div>
    <a href="{{ route('portals.facility.admin.invoices') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('billing_desk.lines_title') }} <span class="od-card__count">({{ $invoice->items->count() }})</span></h2>
    </header>
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('billing_desk.col_description') }}</th>
                    <th>{{ __('billing_desk.col_qty') }}</th>
                    <th>{{ __('billing_desk.col_unit') }}</th>
                    <th>{{ __('billing_desk.col_line_total') }}</th>
                </tr></thead>
                <tbody>
                @forelse($invoice->items as $item)
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td><span class="od-table__name">{{ $item->description }}</span></td>
                        <td>{{ Xaf::int($item->quantity) }}</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($item->unit_price)) }}</td>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($item->line_total_amount)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="od-muted">—</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.total_due') }}</td><td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td></tr>
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.paid') }}</td><td class="odc-money">{{ Xaf::format(Xaf::int($invoice->paid_amount)) }}</td></tr>
                    @if(Xaf::int($invoice->refunded_amount) > 0)
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.refunded') }}</td><td class="odc-money">{{ Xaf::format(Xaf::int($invoice->refunded_amount)) }}</td></tr>
                    @endif
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.balance') }}</td><td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</td></tr>
                </tfoot>
            </table>
        </div>
        @if($invoice->status === BillingDeskService::STATUS_VOID)
            <div class="odc-note odc-note--err odc-note--inset"><i data-lucide="ban"></i>
                <div>{{ __('billing_desk.voided_on') }} {{ $invoice->voided_at?->translatedFormat('d M Y H:i') }} — {{ __('billing_desk.void_reason') }}: {{ $invoice->void_reason }}</div>
            </div>
        @endif
    </div>
</section>

<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.payments_title') }} <span class="od-card__count">({{ $payments->count() }})</span></h2></header>
    <div class="od-card__body">
        @if($payments->isEmpty())
            <p class="od-muted">{{ __('billing_desk.payments_empty') }}</p>
        @else
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
                <thead><tr>
                    <th>{{ __('billing_desk.col_reference') }}</th>
                    <th>{{ __('billing_desk.col_method') }}</th>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refunded') }}</th>
                </tr></thead>
                <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td><span class="od-table__name">{{ $payment->payment_reference }}</span><span class="od-table__sub">{{ $payment->created_at?->translatedFormat('d M Y H:i') }}</span></td>
                        <td>{{ $methodLabel($payment->method) }}</td>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($payment->amount)) }}</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($payment->refunded_amount)) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

@if($refunds->isNotEmpty())
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.refund.title') }} <span class="od-card__count">({{ $refunds->count() }})</span></h2></header>
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refund.col_reason') }}</th>
                    <th>{{ __('billing_desk.refund.col_status') }}</th>
                    <th>{{ __('billing_desk.refund.col_requested') }}</th>
                </tr></thead>
                <tbody>
                @foreach($refunds as $refund)
                    <tr>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($refund->amount)) }}</td>
                        <td>{{ $refund->reason }}</td>
                        <td>{{ __('billing_desk.refund.status.' . $refund->status) }}</td>
                        <td>{{ $refund->created_at?->translatedFormat('d M Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endif

<div class="odc-note odc-note--info"><i data-lucide="info"></i><div class="odc-note__text">{{ __('facility_admin.invoices_subtitle') }}</div></div>

</div>
@endsection
