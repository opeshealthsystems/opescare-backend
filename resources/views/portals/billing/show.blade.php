@extends('layouts.portal')

@section('title', __('billing_desk.invoice') . ' ' . $invoice->invoice_number)

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    use App\Services\Billing\BillingDeskService;
    use App\Services\Billing\Xaf;

    $statusPill = fn (string $s): string => match ($s) {
        'paid'      => 'ready',
        'part_paid' => 'verifying',
        'issued'    => 'pending',
        'void'      => 'low',
        default     => 'neutral',
    };
    $refundPill = fn (string $s): string => match ($s) {
        'processed' => 'ready',
        'approved'  => 'normal',
        'pending'   => 'pending',
        'rejected'  => 'low',
        default     => 'neutral',
    };
    $methodLabel = fn (string $m) => $m === BillingDeskService::METHOD_CASH
        ? __('billing_desk.method_cash')
        : (\App\Services\Payments\Domain\PaymentMethod::tryFrom($m)?->label() ?? $m);

    $pName   = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $pInit   = mb_strtoupper(mb_substr(trim((string) ($patient->first_name ?? '')), 0, 1) . mb_substr(trim((string) ($patient->last_name ?? '')), 0, 1));
    $pTone   = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($pName !== '' ? $pName : (string) $invoice->patient_id) % 6];
    $balance = Xaf::int($invoice->balance_amount);
    $payable = in_array($invoice->status, [BillingDeskService::STATUS_ISSUED, BillingDeskService::STATUS_PART_PAID], true);
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('billing_desk.invoice') }} {{ $invoice->invoice_number }}</h1>
        <p class="od-page__sub">{{ __('billing_desk.raised_on') }} {{ $invoice->created_at?->translatedFormat('d M Y H:i') }}@if($invoice->issued_at) · {{ __('billing_desk.issued_on') }} {{ $invoice->issued_at->translatedFormat('d M Y H:i') }}@endif</p>
    </div>
    <a href="{{ route('documents.new.invoice', $invoice->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_invoice_pdf') }}</a>
    <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
@endif

<div class="od-grid od-grid--main">

<div class="od-stack">
{{-- ── Lines ──────────────────────────────────────────────────────────── --}}
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
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td><span class="od-table__name">{{ $item->description }}</span><span class="od-table__sub">{{ __('billing_desk.col_code') }}: {{ $item->service_code ?? '—' }}</span></td>
                        <td>{{ Xaf::int($item->quantity) }}</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($item->unit_price)) }}</td>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($item->line_total_amount)) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.total_due') }}</td><td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td></tr>
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.paid') }}</td><td class="odc-money">{{ Xaf::format(Xaf::int($invoice->paid_amount)) }}</td></tr>
                    @if(Xaf::int($invoice->refunded_amount) > 0)
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.refunded') }}</td><td class="odc-money">{{ Xaf::format(Xaf::int($invoice->refunded_amount)) }}</td></tr>
                    @endif
                    <tr><td colspan="4" class="od-muted">{{ __('billing_desk.balance') }}</td><td class="odc-money odc-strong">{{ Xaf::format($balance) }}</td></tr>
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

{{-- ── Payments ───────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.payments_title') }} <span class="od-card__count">({{ $payments->count() }})</span></h2></header>
    @if($payments->isEmpty())
        <div class="od-empty od-empty--compact">
            <span class="od-empty__icon"><i data-lucide="banknote"></i></span>
            <p class="od-empty__text">{{ __('billing_desk.payments_empty') }}</p>
        </div>
    @else
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
                <thead><tr>
                    <th>{{ __('billing_desk.col_reference') }}</th>
                    <th>{{ __('billing_desk.col_method') }}</th>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refunded') }}</th>
                    <th>{{ __('billing_desk.col_taken_by') }}</th>
                    <th>{{ __('billing_desk.col_receipt') }}</th>
                </tr></thead>
                <tbody>
                @foreach($payments as $payment)
                    @php $receipt = $receipts[$payment->id] ?? null; @endphp
                    <tr>
                        <td><span class="od-table__name">{{ $payment->payment_reference }}</span><span class="od-table__sub">{{ $payment->created_at?->translatedFormat('d M Y H:i') }}</span></td>
                        <td>{{ $methodLabel($payment->method) }}</td>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($payment->amount)) }}</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($payment->refunded_amount)) }}</td>
                        <td>{{ $payment->cashier?->name ?? '—' }}</td>
                        <td class="od-table__actions">
                            @if($receipt)
                                <a href="{{ route('portals.billing.receipt', $receipt->id) }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="printer"></i> {{ $receipt->receipt_number }}</a>
                                <a href="{{ route('documents.new.receipt', $receipt->id) }}" class="od-btn od-btn--secondary od-btn--sm" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_receipt_pdf') }}</a>
                            @endif
                        </td>
                    </tr>
                    @if(Xaf::int($payment->amount) - Xaf::int($payment->refunded_amount) > 0)
                    <tr class="odc-subrow">
                        <td colspan="6">
                            <form method="POST" action="{{ route('portals.billing.refund.request', $payment->id) }}" class="odc-inline-form">
                                @csrf
                                <span class="od-muted">{{ __('billing_desk.refund.request_title') }}</span>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" name="amount_xaf" class="od-input" placeholder="{{ __('billing_desk.amount_label') }}" required aria-label="{{ __('billing_desk.amount_label') }}">
                                <input type="text" name="reason" class="od-input" placeholder="{{ __('billing_desk.refund.reason_label') }}" required minlength="3" maxlength="500" aria-label="{{ __('billing_desk.refund.reason_label') }}">
                                <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="undo-2"></i> {{ __('billing_desk.refund.request_button') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</section>

{{-- ── Refunds on this invoice ────────────────────────────────────────── --}}
@if($refunds->isNotEmpty())
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.refund.title') }} <span class="od-card__count">({{ $refunds->count() }})</span></h2></header>
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
                <thead><tr>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refund.col_reason') }}</th>
                    <th>{{ __('billing_desk.refund.col_status') }}</th>
                    <th>{{ __('billing_desk.refund.col_requested') }}</th>
                    <th>{{ __('billing_desk.refund.col_decision') }}</th>
                </tr></thead>
                <tbody>
                @foreach($refunds as $refund)
                    <tr>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($refund->amount)) }}</td>
                        <td>{{ $refund->reason }}@if($refund->status === 'rejected' && $refund->notes)<span class="od-table__sub">{{ $refund->notes }}</span>@endif</td>
                        <td><span class="od-pill od-pill--{{ $refundPill($refund->status) }}">{{ __('billing_desk.refund.status.'.$refund->status) }}</span></td>
                        <td>{{ $refund->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="od-table__actions">
                            @if($refund->status === 'pending' && $canRefund)
                                <form method="POST" action="{{ route('portals.billing.refund.approve', $refund->id) }}">@csrf
                                    <button type="submit" class="od-btn od-btn--row"><i data-lucide="check"></i> {{ __('billing_desk.refund.approve') }}</button>
                                </form>
                            @elseif($refund->status === 'pending')
                                <span class="od-muted">{{ __('billing_desk.refund.no_authority') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endif

{{-- ── Mobile money prompts ───────────────────────────────────────────── --}}
@if($charges->isNotEmpty())
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('billing_desk.momo.title') }}</h2>
        @if($onlinePay ?? false)
        <form method="POST" action="{{ route('portals.billing.sync_mobile', $invoice->id) }}">@csrf
            <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="refresh-cw"></i> {{ __('billing_desk.momo.sync_button') }}</button>
        </form>
        @endif
    </header>
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
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
                        <td>{{ $charge->method?->label() ?? '—' }}</td>
                        <td>{{ $charge->payer_msisdn_masked ?? '—' }}</td>
                        <td class="odc-money">{{ Xaf::format((int) $charge->amount_minor) }}</td>
                        <td><span class="od-pill od-pill--neutral">{{ $charge->status?->label() ?? '—' }}</span></td>
                        <td>{{ $charge->created_at?->translatedFormat('d M Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endif
</div>

{{-- ── Right-hand detail panel: who, how much, what next ─────────────── --}}
<div class="od-stack odc-aside">
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('billing_desk.invoice') }}</h2>
        <span class="od-pill od-pill--dot od-pill--{{ $statusPill($invoice->status) }}">{{ __('billing_desk.status.'.$invoice->status) }}</span>
    </header>
    <div class="od-card__body od-detail">
        <div class="od-detail__head">
            <span class="od-avatar od-avatar--lg od-tint--{{ $pTone }}" aria-hidden="true">{{ $pInit !== '' ? $pInit : '—' }}</span>
            <div class="od-detail__main">
                <h3 class="od-detail__name">{{ $pName !== '' ? $pName : '—' }}</h3>
                <div class="od-detail__ref">
                    <span>{{ $invoice->invoice_number }}</span>
                    <span>{{ $invoice->created_at?->translatedFormat('d M Y') }}</span>
                </div>
                @if($patient?->health_id)
                <div class="od-detail__facts"><span class="od-detail__fact"><i data-lucide="id-card"></i> {{ $patient->health_id }}</span></div>
                @endif
            </div>
        </div>
        <div class="od-detail__people">
            <div class="od-person">
                <span class="od-person__icon"><i data-lucide="receipt"></i></span>
                <div>
                    <span class="od-person__label">{{ __('billing_desk.total_due') }}</span>
                    <span class="od-person__name odc-money">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</span>
                </div>
            </div>
            <div class="od-person">
                <span class="od-person__icon"><i data-lucide="hand-coins"></i></span>
                <div>
                    <span class="od-person__label">{{ __('billing_desk.balance') }}</span>
                    <span class="od-person__name odc-money">{{ Xaf::format($balance) }}</span>
                </div>
            </div>
        </div>
        @if($invoice->status === BillingDeskService::STATUS_DRAFT)
            <form method="POST" action="{{ route('portals.billing.issue', $invoice->id) }}">@csrf
                <button type="submit" class="od-btn od-btn--primary od-btn--block"><i data-lucide="send"></i> {{ __('billing_desk.issue_button') }}</button>
            </form>
        @endif
    </div>
</section>

@if($payable)
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.take_payment') }}</h2></header>
    <div class="od-card__body">
        @if($session === null)
            <div class="odc-note odc-note--warn odc-note--inset"><i data-lucide="wallet"></i>
                <div>{{ __('billing_desk.no_open_session') }} <a href="{{ route('portals.billing.sessions') }}">{{ __('billing_desk.open_session_link') }}</a></div>
            </div>
        @endif
        <form method="POST" action="{{ route('portals.billing.pay', $invoice->id) }}" class="odc-form">
            @csrf
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="pay-amount">{{ __('billing_desk.amount_label') }}</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="pay-amount" name="amount_xaf" class="od-input" value="{{ old('amount_xaf', $balance) }}" required>
                <p class="odc-hint">{{ __('billing_desk.amount_hint', ['balance' => Xaf::format($balance)]) }}</p>
            </div>
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="pay-method">{{ __('billing_desk.method_label') }}</label>
                <select id="pay-method" name="method" class="od-select" required>
                    <option value="{{ BillingDeskService::METHOD_CASH }}" @selected(old('method', 'cash') === 'cash')>{{ __('billing_desk.method_cash') }}</option>
                    @foreach($methods as $m)
                        <option value="{{ $m->value }}" @selected(old('method') === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            @if($onlinePay ?? false)
            <div class="odc-field">
                <label class="odc-label" for="pay-phone">{{ __('billing_desk.phone_label') }}</label>
                <input type="tel" id="pay-phone" name="phone" class="od-input" value="{{ old('phone') }}" autocomplete="off">
                <p class="odc-hint">{{ __('billing_desk.phone_hint') }}</p>
            </div>
            @else
            <div class="odc-note odc-note--inset" data-pay-offline><i data-lucide="store"></i>
                <div>{{ __('billing_desk.momo.offline_note') }}</div>
            </div>
            @endif
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check-circle"></i> {{ __('billing_desk.pay_button') }}</button>
            </div>
        </form>
    </div>
</section>
@endif

@if($invoice->status !== BillingDeskService::STATUS_VOID)
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.void_title') }}</h2></header>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.billing.void', $invoice->id) }}" class="odc-form">
            @csrf
            <p class="odc-hint">{{ __('billing_desk.void_hint') }}</p>
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="void-reason">{{ __('billing_desk.void_reason_label') }}</label>
                <input type="text" id="void-reason" name="reason" class="od-input" required minlength="3" maxlength="500">
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--danger"><i data-lucide="ban"></i> {{ __('billing_desk.void_button') }}</button>
            </div>
        </form>
    </div>
</section>
@endif
</div>

</div>
</div>
@endsection
