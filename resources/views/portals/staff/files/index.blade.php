@extends('layouts.portal')
@section('title', __('public.stf_files_title'))
@section('breadcrumb_home', __('staff_data.bc_home', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.stf_files_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('public.stf_files_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.files.create', ['resource_type' => $resourceType, 'resource_id' => $resourceId]) }}"
       class="btn btn-primary btn-sm">
        <i data-lucide="upload"></i> {{ __('public.stf_files_upload_btn') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('public.stf_files_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Resource filter bar --}}
<form method="GET" action="{{ route('portals.staff.files.index') }}" class="filter-bar">
    <select name="resource_type" class="filter-select">
        @foreach(['patient','visit','triage_record','clinical_note','invoice','support_ticket'] as $rt)
            <option value="{{ $rt }}" {{ $resourceType === $rt ? 'selected' : '' }}>@enum($rt, 'resource_type')</option>
        @endforeach
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="resource_id" value="{{ $resourceId }}" placeholder="{{ __('public.stf_files_placeholder_uuid') }}">
    </label>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_files_filter_btn') }}</button>
</form>

@if($resourceId && $attachments->isNotEmpty())
{{-- Attachments for specific resource --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title">
            <i data-lucide="paperclip"></i>
            {{ __('public.stf_files_attachments_for') }} @enum($resourceType, 'resource_type')
        </h3>
        <span class="badge badge-neutral">{{ $attachments->count() }}</span>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.stf_files_col_file') }}</th><th>{{ __('public.stf_files_col_category') }}</th><th>{{ __('public.stf_files_col_description') }}</th><th>{{ __('public.stf_files_col_size') }}</th><th>{{ __('public.stf_files_col_uploaded_by') }}</th><th>{{ __('public.stf_files_col_date') }}</th><th>{{ __('public.stf_files_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($attachments as $att)
                    <tr>
                        <td data-label="{{ __('public.stf_files_col_file') }}">
                            @php
                                $mime = $att->fileAsset->mime_type ?? '';
                                $icon = str_contains($mime,'pdf') ? 'file-text' : (str_contains($mime,'image') ? 'image' : 'file');
                            @endphp
                            <span class="cell-with-icon">
                                <i data-lucide="{{ $icon }}"></i>
                                <span class="td-strong">{{ $att->fileAsset->original_name ?? '—' }}</span>
                            </span>
                        </td>
                        <td data-label="{{ __('public.stf_files_col_category') }}">
                            @if($att->category)
                                <span class="badge badge-neutral">{{ $categories[$att->category] ?? $att->category }}</span>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.stf_files_col_description') }}" class="td-muted">{{ $att->description ?? '—' }}</td>
                        <td data-label="{{ __('public.stf_files_col_size') }}" class="td-muted">{{ $att->fileAsset?->humanSize() ?? '—' }}</td>
                        <td data-label="{{ __('public.stf_files_col_uploaded_by') }}" class="td-muted">{{ $att->fileAsset->uploaded_by ?? '—' }}</td>
                        <td data-label="{{ __('public.stf_files_col_date') }}" class="td-muted">{{ \Carbon\Carbon::parse($att->created_at)->format('M d, Y') }}</td>
                        <td data-label="{{ __('public.stf_files_col_actions') }}">
                            <div class="row-actions-inline">
                            <a href="{{ route('portals.staff.files.download', $att->file_asset_id) }}"
                               class="btn btn-ghost btn-xs">
                                <i data-lucide="download"></i> {{ __('public.stf_files_download') }}
                            </a>
                            <form method="POST" action="{{ route('portals.staff.files.destroy', $att->id) }}" class="inline-form"
                                  onsubmit="return confirm('{{ __('staff_data.files_confirm_remove', [], app()->getLocale()) ?: 'Remove this attachment?' }}');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@elseif($resourceId)
<div class="panel mb-6">
    <div class="panel-body">
        <div class="empty-state">
            <p>{{ __('public.stf_files_no_attachments') }} @enum($resourceType, 'resource_type').</p>
        </div>
    </div>
</div>
@endif

{{-- All facility files --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">
            <i data-lucide="folder-open"></i>
            {{ __('public.stf_files_all_facility') }}
        </h3>
        <span class="badge badge-neutral">{{ __('staff_data.files_total_suffix', ['n' => $assets->total()], app()->getLocale()) ?: $assets->total().' total' }}</span>
    </div>
    <div class="panel-body panel-body--flush">
        @if($assets->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="folder-open"></i></div>
                <p>{{ __('public.stf_files_no_files') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.stf_files_col_filename') }}</th><th>{{ __('public.stf_files_col_type') }}</th><th>{{ __('public.stf_files_col_size') }}</th><th>{{ __('public.stf_files_col_checksum') }}</th><th>{{ __('public.stf_files_col_uploaded_by') }}</th><th>{{ __('public.stf_files_col_date') }}</th><th>{{ __('public.stf_files_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($assets as $asset)
                    <tr>
                        <td data-label="{{ __('public.stf_files_col_filename') }}" class="td-strong">{{ $asset->original_name }}</td>
                        <td data-label="{{ __('public.stf_files_col_type') }}"><span class="mono">{{ $asset->mime_type ?? '—' }}</span></td>
                        <td data-label="{{ __('public.stf_files_col_size') }}" class="td-muted">{{ $asset->humanSize() }}</td>
                        <td data-label="{{ __('public.stf_files_col_checksum') }}" class="td-muted">
                            @if($asset->checksum)
                                <span class="mono" title="{{ $asset->checksum }}">{{ substr($asset->checksum,0,12) }}…</span>
                            @else —
                            @endif
                        </td>
                        <td data-label="{{ __('public.stf_files_col_uploaded_by') }}" class="td-muted">{{ $asset->uploaded_by ?? '—' }}</td>
                        <td data-label="{{ __('public.stf_files_col_date') }}" class="td-muted">{{ \Carbon\Carbon::parse($asset->created_at)->format('M d, Y') }}</td>
                        <td data-label="{{ __('public.stf_files_col_actions') }}">
                            <a href="{{ route('portals.staff.files.download', $asset->id) }}"
                               class="btn btn-ghost btn-xs">
                                <i data-lucide="download"></i> {{ __('public.stf_files_download') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            {{ $assets->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
