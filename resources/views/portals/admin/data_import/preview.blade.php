@extends('layouts.portal')
@section('title', __('public.stf_import_validation_summary'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('staff_data.title_import'))
@section('breadcrumb_home_url', route('portals.admin.data_import.index'))
@section('breadcrumb_section', __('public.stf_import_validation_summary'))

@section('content')

@include('portals.admin.data_import._wizard_steps', ['step' => 3])

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title">{{ __('public.stf_import_validation_summary') }}</h3>
        <span class="badge {{ $job->status === 'validated' ? 'badge-success' : 'badge-danger' }}">@enum($job->status)</span>
    </div>
    <div class="panel-body">
        <p class="td-muted mb-6">
            {{ $job->original_filename }} · {{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}
        </p>
        @php
            $cards = [
                [__('public.stf_import_total_rows'), (int) $job->total_rows,   '',                                                'rows-3'],
                [__('public.stf_import_valid'),      (int) $job->valid_rows,   'stat-card--success',                              'check-circle'],
                [__('public.stf_import_invalid'),    (int) $job->invalid_rows, $job->invalid_rows > 0 ? 'stat-card--danger' : '', 'triangle-alert'],
            ];
        @endphp
        <div class="stat-grid">
            @foreach($cards as [$label, $value, $mod, $icon])
            <div class="stat-card {{ $mod }}">
                <div class="stat-card__head"><i data-lucide="{{ $icon }}"></i></div>
                <div class="stat-card__value">{{ number_format($value) }}</div>
                <div class="stat-card__label">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@if($job->rowErrors->count() > 0)
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title">{{ __('public.stf_import_val_errors_title') }} <span class="badge badge-danger">{{ $job->rowErrors->count() }}</span></h3>
        <span class="td-muted">{{ __('public.stf_import_showing_errors') }}</span>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_import_col_row_no') }}</th>
                        <th>{{ __('public.stf_import_col_field') }}</th>
                        <th>{{ __('public.stf_import_col_error') }}</th>
                        <th>{{ __('public.stf_import_col_message') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($job->rowErrors as $err)
                    <tr>
                        <td data-label="{{ __('public.stf_import_col_row_no') }}">{{ $err->row_number }}</td>
                        <td data-label="{{ __('public.stf_import_col_field') }}"><span class="badge badge-neutral">{{ $err->field ?? '—' }}</span></td>
                        <td data-label="{{ __('public.stf_import_col_error') }}"><span class="mono">{{ $err->error_code }}</span></td>
                        <td data-label="{{ __('public.stf_import_col_message') }}">{{ $err->message }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-body">
        @if($job->canBeApproved())
            <div class="alert alert-success mb-6">
                <i data-lucide="shield-check"></i>
                <div>
                    <strong>{{ __('public.stf_import_ready_title') }}</strong>
                    <p>
                        {{ number_format($job->valid_rows) }} {{ __('public.stf_import_ready_desc') }}
                        @if($job->invalid_rows > 0)
                            {{ number_format($job->invalid_rows) }} {{ __('public.stf_import_invalid_skipped') }}
                        @endif
                        {{ __('public.stf_import_no_undo') }}
                    </p>
                </div>
            </div>
            <div class="row-actions-inline">
                <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_edit_mapping') }}</a>
                <form method="POST" action="{{ route('portals.admin.data_import.approve', $job->id) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"
                        data-confirm="{{ __('staff_data.confirm_approve', ['n' => $job->valid_rows]) }}">
                        <i data-lucide="check-circle"></i>
                        {{ __('public.stf_import_approve_btn') }} {{ number_format($job->valid_rows) }} {{ __('public.stf_import_records_suffix') }}
                    </button>
                </form>
            </div>

        @elseif($job->status === 'validation_failed')
            <div class="alert alert-danger mb-6">
                <i data-lucide="triangle-alert"></i>
                <div>
                    <strong>{{ __('public.stf_import_all_failed_title') }}</strong>
                    <p>{{ __('public.stf_import_all_failed_desc') }}</p>
                </div>
            </div>
            <div class="row-actions-inline">
                <a href="{{ route('portals.admin.data_import.mapping', $job->id) }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_edit_mapping') }}</a>
                <a href="{{ route('portals.admin.data_import.create') }}" class="btn btn-primary btn-sm">{{ __('public.stf_import_reupload') }}</a>
            </div>

        @else
            <div class="row-actions-inline">
                <a href="{{ route('portals.admin.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_back_history') }}</a>
            </div>
        @endif
    </div>
</div>

@endsection
