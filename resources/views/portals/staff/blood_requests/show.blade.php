{{--
    One blood request — /facility/blood-requests/{id}

    Only reachable for a request addressed to THIS facility; the controller
    answers 404 for anyone else's, never 403.

    The action buttons below are generated from
    App\Enums\BloodRequestStatus::facilityTransitions() — the same map the
    mobile desk and the partner API obey. A closed request offers no buttons at
    all, because there is no legal move left, not because the screen hides them.
--}}
@extends('layouts.portal')

@section('title', __('blood_desk.detail_title', ['reference' => $bloodRequest->reference]))

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('blood_desk.breadcrumb'))

@section('content')

@php
    $statusBadge = match($bloodRequest->status->value) {
        'pending'                         => 'badge-warning',
        'confirmed', 'ready', 'fulfilled' => 'badge-success',
        default                           => 'badge-neutral',
    };
    $urgencyBadge = match($bloodRequest->urgency->value) {
        'emergency' => 'badge-danger',
        'urgent'    => 'badge-warning',
        default     => 'badge-neutral',
    };
    $moves = collect($nextMoves)->map(fn ($s) => $s->value)->all();
@endphp

<div class="page-head">
    <h2>{{ __('blood_desk.detail_title', ['reference' => $bloodRequest->reference]) }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="btn btn-ghost btn-sm fs-tap">
        <i data-lucide="arrow-left"></i>
        {{ __('blood_desk.back_to_queue') }}
    </a>
</div>
<p class="page-subtitle mb-6">
    <span class="badge {{ $statusBadge }}">{{ __('blood_desk.status_' . $bloodRequest->status->value) }}</span>
    <span class="badge {{ $urgencyBadge }}">{{ __('blood_desk.urgency_' . $bloodRequest->urgency->value) }}</span>
</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel mb-6">
    <div class="panel-header"><h3>{{ __('blood_desk.section_request') }}</h3></div>
    <div class="panel-body">
        <dl class="detail-list">
            <dt>{{ __('blood_desk.lbl_group') }}</dt>
            <dd><span class="fs-bgroup-tag">{{ $bloodRequest->blood_group->value }}</span></dd>

            <dt>{{ __('blood_desk.lbl_component') }}</dt>
            <dd><span class="badge badge-neutral">@enum($bloodRequest->component_type->value, 'blood_component')</span></dd>

            <dt>{{ __('blood_desk.lbl_quantity') }}</dt>
            <dd>{{ $bloodRequest->quantity }} {{ __('blood_desk.units_label') }}</dd>

            <dt>{{ __('blood_desk.lbl_needed_by') }}</dt>
            <dd>{{ optional($bloodRequest->needed_by)->translatedFormat('M d, Y H:i') ?: __('blood_desk.not_recorded') }}</dd>

            <dt>{{ __('blood_desk.lbl_expires') }}</dt>
            <dd>{{ optional($bloodRequest->expires_at)->translatedFormat('M d, Y H:i') ?: __('blood_desk.not_recorded') }}</dd>

            <dt>{{ __('blood_desk.lbl_filed') }}</dt>
            <dd>{{ optional($bloodRequest->created_at)->translatedFormat('M d, Y H:i') }}</dd>

            <dt>{{ __('blood_desk.lbl_patient_note') }}</dt>
            <dd>{{ $bloodRequest->patient_note ?: __('blood_desk.not_recorded') }}</dd>
        </dl>
    </div>
</div>

{{-- Identity only, and only because a clerk at the counter has to match the
     person in front of them to the request. No chart, no address, no history. --}}
<div class="panel mb-6">
    <div class="panel-header"><h3>{{ __('blood_desk.section_patient') }}</h3></div>
    <div class="panel-body">
        <dl class="detail-list">
            <dt>{{ __('blood_desk.lbl_name') }}</dt>
            <dd>{{ $bloodRequest->patient ? trim($bloodRequest->patient->first_name . ' ' . $bloodRequest->patient->last_name) : __('blood_desk.not_recorded') }}</dd>

            <dt>{{ __('blood_desk.lbl_health_id') }}</dt>
            <dd><span class="mono">{{ $bloodRequest->patient?->health_id ?: __('blood_desk.not_recorded') }}</span></dd>

            <dt>{{ __('blood_desk.lbl_phone') }}</dt>
            <dd>{{ $bloodRequest->contact_phone ?: __('blood_desk.not_recorded') }}</dd>
        </dl>
    </div>
</div>

<div class="panel mb-6">
    <div class="panel-header"><h3>{{ __('blood_desk.section_decision') }}</h3></div>
    <div class="panel-body">
        <dl class="detail-list">
            <dt>{{ __('blood_desk.lbl_status') }}</dt>
            <dd><span class="badge {{ $statusBadge }}">{{ __('blood_desk.status_' . $bloodRequest->status->value) }}</span></dd>

            <dt>{{ __('blood_desk.lbl_decided_by') }}</dt>
            <dd>{{ $bloodRequest->decided_by ?: __('blood_desk.not_recorded') }}</dd>

            <dt>{{ __('blood_desk.lbl_decided_at') }}</dt>
            <dd>{{ optional($bloodRequest->decided_at)->translatedFormat('M d, Y H:i') ?: __('blood_desk.not_recorded') }}</dd>

            <dt>{{ __('blood_desk.lbl_facility_note') }}</dt>
            <dd>{{ $bloodRequest->facility_note ?: __('blood_desk.not_recorded') }}</dd>
        </dl>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3>{{ __('blood_desk.section_actions') }}</h3></div>
    <div class="panel-body">
        @if($moves === [])
            <p class="td-muted">{{ __('blood_desk.no_moves') }}</p>
        @else
            {{-- Said on the screen because it is the thing staff most easily
                 get wrong: confirming reserves nothing. --}}
            <div class="alert alert-info mb-6">
                <i data-lucide="info"></i>
                <div>{{ __('blood_desk.no_hold_notice') }}</div>
            </div>

            <form method="POST" action="{{ route('portals.staff.blood_requests.decide', $bloodRequest->id) }}" id="decide-form">
                @csrf
                <label class="form-label" for="note">{{ __('blood_desk.note_label') }}</label>
                <textarea name="note" id="note" rows="3" maxlength="500" class="form-control mb-6"
                          placeholder="{{ __('blood_desk.note_placeholder') }}"></textarea>

                <div class="row-actions-inline">
                    @if(in_array('confirmed', $moves, true))
                        <button type="submit" name="decision" value="confirmed" class="btn btn-primary btn-sm fs-tap" data-confirm="{{ __('hospital_desk.confirm_blood_confirm') }}">
                            <i data-lucide="check-circle"></i>
                            {{ __('blood_desk.action_confirm') }}
                        </button>
                    @endif
                    @if(in_array('rejected', $moves, true))
                        <button type="submit" name="decision" value="rejected" class="btn btn-danger btn-sm fs-tap" data-confirm="{{ __('hospital_desk.confirm_blood_reject') }}">
                            <i data-lucide="x-circle"></i>
                            {{ __('blood_desk.action_reject') }}
                        </button>
                    @endif
                </div>
            </form>

            {{-- `ready` and `fulfilled` are counter events, not decisions, so
                 they get their own verbs — the same split the mobile desk
                 makes. Both still travel through the one enum transition. --}}
            <div class="row-actions-inline mt-6">
                @if(in_array('ready', $moves, true))
                    <form method="POST" action="{{ route('portals.staff.blood_requests.ready', $bloodRequest->id) }}" data-confirm="{{ __('hospital_desk.confirm_blood_ready') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm fs-tap">
                            <i data-lucide="package-check"></i>
                            {{ __('blood_desk.action_ready') }}
                        </button>
                    </form>
                @endif
                @if(in_array('fulfilled', $moves, true))
                    <form method="POST" action="{{ route('portals.staff.blood_requests.fulfil', $bloodRequest->id) }}" data-confirm="{{ __('hospital_desk.confirm_blood_fulfil') }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm fs-tap">
                            <i data-lucide="hand-heart"></i>
                            {{ __('blood_desk.action_fulfil') }}
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>

@endsection
