@extends('layouts.portal')

@section('title', __('dhis2.mappings_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('dhis2.mappings_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('dhis2.mappings_title') }}</h1>
        <p class="page-subtitle">{{ __('dhis2.mappings_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.admin.dhis2') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('dhis2.back') }}</a>
</div>

@include('portals.admin.dhis2._flash')

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('dhis2.set_org_unit') }}</h3></div>
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.admin.dhis2.mappings') }}" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end;" class="mb-4">
            <div style="min-width:16rem;"><label class="form-label" for="q">{{ __('dhis2.search_facility') }}</label>
                <input type="search" id="q" name="q" class="form-control" value="{{ $q }}" maxlength="120"></div>
            <button type="submit" class="btn btn-secondary"><i data-lucide="search"></i> {{ __('dhis2.search') }}</button>
        </form>
        <p class="td-muted">{{ __('dhis2.org_unit_hint') }}</p>
        @if($q !== '' && $candidates->isEmpty())
            <p class="td-muted">{{ __('dhis2.no_facilities') }}</p>
        @endif
        @foreach($candidates as $f)
            <form method="POST" action="{{ route('portals.admin.dhis2.mappings.org-unit') }}" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:.5rem;">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $f->id }}">
                <span class="td-strong" style="min-width:14rem;">{{ $f->name }}</span>
                <input type="text" name="org_unit_id" class="form-control" style="width:12rem;" maxlength="11" value="{{ $mappedIds[$f->id] ?? '' }}" aria-label="{{ __('dhis2.col_org_unit') }}">
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="save"></i> {{ __('dhis2.save') }}</button>
            </form>
        @endforeach
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="map-pin"></i> {{ __('dhis2.mapped_facilities') }}</h3></div>
    @if($mappings->isEmpty())
        <div class="empty-state"><i data-lucide="map-pin-off"></i><p>{{ __('dhis2.no_mappings') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>{{ __('dhis2.col_facility') }}</th><th>{{ __('dhis2.col_org_unit') }}</th><th></th></tr></thead>
            <tbody>
            @foreach($mappings as $m)
                <tr>
                    <td data-label="{{ __('dhis2.col_facility') }}"><span class="td-strong">{{ $facilityNames[$m->facility_id] ?? '—' }}</span></td>
                    <td colspan="2">
                        <form method="POST" action="{{ route('portals.admin.dhis2.mappings.org-unit') }}" style="display:flex;gap:.5rem;align-items:center;">
                            @csrf
                            <input type="hidden" name="facility_id" value="{{ $m->facility_id }}">
                            <input type="text" name="org_unit_id" class="form-control" style="width:12rem;" maxlength="11" value="{{ $m->org_unit_id }}" aria-label="{{ __('dhis2.col_org_unit') }}">
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> {{ __('dhis2.save') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="list-tree"></i> {{ __('dhis2.data_elements_title') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>{{ __('dhis2.col_indicator') }}</th><th>{{ __('dhis2.col_data_element') }} / {{ __('dhis2.col_coc') }}</th></tr></thead>
            <tbody>
            @foreach($codes as $code)
                @php $em = $elementMappings[$code] ?? null; @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_indicator') }}">@include('portals.admin.dhis2._indicator', ['code' => $code, 'meta' => []])
                        <div class="td-muted"><code>{{ $code }}</code></div>
                        @if(! $em && isset($envMap[$code]))<div class="td-muted">{{ $envMap[$code] }} ({{ __('dhis2.from_env') }})</div>@endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('portals.admin.dhis2.mappings.data-element') }}" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                            @csrf
                            <input type="hidden" name="indicator_code" value="{{ $code }}">
                            <input type="text" name="data_element_id" class="form-control" style="width:10rem;" maxlength="11" value="{{ $em->data_element_id ?? '' }}" aria-label="{{ __('dhis2.col_data_element') }}">
                            <input type="text" name="category_option_combo_id" class="form-control" style="width:10rem;" maxlength="11" value="{{ $em->category_option_combo_id ?? '' }}" aria-label="{{ __('dhis2.col_coc') }}">
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> {{ __('dhis2.save') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
