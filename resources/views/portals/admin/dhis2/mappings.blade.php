@extends('layouts.portal')

@section('title', __('dhis2.mappings_title') . ' — OpesCare')

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('dhis2.mappings_title') }}</h1>
        <p class="od-page__sub">{{ __('dhis2.mappings_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.dhis2') }}" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="arrow-left"></i> {{ __('dhis2.back') }}</a>
    </div>
</div>

@include('portals.admin.dhis2._flash')

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="building-2"></i> {{ __('dhis2.set_org_unit') }}</h2></div>
    <div class="od-card__body od-stack">
        <form method="GET" action="{{ route('portals.admin.dhis2.mappings') }}" class="oda-toolbar">
            <label class="oda-gb-sr" for="q">{{ __('dhis2.search_facility') }}</label>
            <label class="od-field">
                <i data-lucide="search"></i>
                <input type="search" id="q" name="q" class="od-input" value="{{ $q }}" maxlength="120" placeholder="{{ __('dhis2.search_facility') }}">
            </label>
            <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="search"></i> {{ __('dhis2.search') }}</button>
        </form>
        <p class="oda-help">{{ __('dhis2.org_unit_hint') }}</p>
        @if($q !== '' && $candidates->isEmpty())
            <p class="oda-help">{{ __('dhis2.no_facilities') }}</p>
        @endif
        @if($candidates->isNotEmpty())
        <div class="od-list">
        @foreach($candidates as $f)
            <form method="POST" action="{{ route('portals.admin.dhis2.mappings.org-unit') }}" class="od-list__row oda-gb-maprow">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $f->id }}">
                <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $f->name) % 6] }}">{{ mb_strtoupper(mb_substr($f->name, 0, 1)) }}</span>
                <span class="od-list__text"><span class="od-list__title">{{ $f->name }}</span></span>
                <input type="text" name="org_unit_id" class="od-input oda-gb-uid" maxlength="11" value="{{ $mappedIds[$f->id] ?? '' }}" aria-label="{{ __('dhis2.col_org_unit') }}">
                <button type="submit" class="od-btn od-btn--row"><i data-lucide="save"></i> {{ __('dhis2.save') }}</button>
            </form>
        @endforeach
        </div>
        @endif
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="map-pin"></i> {{ __('dhis2.mapped_facilities') }}</h2>
        <span class="od-card__count">{{ $mappings->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
    @if($mappings->isEmpty())
        <div class="od-empty">
            <div class="od-empty__icon"><i data-lucide="map-pin-off"></i></div>
            <p class="od-empty__text">{{ __('dhis2.no_mappings') }}</p>
        </div>
    @else
    <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr><th>{{ __('dhis2.col_facility') }}</th><th>{{ __('dhis2.col_org_unit') }}</th></tr></thead>
            <tbody>
            @foreach($mappings as $m)
                @php $fname = $facilityNames[$m->facility_id] ?? '—'; @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_facility') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $fname) % 6] }}">{{ mb_strtoupper(mb_substr($fname, 0, 1)) }}</span>
                            <span class="od-table__name">{{ $fname }}</span>
                        </div>
                    </td>
                    <td data-label="{{ __('dhis2.col_org_unit') }}">
                        <form method="POST" action="{{ route('portals.admin.dhis2.mappings.org-unit') }}" class="oda-inline-form">
                            @csrf
                            <input type="hidden" name="facility_id" value="{{ $m->facility_id }}">
                            <input type="text" name="org_unit_id" class="od-input oda-gb-uid" maxlength="11" value="{{ $m->org_unit_id }}" aria-label="{{ __('dhis2.col_org_unit') }}">
                            <button type="submit" class="od-btn od-btn--row"><i data-lucide="save"></i> {{ __('dhis2.save') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="list-tree"></i> {{ __('dhis2.data_elements_title') }}</h2>
        <span class="od-card__count">{{ count($codes) }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
    <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr><th>{{ __('dhis2.col_indicator') }}</th><th>{{ __('dhis2.col_data_element') }} / {{ __('dhis2.col_coc') }}</th></tr></thead>
            <tbody>
            @foreach($codes as $code)
                @php $em = $elementMappings[$code] ?? null; @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_indicator') }}"><div class="od-table__name">@include('portals.admin.dhis2._indicator', ['code' => $code, 'meta' => []])</div>
                        <div class="od-table__sub"><code class="oda-mono">{{ $code }}</code></div>
                        @if(! $em && isset($envMap[$code]))<div class="od-table__sub">{{ $envMap[$code] }} ({{ __('dhis2.from_env') }})</div>@endif
                    </td>
                    <td data-label="{{ __('dhis2.col_data_element') }}">
                        <form method="POST" action="{{ route('portals.admin.dhis2.mappings.data-element') }}" class="oda-inline-form">
                            @csrf
                            <input type="hidden" name="indicator_code" value="{{ $code }}">
                            <input type="text" name="data_element_id" class="od-input oda-gb-uid" maxlength="11" value="{{ $em->data_element_id ?? '' }}" aria-label="{{ __('dhis2.col_data_element') }}">
                            <input type="text" name="category_option_combo_id" class="od-input oda-gb-uid" maxlength="11" value="{{ $em->category_option_combo_id ?? '' }}" aria-label="{{ __('dhis2.col_coc') }}">
                            <button type="submit" class="od-btn od-btn--row"><i data-lucide="save"></i> {{ __('dhis2.save') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    </div>
</section>
</div>
@endsection
