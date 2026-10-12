@extends('layouts.portal')

@section('title', __('public.medical_id.health_id', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $l         = app()->getLocale();
    $isViewing = $isViewing ?? false;
    $canWrite  = $canWrite ?? true;
    $viewName  = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
@endphp

@section('content')

@if($patient)

<div class="pf pf--wide">

    {{-- Greeting --}}
    <div class="pf-head">
        <div>
            <h1>
                @if($isViewing)
                    {{ __('family.record_of', ['name' => $viewName]) }}
                @elseif($patient->first_name)
                    {{ __('finder_patient.greeting', ['name' => $patient->first_name]) }}
                @else
                    {{ __('finder_patient.greeting_generic') }}
                @endif
            </h1>
            <p class="pf-sub">
                @if($isViewing)
                    {{ $canWrite ? __('family.access_full_explained') : __('family.access_read_only_explained') }}
                @else
                    {{ __('finder_patient.greeting_sub') }}
                @endif
            </p>
        </div>
        @if($isViewing)
        <a href="{{ route('portals.guardian.dashboard') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'users', 'size' => 18])
            {{ __('family.nav_dependents') }}
        </a>
        @endif
    </div>

    {{-- Health ID card --}}
    <div class="pf-hid pf-section" data-section="health-id-card">
        <div class="pf-hid__row">
            <div style="min-width:0;flex:1;">
                <span class="pf-hid__label">
                    @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16])
                    {{ __('public.medical_id.health_id', [], $l) }}
                </span>
                <div class="pf-hid__value">{{ $patient->health_id }}</div>
                @if($isViewing)
                <div style="font-weight:700;margin:-8px 0 12px;">{{ $viewName }}</div>
                @endif
                <div class="pf-hid__meta">
                    <div>
                        <div class="pf-hid__meta-label">{{ __('public.portal.status', [], $l) }}</div>
                        <div class="pf-hid__meta-value">
                            @enum(($patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : $patient->verification_status) ?? 'Active', 'verification')
                        </div>
                    </div>
                    <div>
                        <div class="pf-hid__meta-label">{{ __('public.portal.country', [], $l) }}</div>
                        <div class="pf-hid__meta-value">{{ $patient->country_code ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="pf-hid__meta-label">{{ __('public.portal.registered', [], $l) }}</div>
                        <div class="pf-hid__meta-value">{{ $patient->created_at?->translatedFormat('M Y') ?? '—' }}</div>
                    </div>
                </div>
                @if($canWrite)
                <a href="{{ route('portals.patient.health-id-card') }}" class="f-btn" style="margin-top:14px;background:#fff;color:var(--f-brand-ink);border-color:#fff;" data-action="print-card">
                    <i data-lucide="printer" aria-hidden="true" style="width:16px;height:16px;"></i>
                    {{ __('family.card_print') }}
                </a>
                @endif
            </div>
            <div class="pf-hid__qr">
                @if($staticQrDataUri)
                    <img src="{{ $staticQrDataUri }}" alt="{{ __('public.medical_id.health_id_qr_alt', [], $l) }}">
                    <span>{{ __('public.medical_id.scan_qr', [], $l) }}</span>
                @elseif(!$canWrite)
                    <div style="width:88px;height:88px;display:flex;align-items:center;justify-content:center;" title="{{ __('family.read_only_qr_note', ['name' => $viewName]) }}">
                        <i data-lucide="lock" aria-hidden="true" style="width:36px;height:36px;"></i>
                    </div>
                    <span>{{ __('family.banner_read_only') }}</span>
                @else
                    <div style="width:88px;height:88px;display:flex;align-items:center;justify-content:center;">
                        @include('partials.finder.icon', ['name' => 'qr-code', 'size' => 40])
                    </div>
                    <span>{{ __('public.medical_id.scan_qr', [], $l) }}</span>
                @endif
            </div>
        </div>
    </div>

    @if($isViewing && !$canWrite)
    <div class="pf-alert" data-section="read-only-note">
        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
        <p class="f-small">{{ __('family.read_only_qr_note', ['name' => $viewName]) }}</p>
    </div>
    @endif

    {{-- Onboarding checklist --}}
    @isset($onboarding)
        @php
            $obDone  = collect($onboarding)->where('done', true)->count();
            $obTotal = count($onboarding);
            $obIcons = ['id-card' => 'shield-check', 'badge-check' => 'check', 'users' => 'users', 'gift' => 'gift'];
        @endphp
        @if($obTotal && $obDone < $obTotal)
        <div class="f-card pf-section">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'star', 'size' => 18])
                {{ __('public.pat_onb_title') }}
                <span class="f-badge">{{ __('public.pat_onb_progress', ['done' => $obDone, 'total' => $obTotal]) }}</span>
            </h2>
            <p class="f-hint" style="margin:-6px 0 0;">{{ __('public.pat_onb_subtitle') }}</p>
            <div class="pf-progress"><span style="width:{{ round($obDone / $obTotal * 100) }}%;"></span></div>
            <ul style="list-style:none;display:grid;gap:10px;">
                @foreach($onboarding as $step)
                <li style="display:flex;align-items:center;gap:10px;">
                    <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;{{ $step['done'] ? 'background:var(--f-good-wash);color:var(--f-good);' : '' }}">
                        @include('partials.finder.icon', ['name' => $step['done'] ? 'check' : ($obIcons[$step['icon']] ?? 'chevron-right'), 'size' => 15])
                    </span>
                    <span style="flex:1;{{ $step['done'] ? 'color:var(--f-muted);' : '' }}">{{ __('public.pat_onb_' . $step['key']) }}</span>
                    @if($step['done'])
                        <span class="f-badge f-badge--good">{{ __('public.pat_onb_done') }}</span>
                    @elseif($step['url'])
                        <a href="{{ $step['url'] }}" class="f-btn" style="min-height:40px;padding:6px 12px;" aria-label="{{ __('public.pat_onb_' . $step['key']) }}">
                            @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                        </a>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    @endisset

    {{-- Quick actions — every record page has its link here --}}
    <div class="pf-section">
        <h2 class="pf-card-head" style="margin-bottom:10px;">{{ __('finder_patient.quick_actions') }}</h2>
        <div class="pf-qa" data-section="quick-actions">
            <a href="{{ route('portals.patient.appointments') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'calendar', 'size' => 20])</span>
                {{ __('public.portal.nav_appointments', [], $l) }}
            </a>
            <a href="{{ route('portals.patient.prescriptions') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'pill', 'size' => 20])</span>
                {{ __('public.portal.nav_prescriptions', [], $l) }}
            </a>
            <a href="{{ route('portals.patient.immunizations') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><i data-lucide="syringe" aria-hidden="true" style="width:20px;height:20px;"></i></span>
                {{ __('family.nav_immunizations') }}
            </a>
            <a href="{{ route('portals.patient.timeline') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><i data-lucide="activity" aria-hidden="true" style="width:20px;height:20px;"></i></span>
                {{ __('family.nav_timeline') }}
            </a>
            <a href="{{ route('portals.patient.documents') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><i data-lucide="file-text" aria-hidden="true" style="width:20px;height:20px;"></i></span>
                {{ __('family.nav_documents') }}
            </a>
            <a href="{{ route('portals.patient.allergies') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 20])</span>
                {{ __('family.nav_allergies') }}
            </a>
            <a href="{{ route('portals.patient.clinical') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'stethoscope', 'size' => 20])</span>
                {{ __('family.nav_clinical') }}
            </a>
            <a href="{{ route('portals.patient.care-plans') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><i data-lucide="clipboard-list" aria-hidden="true" style="width:20px;height:20px;"></i></span>
                {{ __('family.nav_care_plans') }}
            </a>
            <a href="{{ route('portals.patient.access-logs') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><i data-lucide="history" aria-hidden="true" style="width:20px;height:20px;"></i></span>
                {{ __('family.nav_access_logs') }}
            </a>
            <a href="{{ route('portals.patient.profile') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 20])</span>
                {{ __('public.portal.nav_profile', [], $l) }}
            </a>
            <a href="{{ route('public.care-map') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 20])</span>
                {{ __('public.portal.nav_care_map', [], $l) }}
            </a>
            <a href="{{ route('portals.patient.family') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon">@include('partials.finder.icon', ['name' => 'users', 'size' => 20])</span>
                {{ __('public.portal.nav_family', [], $l) }}
            </a>
            <a href="{{ route('portals.guardian.dashboard') }}" class="f-card pf-qa__item">
                <span class="pf-qa__icon"><i data-lucide="baby" aria-hidden="true" style="width:20px;height:20px;"></i></span>
                {{ __('family.nav_dependents') }}
            </a>
        </div>
    </div>

    {{-- Upcoming appointments --}}
    <div class="pf-section">
        <h2 class="pf-card-head" style="margin-bottom:10px;">
            @include('partials.finder.icon', ['name' => 'calendar', 'size' => 18])
            {{ __('public.portal.upcoming_appointments', [], $l) }}
            @if(isset($upcomingAppointments) && $upcomingAppointments->isNotEmpty())
            <a href="{{ route('portals.patient.appointments') }}" class="f-btn f-btn--ghost" style="margin-left:auto;min-height:36px;padding:4px 12px;">
                {{ __('public.portal.view_all', [], $l) }}
            </a>
            @endif
        </h2>
        @if(isset($upcomingAppointments) && $upcomingAppointments->isNotEmpty())
        <div class="pf-stack">
            @foreach($upcomingAppointments->take(3) as $appt)
            <div class="f-card">
                <div class="pf-appt">
                    <div class="pf-appt__date" aria-hidden="true">
                        <div class="d">{{ $appt->scheduled_at?->format('d') ?? '—' }}</div>
                        <div class="m">{{ $appt->scheduled_at?->isoFormat('MMM') ?? '' }}</div>
                    </div>
                    <div class="pf-appt__body">
                        <p class="pf-appt__title">
                            @enum($appt->appointment_type ?? 'consultation')
                            <span class="f-badge f-badge--good">@enum($appt->status ?? 'scheduled')</span>
                        </p>
                        <div class="pf-appt__meta">
                            @if($appt->scheduled_at)
                            <span>
                                @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                                {{ $appt->scheduled_at->isoFormat('ddd D MMM, HH:mm') }}
                            </span>
                            @endif
                            <span>
                                @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 15])
                                {{ $appt->facility?->name ?? '—' }}
                            </span>
                            @if($appt->provider?->name)
                            <span>
                                @include('partials.finder.icon', ['name' => 'users', 'size' => 15])
                                {{ __('finder_patient.with_provider', ['name' => $appt->provider->name]) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="f-card" style="text-align:center;padding:28px 20px;">
            <p style="font-weight:700;margin:0 0 4px;">{{ __('finder_patient.no_upcoming_title') }}</p>
            <p class="f-muted f-small" style="margin:0 0 14px;">{{ __('finder_patient.no_upcoming_body') }}</p>
            @if($canWrite)
            <a href="{{ route('portals.patient.appointments.book') }}" class="f-btn f-btn--primary" style="min-height:48px;">
                @include('partials.finder.icon', ['name' => 'calendar', 'size' => 18])
                {{ __('public.appt_book_btn') }}
            </a>
            @endif
        </div>
        @endif
    </div>

    {{-- Clinical safety summary --}}
    <div class="f-card pf-section">
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18])
            {{ __('public.portal.clinical_safety_summary', [], $l) }}
        </h2>
        <div class="pf-tiles">
            <div>
                <div class="pf-tile__label">{{ __('public.portal.blood_group', [], $l) }}</div>
                <div class="pf-tile__value">{{ $patient->blood_group ?? '—' }}</div>
                @if(!$patient->blood_group && $canWrite)
                    <a href="{{ route('portals.patient.profile') }}" class="f-small">{{ __('public.portal.lnk_add_in_profile', [], $l) }}</a>
                @endif
            </div>
            <div>
                <div class="pf-tile__label">{{ __('public.portal.critical_allergies', [], $l) }}</div>
                @if(($criticalAllergies ?? collect())->isEmpty())
                    <div class="pf-tile__value" style="color:var(--f-good);font-size:.9375rem;">{{ __('public.portal.none_on_record', [], $l) }}</div>
                @else
                    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">
                        @foreach(($criticalAllergies ?? collect())->take(3) as $a)
                            <span class="f-badge f-badge--danger">{{ $a->substance }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div>
                <div class="pf-tile__label">{{ __('public.portal.active_allergies', [], $l) }}</div>
                <div class="pf-tile__value">{{ ($activeAllergies ?? collect())->count() }}</div>
            </div>
            <div>
                <div class="pf-tile__label">{{ __('public.portal.active_conditions', [], $l) }}</div>
                <div class="pf-tile__value">{{ ($activeConditions ?? collect())->count() }}</div>
            </div>
        </div>
        @if(($activeAllergies ?? collect())->isNotEmpty() || ($activeConditions ?? collect())->isNotEmpty())
        <div class="pf-alert pf-alert--warn" style="margin:14px 0 0;">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
            <p>{{ __('public.portal.allergy_safety_notice', [], $l) }}</p>
        </div>
        @endif
    </div>

    <div class="pf-cols pf-section">

        {{-- Temporary access QR --}}
        <div class="f-card" style="text-align:center;" data-section="temporary-qr">
            <h2 class="pf-card-head" style="justify-content:center;">
                @include('partials.finder.icon', ['name' => 'qr-code', 'size' => 18])
                {{ __('public.medical_id.temporary_access_qr', [], $l) }}
            </h2>
            <p class="f-muted f-small" style="margin:0 0 16px;">
                {{ __('public.portal.temp_qr_desc', [], $l) }}
            </p>

            @if($canWrite)
            <button id="generate-temp-qr" class="f-btn f-btn--primary" style="min-height:48px;"
                    data-label-generating="{{ __('public.portal.generating', [], $l) }}"
                    data-label-regenerate="{{ __('public.portal.regenerate_qr', [], $l) }}">
                {{ __('public.portal.generate_qr', [], $l) }}
            </button>
            @else
            <div class="pf-alert pf-alert--warn" style="text-align:left;margin:0;">
                <i data-lucide="lock" aria-hidden="true" style="width:18px;height:18px;flex:none;"></i>
                <p class="f-small">{{ __('family.read_only_qr_note', ['name' => $viewName]) }}</p>
            </div>
            @endif

            <p id="temp-qr-error" class="f-small" role="alert" style="display:none;color:var(--f-danger);margin:12px 0 0;"></p>

            <div id="temp-qr-container" style="display:none;flex-direction:column;align-items:center;margin-top:16px;" aria-live="polite">
                <div style="padding:10px;border-radius:var(--f-r);border:1px solid var(--f-line);background:var(--f-surface);">
                    <img id="temp-qr-img" src="" alt="{{ __('public.portal.temp_qr_alt', [], $l) }}"
                         style="width:8rem;height:8rem;border-radius:6px;display:none;">
                    <div id="temp-qr-placeholder" style="width:8rem;height:8rem;display:flex;align-items:center;justify-content:center;background:var(--f-wash);border-radius:6px;color:var(--f-brand);">
                        @include('partials.finder.icon', ['name' => 'qr-code', 'size' => 48])
                    </div>
                </div>
                <p class="f-small" style="color:var(--f-warn);font-weight:700;display:flex;align-items:center;gap:6px;margin:10px 0 0;">
                    @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                    {{ __('public.portal.expires_in', [], $l) }}: <span id="countdown">60:00</span>
                </p>
            </div>

            <div class="pf-alert" style="text-align:left;margin:16px 0 0;">
                @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                <p class="f-small">{{ __('public.portal.qr_audit_notice', [], $l) }}
                    <a href="{{ route('portals.patient.access-logs') }}">{{ __('family.nav_access_logs') }}</a></p>
            </div>
        </div>

        <div class="pf-stack">
            {{-- Profile prompt --}}
            <div class="f-card">
                <p class="f-muted f-small" style="margin:0 0 12px;">{{ __('public.portal.profile_prompt_desc', [], $l) }}</p>
                <a href="{{ route('portals.patient.profile') }}" class="f-btn">
                    @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16])
                    {{ __('public.portal.profile_prompt_btn', [], $l) }}
                </a>
            </div>

            {{-- Clinical disclaimer --}}
            <div class="pf-alert pf-alert--warn" style="margin:0;">
                @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                <p class="f-small">{{ __('onboarding.brand.clinical_disclaimer', [], $l) }}</p>
            </div>
        </div>

    </div>
</div>

@else
{{-- No patient profile --}}
<div class="pf">
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.portal.no_profile_title', [], app()->getLocale()) }}</h2>
        <p class="f-muted" style="margin:0 0 18px;">{{ __('public.portal.no_profile_desc', [], app()->getLocale()) }}</p>
        <div class="pf-actions" style="justify-content:center;">
            <a href="{{ route('portals.guardian.dashboard') }}" class="f-btn">
                @include('partials.finder.icon', ['name' => 'users', 'size' => 18])
                {{ __('family.nav_dependents') }}
            </a>
            <a href="{{ route('public.help') }}" class="f-btn f-btn--primary">
                @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                {{ __('public.portal.nav_help', [], app()->getLocale()) }}
            </a>
        </div>
    </div>
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
            if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }

            try {
                var response = await fetch("{{ route('portals.patient.qr') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (!response.ok) {
                    // A refusal carries its reason (read-only guardian, expired switch): show it, never a silent failure.
                    var body = null;
                    try { body = await response.json(); } catch (ignore) {}
                    if (errEl) { errEl.textContent = (body && body.message) ? body.message : ('QR generation failed (' + response.status + ')'); errEl.style.display = 'block'; }
                    return;
                }
                var data = await response.json();
                if (data.qr_image) {
                    var img         = document.getElementById('temp-qr-img');
                    var placeholder = document.getElementById('temp-qr-placeholder');
                    var container   = document.getElementById('temp-qr-container');
                    img.src         = data.qr_image;
                    img.style.display         = 'block';
                    placeholder.style.display = 'none';
                    container.style.display   = 'flex';
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
