@extends('layouts.portal')

@section('title', __('dhis2.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('dhis2.nav'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('dhis2.title') }}</h1>
        <p class="page-subtitle">{{ __('dhis2.subtitle') }}</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('portals.admin.dhis2.mappings') }}" class="btn btn-secondary"><i data-lucide="link-2"></i> {{ __('dhis2.mappings_link') }}</a>
        @if($configured)
        <form method="POST" action="{{ route('portals.admin.dhis2.test') }}">@csrf
            <button type="submit" class="btn btn-secondary"><i data-lucide="plug-zap"></i> {{ __('dhis2.test_connection') }}</button>
        </form>
        @endif
    </div>
</div>

@include('portals.admin.dhis2._flash')

@if(! $configured)
    <div class="alert alert-warning mb-4"><i data-lucide="alert-triangle"></i><div>{{ __('dhis2.not_configured') }}</div></div>
@else
    <div class="alert alert-info mb-4"><i data-lucide="shield-check"></i><div>{{ __('dhis2.configured') }}</div></div>
@endif

@if($connection)
    @if($connection['connected'])
        <div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ __('dhis2.connection_ok', ['version' => $connection['version'] ?? '']) }}</div></div>
    @else
        <div class="alert alert-danger mb-4"><i data-lucide="x-circle"></i><div>{{ __('dhis2.connection_failed', ['error' => $connection['error'] ?? '']) }}</div></div>
    @endif
@endif

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-plus"></i> {{ __('dhis2.generate_title') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.dhis2.generate') }}" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div><label class="form-label" for="month">{{ __('dhis2.month') }}</label>
                <input type="month" id="month" name="month" class="form-control" value="{{ old('month', $defaultMonth) }}" required></div>
            <div style="min-width:16rem;"><label class="form-label" for="facility_id">{{ __('dhis2.facility') }}</label>
                <select id="facility_id" name="facility_id" class="form-control">
                    <option value="">{{ __('dhis2.all_mapped') }} ({{ $mappedFacilities->count() }})</option>
                    @foreach($mappedFacilities as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach
                </select></div>
            <button type="submit" class="btn btn-primary"><i data-lucide="calculator"></i> {{ __('dhis2.generate') }}</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="send"></i> {{ __('dhis2.drafts_title') }}</h3></div>
    @if($reports->isEmpty())
        <div class="empty-state"><i data-lucide="file-bar-chart"></i><p>{{ __('dhis2.no_reports') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('dhis2.col_facility') }}</th><th>{{ __('dhis2.col_period') }}</th><th>{{ __('dhis2.col_org_unit') }}</th>
                <th>{{ __('dhis2.col_values') }}</th><th>{{ __('dhis2.col_quality') }}</th><th>{{ __('dhis2.col_status') }}</th><th></th>
            </tr></thead>
            <tbody>
            @foreach($reports as $r)
                @php $sc = ['submitted' => 'success', 'approved_for_submission' => 'info', 'submission_failed' => 'danger'][$r->status] ?? 'warning'; @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_facility') }}"><span class="td-strong">{{ $r->facility->name ?? '—' }}</span></td>
                    <td data-label="{{ __('dhis2.col_period') }}">{{ $r->reporting_period_start->format('Y-m') }}</td>
                    <td data-label="{{ __('dhis2.col_org_unit') }}">
                        @if(isset($mapped[$r->facility_id]))<code>{{ $mapped[$r->facility_id] }}</code>
                        @else<span class="badge badge-warning">{{ __('dhis2.not_mapped') }}</span>@endif
                    </td>
                    <td data-label="{{ __('dhis2.col_values') }}">{{ $r->items_count }}</td>
                    <td data-label="{{ __('dhis2.col_quality') }}">{{ $r->data_quality_score }}</td>
                    <td data-label="{{ __('dhis2.col_status') }}"><span class="badge badge-{{ $sc }}">{{ __('dhis2.status_' . $r->status) }}</span></td>
                    <td><a class="btn btn-ghost btn-sm" href="{{ route('portals.admin.dhis2.drafts.show', $r->id) }}"><i data-lucide="eye"></i> {{ __('dhis2.open') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $reports->links() }}</div>
    @endif
</div>

@endsection
