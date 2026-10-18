@extends('layouts.portal')

@section('title', __('facility_admin.wards_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.wards_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

@php
    $wards = $board['wards'];
    $typeLabel = fn (string $type): string => __('facility_admin.ward_types.' . $type) !== 'facility_admin.ward_types.' . $type
        ? __('facility_admin.ward_types.' . $type) : $type;
    $bedStatus = fn (string $status): string => __('facility_admin.bed_status_' . $status) !== 'facility_admin.bed_status_' . $status
        ? __('facility_admin.bed_status_' . $status) : $status;
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('facility_admin.wards_title') }}</h1>
        <p class="od-page__sub">{{ __('facility_admin.wards_subtitle') }}</p>
    </div>
    <div class="odc-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="od-btn od-btn--secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
    </div>
</header>

{{-- Scope, stated plainly: structure only; admissions are recorded elsewhere. --}}
<div class="odc-note odc-note--info">
    <i data-lucide="info"></i>
    <div class="odc-note__text">{{ __('facility_admin.wards_scope_note') }}</div>
</div>

@if(session('success'))
    <div class="odc-note" role="status"><i data-lucide="check-circle-2"></i><div class="odc-note__text">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odc-note odc-note--error" role="alert"><i data-lucide="alert-circle"></i><div class="odc-note__text">{{ session('error') }}</div></div>
@endif

{{-- ── Board ────────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.wards_title') }} <span class="od-card__count">({{ count($wards) }})</span></h2>
        <span class="od-pill od-pill--normal" data-active-admissions="{{ $board['active_admissions'] }}">
            {{ __('facility_admin.wards_active_admissions') }}: {{ $board['active_admissions'] }}
        </span>
    </header>
    <div class="od-card__body od-card__body--flush">
        @if($wards === [])
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="bed"></i></span>
                <p class="od-empty__text">{{ __('facility_admin.wards_empty') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="wards">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('facility_admin.col_ward') }}</th>
                        <th>{{ __('facility_admin.col_ward_type') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_ward_capacity') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_beds') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_occupied') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_available') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_out_of_service') }}</th>
                        <th>{{ __('facility_admin.col_occupancy') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($wards as $w)
                    <tr data-ward="{{ $w['id'] }}">
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('facility_admin.col_ward') }}">
                            <div class="od-table__person">
                                <span class="od-avatar odc-ico-avatar od-tint--{{ $tone((string) $w['name']) }}" aria-hidden="true"><i data-lucide="bed"></i></span>
                                <div>
                                    <span class="od-table__name">{{ $w['name'] }}</span>
                                    @unless($w['is_active'])<span class="od-table__sub">{{ __('facility_admin.ward_inactive') }}</span>@endunless
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('facility_admin.col_ward_type') }}">{{ $typeLabel($w['ward_type']) }}</td>
                        <td data-label="{{ __('facility_admin.col_ward_capacity') }}" class="odc-num">{{ $w['total_beds'] }}</td>
                        <td data-label="{{ __('facility_admin.col_beds') }}" class="odc-num" data-beds>{{ $w['beds'] }}</td>
                        <td data-label="{{ __('facility_admin.col_occupied') }}" class="odc-num" data-occupied>{{ $w['occupied'] }}</td>
                        <td data-label="{{ __('facility_admin.col_available') }}" class="odc-num" data-available>{{ $w['available'] }}</td>
                        <td data-label="{{ __('facility_admin.col_out_of_service') }}" class="odc-num" data-out-of-service>{{ $w['out_of_service'] }}</td>
                        <td data-label="{{ __('facility_admin.col_occupancy') }}"><span class="od-pill od-pill--{{ $w['occupancy_pct'] === null ? 'neutral' : ($w['occupancy_pct'] >= 90 ? 'urgent' : ($w['occupancy_pct'] >= 70 ? 'pending' : 'ready')) }}">{{ $w['occupancy_pct'] === null ? '—' : $w['occupancy_pct'] . ' %' }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

{{-- ── Per-ward beds ────────────────────────────────────────────────── --}}
<div class="od-grid od-grid--2">
@foreach($wards as $w)
<section class="od-card" data-ward-panel="{{ $w['id'] }}">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ $w['name'] }}</h2>
        <span class="od-pill od-pill--neutral">{{ $typeLabel($w['ward_type']) }}</span>
    </header>
    <div class="od-card__body">
        @php $beds = $bedsByWard[$w['id']] ?? collect(); @endphp
        @if($beds->isEmpty())
            <p class="odc-muted">{{ __('facility_admin.beds_none_in_ward') }}</p>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="beds">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_beds') }}</th>
                        <th>{{ __('facility_admin.col_status') }}</th>
                        <th aria-hidden="true"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($beds as $bed)
                    <tr data-bed="{{ $bed->id }}" data-bed-status="{{ $bed->status }}">
                        <td><span class="od-table__name">{{ $bed->bed_number }}</span></td>
                        <td>
                            <span class="od-pill od-pill--{{ $bed->status === 'available' ? 'ready' : ($bed->status === 'occupied' ? 'pending' : 'neutral') }}">{{ $bedStatus($bed->status) }}</span>
                        </td>
                        <td class="od-table__actions">
                            @if($bed->status === \App\Services\Portal\FacilityAdminDeskService::BED_OUT_OF_SERVICE)
                                <form method="POST" action="{{ route('portals.facility.admin.wards.bed.service', [$w['id'], $bed->id]) }}" class="odc-inline">
                                    @csrf
                                    <input type="hidden" name="in_service" value="1">
                                    <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="wrench"></i> {{ __('facility_admin.bed_set_in_service') }}</button>
                                </form>
                            @elseif($bed->status === \App\Services\Portal\FacilityAdminDeskService::BED_IN_SERVICE)
                                <form method="POST" action="{{ route('portals.facility.admin.wards.bed.service', [$w['id'], $bed->id]) }}" class="odc-inline">
                                    @csrf
                                    <input type="hidden" name="in_service" value="0">
                                    <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="wrench"></i> {{ __('facility_admin.bed_set_out_of_service') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.facility.admin.wards.beds', $w['id']) }}" class="odc-form odc-form--range odc-links">
            @csrf
            <div>
                <label class="odc-label" for="count-{{ $w['id'] }}">{{ __('facility_admin.beds_add_count') }}</label>
                <input type="number" id="count-{{ $w['id'] }}" name="count" class="od-input" min="1" max="200" value="1" required>
            </div>
            <div>
                <label class="odc-label" for="prefix-{{ $w['id'] }}">{{ __('facility_admin.beds_add_prefix') }}</label>
                <input type="text" id="prefix-{{ $w['id'] }}" name="prefix" class="od-input" maxlength="10" pattern="[A-Za-z0-9]+" placeholder="A">
            </div>
            <div>
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('facility_admin.beds_add_submit') }}</button>
            </div>
        </form>
        <p class="odc-hint">{{ __('facility_admin.beds_add_prefix_help') }}</p>
    </div>
</section>
@endforeach
</div>

{{-- ── Create ward ──────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.ward_create_title') }}</h2>
    </header>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.facility.admin.wards.create') }}">
            @csrf
            <div class="odc-form">
                <div>
                    <label class="odc-label" for="ward-name">{{ __('facility_admin.ward_name') }}</label>
                    <input type="text" id="ward-name" name="name" class="od-input" maxlength="120" value="{{ old('name') }}" required>
                    @error('name')<p class="odc-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="odc-label" for="ward-type">{{ __('facility_admin.ward_type') }}</label>
                    <select id="ward-type" name="ward_type" class="od-select" required>
                        @foreach($wardTypes as $type)
                            <option value="{{ $type }}" @selected(old('ward_type') === $type)>{{ $typeLabel($type) }}</option>
                        @endforeach
                    </select>
                    @error('ward_type')<p class="odc-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="odc-label" for="ward-capacity">{{ __('facility_admin.ward_capacity') }}</label>
                    <input type="number" id="ward-capacity" name="capacity" class="od-input" min="0" max="1000" value="{{ old('capacity', 0) }}" required>
                    @error('capacity')<p class="odc-error">{{ $message }}</p>@enderror
                    <p class="odc-hint">{{ __('facility_admin.ward_capacity_help') }}</p>
                </div>
            </div>
            <div class="odc-form-foot">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('facility_admin.ward_create_submit') }}</button>
            </div>
        </form>
    </div>
</section>

</div>
@endsection
