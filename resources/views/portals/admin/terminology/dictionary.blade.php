@extends('layouts.portal')
@section('title', __('terminology.dict_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.dict_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('terminology.dict_title') }}</h2>
    <a href="{{ route('portals.admin.terminology.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="list-checks"></i> {{ __('terminology.dict_nav_queue') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('terminology.dict_subtitle') }}</p>

{{-- The standing size of each vocabulary, with its release. Reference data
     whose provenance nobody can see is reference data nobody can check. --}}
<div class="stat-grid mb-6">
    @forelse($totals as $system => $row)
    <a href="{{ route('portals.admin.terminology.dictionary', ['system' => $system]) }}" class="stat-card">
        <div class="stat-card__head">
            <i data-lucide="{{ $system === 'LOINC' ? 'flask-conical' : 'stethoscope' }}"></i>
            <span class="stat-card__label">{{ $system }}</span>
        </div>
        <div class="stat-card__value">{{ number_format($row->total) }}</div>
        <div class="stat-card__meta">{{ __('terminology.dict_common_count', ['count' => number_format($row->common)]) }}</div>
    </a>
    @empty
    {{-- Empty is not the same as "no matches", and must not read like it. --}}
    <div class="alert alert-warning">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('terminology.dict_never_imported') }}</div>
    </div>
    @endforelse
</div>

@if($releases->isNotEmpty())
<p class="text-muted text-sm mb-6">
    {{ __('terminology.dict_provenance') }}
    @foreach($releases as $r)
        <span class="pill pill--muted">{{ $r->system }} · {{ $r->source }}@if($r->source_version) · {{ $r->source_version }}@endif</span>
    @endforeach
</p>
@endif

<form method="GET" action="{{ route('portals.admin.terminology.dictionary') }}" class="filter-bar mb-6">
    <input type="search" name="q" value="{{ request('q') }}"
           class="form-control" placeholder="{{ __('terminology.dict_search_placeholder') }}">

    <select name="system" class="form-control">
        <option value="">{{ __('terminology.dict_all_systems') }}</option>
        @foreach(['ICD-10', 'LOINC'] as $s)
        <option value="{{ $s }}" @selected($system === $s)>{{ $s }}</option>
        @endforeach
    </select>

    <label class="checkbox">
        <input type="checkbox" name="common" value="1" @checked(request()->boolean('common'))>
        {{ __('terminology.dict_only_common') }}
    </label>

    <label class="checkbox">
        <input type="checkbox" name="headings" value="1" @checked(request()->boolean('headings'))>
        {{ __('terminology.dict_show_headings') }}
    </label>

    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="search"></i> {{ __('terminology.dict_search') }}
    </button>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('terminology.dict_col_code') }}</th>
                <th>{{ __('terminology.dict_col_system') }}</th>
                <th>{{ __('terminology.dict_col_display') }}</th>
                <th>{{ __('terminology.dict_col_basis') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($codes as $code)
            <tr>
                <td><code>{{ $code->code }}</code></td>
                <td>
                    <span class="pill pill--muted">{{ $code->system }}</span>
                    @if($code->kind !== 'category' && $code->kind !== 'term')
                        {{-- A chapter or block is a heading, not a diagnosis.
                             Saying so is what stops it being assigned. --}}
                        <span class="pill pill--warning">{{ __('terminology.dict_heading') }}</span>
                    @endif
                </td>
                <td>
                    {{ $code->display }}
                    @if($code->synonyms)
                        <div class="text-muted text-sm">{{ Str::limit($code->synonyms, 120) }}</div>
                    @endif
                </td>
                <td>
                    @if($code->is_common)
                        <span class="pill pill--success">{{ $code->common_reason }}</span>
                    @elseif($code->chapter)
                        <span class="text-muted text-sm">{{ __('terminology.dict_chapter', ['chapter' => $code->chapter]) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-muted">{{ __('terminology.dict_no_results') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $codes->links() }}</div>
@endsection
