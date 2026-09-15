{{-- Group D import: web page over the platform's ComplianceExportService.
     CSVs are rendered on demand and never stored; they carry metadata and counts only — no patient or clinical content. --}}
@extends('layouts.portal')
@section('title', __('security_ops.ce_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.ce_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.ce_title') }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.ce_subtitle') }}</p>

<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-down"></i> {{ __('security_ops.ce_request') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.security.compliance_export.download') }}" class="filter-bar">
            @csrf
            <select name="export_type" class="filter-select" aria-label="{{ __('security_ops.ce_lbl_type') }}">
                @foreach($types as $t)<option value="{{ $t }}">{{ __('security_ops.ce_type_'.$t) }}</option>@endforeach
            </select>
            <label class="form-label" for="ce_from">{{ __('security_ops.ce_lbl_from') }}</label>
            <input id="ce_from" type="date" name="from" class="form-control">
            <label class="form-label" for="ce_to">{{ __('security_ops.ce_lbl_to') }}</label>
            <input id="ce_to" type="date" name="to" class="form-control">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="download"></i> {{ __('security_ops.ce_btn_download') }}</button>
        </form>
        <p class="td-muted text-sm mt-3"><i data-lucide="lock"></i> {{ __('security_ops.ce_no_clinical') }}</p>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('security_ops.ce_recent') }}</h3></div>
    <div class="panel-body panel-body--flush">
        @if($exports->isEmpty())
            <p class="td-muted empty-cell">{{ __('security_ops.ce_empty') }}</p>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.ce_lbl_type') }}</th>
                    <th>{{ __('security_ops.ce_col_filters') }}</th>
                    <th>{{ __('security_ops.ce_col_when') }}</th>
                </tr></thead>
                <tbody>
                @foreach($exports as $export)
                    <tr>
                        <td data-label="{{ __('security_ops.ce_lbl_type') }}">{{ in_array($export->export_type, $types) ? __('security_ops.ce_type_'.$export->export_type) : $export->export_type }}</td>
                        <td data-label="{{ __('security_ops.ce_col_filters') }}" class="td-muted">{{ collect($export->filters ?? [])->map(fn ($v, $k) => $k.': '.$v)->implode(', ') ?: '—' }}</td>
                        <td data-label="{{ __('security_ops.ce_col_when') }}" class="td-muted">{{ $export->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
