@extends('layouts.portal')

@section('title', __('billing_desk.session.title'))
@section('breadcrumb_home', __('billing_desk.title'))
@section('breadcrumb_home_url', route('portals.billing.index'))
@section('breadcrumb_section', __('billing_desk.session.title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    use App\Services\Billing\Xaf;
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    $initialsOf = function (?string $name): string {
        $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $name)) ?: [], fn ($w) => preg_match('/^\p{L}/u', $w)));
        $s = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
        return $s !== '' ? $s : '—';
    };
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('billing_desk.session.title') }}</h1>
        <p class="od-page__sub">{{ __('billing_desk.session.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.billing.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('billing_desk.back_to_list') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
@endif

@if($open === null)
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('billing_desk.session.open_title') }}</h2></header>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.billing.sessions.open') }}" class="odc-form">
            @csrf
            <div class="odc-grid odc-grid--1">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="session-float">{{ __('billing_desk.session.float_label') }}</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" id="session-float" name="float_xaf" class="od-input" value="{{ old('float_xaf', '0') }}" required>
                    <p class="odc-hint">{{ __('billing_desk.session.float_hint') }}</p>
                </div>
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="unlock"></i> {{ __('billing_desk.session.open_button') }}</button>
            </div>
        </form>
    </div>
</section>
@else
@php $float = Xaf::int($open->opening_float_amount); @endphp
<div class="od-kpis">
    <div class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="clock"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('billing_desk.session.opened_at') }}</span>
            <span class="od-kpi__value">{{ $open->opened_at?->format('H:i') }}</span>
            <span class="od-kpi__sub">{{ $open->opened_at?->translatedFormat('d M Y') }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--teal">
        <span class="od-kpi__icon"><i data-lucide="wallet"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('billing_desk.session.float') }}</span>
            <span class="od-kpi__value odc-money">{{ Xaf::format($float) }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="banknote"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('billing_desk.session.cash_taken') }}</span>
            <span class="od-kpi__value odc-money">{{ Xaf::format($cashSoFar) }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="calculator"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('billing_desk.session.expected') }}</span>
            <span class="od-kpi__value odc-money">{{ Xaf::format($float + $cashSoFar) }}</span>
        </span>
    </div>
</div>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('billing_desk.session.current_title') }}</h2>
        <span class="od-pill od-pill--dot od-pill--ready">{{ __('billing_desk.session.status_open') }}</span>
    </header>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.billing.sessions.close', $open->id) }}" class="odc-form">
            @csrf
            <div class="odc-grid">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="session-counted">{{ __('billing_desk.session.counted_label') }}</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" id="session-counted" name="counted_xaf" class="od-input" value="{{ old('counted_xaf') }}" required>
                </div>
                <div class="odc-field">
                    <label class="odc-label" for="session-note">{{ __('billing_desk.session.note_label') }}</label>
                    <input type="text" id="session-note" name="note" class="od-input" maxlength="500" value="{{ old('note') }}">
                </div>
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="lock"></i> {{ __('billing_desk.session.close_button') }}</button>
            </div>
        </form>
    </div>
</section>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('billing_desk.session.history_title') }} <span class="od-card__count">({{ method_exists($sessions, 'total') ? $sessions->total() : $sessions->count() }})</span></h2>
    </header>
    @if($sessions->isEmpty())
        <div class="od-empty od-empty--compact">
            <span class="od-empty__icon"><i data-lucide="history"></i></span>
            <p class="od-empty__text">{{ __('billing_desk.session.empty') }}</p>
        </div>
    @else
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table odc-nowrap">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('billing_desk.session.col_cashier') }}</th>
                    <th>{{ __('billing_desk.col_status') }}</th>
                    <th>{{ __('billing_desk.session.col_opened') }}</th>
                    <th>{{ __('billing_desk.session.col_closed') }}</th>
                    <th>{{ __('billing_desk.session.col_float') }}</th>
                    <th>{{ __('billing_desk.session.col_cash') }}</th>
                    <th>{{ __('billing_desk.session.col_expected') }}</th>
                    <th>{{ __('billing_desk.session.col_counted') }}</th>
                    <th>{{ __('billing_desk.session.col_variance') }}</th>
                </tr></thead>
                <tbody>
                @foreach($sessions as $s)
                    @php
                        $variance = $s->variance_amount === null ? null : Xaf::int($s->variance_amount);
                        $cName = $cashiers[$s->cashier_id]->name ?? '';
                        $first = method_exists($sessions, 'firstItem') ? (int) $sessions->firstItem() : 1;
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $first + $loop->index }}</td>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($cName !== '' ? $cName : (string) $s->cashier_id) }}" aria-hidden="true">{{ $initialsOf($cName) }}</span>
                                <span class="od-table__name">{{ $cName !== '' ? $cName : '—' }}</span>
                            </div>
                        </td>
                        <td><span class="od-pill od-pill--dot od-pill--{{ $s->status === 'open' ? 'ready' : 'neutral' }}">{{ $s->status === 'open' ? __('billing_desk.session.status_open') : __('billing_desk.session.status_closed') }}</span></td>
                        <td>{{ $s->opened_at?->translatedFormat('d M Y') }}<span class="od-table__sub">{{ $s->opened_at?->format('H:i') }}</span></td>
                        <td>@if($s->closed_at){{ $s->closed_at->translatedFormat('d M Y') }}<span class="od-table__sub">{{ $s->closed_at->format('H:i') }}</span>@else — @endif</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($s->opening_float_amount)) }}</td>
                        <td class="odc-money">{{ Xaf::format(Xaf::int($s->cash_total_amount)) }}</td>
                        <td class="odc-money">{{ $s->expected_cash_amount === null ? '—' : Xaf::format(Xaf::int($s->expected_cash_amount)) }}</td>
                        <td class="odc-money">{{ $s->counted_cash_amount === null ? '—' : Xaf::format(Xaf::int($s->counted_cash_amount)) }}</td>
                        <td>
                            @if($variance === null) — @else
                                <span class="od-pill od-pill--{{ $variance === 0 ? 'ready' : 'low' }}">{{ Xaf::format($variance) }}</span>
                            @endif
                            @if($s->closing_note)<span class="od-table__sub">{{ $s->closing_note }}</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($sessions->hasPages())<div class="odc-pager">{{ $sessions->links() }}</div>@endif
    @endif
</section>

</div>
@endsection
