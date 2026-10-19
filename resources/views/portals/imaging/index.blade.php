@extends('layouts.portal')

@php
    $isReports = request()->routeIs('portals.imaging.reports.index');
    $pageTitle = $isReports ? __('imaging_desk.reports_title') : __('imaging_desk.worklist_title');
    $urgencyBadge = fn (string $u) => 'od-pill od-pill--dot od-pill--' . match ($u) {
        'stat'   => 'stat',
        'urgent' => 'urgent',
        default  => 'normal',
    };
    $tabDot = fn (string $t) => match ($t) { 'pending' => 'pending', 'awaiting_report' => 'urgent', 'reported', 'completed' => 'ready', 'in_progress' => 'teal', default => 'normal' };
    $queryBase = array_filter($filters, fn ($v, $k) => $k !== 'tab' && $v !== null && $v !== '', ARRAY_FILTER_USE_BOTH);
@endphp

@section('title', __('imaging_desk.title'))

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $pageTitle }}</h1>
        <p class="od-page__sub odl-pills">{{ $isReports ? __('imaging_desk.reports_subtitle') : __('imaging_desk.worklist_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.imaging.create') }}" class="od-btn od-btn--primary">
        <i data-lucide="plus"></i> {{ __('imaging_desk.new_request') }}
    </a>
</div>

@if(session('success'))<div class="od-flash od-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="od-flash od-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── Filters. Exact Health ID only: no name search on this desk. ──────── --}}
<div class="od-card">
    <div class="od-card__body">
        <form method="GET" action="{{ $isReports ? route('portals.imaging.reports.index') : route('portals.imaging.index') }}" class="odl-fgrid">
            @unless($isReports)<input type="hidden" name="tab" value="{{ $tab }}">@endunless
            <div class="odl-field">
                <label class="odl-label" for="img-health-id">{{ __('imaging_desk.health_id') }}</label>
                <input type="text" id="img-health-id" name="health_id" class="od-input"
                       value="{{ $filters['health_id'] ?? '' }}" autocomplete="off" maxlength="64">
                <div class="odl-hint">{{ __('imaging_desk.health_id_hint') }}</div>
            </div>
            <div class="odl-field">
                <label class="odl-label" for="img-modality">{{ __('imaging_desk.filter_modality') }}</label>
                <select id="img-modality" name="modality" class="od-input">
                    <option value="">{{ __('imaging_desk.any') }}</option>
                    @foreach($modalities as $m)
                        <option value="{{ $m }}" @selected(($filters['modality'] ?? '') === $m)>{{ __('imaging_desk.modality.' . $m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="odl-field">
                <label class="odl-label" for="img-urgency">{{ __('imaging_desk.filter_urgency') }}</label>
                <select id="img-urgency" name="urgency" class="od-input">
                    <option value="">{{ __('imaging_desk.any') }}</option>
                    @foreach($urgencies as $u)
                        <option value="{{ $u }}" @selected(($filters['urgency'] ?? '') === $u)>{{ __('imaging_desk.urgency.' . $u) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="odl-field">
                <label class="odl-label" for="img-date">{{ __('imaging_desk.filter_date') }}</label>
                <input type="date" id="img-date" name="date" class="od-input" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="odl-field od-row">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="filter"></i> <span>{{ __('imaging_desk.apply_filters') }}</span></button>
                @if($queryBase)
                    <a href="{{ $isReports ? route('portals.imaging.reports.index') : route('portals.imaging.index', ['tab' => $tab]) }}" class="od-btn od-btn--secondary">{{ __('imaging_desk.clear') }}</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="od-card">
    @unless($isReports)
    <div class="od-card__body">
        <nav class="od-tabs" aria-label="{{ __('imaging_desk.worklist_title') }}">
            @foreach($tabs as $t)
                <a href="{{ route('portals.imaging.index', ['tab' => $t] + $queryBase) }}"
                   class="od-tab {{ $t === $tab ? 'is-active' : '' }}" @if($t === $tab) aria-current="page" @endif>
                    <span class="od-tab__dot od-tab__dot--{{ $tabDot($t) }}"></span>{{ __('imaging_desk.tab.' . $t) }} <span class="od-tab__count">({{ $counts[$t] ?? 0 }})</span>
                </a>
            @endforeach
        </nav>
    </div>
    @endunless

    @if($orders->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="scan"></i></span>
                <p class="od-empty__title">{{ __('imaging_desk.empty_worklist') }}</p>
            </div>
        </div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('imaging_desk.col_accession') }}</th>
                        <th>{{ __('imaging_desk.col_patient') }}</th>
                        <th>{{ __('imaging_desk.col_exam') }}</th>
                        <th>{{ __('imaging_desk.col_urgency') }}</th>
                        <th>{{ __('imaging_desk.col_status') }}</th>
                        <th>{{ __('imaging_desk.col_ordered_at') }}</th>
                        <th>{{ __('imaging_desk.col_scheduled_at') }}</th>
                        <th class="od-table__actions">{{ __('imaging_desk.col_open') }}</th>
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
                        <td data-label="{{ __('imaging_desk.col_accession') }}"><span class="od-strong">{{ $order->accession_number ?: '—' }}</span></td>
                        <td data-label="{{ __('imaging_desk.col_patient') }}">
                            <div class="od-person">
                                @include('portals.lab.partials._avatar', ['name' => $pName])
                                <div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div>
                            </div>
                        </td>
                        <td data-label="{{ __('imaging_desk.col_exam') }}">
                            <div class="od-table__name">{{ __('imaging_desk.modality.' . $order->modality) }}</div>
                            @if($order->body_part)<div class="od-table__sub">{{ $order->body_part }}</div>@endif
                        </td>
                        <td data-label="{{ __('imaging_desk.col_urgency') }}">
                            <span class="{{ $urgencyBadge((string) $order->urgency) }}">{{ __('imaging_desk.urgency.' . $order->urgency) }}</span>
                        </td>
                        <td data-label="{{ __('imaging_desk.col_status') }}">
                            @if($st)<span class="od-pill od-pill--{{ $st->value }}">{{ __($st->labelKey()) }}</span>@endif
                        </td>
                        <td data-label="{{ __('imaging_desk.col_ordered_at') }}" class="od-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('imaging_desk.col_scheduled_at') }}" class="od-muted">{{ $order->scheduled_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td class="od-table__actions" data-label="{{ __('imaging_desk.col_open') }}">
                            <a href="{{ route('portals.imaging.show', $order->id) }}" class="od-btn od-btn--row">{{ __('imaging_desk.open_order') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="od-card__body">{{ $orders->links() }}</div>
        @endif
    @endif
</div>

</div>
@endsection
