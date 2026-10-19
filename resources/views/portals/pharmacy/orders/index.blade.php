@extends('layouts.portal')
@section('title', __('pharmacy_orders.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))

@php
    $now = \Illuminate\Support\Carbon::now();
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    // Desk status -> pill. Never purple.
    $deskPill = fn (string $desk): string => match ($desk) {
        \App\Models\MedicineReservation::DESK_PENDING   => 'pending',
        \App\Models\MedicineReservation::DESK_CANCELLED => 'neutral',
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED => 'ready',
        default                                          => 'verifying',
    };
    $tabDot = ['pending' => 'pending', 'in_progress' => 'normal', 'done' => 'ready'];
@endphp

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('pharmacy_orders.title') }}</h1>
        <p class="od-page__sub">{{ __('pharmacy_orders.subtitle') }}</p>
    </div>
</div>

@if(isset($hasListing) && ! $hasListing)
<div class="odp-notice od-tint--amber" role="status">
    <i data-lucide="link-2-off"></i>
    <div>{{ __('pharmacy_orders.no_listing') }}</div>
</div>
@endif

<section class="od-card odp-queue">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_orders.tab_' . $tab) }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => $counts[$tab] ?? 0]) }}</span></h2>
    </header>

    <div class="od-card__body odp-queue__body">
        <nav class="od-tabs" aria-label="{{ __('pharmacy_orders.title') }}">
            @foreach($tabs as $t)
                <a href="{{ route('portals.pharmacy.orders.index', ['tab' => $t]) }}" class="od-tab {{ $t === $tab ? 'is-active' : '' }}" @if($t === $tab) aria-current="page" @endif>
                    <span class="od-tab__dot od-tab__dot--{{ $tabDot[$t] ?? 'normal' }}"></span>
                    {{ __('pharmacy_orders.tab_' . $t) }}
                    <span class="od-tab__count">{{ __('pharmacy_desk.count_paren', ['count' => $counts[$t] ?? 0]) }}</span>
                </a>
            @endforeach
        </nav>

        @if($reservations->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="shopping-bag"></i></span>
                <div class="od-empty__title">{{ __('pharmacy_orders.tab_' . $tab) }}</div>
                <p class="od-empty__text">{{ __('pharmacy_orders.empty_' . $tab) }}</p>
                <a href="{{ route('portals.pharmacy.dashboard') }}" class="od-btn od-btn--secondary"><i data-lucide="layout-dashboard"></i> {{ __('public.pharmacy_portal.nav_dashboard') }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table odp-stack-sm">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('pharmacy_orders.col_patient') }}</th>
                    <th>{{ __('pharmacy_orders.col_medicine') }}</th>
                    <th>{{ __('pharmacy_orders.col_qty') }}</th>
                    <th>{{ __('pharmacy_orders.col_pack') }}</th>
                    <th>{{ __('pharmacy_orders.col_total') }}</th>
                    <th>{{ __('pharmacy_orders.col_status') }}</th>
                    <th>{{ __('pharmacy_orders.col_placed') }}</th>
                    <th>{{ __('pharmacy_orders.col_expires') }}</th>
                    <th>{{ __('pharmacy_orders.col_action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($reservations as $i => $r)
                    @php
                        $desk    = $r->deskStatus();
                        $patient = $r->patient;
                        $name    = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
                        $showUrl = route('portals.pharmacy.orders.show', $r->id);
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $i + 1 }}</td>
                        <td data-label="{{ __('pharmacy_orders.col_patient') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($name !== '' ? $name : (string) $r->id) }}" aria-hidden="true">{{ $initials($patient?->first_name, $patient?->last_name) }}</span>
                                <div>
                                    <a href="{{ $showUrl }}" class="od-table__name">{{ $name !== '' ? $name : '—' }}</a>
                                    <span class="od-table__sub">
                                        {{ $r->reference }}
                                        &middot; {{ $r->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}
                                        @if($patient?->health_id)&middot; {{ $patient->health_id }}@endif
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('pharmacy_orders.col_medicine') }}">
                            <span class="od-table__name">{{ $r->medicine?->name ?? '—' }}</span>
                            <span class="od-table__sub">{{ $r->medicine?->strength }} {{ $r->medicine?->form }}</span>
                        </td>
                        <td data-label="{{ __('pharmacy_orders.col_qty') }}" class="odp-mono">{{ $r->quantity }}</td>
                        <td data-label="{{ __('pharmacy_orders.col_pack') }}">{{ $r->pack_size ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_orders.col_total') }}" class="odp-mono">
                            @if($r->total_price !== null){{ number_format((float) $r->total_price, 0, ',', ' ') }} FCFA @else <span class="od-muted">{{ __('pharmacy_orders.no_price') }}</span> @endif
                        </td>
                        <td data-label="{{ __('pharmacy_orders.col_status') }}">
                            <span class="od-pill od-pill--{{ $deskPill($desk) }}">@enum($desk, 'reservation_status')</span>
                        </td>
                        <td data-label="{{ __('pharmacy_orders.col_placed') }}" class="od-muted">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td data-label="{{ __('pharmacy_orders.col_expires') }}" class="od-muted">
                            @if($r->expires_at)
                                {{ $r->expires_at->translatedFormat('d M Y H:i') }}
                                @if($desk === \App\Models\MedicineReservation::DESK_PENDING && $r->expires_at->lt($now))
                                    <span class="od-pill od-pill--urgent">{{ __('pharmacy_orders.expired') }}</span>
                                @endif
                            @else
                                {{ __('pharmacy_orders.no_expiry') }}
                            @endif
                        </td>
                        <td class="od-table__actions" data-label="{{ __('pharmacy_orders.col_action') }}">
                            @include('portals.pharmacy.orders._actions', ['reservation' => $r, 'desk' => $desk, 'compact' => true])
                            <a href="{{ $showUrl }}" class="od-kebab" aria-label="{{ __('pharmacy_orders.btn_open') }}" title="{{ __('pharmacy_orders.btn_open') }}"><i data-lucide="more-horizontal"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

@endsection
