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


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

@php
    $statusPill = match($bloodRequest->status->value) {
        'pending'                         => 'pending',
        'confirmed', 'ready', 'fulfilled' => 'ready',
        default                           => 'neutral',
    };
    $urgencyPill = match($bloodRequest->urgency->value) {
        'emergency' => 'urgent',
        'urgent'    => 'pending',
        default     => 'neutral',
    };
    $moves = collect($nextMoves)->map(fn ($s) => $s->value)->all();
    $bp = $bloodRequest->patient;
    $bpName = $bp ? trim($bp->first_name . ' ' . $bp->last_name) : '';
    $bpInitials = $bp ? (mb_strtoupper(mb_substr(trim((string) $bp->first_name), 0, 1) . mb_substr(trim((string) $bp->last_name), 0, 1)) ?: '—') : '—';
    $bpTone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($bpName !== '' ? $bpName : (string) $bloodRequest->id) % 6];
@endphp

<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('blood_desk.detail_title', ['reference' => $bloodRequest->reference]) }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--{{ $statusPill }}">{{ __('blood_desk.status_' . $bloodRequest->status->value) }}</span>
            <span class="od-pill od-pill--dot od-pill--{{ $urgencyPill }}">{{ __('blood_desk.urgency_' . $bloodRequest->urgency->value) }}</span>
        </p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('documents.new.blood_request', $bloodRequest->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_blood_slip') }}</a>
        <a href="{{ route('portals.staff.blood_requests.index') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i>
            {{ __('blood_desk.back_to_queue') }}
        </a>
    </div>
</header>

@if(session('success'))
    <div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<div class="od-grid od-grid--main">

<div class="od-stack">
    {{-- Identity only, and only because a clerk at the counter has to match the
         person in front of them to the request. No chart, no address, no history. --}}
    <section class="od-card" aria-labelledby="odc-br-patient">
        <header class="od-card__head"><h2 class="od-card__title" id="odc-br-patient">{{ __('blood_desk.section_patient') }}</h2></header>
        <div class="od-card__body od-detail">
            <div class="od-detail__head">
                <span class="od-avatar od-avatar--lg od-tint--{{ $bpTone }}" aria-hidden="true">{{ $bpInitials }}</span>
                <div class="od-detail__main">
                    <h3 class="od-detail__name">{{ $bpName !== '' ? $bpName : __('blood_desk.not_recorded') }}</h3>
                    <div class="od-detail__ref">
                        <span>{{ $bloodRequest->reference }}</span>
                        <span>{{ optional($bloodRequest->created_at)->translatedFormat('d M Y') }}</span>
                        <span>{{ optional($bloodRequest->created_at)->format('H:i') }}</span>
                    </div>
                    <div class="od-detail__facts">
                        <span class="od-detail__fact"><i data-lucide="id-card"></i> <span class="odc-mono">{{ $bp?->health_id ?: __('blood_desk.not_recorded') }}</span></span>
                        <span class="od-detail__fact"><i data-lucide="phone"></i> {{ $bloodRequest->contact_phone ?: __('blood_desk.not_recorded') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="od-card" aria-labelledby="odc-br-request">
        <header class="od-card__head"><h2 class="od-card__title" id="odc-br-request">{{ __('blood_desk.section_request') }}</h2></header>
        <div class="od-card__body">
            <dl class="odc-kv">
                <div><dt>{{ __('blood_desk.lbl_group') }}</dt><dd><span class="odc-btag">{{ $bloodRequest->blood_group->value }}</span></dd></div>
                <div><dt>{{ __('blood_desk.lbl_component') }}</dt><dd><span class="od-pill od-pill--neutral">@enum($bloodRequest->component_type->value, 'blood_component')</span></dd></div>
                <div><dt>{{ __('blood_desk.lbl_quantity') }}</dt><dd>{{ $bloodRequest->quantity }} {{ __('blood_desk.units_label') }}</dd></div>
                <div><dt>{{ __('blood_desk.lbl_needed_by') }}</dt><dd>{{ optional($bloodRequest->needed_by)->translatedFormat('M d, Y H:i') ?: __('blood_desk.not_recorded') }}</dd></div>
                <div><dt>{{ __('blood_desk.lbl_expires') }}</dt><dd>{{ optional($bloodRequest->expires_at)->translatedFormat('M d, Y H:i') ?: __('blood_desk.not_recorded') }}</dd></div>
                <div><dt>{{ __('blood_desk.lbl_filed') }}</dt><dd>{{ optional($bloodRequest->created_at)->translatedFormat('M d, Y H:i') }}</dd></div>
                <div class="odc-span"><dt>{{ __('blood_desk.lbl_patient_note') }}</dt><dd>{{ $bloodRequest->patient_note ?: __('blood_desk.not_recorded') }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="od-card" aria-labelledby="odc-br-decision">
        <header class="od-card__head"><h2 class="od-card__title" id="odc-br-decision">{{ __('blood_desk.section_decision') }}</h2></header>
        <div class="od-card__body">
            <dl class="odc-kv">
                <div><dt>{{ __('blood_desk.lbl_status') }}</dt><dd><span class="od-pill od-pill--{{ $statusPill }}">{{ __('blood_desk.status_' . $bloodRequest->status->value) }}</span></dd></div>
                <div><dt>{{ __('blood_desk.lbl_decided_by') }}</dt><dd>{{ $bloodRequest->decided_by ?: __('blood_desk.not_recorded') }}</dd></div>
                <div><dt>{{ __('blood_desk.lbl_decided_at') }}</dt><dd>{{ optional($bloodRequest->decided_at)->translatedFormat('M d, Y H:i') ?: __('blood_desk.not_recorded') }}</dd></div>
                <div class="odc-span"><dt>{{ __('blood_desk.lbl_facility_note') }}</dt><dd>{{ $bloodRequest->facility_note ?: __('blood_desk.not_recorded') }}</dd></div>
            </dl>
        </div>
    </section>
</div>

<section class="od-card" aria-labelledby="odc-br-actions">
    <header class="od-card__head"><h2 class="od-card__title" id="odc-br-actions">{{ __('blood_desk.section_actions') }}</h2></header>
    <div class="od-card__body od-stack">
        @if($moves === [])
            <div class="od-empty od-empty--compact">
                <span class="od-empty__icon"><i data-lucide="lock"></i></span>
                <p class="od-empty__text">{{ __('blood_desk.no_moves') }}</p>
            </div>
        @else
            {{-- Said on the screen because it is the thing staff most easily
                 get wrong: confirming reserves nothing. --}}
            <div class="odc-note odc-note--info">
                <i data-lucide="info"></i>
                <div>{{ __('blood_desk.no_hold_notice') }}</div>
            </div>

            <form method="POST" action="{{ route('portals.staff.blood_requests.decide', $bloodRequest->id) }}" id="decide-form" class="odc-form">
                @csrf
                <label class="odc-label" for="note">{{ __('blood_desk.note_label') }}
                    <textarea name="note" id="note" rows="3" maxlength="500" class="odc-textarea"
                              placeholder="{{ __('blood_desk.note_placeholder') }}"></textarea>
                </label>

                <div class="odc-actions">
                    @if(in_array('rejected', $moves, true))
                        <button type="submit" name="decision" value="rejected" class="od-btn odc-btn-danger" data-confirm="{{ __('hospital_desk.confirm_blood_reject') }}">
                            <i data-lucide="x-circle"></i>
                            {{ __('blood_desk.action_reject') }}
                        </button>
                    @endif
                    @if(in_array('confirmed', $moves, true))
                        <button type="submit" name="decision" value="confirmed" class="od-btn od-btn--primary" data-confirm="{{ __('hospital_desk.confirm_blood_confirm') }}">
                            <i data-lucide="check-circle"></i>
                            {{ __('blood_desk.action_confirm') }}
                        </button>
                    @endif
                </div>
            </form>

            {{-- `ready` and `fulfilled` are counter events, not decisions, so
                 they get their own verbs — the same split the mobile desk
                 makes. Both still travel through the one enum transition. --}}
            <div class="odc-actions">
                @if(in_array('ready', $moves, true))
                    <form method="POST" action="{{ route('portals.staff.blood_requests.ready', $bloodRequest->id) }}" data-confirm="{{ __('hospital_desk.confirm_blood_ready') }}">
                        @csrf
                        <button type="submit" class="od-btn od-btn--primary">
                            <i data-lucide="package-check"></i>
                            {{ __('blood_desk.action_ready') }}
                        </button>
                    </form>
                @endif
                @if(in_array('fulfilled', $moves, true))
                    <form method="POST" action="{{ route('portals.staff.blood_requests.fulfil', $bloodRequest->id) }}" data-confirm="{{ __('hospital_desk.confirm_blood_fulfil') }}">
                        @csrf
                        <button type="submit" class="od-btn odc-btn-ok">
                            <i data-lucide="hand-heart"></i>
                            {{ __('blood_desk.action_fulfil') }}
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</section>

</div>
</div>
@endsection
