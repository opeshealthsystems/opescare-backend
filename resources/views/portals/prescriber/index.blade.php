@extends('layouts.portal')
@section('title', __('prescriber.title'))
@section('breadcrumb_home', __('prescriber.title'))
@section('breadcrumb_home_url', route('portals.prescriber.index'))
@section('breadcrumb_section', __('prescriber.nav'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    // The statuses a prescription can actually carry, in the order a prescriber scans them.
    $rxStatuses = ['active', 'dispensed', 'partially_dispensed', 'voided', 'expired', 'cancelled'];
    $rxPill = fn ($s) => match ($s) {
        'active'              => 'pending',
        'dispensed'           => 'ready',
        'partially_dispensed' => 'verifying',
        default               => 'neutral',
    };
    $rxDot = fn ($s) => match ($s) {
        'active'              => 'pending',
        'dispensed'           => 'ready',
        'partially_dispensed' => 'normal',
        default               => null,
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
        <h1 class="od-page__title">{{ __('prescriber.title') }}</h1>
        <p class="od-page__sub">{{ __('prescriber.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.prescriber.create') }}" class="od-btn od-btn--primary"><x-od-icon name="rx" /> {{ __('prescriber.new') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card" aria-labelledby="odc-rx-title">
    <header class="od-card__head">
        <h2 class="od-card__title" id="odc-rx-title">{{ __('prescriber.nav') }} <span class="od-card__count">({{ method_exists($prescriptions, 'total') ? $prescriptions->total() : $prescriptions->count() }})</span></h2>
    </header>

    <nav class="od-tabs" aria-label="{{ __('prescriber.col_status') }}">
        <a href="{{ route('portals.prescriber.index') }}" class="od-tab {{ $status ? '' : 'is-active' }}" @if(! $status) aria-current="page" @endif>{{ __('prescriber.filter_all') }}</a>
        @foreach($rxStatuses as $rxStatus)
            <a href="{{ route('portals.prescriber.index', ['status' => $rxStatus]) }}" class="od-tab {{ $status === $rxStatus ? 'is-active' : '' }}" @if($status === $rxStatus) aria-current="page" @endif>
                @if($rxDot($rxStatus))<span class="od-tab__dot od-tab__dot--{{ $rxDot($rxStatus) }}"></span>@endif
                {{ __('prescriber.st_' . $rxStatus) }}
            </a>
        @endforeach
    </nav>

    <div class="od-card__body">
        @if($prescriptions->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><x-od-icon name="rx" /></span>
                <div class="od-empty__title">{{ __('prescriber.empty') }}</div>
                <p class="od-empty__text">{{ __('prescriber.empty_desc') }}</p>
                <a href="{{ route('portals.prescriber.create') }}" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('hospital_desk.empty_prescriber_action') }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('prescriber.col_patient') }}</th>
                    <th>{{ __('prescriber.col_items') }}</th>
                    <th>{{ __('prescriber.col_status') }}</th>
                    <th>{{ __('prescriber.col_issued') }}</th>
                    <th>{{ __('prescriber.view') }}</th>
                </tr></thead>
                <tbody>
                @foreach($prescriptions as $rx)
                    @php
                        $rxPatient = $rx->patient;
                        $rxName    = trim(($rxPatient->first_name ?? '') . ' ' . ($rxPatient->last_name ?? ''));
                        $rxFirst   = $rx->items->first();
                        $rxExtra   = max($rx->items->count() - 1, 0);
                        $first     = method_exists($prescriptions, 'firstItem') ? (int) $prescriptions->firstItem() : 1;
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $first + $loop->index }}</td>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($rxName !== '' ? $rxName : (string) $rx->id) }}" aria-hidden="true">{{ $initials($rxPatient->first_name ?? '', $rxPatient->last_name ?? '') }}</span>
                                <div>
                                    <a href="{{ route('portals.prescriber.show', $rx->id) }}" class="od-table__name">{{ $rxName !== '' ? $rxName : '—' }}</a>
                                    @if($rxPatient?->health_id)<span class="od-table__sub">{{ $rxPatient->health_id }}</span>@endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($rxFirst)
                                <span class="od-table__name">{{ $rxFirst->drug_name }}</span>
                                @if($rxExtra > 0)<span class="od-table__sub">+{{ $rxExtra }} {{ __('public.pharmacy_portal.lbl_items') }}</span>@endif
                            @else
                                <span class="od-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="od-pill od-pill--{{ $rxPill($rx->status) }}">
                                @if(\Illuminate\Support\Facades\Lang::has('prescriber.st_' . $rx->status))
                                    {{ __('prescriber.st_' . $rx->status) }}
                                @else
                                    @enum($rx->status)
                                @endif
                            </span>
                        </td>
                        <td>
                            {{ $rx->prescribed_at?->translatedFormat('d M Y') ?? '—' }}
                            @if(filled($rx->reference))<span class="od-table__sub" data-rx-code>{{ $rx->reference }}</span>@endif
                        </td>
                        <td class="od-table__actions">
                            <a href="{{ route('portals.prescriber.show', $rx->id) }}" class="od-btn od-btn--row">{{ __('prescriber.view') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($prescriptions->hasPages())
        <div class="odc-pager">{{ $prescriptions->links() }}</div>
    @endif
</section>

</div>
@endsection
