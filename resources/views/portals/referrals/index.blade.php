@extends('layouts.portal')

@section('title', __('referral_desk.title'))
@section('breadcrumb_home', __('referral_desk.title'))
@section('breadcrumb_home_url', route('portals.referrals.index'))
@section('breadcrumb_section', __('referral_desk.tab_' . $tab))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    $statusPill = fn (string $s): string => match ($s) {
        'draft'     => 'neutral',
        'sent'      => 'pending',
        'accepted'  => 'normal',
        'completed' => 'ready',
        'rejected', 'cancelled' => 'low',
        default     => 'neutral',
    };
@endphp

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('referral_desk.title') }}</h1>
        <p class="od-page__sub">{{ __('referral_desk.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.referrals.create') }}" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('referral_desk.new') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card" aria-labelledby="odc-ref-title">
    <header class="od-card__head">
        <h2 class="od-card__title" id="odc-ref-title">{{ __('referral_desk.tab_' . $tab) }} <span class="od-card__count">({{ method_exists($referrals, 'total') ? $referrals->total() : $referrals->count() }})</span></h2>
    </header>

    <nav class="od-tabs" aria-label="{{ __('referral_desk.title') }}">
        @foreach(['sent', 'received'] as $t)
            <a href="{{ route('portals.referrals.index', ['tab' => $t, 'status' => $status ?: null]) }}" class="od-tab {{ $tab === $t ? 'is-active' : '' }}" @if($tab === $t) aria-current="page" @endif>
                <i data-lucide="{{ $t === 'sent' ? 'send' : 'inbox' }}"></i> {{ __('referral_desk.tab_' . $t) }}
            </a>
        @endforeach
    </nav>

    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.referrals.index') }}" class="odc-toolbar">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="status" class="od-select" aria-label="{{ __('referral_desk.col_status') }}">
                <option value="">{{ __('referral_desk.all_statuses') }}</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>@enum($s)</option>
                @endforeach
            </select>
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('referral_desk.filter') }}</button>
        </form>

        @if($referrals->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="{{ $tab === 'sent' ? 'send' : 'inbox' }}"></i></span>
                <div class="od-empty__title">{{ __('referral_desk.none') }}</div>
                <p class="od-empty__text">{{ __('hospital_desk.empty_referrals_' . $tab . '_hint') }}</p>
                @if($tab === 'sent')
                <a href="{{ route('portals.referrals.create') }}" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('hospital_desk.empty_referrals_action') }}</a>
                @endif
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('referral_desk.col_patient') }}</th>
                    <th>{{ __('referral_desk.col_from') }}</th>
                    <th>{{ __('referral_desk.col_to') }}</th>
                    <th>{{ __('referral_desk.col_urgency') }}</th>
                    <th>{{ __('referral_desk.col_status') }}</th>
                    <th>{{ __('referral_desk.col_created') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($referrals as $i => $r)
                    @php
                        $current = $service->currentStatus($r);
                        $hid = $r->patient?->health_id ?? '';
                        $first = method_exists($referrals, 'firstItem') ? (int) $referrals->firstItem() : 1;
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $first + $loop->index }}</td>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $tone($hid !== '' ? $hid : (string) $r->id) }}" aria-hidden="true"><i data-lucide="user"></i></span>
                                <span class="od-table__name">{{ $hid !== '' ? $hid : '—' }}</span>
                            </div>
                        </td>
                        <td>{{ $r->referringFacility?->name ?? '—' }}</td>
                        <td>
                            <span class="od-table__name">{{ $r->receivingFacility?->name ?? ($r->receiving_specialty ?: ($r->receiving_provider_name ?: '—')) }}</span>
                            @if($r->receivingFacility && $r->receiving_specialty)<span class="od-table__sub">{{ $r->receiving_specialty }}</span>@endif
                        </td>
                        <td><span class="od-pill od-pill--{{ $r->urgency === 'routine' ? 'neutral' : 'urgent' }}">{{ __('referral_desk.urgency.' . $r->urgency) }}</span></td>
                        <td><span class="od-pill od-pill--dot od-pill--{{ $statusPill((string) $current) }}">@enum($current)</span></td>
                        <td>{{ $r->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="od-table__actions"><a href="{{ route('portals.referrals.show', $r->id) }}" class="od-btn od-btn--row">{{ __('referral_desk.open') }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if(! $referrals->isEmpty() && method_exists($referrals, 'hasPages') && $referrals->hasPages())
        <div class="odc-pager">{{ $referrals->links() }}</div>
    @endif
</section>

</div>
@endsection
