@extends('layouts.portal')

@section('title', __('billing_desk.title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    use App\Services\Billing\Xaf;
    $statusPill = fn (string $s): string => match ($s) {
        'paid'      => 'ready',
        'part_paid' => 'verifying',
        'issued'    => 'pending',
        'void'      => 'low',
        default     => 'neutral',
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $allCount = array_sum(array_map('intval', (array) ($counts ?? [])));
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('billing_desk.list_title') }}</h1>
        <p class="od-page__sub">{{ __('billing_desk.list_subtitle') }}</p>
    </div>
    <div class="odc-head-actions">
        <a href="{{ route('portals.billing.sessions') }}" class="od-btn od-btn--secondary"><i data-lucide="wallet"></i> {{ __('billing_desk.session.title') }}</a>
        <a href="{{ route('portals.billing.create') }}" class="od-btn od-btn--primary"><i data-lucide="file-plus-2"></i> {{ __('billing_desk.new_invoice') }}</a>
    </div>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($session === null)
<div class="odc-note odc-note--warn" role="status">
    <i data-lucide="wallet"></i>
    <div>{{ __('billing_desk.no_session_banner') }} <a href="{{ route('portals.billing.sessions') }}">{{ __('billing_desk.open_session_link') }}</a></div>
</div>
@endif

<section class="od-card" aria-labelledby="odc-inv-title">
    <header class="od-card__head">
        <h2 class="od-card__title" id="odc-inv-title">{{ __('billing_desk.list_title') }} <span class="od-card__count">({{ method_exists($invoices, 'total') ? $invoices->total() : $invoices->count() }})</span></h2>
        <a href="{{ route('portals.billing.refunds') }}" class="od-card__link">{{ __('billing_desk.refund.title') }}</a>
    </header>

    <nav class="od-tabs" aria-label="{{ __('billing_desk.col_status') }}">
        <a href="{{ route('portals.billing.index', array_filter(['health_id' => $healthId])) }}" class="od-tab {{ ($status ?? '') === '' ? 'is-active' : '' }}" @if(($status ?? '') === '') aria-current="page" @endif>{{ __('billing_desk.all_statuses') }} <span class="od-tab__count">({{ $allCount }})</span></a>
        @foreach($statuses as $s)
            <a href="{{ route('portals.billing.index', array_filter(['status' => $s, 'health_id' => $healthId])) }}" class="od-tab {{ $status === $s ? 'is-active' : '' }}" @if($status === $s) aria-current="page" @endif>
                {{ __('billing_desk.status.'.$s) }} <span class="od-tab__count">({{ $counts[$s] ?? 0 }})</span>
            </a>
        @endforeach
    </nav>

    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.billing.index') }}" class="odc-toolbar">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="od-field">
                <i data-lucide="search"></i>
                <input type="text" id="billing-health-id" name="health_id" class="od-input" value="{{ $healthId }}" autocomplete="off" placeholder="OC-XXX-000" aria-label="{{ __('billing_desk.health_id') }}" aria-describedby="billing-hid-hint">
            </label>
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> {{ __('billing_desk.lookup') }}</button>
            <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary">{{ __('billing_desk.clear') }}</a>
        </form>
        <p class="odc-hint" id="billing-hid-hint">{{ __('billing_desk.health_id_hint') }}</p>

        @if($notFound)
            <div class="odc-note odc-note--err odc-note--inset" role="alert"><i data-lucide="user-x"></i><div>{{ __('billing_desk.not_found') }}</div></div>
        @endif

        @if($invoices->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="receipt"></i></span>
                <div class="od-empty__title">{{ __('billing_desk.empty_list') }}</div>
                <a href="{{ route('portals.billing.create') }}" class="od-btn od-btn--primary"><i data-lucide="file-plus-2"></i> {{ __('billing_desk.new_invoice') }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('billing_desk.col_patient') }}</th>
                    <th>{{ __('billing_desk.col_number') }}</th>
                    <th>{{ __('billing_desk.col_status') }}</th>
                    <th>{{ __('billing_desk.col_total') }}</th>
                    <th>{{ __('billing_desk.col_paid') }}</th>
                    <th>{{ __('billing_desk.col_balance') }}</th>
                    <th>{{ __('billing_desk.col_date') }}</th>
                    <th>{{ __('billing_desk.col_open') }}</th>
                </tr></thead>
                <tbody>
                @foreach($invoices as $invoice)
                    @php
                        $p     = $patients[$invoice->patient_id] ?? null;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $first = method_exists($invoices, 'firstItem') ? (int) $invoices->firstItem() : 1;
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $first + $loop->index }}</td>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($pName !== '' ? $pName : (string) $invoice->patient_id) }}" aria-hidden="true">{{ $initials($p->first_name ?? '', $p->last_name ?? '') }}</span>
                                <div>
                                    <span class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</span>
                                    @if($p?->health_id)<span class="od-table__sub">{{ $p->health_id }}</span>@endif
                                </div>
                            </div>
                        </td>
                        <td><span class="od-table__name">{{ $invoice->invoice_number }}</span></td>
                        <td><span class="od-pill od-pill--{{ $statusPill($invoice->status) }}">{{ __('billing_desk.status.'.$invoice->status) }}</span></td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($invoice->patient_responsibility_amount)) }}</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($invoice->paid_amount) - Xaf::int($invoice->refunded_amount)) }}</td>
                        <td class="odc-money odc-strong">{{ Xaf::format(Xaf::int($invoice->balance_amount)) }}</td>
                        <td>{{ $invoice->created_at?->translatedFormat('d M Y') ?? '—' }}<span class="od-table__sub">{{ $invoice->created_at?->format('H:i') }}</span></td>
                        <td class="od-table__actions"><a href="{{ route('portals.billing.show', $invoice->id) }}" class="od-btn od-btn--row">{{ __('billing_desk.col_open') }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($invoices->hasPages())
        <div class="odc-pager">{{ $invoices->links() }}</div>
    @endif
</section>

</div>
@endsection
