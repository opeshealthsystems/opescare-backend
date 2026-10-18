@extends('layouts.portal')
@section('title', __('pharmacy_orders.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_orders.title'))

@php
    $now = \Illuminate\Support\Carbon::now();
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    // Desk status -> pill colour. Theme badges only; never purple.
    $deskBadge = fn (string $desk): string => match ($desk) {
        \App\Models\MedicineReservation::DESK_PENDING   => 'badge-warning',
        \App\Models\MedicineReservation::DESK_CANCELLED => 'badge-neutral',
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED => 'badge-success',
        default                                          => 'badge-info',
    };
    $tabDot = ['pending' => 'warning', 'in_progress' => 'info', 'done' => 'success'];
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_orders.title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_orders.subtitle') }}</p>
</div>

@if(isset($hasListing) && ! $hasListing)
<div class="banner banner--warning" role="status">
    <i data-lucide="link-2-off"></i>
    <div>{{ __('pharmacy_orders.no_listing') }}</div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="shopping-bag"></i> {{ __('pharmacy_orders.tab_' . $tab) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $counts[$tab] ?? 0]) }}</span></h3>
    </div>

    <div class="ph-tools">
        <nav class="ph-pills" aria-label="{{ __('pharmacy_orders.title') }}">
            @foreach($tabs as $t)
                <a href="{{ route('portals.pharmacy.orders.index', ['tab' => $t]) }}" class="ph-pill {{ $t === $tab ? 'active' : '' }}" @if($t === $tab) aria-current="page" @endif>
                    <span class="ph-dot ph-dot--{{ $tabDot[$t] ?? 'neutral' }}"></span>
                    {{ __('pharmacy_orders.tab_' . $t) }}
                    <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $counts[$t] ?? 0]) }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_orders.col_patient') }}</th>
                <th>{{ __('pharmacy_orders.col_medicine') }}</th>
                <th>{{ __('pharmacy_orders.col_qty') }}</th>
                <th>{{ __('pharmacy_orders.col_pack') }}</th>
                <th>{{ __('pharmacy_orders.col_total') }}</th>
                <th>{{ __('pharmacy_orders.col_status') }}</th>
                <th>{{ __('pharmacy_orders.col_placed') }}</th>
                <th>{{ __('pharmacy_orders.col_expires') }}</th>
                <th class="row-actions">{{ __('pharmacy_orders.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($reservations as $r)
                @php
                    $desk    = $r->deskStatus();
                    $patient = $r->patient;
                    $name    = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
                    $showUrl = route('portals.pharmacy.orders.show', $r->id);
                @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_orders.col_patient') }}">
                        <div class="ph-cell">
                            <span class="ph-avatar {{ $r->isDelivery() ? 'ph-avatar--teal' : '' }}" aria-hidden="true">{{ $initials($patient?->first_name, $patient?->last_name) }}</span>
                            <div>
                                <span class="ph-cell__title">{{ $name !== '' ? $name : '—' }}</span>
                                <div class="ph-cell__sub">
                                    <a href="{{ $showUrl }}" class="fs-mono">{{ $r->reference }}</a>
                                    &middot; {{ $r->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}
                                    @if($patient?->health_id)&middot; <span class="fs-mono">{{ $patient->health_id }}</span>@endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_medicine') }}" class="td-strong">
                        {{ $r->medicine?->name ?? '—' }}
                        <div class="td-muted">{{ $r->medicine?->strength }} {{ $r->medicine?->form }}</div>
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_qty') }}" class="fs-mono">{{ $r->quantity }}</td>
                    <td data-label="{{ __('pharmacy_orders.col_pack') }}">{{ $r->pack_size ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_orders.col_total') }}" class="fs-mono">
                        @if($r->total_price !== null){{ number_format((float) $r->total_price, 0, ',', ' ') }} FCFA @else <span class="td-muted">{{ __('pharmacy_orders.no_price') }}</span> @endif
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_status') }}">
                        <span class="badge {{ $deskBadge($desk) }}">@enum($desk, 'reservation_status')</span>
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_placed') }}" class="td-muted">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</td>
                    <td data-label="{{ __('pharmacy_orders.col_expires') }}" class="td-muted">
                        @if($r->expires_at)
                            {{ $r->expires_at->translatedFormat('d M Y H:i') }}
                            @if($desk === \App\Models\MedicineReservation::DESK_PENDING && $r->expires_at->lt($now))
                                <span class="badge badge-danger badge-sm">{{ __('pharmacy_orders.expired') }}</span>
                            @endif
                        @else
                            {{ __('pharmacy_orders.no_expiry') }}
                        @endif
                    </td>
                    <td class="row-actions" data-label="{{ __('pharmacy_orders.col_action') }}">
                        @include('portals.pharmacy.orders._actions', ['reservation' => $r, 'desk' => $desk, 'compact' => true])
                        <a href="{{ $showUrl }}" class="btn btn-ghost btn-icon ph-kebab" aria-label="{{ __('pharmacy_orders.btn_open') }}" title="{{ __('pharmacy_orders.btn_open') }}"><i data-lucide="eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="shopping-bag"></i>
                        <p>{{ __('pharmacy_orders.empty_' . $tab) }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
