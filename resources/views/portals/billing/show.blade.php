@extends('layouts.portal')

@section('title', __('billing_desk.invoice') . ' ' . $invoice->invoice_number)
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', $invoice->invoice_number)

@php
    use App\Services\Billing\BillingDeskService;
    use App\Services\Billing\Xaf;

    $statusBadge = fn (string $s) => 'badge badge-' . match ($s) {
        'paid'      => 'success',
        'part_paid' => 'info',
        'issued'    => 'warning',
        'void'      => 'danger',
        default     => 'neutral',
    };
    $refundBadge = fn (string $s) => 'badge badge-' . match ($s) {
        'processed' => 'success',
        'approved'  => 'info',
        'pending'   => 'warning',
        'rejected'  => 'danger',
        default     => 'neutral',
    };
    $methodLabel = fn (string $m) => $m === BillingDeskService::METHOD_CASH
        ? __('billing_desk.method_cash')
        : (\App\Services\Payments\Domain\PaymentMethod::tryFrom($m)?->label() ?? $m);

    $pName   = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $balance = Xaf::int($invoice->balance_amount);
    $payable = in_array($invoice->status, [BillingDeskService::STATUS_ISSUED, BillingDeskService::STATUS_PART_PAID], true);
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('billing_desk.invoice') }} {{ $invoice->invoice_number }}
            <span class="{{ $statusBadge($invoice->status) }}">{{ __('billing_desk.status.'.$invoice->status) }}</span>
        </h1>
        <p class="page-subtitle">
            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
            @if($patient?->health_id) · {{ $patient->health_id }} @endif
        </p>
    </div>
    <a href="{{ route('portals.billing.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

{{-- ── Lines ──────────────────────────────────────────────────────────── --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="receipt"></i> {{ __('billing_desk.lines_title') }}</h2>
        <div class="td-muted">
            {{ __('billing_desk.raised_on') }} {{ $invoice->created_at?->format('d M Y H:i') }}
            @if($invoice->issued_at) · {{ __('billing_desk.issued_on') }} {{ $invoice->issued_at->format('d M Y H:i') }} @endif
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('billing_desk.col_description') }}</th>
                <th>{{ __('billing_desk.col_code') }}</th>
                <th>{{ __('billing_desk.col_qty') }}</th>
                <th>{{ __('billing_desk.col_unit') }}</th>
                <th>{{ __('billing_desk.col_line_total') }}</th>
            </tr></thead>
            <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td data-label="{{ __('billing_desk.col_description') }}">{{ $item->description }}</td>
                    <td data-label="{{ __('billing_desk.col_code') }}" class="td-muted">{{ $item->service_code ?? '—' }}</td>
                    <td data-label="{{ __('billing_desk.col_qty') }}">{{ Xaf::int($item->quantity) }}</td>
                    <td data-label="{{ __('billing_desk.col_unit') }}">{{ Xaf::format(Xaf::int($item->unit_price)) }}</td>
                    <td data-label="{{ __('billing_desk.col_line_total') }}" class="td-strong">{{ Xaf::format(Xaf::int($item->line_total_amount)) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="4" class="td-muted">{{ __('billing_desk.total_due') }}</td><td class="td-strong">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td></tr>
                <tr><td colspan="4" class="td-muted">{{ __('billing_desk.paid') }}</td><td>{{ Xaf::format(Xaf::int($invoice->paid_amount)) }}</td></tr>
                @if(Xaf::int($invoice->refunded_amount) > 0)
                <tr><td colspan="4" class="td-muted">{{ __('billing_desk.refunded') }}</td><td>{{ Xaf::format(Xaf::int($invoice->refunded_amount)) }}</td></tr>
                @endif
                <tr><td colspan="4" class="td-muted">{{ __('billing_desk.balance') }}</td><td class="td-strong">{{ Xaf::format($balance) }}</td></tr>
            </tfoot>
        </table>
    </div>
    @if($invoice->status === BillingDeskService::STATUS_VOID)
        <div class="panel-body">
            <div class="alert alert-danger"><i data-lucide="ban"></i>
                <div>{{ __('billing_desk.voided_on') }} {{ $invoice->voided_at?->format('d M Y H:i') }} — {{ __('billing_desk.void_reason') }}: {{ $invoice->void_reason }}</div>
            </div>
        </div>
    @endif
</div>

{{-- ── Payments ───────────────────────────────────────────────────────── --}}
<div class="panel mb-6">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="banknote"></i> {{ __('billing_desk.payments_title') }}</h2></div>
    @if($payments->isEmpty())
        <div class="panel-body td-muted">{{ __('billing_desk.payments_empty') }}</div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('billing_desk.col_reference') }}</th>
                    <th>{{ __('billing_desk.col_method') }}</th>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refunded') }}</th>
                    <th>{{ __('billing_desk.col_taken_by') }}</th>
                    <th>{{ __('billing_desk.col_taken_at') }}</th>
                    <th class="row-actions">{{ __('billing_desk.col_receipt') }}</th>
                </tr></thead>
                <tbody>
                @foreach($payments as $payment)
                    @php $receipt = $receipts[$payment->id] ?? null; @endphp
                    <tr>
                        <td data-label="{{ __('billing_desk.col_reference') }}"><span class="td-strong">{{ $payment->payment_reference }}</span></td>
                        <td data-label="{{ __('billing_desk.col_method') }}">{{ $methodLabel($payment->method) }}</td>
                        <td data-label="{{ __('billing_desk.col_amount') }}">{{ Xaf::format(Xaf::int($payment->amount)) }}</td>
                        <td data-label="{{ __('billing_desk.refunded') }}">{{ Xaf::format(Xaf::int($payment->refunded_amount)) }}</td>
                        <td data-label="{{ __('billing_desk.col_taken_by') }}" class="td-muted">{{ $payment->cashier?->name ?? '—' }}</td>
                        <td data-label="{{ __('billing_desk.col_taken_at') }}" class="td-muted">{{ $payment->created_at?->format('d M Y H:i') }}</td>
                        <td class="row-actions" data-label="{{ __('billing_desk.col_receipt') }}">
                            @if($receipt)
                                <a href="{{ route('portals.billing.receipt', $receipt->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="printer"></i> {{ $receipt->receipt_number }}</a>
                            @endif
                        </td>
                    </tr>
                    @if(Xaf::int($payment->amount) - Xaf::int($payment->refunded_amount) > 0)
                    <tr>
                        <td colspan="7">
                            <form method="POST" action="{{ route('portals.billing.refund.request', $payment->id) }}" class="row-actions-inline">
                                @csrf
                                <span class="td-muted">{{ __('billing_desk.refund.request_title') }}</span>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" name="amount_xaf" class="form-control" style="max-width:160px" placeholder="{{ __('billing_desk.amount_label') }}" required aria-label="{{ __('billing_desk.amount_label') }}">
                                <input type="text" name="reason" class="form-control" style="flex:1;min-width:200px" placeholder="{{ __('billing_desk.refund.reason_label') }}" required minlength="3" maxlength="500" aria-label="{{ __('billing_desk.refund.reason_label') }}">
                                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="undo-2"></i> {{ __('billing_desk.refund.request_button') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Refunds on this invoice ────────────────────────────────────────── --}}
@if($refunds->isNotEmpty())
<div class="panel mb-6">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="undo-2"></i> {{ __('billing_desk.refund.title') }}</h2></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('billing_desk.col_amount') }}</th>
                <th>{{ __('billing_desk.refund.col_reason') }}</th>
                <th>{{ __('billing_desk.refund.col_status') }}</th>
                <th>{{ __('billing_desk.refund.col_requested') }}</th>
                <th class="row-actions">{{ __('billing_desk.refund.col_decision') }}</th>
            </tr></thead>
            <tbody>
            @foreach($refunds as $refund)
                <tr>
                    <td data-label="{{ __('billing_desk.col_amount') }}" class="td-strong">{{ Xaf::format(Xaf::int($refund->amount)) }}</td>
                    <td data-label="{{ __('billing_desk.refund.col_reason') }}">{{ $refund->reason }}@if($refund->status === 'rejected' && $refund->notes) <div class="td-muted">{{ $refund->notes }}</div>@endif</td>
                    <td data-label="{{ __('billing_desk.refund.col_status') }}"><span class="{{ $refundBadge($refund->status) }}">{{ __('billing_desk.refund.status.'.$refund->status) }}</span></td>
                    <td data-label="{{ __('billing_desk.refund.col_requested') }}" class="td-muted">{{ $refund->created_at?->format('d M Y H:i') }}</td>
                    <td class="row-actions" data-label="{{ __('billing_desk.refund.col_decision') }}">
                        @if($refund->status === 'pending' && $canRefund)
                            <form method="POST" action="{{ route('portals.billing.refund.approve', $refund->id) }}" style="display:inline">@csrf
                                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('billing_desk.refund.approve') }}</button>
                            </form>
                        @elseif($refund->status === 'pending')
                            <span class="td-muted">{{ __('billing_desk.refund.no_authority') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Mobile money prompts ───────────────────────────────────────────── --}}
@if($charges->isNotEmpty())
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="smartphone"></i> {{ __('billing_desk.momo.title') }}</h2>
        <form method="POST" action="{{ route('portals.billing.sync_mobile', $invoice->id) }}">@csrf
            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="refresh-cw"></i> {{ __('billing_desk.momo.sync_button') }}</button>
        </form>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('billing_desk.col_method') }}</th>
                <th>{{ __('billing_desk.momo.col_number') }}</th>
                <th>{{ __('billing_desk.col_amount') }}</th>
                <th>{{ __('billing_desk.momo.col_status') }}</th>
                <th>{{ __('billing_desk.momo.col_sent') }}</th>
            </tr></thead>
            <tbody>
            @foreach($charges as $charge)
                <tr>
                    <td data-label="{{ __('billing_desk.col_method') }}">{{ $charge->method?->label() ?? '—' }}</td>
                    <td data-label="{{ __('billing_desk.momo.col_number') }}" class="td-muted">{{ $charge->payer_msisdn_masked ?? '—' }}</td>
                    <td data-label="{{ __('billing_desk.col_amount') }}">{{ Xaf::format((int) $charge->amount_minor) }}</td>
                    <td data-label="{{ __('billing_desk.momo.col_status') }}"><span class="badge badge-neutral">{{ $charge->status?->value ?? '—' }}</span></td>
                    <td data-label="{{ __('billing_desk.momo.col_sent') }}" class="td-muted">{{ $charge->created_at?->format('d M Y H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Actions ────────────────────────────────────────────────────────── --}}
@if($invoice->status === BillingDeskService::STATUS_DRAFT)
<div class="panel mb-6">
    <div class="panel-body row-actions-inline">
        <form method="POST" action="{{ route('portals.billing.issue', $invoice->id) }}">@csrf
            <button type="submit" class="btn btn-primary"><i data-lucide="send"></i> {{ __('billing_desk.issue_button') }}</button>
        </form>
    </div>
</div>
@endif

@if($payable)
<div class="panel form-panel mb-6">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="hand-coins"></i> {{ __('billing_desk.take_payment') }}</h2></div>
    <div class="panel-body">
        @if($session === null)
            <div class="alert alert-warning mb-4"><i data-lucide="wallet"></i>
                <div>{{ __('billing_desk.no_open_session') }} <a href="{{ route('portals.billing.sessions') }}">{{ __('billing_desk.open_session_link') }}</a></div>
            </div>
        @endif
        <form method="POST" action="{{ route('portals.billing.pay', $invoice->id) }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="pay-amount">{{ __('billing_desk.amount_label') }}</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="pay-amount" name="amount_xaf" class="form-control" value="{{ old('amount_xaf', $balance) }}" required>
                <div class="form-hint">{{ __('billing_desk.amount_hint', ['balance' => Xaf::format($balance)]) }}</div>
            </div>
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="pay-method">{{ __('billing_desk.method_label') }}</label>
                <select id="pay-method" name="method" class="form-control" required>
                    <option value="{{ BillingDeskService::METHOD_CASH }}" @selected(old('method', 'cash') === 'cash')>{{ __('billing_desk.method_cash') }}</option>
                    @foreach($methods as $m)
                        <option value="{{ $m->value }}" @selected(old('method') === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="pay-phone">{{ __('billing_desk.phone_label') }}</label>
                <input type="tel" id="pay-phone" name="phone" class="form-control" value="{{ old('phone') }}" autocomplete="off">
                <div class="form-hint">{{ __('billing_desk.phone_hint') }}</div>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> {{ __('billing_desk.pay_button') }}</button>
        </form>
    </div>
</div>
@endif

@if($invoice->status !== BillingDeskService::STATUS_VOID)
<div class="panel form-panel">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="ban"></i> {{ __('billing_desk.void_title') }}</h2></div>
    <div class="panel-body">
        <p class="form-hint mb-4">{{ __('billing_desk.void_hint') }}</p>
        <form method="POST" action="{{ route('portals.billing.void', $invoice->id) }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="void-reason">{{ __('billing_desk.void_reason_label') }}</label>
                <input type="text" id="void-reason" name="reason" class="form-control" required minlength="3" maxlength="500">
            </div>
            <button type="submit" class="btn btn-danger"><i data-lucide="ban"></i> {{ __('billing_desk.void_button') }}</button>
        </form>
    </div>
</div>
@endif

@endsection
