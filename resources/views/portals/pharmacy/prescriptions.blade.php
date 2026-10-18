@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_rx', [], app()->getLocale()))
@php
    $l = app()->getLocale();
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
    // Status filter pills: same `status` query parameter the select used to send.
    $statusPills = [
        ''                    => ['label' => __('public.pharmacy_portal.filter_pending_all', [], $l),      'dot' => null],
        'active'              => ['label' => __('public.pharmacy_portal.filter_status_active', [], $l),    'dot' => 'pending'],
        'partially_dispensed' => ['label' => __('public.pharmacy_portal.filter_status_partial', [], $l),   'dot' => 'normal'],
        'dispensed'           => ['label' => __('public.pharmacy_portal.filter_status_dispensed', [], $l), 'dot' => 'ready'],
        'cancelled'           => ['label' => __('public.pharmacy_portal.filter_status_cancelled', [], $l), 'dot' => 'urgent'],
        'expired'             => ['label' => __('public.pharmacy_portal.filter_status_expired', [], $l),   'dot' => 'urgent'],
    ];
    $statusPill = fn (string $status): string => match ($status) {
        'active'              => 'pending',
        'partially_dispensed' => 'verifying',
        'dispensed'           => 'ready',
        'cancelled', 'expired', 'voided', 'entered_in_error' => 'urgent',
        default               => 'neutral',
    };
    $currentStatus = (string) request('status', '');
@endphp

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.pharmacy_portal.page_heading_rx', [], $l) }}</h1>
        <p class="od-page__sub">{{ __('public.pharmacy_portal.page_subtitle_rx', [], $l) }}</p>
    </div>
    <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-btn od-btn--primary"><x-od-icon name="barcode" /> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
</div>

@if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<section class="od-card odp-queue">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('public.pharmacy_portal.page_heading_rx', [], $l) }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => $prescriptions->total()], $l) }}</span></h2>
    </header>

    <div class="od-card__body odp-queue__body">
        <nav class="od-tabs" aria-label="{{ __('public.aria_status') }}">
            @foreach($statusPills as $value => $pill)
                <a href="{{ route('portals.pharmacy.prescriptions', array_filter(['status' => $value, 'search' => request('search')], fn ($v) => $v !== null && $v !== '')) }}"
                   class="od-tab {{ $currentStatus === $value ? 'is-active' : '' }}" @if($currentStatus === $value) aria-current="page" @endif>
                    @if($pill['dot'])<span class="od-tab__dot od-tab__dot--{{ $pill['dot'] }}"></span>@endif {{ $pill['label'] }}
                </a>
            @endforeach
        </nav>
        <form method="GET" class="odp-toolbar">
            @if($currentStatus !== '')<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
            <label class="od-field">
                <i data-lucide="search"></i>
                <input type="text" name="search" class="od-input" placeholder="{{ __('public.pharmacy_portal.ph_search_patient', [], $l) }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search_patient') }}">
            </label>
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) }}</button>
            @if(request()->hasAny(['status','search']))
                <a href="{{ route('portals.pharmacy.prescriptions') }}" class="od-btn od-btn--ghost">{{ __('public.pharmacy_portal.btn_clear', [], $l) }}</a>
            @endif
        </form>

        @if($prescriptions->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><x-od-icon name="rx" /></span>
                <div class="od-empty__title">{{ __('pharmacy_desk.od_queue_empty_title', [], $l) }}</div>
                <p class="od-empty__text">{{ __('public.pharmacy_portal.no_prescriptions', [], $l) }}</p>
                <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-btn od-btn--primary"><x-od-icon name="barcode" /> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table odp-stack-sm">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('public.pharmacy_portal.col_patient', [], $l) }}</th>
                        <th>{{ __('public.pharmacy_portal.col_items', [], $l) }}</th>
                        <th>{{ __('public.pharmacy_portal.col_prescribed', [], $l) }}</th>
                        <th>{{ __('public.pharmacy_portal.col_expires', [], $l) }}</th>
                        <th>{{ __('public.pharmacy_portal.col_status', [], $l) }}</th>
                        <th>{{ __('public.pharmacy_portal.col_action', [], $l) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescriptions as $i => $rx)
                    @php
                        $rxUrl = route('portals.pharmacy.prescriptions.show', $rx->id);
                        $rxName = trim(($rx->patient?->first_name ?? '') . ' ' . ($rx->patient?->last_name ?? ''));
                        $dispensable = in_array($rx->status, ['active', 'partially_dispensed']);
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $prescriptions->firstItem() + $i }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_patient', [], $l) }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($rxName !== '' ? $rxName : (string) $rx->id) }}" aria-hidden="true">{{ $initials($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                                <div>
                                    <a href="{{ $rxUrl }}" class="od-table__name">{{ $rxName !== '' ? $rxName : '—' }}</a>
                                    <span class="od-table__sub">{{ $rx->patient?->health_id ?? '' }} @if($rx->reference)&middot; {{ $rx->reference }}@endif &middot; {{ $rx->items->count() }} {{ __('public.pharmacy_portal.lbl_items', [], $l) }}</span>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_items', [], $l) }}">
                            <div class="odp-lines">
                            @foreach($rx->items->take(2) as $item)
                                <span>{{ $item->drug_name }} {{ $item->dosage }}</span>
                            @endforeach
                            @if($rx->items->count() > 2)
                                <span class="od-table__sub">+{{ $rx->items->count() - 2 }} {{ __('public.pharmacy_portal.lbl_items', [], $l) }}</span>
                            @endif
                            </div>
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_prescribed', [], $l) }}" class="od-muted">{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') ?? $rx->created_at?->translatedFormat('d M Y') }}</td>
                        <td data-label="{{ __('public.pharmacy_portal.col_expires', [], $l) }}">
                            @if($rx->expires_at)
                                @if($rx->expires_at->isPast())<span class="od-pill od-pill--urgent">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>
                                @else<span class="od-muted">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>@endif
                            @else
                                <span class="od-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.pharmacy_portal.col_status', [], $l) }}"><span class="od-pill od-pill--{{ $statusPill((string) $rx->status) }}">@enum($rx->status)</span></td>
                        <td class="od-table__actions" data-label="">
                            @if($dispensable)
                            <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="inline-form">@csrf
                                <button type="submit" class="od-btn od-btn--row" data-confirm="{{ __('public.pharmacy_portal.confirm_dispense_msg', [], $l) }}">{{ __('public.pharmacy_portal.btn_dispense', [], $l) }}</button>
                            </form>
                            <a href="{{ $rxUrl }}" class="od-kebab" aria-label="{{ __('pharmacy_desk.btn_open_rx', [], $l) }}" title="{{ __('pharmacy_desk.btn_open_rx', [], $l) }}"><i data-lucide="more-horizontal"></i></a>
                            @else
                            <a href="{{ $rxUrl }}" class="od-btn od-btn--row">{{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($prescriptions->hasPages())<footer class="od-card__foot">{{ $prescriptions->links() }}</footer>@endif
</section>

@endsection
