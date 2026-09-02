@extends('layouts.portal')

@section('title', __('public.staff_portal.page_heading_search', [], app()->getLocale()) ?: 'Global Search')
@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.breadcrumb_section_search', [], app()->getLocale()) ?: 'Global Search')

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.staff_portal.page_heading_search', [], $l) ?: 'Global Search' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.page_subtitle_search', [], $l) ?: 'Search across patients, facilities, medicines, lab tests, documents, and more.' }}</p>
    </div>
</div>

{{-- Search Bar --}}
<form method="GET" action="{{ route('portals.staff.search') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="q" value="{{ $query }}"
            placeholder="{{ __('public.staff_portal.ph_search_global', [], $l) ?: 'Search patients, medicines, facilities, documents…' }}"
            aria-label="{{ __('public.staff_portal.search_empty_title', [], $l) ?: 'Search the platform' }}"
            autofocus>
    </label>
    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_portal.btn_search', [], $l) ?: 'Search' }}</button>
    @if($query)
        <a href="{{ route('portals.staff.search') }}" class="btn btn-ghost btn-sm">{{ __('public.staff_portal.filter_clear', [], $l) ?: 'Clear' }}</a>
    @endif
</form>

@if($query === '')
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="search"></i></div>
        <h3>{{ __('public.staff_portal.search_empty_title', [], $l) ?: 'Search the platform' }}</h3>
        <p>{{ __('public.staff_portal.search_empty_desc', [], $l) ?: 'Enter a keyword above to search across facilities, medicines, lab tests, documents, and partners.' }}</p>
        <div class="row-actions-inline mt-6">
            @foreach([
                __('public.staff_portal.search_cat_facilities', [], $l) ?: 'Facilities',
                __('public.staff_portal.search_cat_medicines', [], $l) ?: 'Medicines',
                __('public.staff_portal.search_cat_lab_tests', [], $l) ?: 'Lab Tests',
                __('public.staff_portal.search_cat_documents', [], $l) ?: 'Documents',
                __('public.staff_portal.search_cat_partners', [], $l) ?: 'Partners',
            ] as $cat)
                <span class="badge badge-neutral">{{ $cat }}</span>
            @endforeach
        </div>
    </div>

@elseif($total === 0)
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
        <h3>{{ __('public.staff_portal.search_no_results_title', [], $l) ?: 'No results for' }} "{{ $query }}"</h3>
        <p>{{ __('public.staff_portal.search_no_results_desc', [], $l) ?: 'Try different keywords or check your spelling.' }}</p>
    </div>

@else
    <div class="filter-bar">
        <span class="td-muted">
            <strong class="kv-strong">{{ $total }}</strong> {{ __('public.staff_portal.lbl_results_for', [], $l) ?: 'result(s) for' }}
            <strong class="kv-strong">"{{ $query }}"</strong>
        </span>
        @foreach($counts as $type => $cnt)
            <span class="badge badge-neutral badge-sm">{{ ucfirst(str_replace('_',' ',$type)) }}: {{ $cnt }}</span>
        @endforeach
    </div>

    @php
        $typeIcon = [
            'patient'   => 'user',
            'facility'  => 'building-2',
            'document'  => 'file-text',
            'medicine'  => 'pill',
            'lab_test'  => 'flask-conical',
            'partner'   => 'handshake',
            'message'   => 'message-square',
        ];
        $typeBadge = [
            'patient'   => 'badge-primary',
            'facility'  => 'badge-neutral',
            'document'  => 'badge-warning',
            'medicine'  => 'badge-success',
            'lab_test'  => 'badge-neutral',
            'partner'   => 'badge-neutral',
            'message'   => 'badge-neutral',
        ];
    @endphp

    @foreach($grouped as $type => $items)
    <div class="panel mb-4">
        <div class="panel-header">
            <h3 class="panel-title">
                <i data-lucide="{{ $typeIcon[$type] ?? 'circle' }}"></i>
                {{ ucfirst($type) }}
                <span class="badge badge-neutral badge-sm">{{ $items->count() }}</span>
            </h3>
        </div>
        <div class="panel-body panel-body--flush">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('public.staff_portal.col_title', [], $l) ?: 'Title' }}</th>
                        <th>{{ __('public.staff_portal.col_details', [], $l) ?: 'Details' }}</th>
                        <th>{{ __('public.staff_portal.col_metadata', [], $l) ?: 'Metadata' }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td data-label="{{ __('public.staff_portal.col_title', [], $l) ?: 'Title' }}">
                                <span class="cell-with-icon">
                                    <i data-lucide="{{ $typeIcon[$type] ?? 'circle' }}"></i>
                                    <span class="td-strong">{{ $item['title'] }}</span>
                                </span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_details', [], $l) ?: 'Details' }}" class="td-muted">{{ $item['subtitle'] ?? '—' }}</td>
                            <td data-label="{{ __('public.staff_portal.col_metadata', [], $l) ?: 'Metadata' }}">
                                @foreach($item['metadata'] ?? [] as $mk => $mv)
                                    @if($mv !== null && $mv !== '')
                                        <span class="td-muted">{{ $mk }}:</span>
                                        <code class="mono">{{ is_array($mv) ? json_encode($mv) : $mv }}</code>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
@endif
@endsection
