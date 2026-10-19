@extends('layouts.portal')

@section('title', __('public.medical_id.health_id', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $l         = app()->getLocale();
    $isViewing = $isViewing ?? false;
    $canWrite  = $canWrite ?? true;
    $viewName  = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
    $upcoming  = $upcomingAppointments ?? collect();
    $results   = $recentResults ?? collect();
    $consents  = $pendingConsentRequests ?? collect();
    $nextAppt  = $upcoming->first();
    $resultTone = fn (?string $flag): string => match (strtoupper((string) $flag)) {
        'H', 'HH', 'L', 'LL', 'A', 'AA', 'HIGH', 'LOW', 'ABNORMAL', 'CRITICAL' => 'pending',
        default => 'ready',
    };
@endphp

@section('content')

@if($patient)

<div class="odpt">

    {{-- Greeting --}}
    <section class="od-card odpt-greet" aria-labelledby="odpt-greet-title">
        <div class="odpt-greet__text">
            <h1 class="odpt-greet__title" id="odpt-greet-title">
                @if($isViewing)
                    {{ __('family.record_of', ['name' => $viewName]) }}
                @elseif($patient->first_name)
                    {{ __('finder_patient.greeting', ['name' => $patient->first_name]) }}
                @else
                    {{ __('finder_patient.greeting_generic') }}
                @endif
            </h1>
            <p class="odpt-greet__sub">
                @if($isViewing)
                    {{ $canWrite ? __('family.access_full_explained') : __('family.access_read_only_explained') }}
                @else
                    {{ __('finder_patient.greeting_sub') }}
                @endif
            </p>
        </div>
        <div class="odpt-greet__actions">
            @if($isViewing)
                <a href="{{ route('portals.guardian.dashboard') }}" class="od-btn od-btn--secondary"><i data-lucide="users"></i> {{ __('family.nav_dependents') }}</a>
            @endif
            @if($canWrite)
                <a href="{{ route('portals.patient.appointments.book') }}" class="od-btn od-btn--primary"><i data-lucide="calendar-plus"></i> {{ __('public.appt_book_btn') }}</a>
            @endif
        </div>
    </section>

    @if($isViewing && !$canWrite)
    <div class="odpt-note" data-section="read-only-note">
        <i data-lucide="info"></i>
        <p>{{ __('family.read_only_qr_note', ['name' => $viewName]) }}</p>
    </div>
    @endif

    {{-- Four figures, one colour each. Every number is a count over this record. --}}
    <div class="od-kpis odpt-kpis" data-section="home-kpis">
        <a href="{{ route('portals.patient.appointments') }}" class="od-kpi od-tint--blue">
            <span class="od-kpi__icon"><i data-lucide="calendar"></i></span>
            <span class="od-kpi__body">
                <span class="od-kpi__label">{{ __('patient_ui.kpi_appointments') }}</span>
                <span class="od-kpi__value">{{ $upcoming->count() }}</span>
                <span class="od-kpi__sub">{{ __('patient_ui.kpi_appointments_sub') }}</span>
            </span>
        </a>
        <a href="{{ route('portals.patient.prescriptions') }}" class="od-kpi od-tint--green">
            <span class="od-kpi__icon"><x-od-icon name="rx" /></span>
            <span class="od-kpi__body">
                <span class="od-kpi__label">{{ __('patient_ui.kpi_prescriptions') }}</span>
                <span class="od-kpi__value">{{ $activePrescriptionsCount ?? 0 }}</span>
                <span class="od-kpi__sub">{{ __('patient_ui.kpi_prescriptions_sub') }}</span>
            </span>
        </a>
        <a href="{{ route('portals.patient.timeline') }}" class="od-kpi od-tint--teal">
            <span class="od-kpi__icon"><x-od-icon name="test-tube" /></span>
            <span class="od-kpi__body">
                <span class="od-kpi__label">{{ __('patient_ui.kpi_results') }}</span>
                <span class="od-kpi__value">{{ $newResultsCount ?? 0 }}</span>
                <span class="od-kpi__sub">{{ __('patient_ui.kpi_results_sub') }}</span>
            </span>
        </a>
        <a href="#odpt-consent-title" class="od-kpi od-tint--amber">
            <span class="od-kpi__icon"><i data-lucide="shield-check"></i></span>
            <span class="od-kpi__body">
                <span class="od-kpi__label">{{ __('patient_ui.kpi_consents') }}</span>
                <span class="od-kpi__value">{{ $consents->count() }}</span>
                <span class="od-kpi__sub">{{ __('patient_ui.kpi_consents_sub') }}</span>
            </span>
        </a>
    </div>

    <div class="odpt-cols">

        <div class="odpt-col">
            {{-- Next appointment --}}
            <section class="od-card" aria-labelledby="odpt-next-title">
                <header class="od-card__head">
                    <h2 class="od-card__title" id="odpt-next-title">{{ __('patient_ui.next_appointment') }}</h2>
                    @if($upcoming->isNotEmpty())
                        <a href="{{ route('portals.patient.appointments') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
                    @endif
                </header>
                @if($nextAppt)
                    <div class="odpt-appt">
                        <div class="odpt-appt__date" aria-hidden="true">
                            <span class="odpt-appt__day">{{ $nextAppt->scheduled_at?->format('d') ?? '—' }}</span>
                            <span class="odpt-appt__month">{{ $nextAppt->scheduled_at?->isoFormat('MMM') ?? '' }}</span>
                        </div>
                        <div class="odpt-appt__body">
                            <p class="odpt-appt__title">@enum($nextAppt->appointment_type ?? 'consultation') <span class="od-pill od-pill--normal">@enum($nextAppt->status ?? 'scheduled')</span></p>
                            <ul class="odpt-facts">
                                @if($nextAppt->scheduled_at)
                                <li><i data-lucide="clock"></i>{{ $nextAppt->scheduled_at->isoFormat('ddd D MMM, HH:mm') }}</li>
                                @endif
                                <li><i data-lucide="map-pin"></i>{{ $nextAppt->facility?->name ?? '—' }}</li>
                                @if($nextAppt->provider?->name)
                                <li><i data-lucide="user-round"></i>{{ __('finder_patient.with_provider', ['name' => $nextAppt->provider->name]) }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    @if($upcoming->count() > 1)
                    <ul class="od-list odpt-list">
                        @foreach($upcoming->slice(1, 2) as $appt)
                        <li class="od-list__row">
                            <span class="od-list__icon od-tint--blue"><i data-lucide="calendar"></i></span>
                            <span class="od-list__text">
                                <span class="od-list__title">@enum($appt->appointment_type ?? 'consultation')</span>
                                <span class="od-list__sub">{{ $appt->scheduled_at?->isoFormat('ddd D MMM, HH:mm') }} · {{ $appt->facility?->name ?? '—' }}</span>
                            </span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                @else
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="calendar"></i></span>
                        <div class="od-empty__title">{{ __('finder_patient.no_upcoming_title') }}</div>
                        <p class="od-empty__text">{{ __('finder_patient.no_upcoming_body') }}</p>
                        @if($canWrite)
                            <a href="{{ route('portals.patient.appointments.book') }}" class="od-btn od-btn--primary">{{ __('public.appt_book_btn') }}</a>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Recent results --}}
            <section class="od-card" aria-labelledby="odpt-results-title">
                <header class="od-card__head">
                    <h2 class="od-card__title" id="odpt-results-title">{{ __('patient_ui.recent_results') }}</h2>
                    @if($results->isNotEmpty())
                        <a href="{{ route('portals.patient.timeline') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
                    @endif
                </header>
                @if($results->isEmpty())
                    <div class="od-empty">
                        <span class="od-empty__icon"><x-od-icon name="test-tube" /></span>
                        <div class="od-empty__title">{{ __('patient_ui.results_empty_title') }}</div>
                        <p class="od-empty__text">{{ __('patient_ui.results_empty_text') }}</p>
                    </div>
                @else
                    <ul class="od-list">
                        @foreach($results as $r)
                        <li class="od-list__row">
                            <span class="od-list__icon od-tint--teal"><x-od-icon name="test-tube" /></span>
                            <span class="od-list__text">
                                <span class="od-list__title">{{ $r->parameter_name }}</span>
                                <span class="od-list__sub">{{ $r->resulted_at?->translatedFormat('d M Y') }}</span>
                            </span>
                            <span class="od-list__value">{{ $r->value }} {{ $r->unit }}</span>
                            @if($r->flag)
                                <span class="od-pill od-pill--{{ $resultTone($r->flag) }}">{{ $resultTone($r->flag) === 'ready' ? __('patient_ui.result_in_range') : __('patient_ui.result_out_of_range') }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Consent requests --}}
            <section class="od-card" aria-labelledby="odpt-consent-title" data-section="consent-requests">
                <header class="od-card__head">
                    <h2 class="od-card__title" id="odpt-consent-title">{{ __('patient_ui.consent_title') }} <span class="od-card__count">({{ $consents->count() }})</span></h2>
                    <a href="{{ route('portals.patient.access-logs') }}" class="od-card__link">{{ __('family.nav_access_logs') }}</a>
                </header>
                @if($consents->isEmpty())
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="shield-check"></i></span>
                        <div class="od-empty__title">{{ __('patient_ui.consent_empty_title') }}</div>
                        <p class="od-empty__text">{{ __('patient_ui.consent_empty_text') }}</p>
                    </div>
                @else
                    <ul class="od-list">
                        @foreach($consents as $c)
                        <li class="od-list__row">
                            <span class="od-list__icon od-tint--amber"><i data-lucide="building-2"></i></span>
                            <span class="od-list__text">
                                <span class="od-list__title">{{ $c->requestingFacility?->name ?? __('patient_ui.consent_a_facility') }}</span>
                                <span class="od-list__sub">{{ $c->purpose ?: __('patient_ui.consent_no_purpose') }} · {{ $c->created_at?->diffForHumans() }}</span>
                            </span>
                            <span class="od-pill od-pill--pending">{{ __('patient_ui.consent_waiting') }}</span>
                        </li>
                        @endforeach
                    </ul>
                    <p class="odpt-hint">{{ __('patient_ui.consent_answer_in_app') }}</p>
                @endif
            </section>
        </div>

        <div class="odpt-col">
            {{-- Health ID credential --}}
            <section class="od-card odpt-hid" data-section="health-id-card" aria-labelledby="odpt-hid-title">
                <header class="od-card__head">
                    <h2 class="od-card__title" id="odpt-hid-title">{{ __('public.medical_id.health_id', [], $l) }}</h2>
                </header>
                @include('portals.patient._health_id_card', ['patient' => $patient, 'staticQrDataUri' => $staticQrDataUri, 'canWrite' => $canWrite])
                <dl class="odpt-hid__meta">
                    <div><dt>{{ __('public.portal.status', [], $l) }}</dt><dd>@enum(($patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : $patient->verification_status) ?? 'Active', 'verification')</dd></div>
                    <div><dt>{{ __('public.portal.country', [], $l) }}</dt><dd>{{ $patient->country_code ?? '—' }}</dd></div>
                    <div><dt>{{ __('public.portal.registered', [], $l) }}</dt><dd>{{ $patient->created_at?->translatedFormat('M Y') ?? '—' }}</dd></div>
                </dl>
                @if($canWrite)
                <div class="odpt-hid__actions">
                    <a href="{{ route('portals.patient.health-id-card') }}" class="od-btn od-btn--secondary" data-action="print-card"><i data-lucide="printer"></i> {{ __('family.card_print') }}</a>
                    <button type="button" id="generate-temp-qr" class="od-btn od-btn--primary"
                            data-label-generating="{{ __('public.portal.generating', [], $l) }}"
                            data-label-regenerate="{{ __('public.portal.regenerate_qr', [], $l) }}">{{ __('public.portal.generate_qr', [], $l) }}</button>
                </div>
                @endif

                <div data-section="temporary-qr" class="odpt-tempqr">
                    <p class="odpt-hint">{{ __('public.portal.temp_qr_desc', [], $l) }}</p>
                    @unless($canWrite)
                    <div class="odpt-note odpt-note--warn">
                        <i data-lucide="lock"></i>
                        <p>{{ __('family.read_only_qr_note', ['name' => $viewName]) }}</p>
                    </div>
                    @endunless
                    <p id="temp-qr-error" class="odpt-error" role="alert" hidden></p>
                    <div id="temp-qr-container" class="odpt-tempqr__box" aria-live="polite" hidden>
                        <img id="temp-qr-img" alt="{{ __('public.portal.temp_qr_alt', [], $l) }}" class="odpt-tempqr__img" hidden>
                        <span id="temp-qr-placeholder" class="odpt-tempqr__ph"><i data-lucide="qr-code"></i></span>
                        <p class="odpt-tempqr__exp"><i data-lucide="clock"></i>{{ __('public.portal.expires_in', [], $l) }}: <span id="countdown">60:00</span></p>
                    </div>
                    <p class="odpt-hint">{{ __('public.portal.qr_audit_notice', [], $l) }} <a href="{{ route('portals.patient.access-logs') }}">{{ __('family.nav_access_logs') }}</a></p>
                </div>
            </section>

            {{-- Clinical safety summary --}}
            <section class="od-card" aria-labelledby="odpt-safety-title">
                <header class="od-card__head">
                    <h2 class="od-card__title" id="odpt-safety-title">{{ __('public.portal.clinical_safety_summary', [], $l) }}</h2>
                </header>
                <dl class="odpt-safety">
                    <div>
                        <dt>{{ __('public.portal.blood_group', [], $l) }}</dt>
                        <dd>{{ $patient->blood_group ?? '—' }}</dd>
                        @if(!$patient->blood_group && $canWrite)
                            <a href="{{ route('portals.patient.profile') }}" class="odpt-small">{{ __('public.portal.lnk_add_in_profile', [], $l) }}</a>
                        @endif
                    </div>
                    <div>
                        <dt>{{ __('public.portal.active_allergies', [], $l) }}</dt>
                        <dd>{{ ($activeAllergies ?? collect())->count() }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('public.portal.active_conditions', [], $l) }}</dt>
                        <dd>{{ ($activeConditions ?? collect())->count() }}</dd>
                    </div>
                </dl>
                <div class="odpt-crit">
                    <span class="odpt-crit__label">{{ __('public.portal.critical_allergies', [], $l) }}</span>
                    @if(($criticalAllergies ?? collect())->isEmpty())
                        <span class="od-pill od-pill--ready">{{ __('public.portal.none_on_record', [], $l) }}</span>
                    @else
                        @foreach(($criticalAllergies ?? collect())->take(3) as $a)
                            <span class="od-pill od-pill--urgent">{{ $a->substance }}</span>
                        @endforeach
                    @endif
                </div>
                @if(($activeAllergies ?? collect())->isNotEmpty() || ($activeConditions ?? collect())->isNotEmpty())
                <p class="odpt-hint">{{ __('public.portal.allergy_safety_notice', [], $l) }}</p>
                @endif
            </section>

            {{-- Onboarding checklist (own account only) --}}
            @isset($onboarding)
                @php
                    $obDone  = collect($onboarding)->where('done', true)->count();
                    $obTotal = count($onboarding);
                    $obIcons = ['id-card' => 'shield-check', 'badge-check' => 'badge-check', 'users' => 'users', 'gift' => 'gift'];
                @endphp
                @if($obTotal && $obDone < $obTotal)
                <section class="od-card" aria-labelledby="odpt-onb-title">
                    <header class="od-card__head">
                        <h2 class="od-card__title" id="odpt-onb-title">{{ __('public.pat_onb_title') }}</h2>
                        <span class="od-pill od-pill--normal">{{ __('public.pat_onb_progress', ['done' => $obDone, 'total' => $obTotal]) }}</span>
                    </header>
                    <p class="odpt-hint">{{ __('public.pat_onb_subtitle') }}</p>
                    <progress class="odpt-progress" max="{{ $obTotal }}" value="{{ $obDone }}">{{ $obDone }}/{{ $obTotal }}</progress>
                    <ul class="od-list">
                        @foreach($onboarding as $step)
                        <li class="od-list__row">
                            <span class="od-list__icon {{ $step['done'] ? 'od-tint--green' : 'od-tint--blue' }}"><i data-lucide="{{ $step['done'] ? 'check' : ($obIcons[$step['icon']] ?? 'chevron-right') }}"></i></span>
                            <span class="od-list__text"><span class="od-list__title">{{ __('public.pat_onb_' . $step['key']) }}</span></span>
                            @if($step['done'])
                                <span class="od-pill od-pill--ready">{{ __('public.pat_onb_done') }}</span>
                            @elseif($step['url'])
                                <a href="{{ $step['url'] }}" class="od-btn od-btn--icon od-btn--secondary" aria-label="{{ __('public.pat_onb_' . $step['key']) }}"><i data-lucide="arrow-right"></i></a>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </section>
                @endif
            @endisset
        </div>
    </div>

    {{-- Every record page, one quiet row of links --}}
    <nav class="od-card odpt-links" aria-labelledby="odpt-links-title" data-section="quick-actions">
        <h2 class="od-card__title" id="odpt-links-title">{{ __('patient_ui.your_record') }}</h2>
        <ul class="odpt-links__grid">
            <li><a href="{{ route('portals.patient.appointments') }}"><i data-lucide="calendar"></i>{{ __('public.portal.nav_appointments', [], $l) }}</a></li>
            <li><a href="{{ route('portals.patient.prescriptions') }}"><i data-lucide="pill"></i>{{ __('public.portal.nav_prescriptions', [], $l) }}</a></li>
            <li><a href="{{ route('portals.patient.immunizations') }}"><i data-lucide="syringe"></i>{{ __('family.nav_immunizations') }}</a></li>
            <li><a href="{{ route('portals.patient.timeline') }}"><i data-lucide="activity"></i>{{ __('family.nav_timeline') }}</a></li>
            <li><a href="{{ route('portals.patient.documents') }}"><i data-lucide="file-text"></i>{{ __('family.nav_documents') }}</a></li>
            <li><a href="{{ route('portals.patient.allergies') }}"><i data-lucide="triangle-alert"></i>{{ __('family.nav_allergies') }}</a></li>
            <li><a href="{{ route('portals.patient.clinical') }}"><i data-lucide="stethoscope"></i>{{ __('family.nav_clinical') }}</a></li>
            <li><a href="{{ route('portals.patient.care-plans') }}"><i data-lucide="clipboard-list"></i>{{ __('family.nav_care_plans') }}</a></li>
            <li><a href="{{ route('portals.patient.access-logs') }}"><i data-lucide="history"></i>{{ __('family.nav_access_logs') }}</a></li>
            <li><a href="{{ route('portals.patient.profile') }}"><i data-lucide="user-round"></i>{{ __('public.portal.nav_profile', [], $l) }}</a></li>
            <li><a href="{{ route('public.care-map') }}"><i data-lucide="map-pin"></i>{{ __('public.portal.nav_care_map', [], $l) }}</a></li>
            <li><a href="{{ route('portals.patient.family') }}"><i data-lucide="users"></i>{{ __('public.portal.nav_family', [], $l) }}</a></li>
            <li><a href="{{ route('portals.guardian.dashboard') }}"><i data-lucide="baby"></i>{{ __('family.nav_dependents') }}</a></li>
        </ul>
    </nav>

    <p class="odpt-disclaimer"><i data-lucide="info"></i>{{ __('onboarding.brand.clinical_disclaimer', [], $l) }}</p>
</div>

@else
{{-- No patient profile --}}
<div class="odpt">
    <section class="od-card">
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="circle-alert"></i></span>
            <div class="od-empty__title">{{ __('public.portal.no_profile_title', [], app()->getLocale()) }}</div>
            <p class="od-empty__text">{{ __('public.portal.no_profile_desc', [], app()->getLocale()) }}</p>
            <div class="odpt-greet__actions">
                <a href="{{ route('portals.guardian.dashboard') }}" class="od-btn od-btn--secondary"><i data-lucide="users"></i> {{ __('family.nav_dependents') }}</a>
                <a href="{{ route('public.help') }}" class="od-btn od-btn--primary"><i data-lucide="info"></i> {{ __('public.portal.nav_help', [], app()->getLocale()) }}</a>
            </div>
        </div>
    </section>
</div>
@endif

@endsection

@section('scripts')
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function () {
    var countdownInterval = null;
    var btnGen = document.getElementById('generate-temp-qr');
    var errEl  = document.getElementById('temp-qr-error');

    if (btnGen) {
        var lblGenerating   = btnGen.getAttribute('data-label-generating');
        var lblRegenerateQr = btnGen.getAttribute('data-label-regenerate');
        var lblQrExpired    = @json(__('public.portal.lbl_qr_expired', [], app()->getLocale()));

        btnGen.addEventListener('click', async function () {
            btnGen.disabled = true;
            btnGen.textContent = lblGenerating;
            if (errEl) { errEl.hidden = true; errEl.textContent = ''; }

            try {
                var response = await fetch("{{ route('portals.patient.qr') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (!response.ok) {
                    // A refusal carries its reason (read-only guardian, expired switch): show it, never a silent failure.
                    var body = null;
                    try { body = await response.json(); } catch (ignore) {}
                    if (errEl) { errEl.textContent = (body && body.message) ? body.message : ('QR generation failed (' + response.status + ')'); errEl.hidden = false; }
                    return;
                }
                var data = await response.json();
                if (data.qr_image) {
                    var img         = document.getElementById('temp-qr-img');
                    var placeholder = document.getElementById('temp-qr-placeholder');
                    var container   = document.getElementById('temp-qr-container');
                    img.src         = data.qr_image;
                    img.hidden         = false;
                    placeholder.hidden = true;
                    container.hidden   = false;
                    startCountdown(data.expires_in || 3600);
                }
            } catch (e) {
                console.error('Temp QR error:', e);
            } finally {
                btnGen.disabled = false;
                btnGen.textContent = lblRegenerateQr;
            }
        });

        function startCountdown(seconds) {
            if (countdownInterval) clearInterval(countdownInterval);
            var el = document.getElementById('countdown');
            countdownInterval = setInterval(function () {
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    if (el) el.textContent = lblQrExpired;
                    return;
                }
                seconds--;
                var m = Math.floor(seconds / 60).toString().padStart(2, '0');
                var s = (seconds % 60).toString().padStart(2, '0');
                if (el) el.textContent = m + ':' + s;
            }, 1000);
        }
    }
});
</script>
@endsection
