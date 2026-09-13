@extends('layouts.portal')
@section('title', __('governance_desk.index_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('governance_desk.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('governance_desk.index_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('governance_desk.index_title') }}</h2>
    <a href="{{ route('portals.admin.governance.log') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="scroll-text"></i> {{ __('governance_desk.nav_log') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('governance_desk.index_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- The age of the oldest untouched request. A statutory clock starts when the
     patient files, not when somebody notices, so the wait is stated plainly. --}}
@if(!is_null($oldestDays))
<div class="alert alert-warning mb-6">
    <i data-lucide="clock"></i>
    <div>{{ __('governance_desk.oldest_pending', ['days' => $oldestDays]) }}</div>
</div>
@endif

<div class="stat-grid mb-6">
    @php $kpis = [
        [__('governance_desk.kpi_export_pending'),     $exportCounts['pending']      ?? 0, 'inbox'],
        [__('governance_desk.kpi_export_approved'),    $exportCounts['approved']     ?? 0, 'check-check'],
        [__('governance_desk.kpi_correction_pending'), $correctionCounts['pending']  ?? 0, 'file-pen-line'],
        [__('governance_desk.kpi_correction_refused'), $correctionCounts['rejected'] ?? 0, 'x-circle'],
    ]; @endphp
    @foreach($kpis as [$label, $count, $icon])
    <div class="stat-card">
        <div class="stat-card__head">
            <i data-lucide="{{ $icon }}"></i>
            <span class="stat-card__label">{{ $label }}</span>
        </div>
        <div class="stat-card__value">{{ $count }}</div>
    </div>
    @endforeach
</div>

{{-- Status only. There is deliberately no patient name or Health-ID box: this
     desk lists requests, and must not double as a way to find out whether a
     given person is a patient here. --}}
<form method="GET" action="{{ route('portals.admin.governance.index') }}" class="filter-bar">
    <select name="status" class="filter-select" aria-label="{{ __('governance_desk.filter_status') }}">
        <option value="">{{ __('governance_desk.filter_all') }}</option>
        @foreach($statuses as $s)
            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ __('governance_desk.status_' . $s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('governance_desk.btn_filter') }}</button>
    <a href="{{ route('portals.admin.governance.index') }}" class="btn btn-ghost btn-sm">{{ __('governance_desk.btn_clear') }}</a>
</form>

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="download"></i> {{ __('governance_desk.section_exports') }}</h3>
    </div>
    <div class="panel-body panel-body--flush">
        @if($exports->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="inbox"></i></div>
                <h3>{{ __('governance_desk.empty_exports_heading') }}</h3>
                <p>{{ __('governance_desk.empty_exports_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_filed') }}</th>
                    <th>{{ __('governance_desk.col_type') }}</th>
                    <th>{{ __('governance_desk.col_status') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($exports as $export)
                    <tr>
                        <td>{{ optional($export->created_at)->format('Y-m-d H:i') }}</td>
                        <td><code>{{ $export->export_type }}</code></td>
                        <td><span class="badge">{{ __('governance_desk.status_' . $export->status) }}</span></td>
                        <td>
                            <a href="{{ route('portals.admin.governance.exports.show', $export->id) }}" class="btn btn-ghost btn-sm">
                                <i data-lucide="arrow-right"></i> {{ __('governance_desk.btn_open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $exports->links() }}</div>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-pen-line"></i> {{ __('governance_desk.section_corrections') }}</h3>
    </div>
    <div class="panel-body panel-body--flush">
        @if($corrections->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="file-check-2"></i></div>
                <h3>{{ __('governance_desk.empty_corrections_heading') }}</h3>
                <p>{{ __('governance_desk.empty_corrections_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_filed') }}</th>
                    <th>{{ __('governance_desk.col_resource') }}</th>
                    <th>{{ __('governance_desk.col_status') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                </tr></thead>
                <tbody>
                @foreach($corrections as $correction)
                    <tr>
                        <td>{{ optional($correction->created_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <code>{{ $correction->resource_type }}</code>
                            {{-- A request this desk cannot honour is marked HERE, in the
                                 queue, not discovered after a reviewer clicks approve. --}}
                            @if(($refusals[$correction->id] ?? null) === 'governance_desk.refusal_type_unsupported')
                                <span class="badge badge--warning">{{ __('governance_desk.refusal_heading') }}</span>
                            @endif
                        </td>
                        <td><span class="badge">{{ __('governance_desk.status_' . $correction->status) }}</span></td>
                        <td>
                            <a href="{{ route('portals.admin.governance.corrections.show', $correction->id) }}" class="btn btn-ghost btn-sm">
                                <i data-lucide="arrow-right"></i> {{ __('governance_desk.btn_open') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $corrections->links() }}</div>
        @endif
    </div>
</div>
@endsection
