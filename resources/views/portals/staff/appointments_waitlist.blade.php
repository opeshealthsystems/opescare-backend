@extends('layouts.portal')

@section('title', __('waitlist.staff_title'))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('waitlist.staff_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    $lApp   = app()->getLocale();
    $canAct   = \App\Support\StaffCapabilities::can(auth()->user(), 'front_desk', 'schedule');
    $canChart = \App\Support\StaffCapabilities::can(auth()->user(), 'clinical_chart');
    $odInitials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $odTone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('waitlist.staff_title') }}</h1>
        <p class="od-page__sub">{{ __('waitlist.staff_subtitle') }}</p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.appointments') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i>
            {{ __('public.stf_back') }}
        </a>
    </div>
</header>

@if(session('success'))
    <div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('waitlist.staff_title') }} <span class="od-card__count">({{ method_exists($entries, 'total') ? $entries->total() : $entries->count() }})</span></h2>
    </header>
@if($entries->isEmpty())
    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="list-ordered"></i></span>
        <div class="od-empty__title">{{ __('waitlist.staff_empty_title') }}</div>
        <p class="od-empty__text">{{ __('waitlist.staff_empty_desc') }}</p>
        <p class="od-empty__text">{{ __('hospital_desk.empty_waitlist_hint') }}</p>
        @if($canAct)
        <a href="{{ route('portals.staff.appointments.create') }}" class="od-btn od-btn--primary">
            <i data-lucide="calendar-plus"></i> {{ __('hospital_desk.empty_appointments_action') }}
        </a>
        @endif
    </div>
@else
    <div class="od-card__body od-card__body--flush">
    <div class="od-table-wrap">
        <table class="od-table odc-stack">
            <thead>
                <tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('waitlist.col_patient') }}</th>
                    <th>{{ __('public.staff_portal.col_type', [], $lApp) }}</th>
                    <th>{{ __('waitlist.col_window') }}</th>
                    <th>{{ __('waitlist.col_urgency') }}</th>
                    <th>{{ __('public.staff_portal.col_status', [], $lApp) }}</th>
                    <th>{{ __('waitlist.col_joined') }}</th>
                    <th>{{ __('public.staff_portal.col_actions', [], $lApp) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $i => $entry)
                @php
                    $name = trim(($entry->patient?->first_name ?? '') . ' ' . ($entry->patient?->last_name ?? ''));
                    $healthId = $entry->patient?->health_id ?? '—';
                    $urgencyPill = $entry->urgency === 'urgent' ? 'urgent' : 'neutral';
                    $statusPill  = $entry->status === 'notified' ? 'teal' : 'pending';
                    $from = $entry->preferred_earliest_date?->translatedFormat('d M Y');
                    $to   = $entry->preferred_latest_date?->translatedFormat('d M Y');
                @endphp
                <tr>
                    <td class="od-table__num">{{ ($entries->firstItem() ?? 1) + $i }}</td>
                    <td data-label="{{ __('waitlist.col_patient') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $odTone($name !== '' ? $name : (string) $healthId) }}" aria-hidden="true">{{ $odInitials($entry->patient?->first_name, $entry->patient?->last_name) }}</span>
                            <div>
                                <span class="od-table__name">{{ $name !== '' ? $name : '—' }}</span>
                                <span class="od-table__sub odc-mono">{{ $healthId }}</span>
                                @if($canChart && $entry->patient?->health_id)
                                    <a href="{{ route('portals.chart.index', ['health_id' => $entry->patient->health_id]) }}" class="od-table__sub">{{ __('hospital_desk.open_chart') }}</a>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.staff_portal.col_type', [], $lApp) }}">
                        <span class="od-pill od-pill--normal">@enum($entry->appointment_type)</span>
                    </td>
                    <td data-label="{{ __('waitlist.col_window') }}">
                        @if($from || $to)
                            {{ $from ?? '…' }} → {{ $to ?? '…' }}
                        @else
                            <span class="od-muted">{{ __('waitlist.any_date') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('waitlist.col_urgency') }}">
                        <span class="od-pill od-pill--dot od-pill--{{ $urgencyPill }}">@enum($entry->urgency, 'urgency')</span>
                    </td>
                    <td data-label="{{ __('public.staff_portal.col_status', [], $lApp) }}">
                        <span class="od-pill od-pill--{{ $statusPill }}">@enum($entry->status)</span>
                        @if($entry->notified_at)
                            <span class="od-table__sub">{{ $entry->notified_at->translatedFormat('d M, H:i') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('waitlist.col_joined') }}">
                        {{ $entry->created_at?->translatedFormat('d M Y') }}
                    </td>
                    <td class="od-table__actions" data-label="{{ __('public.staff_portal.col_actions', [], $lApp) }}">
                        @if($canAct)
                        <a class="od-btn od-btn--row"
                           href="{{ route('portals.staff.appointments.create', ['waitlist' => $entry->id, 'patient_id' => $entry->patient_id]) }}">
                            <i data-lucide="calendar-plus"></i>
                            {{ __('waitlist.btn_book') }}
                        </a>
                        @else
                            <span class="od-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @if($entries->hasPages())
    <footer class="od-card__foot odc-pager">
        <span>{{ __('hospital_desk.waitlist_showing', ['from' => $entries->firstItem(), 'to' => $entries->lastItem(), 'total' => $entries->total()]) }}</span>
        <div>{{ $entries->links() }}</div>
    </footer>
    @endif
@endif
</section>

</div>
@endsection
