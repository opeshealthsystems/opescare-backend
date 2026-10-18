@extends('layouts.portal')

@section('title', __('waitlist.title') . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('waitlist.title'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $l = app()->getLocale();
    $openCount = $entries->whereIn('status', ['waiting', 'notified'])->count();
@endphp

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('waitlist.title') }}</h1>
            <p class="pf-sub">{{ __('waitlist.subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.appointments') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.nav_appointments', [], $l) }}
        </a>
    </div>

    @if(session('success'))
    <div class="pf-alert pf-alert--good" role="status">
        @include('partials.finder.icon', ['name' => 'check-circle', 'size' => 18])
        <p>{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="pf-alert pf-alert--danger" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <p>{{ session('error') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="pf-alert pf-alert--danger" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <p>{{ $errors->first() }}</p>
    </div>
    @endif

    {{-- Join form --}}
    <form method="POST" action="{{ route('portals.patient.waitlist.join') }}" class="f-card" id="pf-waitlist-form">
        @csrf
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 18])
            {{ __('waitlist.join_title') }}
        </h2>
        <p class="f-hint ods-0e1ed56">{{ __('waitlist.join_help') }}</p>

        <div class="f-field">
            <label class="f-label" for="wl_facility_id">{{ __('waitlist.field_facility') }}</label>
            <select id="wl_facility_id" name="facility_id" class="f-input" required>
                <option value="">{{ __('finder_patient.choose_facility') }}</option>
                @foreach($facilities as $f)
                    <option value="{{ $f->id }}" @selected(old('facility_id', $defaultFacility) === $f->id)>{{ $f->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="f-field">
            <label class="f-label" for="wl_type">{{ __('waitlist.field_type') }}</label>
            <select id="wl_type" name="appointment_type" class="f-input" required>
                @foreach($types as $t)
                    <option value="{{ $t }}" @selected(old('appointment_type', $types[0]) === $t)>{{ __('public.appt_type_' . $t) }}</option>
                @endforeach
            </select>
        </div>

        <div class="pf-actions ods-b5053b6">
            <div class="f-field">
                <label class="f-label" for="wl_earliest">{{ __('waitlist.field_earliest') }}</label>
                <input type="date" id="wl_earliest" name="preferred_earliest_date" class="f-input"
                       value="{{ old('preferred_earliest_date') }}" min="{{ now()->toDateString() }}">
            </div>
            <div class="f-field">
                <label class="f-label" for="wl_latest">{{ __('waitlist.field_latest') }}</label>
                <input type="date" id="wl_latest" name="preferred_latest_date" class="f-input"
                       value="{{ old('preferred_latest_date') }}" min="{{ now()->toDateString() }}">
            </div>
        </div>
        <p class="f-hint">{{ __('waitlist.window_help') }}</p>

        <div class="f-field">
            <span class="f-label">{{ __('waitlist.field_urgency') }}</span>
            <div class="pf-choices" role="radiogroup" aria-label="{{ __('waitlist.field_urgency') }}">
                @foreach(['routine', 'urgent'] as $u)
                <label class="pf-choice">
                    <input type="radio" name="urgency" value="{{ $u }}" required @checked(old('urgency', 'routine') === $u)>
                    @enum($u, 'urgency')
                    <span class="pf-choice__check">@include('partials.finder.icon', ['name' => 'check', 'size' => 18])</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="f-field">
            <label class="f-label" for="wl_notes">{{ __('waitlist.field_notes') }}</label>
            <textarea id="wl_notes" name="notes" class="f-input" rows="3" maxlength="500"
                      placeholder="{{ __('waitlist.notes_ph') }}">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="f-btn f-btn--primary ods-a27b458">
            @include('partials.finder.icon', ['name' => 'list-plus', 'size' => 18])
            {{ __('waitlist.btn_join') }}
        </button>
    </form>

    {{-- Entries --}}
    <h2 class="ods-7852867">
        {{ __('waitlist.my_entries') }}
        @if($openCount > 0)<span class="f-badge f-badge--warn">{{ $openCount }}</span>@endif
    </h2>

    @if($entries->isEmpty())
    <div class="f-card ods-9ca32e0">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 24])
        </div>
        <h3 class="ods-b152a63">{{ __('waitlist.empty_title') }}</h3>
        <p class="f-muted ods-1386d50">{{ __('waitlist.empty_desc') }}</p>
    </div>
    @else
    <div class="pf-stack">
        @foreach($entries as $entry)
        @php
            $badgeCls = match($entry->status) {
                'waiting'   => 'f-badge--warn',
                'notified'  => 'f-badge--good',
                'booked'    => 'f-badge--good',
                'cancelled', 'expired' => 'pf-badge--muted',
                default     => '',
            };
            $canCancel = in_array($entry->status, ['waiting', 'notified'], true);
            $from = $entry->preferred_earliest_date?->isoFormat('D MMM YYYY');
            $to   = $entry->preferred_latest_date?->isoFormat('D MMM YYYY');
        @endphp
        <div class="f-card">
            <p class="pf-appt__title">
                @enum($entry->appointment_type)
                <span class="f-badge {{ $badgeCls }}">@enum($entry->status)</span>
                @if($entry->urgency === 'urgent')
                    <span class="f-badge f-badge--danger">@enum($entry->urgency, 'urgency')</span>
                @endif
            </p>
            <div class="pf-appt__meta">
                <span>
                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
                    {{ $entry->facility?->name ?? (__('public.portal.unknown_facility', [], $l)) }}
                </span>
                <span>
                    @include('partials.finder.icon', ['name' => 'calendar', 'size' => 15])
                    @if($from || $to)
                        {{ $from ?? '…' }} → {{ $to ?? '…' }}
                    @else
                        {{ __('waitlist.any_date') }}
                    @endif
                </span>
                <span>
                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                    {{ __('waitlist.joined_on', ['date' => $entry->created_at?->isoFormat('D MMM YYYY')]) }}
                </span>
            </div>
            @if($entry->status === 'notified')
            <p class="f-small ods-42751fa">
                @include('partials.finder.icon', ['name' => 'bell-ring', 'size' => 15])
                {{ __('waitlist.notified_hint') }}
                <a href="{{ route('portals.patient.appointments.book') }}">{{ __('public.appt_book_btn') }}</a>
            </p>
            @endif
            @if($entry->notes)
                <p class="f-small f-muted ods-00dd4a9">{{ $entry->notes }}</p>
            @endif
            @if($canCancel)
            <div class="pf-appt__actions">
                <form method="POST" action="{{ route('portals.patient.waitlist.cancel', $entry->id) }}">
                    @csrf
                    <button type="submit" class="f-btn pf-btn--danger-ghost">
                        @include('partials.finder.icon', ['name' => 'x', 'size' => 16])
                        {{ __('waitlist.btn_leave') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
