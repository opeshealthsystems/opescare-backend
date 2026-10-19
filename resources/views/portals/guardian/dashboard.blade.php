@extends('layouts.portal')

@section('title', __('family.dash_title') . ' — OpesCare')

@section('head')
    @include('portals.patient._finder_head')
    <style>
        .gd-nav{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
        .gd-nav a{display:inline-flex;align-items:center;gap:6px;min-height:40px;padding:6px 12px;border:1px solid var(--f-line);border-radius:var(--f-r);background:var(--f-surface);color:var(--f-ink-soft);font-weight:650;font-size:.875rem;text-decoration:none}
        .gd-nav a:hover{border-color:var(--f-brand);color:var(--f-brand-ink)}
        .gd-nav a svg,.gd-nav a i{width:16px;height:16px;color:var(--f-brand)}
        .gd-nav__label{width:100%;font-size:.75rem;font-weight:750;letter-spacing:.05em;text-transform:uppercase;color:#5B6B82}
        .gd-grid{display:grid;gap:12px}
        @media (min-width:900px){.gd-grid{grid-template-columns:repeat(2,1fr)}}
        .gd-dep__head{display:flex;align-items:center;gap:12px}
        .gd-dep__facts{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin:14px 0}
        .gd-dep__links{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
        .gd-dep__links button{background:none;border:1px solid var(--f-line);border-radius:999px;padding:4px 10px;font-size:.8125rem;font-weight:650;color:var(--f-ink-soft);cursor:pointer;min-height:32px}
        .gd-dep__links button:hover{border-color:var(--f-brand);color:var(--f-brand-ink)}
        .gd-inline-form{display:inline;margin:0}
    </style>
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $l          = app()->getLocale();
    $hasPatient = auth()->user()?->patient_id !== null;
@endphp

@section('content')
<div class="pf pf--wide">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.dash_title') }}</h1>
            <p class="pf-sub">{{ __('family.dash_subtitle') }}</p>
        </div>
        <div class="pf-actions">
            <a href="{{ route('portals.patient.family.add') }}" class="f-btn f-btn--primary">
                @include('partials.finder.icon', ['name' => 'plus', 'size' => 16])
                {{ __('family.dash_add_child') }}
            </a>
            <a href="{{ route('portals.patient.family.invite') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'send', 'size' => 16])
                {{ __('family.dash_invite_adult') }}
            </a>
        </div>
    </div>

    {{-- The navigation this role needs, carried by the page itself. --}}
    <nav class="gd-nav" aria-label="{{ __('family.dash_nav_title') }}">
        <span class="gd-nav__label">{{ __('family.dash_nav_title') }}</span>
        <a href="{{ route('portals.patient') }}"><i data-lucide="layout-dashboard" aria-hidden="true"></i>{{ __('family.nav_dashboard') }}</a>
        <a href="{{ route('portals.patient.family') }}"><i data-lucide="users" aria-hidden="true"></i>{{ __('family.nav_family') }}</a>
        @if($hasPatient)
        <a href="{{ route('portals.patient.family.invitations') }}"><i data-lucide="mail" aria-hidden="true"></i>{{ __('family.nav_invitations') }}</a>
        @endif
        <a href="{{ route('portals.patient.appointments') }}"><i data-lucide="calendar-check-2" aria-hidden="true"></i>{{ __('family.nav_appointments') }}</a>
        <a href="{{ route('portals.patient.prescriptions') }}"><i data-lucide="pill" aria-hidden="true"></i>{{ __('family.nav_prescriptions') }}</a>
        <a href="{{ route('portals.patient.immunizations') }}"><i data-lucide="syringe" aria-hidden="true"></i>{{ __('family.nav_immunizations') }}</a>
        <a href="{{ route('portals.patient.timeline') }}"><i data-lucide="activity" aria-hidden="true"></i>{{ __('family.nav_timeline') }}</a>
        <a href="{{ route('portals.patient.documents') }}"><i data-lucide="file-text" aria-hidden="true"></i>{{ __('family.nav_documents') }}</a>
        <a href="{{ route('public.care-map') }}"><i data-lucide="map-pin" aria-hidden="true"></i>{{ __('family.nav_care_map') }}</a>
        <a href="{{ route('portals.patient.settings') }}"><i data-lucide="settings" aria-hidden="true"></i>{{ __('family.nav_settings') }}</a>
    </nav>

    @if($incomingCount > 0)
    <a href="{{ route('portals.patient.family.invitations') }}" class="pf-alert ods-116e33d" data-section="incoming-invitations">
        @include('partials.finder.icon', ['name' => 'bell', 'size' => 18])
        <p><strong>{{ __('family.dash_incoming') }}</strong> — {{ __('family.dash_incoming_count', ['count' => $incomingCount]) }}</p>
    </a>
    @endif

    @if($links->isEmpty())
    <div class="f-card ods-99e8a36" data-section="dependents-empty">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'users', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('family.dash_empty_title') }}</h2>
        <p class="f-muted ods-7307c03">{{ __('family.dash_empty_body') }}</p>
        <div class="pf-actions ods-9b54a37">
            <a href="{{ route('portals.patient.family.add') }}" class="f-btn f-btn--primary ods-a27b458">{{ __('family.dash_add_child') }}</a>
            <a href="{{ route('portals.patient.family.invite') }}" class="f-btn ods-a27b458">{{ __('family.dash_invite_adult') }}</a>
        </div>
    </div>
    @else
    <div class="gd-grid" data-section="dependents">
        @foreach($links as $link)
        @php
            $dep       = $link->dependentPatient;
            $depName   = $dep ? trim(($dep->first_name ?? '') . ' ' . ($dep->last_name ?? '')) : null;
            $dob       = $dep?->date_of_birth;
            $age       = $dob?->age;
            $turns     = $dob ? $dob->copy()->addYears($majorityAge) : null;
            $soon      = $turns && $turns->isFuture() && $turns->diffInDays(now()) <= $warningDays;
            $identity  = $dep?->identity_status instanceof \BackedEnum ? $dep->identity_status->value : ($dep?->identity_status ?? null);
            $next      = $dep ? ($nextAppointments[$dep->id] ?? null) : null;
            $shots     = $dep ? (int) ($immunisationCounts[$dep->id] ?? 0) : 0;
            $isOpen    = $viewingId && $dep && $viewingId === $dep->id;
            $readOnly  = \App\Http\Middleware\GuardianAccessMiddleware::isReadOnly($link);
        @endphp
        <div class="f-card" data-dependent="{{ $link->dependent_patient_id }}">
            <div class="gd-dep__head">
                <div class="pf-avatar">{{ strtoupper(substr($dep?->first_name ?? 'D', 0, 1)) }}</div>
                <div class="ods-89bd09b">
                    <div class="ods-27b9a2f">
                        {{ $link->status === 'active' ? ($depName ?? __('public.portal.unknown_patient', [], $l)) : ($dep ? trim(($dep->first_name ?? '') . ' ' . mb_substr((string) ($dep->last_name ?? ''), 0, 1) . '.') : __('public.portal.unknown_patient', [], $l)) }}
                        @if($age !== null && $link->status === 'active')<span class="f-badge pf-badge--muted">{{ __('family.dash_age', ['age' => $age]) }}</span>@endif
                        @if($isOpen)<span class="f-badge f-badge--good">{{ __('family.dash_currently_viewing') }}</span>@endif
                    </div>
                    @if($dep && $link->status === 'active')
                    <div class="f-small f-muted ods-9709411">{{ $dep->health_id }}</div>
                    @endif
                </div>
                @if($link->status === 'pending_invite')
                    <span class="f-badge f-badge--warn">{{ __('public.portal.lbl_pending', [], $l) }}</span>
                @else
                    <span class="f-badge {{ $readOnly ? 'pf-badge--muted' : 'f-badge--good' }}">{{ $readOnly ? __('family.access.read_only') : __('family.access.full') }}</span>
                @endif
            </div>

            <p class="f-small f-muted ods-00dd4a9">
                {{ __('family.relationship.' . $link->relationship) !== 'family.relationship.' . $link->relationship ? __('family.relationship.' . $link->relationship) : ucfirst(str_replace('_', ' ', $link->relationship)) }}
                @if($link->status === 'pending_invite') &middot; {{ __('family.dash_pending_invite') }} @endif
            </p>

            @if(!$dep)
                <div class="pf-alert pf-alert--warn ods-446a577">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <p class="f-small">{{ __('family.dash_hidden_dependent') }}</p>
                </div>
            @elseif($link->status === 'active')
                <div class="gd-dep__facts">
                    <div>
                        <div class="pf-tile__label">{{ __('family.dash_next_appointment') }}</div>
                        <div class="pf-tile__value ods-6907a20">
                            @if($next)
                                {{ $next->scheduled_at->isoFormat('ddd D MMM, HH:mm') }}
                                <div class="f-small f-muted ods-1e29eef">{{ $next->facility?->name ?? '—' }}</div>
                            @else
                                <span class="f-muted ods-e634e6d">{{ __('family.dash_none_scheduled') }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="pf-tile__label">{{ __('family.dash_immunisations') }}</div>
                        <div class="pf-tile__value">{{ $shots }}</div>
                    </div>
                    <div>
                        <div class="pf-tile__label">{{ __('family.dash_identity') }}</div>
                        <div class="pf-tile__value ods-6907a20">@enum($identity ?? 'provisional', 'verification')</div>
                    </div>
                    <div>
                        <div class="pf-tile__label">{{ __('public.portal.blood_group', [], $l) }}</div>
                        <div class="pf-tile__value">{{ $dep->blood_group ?? '—' }}</div>
                    </div>
                </div>

                @if($link->isExpiredByAge())
                    <div class="pf-alert pf-alert--warn ods-0998cfa">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                        <p class="f-small">{{ __('family.age_grace', ['name' => $dep->first_name, 'date' => $link->age_transition_expires_at->translatedFormat('d M Y')]) }}</p>
                    </div>
                @elseif($link->age_transition_expires_at)
                    <div class="pf-alert pf-alert--warn ods-0998cfa">
                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 18])
                        <p class="f-small">{{ __('family.age_grace', ['name' => $dep->first_name, 'date' => $link->age_transition_expires_at->translatedFormat('d M Y')]) }}</p>
                    </div>
                @elseif($soon)
                    <div class="pf-alert ods-0998cfa" data-age-transition>
                        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                        <p class="f-small">{{ __('family.age_turns_majority', ['name' => $dep->first_name, 'age' => $majorityAge, 'date' => $turns->translatedFormat('d M Y'), 'grace' => $graceDays]) }}</p>
                    </div>
                @endif

                <div class="pf-appt__actions ods-53896af">
                    <form method="POST" action="{{ route('portals.patient.family.switch', $dep->id) }}" class="gd-inline-form">
                        @csrf
                        <input type="hidden" name="next" value="dashboard">
                        <button type="submit" class="f-btn f-btn--primary">
                            @include('partials.finder.icon', ['name' => 'eye', 'size' => 16])
                            {{ __('family.dash_view_as') }}
                        </button>
                    </form>
                    <a href="{{ route('portals.patient.family.edit', $link->id) }}" class="f-btn">{{ __('public.portal.btn_edit', [], $l) }}</a>
                </div>
                <div class="gd-dep__links" aria-label="{{ __('family.dash_quick_links') }}">
                    @foreach([
                        'appointments'  => __('family.nav_appointments'),
                        'immunizations' => __('family.nav_immunizations'),
                        'prescriptions' => __('family.nav_prescriptions'),
                        'timeline'      => __('family.nav_timeline'),
                        'documents'     => __('family.nav_documents'),
                    ] as $target => $label)
                    @if(in_array($target, $switchTargets, true))
                    <form method="POST" action="{{ route('portals.patient.family.switch', $dep->id) }}" class="gd-inline-form">
                        @csrf
                        <input type="hidden" name="next" value="{{ $target }}">
                        <button type="submit">{{ $label }}</button>
                    </form>
                    @endif
                    @endforeach
                </div>
            @else
                <div class="pf-appt__actions">
                    <a href="{{ route('portals.patient.family.edit', $link->id) }}" class="f-btn">{{ __('public.portal.btn_edit', [], $l) }}</a>
                </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
