@extends('layouts.portal')

@section('title', __('lab_manager.dashboard_title'))

@section('head')
    @include('portals.lab.partials._head')
@endsection

@php
    // Whole minutes → the shortest honest unit. Nothing is rounded into existence:
    // a null (no data) is printed as the "not enough data" sentence, never as 0.
    $duration = function (?int $minutes): ?string {
        if ($minutes === null) {
            return null;
        }
        if ($minutes < 60) {
            return __('lab_manager.tat_minutes', ['minutes' => $minutes]);
        }
        if ($minutes < 60 * 24) {
            return __('lab_manager.tat_hours', ['hours' => intdiv($minutes, 60), 'minutes' => $minutes % 60]);
        }

        return __('lab_manager.tat_days', ['days' => intdiv($minutes, 60 * 24), 'hours' => intdiv($minutes % (60 * 24), 60)]);
    };

    $me       = auth()->user();
    $roleSlug = $me ? \App\Support\StaffCapabilities::roleModelFor($me, \App\Support\StaffCapabilities::contextFacilityId($me))?->name : null;
    $roleName = $roleSlug && \Illuminate\Support\Facades\Lang::has('portal.role_name.' . $roleSlug)
        ? __('portal.role_name.' . $roleSlug)
        : ($me?->roleName() ?? '');

    $urgencyPill = fn (string $u) => match ($u) { 'stat' => 'od-pill--stat', 'urgent' => 'od-pill--urgent', default => 'od-pill--normal' };
    $statusPill  = fn (string $s) => match ($s) {
        'collected'   => 'od-pill--normal',
        'in_progress' => 'od-pill--pending',
        'resulted', 'validated' => 'od-pill--ready',
        'cancelled'   => 'od-pill--urgent',
        default       => 'od-pill--neutral',
    };
    $webStatus = fn ($o) => strtolower(trim((string) $o->status)) === \App\Services\Lab\LabDeskWebService::VALIDATED_STATUS ? 'validated' : $o->deskStatus();

    // Today's workload donut: the open bench by column plus what waits for a
    // second signature. Every arc is a live count; an empty bench draws a ring.
    $segments = collect([
        ['key' => 'ordered',     'label' => __('lab_desk.status.ordered'),     'n' => (int) ($counts['ordered'] ?? 0),     'tone' => 'amber'],
        ['key' => 'collected',   'label' => __('lab_desk.status.collected'),   'n' => (int) ($counts['collected'] ?? 0),   'tone' => 'blue'],
        ['key' => 'in_progress', 'label' => __('lab_desk.status.in_progress'), 'n' => (int) ($counts['in_progress'] ?? 0), 'tone' => 'teal'],
        ['key' => 'resulted',    'label' => __('lab_desk.status.resulted'),    'n' => (int) $awaiting_validation,           'tone' => 'green'],
    ]);
    $donutTotal = $segments->sum('n');
    $circ       = 2 * M_PI * 64;
    $offset     = 0.0;
    $arcs       = $segments->map(function ($s) use ($donutTotal, $circ, &$offset) {
        $len = $donutTotal > 0 ? $circ * $s['n'] / $donutTotal : 0;
        $arc = $s + ['len' => round($len, 2), 'gap' => round($circ - $len, 2), 'offset' => round(-$offset, 2)];
        $offset += $len;

        return $arc;
    });
@endphp

@section('content')
<div class="od-page odl">

@if(session('success'))<div class="od-flash od-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ $errors->first() }}</span></div>@endif

{{-- ── Welcome + the four figures ───────────────────────────────────────── --}}
<section class="odl-top">
    <div class="od-greeting odl-welcome">
        <p class="od-greeting__hello">{{ __('lab_desk_ui.welcome_back') }}</p>
        <h1 class="od-greeting__name">{{ $me?->name }}</h1>
        <p class="od-greeting__meta">{{ $roleName }}</p>
        @if($facilityName)<p class="od-greeting__meta">{{ $facilityName }}</p>@endif
        <p class="od-greeting__meta"><i data-lucide="calendar-days"></i> {{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    <a href="{{ route('portals.lab.index', ['status' => 'collected']) }}" class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="file-text"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('lab_desk_ui.kpi_received') }}</span>
            <span class="od-kpi__value" data-kpi="received_today">{{ $today['collected'] }}</span>
            <span class="od-kpi__sub">{{ __('lab_desk_ui.today') }}</span>
        </span>
    </a>
    <a href="{{ route('portals.lab.index', ['status' => 'in_progress']) }}" class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="flask-conical"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('lab_desk_ui.kpi_in_progress') }}</span>
            <span class="od-kpi__value" data-kpi="in_progress">{{ $counts['in_progress'] ?? 0 }}</span>
            <span class="od-kpi__sub">{{ __('lab_desk_ui.active') }}</span>
        </span>
    </a>
    <a href="{{ route('portals.lab.index', ['status' => 'validated']) }}" class="od-kpi od-tint--teal">
        <span class="od-kpi__icon"><i data-lucide="badge-check"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('lab_desk_ui.kpi_validated') }}</span>
            <span class="od-kpi__value" data-kpi="validated_today">{{ $today['validated'] }}</span>
            <span class="od-kpi__sub">{{ __('lab_desk_ui.today') }}</span>
        </span>
    </a>
    <a href="{{ route('portals.lab.index', ['status' => 'in_progress']) }}" class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="clock-3"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('lab_desk_ui.kpi_past_tat') }}</span>
            <span class="od-kpi__value" data-kpi="past_tat">{{ $past_tat ?? '—' }}</span>
            <span class="od-kpi__sub">
                @if($past_tat === null)
                    {{ __('lab_desk_ui.past_tat_no_median') }}
                @else
                    {{ __('lab_desk_ui.past_tat_sub', ['median' => $duration($turnaround['median_minutes'])]) }}
                @endif
            </span>
        </span>
    </a>
</section>

{{-- ── First run: a lab that has never received an order or a request ───── --}}
@unless($hasAnyWork)
<section class="od-card odl-firstrun">
    <details class="odl-strip">
        <summary>
            <span class="od-list__icon od-tint--blue"><i data-lucide="compass"></i></span>
            <span class="odl-strip__text">{{ __('lab_manager.first_run_title') }}</span>
            <span class="od-card__link">{{ __('lab_desk_ui.how_work_arrives') }}</span>
        </summary>
        <ol class="odl-steps">
            <li>{{ __('lab_manager.first_run_step_catalogue') }}</li>
            <li>{{ __('lab_manager.first_run_step_requests') }}</li>
            <li>{{ __('lab_manager.first_run_step_walkin') }}</li>
        </ol>
    </details>
</section>
@endunless

{{-- ── Quick actions — real routes only ─────────────────────────────────── --}}
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('lab_desk_ui.quick_actions') }}</h2></div>
    <div class="od-card__body">
    <div class="od-quick od-quick--lab">
        <a class="od-quick__tile" href="{{ route('portals.lab.collection') }}"><i data-lucide="barcode"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_register') }}</span></a>
        <a class="od-quick__tile" href="{{ route('portals.lab.index') }}"><i data-lucide="list-checks"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_worklist') }}</span></a>
        <a class="od-quick__tile" href="{{ route('portals.lab.index', ['status' => 'collected']) }}"><i data-lucide="file-pen-line"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_enter') }}</span></a>
        <a class="od-quick__tile" href="{{ route('portals.lab.index', ['status' => 'resulted']) }}"><i data-lucide="circle-check-big"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_validate') }}</span></a>
        <a class="od-quick__tile" href="{{ route('portals.lab.index', ['status' => 'validated']) }}"><i data-lucide="printer"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_print') }}</span></a>
        <a class="od-quick__tile" href="{{ route('portals.lab_manager.requests') }}"><i data-lucide="inbox"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_requests') }}</span></a>
        @if($canCatalogue)
        <a class="od-quick__tile" href="{{ route('portals.facility.catalogue') }}"><i data-lucide="clipboard-list"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_catalogue') }}</span></a>
        @endif
        <a class="od-quick__tile" href="{{ route('portals.lab.index') }}#lab-health-id"><i data-lucide="search"></i><span class="od-quick__label">{{ __('lab_desk_ui.qa_search') }}</span></a>
    </div>
    </div>
</section>

<div class="odl-grid">

    {{-- ── Sample queue ──────────────────────────────────────────────────── --}}
    <section class="od-card odl-queue" id="sample-queue">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('lab_desk_ui.queue_title') }} <span class="od-card__count">({{ $queue_counts['all'] }})</span></h2>
            <a class="od-card__link" href="{{ route('portals.lab.index') }}">{{ __('lab_desk_ui.view_all') }}</a>
        </div>
    <div class="od-card__body">
        <div class="od-tabs" role="tablist" data-queue-tabs>
            <button type="button" class="od-tab is-active" role="tab" aria-selected="true" data-band="all">{{ __('lab_desk_ui.tab_all') }} ({{ $queue_counts['all'] }})</button>
            <button type="button" class="od-tab" role="tab" aria-selected="false" data-band="routine"><span class="od-tab__dot od-tab__dot--normal"></span>{{ __('lab_desk.urgency.routine') }} ({{ $queue_counts['routine'] }})</button>
            <button type="button" class="od-tab" role="tab" aria-selected="false" data-band="urgent"><span class="od-tab__dot od-tab__dot--urgent"></span>{{ __('lab_desk.urgency.urgent') }} ({{ $queue_counts['urgent'] }})</button>
            <button type="button" class="od-tab" role="tab" aria-selected="false" data-band="stat"><span class="od-tab__dot od-tab__dot--teal"></span>{{ __('lab_desk.urgency.stat') }} ({{ $queue_counts['stat'] }})</button>
        </div>
        @if($queue->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="test-tube-2"></i></span>
                <p class="od-empty__title">{{ __('lab_desk_ui.queue_empty') }}</p>
                <p class="od-empty__text">{{ __('lab_desk_ui.queue_empty_body') }}</p>
                <a class="od-btn od-btn--primary" href="{{ route('portals.lab.collection') }}">{{ __('lab_desk_ui.qa_register') }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('lab_desk_ui.col_time') }}</th>
                        <th>{{ __('lab_desk.col_patient') }}</th>
                        <th>{{ __('lab_desk_ui.col_tests') }}</th>
                        <th>{{ __('lab_desk_ui.col_priority') }}</th>
                        <th>{{ __('lab_desk_ui.col_status') }}</th>
                        <th class="od-table__actions"><span class="od-sr">{{ __('lab_desk.col_open') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($queue as $i => $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $u     = $order->deskUrgency();
                        $st    = $webStatus($order);
                    @endphp
                    <tr data-band="{{ $u }}" @if($i >= 6) hidden @endif>
                        <td class="od-table__num">{{ $i + 1 }}</td>
                        <td class="odl-time">{{ $order->ordered_at?->format('H:i') ?? '—' }}</td>
                        <td>
                            <div class="od-person">
                                @include('portals.lab.partials._avatar', ['name' => $pName])
                                <div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div>
                            </div>
                        </td>
                        <td class="od-muted odl-clip" title="{{ $order->test_name }}">{{ $order->test_name }}</td>
                        <td><span class="od-pill {{ $urgencyPill($u) }} od-pill--dot">{{ __('lab_desk.urgency.' . $u) }}</span></td>
                        <td><span class="od-pill {{ $statusPill($st) }}">{{ __('lab_desk.status.' . $st) }}</span></td>
                        <td class="od-table__actions">
                            <a class="od-kebab" href="{{ route('portals.lab.show', $order->id) }}" aria-label="{{ __('lab_desk.open_order') }}" title="{{ __('lab_desk.open_order') }}"><i data-lucide="more-horizontal"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="odl-queue__none" hidden data-queue-none>{{ __('lab_desk_ui.queue_band_empty') }}</p>
        </div>
        @endif
    </div>
</section>

    {{-- ── Today's workload ──────────────────────────────────────────────── --}}
    <section class="od-card odl-workload">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('lab_desk_ui.workload_title') }}</h2>
            <a class="od-card__link" href="{{ route('portals.lab.index') }}">{{ __('lab_desk_ui.view_all') }}</a>
        </div>
    <div class="od-card__body">
        <div class="od-donut">
            <div class="od-donut__chart">
                <svg class="od-donut__svg" viewBox="0 0 160 160" role="img" aria-label="{{ __('lab_desk_ui.workload_aria', ['total' => $donutTotal]) }}">
                    <circle class="od-donut__track" cx="80" cy="80" r="64"></circle>
                    @foreach($arcs as $arc)
                        @if($arc['n'] > 0)
                        <circle class="od-swatch--{{ $arc['tone'] }}" cx="80" cy="80" r="64"
                                stroke-dasharray="{{ $arc['len'] }} {{ $arc['gap'] }}" stroke-dashoffset="{{ $arc['offset'] }}"></circle>
                        @endif
                    @endforeach
                </svg>
                <div class="od-donut__centre">
                    <span class="od-donut__total">{{ $donutTotal }}</span>
                    <span class="od-donut__caption">{{ __('lab_desk_ui.workload_total') }}</span>
                </div>
            </div>
            <ul class="od-donut__legend">
                @foreach($segments as $s)
                    <li class="od-donut__item"><span class="od-donut__swatch od-swatch--{{ $s['tone'] }}"></span>{{ $s['label'] }}<span class="od-donut__value">{{ $s['n'] }}</span></li>
                @endforeach
            </ul>
        </div>
        <div class="odl-tat">
            <span class="odl-tat__icon"><i data-lucide="timer"></i></span>
            <div>
                <div class="odl-tat__label">{{ __('lab_desk_ui.tat_median', ['days' => $turnaround['window_days']]) }}</div>
                @if($turnaround['released_orders'] === 0)
                    <div class="odl-tat__value">{{ __('lab_desk_ui.tat_none_short') }}</div>
                    <div class="od-muted odl-small">{{ __('lab_manager.tat_none', ['days' => $turnaround['window_days']]) }}</div>
                @else
                    <div class="odl-tat__value">{{ $duration($turnaround['median_minutes']) }}</div>
                @endif
                @if($turnaround['released_orders'] > 0)
                    <div class="od-muted odl-small">{{ __('lab_manager.tat_mean') }} {{ $duration($turnaround['mean_minutes']) }} · {{ __('lab_desk_ui.tat_measured', ['n' => $turnaround['released_orders']]) }}</div>
                @endif
            </div>
        </div>
    </div>
</section>

    {{-- ── Alerts & notifications ────────────────────────────────────────── --}}
    <section class="od-card odl-alerts">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('lab_desk_ui.alerts_title') }}</h2>
        </div>
    <div class="od-card__body">
        @php
            $alertRows = collect([
                ['n' => $critical_open, 'tone' => 'red', 'icon' => 'siren', 'title' => trans_choice('lab_desk_ui.alert_critical', $critical_open, ['n' => $critical_open]), 'sub' => __('lab_desk_ui.alert_critical_sub'), 'href' => '#critical'],
                ['n' => (int) ($past_tat ?? 0), 'tone' => 'amber', 'icon' => 'clock-alert', 'title' => trans_choice('lab_desk_ui.alert_past_tat', (int) ($past_tat ?? 0), ['n' => (int) ($past_tat ?? 0)]), 'sub' => __('lab_desk_ui.alert_past_tat_sub'), 'href' => route('portals.lab.index', ['status' => 'in_progress'])],
                ['n' => $requests_pending, 'tone' => 'blue', 'icon' => 'inbox', 'title' => trans_choice('lab_desk_ui.alert_requests', $requests_pending, ['n' => $requests_pending]), 'sub' => __('lab_manager.kpi_hint_requests'), 'href' => route('portals.lab_manager.requests')],
                ['n' => $awaiting_validation, 'tone' => 'teal', 'icon' => 'shield-check', 'title' => trans_choice('lab_desk_ui.alert_validation', $awaiting_validation, ['n' => $awaiting_validation]), 'sub' => __('lab_desk_ui.alert_validation_sub'), 'href' => route('portals.lab.index', ['status' => 'resulted'])],
                ['n' => $today['rejected'], 'tone' => 'amber', 'icon' => 'ban', 'title' => trans_choice('lab_desk_ui.alert_rejected', $today['rejected'], ['n' => $today['rejected']]), 'sub' => __('lab_manager.kpi_hint_today'), 'href' => null],
            ])->filter(fn ($r) => $r['n'] > 0);
        @endphp
        @if($alertRows->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon od-tint--green"><i data-lucide="check-circle"></i></span>
                <p class="od-empty__title">{{ __('lab_desk_ui.alerts_empty') }}</p>
                <p class="od-empty__text">{{ __('lab_desk_ui.alerts_empty_body') }}</p>
            </div>
        @else
            <ul class="od-list">
                @foreach($alertRows as $row)
                    <li class="od-list__row">
                        <span class="od-list__icon od-list__icon--solid od-tint--{{ $row['tone'] }}"><i data-lucide="{{ $row['icon'] }}"></i></span>
                        <div class="od-list__text">
                            @if($row['href'])<a href="{{ $row['href'] }}" class="od-list__title">{{ $row['title'] }}</a>@else<span class="od-list__title">{{ $row['title'] }}</span>@endif
                            <span class="od-list__sub">{{ $row['sub'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

    {{-- ── Recent results ────────────────────────────────────────────────── --}}
    <section class="od-card odl-recent">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('lab_desk_ui.recent_title') }}</h2>
            <a class="od-card__link" href="{{ route('portals.lab.index', ['status' => 'resulted']) }}">{{ __('lab_desk_ui.view_all') }}</a>
        </div>
    <div class="od-card__body">
        @if($recent->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="file-check-2"></i></span>
                <p class="od-empty__title">{{ __('lab_desk_ui.recent_empty') }}</p>
                <p class="od-empty__text">{{ __('lab_desk_ui.recent_empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_desk_ui.col_time') }}</th>
                        <th>{{ __('lab_desk.col_patient') }}</th>
                        <th>{{ __('lab_desk_ui.col_tests') }}</th>
                        <th>{{ __('lab_desk.col_results') }}</th>
                        <th>{{ __('lab_desk_ui.col_status') }}</th>
                        <th class="od-table__actions"><span class="od-sr">{{ __('lab_desk.col_open') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($recent as $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $st    = $webStatus($order);
                    @endphp
                    <tr>
                        <td class="odl-time">{{ $order->resulted_at?->isToday() ? $order->resulted_at->format('H:i') : $order->resulted_at?->translatedFormat('d M') }}</td>
                        <td>
                            <div class="od-person">
                                @include('portals.lab.partials._avatar', ['name' => $pName])
                                <div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>
                            </div>
                        </td>
                        <td class="od-muted odl-clip" title="{{ $order->test_name }}">{{ $order->test_name }}</td>
                        <td>{{ $order->results_count }}</td>
                        <td><span class="od-pill {{ $statusPill($st) }}">{{ __('lab_desk.status.' . $st) }}</span></td>
                        <td class="od-table__actions">
                            <a class="od-kebab" href="{{ route('portals.lab.show', $order->id) }}" aria-label="{{ __('lab_desk.open_order') }}" title="{{ __('lab_desk.open_order') }}"><i data-lucide="more-horizontal"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

    {{-- ── What the public lab finder shows for this laboratory ─────────── --}}
    <section class="od-card odl-publication" id="publication">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('lab_manager.pub_title') }}</h2>
        </div>
    <div class="od-card__body">
        @if(! $publication['linked'])
            <div class="od-note od-note--amber"><i data-lucide="link-2-off"></i><span>{{ __('lab_manager.pub_unlinked') }}</span></div>
        @else
            @unless($publication['listing_active'])
                <div class="od-note od-note--amber"><i data-lucide="eye-off"></i><span>{{ __('lab_manager.pub_listing_inactive', ['listing' => $publication['listing_name']]) }}</span></div>
            @endunless
            <ul class="od-list">
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--blue"><i data-lucide="building-2"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ $publication['listing_name'] }}</span><span class="od-list__sub">{{ __('lab_manager.pub_listing') }}</span></div>
                </li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--teal"><i data-lucide="globe"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('lab_manager.pub_published') }}</span><span class="od-list__sub">{{ __('lab_manager.pub_last') }}: {{ $publication['last_published_at'] ? $publication['last_published_at']->translatedFormat('d M Y H:i') : __('lab_manager.pub_never') }}</span></div>
                    <strong class="od-list__value">{{ $publication['published'] }}</strong>
                </li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--sky"><i data-lucide="clipboard-list"></i></span>
                    <div class="od-list__text"><span class="od-list__title">{{ __('lab_manager.pub_catalogue_tests') }}</span>@if($publication['catalogue_tests'] === 0)<span class="od-list__sub">{{ __('lab_manager.pub_no_tests') }}</span>@endif</div>
                    <strong class="od-list__value">{{ $publication['catalogue_tests'] }}</strong>
                </li>
            </ul>
        @endif
        <div class="odl-actions">
            @if($canCatalogue)
                <a href="{{ route('portals.facility.catalogue') }}" class="od-btn od-btn--secondary"><i data-lucide="clipboard-list"></i> {{ __('lab_manager.pub_open_catalogue') }}</a>
            @endif
            <a href="{{ route('public.network.lab-test-finder') }}" class="od-btn od-btn--ghost" target="_blank" rel="noopener"><i data-lucide="external-link"></i> {{ __('lab_manager.pub_open_finder') }}</a>
        </div>
    </div>
</section>
</div>

{{-- ── Critical values nobody has acknowledged: the call-back desk ─────── --}}
<section class="od-card" id="critical">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('lab_manager.critical_title') }} <span class="od-card__count">({{ $critical_open }})</span></h2>
    </div>
    <div class="od-card__body">
    @if($critical->isEmpty())
        <div class="od-empty od-empty--inline">
            <span class="od-empty__icon od-tint--green"><i data-lucide="check-circle"></i></span>
            <p class="od-empty__title">{{ __('lab_manager.critical_empty') }}</p>
        </div>
    @else
        <p class="od-muted odl-lead">{{ __('lab_manager.critical_subtitle') }}</p>
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_manager.col_raised') }}</th>
                        <th>{{ __('lab_manager.col_patient') }}</th>
                        <th>{{ __('lab_manager.col_test') }}</th>
                        <th>{{ __('lab_manager.col_value') }}</th>
                        <th>{{ __('lab_manager.col_threshold') }}</th>
                        <th class="od-table__actions">{{ __('lab_manager.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($critical as $alert)
                    @php
                        $order = $alert->labResult?->labOrder;
                        $p     = $order?->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr>
                        <td class="odl-time">{{ $alert->created_at?->translatedFormat('d M H:i') ?? '—' }}</td>
                        <td>
                            <div class="od-person">
                                @include('portals.lab.partials._avatar', ['name' => $pName])
                                <div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div>
                            </div>
                        </td>
                        <td>{{ $alert->test_name }}</td>
                        <td><span class="od-pill od-pill--urgent">{{ $alert->result_value }}</span></td>
                        <td class="od-muted">{{ $alert->critical_threshold }}</td>
                        <td class="od-table__actions odl-callback">
                            @if($order)
                            <a href="{{ route('portals.lab.show', $order->id) }}" class="od-btn od-btn--secondary od-btn--sm">{{ __('lab_manager.open_order') }}</a>
                            @endif
                            <details class="odl-details">
                                <summary class="od-btn od-btn--row"><i data-lucide="phone-call"></i> {{ __('lab_manager.callback') }}</summary>
                                <form method="POST" action="{{ route('portals.lab_manager.critical_callback', $alert->id) }}" class="odl-form">
                                    @csrf
                                    <label class="odl-label" for="informed-{{ $alert->id }}">{{ __('lab_manager.callback_informed') }}</label>
                                    <input class="od-input" id="informed-{{ $alert->id }}" name="informed" required minlength="2" maxlength="160" placeholder="{{ __('lab_manager.callback_informed_placeholder') }}">
                                    <label class="odl-label" for="note-{{ $alert->id }}">{{ __('lab_manager.callback_note') }}</label>
                                    <input class="od-input" id="note-{{ $alert->id }}" name="note" maxlength="500">
                                    <p class="od-muted odl-small">{{ __('lab_manager.callback_hint') }}</p>
                                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('lab_manager.callback_submit') }}</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
</section>

</div>
@endsection

@section('scripts')
<script @cspNonce>
(function () {
    // Today's sample queue: Routine / Urgent / STAT tabs filter the rows the
    // server already sent (first six of the band). No request, no new data.
    var tabs = document.querySelector('[data-queue-tabs]');
    if (!tabs) return;
    var rows = Array.prototype.slice.call(document.querySelectorAll('#sample-queue tbody tr[data-band]'));
    var none = document.querySelector('[data-queue-none]');
    tabs.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-band]');
        if (!btn) return;
        var band = btn.getAttribute('data-band');
        tabs.querySelectorAll('[data-band]').forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        var shown = 0;
        rows.forEach(function (r) {
            var match = band === 'all' || r.getAttribute('data-band') === band;
            r.hidden = !(match && shown < 6);
            if (match && shown < 6) shown++;
        });
        if (none) none.hidden = shown > 0;
    });
})();
</script>
@endsection
