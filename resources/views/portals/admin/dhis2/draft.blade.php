@extends('layouts.portal')

@section('title', __('dhis2.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()) ?: 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('dhis2.nav'))

@section('content')

@php $sc = ['submitted' => 'success', 'approved_for_submission' => 'info', 'submission_failed' => 'danger'][$report->status] ?? 'warning'; @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $report->facility->name ?? '—' }} · {{ $report->reporting_period_start->format('Y-m') }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-{{ $sc }}">{{ __('dhis2.status_' . $report->status) }}</span>
            {{ __('dhis2.col_org_unit') }}:
            @if($orgUnit)<code>{{ $orgUnit }}</code>@else<span class="badge badge-warning">{{ __('dhis2.not_mapped') }}</span>@endif
            · {{ __('dhis2.col_period') }}: <code>{{ $period }}</code>
        </p>
    </div>
    <a href="{{ route('portals.admin.dhis2') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('dhis2.back') }}</a>
</div>

@include('portals.admin.dhis2._flash')

@if(! $configured)
    <div class="alert alert-warning mb-4"><i data-lucide="alert-triangle"></i><div>{{ __('dhis2.not_configured') }}</div></div>
@endif

<div class="alert alert-info mb-4"><i data-lucide="shield"></i><div>{{ __('dhis2.aggregate_notice') }}</div></div>

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="table"></i> {{ __('dhis2.col_values') }}</h3></div>
    @if($items->isEmpty())
        <div class="empty-state"><i data-lucide="inbox"></i><p>{{ __('dhis2.no_items') }}</p></div>
    @else
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('dhis2.col_indicator') }}</th><th>{{ __('dhis2.col_code') }}</th><th>{{ __('dhis2.col_value') }}</th>
                <th>{{ __('dhis2.col_data_element') }}</th><th>{{ __('dhis2.col_sent') }}</th>
            </tr></thead>
            <tbody>
            @foreach($items as $row)
                @php $item = $row['item']; $rec = $records[$item->indicator_code] ?? null; @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_indicator') }}">@include('portals.admin.dhis2._indicator', ['code' => $item->indicator_code, 'meta' => $item->metadata_json ?? []])</td>
                    <td data-label="{{ __('dhis2.col_code') }}"><code>{{ $item->indicator_code }}</code></td>
                    <td data-label="{{ __('dhis2.col_value') }}"><span class="td-strong">{{ $item->value }}</span></td>
                    <td data-label="{{ __('dhis2.col_data_element') }}">
                        @if($row['mapping'])<code>{{ $row['mapping']['data_element_id'] }}</code>
                        @else<span class="badge badge-warning">{{ __('dhis2.not_mapped') }}</span>@endif
                    </td>
                    <td data-label="{{ __('dhis2.col_sent') }}">
                        @if($rec)
                            <span class="badge badge-{{ $rec->status === 'imported' ? 'success' : 'danger' }}">{{ $rec->value }}</span>
                            <span class="td-muted">{{ $rec->pushed_at?->isoFormat('lll') }}</span>
                        @else — @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="gauge"></i> {{ __('dhis2.checks_title') }} · {{ $report->data_quality_score }}/100</h3></div>
    <div class="panel-body">
        @forelse($report->qualityChecks as $chk)
            <div class="alert alert-{{ $chk->status === 'failed' ? 'danger' : 'warning' }} mb-2">
                <i data-lucide="{{ $chk->status === 'failed' ? 'octagon-alert' : 'alert-triangle' }}"></i>
                <div><strong>{{ __('dhis2.check_' . $chk->status) }}</strong> —
                    {{ \Illuminate\Support\Facades\Lang::has('dhis2.check_' . $chk->check_code) ? __('dhis2.check_' . $chk->check_code, ['count' => $chk->field_reference ?? '']) : $chk->message }}</div>
            </div>
        @empty
            <p class="td-muted">{{ __('dhis2.no_checks') }}</p>
        @endforelse
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-body" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
        @if($report->status === 'draft')
            <form method="POST" action="{{ route('portals.admin.dhis2.drafts.regenerate', $report->id) }}">@csrf
                <button type="submit" class="btn btn-secondary"><i data-lucide="refresh-cw"></i> {{ __('dhis2.regenerate') }}</button></form>
            <form method="POST" action="{{ route('portals.admin.dhis2.drafts.approve', $report->id) }}">@csrf
                <button type="submit" class="btn btn-primary" @disabled($blocking > 0)><i data-lucide="check"></i> {{ __('dhis2.approve') }}</button></form>
            <span class="td-muted">{{ __('dhis2.approve_hint') }}</span>
        @else
            <form method="POST" action="{{ route('portals.admin.dhis2.drafts.push', $report->id) }}">@csrf
                <button type="submit" class="btn btn-primary" @disabled(! $configured || ! $orgUnit)><i data-lucide="send"></i>
                    {{ $report->status === 'approved_for_submission' ? __('dhis2.push') : __('dhis2.push_again') }}</button></form>
            @if(! $configured)<span class="td-muted">{{ __('dhis2.push_disabled_config') }}</span>
            @elseif(! $orgUnit)<span class="td-muted">{{ __('dhis2.push_disabled_mapping') }}</span>@endif
        @endif
    </div>
</div>

@if($attempts->isNotEmpty())
<div class="panel mb-4">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="server"></i> {{ __('dhis2.submissions_title') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>{{ __('dhis2.col_when') }}</th><th>{{ __('dhis2.col_status') }}</th><th>{{ __('dhis2.col_values') }}</th></tr></thead>
            <tbody>
            @foreach($attempts as $sub)
                @php $res = $sub->after_state ?? []; $sum = $res['summary'] ?? []; $ic = $sum['importCount'] ?? []; $okRow = in_array($res['outcome'] ?? '', ['pushed', 'nothing_to_send'], true); @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_when') }}">{{ $sub->created_at?->isoFormat('lll') }}</td>
                    <td data-label="{{ __('dhis2.col_status') }}"><span class="badge badge-{{ $okRow ? 'success' : 'danger' }}">{{ $sum['status'] ?? ($sum['http_status'] ?? '—') }}</span></td>
                    <td data-label="{{ __('dhis2.col_values') }}">{{ __('dhis2.import_counts', ['imported' => $ic['imported'] ?? 0, 'updated' => $ic['updated'] ?? 0, 'ignored' => $ic['ignored'] ?? 0]) }}
                        @if(!empty($sum['description']))<div class="td-muted">{{ $sum['description'] }}</div>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('dhis2.history_title') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>{{ __('dhis2.col_when') }}</th><th>{{ __('dhis2.col_status') }}</th><th>{{ __('dhis2.col_by') }}</th></tr></thead>
            <tbody>
            @foreach($history as $h)
                <tr>
                    <td data-label="{{ __('dhis2.col_when') }}">{{ \Illuminate\Support\Carbon::parse($h->changed_at)->isoFormat('lll') }}</td>
                    <td data-label="{{ __('dhis2.col_status') }}">{{ __('dhis2.status_' . $h->new_status) }}</td>
                    <td data-label="{{ __('dhis2.col_by') }}">{{ $h->changed_by ? (\App\Models\User::find($h->changed_by)?->name ?? '—') : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
