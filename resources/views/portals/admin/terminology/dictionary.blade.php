@extends('layouts.portal')
@section('title', __('terminology.dict_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('terminology.dict_title') }}</h1>
        <p class="od-page__sub">{{ __('terminology.dict_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.terminology.index') }}" class="od-btn od-btn--secondary od-btn--sm">
            <i data-lucide="list-checks"></i> {{ __('terminology.dict_nav_queue') }}
        </a>
    </div>
</div>

{{-- The standing size of each vocabulary, with its release. Reference data
     whose provenance nobody can see is reference data nobody can check. --}}
@if(collect($totals)->isEmpty())
    {{-- Empty is not the same as "no matches", and must not read like it. --}}
    <div class="oda-gb-alert oda-gb-alert--warn">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('terminology.dict_never_imported') }}</div>
    </div>
@else
<div class="od-kpis">
    @foreach($totals as $system => $row)
    <a href="{{ route('portals.admin.terminology.dictionary', ['system' => $system]) }}" class="od-kpi od-tint--{{ $gbTints[crc32((string) $system) % 6] }}">
        <span class="od-kpi__icon"><i data-lucide="{{ $system === 'LOINC' ? 'flask-conical' : 'stethoscope' }}"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ $system }}</span>
            <span class="od-kpi__value">{{ number_format($row->total) }}</span>
            <span class="od-kpi__sub">{{ __('terminology.dict_common_count', ['count' => number_format($row->common)]) }}</span>
        </span>
    </a>
    @endforeach
</div>
@endif

@if($releases->isNotEmpty())
<p class="oda-gb-note">
    {{ __('terminology.dict_provenance') }}
    @foreach($releases as $r)
        <span class="od-pill od-pill--neutral">{{ $r->system }} · {{ $r->source }}@if($r->source_version) · {{ $r->source_version }}@endif</span>
    @endforeach
</p>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('terminology.dict_title') }}</h2>
        <span class="od-card__count">{{ method_exists($codes, 'total') ? number_format($codes->total()) : $codes->count() }}</span>
    </div>
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.admin.terminology.dictionary') }}" class="oda-toolbar">
            <label class="od-field">
                <i data-lucide="search"></i>
                <input type="search" name="q" value="{{ request('q') }}"
                       class="od-input" placeholder="{{ __('terminology.dict_search_placeholder') }}">
            </label>

            <select name="system" class="od-select">
                <option value="">{{ __('terminology.dict_all_systems') }}</option>
                {{-- Built from the table, not a literal. Hardcoding the two systems
                     that existed on the day this was written hid six others the
                     moment they were imported. --}}
                @foreach($systems as $s)
                <option value="{{ $s }}" @selected($system === $s)>{{ $s }}</option>
                @endforeach
            </select>

            <label class="oda-check">
                <input type="checkbox" name="common" value="1" @checked(request()->boolean('common'))>
                {{ __('terminology.dict_only_common') }}
            </label>

            <label class="oda-check">
                <input type="checkbox" name="headings" value="1" @checked(request()->boolean('headings'))>
                {{ __('terminology.dict_show_headings') }}
            </label>

            <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                <i data-lucide="search"></i> {{ __('terminology.dict_search') }}
            </button>
        </form>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
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
                        <td><code class="oda-mono">{{ $code->code }}</code></td>
                        <td>
                            <span class="od-pill od-pill--neutral">{{ $code->system }}</span>
                            @if($code->kind !== 'category' && $code->kind !== 'term')
                                {{-- A chapter or block is a heading, not a diagnosis.
                                     Saying so is what stops it being assigned. --}}
                                <span class="od-pill od-pill--pending">{{ __('terminology.dict_heading') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="od-table__name">{{ $code->label }}</div>
                            @if($code->label !== $code->display)
                                {{-- The published English, kept visible under the French
                                     title. A code exchanged with another system travels
                                     under its English label, so hiding it would make the
                                     screen disagree with the wire. --}}
                                <div class="od-table__sub">{{ $code->display }}</div>
                            @endif
                            @if($code->synonyms)
                                <div class="od-table__sub">{{ Str::limit($code->synonyms, 120) }}</div>
                            @endif
                        </td>
                        <td>
                            @if($code->is_common)
                                <span class="od-pill od-pill--ready">{{ $code->common_reason }}</span>
                            @elseif($code->chapter)
                                <span class="od-table__sub">{{ __('terminology.dict_chapter', ['chapter' => $code->chapter]) }}</span>
                            @else
                                <span class="od-table__sub">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="od-empty">
                                <div class="od-empty__icon"><i data-lucide="search-x"></i></div>
                                <p class="od-empty__text">{{ __('terminology.dict_no_results') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="oda-gb-pager">{{ $codes->links() }}</div>
</div>
@endsection
