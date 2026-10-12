@extends('layouts.portal')
@section('title', __('public.stf_import_audit_log'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_audit_log'))

@section('content')

<div class="page-head">
    <div>
        <h2>{{ __('public.stf_import_audit_log') }}</h2>
        <p class="page-subtitle">{{ $job->original_filename }} · {{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}</p>
    </div>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_back') }}</a>
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($job->auditEvents->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="scroll-text"></i></div>
                <h3>{{ __('public.stf_import_no_audit_title') }}</h3>
                <p>{{ __('public.stf_import_no_audit_desc') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_import_col_status') }}</th>
                            <th>{{ __('public.stf_import_col_created') }}</th>
                            <th>{{ __('public.stf_import_col_message') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->auditEvents->sortBy('occurred_at') as $event)
                        <tr>
                            <td data-label="{{ __('public.stf_import_col_status') }}">
                                <span class="td-strong">@enum($event->action)</span>
                                @if($event->actor_id)
                                    <div class="td-muted">{{ __('staff_data.audit_by', ['id' => $event->actor_id]) }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_import_col_created') }}" class="td-muted">
                                {{ \Carbon\Carbon::parse($event->occurred_at)->translatedFormat('M d, Y H:i:s') }}
                            </td>
                            <td data-label="{{ __('public.stf_import_col_message') }}">
                                @if($event->details)
                                    <pre class="mono">{{ json_encode($event->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
