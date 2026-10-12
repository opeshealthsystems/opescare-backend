@extends('layouts.portal')

@section('title', __('public.staff_portal.page_heading_search', [], app()->getLocale()))
@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.breadcrumb_section_search', [], app()->getLocale()))

@php $l = app()->getLocale(); @endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.staff_portal.page_heading_search', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.page_subtitle_search', [], $l) }}</p>
    </div>
</div>

{{-- Search Bar --}}
<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="q" value="{{ $query }}"
            placeholder="{{ __('public.staff_portal.ph_search_global', [], $l) }}"
            aria-label="{{ __('public.staff_portal.search_empty_title', [], $l) }}"
            autofocus>
    </label>
    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_portal.btn_search', [], $l) }}</button>
    @if($query)
            @endif
</form>

@if($query === '')
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="search"></i></div>
        <h3>{{ __('public.staff_portal.search_empty_title', [], $l) }}</h3>
        <p>{{ __('public.staff_portal.search_empty_desc', [], $l) }}</p>
        <div class="row-actions-inline mt-6">
            @foreach([
                __('public.staff_portal.search_cat_facilities', [], $l),
                __('public.staff_portal.search_cat_medicines', [], $l),
                __('public.staff_portal.search_cat_lab_tests', [], $l),
                __('public.staff_portal.search_cat_documents', [], $l),
                __('public.staff_portal.search_cat_partners', [], $l),
            ] as $cat)
                <span class="badge badge-neutral">{{ $cat }}</span>
            @endforeach
        </div>
    </div>

@elseif($total === 0)
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
        <h3>{{ __('public.staff_portal.search_no_results_title', [], $l) }} "{{ $query }}"</h3>
        <p>{{ __('public.staff_portal.search_no_results_desc', [], $l) }}</p>
    </div>

@else
    <div class="filter-bar">
        <span class="td-muted">
            <strong class="kv-strong">{{ $total }}</strong> {{ __('public.staff_portal.lbl_results_for', [], $l) }}
            <strong class="kv-strong">"{{ $query }}"</strong>
        </span>
        @foreach($counts as $type => $cnt)
            <span class="badge badge-neutral badge-sm">{{ \Illuminate\Support\Facades\Lang::has('staff.desk.search_types.' . $type) ? __('staff.desk.search_types.' . $type) : ucfirst(str_replace('_',' ',$type)) }}: {{ $cnt }}</span>
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
                {{ \Illuminate\Support\Facades\Lang::has('staff.desk.search_types.' . $type) ? __('staff.desk.search_types.' . $type) : ucfirst(str_replace('_', ' ', $type)) }}
                <span class="badge badge-neutral badge-sm">{{ $items->count() }}</span>
            </h3>
        </div>
        <div class="panel-body panel-body--flush">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('public.staff_portal.col_title', [], $l) }}</th>
                        <th>{{ __('public.staff_portal.col_details', [], $l) }}</th>
                        <th>{{ __('public.staff_portal.col_metadata', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td data-label="{{ __('public.staff_portal.col_title', [], $l) }}">
                                <span class="cell-with-icon">
                                    <i data-lucide="{{ $typeIcon[$type] ?? 'circle' }}"></i>
                                    <span class="td-strong">{{ $item['title'] }}</span>
                                </span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_details', [], $l) }}" class="td-muted">{{ $item['subtitle'] ?? '—' }}</td>
                            <td data-label="{{ __('public.staff_portal.col_metadata', [], $l) }}">
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
