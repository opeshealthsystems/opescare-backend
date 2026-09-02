@extends('layouts.portal')
@section('title', __('public.adm_codemap_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_codemap_idx_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_codemap_idx_breadcrumb_section'))

@section('content')

<div class="page-head">
    <h2><i data-lucide="git-merge"></i> {{ __('public.adm_codemap_idx_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.code_mappings.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('public.adm_codemap_idx_btn_add') }}</a>
</div>
<p class="td-muted mb-6">{{ __('public.adm_codemap_idx_desc') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats --}}
<div class="stat-grid mb-6">
    <div class="stat-card"><div class="stat-card__value">{{ $stats['total'] }}</div><div class="stat-card__label">{{ __('public.adm_codemap_idx_stat_total') }}</div></div>
    <div class="stat-card stat-card--success"><div class="stat-card__value">{{ $stats['approved'] }}</div><div class="stat-card__label">{{ __('public.adm_codemap_idx_stat_approved') }}</div></div>
    <div class="stat-card stat-card--warning"><div class="stat-card__value">{{ $stats['pending'] }}</div><div class="stat-card__label">{{ __('public.adm_codemap_idx_stat_pending') }}</div></div>
    <div class="stat-card"><div class="stat-card__value">{{ $stats['loinc'] }}</div><div class="stat-card__label">LOINC</div></div>
    <div class="stat-card"><div class="stat-card__value">{{ $stats['icd10'] }}</div><div class="stat-card__label">ICD-10</div></div>
    <div class="stat-card"><div class="stat-card__value">{{ $stats['atc'] }}</div><div class="stat-card__label">ATC</div></div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('public.adm_codemap_idx_ph_search') }}" aria-label="{{ __('public.aria_search_mappings') }}">
    </label>
    <select name="system" class="filter-select" aria-label="{{ __('public.aria_system') }}">
        <option value="">{{ __('public.adm_codemap_idx_filter_all_systems') }}</option>
        @foreach($systems as $sys)
        <option value="{{ $sys }}" {{ $system === $sys ? 'selected' : '' }}>{{ strtoupper($sys) }}</option>
        @endforeach
    </select>
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}">
        <option value="">{{ __('public.adm_codemap_idx_filter_all_statuses') }}</option>
        @foreach($statuses as $s)
        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <select name="resource_type" class="filter-select" aria-label="{{ __('admin_extra.aria_type', [], app()->getLocale()) ?: 'Type' }}">
        <option value="">{{ __('public.adm_codemap_idx_filter_all_types') }}</option>
        @foreach($resourceTypes as $rt)
        <option value="{{ $rt }}" {{ $resourceType === $rt ? 'selected' : '' }}>{{ $rt }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_codemap_idx_btn_filter') }}</button>
    <a href="{{ route('portals.admin.code_mappings.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_codemap_idx_btn_clear') }}</a>
</form>

{{-- Table --}}
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_codemap_idx_col_local_code') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_local_name') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_system') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_std_code') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_std_display') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_type') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_confidence') }}</th>
                    <th>{{ __('public.adm_codemap_idx_col_status') }}</th>
                    <th class="row-actions">{{ __('public.adm_codemap_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mappings as $mapping)
                <tr>
                    <td data-label="{{ __('public.adm_codemap_idx_col_local_code') }}"><span class="mono td-strong">{{ $mapping->local_code }}</span></td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_local_name') }}" title="{{ $mapping->local_name }}">{{ $mapping->local_name ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_system') }}"><span class="badge badge-primary">{{ strtoupper($mapping->standard_system) }}</span></td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_std_code') }}"><span class="mono">{{ $mapping->standard_code }}</span></td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_std_display') }}" title="{{ $mapping->standard_display }}">{{ $mapping->standard_display ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_type') }}">{{ $mapping->resource_type }}</td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_confidence') }}">{{ ucfirst($mapping->mapping_confidence) }}</td>
                    <td data-label="{{ __('public.adm_codemap_idx_col_status') }}"><span class="badge {{ $mapping->statusBadgeClass() }}">@enum($mapping->status)</span></td>
                    <td class="row-actions" data-label="{{ __('public.adm_codemap_idx_col_actions') }}">
                        @if($mapping->isPending())
                        <form method="POST" action="{{ route('portals.admin.code_mappings.approve', $mapping) }}" class="inline-form">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm"><i data-lucide="check"></i> {{ __('public.adm_codemap_idx_btn_approve') }}</button>
                        </form>
                        <form method="POST" action="{{ route('portals.admin.code_mappings.reject', $mapping) }}" class="inline-form">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="x"></i> {{ __('public.adm_codemap_idx_btn_reject') }}</button>
                        </form>
                        @endif
                        <button type="button" class="btn btn-ghost btn-sm" onclick="opOpenModal('delete-{{ $mapping->id }}')">{{ __('public.adm_codemap_idx_btn_delete') }}</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="td-muted empty-cell">{{ __('public.adm_codemap_idx_empty') }} <a href="{{ route('portals.admin.code_mappings.create') }}">{{ __('public.adm_codemap_idx_empty_link') }}</a></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mappings->hasPages())
    <div class="panel-body">{{ $mappings->links() }}</div>
    @endif
</div>

{{-- Delete confirm modals --}}
@foreach($mappings as $mapping)
<div id="delete-{{ $mapping->id }}" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-{{ $mapping->id }}-title">
        <h3 class="modal__title" id="delete-{{ $mapping->id }}-title"><i data-lucide="trash-2"></i> {{ __('public.adm_codemap_idx_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.code_mappings.destroy', $mapping) }}">
            @csrf @method('DELETE')
            <div class="modal__body"><p>{{ __('public.adm_codemap_idx_modal_title') }} <strong>{{ $mapping->local_code }}</strong>? {{ __('public.adm_codemap_idx_modal_warning') }}</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-{{ $mapping->id }}')">{{ __('public.adm_codemap_idx_modal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.adm_codemap_idx_modal_btn_delete') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
