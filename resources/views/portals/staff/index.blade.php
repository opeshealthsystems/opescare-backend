@extends('layouts.portal')

@section('title', __('public.staff_portal.dashboard_title', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    $l  = app()->getLocale();
    $me = auth()->user();
    // Avatar initials: first letter of each name part, never anything else.
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    // Fixed six-colour rotation by name hash (contract): never purple.
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    $queueToday        = $queueToday ?? collect();
    $appointmentsToday = $appointmentsToday ?? collect();
    $urgentWaiting     = (int) ($urgentWaiting ?? 0);
    $qPill = fn (string $status): string => match ($status) {
        'waiting'         => 'pending',
        'called'          => 'verifying',
        'service_started' => 'counselling',
        default           => 'neutral',
    };
    $rankPill = fn (int $rank): string => match (true) {
        $rank <= 1  => 'urgent',
        $rank === 2 => 'pending',
        $rank === 3 => 'normal',
        default     => 'neutral',
    };
    $apptPill = fn (string $status): string => match ($status) {
        'confirmed', 'checked_in', 'completed' => 'ready',
        'scheduled', 'booked', 'pending'       => 'pending',
        'cancelled', 'no_show'                 => 'urgent',
        default                                => 'neutral',
    };
    $arrival = __('queue_desk.arrival_levels');
@endphp

@section('content')
<div class="od-page odh">

@if(session('success'))<div class="odh-flash odh-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="odh-flash odh-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif

{{-- ── Welcome + the four figures (every figure from the controller) ───── --}}
<section class="odh-top">
    <div class="od-card od-greeting odh-welcome">
        <p class="od-greeting__hello">{{ __('hospital_desk.dash_welcome', [], $l) }}</p>
        <h1 class="od-greeting__name">{{ $me?->name }}</h1>
        <p class="od-greeting__meta"><i data-lucide="calendar-days"></i> {{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    @capability('front_desk', 'schedule')
    <a href="{{ route('portals.staff.appointments', ['date' => now()->toDateString()]) }}" class="od-kpi od-tint--blue">
    @else
    <div class="od-kpi od-tint--blue">
    @endcapability
        <span class="od-kpi__icon"><i data-lucide="calendar-check-2"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.staff_portal.kpi_todays_appointments', [], $l) }}</span>
            <span class="od-kpi__value">{{ $kpis['todays_appointments'] ?? 0 }}</span>
            <span class="od-kpi__sub">{{ __('public.staff_portal.kpi_scheduled_today', [], $l) }}</span>
        </span>
    @capability('front_desk', 'schedule')
    </a>
    @else
    </div>
    @endcapability

    @capability('queue')
    <a href="{{ route('portals.queue.index') }}" class="od-kpi od-tint--teal">
    @else
    <div class="od-kpi od-tint--teal">
    @endcapability
        <span class="od-kpi__icon"><i data-lucide="users"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('hospital_desk.dash_kpi_queue', [], $l) }}</span>
            <span class="od-kpi__value">{{ $kpis['in_queue'] ?? 0 }}</span>
            <span class="od-kpi__sub {{ $urgentWaiting > 0 ? 'od-kpi__sub--alert' : '' }}">{{ $urgentWaiting > 0 ? __('hospital_desk.dash_kpi_queue_urgent', ['count' => $urgentWaiting], $l) : __('public.staff_portal.kpi_currently_waiting', [], $l) }}</span>
        </span>
    @capability('queue')
    </a>
    @else
    </div>
    @endcapability

    @capability('clinical_chart')
    <a href="{{ route('portals.referrals.index', ['tab' => 'received']) }}" class="od-kpi od-tint--amber">
    @else
    <div class="od-kpi od-tint--amber">
    @endcapability
        <span class="od-kpi__icon"><i data-lucide="send"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.staff_portal.kpi_pending_referrals', [], $l) }}</span>
            <span class="od-kpi__value">{{ $kpis['pending_referrals'] ?? 0 }}</span>
            <span class="od-kpi__sub">{{ __('public.staff_portal.kpi_awaiting_action', [], $l) }}</span>
        </span>
    @capability('clinical_chart')
    </a>
    @else
    </div>
    @endcapability

    @capability('prescribe')
    <a href="{{ route('portals.prescriber.index') }}" class="od-kpi od-tint--green">
    @else
    <div class="od-kpi od-tint--green">
    @endcapability
        <span class="od-kpi__icon"><x-od-icon name="rx" /></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('hospital_desk.dash_kpi_rx', [], $l) }}</span>
            <span class="od-kpi__value">{{ $kpis['rx_today'] ?? 0 }}</span>
            <span class="od-kpi__sub">{{ __('hospital_desk.dash_kpi_rx_sub', [], $l) }}</span>
        </span>
    @capability('prescribe')
    </a>
    @else
    </div>
    @endcapability
</section>

{{-- ── Quick actions — every tile is a real route behind its own gate ──── --}}
@capability('front_desk', 'schedule', 'queue', 'triage', 'prescribe', 'clinical_chart')
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('hospital_desk.dash_quick', [], $l) }}</h2></div>
    <div class="od-card__body">
        <div class="od-quick">
            @capability('front_desk', 'schedule')
            <a class="od-quick__tile" href="{{ route('portals.staff.appointments.create') }}"><i data-lucide="calendar-plus"></i><span class="od-quick__label">{{ __('hospital_desk.dash_qa_book', [], $l) }}</span></a>
            @endcapability
            @capability('queue')
            <a class="od-quick__tile" href="{{ route('portals.queue.create') }}"><i data-lucide="user-check"></i><span class="od-quick__label">{{ __('hospital_desk.dash_qa_checkin', [], $l) }}</span></a>
            @endcapability
            @capability('triage')
            <a class="od-quick__tile" href="{{ route('portals.triage.create') }}"><i data-lucide="activity"></i><span class="od-quick__label">{{ __('hospital_desk.dash_qa_triage', [], $l) }}</span></a>
            @endcapability
            @capability('prescribe')
            <a class="od-quick__tile" href="{{ route('portals.prescriber.create') }}"><x-od-icon name="rx" class="od-quick__glyph" /><span class="od-quick__label">{{ __('hospital_desk.dash_qa_rx', [], $l) }}</span></a>
            @endcapability
            @capability('clinical_chart')
            <a class="od-quick__tile" href="{{ route('portals.referrals.create') }}"><i data-lucide="send"></i><span class="od-quick__label">{{ __('hospital_desk.dash_qa_referral', [], $l) }}</span></a>
            <a class="od-quick__tile" href="{{ route('portals.chart.index') }}"><i data-lucide="search"></i><span class="od-quick__label">{{ __('hospital_desk.dash_qa_search', [], $l) }}</span></a>
            @endcapability
        </div>
    </div>
</section>
@endcapability

<div class="odh-grid">

    {{-- ── Today's queue: the queue desk's own ranked order ─────────────── --}}
    @capability('queue')
    <section class="od-card odh-queue" aria-labelledby="odh-queue-title">
        <header class="od-card__head">
            <h2 class="od-card__title" id="odh-queue-title">{{ __('hospital_desk.dash_queue_title', [], $l) }} <span class="od-card__count">({{ $kpis['in_queue'] ?? 0 }})</span></h2>
            <a href="{{ route('portals.queue.index') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
        </header>
        @if($queueToday->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon od-tint--teal"><i data-lucide="armchair"></i></span>
                <div class="od-empty__title">{{ __('hospital_desk.dash_queue_empty', [], $l) }}</div>
                <p class="od-empty__text">{{ __('queue_desk.empty_queue', [], $l) }}</p>
                <a href="{{ route('portals.queue.create') }}" class="od-btn od-btn--primary"><i data-lucide="user-check"></i> {{ __('hospital_desk.empty_queue_action', [], $l) }}</a>
            </div>
        @else
        <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('public.portal.col_patient', [], $l) }}</th>
                    <th>{{ __('hospital_desk.dash_col_arrived', [], $l) }}</th>
                    <th>{{ __('hospital_desk.dash_col_priority', [], $l) }}</th>
                    <th>{{ __('hospital_desk.dash_col_status', [], $l) }}</th>
                    <th class="od-table__actions"><span class="sr-only">{{ __('hospital_desk.dash_col_action', [], $l) }}</span></th>
                </tr></thead>
                <tbody>
                @foreach($queueToday as $t)
                    @php
                        $p     = $t->patient;
                        $name  = $p ? trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) : '—';
                        $rank  = (int) ($t->priority_rank ?? $t->priority_level ?? 6);
                        $tKey  = \App\Support\TriageAcuity::labelKey($t->triage_priority ?? null);
                        $st    = (string) $t->status;
                        $age   = $p?->date_of_birth ? \App\Support\ResultFlagResolver::ageBand($p->date_of_birth) : null;
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $t->queue_number ?? $loop->iteration }}</td>
                        <td>
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($name) }}">{{ $initials($p?->first_name, $p?->last_name) }}</span>
                                <span><span class="od-table__name">{{ $name }}</span><span class="od-table__sub">{{ $p?->health_id }}@if($age) · {{ __('chart.age_bands.' . $age, [], $l) }}@endif</span></span>
                            </div>
                        </td>
                        <td>{{ $t->checked_in_at?->format('H:i') }}</td>
                        <td><span class="od-pill od-pill--{{ $rankPill($rank) }}">{{ $tKey ? __($tKey) : (is_array($arrival) ? ($arrival[(int) $t->priority_level] ?? $t->priority_level) : $t->priority_level) }}</span></td>
                        <td><span class="od-pill od-pill--dot od-pill--{{ $qPill($st) }}">{{ __('queue_desk.status.' . $st, [], $l) }}</span></td>
                        <td class="od-table__actions"><a href="{{ route('portals.queue.show', $t->id) }}" class="od-btn od-btn--row">{{ __('hospital_desk.dash_open', [], $l) }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        </div>
        @endif
    </section>
    @endcapability

    {{-- ── Alerts: derived from the same counts, links to the desk that acts ── --}}
    <section class="od-card odh-alerts" aria-labelledby="odh-alerts-title">
        <header class="od-card__head"><h2 class="od-card__title" id="odh-alerts-title">{{ __('hospital_desk.dash_alerts', [], $l) }}</h2></header>
        <div class="od-card__body">
        <ul class="od-list">
            @if($urgentWaiting > 0)
            <li class="od-list__row">
                <span class="od-list__icon od-tint--rose"><i data-lucide="siren"></i></span>
                <div class="od-list__text">
                    @capability('queue')<a href="{{ route('portals.queue.index') }}" class="od-list__title">{{ __('hospital_desk.dash_alert_urgent', [], $l) }}</a>@else<span class="od-list__title">{{ __('hospital_desk.dash_alert_urgent', [], $l) }}</span>@endcapability
                    <span class="od-list__sub">{{ __('hospital_desk.dash_alert_urgent_sub', [], $l) }}</span>
                </div>
                <span class="od-pill od-pill--urgent">{{ $urgentWaiting }}</span>
            </li>
            @endif
            @if(($kpis['pending_referrals'] ?? 0) > 0)
            <li class="od-list__row">
                <span class="od-list__icon od-tint--amber"><i data-lucide="send"></i></span>
                <div class="od-list__text">
                    @capability('clinical_chart')<a href="{{ route('portals.referrals.index') }}" class="od-list__title">{{ __('public.staff_portal.kpi_pending_referrals', [], $l) }}</a>@else<span class="od-list__title">{{ __('public.staff_portal.kpi_pending_referrals', [], $l) }}</span>@endcapability
                    <span class="od-list__sub">{{ __('public.staff_portal.kpi_awaiting_action', [], $l) }}</span>
                </div>
                <span class="od-pill od-pill--pending">{{ $kpis['pending_referrals'] }}</span>
            </li>
            @endif
            @if(($kpis['open_invoices'] ?? 0) > 0)
            <li class="od-list__row">
                <span class="od-list__icon od-tint--blue"><i data-lucide="receipt"></i></span>
                <div class="od-list__text">
                    @capability('billing')<a href="{{ route('portals.billing.index') }}" class="od-list__title">{{ __('public.staff_portal.kpi_open_invoices', [], $l) }}</a>@else<span class="od-list__title">{{ __('public.staff_portal.kpi_open_invoices', [], $l) }}</span>@endcapability
                    <span class="od-list__sub">{{ __('public.staff_portal.kpi_unpaid_balance', [], $l) }}</span>
                </div>
                <span class="od-pill od-pill--normal">{{ $kpis['open_invoices'] }}</span>
            </li>
            @endif
            <li class="od-list__row">
                <span class="od-list__icon od-tint--sky"><i data-lucide="shield-check"></i></span>
                <div class="od-list__text">
                    <span class="od-list__title">{{ __('public.staff_portal.disclaimer_title', [], $l) }}</span>
                    <span class="od-list__sub">{{ __('public.staff_portal.disclaimer_text', [], $l) }}</span>
                </div>
            </li>
        </ul>
        </div>
    </section>

    {{-- ── Today's appointments ──────────────────────────────────────────── --}}
    @capability('front_desk', 'schedule')
    <section class="od-card odh-appts" aria-labelledby="odh-appts-title">
        <header class="od-card__head">
            <h2 class="od-card__title" id="odh-appts-title">{{ __('hospital_desk.dash_appts_title', [], $l) }} <span class="od-card__count">({{ $kpis['todays_appointments'] ?? 0 }})</span></h2>
            <a href="{{ route('portals.staff.appointments', ['date' => now()->toDateString()]) }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
        </header>
        @if($appointmentsToday->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon od-tint--blue"><i data-lucide="calendar-x-2"></i></span>
                <div class="od-empty__title">{{ __('hospital_desk.dash_appts_empty', [], $l) }}</div>
                <p class="od-empty__text">{{ __('hospital_desk.dash_appts_empty_text', [], $l) }}</p>
                <a href="{{ route('portals.staff.appointments.create') }}" class="od-btn od-btn--primary"><i data-lucide="calendar-plus"></i> {{ __('hospital_desk.dash_qa_book', [], $l) }}</a>
            </div>
        @else
        <div class="od-card__body">
        <ul class="od-list">
            @foreach($appointmentsToday as $a)
                @php
                    $ap = $a->patient;
                    $aName = $ap ? trim(($ap->first_name ?? '') . ' ' . ($ap->last_name ?? '')) : '—';
                    $aSt = $a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status;
                @endphp
                <li class="od-list__row">
                    <span class="od-avatar od-avatar--sm od-tint--{{ $tone($aName) }}">{{ $initials($ap?->first_name, $ap?->last_name) }}</span>
                    <div class="od-list__text">
                        <span class="od-list__title">{{ $aName }}</span>
                        <span class="od-list__sub">{{ $a->provider?->name ?? '—' }}@if($a->appointment_type) · @enum($a->appointment_type)@endif</span>
                    </div>
                    <span class="od-list__value">{{ $a->scheduled_at?->format('H:i') }}</span>
                    <span class="od-pill od-pill--{{ $apptPill($aSt) }}">@enum($aSt)</span>
                </li>
            @endforeach
        </ul>
        </div>
        @endif
    </section>
    @endcapability

    {{-- ── Patient verification — exact Health ID only, access is logged ── --}}
    @capability('identify_patient')
    <section class="od-card odh-verify" aria-labelledby="odh-verify-title">
        <header class="od-card__head"><h2 class="od-card__title" id="odh-verify-title">{{ __('public.staff_portal.patient_verification', [], $l) }}</h2></header>
        <div class="od-card__body">
            <p class="odh-hint">{{ __('public.staff_portal.verification_desc', [], $l) }}</p>
            <form method="GET" action="{{ route('portals.staff') }}" autocomplete="off" class="odh-form">
                <div class="odh-field">
                    <label class="odh-label" for="health_id">{{ __('public.medical_id.health_id', [], $l) }}</label>
                    <label class="od-field">
                        <i data-lucide="search"></i>
                        <input type="text" id="health_id" name="health_id" class="od-input mono" placeholder="{{ __('staff.desk.health_id_placeholder') }}" value="{{ request('health_id') }}">
                    </label>
                    <span class="odh-hint">{{ __('public.staff_portal.health_id_hint', [], $l) }}</span>
                </div>
                {{-- No facility field: the access is logged against the session's
                     active facility (RequireFacilityContext), never a typed id. --}}
                <div class="odh-field">
                    <label class="odh-label" for="purpose">{{ __('public.staff_portal.purpose', [], $l) }}</label>
                    <select id="purpose" name="purpose" class="od-select">
                        <option value="treatment" @selected(request('purpose') === 'treatment')>{{ __('public.staff_portal.purpose_treatment', [], $l) }}</option>
                        <option value="pharmacy_dispense" @selected(request('purpose') === 'pharmacy_dispense')>{{ __('public.staff_portal.purpose_pharmacy', [], $l) }}</option>
                        <option value="lab_order" @selected(request('purpose') === 'lab_order')>{{ __('public.staff_portal.purpose_lab', [], $l) }}</option>
                    </select>
                </div>
                <div class="odh-actions">
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> {{ __('public.medical_id.verify_health_id', [], $l) }}</button>
                </div>
            </form>

            @isset($verification)
                @if($verification['patient'])
                    @php
                        $vp = $verification['patient'];
                        $vStatus = $vp->verification_status instanceof \BackedEnum ? $vp->verification_status->value : $vp->verification_status;
                        $vName = trim($vp->first_name . ' ' . $vp->last_name);
                    @endphp
                    <div class="odh-result" role="status">
                        <span class="od-avatar od-tint--{{ $tone($vName) }}">{{ $initials($vp->first_name, $vp->last_name) }}</span>
                        <div class="od-list__text">
                            <span class="od-list__title">{{ $vName }} <span class="od-pill od-pill--ready">@enum($vStatus ?? 'active', 'verification')</span></span>
                            <span class="od-list__sub"><span class="mono">{{ $vp->health_id }}</span>@if($vp->date_of_birth) · {{ __('chart.age_bands.' . \App\Support\ResultFlagResolver::ageBand($vp->date_of_birth), [], $l) }}@endif @if($vp->sex) · @enum($vp->sex)@endif</span>
                            <span class="od-list__sub">{{ __('public.staff_portal.verification_logged', [], $l) }}</span>
                        </div>
                    </div>
                @else
                    <div class="odh-result odh-result--miss" role="alert">
                        <span class="od-list__icon od-tint--rose"><i data-lucide="user-x"></i></span>
                        <span>{{ __('public.staff_portal.verification_not_found', [], $l) }} <span class="mono">{{ $verification['health_id'] }}</span></span>
                    </div>
                @endif
            @endisset
        </div>
    </section>
    @endcapability

</div>
</div>
@endsection
