@extends('layouts.portal')
@section('title', __('terminology.index_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.index_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('terminology.index_title') }}</h2>
    <a href="{{ route('portals.admin.terminology.log') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="scroll-text"></i> {{ __('terminology.nav_log') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('terminology.index_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- The standing state of the queue. "0 approved" is a fact worth showing
     plainly: a mapping table nobody has ever signed off on is not the same
     thing as an empty one. --}}
<div class="stat-grid mb-6">
    @php $kpis = [
        [__('terminology.kpi_pending'),    $counts['pending']    ?? 0, 'inbox',       'pending'],
        [__('terminology.kpi_approved'),   $counts['approved']   ?? 0, 'check-check', 'approved'],
        [__('terminology.kpi_rejected'),   $counts['rejected']   ?? 0, 'x-circle',    'rejected'],
        [__('terminology.kpi_deprecated'), $counts['deprecated'] ?? 0, 'archive',     'deprecated'],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon, $filter])
    <a href="{{ route('portals.admin.terminology.index', ['status' => $filter]) }}" class="stat-card">
        <div class="stat-card__head">
            <i data-lucide="{{ $icon }}"></i>
            <span class="stat-card__label">{{ $label }}</span>
        </div>
        <div class="stat-card__value">{{ $count }}</div>
    </a>
    @endforeach
</div>

<form method="GET" action="{{ route('portals.admin.terminology.index') }}" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('terminology.filter_status') }}">
        <option value="">{{ __('terminology.filter_all_statuses') }}</option>
        @foreach($statuses as $s)
            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ __('terminology.status_' . $s) }}</option>
        @endforeach
    </select>
    <select name="system" class="filter-select" aria-label="{{ __('terminology.filter_system') }}">
        <option value="">{{ __('terminology.filter_all_systems') }}</option>
        {{-- Code system names (LOINC, ICD-10) are terminology identifiers, not
             UI copy. They are printed, never translated. --}}
        @foreach($systems as $sys)
            <option value="{{ $sys }}" {{ $system === $sys ? 'selected' : '' }}>{{ $sys }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('terminology.btn_filter') }}</button>
    <a href="{{ route('portals.admin.terminology.index') }}" class="btn btn-ghost btn-sm">{{ __('terminology.btn_clear') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($mappings->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="book-open-check"></i></div>
                <h3>{{ __('terminology.empty_heading') }}</h3>
                <p>{{ __('terminology.empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('terminology.col_local_term') }}</th>
                    <th>{{ __('terminology.col_resource_type') }}</th>
                    <th>{{ __('terminology.col_standard') }}</th>
                    <th>{{ __('terminology.col_confidence') }}</th>
                    <th>{{ __('terminology.col_radius') }}</th>
                    <th>{{ __('terminology.col_status') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($mappings as $m)
                    @php
                        $r = $radius[$m->id] ?? ['total' => 0, 'write' => 0, 'conflict' => 0, 'unit_mismatch' => 0, 'already' => 0];
                        $needsJudgement = $m->mapping_confidence === 'review' || filled($m->notes);
                    @endphp
                    <tr class="{{ $needsJudgement && $m->isPending() ? 'row--attention' : '' }}">
                        <td data-label="{{ __('terminology.col_local_term') }}" class="td-strong">
                            {{-- Analyte and test names are clinical vocabulary. Printed as stored. --}}
                            {{ $m->local_name ?: $m->local_code }}
                            <div class="td-muted">
                                <code>{{ $m->local_code }}</code>
                                @if($m->local_unit)
                                    {{-- The unit is part of the mapping, not decoration: the same
                                         analyte in a different unit is a different code. --}}
                                    <span class="badge badge--info">{{ $m->local_unit }}</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="{{ __('terminology.col_resource_type') }}">
                            {{ __('terminology.resource_' . $m->resource_type) }}
                        </td>
                        <td data-label="{{ __('terminology.col_standard') }}">
                            <code>{{ $m->standard_code }}</code>
                            <span class="badge">{{ $m->standard_system }}</span>
                            {{-- LOINC display names are the official terminology strings. --}}
                            <div class="td-muted">{{ $m->standard_display }}</div>
                        </td>
                        <td data-label="{{ __('terminology.col_confidence') }}">
                            @if($m->mapping_confidence === 'review')
                                <span class="badge badge--warning">
                                    <i data-lucide="alert-triangle"></i> {{ __('terminology.confidence_review') }}
                                </span>
                            @else
                                <span class="badge badge--success">{{ __('terminology.confidence_high') }}</span>
                            @endif
                            @if(filled($m->notes))
                                <div class="td-muted">{{ $m->notes }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('terminology.col_radius') }}">
                            <strong>{{ $r['total'] }}</strong>
                            <div class="td-muted">
                                {{ __('terminology.radius_write', ['n' => $r['write']]) }}
                                @if($r['conflict'] > 0)
                                    · <span class="text-danger">{{ __('terminology.radius_conflict', ['n' => $r['conflict']]) }}</span>
                                @endif
                                @if($r['unit_mismatch'] > 0)
                                    · {{ __('terminology.radius_unit_mismatch', ['n' => $r['unit_mismatch']]) }}
                                @endif
                            </div>
                        </td>
                        <td data-label="{{ __('terminology.col_status') }}">
                            <span class="badge {{ $m->statusBadgeClass() }}">{{ __('terminology.status_' . $m->status) }}</span>
                        </td>
                        <td>
                            <a href="{{ route('portals.admin.terminology.show', $m->id) }}" class="btn btn-ghost btn-sm">
                                {{ __('terminology.btn_open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<div class="mt-4">{{ $mappings->links() }}</div>
@endsection
