@extends('layouts.portal')

@section('title', __('public.stf_immun_title') . ' — OpesCare Staff Portal')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.stf_immun_title'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_immun_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.immunizations.record') }}" class="btn btn-primary">
        <i data-lucide="syringe"></i>
        {{ __('public.stf_immun_record_btn') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.stf_immun_subtitle') }}</p>

<!-- Filters -->
<div class="panel mb-6">
    <form method="GET" action="{{ route('portals.staff.immunizations') }}" class="panel-body">
        <div class="filter-bar">
            <label class="filter-search">
                <i data-lucide="search"></i>
                <input type="text" name="patient_id" placeholder="{{ __('public.stf_immun_ph_patient_id') }}" value="{{ request('patient_id') }}" aria-label="{{ __('public.aria_filter_patient_hid') }}">
            </label>
            <input type="text" name="facility_id" class="filter-search" placeholder="{{ __('public.stf_immun_ph_facility_id') }}" value="{{ request('facility_id') }}" aria-label="{{ __('public.aria_filter_facility_id') }}">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_immun_filter_btn') }}</button>
            <a href="{{ route('portals.staff.immunizations') }}" class="btn btn-secondary btn-sm"><i data-lucide="x"></i> {{ __('public.stf_immun_clear_btn') }}</a>
        </div>
    </form>
</div>

<!-- Two-panel layout -->
<div class="grid-2">

    <!-- Records Panel -->
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="clipboard-list"></i>
                {{ __('public.stf_immun_records_panel') }}
            </h2>
            <span class="badge badge-teal">{{ count($records) }}</span>
        </div>

        @if(count($records) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="syringe"></i></div>
                <h3>{{ __('public.stf_immun_no_records_title') }}</h3>
                <p>{{ __('public.stf_immun_no_records_desc') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table" aria-label="{{ __('public.aria_immunization_records') }}">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_immun_col_vaccine') }}</th>
                            <th>{{ __('public.stf_immun_col_patient') }}</th>
                            <th>{{ __('public.stf_immun_col_date_given') }}</th>
                            <th>{{ __('public.stf_immun_col_dose') }}</th>
                            <th>{{ __('public.stf_immun_col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                        <tr>
                            <td data-label="{{ __('public.stf_immun_col_vaccine') }}">
                                <span class="td-strong">{{ $record->vaccine_name ?? '—' }}</span>
                                @if(!empty($record->vaccine_code))
                                <div class="td-muted">{{ $record->vaccine_code }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_patient') }}">
                                <span class="td-mono">{{ $record->patient_id }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_date_given') }}">
                                <span class="td-muted">
                                    {{ $record->administered_at ? \Carbon\Carbon::parse($record->administered_at)->format('d M Y') : '—' }}
                                </span>
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_dose') }}">
                                <span class="badge badge-primary">{{ $record->dose_number ?? '1' }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_status') }}">
                                @php
                                    $stCls = match($record->status ?? 'completed') {
                                        'completed'  => 'badge-success',
                                        'historical' => 'badge-neutral',
                                        'verified'   => 'badge-teal',
                                        default      => 'badge-warning',
                                    };
                                @endphp
                                <span class="badge {{ $stCls }}">@enum($record->status ?? 'completed')</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Schedule Panel -->
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="calendar-clock"></i>
                {{ __('public.stf_immun_schedule_panel') }}
            </h2>
            <span class="badge badge-warning">{{ count($schedule) }}</span>
        </div>

        @if(count($schedule) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="calendar-check-2"></i></div>
                <h3>{{ __('public.stf_immun_schedule_empty_title') }}</h3>
                <p>{{ __('public.stf_immun_schedule_empty_desc') }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table" aria-label="{{ __('public.aria_immunization_schedule') }}">
                    <thead>
                        <tr>
                            <th>{{ __('public.stf_immun_col_vaccine') }}</th>
                            <th>{{ __('public.stf_immun_col_patient') }}</th>
                            <th>{{ __('public.stf_immun_col_due_date') }}</th>
                            <th>{{ __('public.stf_immun_col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedule as $item)
                        <tr>
                            <td data-label="{{ __('public.stf_immun_col_vaccine') }}">
                                <span class="td-strong">{{ $item->vaccine_name ?? '—' }}</span>
                                <div class="td-muted">{{ __('staff_clinical.lbl_dose_n', ['n' => $item->dose_number ?? '1'], app()->getLocale()) ?: ('Dose ' . ($item->dose_number ?? '1')) }}</div>
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_patient') }}">
                                <span class="td-mono">{{ $item->patient_id }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_due_date') }}">
                                <span class="td-muted">
                                    {{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('d M Y') : '—' }}
                                </span>
                            </td>
                            <td data-label="{{ __('public.stf_immun_col_status') }}">
                                @php
                                    $stCls = match($item->status ?? 'due') {
                                        'overdue' => 'badge-danger',
                                        'due'     => 'badge-warning',
                                        default   => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $stCls }}">@enum($item->status ?? 'due')</span>
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
