@extends('layouts.portal')

@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))

@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))


@php
    $l = app()->getLocale();
    // Avatar initials: first letter of each name part, never the DOB or anything else.
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    // Fixed six-colour rotation by name hash (contract): never purple.
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    // Trend chip only when yesterday is a real, non-zero figure.
    $trend = function (int $today, int $yesterday): ?array {
        if ($yesterday <= 0) {
            return null;
        }
        $pct = (int) round(($today - $yesterday) * 100 / $yesterday);
        return ['pct' => $pct, 'up' => $pct >= 0];
    };
    $dispTrend   = $trend((int) ($stats['dispensed_today'] ?? 0), (int) ($stats['dispensed_yesterday'] ?? 0));
    $servedTrend = $trend((int) ($stats['served_today'] ?? 0), (int) ($stats['served_yesterday'] ?? 0));
    $statusPill = fn (string $status): string => match ($status) {
        'active'              => 'pending',
        'partially_dispensed' => 'verifying',
        'dispensed'           => 'ready',
        default               => 'neutral',
    };
    $queueTabs = [
        ''                    => ['label' => __('pharmacy_desk.pill_all', [], $l),    'count' => $stats['pending_rx'] ?? 0, 'dot' => null],
        'active'              => ['label' => __('pharmacy_desk.od_tab_new', [], $l),  'count' => $stats['rx_active'] ?? 0,  'dot' => 'pending'],
        'partially_dispensed' => ['label' => __('pharmacy_desk.od_tab_partial', [], $l), 'count' => $stats['rx_partial'] ?? 0, 'dot' => 'verifying'],
    ];
    $sel = $selectedRx ?? null;
    $selPatient = $sel?->patient;
    $selName = $selPatient ? trim(($selPatient->first_name ?? '') . ' ' . ($selPatient->last_name ?? '')) : '';
    $selAge = $selPatient?->date_of_birth ? \App\Support\ResultFlagResolver::ageBand($selPatient->date_of_birth) : null;
    $selIdentity = $selPatient?->identity_status instanceof \BackedEnum ? $selPatient->identity_status->value : (string) ($selPatient?->identity_status ?? '');
    $selVerified = in_array($selIdentity, ['verified', 'verified_by_facility'], true);
    $selUrl = $sel ? route('portals.pharmacy.prescriptions.show', $sel->id) : null;
@endphp

@section('head')
    {{-- Page rules for the pharmacy desk (public/css/od-pharmacy.css), scoped to body.od--pharmacy. --}}
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- KPI row: four tiles, one colour each. Every figure comes from the controller. --}}
<div class="od-kpis">
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><x-od-icon name="rx" /></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('pharmacy_desk.od_kpi_queue', [], $l) }}</span>
            <span class="od-kpi__value stat-card__value" data-kpi="pending_rx">{{ $stats['pending_rx'] ?? 0 }}</span>
            <span class="od-kpi__sub">{{ __('pharmacy_desk.od_kpi_queue_sub', ['active' => $stats['rx_active'] ?? 0, 'partial' => $stats['rx_partial'] ?? 0], $l) }}</span>
        </span>
    </a>
    <a href="{{ route('portals.pharmacy.dispensing', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="check"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('pharmacy_desk.od_kpi_dispensed', [], $l) }}</span>
            <span class="od-kpi__line">
                <span class="od-kpi__value stat-card__value" data-kpi="dispensed_today">{{ $stats['dispensed_today'] ?? 0 }}</span>
                @if($dispTrend)<span class="od-kpi__trend {{ $dispTrend['up'] ? 'od-kpi__trend--up' : 'od-kpi__trend--down' }}"><i data-lucide="{{ $dispTrend['up'] ? 'trending-up' : 'trending-down' }}"></i>{{ $dispTrend['up'] ? '+' : '' }}{{ $dispTrend['pct'] }}%</span>@endif
            </span>
            <span class="od-kpi__sub">{{ $dispTrend ? __('pharmacy_desk.od_vs_yesterday', [], $l) : __('pharmacy_desk.od_yesterday', ['count' => $stats['dispensed_yesterday'] ?? 0], $l) }}</span>
        </span>
    </a>
    @endcapability
    @capability('pharmacy_stock')
    <a href="{{ route('portals.pharmacy.stock', ['stock_status' => 'low_stock']) }}" class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="package"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('pharmacy_desk.od_kpi_low', [], $l) }}</span>
            <span class="od-kpi__value stat-card__value" data-kpi="low_stock">{{ $stats['low_stock'] ?? 0 }}</span>
            <span class="od-kpi__sub">@if(($stats['low_stock'] ?? 0) > 0)<i data-lucide="trending-down" class="is-alert"></i>@endif{{ __('pharmacy_desk.kpi_low_sub', [], $l) }}</span>
        </span>
    </a>
    @endcapability
    @capability('dispense')
    <a href="{{ route('portals.pharmacy.dispensing', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="users"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('pharmacy_desk.od_kpi_served', [], $l) }}</span>
            <span class="od-kpi__line">
                <span class="od-kpi__value stat-card__value" data-kpi="served_today">{{ $stats['served_today'] ?? 0 }}</span>
                @if($servedTrend)<span class="od-kpi__trend {{ $servedTrend['up'] ? 'od-kpi__trend--up' : 'od-kpi__trend--down' }}"><i data-lucide="{{ $servedTrend['up'] ? 'trending-up' : 'trending-down' }}"></i>{{ $servedTrend['up'] ? '+' : '' }}{{ $servedTrend['pct'] }}%</span>@endif
            </span>
            <span class="od-kpi__sub">{{ __('pharmacy_desk.od_today', [], $l) }}</span>
        </span>
    </a>
    @endcapability
</div>

@capability('dispense')
<div class="od-grid od-grid--split od-grid--stretch odp-main">

    {{-- Prescription queue --}}
    <section class="od-card odp-queue" aria-labelledby="odp-queue-title">
        <header class="od-card__head">
            <h2 class="od-card__title" id="odp-queue-title">{{ __('pharmacy_desk.od_queue_title', [], $l) }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => $stats['pending_rx'] ?? 0], $l) }}</span></h2>
            <a href="{{ route('portals.pharmacy.dashboard', array_filter(['tab' => $queueTab])) }}" class="od-btn od-btn--icon od-btn--ghost" aria-label="{{ __('public.portal.view_all', [], $l) }}" title="{{ __('public.portal.view_all', [], $l) }}"><i data-lucide="refresh-cw"></i></a>
        </header>

        <nav class="od-tabs" aria-label="{{ __('pharmacy_desk.od_queue_title', [], $l) }}">
            @foreach($queueTabs as $value => $t)
                @php $active = (string) ($queueTab ?? '') === $value; @endphp
                <a href="{{ route('portals.pharmacy.dashboard', array_filter(['tab' => $value])) }}" class="od-tab {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                    @if($t['dot'])<span class="od-tab__dot od-tab__dot--{{ $t['dot'] }}"></span>@endif
                    {{ $t['label'] }} <span class="od-tab__count">({{ $t['count'] }})</span>
                </a>
            @endforeach
        </nav>

        <div class="od-card__body odp-queue__body">
        <form method="GET" action="{{ route('portals.pharmacy.prescriptions') }}" class="odp-toolbar">
            <label class="od-field">
                <i data-lucide="search"></i>
                <input type="search" name="search" class="od-input" placeholder="{{ __('pharmacy_desk.search_queue', [], $l) }}" aria-label="{{ __('public.aria_search_patient') }}">
            </label>
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('pharmacy_desk.btn_filter', [], $l) }}</button>
        </form>

        @if($pendingRx->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="check-circle-2"></i></span>
                <div class="od-empty__title">{{ __('pharmacy_desk.od_queue_empty_title', [], $l) }}</div>
                <p class="od-empty__text">{{ __('pharmacy_desk.dash_empty_rx', [], $l) }}</p>
                <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-btn od-btn--primary"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.dash_empty_rx_action', [], $l) }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table odp-stack-sm">
                <thead><tr>
                    <th class="od-table__check"><input type="checkbox" aria-label="{{ __('pharmacy_desk.od_select_all', [], $l) }}" data-od-checkall></th>
                    <th class="od-table__num">#</th>
                    <th>{{ __('pharmacy_desk.od_col_time', [], $l) }}</th>
                    <th>{{ __('public.portal.col_patient', [], $l) }}</th>
                    <th>{{ __('pharmacy_desk.od_col_prescription', [], $l) }}</th>
                    <th class="odp-wide-only">{{ __('pharmacy_desk.od_col_source', [], $l) }}</th>
                    <th>{{ __('public.portal.col_status', [], $l) }}</th>
                    <th>{{ __('pharmacy_desk.od_col_actions', [], $l) }}</th>
                </tr></thead>
                <tbody>
                @foreach($pendingRx as $i => $rx)
                @php
                    $rxUrl  = route('portals.pharmacy.prescriptions.show', $rx->id);
                    $rxName = trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? ''));
                    $isSel  = $sel && $sel->id === $rx->id;
                @endphp
                <tr class="{{ $isSel ? 'is-selected' : '' }}">
                    <td class="od-table__check"><input type="checkbox" aria-label="{{ __('pharmacy_desk.od_select_row', ['name' => $rxName !== '' ? $rxName : ($rx->reference ?? '')], $l) }}"></td>
                    <td class="od-table__num">{{ $i + 1 }}</td>
                    <td data-label="{{ __('pharmacy_desk.od_col_time', [], $l) }}">{{ ($rx->prescribed_at ?? $rx->created_at)?->format('H:i') }}</td>
                    <td data-label="{{ __('public.portal.col_patient', [], $l) }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $tone($rxName !== '' ? $rxName : (string) $rx->id) }}" aria-hidden="true">{{ $initials($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                            <a href="{{ route('portals.pharmacy.dashboard', array_filter(['tab' => $queueTab, 'rx' => $rx->id])) }}" class="od-table__name">{{ $rxName !== '' ? $rxName : '—' }}</a>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_desk.od_col_prescription', [], $l) }}">
                        <span class="od-table__name">{{ $rx->reference ?? '—' }}</span>
                        <span class="od-table__sub">{{ __('pharmacy_desk.items_count', ['count' => $rx->items->count()], $l) }}</span>
                    </td>
                    <td class="odp-wide-only" data-label="{{ __('pharmacy_desk.od_col_source', [], $l) }}">
                        @if($rx->pharmacy_route_id)
                            <span class="od-pill od-pill--dot od-pill--normal">{{ __('pharmacy_desk.od_source_routed', [], $l) }}</span>
                        @else
                            <span class="od-pill od-pill--dot od-pill--counselling">{{ __('pharmacy_desk.od_source_here', [], $l) }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.portal.col_status', [], $l) }}"><span class="od-pill od-pill--{{ $statusPill((string) $rx->status) }}">{{ (string) $rx->status === 'partially_dispensed' ? __('pharmacy_desk.od_pill_partial', [], $l) : \App\Support\Enums::label((string) $rx->status) }}</span></td>
                    <td class="od-table__actions">
                        <a href="{{ $rxUrl }}" class="od-btn od-btn--row">{{ __('pharmacy_desk.od_btn_dispense', [], $l) }}</a>
                        <a href="{{ route('portals.pharmacy.dashboard', array_filter(['tab' => $queueTab, 'rx' => $rx->id])) }}" class="od-kebab" aria-label="{{ __('pharmacy_desk.od_kebab', ['reference' => $rx->reference ?? ''], $l) }}"><i data-lucide="more-horizontal"></i></a>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
        </div>
        <footer class="od-card__foot">
            <a href="{{ route('portals.pharmacy.orders.index') }}" class="od-card__link odp-orders-link" data-pending-orders="{{ $pendingOrders ?? 0 }}"><i data-lucide="shopping-bag"></i> {{ trans_choice('pharmacy_orders.dashboard_pending', $pendingOrders ?? 0, ['count' => $pendingOrders ?? 0]) }}</a>
            <a href="{{ route('portals.pharmacy.prescriptions') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
        </footer>
    </section>

    {{-- Patient & prescription details for the selected (or first) queue row.
         Name, Health ID, age band, sex. Never the date of birth. --}}
    <section class="od-card odp-detail" aria-labelledby="odp-detail-title">
        <header class="od-card__head">
            <h2 class="od-card__title" id="odp-detail-title">{{ __('pharmacy_desk.sec_patient_rx', [], $l) }}</h2>
            @if($sel && $selVerified)
                <span class="od-chip od-chip--ok"><i data-lucide="shield-check"></i> {{ __('pharmacy_desk.od_verified', [], $l) }}</span>
            @elseif($sel && $selIdentity !== '')
                <span class="od-chip">{{ \App\Enums\IdentityStatus::tryFrom($selIdentity)?->label() ?? $selIdentity }}</span>
            @endif
        </header>

        @if(! $sel)
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="file-search"></i></span>
                <div class="od-empty__title">{{ __('pharmacy_desk.sec_patient_rx', [], $l) }}</div>
                <p class="od-empty__text">{{ __('pharmacy_desk.od_detail_empty', [], $l) }}</p>
                <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-btn od-btn--secondary"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.dash_empty_rx_action', [], $l) }}</a>
            </div>
        @else
        <div class="od-card__body od-detail">
            <div class="od-detail__head">
                <span class="od-avatar od-avatar--lg od-tint--{{ $tone($selName !== '' ? $selName : (string) $sel->id) }}" aria-hidden="true">{{ $initials($selPatient?->first_name, $selPatient?->last_name) }}</span>
                <div class="od-detail__main">
                    <h3 class="od-detail__name">{{ $selName !== '' ? $selName : __('pharmacy_desk.patient_hidden', [], $l) }}</h3>
                    <div class="od-detail__ref">
                        <span>{{ $sel->reference ?? '—' }}</span>
                        <span>{{ ($sel->prescribed_at ?? $sel->created_at)?->translatedFormat('d M Y') }}</span>
                        <span>{{ ($sel->prescribed_at ?? $sel->created_at)?->format('H:i') }}</span>
                    </div>
                    @if($selPatient)
                    <div class="od-detail__facts">
                        <span class="od-detail__fact"><i data-lucide="calendar"></i> {{ $selAge ? __('pharmacy_desk.age_bands.' . $selAge, [], $l) : __('pharmacy_desk.not_recorded', [], $l) }}</span>
                        <span class="od-detail__fact"><i data-lucide="user"></i> {{ filled($selPatient->sex) ? \App\Support\Enums::label((string) $selPatient->sex) : __('pharmacy_desk.not_recorded', [], $l) }}</span>
                        <span class="od-detail__fact"><i data-lucide="id-card"></i> {{ $selPatient->health_id ?? '—' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <nav class="od-tabs od-tabs--inner" aria-label="{{ __('pharmacy_desk.rx_breadcrumb', [], $l) }}">
                <a href="{{ $selUrl }}#prescription" class="od-tab is-active">{{ __('pharmacy_desk.tab_prescription', [], $l) }}</a>
                <a href="{{ $selUrl }}#history" class="od-tab">{{ __('pharmacy_desk.tab_history', [], $l) }}</a>
                <a href="{{ $selUrl }}#alerts" class="od-tab">{{ __('pharmacy_desk.tab_alerts', [], $l) }}</a>
                <a href="{{ $selUrl }}#notes" class="od-tab">{{ __('pharmacy_desk.od_tab_notes', [], $l) }}</a>
            </nav>

            <div class="od-table-wrap">
                <table class="od-table">
                    <thead><tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('pharmacy_desk.od_col_medicine', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_dose', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_qty', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.od_col_instructions', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($sel->items as $n => $it)
                    <tr>
                        <td class="od-table__num">{{ $n + 1 }}</td>
                        <td><span class="od-table__name">{{ $it->drug_name ?: '—' }}</span>@if($it->route)<span class="od-table__sub">{{ \App\Support\Enums::label($it->route, 'route') }}</span>@endif</td>
                        <td>{{ $it->dose ?: '—' }}</td>
                        <td>{{ $it->quantity ?? '—' }}</td>
                        <td>{{ collect([$it->frequency, $it->duration_days ? __('pharmacy_desk.days', ['count' => $it->duration_days], $l) : null])->filter()->implode(' · ') ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="od-muted">{{ __('pharmacy_desk.no_items', [], $l) }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="od-detail__people">
                <div class="od-person">
                    <span class="od-person__icon"><i data-lucide="user-round"></i></span>
                    <div>
                        <span class="od-person__label">{{ __('pharmacy_desk.lbl_prescriber', [], $l) }}</span>
                        <span class="od-person__name">{{ $sel->prescriber?->name ?? '—' }}</span>
                        <span class="od-person__sub">{{ $sel->facility?->name ?? '—' }}</span>
                    </div>
                </div>
                <div class="od-person">
                    <span class="od-person__icon"><i data-lucide="file-text"></i></span>
                    <div>
                        <span class="od-person__label">{{ __('pharmacy_desk.lbl_type', [], $l) }}</span>
                        <span class="od-person__name">{{ $sel->pharmacy_route_id ? __('pharmacy_desk.type_external', [], $l) : __('pharmacy_desk.type_issued_here', [], $l) }}</span>
                        <span class="od-person__sub">@if($sel->expires_at){!! __('pharmacy_desk.valid_until', ['date' => '<span class="odp-nowrap">' . e($sel->expires_at->translatedFormat('d M Y')) . '</span>'], $l) !!}@else{{ __('pharmacy_desk.lbl_expires', [], $l) }}: —@endif</span>
                    </div>
                </div>
            </div>
            <a href="{{ $selUrl }}" class="od-btn od-btn--primary od-btn--block"><x-od-icon name="capsule" /> {{ __('pharmacy_desk.od_open_full', [], $l) }}</a>
        </div>
        @endif
    </section>
</div>
@endcapability

{{-- Bottom row: quick actions, common medicines, stock alerts --}}
<div class="od-grid odp-bottom">
    @capability('dispense')
    <section class="od-card odp-quick-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_desk.quick_title', [], $l) }}</h2></header>
        <div class="od-card__body">
        <div class="od-quick">
            <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-quick__tile" data-od-hotkey="F2">
                <x-od-icon name="barcode" class="od-quick__glyph" />
                <span class="od-quick__label">{{ __('pharmacy_desk.quick_lookup', [], $l) }}</span>
                <span class="od-quick__key">{{ __('pharmacy_desk.quick_key_lookup', [], $l) }}</span>
            </a>
            <a href="{{ route('portals.pharmacy.prescriptions') }}" class="od-quick__tile" data-od-hotkey="F3">
                <x-od-icon name="rx" class="od-quick__glyph" />
                <span class="od-quick__label">{{ __('pharmacy_desk.quick_queue', [], $l) }}</span>
                <span class="od-quick__key">{{ __('pharmacy_desk.quick_key_queue', [], $l) }}</span>
            </a>
            <a href="{{ route('portals.pharmacy.dispensing') }}" class="od-quick__tile" data-od-hotkey="F4">
                <x-od-icon name="capsule" class="od-quick__glyph" />
                <span class="od-quick__label">{{ __('pharmacy_desk.quick_register', [], $l) }}</span>
                <span class="od-quick__key">{{ __('pharmacy_desk.quick_key_register', [], $l) }}</span>
            </a>
            <a href="{{ route('portals.pharmacy.stock') }}" class="od-quick__tile" data-od-hotkey="F5">
                <x-od-icon name="pill-bottle" class="od-quick__glyph" />
                <span class="od-quick__label">{{ __('pharmacy_desk.quick_stock', [], $l) }}</span>
                <span class="od-quick__key">{{ __('pharmacy_desk.quick_key_stock', [], $l) }}</span>
            </a>
        </div>
        </div>
    </section>

    <section class="od-card odp-common">
        <header class="od-card__head">
            <h2 class="od-card__title">{{ __('pharmacy_desk.od_common_title', [], $l) }}</h2>
            <a href="{{ route('portals.pharmacy.dispensing') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
        </header>
        @if($commonMedicines->isEmpty())
            <div class="od-empty od-empty--compact">
                <span class="od-empty__icon"><x-od-icon name="capsule" /></span>
                <p class="od-empty__text">{{ __('pharmacy_desk.od_common_empty', [], $l) }}</p>
            </div>
        @else
        <div class="od-card__body"><ul class="odp-meds">
            @foreach($commonMedicines as $m)
            <li class="odp-med">
                <span class="odp-med__pack"><x-od-icon name="pill-bottle" /></span>
                <span class="odp-med__name">{{ $m->drug_name }}</span>
                <span class="odp-med__sub">{{ trans_choice('pharmacy_desk.od_common_times', (int) $m->times, ['count' => (int) $m->times], $l) }}</span>
            </li>
            @endforeach
        </ul></div>
        @endif
    </section>
    @endcapability

    <section class="od-card odp-alerts">
        <header class="od-card__head">
            <h2 class="od-card__title">{{ __('public.pharmacy_portal.panel_stock_alerts', [], $l) }}</h2>
            <a href="{{ route('portals.pharmacy.stock') }}" class="od-card__link">{{ __('public.portal.view_all', [], $l) }}</a>
        </header>
        @if($alerts->isEmpty())
            <div class="od-empty od-empty--compact">
                <span class="od-empty__icon"><i data-lucide="package-check"></i></span>
                <div class="od-empty__title">{{ __('public.pharmacy_portal.no_stock_alerts', [], $l) }}</div>
                <p class="od-empty__text">{{ __('pharmacy_desk.od_alerts_empty', [], $l) }}</p>
            </div>
        @else
        <div class="od-card__body"><ul class="od-list">
            @foreach($alerts as $drug)
            @php $st = $drug->stock_status instanceof \BackedEnum ? $drug->stock_status->value : (string) $drug->stock_status; @endphp
            <li class="od-list__row">
                <span class="od-list__icon {{ $st === 'out_of_stock' ? 'od-tint--red' : 'od-tint--amber' }}"><x-od-icon name="pill-bottle" /></span>
                <span class="od-list__text">
                    @if(isset($ledgerItems) && $ledgerItems->has($drug->medicine_id))
                        <a href="{{ route('portals.pharmacy.stock.items.show', $ledgerItems->get($drug->medicine_id)) }}" class="od-list__title">{{ $drug->medicine?->name ?? '—' }}</a>
                    @else
                        <span class="od-list__title">{{ $drug->medicine?->name ?? '—' }}</span>
                    @endif
                    <span class="od-list__sub">{{ collect([$drug->medicine?->form, $drug->medicine?->strength])->filter()->implode(' ') }}</span>
                </span>
                <span class="od-pill od-pill--{{ $st === 'out_of_stock' ? 'low' : 'reorder' }}">{{ $st === 'out_of_stock' ? __('pharmacy_desk.od_pill_out', [], $l) : __('pharmacy_desk.od_pill_low', [], $l) }}</span>
            </li>
            @endforeach
        </ul></div>
        @endif
    </section>
</div>

<script @cspNonce>
(function () {
    // F2–F5 open the quick-action tiles (keys printed on each tile).
    document.addEventListener('keydown', function (e) {
        if (e.altKey || e.ctrlKey || e.metaKey) return;
        var tile = document.querySelector('[data-od-hotkey="' + e.key + '"]');
        if (!tile) return;
        e.preventDefault();
        window.location.href = tile.getAttribute('href');
    });
    var all = document.querySelector('[data-od-checkall]');
    if (all) {
        all.addEventListener('change', function () {
            all.closest('table').querySelectorAll('tbody input[type="checkbox"]').forEach(function (b) { b.checked = all.checked; });
        });
    }
})();
</script>

@endsection
