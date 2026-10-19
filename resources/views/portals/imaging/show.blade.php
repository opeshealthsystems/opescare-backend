@extends('layouts.portal')

@section('title', __('imaging_desk.order_title'))

@php
    use App\Enums\ImagingOrderStatus;

    $p     = $order->patient;
    $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
    $urgencyBadge = 'od-pill od-pill--' . match ((string) $order->urgency) { 'stat' => 'danger', 'urgent' => 'warning', default => 'neutral' };

    $canSchedule = $status->canTransitionTo(ImagingOrderStatus::Scheduled);
    $canStart    = $status->canTransitionTo(ImagingOrderStatus::InProgress);
    $canComplete = $status->canTransitionTo(ImagingOrderStatus::Completed);
    $canCancel   = $status->canTransitionTo(ImagingOrderStatus::Cancelled);
@endphp

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('imaging_desk.modality.' . $order->modality) }}@if($order->body_part) &middot; {{ $order->body_part }}@endif</h1>
        <p class="od-page__sub odl-pills">
            <span class="od-pill od-pill--{{ $status->value }}">{{ __($status->labelKey()) }}</span>
            <span class="{{ $urgencyBadge }}">{{ __('imaging_desk.urgency.' . $order->urgency) }}</span>
            <span class="od-muted">{{ $order->accession_number }}</span>
        </p>
    </div>
    <a href="{{ route('documents.new.imaging_request', $order->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_imaging_request') }}</a>
    <a href="{{ route('portals.imaging.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('imaging_desk.back_to_worklist') }}
    </a>
</div>

@if(session('success'))<div class="od-flash od-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="od-flash od-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><div><ul class="alert-list">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div></div>
@endif

{{-- ── The order ─────────────────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_desk.order_title') }}</h2></div>
    <div class="od-card__body">
        <div class="odl-fgrid">
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.patient') }}</div>
                <div class="od-strong">{{ $pName !== '' ? $pName : '—' }}</div>
                <div class="od-muted">
                    {{ $p?->health_id ?? '—' }}
                    &middot; {{ $ageBand ? __('imaging_desk.band.' . $ageBand) : __('imaging_desk.age_band_unknown') }}
                    @if($p?->sex) &middot; @enum($p->sex) @endif
                </div>
            </div>
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.accession') }}</div>
                <div class="od-strong">{{ $order->accession_number ?: '—' }}</div>
            </div>
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.ordered_by') }}</div>
                <div>{{ $order->orderedBy?->name ?? __('imaging_desk.unknown') }}</div>
                <div class="od-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</div>
            </div>
            @if($order->referring_physician)
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.referring_physician') }}</div>
                <div>{{ $order->referring_physician }}</div>
            </div>
            @endif
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.scheduled_at') }}</div>
                <div>{{ $order->scheduled_at?->translatedFormat('d M Y H:i') ?? __('imaging_desk.not_yet') }}</div>
            </div>
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.completed_at') }}</div>
                <div>{{ $order->completed_at?->translatedFormat('d M Y H:i') ?? __('imaging_desk.not_yet') }}</div>
            </div>
            @if($order->clinical_indication)
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.clinical_indication') }}</div>
                <div>{{ $order->clinical_indication }}</div>
            </div>
            @endif
            @if($order->notes)
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.notes') }}</div>
                <div class="od-muted">{!! nl2br(e($order->notes)) !!}</div>
            </div>
            @endif
            @if($order->cancel_reason)
            <div class="odl-field">
                <div class="odl-label">{{ __('imaging_desk.cancel_reason') }}</div>
                <div>{{ $order->cancel_reason }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Timeline ──────────────────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_desk.timeline_title') }}</h2></div>
    <div class="od-table-wrap">
        <table class="od-table">
            <tbody>
            @forelse($timeline as $row)
                <tr>
                    <td class="od-strong">{{ __('imaging_desk.timeline.' . $row['key']) }}</td>
                    <td class="od-muted">{{ $row['at']->translatedFormat('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="od-muted">{{ __('imaging_desk.timeline_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Actions by status ─────────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_desk.actions_title') }}</h2></div>
    <div class="od-card__body">
        @if($status->isTerminal())
            <p class="od-muted">{{ __('imaging_desk.no_actions') }}</p>
        @else
            <div class="odl-fgrid">
                @if($canSchedule)
                <form method="POST" action="{{ route('portals.imaging.schedule', $order->id) }}" class="odl-field" data-confirm="{{ __('imaging_desk.schedule_confirm') }}">
                    @csrf
                    <label class="odl-label odl-label--req" for="img-schedule-at">{{ __('imaging_desk.scheduled_slot') }}</label>
                    <input type="datetime-local" id="img-schedule-at" name="scheduled_at" class="od-input" required value="{{ old('scheduled_at') }}">
                    <button type="submit" class="od-btn od-btn--primary odl-mt"><i data-lucide="calendar-clock"></i> <span>{{ __('imaging_desk.schedule') }}</span></button>
                </form>
                @endif

                @if($canStart)
                <form method="POST" action="{{ route('portals.imaging.start', $order->id) }}" class="odl-field" data-confirm="{{ __('imaging_desk.start_confirm') }}">
                    @csrf
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="play"></i> <span>{{ __('imaging_desk.start') }}</span></button>
                </form>
                @endif

                @if($canComplete)
                <form method="POST" action="{{ route('portals.imaging.complete', $order->id) }}" class="odl-field" data-confirm="{{ __('imaging_desk.complete_confirm') }}">
                    @csrf
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check-circle"></i> <span>{{ __('imaging_desk.complete') }}</span></button>
                </form>
                @endif

                @if($canCancel)
                <form method="POST" action="{{ route('portals.imaging.cancel', $order->id) }}" class="odl-field" data-confirm="{{ __('imaging_desk.cancel_confirm') }}">
                    @csrf
                    <label class="odl-label odl-label--req" for="img-cancel-reason">{{ __('imaging_desk.reason') }}</label>
                    <input type="text" id="img-cancel-reason" name="reason" class="od-input" required maxlength="500" value="{{ old('reason') }}">
                    <div class="odl-hint">{{ __('imaging_desk.reason_hint') }}</div>
                    <button type="submit" class="od-btn od-btn--danger odl-mt"><i data-lucide="x-circle"></i> <span>{{ __('imaging_desk.cancel_order') }}</span></button>
                </form>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ── Report panel. The report desk fills this section through
     portals.imaging._report_panel; until it lands the panel states that no
     report exists, which is the truth, not a placeholder. ─────────────── --}}
<section id="report-panel" class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_desk.report_title') }}</h2></div>
    @includeIf('portals.imaging._report_panel', ['order' => $order, 'canReport' => $canReport])
    @if(! view()->exists('portals.imaging._report_panel'))
    <div class="od-card__body">
        @if($order->report)
            <span class="od-pill od-pill--info">{{ __('imaging_desk.report_status.' . $order->report->status) }}</span>
        @else
            <p class="od-muted">{{ __('imaging_desk.report_none') }}</p>
        @endif
    </div>
    @endif
</section>

</div>
@endsection
