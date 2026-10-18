@extends('layouts.portal')

@section('title', __('public.staff_portal.dashboard_title', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.staff_portal.dashboard_title', [], app()->getLocale()) }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('public.staff_portal.dashboard_subtitle', [], app()->getLocale()) }}</p>

{{-- KPI Cards --}}
<div class="stat-grid">
    {{-- The door to today's appointment book for roles holding `front_desk`
         or `schedule` (the route's gate); a plain figure for anyone else. --}}
    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments', ['date' => now()->toDateString()]) }}" class="stat-card stat-card--primary">
    @else
    <div class="stat-card stat-card--primary">
    @endcapability
        <div class="stat-card__head"><i data-lucide="calendar-check-2"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_todays_appointments', [], app()->getLocale()) }}</div>
        <div class="stat-card__value">{{ $kpis['todays_appointments'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_scheduled_today', [], app()->getLocale()) }}</div>
    @capability('front_desk', 'schedule')
    </a>
    @else
    </div>
    @endcapability
    {{-- The tile is the door to the queue desk for roles that hold the
         `queue` capability (the same gate as the route); for anyone else it
         stays a plain figure rather than a link that would 403. --}}
    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="stat-card stat-card--teal">
    @else
    <div class="stat-card stat-card--teal">
    @endcapability
        <div class="stat-card__head"><i data-lucide="list-ordered"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_patient_queue', [], app()->getLocale()) }}</div>
        <div class="stat-card__value">{{ $kpis['in_queue'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_currently_waiting', [], app()->getLocale()) }}</div>
    @capability('queue')
    </a>
    @else
    </div>
    @endcapability
    {{-- The door to the referral desk's received tab for roles holding
         `clinical_chart` (the route's gate); a plain figure for anyone else. --}}
    @capability('clinical_chart')
    <a href="{{ route('portals.referrals.index', ['tab' => 'received']) }}" class="stat-card stat-card--warning">
    @else
    <div class="stat-card stat-card--warning">
    @endcapability
        <div class="stat-card__head"><i data-lucide="send"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_pending_referrals', [], app()->getLocale()) }}</div>
        <div class="stat-card__value">{{ $kpis['pending_referrals'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_awaiting_action', [], app()->getLocale()) }}</div>
    @capability('clinical_chart')
    </a>
    @else
    </div>
    @endcapability
    {{-- The door to the billing desk for roles holding `billing`; a plain
         figure for the clinicians who see the number but do not bill. --}}
    @capability('billing')
    <a href="{{ route('portals.billing.index') }}" class="stat-card stat-card--primary">
    @else
    <div class="stat-card stat-card--primary">
    @endcapability
        <div class="stat-card__head"><i data-lucide="receipt"></i></div>
        <div class="stat-card__label">{{ __('public.staff_portal.kpi_open_invoices', [], app()->getLocale()) }}</div>
        <div class="stat-card__value">{{ $kpis['open_invoices'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('public.staff_portal.kpi_unpaid_balance', [], app()->getLocale()) }}</div>
    @capability('billing')
    </a>
    @else
    </div>
    @endcapability
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
                {{ __('public.staff_portal.patient_verification', [], app()->getLocale()) }}
            </h2>
        </div>
        <div class="panel-body">
            <p class="form-hint mb-4">
                {{ __('public.staff_portal.verification_desc', [], app()->getLocale()) }}
            </p>
            <form method="GET" action="{{ route('portals.staff') }}" autocomplete="off">
                <div class="form-group">
                    <label class="form-label form-label-required" for="health_id">
                        {{ __('public.medical_id.health_id', [], app()->getLocale()) }}
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
                            placeholder="{{ __('staff.desk.health_id_placeholder') }}"
                            value="{{ request('health_id') }}"
                        >
                    </div>
                    <span class="form-hint">{{ __('public.staff_portal.health_id_hint', [], app()->getLocale()) }}</span>
                </div>
                {{-- No facility field: the access is logged against the session's
                     active facility (RequireFacilityContext), never a typed id. --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="purpose">
                            {{ __('public.staff_portal.purpose', [], app()->getLocale()) }}
                        </label>
                        <select id="purpose" name="purpose" class="form-control">
                            <option value="treatment" @selected(request('purpose') === 'treatment')>{{ __('public.staff_portal.purpose_treatment', [], app()->getLocale()) }}</option>
                            <option value="pharmacy_dispense" @selected(request('purpose') === 'pharmacy_dispense')>{{ __('public.staff_portal.purpose_pharmacy', [], app()->getLocale()) }}</option>
                            <option value="lab_order" @selected(request('purpose') === 'lab_order')>{{ __('public.staff_portal.purpose_lab', [], app()->getLocale()) }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        {{ __('public.medical_id.verify_health_id', [], app()->getLocale()) }}
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
                                @if($vp->date_of_birth) · {{ \Carbon\Carbon::parse($vp->date_of_birth)->translatedFormat('d M Y') }}@endif
                                @if($vp->sex) · @enum($vp->sex)@endif
                            </div>
                            <div class="form-hint mt-1">{{ __('public.staff_portal.verification_logged', [], app()->getLocale()) }}</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger mt-4" role="alert">
                        <i data-lucide="user-x"></i>
                        <div>{{ __('public.staff_portal.verification_not_found', [], app()->getLocale()) }} <span class="mono">{{ $verification['health_id'] }}</span></div>
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
                {{ __('public.staff_portal.quick_links', [], app()->getLocale()) }}
            </h2>
        </div>
        <div class="panel-body">
            <div class="grid-2">
                @capability('front_desk', 'schedule')
                <a href="{{ route('portals.staff.appointments') }}" class="btn btn-secondary">
                    <i data-lucide="calendar-check-2"></i>
                    {{ __('public.portal.nav_appointments', [], app()->getLocale()) }}
                </a>
                @endcapability

                @capability('prescribe')
                <a href="{{ route('portals.prescriber.index') }}" class="btn btn-secondary">
                    <i data-lucide="file-text"></i>
                    {{ __('prescriber.nav', [], app()->getLocale()) }}
                </a>
                @endcapability

                @capability('blood_inventory')
                <a href="{{ route('portals.staff.inventory.blood') }}" class="btn btn-secondary">
                    <i data-lucide="droplet"></i>
                    {{ __('public.portal.nav_blood_inventory', [], app()->getLocale()) }}
                </a>
                @endcapability

                @capability('messaging')
                <a href="{{ route('portals.staff.messages') }}" class="btn btn-secondary">
                    <i data-lucide="messages-square"></i>
                    {{ __('public.portal.nav_messages', [], app()->getLocale()) }}
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
        <strong>{{ __('public.staff_portal.disclaimer_title', [], app()->getLocale()) }}</strong>
        {{ __('public.staff_portal.disclaimer_text', [], app()->getLocale()) }}
    </div>
</div>

@endsection
