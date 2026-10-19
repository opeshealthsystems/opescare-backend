@extends('layouts.portal')

@section('title', __('imaging_report.queue_title'))

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('imaging_report.queue_title') }}</h1>
        <p class="od-page__sub odl-pills">{{ __('imaging_report.queue_subtitle') }}</p>
    </div>
    @if(\Illuminate\Support\Facades\Route::has('portals.imaging.index'))
    <a href="{{ route('portals.imaging.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('imaging_report.back_to_worklist') }}
    </a>
    @endif
</div>

@if(session('success'))<div class="od-flash od-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="od-flash od-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── Completed studies with no final report ─────────────────────────── --}}
<div class="od-card" data-section="awaiting-report">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('imaging_report.queue_awaiting') }}
            <span class="od-card__count">({{ $awaiting->count() }})</span></h2>
    </div>
    @if($awaiting->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="check-circle-2"></i></div>
                <h3>{{ __('imaging_report.queue_empty') }}</h3>
            </div>
        </div>
    @else
        <div class="od-card__body"><p class="od-muted">{{ __('imaging_report.queue_awaiting_hint') }}</p></div>
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('imaging_report.col_patient') }}</th>
                        <th>{{ __('imaging_report.col_study') }}</th>
                        <th>{{ __('imaging_report.col_accession') }}</th>
                        <th>{{ __('imaging_report.col_completed') }}</th>
                        <th>{{ __('imaging_report.col_report') }}</th>
                        <th class="od-table__actions">{{ __('imaging_report.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($awaiting as $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $draft = $drafts[$order->id] ?? null;
                    @endphp
                    <tr>
                        <td data-label="{{ __('imaging_report.col_patient') }}">
                            <div class="od-person">@include('portals.lab.partials._avatar', ['name' => $pName])<div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div></div>
                        </td>
                        <td data-label="{{ __('imaging_report.col_study') }}">
                            <span class="od-strong">{{ __('imaging_report.modality.' . ($order->modality ?: 'other')) }}</span>
                            <div class="od-muted">{{ $order->body_part ?: '—' }}</div>
                        </td>
                        <td data-label="{{ __('imaging_report.col_accession') }}" class="od-muted">{{ $order->accession_number ?: '—' }}</td>
                        <td data-label="{{ __('imaging_report.col_completed') }}" class="od-muted">{{ $order->completed_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('imaging_report.col_report') }}">
                            <span class="od-pill {{ $draft ? 'od-pill--pending' : 'od-pill--neutral' }}">{{ __('imaging_report.status.' . ($draft ? 'draft' : 'none')) }}</span>
                        </td>
                        <td class="od-table__actions" data-label="{{ __('imaging_report.col_actions') }}">
                            <a href="{{ route('portals.imaging.report.edit', $order->id) }}" class="od-btn od-btn--primary od-btn--sm">
                                <i data-lucide="file-pen-line"></i> {{ $draft ? __('imaging_report.open_report') : __('imaging_report.write_report') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Signed, not yet handed to the patient ──────────────────────────── --}}
<div class="od-card" data-section="undistributed">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('imaging_report.queue_undistributed') }}
            <span class="od-card__count">({{ $undistributed->count() }})</span></h2>
    </div>
    @if($undistributed->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="mail-check"></i></div>
                <h3>{{ __('imaging_report.queue_undistributed_empty') }}</h3>
            </div>
        </div>
    @else
        <div class="od-card__body"><p class="od-muted">{{ __('imaging_report.queue_undistributed_hint') }}</p></div>
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('imaging_report.col_patient') }}</th>
                        <th>{{ __('imaging_report.col_study') }}</th>
                        <th>{{ __('imaging_report.col_author') }}</th>
                        <th>{{ __('imaging_report.col_finalized') }}</th>
                        <th>{{ __('imaging_report.col_report') }}</th>
                        <th class="od-table__actions">{{ __('imaging_report.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($undistributed as $report)
                    @php
                        $p     = $report->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('imaging_report.col_patient') }}">
                            <div class="od-person">@include('portals.lab.partials._avatar', ['name' => $pName])<div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div></div>
                        </td>
                        <td data-label="{{ __('imaging_report.col_study') }}">
                            <span class="od-strong">{{ __('imaging_report.modality.' . $report->modality) }}</span>
                            <div class="od-muted">{{ $report->body_part ?: '—' }}</div>
                        </td>
                        <td data-label="{{ __('imaging_report.col_author') }}">{{ $report->reportedBy?->name ?? __('imaging_report.unknown') }}</td>
                        <td data-label="{{ __('imaging_report.col_finalized') }}" class="od-muted">{{ $report->finalized_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('imaging_report.col_report') }}">
                            <span class="od-pill od-pill--success">{{ __('imaging_report.status.' . $report->status) }}</span>
                            <span class="od-pill od-pill--neutral">{{ __('imaging_report.version_n', ['n' => (int) $report->version]) }}</span>
                        </td>
                        <td class="od-table__actions" data-label="{{ __('imaging_report.col_actions') }}">
                            @if($report->imaging_order_id)
                            <a href="{{ route('portals.imaging.report.edit', $report->imaging_order_id) }}" class="od-btn od-btn--secondary od-btn--sm">
                                <i data-lucide="send"></i> {{ __('imaging_report.open_report') }}
                            </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

</div>
@endsection
