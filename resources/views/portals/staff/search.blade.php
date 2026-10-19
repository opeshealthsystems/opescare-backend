@extends('layouts.portal')

@section('title', __('public.staff_portal.page_heading_search', [], app()->getLocale()))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    $l = app()->getLocale();
    $typeIcon = [
        'patient'   => 'user',
        'facility'  => 'building-2',
        'document'  => 'file-text',
        'medicine'  => 'pill',
        'lab_test'  => 'flask-conical',
        'partner'   => 'handshake',
        'message'   => 'message-square',
    ];
    $typeTint = [
        'patient'   => 'blue',
        'facility'  => 'teal',
        'document'  => 'amber',
        'medicine'  => 'green',
        'lab_test'  => 'sky',
        'partner'   => 'rose',
        'message'   => 'blue',
    ];
    $typeLabel = fn ($type) => \Illuminate\Support\Facades\Lang::has('staff.desk.search_types.' . $type) ? __('staff.desk.search_types.' . $type) : ucfirst(str_replace('_', ' ', $type));
@endphp

@section('content')
<div class="od-page odc-page">

<header class="od-page__head">
    <h1 class="od-page__title">{{ __('public.staff_portal.page_heading_search', [], $l) }}</h1>
    <p class="od-page__sub">{{ __('public.staff_portal.page_subtitle_search', [], $l) }}</p>
</header>

<section class="od-card">
    <div class="od-card__body">
    {{-- Search Bar --}}
    <form method="GET" class="odc-toolbar">
        <label class="od-field">
            <i data-lucide="search"></i>
            <input type="text" name="q" value="{{ $query }}" class="od-input"
                placeholder="{{ __('public.staff_portal.ph_search_global', [], $l) }}"
                aria-label="{{ __('public.staff_portal.search_empty_title', [], $l) }}"
                autofocus>
        </label>
        <button type="submit" class="od-btn od-btn--primary">{{ __('public.staff_portal.btn_search', [], $l) }}</button>
    </form>

@if($query === '')
    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="search"></i></span>
        <div class="od-empty__title">{{ __('public.staff_portal.search_empty_title', [], $l) }}</div>
        <p class="od-empty__text">{{ __('public.staff_portal.search_empty_desc', [], $l) }}</p>
        <div class="od-row">
            @foreach([
                __('public.staff_portal.search_cat_facilities', [], $l),
                __('public.staff_portal.search_cat_medicines', [], $l),
                __('public.staff_portal.search_cat_lab_tests', [], $l),
                __('public.staff_portal.search_cat_documents', [], $l),
                __('public.staff_portal.search_cat_partners', [], $l),
            ] as $cat)
                <span class="od-pill od-pill--neutral">{{ $cat }}</span>
            @endforeach
        </div>
    </div>

@elseif($total === 0)
    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="search-x"></i></span>
        <div class="od-empty__title">{{ __('public.staff_portal.search_no_results_title', [], $l) }} "{{ $query }}"</div>
        <p class="od-empty__text">{{ __('public.staff_portal.search_no_results_desc', [], $l) }}</p>
    </div>

@else
    <div class="od-row">
        <span class="od-muted">
            <strong class="od-strong">{{ $total }}</strong> {{ __('public.staff_portal.lbl_results_for', [], $l) }}
            <strong class="od-strong">"{{ $query }}"</strong>
        </span>
        @foreach($counts as $type => $cnt)
            <span class="od-pill od-pill--neutral">{{ $typeLabel($type) }}: {{ $cnt }}</span>
        @endforeach
    </div>
@endif
    </div>
</section>

@if($query !== '' && $total > 0)
    @foreach($grouped as $type => $items)
    <section class="od-card">
        <header class="od-card__head">
            <h2 class="od-card__title">{{ $typeLabel($type) }} <span class="od-card__count">({{ $items->count() }})</span></h2>
        </header>
        <div class="od-card__body od-card__body--flush">
            <div class="od-table-wrap">
                <table class="od-table odc-stack">
                    <thead><tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('public.staff_portal.col_title', [], $l) }}</th>
                        <th>{{ __('public.staff_portal.col_details', [], $l) }}</th>
                        <th>{{ __('public.staff_portal.col_metadata', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        <tr>
                            <td class="od-table__num">{{ $i + 1 }}</td>
                            <td data-label="{{ __('public.staff_portal.col_title', [], $l) }}">
                                <span class="odc-filecell">
                                    <span class="od-list__icon od-tint--{{ $typeTint[$type] ?? 'blue' }}"><i data-lucide="{{ $typeIcon[$type] ?? 'circle' }}"></i></span>
                                    @php
                                        // Only link a row whose show route exists; anything
                                        // else stays plain text rather than a dead link.
                                        $__route = $item['route'] ?? null;
                                        $__href = ($__route && \Illuminate\Support\Facades\Route::has($__route[0])) ? route($__route[0], $__route[1] ?? []) : null;
                                    @endphp
                                    @if($__href)
                                        <a href="{{ $__href }}" class="od-table__name">{{ $item['title'] }}</a>
                                    @else
                                        <span class="od-table__name">{{ $item['title'] }}</span>
                                    @endif
                                </span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_details', [], $l) }}" class="od-muted">{{ $item['subtitle'] ?? '—' }}</td>
                            <td data-label="{{ __('public.staff_portal.col_metadata', [], $l) }}">
                                @php
                                    // Known, human-meaningful fields only. Internal ids and
                                    // nested structures are not shown.
                                    $__meta = $item['metadata'] ?? [];
                                @endphp
                                @foreach(['status' => 'public.staff_portal.meta_status', 'document_number' => 'public.staff_portal.meta_document_number', 'verification_code' => 'public.staff_portal.meta_verification_code'] as $mk => $mlabel)
                                    @if(isset($__meta[$mk]) && is_scalar($__meta[$mk]) && $__meta[$mk] !== '')
                                        <span class="od-table__sub">{{ __($mlabel, [], $l) }}: <span class="odc-mono">{{ $__meta[$mk] }}</span></span>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endforeach
@endif

</div>
@endsection
