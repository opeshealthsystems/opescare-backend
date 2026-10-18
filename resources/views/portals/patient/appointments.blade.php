@extends('layouts.portal')

@section('title', __('public.portal.nav_appointments', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $l = app()->getLocale();
    $apptCount = method_exists($appointments, 'total') ? $appointments->total() : $appointments->count();
@endphp

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.nav_appointments', [], $l) }}</h1>
            <p class="pf-sub">{{ __('public.portal.appointments_subtitle', [], $l) }}</p>
        </div>
        <div class="pf-actions">
            <a href="{{ route('portals.patient.waitlist') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 18])
                {{ __('waitlist.btn_join') }}
            </a>
            <a href="{{ route('portals.patient.appointments.book') }}" class="f-btn f-btn--primary">
                @include('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 18])
                {{ __('public.appt_book_btn') }}
            </a>
        </div>
    </div>

    @if($apptCount === 0)
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'calendar', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('public.portal.no_appointments_title', [], $l) }}</h2>
        <p class="f-muted ods-7307c03">{{ __('public.portal.no_appointments_desc', [], $l) }}</p>
        <div class="pf-actions ods-9b54a37">
            <a href="{{ route('portals.patient.appointments.book') }}" class="f-btn f-btn--primary ods-a27b458">
                @include('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 18])
                {{ __('public.appt_book_btn') }}
            </a>
            <a href="{{ route('portals.patient.waitlist') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'list-ordered', 'size' => 18])
                {{ __('waitlist.btn_join') }}
            </a>
            <a href="{{ route('public.care-map') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                {{ __('public.portal.nav_care_map', [], $l) }}
            </a>
        </div>
    </div>
    @else

    <div class="pf-stack">
        @foreach($appointments as $appt)
        @php
            $status = $appt->status ?? 'scheduled';
            $badgeCls = match($status) {
                'requested'                             => 'f-badge--warn',
                'confirmed', 'scheduled', 'checked_in'  => 'f-badge--good',
                'cancelled'                             => 'pf-badge--muted',
                'no_show'                               => 'f-badge--danger',
                default                                 => '',
            };
            $providerName = trim($appt->provider?->name
                ?? (($appt->provider?->first_name ?? '') . ' ' . ($appt->provider?->last_name ?? '')));
            $isActionable = in_array($status, ['requested', 'scheduled', 'confirmed']);
        @endphp
        <div class="f-card">
            <div class="pf-appt">
                <div class="pf-appt__date" aria-hidden="true">
                    <div class="d">{{ $appt->scheduled_at?->format('d') ?? '—' }}</div>
                    <div class="m">{{ $appt->scheduled_at?->isoFormat('MMM') ?? '' }}</div>
                </div>
                <div class="pf-appt__body">
                    <p class="pf-appt__title">
                        @enum($appt->appointment_type ?? 'General')
                        <span class="f-badge {{ $badgeCls }}">@enum($status)</span>
                    </p>
                    <div class="pf-appt__meta">
                        @if($appt->scheduled_at)
                        <span>
                            @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                            {{ $appt->scheduled_at->isoFormat('ddd D MMM YYYY, HH:mm') }}
                        </span>
                        @endif
                        <span>
                            @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
                            {{ $appt->facility?->name ?? (__('public.portal.unknown_facility', [], $l)) }}
                        </span>
                        @if($providerName !== '')
                        <span>
                            @include('partials.finder.icon', ['name' => 'users', 'size' => 15])
                            {{ __('finder_patient.with_provider', ['name' => $providerName]) }}
                        </span>
                        @endif
                    </div>
                    @if($status === 'requested')
                    <p class="f-small ods-2cfa178">
                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                        {{ __('finder_patient.requested_hint') }}
                    </p>
                    @endif
                    @if($isActionable)
                    <div class="pf-appt__actions">
                        <a href="{{ route('portals.patient.appointments.reschedule', $appt->id) }}" class="f-btn">
                            @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 16])
                            {{ __('public.appt_reschedule_btn') }}
                        </a>
                        <button type="button" class="f-btn pf-btn--danger-ghost" data-call="pfOpenModal" data-args="[&quot;cancel-appt-{{ $appt->id }}&quot;]">
                            @include('partials.finder.icon', ['name' => 'x', 'size' => 16])
                            {{ __('public.portal.btn_cancel_appointment', [], $l) }}
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if(method_exists($appointments, 'links'))
    <div class="pf-pagination">
        {{ $appointments->links() }}
    </div>
    @endif

    {{-- Cancel confirmations --}}
    @foreach($appointments as $appt)
        @if(in_array($appt->status, ['requested', 'scheduled', 'confirmed']))
        <div id="cancel-appt-{{ $appt->id }}" class="pf-modal-backdrop" hidden>
            <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-appt-{{ $appt->id }}-title">
                <h3 id="cancel-appt-{{ $appt->id }}-title">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    {{ __('public.portal.modal_cancel_appt_title', [], $l) }}
                </h3>
                <form method="POST" action="{{ route('portals.patient.appointments.cancel', $appt->id) }}">
                    @csrf
                    <p>{{ __('public.portal.modal_cancel_appt_body', [], $l) }}</p>
                    <div class="pf-modal__actions">
                        <button type="button" class="f-btn" data-call="pfCloseModal" data-args="[&quot;cancel-appt-{{ $appt->id }}&quot;]">{{ __('public.portal.btn_keep_appointment', [], $l) }}</button>
                        <button type="submit" class="f-btn pf-btn--danger">{{ __('public.portal.btn_cancel_appointment', [], $l) }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endforeach

    @endif
</div>

@endsection

@section('scripts')
<script @cspNonce>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
