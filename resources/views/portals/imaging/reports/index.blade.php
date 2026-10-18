@extends('layouts.portal')

@section('title', __('imaging_report.queue_title'))
@section('breadcrumb_home', __('imaging_desk.title'))
@section('breadcrumb_home_url', \Illuminate\Support\Facades\Route::has('portals.imaging.index') ? route('portals.imaging.index') : route('portals.imaging.reports.index'))
@section('breadcrumb_section', __('imaging_report.queue_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('imaging_report.queue_title') }}</h1>
        <p class="page-subtitle">{{ __('imaging_report.queue_subtitle') }}</p>
    </div>
    @if(\Illuminate\Support\Facades\Route::has('portals.imaging.index'))
    <a href="{{ route('portals.imaging.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('imaging_report.back_to_worklist') }}
    </a>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── Completed studies with no final report ─────────────────────────── --}}
<div class="panel mb-4" data-section="awaiting-report">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="scan-line"></i> {{ __('imaging_report.queue_awaiting') }}
            <span class="badge badge-neutral">{{ $awaiting->count() }}</span></h2>
    </div>
    @if($awaiting->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="check-circle-2"></i></div>
                <h3>{{ __('imaging_report.queue_empty') }}</h3>
            </div>
        </div>
    @else
        <div class="panel-body"><p class="td-muted">{{ __('imaging_report.queue_awaiting_hint') }}</p></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('imaging_report.col_patient') }}</th>
                        <th>{{ __('imaging_report.col_study') }}</th>
                        <th>{{ __('imaging_report.col_accession') }}</th>
                        <th>{{ __('imaging_report.col_completed') }}</th>
                        <th>{{ __('imaging_report.col_report') }}</th>
                        <th class="row-actions">{{ __('imaging_report.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($awaiting as $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $draft = $drafts[$order->id] ?? null;
                    @endphp
                    <tr>
                        <td data-label="{{ __('imaging_report.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('imaging_report.col_study') }}">
                            <span class="td-strong">{{ __('imaging_report.modality.' . ($order->modality ?: 'other')) }}</span>
                            <div class="td-muted">{{ $order->body_part ?: '—' }}</div>
                        </td>
                        <td data-label="{{ __('imaging_report.col_accession') }}" class="td-muted">{{ $order->accession_number ?: '—' }}</td>
                        <td data-label="{{ __('imaging_report.col_completed') }}" class="td-muted">{{ $order->completed_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('imaging_report.col_report') }}">
                            <span class="badge {{ $draft ? 'badge-warning' : 'badge-neutral' }}">{{ __('imaging_report.status.' . ($draft ? 'draft' : 'none')) }}</span>
                        </td>
                        <td class="row-actions" data-label="{{ __('imaging_report.col_actions') }}">
                            <a href="{{ route('portals.imaging.report.edit', $order->id) }}" class="btn btn-primary btn-sm">
                                <i data-lucide="file-pen-line"></i> {{ $draft ? __('imaging_report.open_report') : __('imaging_report.write_report') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Signed, not yet handed to the patient ──────────────────────────── --}}
<div class="panel" data-section="undistributed">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="send"></i> {{ __('imaging_report.queue_undistributed') }}
            <span class="badge badge-neutral">{{ $undistributed->count() }}</span></h2>
    </div>
    @if($undistributed->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="mail-check"></i></div>
                <h3>{{ __('imaging_report.queue_undistributed_empty') }}</h3>
            </div>
        </div>
    @else
        <div class="panel-body"><p class="td-muted">{{ __('imaging_report.queue_undistributed_hint') }}</p></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('imaging_report.col_patient') }}</th>
                        <th>{{ __('imaging_report.col_study') }}</th>
                        <th>{{ __('imaging_report.col_author') }}</th>
                        <th>{{ __('imaging_report.col_finalized') }}</th>
                        <th>{{ __('imaging_report.col_report') }}</th>
                        <th class="row-actions">{{ __('imaging_report.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($undistributed as $report)
                    @php
                        $p     = $report->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('imaging_report.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('imaging_report.col_study') }}">
                            <span class="td-strong">{{ __('imaging_report.modality.' . $report->modality) }}</span>
                            <div class="td-muted">{{ $report->body_part ?: '—' }}</div>
                        </td>
                        <td data-label="{{ __('imaging_report.col_author') }}">{{ $report->reportedBy?->name ?? __('imaging_report.unknown') }}</td>
                        <td data-label="{{ __('imaging_report.col_finalized') }}" class="td-muted">{{ $report->finalized_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('imaging_report.col_report') }}">
                            <span class="badge badge-success">{{ __('imaging_report.status.' . $report->status) }}</span>
                            <span class="badge badge-neutral">{{ __('imaging_report.version_n', ['n' => (int) $report->version]) }}</span>
                        </td>
                        <td class="row-actions" data-label="{{ __('imaging_report.col_actions') }}">
                            @if($report->imaging_order_id)
                            <a href="{{ route('portals.imaging.report.edit', $report->imaging_order_id) }}" class="btn btn-secondary btn-sm">
                                <i data-lucide="send"></i> {{ __('imaging_report.open_report') }}
                            </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
