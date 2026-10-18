@extends('layouts.portal')

@section('title', __('dhis2.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.nav_administration', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('dhis2.nav'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')

@php $sc = ['submitted' => 'ready', 'approved_for_submission' => 'normal', 'submission_failed' => 'urgent'][$report->status] ?? 'pending'; @endphp

<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $report->facility->name ?? '—' }} · {{ $report->reporting_period_start->format('Y-m') }}</h1>
        <p class="od-page__sub">{{ __('dhis2.title') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.dhis2') }}" class="od-btn od-btn--ghost od-btn--sm"><i data-lucide="arrow-left"></i> {{ __('dhis2.back') }}</a>
    </div>
</div>

@include('portals.admin.dhis2._flash')

@if(! $configured)
    <div class="oda-gb-alert oda-gb-alert--warn"><i data-lucide="alert-triangle"></i><div>{{ __('dhis2.not_configured') }}</div></div>
@endif

<div class="oda-gb-alert oda-gb-alert--info"><i data-lucide="shield"></i><div>{{ __('dhis2.aggregate_notice') }}</div></div>

<div class="oda-split">
<div class="od-stack">

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="table"></i> {{ __('dhis2.col_values') }}</h2>
        <span class="od-card__count">{{ $items->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
    @if($items->isEmpty())
        <div class="od-empty">
            <div class="od-empty__icon"><i data-lucide="inbox"></i></div>
            <p class="od-empty__text">{{ __('dhis2.no_items') }}</p>
        </div>
    @else
    <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr>
                <th>{{ __('dhis2.col_indicator') }}</th><th>{{ __('dhis2.col_code') }}</th><th>{{ __('dhis2.col_value') }}</th>
                <th>{{ __('dhis2.col_data_element') }}</th><th>{{ __('dhis2.col_sent') }}</th>
            </tr></thead>
            <tbody>
            @foreach($items as $row)
                @php $item = $row['item']; $rec = $records[$item->indicator_code] ?? null; @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_indicator') }}">@include('portals.admin.dhis2._indicator', ['code' => $item->indicator_code, 'meta' => $item->metadata_json ?? []])</td>
                    <td data-label="{{ __('dhis2.col_code') }}"><code class="oda-mono">{{ $item->indicator_code }}</code></td>
                    <td data-label="{{ __('dhis2.col_value') }}"><span class="od-table__name">{{ $item->value }}</span></td>
                    <td data-label="{{ __('dhis2.col_data_element') }}">
                        @if($row['mapping'])<code class="oda-mono">{{ $row['mapping']['data_element_id'] }}</code>
                        @else<span class="od-pill od-pill--pending">{{ __('dhis2.not_mapped') }}</span>@endif
                    </td>
                    <td data-label="{{ __('dhis2.col_sent') }}">
                        @if($rec)
                            <span class="od-pill od-pill--{{ $rec->status === 'imported' ? 'ready' : 'urgent' }}">{{ $rec->value }}</span>
                            <div class="od-table__sub">{{ $rec->pushed_at?->isoFormat('lll') }}</div>
                        @else — @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    </div>
</section>

@if($attempts->isNotEmpty())
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="server"></i> {{ __('dhis2.submissions_title') }}</h2>
        <span class="od-card__count">{{ $attempts->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
    <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr><th>{{ __('dhis2.col_when') }}</th><th>{{ __('dhis2.col_status') }}</th><th>{{ __('dhis2.col_values') }}</th></tr></thead>
            <tbody>
            @foreach($attempts as $sub)
                @php $res = $sub->after_state ?? []; $sum = $res['summary'] ?? []; $ic = $sum['importCount'] ?? []; $okRow = in_array($res['outcome'] ?? '', ['pushed', 'nothing_to_send'], true); @endphp
                <tr>
                    <td data-label="{{ __('dhis2.col_when') }}">{{ $sub->created_at?->isoFormat('lll') }}</td>
                    <td data-label="{{ __('dhis2.col_status') }}"><span class="od-pill od-pill--{{ $okRow ? 'ready' : 'urgent' }}">{{ $sum['status'] ?? ($sum['http_status'] ?? '—') }}</span></td>
                    <td data-label="{{ __('dhis2.col_values') }}">{{ __('dhis2.import_counts', ['imported' => $ic['imported'] ?? 0, 'updated' => $ic['updated'] ?? 0, 'ignored' => $ic['ignored'] ?? 0]) }}
                        @if(!empty($sum['description']))<div class="od-table__sub">{{ $sum['description'] }}</div>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    </div>
</section>
@endif

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="history"></i> {{ __('dhis2.history_title') }}</h2></div>
    <div class="od-card__body od-card__body--flush">
    <div class="od-table-wrap">
        <table class="od-table">
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
</section>

</div>
<div class="od-stack">

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="file-bar-chart"></i> {{ __('dhis2.nav') }}</h2>
        <span class="od-pill od-pill--{{ $sc }}">{{ __('dhis2.status_' . $report->status) }}</span>
    </div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('dhis2.col_facility') }}</dt>
            <dd>{{ $report->facility->name ?? '—' }}</dd>
            <dt>{{ __('dhis2.col_org_unit') }}</dt>
            <dd>@if($orgUnit)<code class="oda-mono">{{ $orgUnit }}</code>@else<span class="od-pill od-pill--pending">{{ __('dhis2.not_mapped') }}</span>@endif</dd>
            <dt>{{ __('dhis2.col_period') }}</dt>
            <dd><code class="oda-mono">{{ $period }}</code></dd>
            <dt>{{ __('dhis2.col_quality') }}</dt>
            <dd>{{ $report->data_quality_score }}/100</dd>
        </dl>
    </div>
    <div class="od-card__body od-stack">
        @if($report->status === 'draft')
            <div class="od-row">
                <form method="POST" action="{{ route('portals.admin.dhis2.drafts.regenerate', $report->id) }}" class="oda-inline-form">@csrf
                    <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="refresh-cw"></i> {{ __('dhis2.regenerate') }}</button></form>
                <form method="POST" action="{{ route('portals.admin.dhis2.drafts.approve', $report->id) }}" class="oda-inline-form">@csrf
                    <button type="submit" class="od-btn od-btn--primary" @disabled($blocking > 0)><i data-lucide="check"></i> {{ __('dhis2.approve') }}</button></form>
            </div>
            <p class="oda-help">{{ __('dhis2.approve_hint') }}</p>
        @else
            <form method="POST" action="{{ route('portals.admin.dhis2.drafts.push', $report->id) }}" class="oda-inline-form">@csrf
                <button type="submit" class="od-btn od-btn--primary" @disabled(! $configured || ! $orgUnit)><i data-lucide="send"></i>
                    {{ $report->status === 'approved_for_submission' ? __('dhis2.push') : __('dhis2.push_again') }}</button></form>
            @if(! $configured)<p class="oda-help">{{ __('dhis2.push_disabled_config') }}</p>
            @elseif(! $orgUnit)<p class="oda-help">{{ __('dhis2.push_disabled_mapping') }}</p>@endif
        @endif
    </div>
</section>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="gauge"></i> {{ __('dhis2.checks_title') }} · {{ $report->data_quality_score }}/100</h2></div>
    <div class="od-card__body od-stack">
        @forelse($report->qualityChecks as $chk)
            <div class="oda-gb-alert oda-gb-alert--{{ $chk->status === 'failed' ? 'bad' : 'warn' }}">
                <i data-lucide="{{ $chk->status === 'failed' ? 'octagon-alert' : 'alert-triangle' }}"></i>
                <div><strong>{{ __('dhis2.check_' . $chk->status) }}</strong> —
                    {{ \Illuminate\Support\Facades\Lang::has('dhis2.check_' . $chk->check_code) ? __('dhis2.check_' . $chk->check_code, ['count' => $chk->field_reference ?? '']) : $chk->message }}</div>
            </div>
        @empty
            <p class="oda-help">{{ __('dhis2.no_checks') }}</p>
        @endforelse
    </div>
</section>

</div>
</div>
</div>
@endsection
