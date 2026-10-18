@extends('layouts.portal')

@php
    $isReports = request()->routeIs('portals.imaging.reports.index');
    $pageTitle = $isReports ? __('imaging_desk.reports_title') : __('imaging_desk.worklist_title');
    $urgencyBadge = fn (string $u) => 'badge badge-' . match ($u) {
        'stat'   => 'danger',
        'urgent' => 'warning',
        default  => 'neutral',
    };
    $queryBase = array_filter($filters, fn ($v, $k) => $k !== 'tab' && $v !== null && $v !== '', ARRAY_FILTER_USE_BOTH);
@endphp

@section('title', __('imaging_desk.title'))
@section('breadcrumb_home', __('imaging_desk.title'))
@section('breadcrumb_home_url', route('portals.imaging.index'))
@section('breadcrumb_section', $pageTitle)

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $pageTitle }}</h1>
        <p class="page-subtitle">{{ $isReports ? __('imaging_desk.reports_subtitle') : __('imaging_desk.worklist_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.imaging.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('imaging_desk.new_request') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── Filters. Exact Health ID only: no name search on this desk. ──────── --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ $isReports ? route('portals.imaging.reports.index') : route('portals.imaging.index') }}" class="form-grid">
            @unless($isReports)<input type="hidden" name="tab" value="{{ $tab }}">@endunless
            <div class="form-group">
                <label class="form-label" for="img-health-id">{{ __('imaging_desk.health_id') }}</label>
                <input type="text" id="img-health-id" name="health_id" class="form-control"
                       value="{{ $filters['health_id'] ?? '' }}" autocomplete="off" maxlength="64">
                <div class="form-hint">{{ __('imaging_desk.health_id_hint') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="img-modality">{{ __('imaging_desk.filter_modality') }}</label>
                <select id="img-modality" name="modality" class="form-control">
                    <option value="">{{ __('imaging_desk.any') }}</option>
                    @foreach($modalities as $m)
                        <option value="{{ $m }}" @selected(($filters['modality'] ?? '') === $m)>{{ __('imaging_desk.modality.' . $m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="img-urgency">{{ __('imaging_desk.filter_urgency') }}</label>
                <select id="img-urgency" name="urgency" class="form-control">
                    <option value="">{{ __('imaging_desk.any') }}</option>
                    @foreach($urgencies as $u)
                        <option value="{{ $u }}" @selected(($filters['urgency'] ?? '') === $u)>{{ __('imaging_desk.urgency.' . $u) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="img-date">{{ __('imaging_desk.filter_date') }}</label>
                <input type="date" id="img-date" name="date" class="form-control" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="form-group row-actions-inline">
                <button type="submit" class="btn btn-primary"><i data-lucide="filter"></i> <span>{{ __('imaging_desk.apply_filters') }}</span></button>
                @if($queryBase)
                    <a href="{{ $isReports ? route('portals.imaging.reports.index') : route('portals.imaging.index', ['tab' => $tab]) }}" class="btn btn-secondary">{{ __('imaging_desk.clear') }}</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="panel">
    @unless($isReports)
    <div class="panel-header">
        <nav class="row-actions-inline" aria-label="{{ __('imaging_desk.worklist_title') }}">
            @foreach($tabs as $t)
                <a href="{{ route('portals.imaging.index', ['tab' => $t] + $queryBase) }}"
                   class="btn btn-sm {{ $t === $tab ? 'btn-primary' : 'btn-secondary' }}">
                    {{ __('imaging_desk.tab.' . $t) }}
                    <span class="badge badge-neutral">{{ $counts[$t] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>
    </div>
    @endunless

    @if($orders->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="scan"></i></div>
                <h3>{{ __('imaging_desk.empty_worklist') }}</h3>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('imaging_desk.col_accession') }}</th>
                        <th>{{ __('imaging_desk.col_patient') }}</th>
                        <th>{{ __('imaging_desk.col_exam') }}</th>
                        <th>{{ __('imaging_desk.col_urgency') }}</th>
                        <th>{{ __('imaging_desk.col_status') }}</th>
                        <th>{{ __('imaging_desk.col_ordered_at') }}</th>
                        <th>{{ __('imaging_desk.col_scheduled_at') }}</th>
                        <th class="row-actions">{{ __('imaging_desk.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $st    = $order->status instanceof \App\Enums\ImagingOrderStatus ? $order->status : \App\Enums\ImagingOrderStatus::tryFrom((string) $order->status);
                    @endphp
                    <tr>
                        <td data-label="{{ __('imaging_desk.col_accession') }}"><span class="td-strong">{{ $order->accession_number ?: '—' }}</span></td>
                        <td data-label="{{ __('imaging_desk.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('imaging_desk.col_exam') }}">
                            <span class="td-strong">{{ __('imaging_desk.modality.' . $order->modality) }}</span>
                            @if($order->body_part)<div class="td-muted">{{ $order->body_part }}</div>@endif
                        </td>
                        <td data-label="{{ __('imaging_desk.col_urgency') }}">
                            <span class="{{ $urgencyBadge((string) $order->urgency) }}">{{ __('imaging_desk.urgency.' . $order->urgency) }}</span>
                        </td>
                        <td data-label="{{ __('imaging_desk.col_status') }}">
                            @if($st)<span class="{{ $st->badgeClass() }}">{{ __($st->labelKey()) }}</span>@endif
                        </td>
                        <td data-label="{{ __('imaging_desk.col_ordered_at') }}" class="td-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('imaging_desk.col_scheduled_at') }}" class="td-muted">{{ $order->scheduled_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td class="row-actions" data-label="{{ __('imaging_desk.col_open') }}">
                            <a href="{{ route('portals.imaging.show', $order->id) }}" class="btn btn-secondary btn-sm">
                                <i data-lucide="eye"></i> {{ __('imaging_desk.open_order') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="panel-body">{{ $orders->links() }}</div>
        @endif
    @endif
</div>

@endsection
