@extends('layouts.portal')

@section('title', __('facility_admin.wards_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.wards_title'))

@section('content')

@php
    $wards = $board['wards'];
    $typeLabel = fn (string $type): string => __('facility_admin.ward_types.' . $type) !== 'facility_admin.ward_types.' . $type
        ? __('facility_admin.ward_types.' . $type) : $type;
    $bedStatus = fn (string $status): string => __('facility_admin.bed_status_' . $status) !== 'facility_admin.bed_status_' . $status
        ? __('facility_admin.bed_status_' . $status) : $status;
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('facility_admin.wards_title') }}</h1>
        <p class="page-subtitle">{{ __('facility_admin.wards_subtitle') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="btn btn-secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
    </div>
</div>

{{-- Scope, stated plainly: structure only; admissions are recorded elsewhere. --}}
<div class="alert alert-info mb-4" style="display:flex;gap:.5rem;align-items:flex-start;">
    <i data-lucide="info"></i>
    <span>{{ __('facility_admin.wards_scope_note') }}</span>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4"><i data-lucide="check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i> {{ session('error') }}</div>
@endif

{{-- ── Board ────────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="bed"></i> {{ __('facility_admin.wards_title') }}</h3>
        <span class="badge badge-neutral" data-active-admissions="{{ $board['active_admissions'] }}">
            {{ __('facility_admin.wards_active_admissions') }}: {{ $board['active_admissions'] }}
        </span>
    </div>
    <div class="panel-body">
        @if($wards === [])
            <div class="empty-state">
                <i data-lucide="bed"></i>
                <p>{{ __('facility_admin.wards_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="wards">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_ward') }}</th>
                        <th>{{ __('facility_admin.col_ward_type') }}</th>
                        <th>{{ __('facility_admin.col_ward_capacity') }}</th>
                        <th>{{ __('facility_admin.col_beds') }}</th>
                        <th>{{ __('facility_admin.col_occupied') }}</th>
                        <th>{{ __('facility_admin.col_available') }}</th>
                        <th>{{ __('facility_admin.col_out_of_service') }}</th>
                        <th>{{ __('facility_admin.col_occupancy') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($wards as $w)
                    <tr data-ward="{{ $w['id'] }}">
                        <td data-label="{{ __('facility_admin.col_ward') }}">
                            <span class="td-strong">{{ $w['name'] }}</span>
                            @unless($w['is_active'])<span class="badge badge-neutral">{{ __('facility_admin.ward_inactive') }}</span>@endunless
                        </td>
                        <td data-label="{{ __('facility_admin.col_ward_type') }}">{{ $typeLabel($w['ward_type']) }}</td>
                        <td data-label="{{ __('facility_admin.col_ward_capacity') }}">{{ $w['total_beds'] }}</td>
                        <td data-label="{{ __('facility_admin.col_beds') }}" data-beds>{{ $w['beds'] }}</td>
                        <td data-label="{{ __('facility_admin.col_occupied') }}" data-occupied>{{ $w['occupied'] }}</td>
                        <td data-label="{{ __('facility_admin.col_available') }}" data-available>{{ $w['available'] }}</td>
                        <td data-label="{{ __('facility_admin.col_out_of_service') }}" data-out-of-service>{{ $w['out_of_service'] }}</td>
                        <td data-label="{{ __('facility_admin.col_occupancy') }}">{{ $w['occupancy_pct'] === null ? '—' : $w['occupancy_pct'] . ' %' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Per-ward beds ────────────────────────────────────────────────── --}}
@foreach($wards as $w)
<div class="panel mb-4" data-ward-panel="{{ $w['id'] }}">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="bed-single"></i> {{ $w['name'] }}</h3>
    </div>
    <div class="panel-body">
        @php $beds = $bedsByWard[$w['id']] ?? collect(); @endphp
        @if($beds->isEmpty())
            <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.beds_none_in_ward') }}</p>
        @else
            <div class="table-wrapper" style="margin-bottom:.75rem;">
            <table class="data-table" data-table="beds">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_beds') }}</th>
                        <th>{{ __('facility_admin.col_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($beds as $bed)
                    <tr data-bed="{{ $bed->id }}" data-bed-status="{{ $bed->status }}">
                        <td><span class="td-strong">{{ $bed->bed_number }}</span></td>
                        <td>
                            <span class="badge {{ $bed->status === 'available' ? 'badge-success' : ($bed->status === 'occupied' ? 'badge-warning' : 'badge-neutral') }}">{{ $bedStatus($bed->status) }}</span>
                        </td>
                        <td>
                            @if($bed->status === \App\Services\Portal\FacilityAdminDeskService::BED_OUT_OF_SERVICE)
                                <form method="POST" action="{{ route('portals.facility.admin.wards.bed.service', [$w['id'], $bed->id]) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="in_service" value="1">
                                    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="wrench"></i> {{ __('facility_admin.bed_set_in_service') }}</button>
                                </form>
                            @elseif($bed->status === \App\Services\Portal\FacilityAdminDeskService::BED_IN_SERVICE)
                                <form method="POST" action="{{ route('portals.facility.admin.wards.bed.service', [$w['id'], $bed->id]) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="in_service" value="0">
                                    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="wrench"></i> {{ __('facility_admin.bed_set_out_of_service') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif

        <form method="POST" action="{{ route('portals.facility.admin.wards.beds', $w['id']) }}" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="form-group">
                <label for="count-{{ $w['id'] }}">{{ __('facility_admin.beds_add_count') }}</label>
                <input type="number" id="count-{{ $w['id'] }}" name="count" class="form-control" min="1" max="200" value="1" required>
            </div>
            <div class="form-group">
                <label for="prefix-{{ $w['id'] }}">{{ __('facility_admin.beds_add_prefix') }}</label>
                <input type="text" id="prefix-{{ $w['id'] }}" name="prefix" class="form-control" maxlength="10" pattern="[A-Za-z0-9]+" placeholder="A">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('facility_admin.beds_add_submit') }}</button>
        </form>
        <p class="page-subtitle" style="margin:.5rem 0 0;">{{ __('facility_admin.beds_add_prefix_help') }}</p>
    </div>
</div>
@endforeach

{{-- ── Create ward ──────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="plus-square"></i> {{ __('facility_admin.ward_create_title') }}</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.facility.admin.wards.create') }}" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="form-group">
                <label for="ward-name">{{ __('facility_admin.ward_name') }}</label>
                <input type="text" id="ward-name" name="name" class="form-control" maxlength="120" value="{{ old('name') }}" required>
                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="ward-type">{{ __('facility_admin.ward_type') }}</label>
                <select id="ward-type" name="ward_type" class="form-control" required>
                    @foreach($wardTypes as $type)
                        <option value="{{ $type }}" @selected(old('ward_type') === $type)>{{ $typeLabel($type) }}</option>
                    @endforeach
                </select>
                @error('ward_type')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="ward-capacity">{{ __('facility_admin.ward_capacity') }}</label>
                <input type="number" id="ward-capacity" name="capacity" class="form-control" min="0" max="1000" value="{{ old('capacity', 0) }}" required>
                @error('capacity')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('facility_admin.ward_create_submit') }}</button>
        </form>
        <p class="page-subtitle" style="margin:.5rem 0 0;">{{ __('facility_admin.ward_capacity_help') }}</p>
    </div>
</div>

@endsection
