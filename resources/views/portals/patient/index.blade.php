@extends('layouts.portal')

@section('title', __('public.medical_id.health_id', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))

@section('content')

@if($patient)

<!-- Health ID Card -->
<div class="health-id-card mb-8" style="background:#0F2744;">
    <div class="flex-between" style="align-items:flex-start;gap:var(--p-space-5);">
        <div style="flex:1;">
            <div class="flex-between" style="justify-content:flex-start;gap:var(--p-space-3);margin-bottom:var(--p-space-5);">
                <div style="padding:var(--p-space-2);background:rgba(255,255,255,.15);border-radius:var(--p-radius);">
                    <i data-lucide="fingerprint"></i>
                </div>
                <span class="health-id-label">{{ __('public.medical_id.health_id', [], app()->getLocale()) ?: 'OpesCare Health ID' }}</span>
            </div>

            <div class="health-id-value">{{ $patient->health_id }}</div>

            <div class="health-id-meta">
                <div>
                    <div class="health-id-meta-label">{{ __('public.portal.status', [], app()->getLocale()) ?: 'Status' }}</div>
                    <div class="health-id-meta-value">
                        @enum(($patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : $patient->verification_status) ?? 'Active', 'verification')
                    </div>
                </div>
                <div>
                    <div class="health-id-meta-label">{{ __('public.portal.country', [], app()->getLocale()) ?: 'Country' }}</div>
                    <div class="health-id-meta-value">{{ $patient->country_code ?? '—' }}</div>
                </div>
                <div>
                    <div class="health-id-meta-label">{{ __('public.portal.registered', [], app()->getLocale()) ?: 'Registered' }}</div>
                    <div class="health-id-meta-value">{{ $patient->created_at?->format('M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Static QR (server-rendered) -->
        <div class="health-id-qr" aria-label="{{ __('public.medical_id.scan_qr', [], app()->getLocale()) ?: 'Scan QR' }}">
            @if($staticQrDataUri)
                <img src="{{ $staticQrDataUri }}"
                     alt="{{ __('public.medical_id.health_id_qr_alt', [], app()->getLocale()) ?: 'Health ID QR Code' }}"
                     style="width:5rem;height:5rem;border-radius:var(--p-radius-sm);" />
            @else
                <div style="width:5rem;height:5rem;display:flex;align-items:center;justify-content:center;background:var(--p-surface-2);border-radius:var(--p-radius-sm);">
                    <i data-lucide="qr-code" style="width:3rem;height:3rem;"></i>
                </div>
            @endif
            <span>{{ __('public.medical_id.scan_qr', [], app()->getLocale()) ?: 'Scan QR' }}</span>
        </div>
    </div>
</div>

@isset($onboarding)
    @php $obDone = collect($onboarding)->where('done', true)->count(); $obTotal = count($onboarding); @endphp
    @if($obTotal && $obDone < $obTotal)
    <div class="panel mb-8">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="rocket"></i> {{ __('public.pat_onb_title') }}</h3>
            <span class="badge badge-info">{{ __('public.pat_onb_progress', ['done' => $obDone, 'total' => $obTotal]) }}</span>
        </div>
        <div class="panel-body">
            <p class="page-subtitle" style="margin:0 0 1rem;">{{ __('public.pat_onb_subtitle') }}</p>
            <div style="height:6px;background:var(--color-border);border-radius:3px;overflow:hidden;margin-bottom:1.25rem;">
                <div style="height:100%;width:{{ round($obDone / $obTotal * 100) }}%;background:#0F4C81;border-radius:3px;"></div>
            </div>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.65rem;">
                @foreach($onboarding as $step)
                    <li style="display:flex;align-items:center;gap:.75rem;">
                        <span style="flex-shrink:0;width:1.65rem;height:1.65rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:{{ $step['done'] ? '#0F4C81' : 'rgba(15,76,129,.08)' }};color:{{ $step['done'] ? '#fff' : '#0F4C81' }};">
                            <i data-lucide="{{ $step['done'] ? 'check' : $step['icon'] }}" style="width:.9rem;height:.9rem;"></i>
                        </span>
                        <span style="flex:1;{{ $step['done'] ? 'text-decoration:line-through;color:var(--color-text-secondary);' : '' }}">{{ __('public.pat_onb_' . $step['key']) }}</span>
                        @if($step['done'])
                            <span class="badge badge-success">{{ __('public.pat_onb_done') }}</span>
                        @elseif($step['url'])
                            <a href="{{ $step['url'] }}" class="btn btn-secondary btn-sm" aria-label="{{ __('public.pat_onb_' . $step['key']) }}"><i data-lucide="arrow-right" style="width:1rem;height:1rem;"></i></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
@endisset

<!-- Quick Actions -->
<div class="quick-actions mb-8">
    <a href="{{ route('portals.patient.appointments') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="calendar-check-2"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</span>
    </a>
    <a href="{{ route('portals.patient.labs') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="flask-conical"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_labs', [], app()->getLocale()) ?: 'Lab Results' }}</span>
    </a>
    <a href="{{ route('portals.patient.prescriptions') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="pill"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_prescriptions', [], app()->getLocale()) ?: 'Prescriptions' }}</span>
    </a>
    <a href="{{ route('portals.patient.consent') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="shield-check"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_consent', [], app()->getLocale()) ?: 'Consent' }}</span>
    </a>
    <a href="{{ route('portals.patient.documents') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="file-text"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_documents', [], app()->getLocale()) ?: 'Documents' }}</span>
    </a>
    <a href="{{ route('portals.patient.logs') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="history"></i></div>
        <span class="quick-action-label">{{ __('public.medical_id.access_logs', [], app()->getLocale()) ?: 'Access Logs' }}</span>
    </a>
    <a href="{{ route('portals.patient.profile') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="user-cog"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_profile', [], app()->getLocale()) ?: 'My Profile' }}</span>
    </a>
    <a href="{{ route('portals.patient.allergies') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="zap"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_allergies', [], app()->getLocale()) ?: 'Allergies' }}</span>
    </a>
    <a href="{{ route('portals.patient.clinical') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="stethoscope"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_conditions', [], app()->getLocale()) ?: 'Conditions' }}</span>
    </a>
    <a href="{{ route('portals.patient.immunizations') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="syringe"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_immunizations', [], app()->getLocale()) ?: 'Immunizations' }}</span>
    </a>
    <a href="{{ route('public.care-map') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="map-pin"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_care_map', [], app()->getLocale()) ?: 'Care Map' }}</span>
    </a>
    <a href="{{ route('portals.patient.family') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="users"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_family', [], app()->getLocale()) ?: 'Family' }}</span>
    </a>
    @feature('insurance_marketplace')
    <a href="{{ route('portals.patient.insurance') }}" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="shield"></i></div>
        <span class="quick-action-label">{{ __('public.portal.nav_insurance', [], app()->getLocale()) ?: 'Insurance' }}</span>
    </a>
    @endfeature
</div>

@if(isset($upcomingAppointments) && $upcomingAppointments->isNotEmpty())
<div class="panel mb-6">
  <div class="panel-header">
    <h3 class="panel-title"><i data-lucide="calendar-clock"></i> {{ __('public.portal.upcoming_appointments', [], app()->getLocale()) ?: 'Upcoming Appointments' }}</h3>
    <a href="{{ route('portals.patient.appointments') }}" class="btn btn-ghost btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead><tr><th>{{ __('public.portal.col_date', [], app()->getLocale()) ?: 'Date' }}</th><th>{{ __('public.portal.col_doctor', [], app()->getLocale()) ?: 'Doctor' }}</th><th>{{ __('public.portal.col_facility', [], app()->getLocale()) ?: 'Facility' }}</th><th>{{ __('public.portal.col_type', [], app()->getLocale()) ?: 'Type' }}</th></tr></thead>
      <tbody>
      @foreach($upcomingAppointments->take(3) as $appt)
      <tr>
        <td data-label="Date">{{ $appt->scheduled_at?->format('d M Y, H:i') ?? '—' }}</td>
        <td data-label="Doctor">{{ $appt->provider?->name ?? '—' }}</td>
        <td data-label="Facility">{{ $appt->facility?->name ?? '—' }}</td>
        <td data-label="Type"><span class="badge badge-info">@enum($appt->appointment_type ?? 'consultation')</span></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<!-- Clinical Safety Banner -->
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="shield-alert"></i> {{ __('public.portal.clinical_safety_summary', [], app()->getLocale()) ?: 'Clinical Safety Summary' }}
        </h2>
    </div>
    <div class="panel-body">
        <div class="stat-grid">

            {{-- Blood Group --}}
            <div class="stat-card stat-card--danger">
                <div class="stat-card__label">{{ __('public.portal.blood_group', [], app()->getLocale()) ?: 'Blood Group' }}</div>
                <div class="stat-card__value">{{ $patient->blood_group ?? '—' }}</div>
                @if(!$patient->blood_group)
                    <a href="{{ route('portals.patient.profile') }}" class="stat-card__hint">{{ __('public.portal.lnk_add_in_profile', [], app()->getLocale()) ?: 'Add in profile →' }}</a>
                @endif
            </div>

            {{-- Critical Allergies --}}
            <div class="stat-card stat-card--danger">
                <div class="stat-card__label">{{ __('public.portal.critical_allergies', [], app()->getLocale()) ?: 'Critical Allergies' }}</div>
                @if(($criticalAllergies ?? collect())->isEmpty())
                    <div class="stat-card__value text-success">{{ __('public.portal.none_on_record', [], app()->getLocale()) ?: 'None on record' }}</div>
                @else
                    <div class="stat-card__value">
                        @foreach(($criticalAllergies ?? collect())->take(3) as $a)
                            <div class="text-sm"><span class="badge badge-danger"><i data-lucide="alert-triangle"></i> {{ $a->substance }}</span></div>
                        @endforeach
                    </div>
                    @if(($criticalAllergies ?? collect())->count() > 3)
                        <a href="{{ route('portals.patient.allergies') }}" class="stat-card__hint">+{{ ($criticalAllergies ?? collect())->count() - 3 }} {{ __('public.portal.lnk_more_suffix', [], app()->getLocale()) ?: 'more →' }}</a>
                    @endif
                @endif
            </div>

            {{-- All Active Allergies count --}}
            <div class="stat-card {{ ($activeAllergies ?? collect())->isEmpty() ? '' : 'stat-card--warning' }}">
                <div class="stat-card__label">{{ __('public.portal.active_allergies', [], app()->getLocale()) ?: 'Active Allergies' }}</div>
                <div class="stat-card__value">{{ ($activeAllergies ?? collect())->count() }}</div>
                <a href="{{ route('portals.patient.allergies') }}" class="stat-card__hint">{{ __('public.portal.lnk_view_all', [], app()->getLocale()) ?: 'View all →' }}</a>
            </div>

            {{-- Active Conditions --}}
            <div class="stat-card">
                <div class="stat-card__label">{{ __('public.portal.active_conditions', [], app()->getLocale()) ?: 'Active Conditions' }}</div>
                <div class="stat-card__value">{{ ($activeConditions ?? collect())->count() }}</div>
                @if(($activeConditions ?? collect())->isNotEmpty())
                    <a href="{{ route('portals.patient.clinical') }}" class="stat-card__hint">{{ __('public.portal.lnk_view_all', [], app()->getLocale()) ?: 'View all →' }}</a>
                @endif
            </div>

        </div>

        @if(($activeAllergies ?? collect())->isNotEmpty() || ($activeConditions ?? collect())->isNotEmpty())
        <div class="alert alert-warning mt-3">
            <i data-lucide="alert-triangle"></i>
            <div>
                {{ __('public.portal.allergy_safety_notice', [], app()->getLocale()) ?: 'Ensure your care team has your up-to-date allergy and condition list before any procedure or prescription.' }}
                <a href="{{ route('portals.patient.allergies') }}">{{ __('public.portal.view_allergy_list', [], app()->getLocale()) ?: 'View full allergy list' }}</a>.
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Main Two-Column Layout -->
<div class="grid-main-side">

    <!-- Left: Temp QR Generator -->
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="qr-code"></i>
                {{ __('public.medical_id.temporary_access_qr', [], app()->getLocale()) ?: 'Temporary Access QR' }}
            </h2>
        </div>
        <div class="panel-body" style="text-align:center;">
            <p class="text-muted mb-6">
                {{ __('public.portal.temp_qr_desc', [], app()->getLocale()) ?: 'Generate a secure, time-limited QR code to share with a new healthcare provider. Expires in 1 hour.' }}
            </p>

            <button id="generate-temp-qr" class="btn btn-primary">
                <i data-lucide="refresh-cw"></i>
                {{ __('public.portal.generate_qr', [], app()->getLocale()) ?: 'Generate Temporary QR' }}
            </button>

            <div id="temp-qr-container" class="mt-6" style="display:none;flex-direction:column;align-items:center;" aria-live="polite">
                <div style="padding:var(--p-space-3);border-radius:var(--p-radius);border:1px solid var(--p-border);background:var(--p-surface);">
                    <img id="temp-qr-img" src="" alt="{{ __('public.portal.temp_qr_alt', [], app()->getLocale()) ?: 'Temporary Access QR Code' }}"
                         style="width:8rem;height:8rem;border-radius:var(--p-radius-sm);display:none;" />
                    <div id="temp-qr-placeholder" style="width:8rem;height:8rem;display:flex;align-items:center;justify-content:center;background:var(--p-surface-2);border-radius:var(--p-radius-sm);">
                        <i data-lucide="qr-code" style="width:4rem;height:4rem;"></i>
                    </div>
                </div>
                <p class="mt-3 text-sm" style="color:var(--p-warning);font-weight:700;display:flex;align-items:center;gap:var(--p-space-2);">
                    <i data-lucide="clock"></i>
                    {{ __('public.portal.expires_in', [], app()->getLocale()) ?: 'Expires in' }}: <span id="countdown">60:00</span>
                </p>
            </div>

            <div class="alert alert-info mt-6" style="text-align:left;">
                <i data-lucide="info"></i>
                <div>{{ __('public.portal.qr_audit_notice', [], app()->getLocale()) ?: 'Each QR scan is audited. You can view access history in your Access Logs.' }}</div>
            </div>
        </div>
    </div>

    <!-- Right: Profile prompt + Disclaimer -->
    <div style="display:flex;flex-direction:column;gap:var(--p-space-5);">

        <!-- Profile prompt -->
        <div class="panel">
            <div class="panel-body" style="text-align:center;">
                <p class="text-sm text-muted mb-3">{{ __('public.portal.profile_prompt_desc', [], app()->getLocale()) ?: 'Manage your privacy preferences, contact details and emergency contacts in your profile.' }}</p>
                <a href="{{ route('portals.patient.profile') }}" class="btn btn-primary btn-sm">
                    <i data-lucide="user-cog"></i> {{ __('public.portal.profile_prompt_btn', [], app()->getLocale()) ?: 'Go to Profile' }}
                </a>
            </div>
        </div>

        <!-- Clinical Safety Disclaimer -->
        <div class="panel">
            <div class="panel-body">
                <div class="alert alert-warning">
                    <i data-lucide="alert-triangle"></i>
                    <div>
                        {{ __('onboarding.brand.clinical_disclaimer', [], app()->getLocale()) ?: 'OpesCare facilitates access to your health records but is not a substitute for clinical advice. Always consult a licensed healthcare provider for medical decisions.' }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@else
<!-- No patient profile -->
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon">
            <i data-lucide="alert-circle"></i>
        </div>
        <h3>{{ __('public.portal.no_profile_title', [], app()->getLocale()) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_desc', [], app()->getLocale()) ?: 'Your patient profile could not be loaded. Please contact support if this problem persists.' }}</p>
        <a href="{{ route('public.help') }}" class="btn btn-primary">
            <i data-lucide="help-circle"></i>
            {{ __('public.portal.nav_help', [], app()->getLocale()) ?: 'Get Help' }}
        </a>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var lblGenerating   = @json(__('public.portal.generating', [], app()->getLocale()) ?: 'Generating…');
    var lblRegenerateQr = @json(__('public.portal.regenerate_qr', [], app()->getLocale()) ?: 'Regenerate QR');
    var lblQrExpired    = @json(__('public.portal.lbl_qr_expired', [], app()->getLocale()) ?: 'Expired');
    var countdownInterval = null;

    var btnGen = document.getElementById('generate-temp-qr');
    if (btnGen) {
        btnGen.addEventListener('click', async function () {
            btnGen.disabled = true;
            btnGen.innerHTML = '<i data-lucide="loader" style="width:1rem;height:1rem;"></i> ' + lblGenerating;
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                var response = await fetch("{{ route('portals.patient.qr') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (!response.ok) throw new Error('QR generation failed (' + response.status + ')');
                var data = await response.json();
                if (data.qr_image) {
                    var img         = document.getElementById('temp-qr-img');
                    var placeholder = document.getElementById('temp-qr-placeholder');
                    var container   = document.getElementById('temp-qr-container');
                    img.src         = data.qr_image;
                    img.style.display       = 'block';
                    placeholder.style.display = 'none';
                    container.style.display = 'flex';
                    startCountdown(data.expires_in || 3600);
                }
            } catch (e) {
                console.error('Temp QR error:', e);
            } finally {
                btnGen.disabled = false;
                btnGen.innerHTML = '<i data-lucide="refresh-cw" style="width:1rem;height:1rem;"></i> ' + lblRegenerateQr;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

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
});
</script>
@endsection
