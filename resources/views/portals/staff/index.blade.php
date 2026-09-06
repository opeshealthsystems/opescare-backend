@extends('layouts.portal')

@section('title', __('public.staff_portal.dashboard_title', [], app()->getLocale()) ?: 'Staff Clinical Portal')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.staff_portal.dashboard_title', [], app()->getLocale()) ?: 'Staff Clinical Portal' }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('public.staff_portal.dashboard_subtitle', [], app()->getLocale()) ?: 'Manage appointments, queues, and patient care from one place.' }}</p>

{{-- KPI Cards --}}
<div class="stat-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="calendar-check-2"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_todays_appointments', [], app()->getLocale()) ?: "Today's Appointments" }}</div>
        <div class="stat-card__value">{{ $kpis['todays_appointments'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_scheduled_today', [], app()->getLocale()) ?: 'Scheduled today' }}</div>
    </div>
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="list-ordered"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_patient_queue', [], app()->getLocale()) ?: 'Patient Queue' }}</div>
        <div class="stat-card__value">{{ $kpis['in_queue'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_currently_waiting', [], app()->getLocale()) ?: 'Currently waiting' }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="send"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_pending_referrals', [], app()->getLocale()) ?: 'Pending Referrals' }}</div>
        <div class="stat-card__value">{{ $kpis['pending_referrals'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_awaiting_action', [], app()->getLocale()) ?: 'Awaiting action' }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="receipt"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_open_invoices', [], app()->getLocale()) ?: 'Open Invoices' }}</div>
        <div class="stat-card__value">{{ $kpis['open_invoices'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_unpaid_balance', [], app()->getLocale()) ?: 'Unpaid balance' }}</div>
    </div>
</div>

<div class="grid-main-side mt-6">

    {{-- Patient Verification Form — `identify_patient` (config/staff_capabilities.php).
         /facility is the landing page for every facility role, including the
         billing and data-quality roles that do NOT hold identify_patient; they
         land here and are simply not offered a Health-ID lookup. The lookup is
         by exact Health ID only — never a name search. --}}
    @capability('identify_patient')
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="shield-check"></i>
                {{ __('public.staff_portal.patient_verification', [], app()->getLocale()) ?: 'Patient Verification' }}
            </h2>
        </div>
        <div class="panel-body">
            <p class="form-hint mb-4">
                {{ __('public.staff_portal.verification_desc', [], app()->getLocale()) ?: 'Enter a patient Health ID to verify their identity before providing care.' }}
            </p>
            <form method="GET" action="{{ route('portals.staff') }}" autocomplete="off">
                <div class="form-group">
                    <label class="form-label form-label-required" for="health_id">
                        {{ __('public.medical_id.health_id', [], app()->getLocale()) ?: 'Health ID' }}
                    </label>
                    <div class="form-search">
                        <span class="search-icon">
                            <i data-lucide="search"></i>
                        </span>
                        <input
                            type="text"
                            id="health_id"
                            name="health_id"
                            class="form-control mono"
                            placeholder="e.g. CM-HID-7KQ9-MP42-X8D1"
                            value="{{ request('health_id') }}"
                        >
                    </div>
                    <span class="form-hint">{{ __('public.staff_portal.health_id_hint', [], app()->getLocale()) ?: 'Enter the full Health ID as printed on the patient card.' }}</span>
                </div>
                {{-- No facility field: the access is logged against the session's
                     active facility (RequireFacilityContext), never a typed id. --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="purpose">
                            {{ __('public.staff_portal.purpose', [], app()->getLocale()) ?: 'Access Purpose' }}
                        </label>
                        <select id="purpose" name="purpose" class="form-control">
                            <option value="treatment" @selected(request('purpose') === 'treatment')>{{ __('public.staff_portal.purpose_treatment', [], app()->getLocale()) ?: 'Treatment' }}</option>
                            <option value="pharmacy_dispense" @selected(request('purpose') === 'pharmacy_dispense')>{{ __('public.staff_portal.purpose_pharmacy', [], app()->getLocale()) ?: 'Pharmacy Dispense' }}</option>
                            <option value="lab_order" @selected(request('purpose') === 'lab_order')>{{ __('public.staff_portal.purpose_lab', [], app()->getLocale()) ?: 'Lab Order' }}</option>
                            <option value="insurance_claim" @selected(request('purpose') === 'insurance_claim')>{{ __('public.staff_portal.purpose_insurance', [], app()->getLocale()) ?: 'Insurance Claim' }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        {{ __('public.medical_id.verify_health_id', [], app()->getLocale()) ?: 'Verify Patient' }}
                    </button>
                </div>
            </form>

            @isset($verification)
                @if($verification['patient'])
                    @php
                        $vp = $verification['patient'];
                        $vStatus = $vp->verification_status instanceof \BackedEnum ? $vp->verification_status->value : $vp->verification_status;
                    @endphp
                    <div class="alert alert-success mt-4" role="status">
                        <i data-lucide="user-check"></i>
                        <div>
                            <strong>{{ $vp->first_name }} {{ $vp->last_name }}</strong>
                            <span class="badge badge-info">@enum($vStatus ?? 'active', 'verification')</span>
                            <div class="td-muted mt-1">
                                <span class="mono">{{ $vp->health_id }}</span>
                                @if($vp->date_of_birth) · {{ \Carbon\Carbon::parse($vp->date_of_birth)->format('d M Y') }}@endif
                                @if($vp->sex) · @enum($vp->sex)@endif
                            </div>
                            <div class="form-hint mt-1">{{ __('public.staff_portal.verification_logged', [], app()->getLocale()) ?: 'Identity confirmed — this access has been logged and audited.' }}</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger mt-4" role="alert">
                        <i data-lucide="user-x"></i>
                        <div>{{ __('public.staff_portal.verification_not_found', [], app()->getLocale()) ?: 'No patient found for that Health ID. Check the ID and try again.' }} <span class="mono">{{ $verification['health_id'] }}</span></div>
                    </div>
                @endif
            @endisset
        </div>
    </div>
    @endcapability

    {{-- Quick Links — one shortcut per web surface the signed-in role holds.
         Every link is gated by the SAME capability as its route (routes/web.php),
         through @capability — never by a hand-written role list: a role gets a
         shortcut by being added to config/staff_capabilities.php, once. The
         panel itself only renders when at least one surface applies. --}}
    @capability('front_desk', 'schedule', 'prescribe', 'blood_inventory', 'messaging')
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="zap"></i>
                {{ __('public.staff_portal.quick_links', [], app()->getLocale()) ?: 'Quick Links' }}
            </h2>
        </div>
        <div class="panel-body">
            <div class="grid-2">
                @capability('front_desk', 'schedule')
                <a href="{{ route('portals.staff.appointments') }}" class="btn btn-secondary">
                    <i data-lucide="calendar-check-2"></i>
                    {{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}
                </a>
                @endcapability

                @capability('prescribe')
                <a href="{{ route('portals.prescriber.index') }}" class="btn btn-secondary">
                    <i data-lucide="file-text"></i>
                    {{ __('prescriber.nav', [], app()->getLocale()) ?: 'Prescriptions' }}
                </a>
                @endcapability

                @capability('blood_inventory')
                <a href="{{ route('portals.staff.inventory.blood') }}" class="btn btn-secondary">
                    <i data-lucide="droplet"></i>
                    {{ __('public.portal.nav_blood_inventory', [], app()->getLocale()) ?: 'Blood Inventory' }}
                </a>
                @endcapability

                @capability('messaging')
                <a href="{{ route('portals.staff.messages') }}" class="btn btn-secondary">
                    <i data-lucide="messages-square"></i>
                    {{ __('public.portal.nav_messages', [], app()->getLocale()) ?: 'Messages' }}
                </a>
                @endcapability
            </div>
        </div>
    </div>
    @endcapability

</div>

{{-- Clinical Safety Disclaimer --}}
<div class="alert alert-warning mt-6">
    <i data-lucide="triangle-alert"></i>
    <div>
        <strong>{{ __('public.staff_portal.disclaimer_title', [], app()->getLocale()) ?: 'Clinical Safety Disclaimer' }}</strong>
        {{ __('public.staff_portal.disclaimer_text', [], app()->getLocale()) ?: 'This portal is for authorised clinical staff only. All access is logged and audited. Do not share login credentials. Patient data must be handled in accordance with applicable data protection regulations and facility privacy policies. In an emergency, use the break-glass access procedure and document the clinical justification.' }}
    </div>
</div>

@endsection
