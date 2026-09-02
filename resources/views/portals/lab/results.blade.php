@extends('layouts.portal')
@section('title', __('public.lab_portal.page_title', [], app()->getLocale()) ?: 'Lab Results')
@section('sidebar_role_badge')
<div class="sidebar-role-badge">
    <i data-lucide="microscope"></i>
    {{ __('public.lab_portal.role_badge', [], app()->getLocale()) ?: 'Laboratory' }}
</div>
@endsection
@section('sidebar_user_role', __('public.lab_portal.role_label', [], app()->getLocale()) ?: 'Lab Technician')
@section('sidebar_nav')
@include('portals.lab._sidebar')
@endsection
@section('breadcrumb_home', __('public.lab_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Lab Portal')
@section('breadcrumb_home_url', route('portals.lab.dashboard'))
@section('breadcrumb_section', __('public.lab_portal.breadcrumb_section_results', [], app()->getLocale()) ?: 'Results')
@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.lab_portal.page_heading_results', [], $l) ?: 'Lab results' }}</h2>
    <p class="page-subtitle">{{ __('public.lab_portal.page_subtitle_results', [], $l) ?: 'View all resulted tests — filter by flag or patient.' }}</p>
</div>

<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="{{ __('public.lab_portal.ph_search_results', [], $l) ?: 'Parameter or patient…' }}" value="{{ request('search') }}" aria-label="{{ __('public.aria_search_results') }}">
    </label>
    <select name="flag" class="filter-select" aria-label="{{ __('public.aria_flag', [], $l) ?: 'Flag' }}" onchange="this.form.submit()">
        <option value="">{{ __('public.lab_portal.filter_all_flags', [], $l) ?: 'All flags' }}</option>
        <option value="H" {{ request('flag') === 'H' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_flag_h', [], $l) ?: 'High' }}</option>
        <option value="HH" {{ request('flag') === 'HH' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_flag_hh', [], $l) ?: 'Critical high' }}</option>
        <option value="L" {{ request('flag') === 'L' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_flag_l', [], $l) ?: 'Low' }}</option>
        <option value="LL" {{ request('flag') === 'LL' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_flag_ll', [], $l) ?: 'Critical low' }}</option>
        <option value="abnormal" {{ request('flag') === 'abnormal' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_flag_abnormal', [], $l) ?: 'Abnormal' }}</option>
        <option value="normal" {{ request('flag') === 'normal' ? 'selected' : '' }}>{{ __('public.lab_portal.filter_flag_normal', [], $l) ?: 'Normal' }}</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.lab_portal.btn_filter', [], $l) ?: 'Filter' }}</button>
    @if(request()->hasAny(['flag','search']))
        <a href="{{ route('portals.lab.results') }}" class="btn btn-ghost btn-sm">{{ __('public.lab_portal.btn_clear', [], $l) ?: 'Clear' }}</a>
    @endif
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.lab_portal.col_parameter', [], $l) ?: 'Parameter' }}</th>
                    <th>{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}</th>
                    <th>{{ __('public.lab_portal.col_value', [], $l) ?: 'Value' }}</th>
                    <th>{{ __('public.lab_portal.col_reference', [], $l) ?: 'Reference' }}</th>
                    <th>{{ __('public.lab_portal.col_flag', [], $l) ?: 'Flag' }}</th>
                    <th>{{ __('public.lab_portal.col_resulted_at', [], $l) ?: 'Resulted at' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $result)
                <tr class="{{ $result->isAbnormal() ? 'row-emergency' : '' }}">
                    <td data-label="{{ __('public.lab_portal.col_parameter', [], $l) ?: 'Parameter' }}" class="td-strong">{{ $result->parameter_name }}</td>
                    <td data-label="{{ __('public.lab_portal.col_patient', [], $l) ?: 'Patient' }}">{{ $result->patient?->full_name ?? '—' }}</td>
                    <td data-label="{{ __('public.lab_portal.col_value', [], $l) ?: 'Value' }}">
                        @if($result->isAbnormal())<span class="badge badge-danger">{{ $result->value }} {{ $result->unit }}</span>
                        @else<span class="td-strong">{{ $result->value }} {{ $result->unit }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.lab_portal.col_reference', [], $l) ?: 'Reference' }}" class="td-muted">{{ $result->reference_range ?? '—' }}</td>
                    <td data-label="{{ __('public.lab_portal.col_flag', [], $l) ?: 'Flag' }}"><span class="badge badge-{{ $result->isAbnormal() ? 'danger' : 'success' }}">{{ $result->flagLabel() }}</span></td>
                    <td data-label="{{ __('public.lab_portal.col_resulted_at', [], $l) ?: 'Resulted at' }}" class="td-muted">{{ $result->resulted_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.lab_portal.no_results', [], $l) ?: 'No results found.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $results->links() }}</div>
</div>

@endsection
