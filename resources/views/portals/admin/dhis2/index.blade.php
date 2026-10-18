@extends('layouts.portal')

@section('title', __('dhis2.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('dhis2.nav'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('dhis2.title') }}</h1>
        <p class="od-page__sub">{{ __('dhis2.subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.dhis2.mappings') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="link-2"></i> {{ __('dhis2.mappings_link') }}</a>
        @if($configured)
        <form method="POST" action="{{ route('portals.admin.dhis2.test') }}" class="oda-inline-form">@csrf
            <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="plug-zap"></i> {{ __('dhis2.test_connection') }}</button>
        </form>
        @endif
    </div>
</div>

@include('portals.admin.dhis2._flash')

@if(! $configured)
    <div class="oda-gb-alert oda-gb-alert--warn"><i data-lucide="alert-triangle"></i><div>{{ __('dhis2.not_configured') }}</div></div>
@else
    <div class="oda-gb-alert oda-gb-alert--info"><i data-lucide="shield-check"></i><div>{{ __('dhis2.configured') }}</div></div>
@endif

@if($connection)
    @if($connection['connected'])
        <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ __('dhis2.connection_ok', ['version' => $connection['version'] ?? '']) }}</div></div>
    @else
        <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="x-circle"></i><div>{{ __('dhis2.connection_failed', ['error' => $connection['error'] ?? '']) }}</div></div>
    @endif
@endif

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="file-plus"></i> {{ __('dhis2.generate_title') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.dhis2.generate') }}" class="oda-form oda-form--3">
            @csrf
            <label class="oda-label" for="month">
                <span class="oda-label__text">{{ __('dhis2.month') }}</span>
                <input type="month" id="month" name="month" class="od-input" value="{{ old('month', $defaultMonth) }}" required>
            </label>
            <label class="oda-label" for="facility_id">
                <span class="oda-label__text">{{ __('dhis2.facility') }}</span>
                <select id="facility_id" name="facility_id" class="od-select">
                    <option value="">{{ __('dhis2.all_mapped') }} ({{ $mappedFacilities->count() }})</option>
                    @foreach($mappedFacilities as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach
                </select>
            </label>
            <div class="oda-gb-align-end">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="calculator"></i> {{ __('dhis2.generate') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="send"></i> {{ __('dhis2.drafts_title') }}</h2>
        <span class="od-card__count">{{ method_exists($reports, 'total') ? $reports->total() : $reports->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
    @if($reports->isEmpty())
        <div class="od-empty">
            <div class="od-empty__icon"><i data-lucide="file-bar-chart"></i></div>
            <p class="od-empty__text">{{ __('dhis2.no_reports') }}</p>
        </div>
    @else
    <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr>
                <th>{{ __('dhis2.col_facility') }}</th><th>{{ __('dhis2.col_period') }}</th><th>{{ __('dhis2.col_org_unit') }}</th>
                <th>{{ __('dhis2.col_values') }}</th><th>{{ __('dhis2.col_quality') }}</th><th>{{ __('dhis2.col_status') }}</th><th></th>
            </tr></thead>
            <tbody>
            @foreach($reports as $r)
                @php
                    $sc = ['submitted' => 'ready', 'approved_for_submission' => 'normal', 'submission_failed' => 'urgent'][$r->status] ?? 'pending';
                    $fname = $r->facility->name ?? '—';
                @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_facility') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $fname) % 6] }}">{{ mb_strtoupper(mb_substr($fname, 0, 1)) }}</span>
                            <span class="od-table__name">{{ $fname }}</span>
                        </div>
                    </td>
                    <td data-label="{{ __('dhis2.col_period') }}">{{ $r->reporting_period_start->format('Y-m') }}</td>
                    <td data-label="{{ __('dhis2.col_org_unit') }}">
                        @if(isset($mapped[$r->facility_id]))<code class="oda-mono">{{ $mapped[$r->facility_id] }}</code>
                        @else<span class="od-pill od-pill--pending">{{ __('dhis2.not_mapped') }}</span>@endif
                    </td>
                    <td data-label="{{ __('dhis2.col_values') }}">{{ $r->items_count }}</td>
                    <td data-label="{{ __('dhis2.col_quality') }}">{{ $r->data_quality_score }}</td>
                    <td data-label="{{ __('dhis2.col_status') }}"><span class="od-pill od-pill--{{ $sc }}">{{ __('dhis2.status_' . $r->status) }}</span></td>
                    <td class="od-table__actions"><a class="od-btn od-btn--row" href="{{ route('portals.admin.dhis2.drafts.show', $r->id) }}"><i data-lucide="eye"></i> {{ __('dhis2.open') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    </div>
    @unless($reports->isEmpty())
    <div class="od-card__foot">{{ $reports->links() }}</div>
    @endunless
</section>
</div>
@endsection
