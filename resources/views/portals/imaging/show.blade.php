@extends('layouts.portal')

@section('title', __('imaging_desk.order_title'))
@section('breadcrumb_home', __('imaging_desk.title'))
@section('breadcrumb_home_url', route('portals.imaging.index'))
@section('breadcrumb_section', __('imaging_desk.order_title'))

@php
    use App\Enums\ImagingOrderStatus;

    $p     = $order->patient;
    $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
    $urgencyBadge = 'badge badge-' . match ((string) $order->urgency) { 'stat' => 'danger', 'urgent' => 'warning', default => 'neutral' };

    $canSchedule = $status->canTransitionTo(ImagingOrderStatus::Scheduled);
    $canStart    = $status->canTransitionTo(ImagingOrderStatus::InProgress);
    $canComplete = $status->canTransitionTo(ImagingOrderStatus::Completed);
    $canCancel   = $status->canTransitionTo(ImagingOrderStatus::Cancelled);
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('imaging_desk.modality.' . $order->modality) }}@if($order->body_part) &middot; {{ $order->body_part }}@endif</h1>
        <p class="page-subtitle">
            <span class="{{ $status->badgeClass() }}">{{ __($status->labelKey()) }}</span>
            <span class="{{ $urgencyBadge }}">{{ __('imaging_desk.urgency.' . $order->urgency) }}</span>
            <span class="td-muted">{{ $order->accession_number }}</span>
        </p>
    </div>
    <a href="{{ route('portals.imaging.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('imaging_desk.back_to_worklist') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i><div><ul class="alert-list">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div></div>
@endif

{{-- ── The order ─────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('imaging_desk.order_title') }}</h2></div>
    <div class="panel-body">
        <div class="form-grid">
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.patient') }}</div>
                <div class="td-strong">{{ $pName !== '' ? $pName : '—' }}</div>
                <div class="td-muted">
                    {{ $p?->health_id ?? '—' }}
                    &middot; {{ $ageBand ? __('imaging_desk.band.' . $ageBand) : __('imaging_desk.age_band_unknown') }}
                    @if($p?->sex) &middot; @enum($p->sex) @endif
                </div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.accession') }}</div>
                <div class="td-strong">{{ $order->accession_number ?: '—' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.ordered_by') }}</div>
                <div>{{ $order->orderedBy?->name ?? __('imaging_desk.unknown') }}</div>
                <div class="td-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</div>
            </div>
            @if($order->referring_physician)
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.referring_physician') }}</div>
                <div>{{ $order->referring_physician }}</div>
            </div>
            @endif
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.scheduled_at') }}</div>
                <div>{{ $order->scheduled_at?->translatedFormat('d M Y H:i') ?? __('imaging_desk.not_yet') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.completed_at') }}</div>
                <div>{{ $order->completed_at?->translatedFormat('d M Y H:i') ?? __('imaging_desk.not_yet') }}</div>
            </div>
            @if($order->clinical_indication)
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.clinical_indication') }}</div>
                <div>{{ $order->clinical_indication }}</div>
            </div>
            @endif
            @if($order->notes)
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.notes') }}</div>
                <div class="td-muted">{!! nl2br(e($order->notes)) !!}</div>
            </div>
            @endif
            @if($order->cancel_reason)
            <div class="form-group">
                <div class="form-label">{{ __('imaging_desk.cancel_reason') }}</div>
                <div>{{ $order->cancel_reason }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Timeline ──────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="history"></i> {{ __('imaging_desk.timeline_title') }}</h2></div>
    <div class="table-wrapper">
        <table class="data-table">
            <tbody>
            @foreach($timeline as $row)
                <tr>
                    <td class="td-strong">{{ __('imaging_desk.timeline.' . $row['key']) }}</td>
                    <td class="td-muted">{{ $row['at']->translatedFormat('d M Y H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ── Actions by status ─────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="play"></i> {{ __('imaging_desk.actions_title') }}</h2></div>
    <div class="panel-body">
        @if($status->isTerminal())
            <p class="td-muted">{{ __('imaging_desk.no_actions') }}</p>
        @else
            <div class="form-grid">
                @if($canSchedule)
                <form method="POST" action="{{ route('portals.imaging.schedule', $order->id) }}" class="form-group" data-confirm="{{ __('imaging_desk.schedule_confirm') }}">
                    @csrf
                    <label class="form-label form-label-required" for="img-schedule-at">{{ __('imaging_desk.scheduled_slot') }}</label>
                    <input type="datetime-local" id="img-schedule-at" name="scheduled_at" class="form-control" required value="{{ old('scheduled_at') }}">
                    <button type="submit" class="btn btn-primary mt-2"><i data-lucide="calendar-clock"></i> <span>{{ __('imaging_desk.schedule') }}</span></button>
                </form>
                @endif

                @if($canStart)
                <form method="POST" action="{{ route('portals.imaging.start', $order->id) }}" class="form-group" data-confirm="{{ __('imaging_desk.start_confirm') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i data-lucide="play"></i> <span>{{ __('imaging_desk.start') }}</span></button>
                </form>
                @endif

                @if($canComplete)
                <form method="POST" action="{{ route('portals.imaging.complete', $order->id) }}" class="form-group" data-confirm="{{ __('imaging_desk.complete_confirm') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> <span>{{ __('imaging_desk.complete') }}</span></button>
                </form>
                @endif

                @if($canCancel)
                <form method="POST" action="{{ route('portals.imaging.cancel', $order->id) }}" class="form-group" data-confirm="{{ __('imaging_desk.cancel_confirm') }}">
                    @csrf
                    <label class="form-label form-label-required" for="img-cancel-reason">{{ __('imaging_desk.reason') }}</label>
                    <input type="text" id="img-cancel-reason" name="reason" class="form-control" required maxlength="500" value="{{ old('reason') }}">
                    <div class="form-hint">{{ __('imaging_desk.reason_hint') }}</div>
                    <button type="submit" class="btn btn-danger mt-2"><i data-lucide="x-circle"></i> <span>{{ __('imaging_desk.cancel_order') }}</span></button>
                </form>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ── Report panel. The report desk fills this section through
     portals.imaging._report_panel; until it lands the panel states that no
     report exists, which is the truth, not a placeholder. ─────────────── --}}
<section id="report-panel" class="panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="file-text"></i> {{ __('imaging_desk.report_title') }}</h2></div>
    @includeIf('portals.imaging._report_panel', ['order' => $order, 'canReport' => $canReport])
    @if(! view()->exists('portals.imaging._report_panel'))
    <div class="panel-body">
        @if($order->report)
            <span class="badge badge-info">{{ __('imaging_desk.report_status.' . $order->report->status) }}</span>
        @else
            <p class="td-muted">{{ __('imaging_desk.report_none') }}</p>
        @endif
    </div>
    @endif
</section>

@endsection
