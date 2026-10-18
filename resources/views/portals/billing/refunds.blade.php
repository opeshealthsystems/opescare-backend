@extends('layouts.portal')

@section('title', __('billing_desk.refund.title'))
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', __('billing_desk.refund.title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    use App\Services\Billing\Xaf;
    $refundPill = fn (string $s): string => match ($s) {
        'processed' => 'ready',
        'approved'  => 'normal',
        'pending'   => 'pending',
        'rejected'  => 'low',
        default     => 'neutral',
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('billing_desk.refund.title') }}</h1>
        <p class="od-page__sub">{{ __('billing_desk.refund.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('billing_desk.refund.title') }} <span class="od-card__count">({{ method_exists($refunds, 'total') ? $refunds->total() : $refunds->count() }})</span></h2>
    </header>
    @if($refunds->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="undo-2"></i></span>
            <div class="od-empty__title">{{ __('billing_desk.refund.empty') }}</div>
        </div>
    @else
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('billing_desk.col_patient') }}</th>
                    <th>{{ __('billing_desk.col_number') }}</th>
                    <th>{{ __('billing_desk.col_amount') }}</th>
                    <th>{{ __('billing_desk.refund.col_reason') }}</th>
                    <th>{{ __('billing_desk.refund.col_status') }}</th>
                    <th>{{ __('billing_desk.refund.col_requested') }}</th>
                    <th>{{ __('billing_desk.refund.col_decision') }}</th>
                </tr></thead>
                <tbody>
                @foreach($refunds as $refund)
                    @php
                        $p     = $patients[$refund->patient_id] ?? null;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $first = method_exists($refunds, 'firstItem') ? (int) $refunds->firstItem() : 1;
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $first + $loop->index }}</td>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($pName !== '' ? $pName : (string) $refund->patient_id) }}" aria-hidden="true">{{ $initials($p->first_name ?? '', $p->last_name ?? '') }}</span>
                                <div>
                                    <span class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</span>
                                    @if($p?->health_id)<span class="od-table__sub">{{ $p->health_id }}</span>@endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($refund->invoice)<a href="{{ route('portals.billing.show', $refund->invoice->id) }}" class="od-table__name">{{ $refund->invoice->invoice_number }}</a>@else — @endif
                            @if($refund->payment?->payment_reference)<span class="od-table__sub">{{ $refund->payment->payment_reference }}</span>@endif
                        </td>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($refund->amount)) }}</td>
                        <td>{{ $refund->reason }}@if($refund->status === 'rejected' && $refund->notes)<span class="od-table__sub">{{ $refund->notes }}</span>@endif</td>
                        <td><span class="od-pill od-pill--{{ $refundPill($refund->status) }}">{{ __('billing_desk.refund.status.'.$refund->status) }}</span></td>
                        <td>{{ $refund->created_at?->translatedFormat('d M Y') }}<span class="od-table__sub">{{ $refund->created_at?->format('H:i') }}</span></td>
                        <td>
                            @if($refund->status === 'pending' && $canRefund)
                                <div class="odc-inline-form">
                                    <form method="POST" action="{{ route('portals.billing.refund.approve', $refund->id) }}">@csrf
                                        <button type="submit" class="od-btn od-btn--row"><i data-lucide="check"></i> {{ __('billing_desk.refund.approve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('portals.billing.refund.reject', $refund->id) }}" class="odc-inline-form">@csrf
                                        <input type="text" name="reason" class="od-input" placeholder="{{ __('billing_desk.refund.reject_reason') }}" required minlength="3" maxlength="500" aria-label="{{ __('billing_desk.refund.reject_reason') }}">
                                        <button type="submit" class="od-btn od-btn--danger od-btn--sm"><i data-lucide="x"></i> {{ __('billing_desk.refund.reject') }}</button>
                                    </form>
                                </div>
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
    @if($refunds->hasPages())<div class="odc-pager">{{ $refunds->links() }}</div>@endif
    @endif
</section>

</div>
@endsection
