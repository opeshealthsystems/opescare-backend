@extends('layouts.portal')

@section('title', __('imaging_report.page_title'))
@section('breadcrumb_home', __('imaging_desk.title'))
@section('breadcrumb_home_url', \Illuminate\Support\Facades\Route::has('portals.imaging.index') ? route('portals.imaging.index') : route('portals.imaging.reports.index'))
@section('breadcrumb_section', __('imaging_report.page_title'))

@php
    $p     = $patient;
    $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

    // Age BAND only, never the date of birth (spec: "patient (age band, never DOB)").
    try { $dob = $p?->date_of_birth; } catch (\Throwable) { $dob = null; }
    $ageBand = $dob ? \App\Support\ResultFlagResolver::ageBand($dob) : 'unknown';

    $modalityKey = $order->modality ?: 'other';
    $urgencyKey  = in_array($order->urgency, ['routine', 'urgent', 'stat'], true) ? $order->urgency : 'routine';
    $urgencyBadge = 'od-pill od-pill--' . match ($urgencyKey) { 'stat' => 'danger', 'urgent' => 'warning', default => 'neutral' };

    $statusKey   = $report?->status ?? 'none';
    $statusBadge = 'od-pill od-pill--' . match ($statusKey) {
        'final', 'amended', 'corrected' => 'success',
        'draft', 'preliminary'          => 'warning',
        default                         => 'neutral',
    };

    $editable    = $report === null || in_array($report->status, ['draft', 'preliminary'], true);
    $signed      = $report !== null && $report->isReleased();
    $distributed = $report !== null && $report->isDistributed();
    $priorDistributed = $signed && ! $distributed && $versions->contains(fn ($v) => $v->distributed_at !== null);
    $attachment  = $report?->attachment()->with('fileAsset')->first();
    $earlier     = $versions->filter(fn ($v) => $report === null || $v->id !== $report->id);

    $showUrl = \Illuminate\Support\Facades\Route::has('portals.imaging.show') ? route('portals.imaging.show', $order->id) : null;
@endphp

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('imaging_report.page_title') }}</h1>
        <p class="od-page__sub odl-pills">
            <span class="{{ $statusBadge }}">{{ __('imaging_report.status.' . $statusKey) }}</span>
            @if($report)<span class="od-pill od-pill--neutral">{{ __('imaging_report.version_n', ['n' => (int) $report->version]) }}</span>@endif
            <span class="{{ $urgencyBadge }}">{{ __('imaging_report.urgency_label.' . $urgencyKey) }}</span>
        </p>
    </div>
    <div class="od-row">
        @if($showUrl)
        <a href="{{ $showUrl }}" class="od-btn od-btn--secondary"><i data-lucide="clipboard-list"></i> {{ __('imaging_report.back_to_order') }}</a>
        @endif
        <a href="{{ route('portals.imaging.reports.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('imaging_report.back_to_queue') }}</a>
    </div>
</div>

@if(session('success'))<div class="od-flash od-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="od-flash od-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><div><ul class="alert-list">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div></div>
@endif

@if(! ($order->status === \App\Enums\ImagingOrderStatus::Completed || $order->status === 'completed') && $editable)
<div class="od-flash od-flash--info"><i data-lucide="info"></i><div>{{ __('imaging_report.order_not_completed') }}</div></div>
@endif

{{-- ── The study ─────────────────────────────────────────────────────── --}}
<div class="od-card" data-section="order-facts">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_report.order_facts') }}</h2></div>
    <div class="od-card__body">
        <div class="form-grid">
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.patient') }}</div>
                <div class="od-strong">{{ $pName !== '' ? $pName : '—' }}</div>
                <div class="od-muted">
                    {{ $p?->health_id ?? '—' }}
                    &middot; {{ __('imaging_report.age_bands.' . $ageBand) }}
                    @if($p?->sex) &middot; {{ $p->sex }} @endif
                </div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.modality_label') }}</div>
                <div class="od-strong">{{ __('imaging_report.modality.' . $modalityKey) }}</div>
                <div class="od-muted">{{ __('imaging_report.body_part') }}: {{ $order->body_part ?: '—' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.accession') }}</div>
                <div>{{ $order->accession_number ?: '—' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.ordered_by') }}</div>
                <div>{{ $order->orderedBy?->name ?? __('imaging_report.unknown') }}</div>
                <div class="od-muted">{{ $order->referring_physician ? __('imaging_report.referring') . ': ' . $order->referring_physician : '' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.completed_at') }}</div>
                <div>{{ $order->completed_at?->translatedFormat('d M Y H:i') ?? __('imaging_report.not_yet') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.indication') }}</div>
                <div>{{ $order->clinical_indication ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Draft: the editable form ──────────────────────────────────────── --}}
@if($editable)
<div class="od-card" data-section="report-form">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_report.form_title') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.imaging.report.save', $order->id) }}" enctype="multipart/form-data" id="report-form">
            @csrf
            <div class="form-group">
                <label class="form-label" for="rr-indication">{{ __('imaging_report.indication') }}</label>
                <textarea id="rr-indication" name="clinical_indication" rows="2" class="od-input" maxlength="5000">{{ old('clinical_indication', $report->clinical_indication ?? $order->clinical_indication) }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label form-label-required" for="rr-findings">{{ __('imaging_report.findings') }}</label>
                <textarea id="rr-findings" name="findings" rows="8" class="od-input" required minlength="3" maxlength="20000">{{ old('findings', $report->findings ?? '') }}</textarea>
                <div class="form-hint">{{ __('imaging_report.findings_hint') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label form-label-required" for="rr-impression">{{ __('imaging_report.impression') }}</label>
                <textarea id="rr-impression" name="impression" rows="3" class="od-input" required minlength="3" maxlength="5000">{{ old('impression', $report->impression ?? '') }}</textarea>
                <div class="form-hint">{{ __('imaging_report.impression_hint') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="rr-recommendation">{{ __('imaging_report.recommendation') }}</label>
                <textarea id="rr-recommendation" name="recommendation" rows="2" class="od-input" maxlength="5000">{{ old('recommendation', $report->recommendation ?? '') }}</textarea>
                <div class="form-hint">{{ __('imaging_report.recommendation_hint') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="rr-attachment">{{ __('imaging_report.attachment') }}</label>
                <input type="file" id="rr-attachment" name="attachment" class="od-input" accept="application/pdf">
                <div class="form-hint">{{ __('imaging_report.attachment_hint') }}</div>
                @if($attachment && $attachment->fileAsset)
                <div class="od-muted">
                    {{ __('imaging_report.attachment_current') }}:
                    <a href="{{ route('portals.imaging.report.attachment', $order->id) }}">{{ $attachment->fileAsset->original_name }}</a>
                    &middot; {{ __('imaging_report.attachment_replace') }}
                </div>
                @endif
            </div>
            <div class="od-row">
                <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="save"></i> <span>{{ __('imaging_report.save_draft') }}</span></button>
                <button type="submit" class="od-btn od-btn--primary"
                        formaction="{{ route('portals.imaging.report.finalize', $order->id) }}"
                        data-confirm="{{ __('imaging_report.finalize_confirm') }}">
                    <i data-lucide="pen-line"></i> <span>{{ __('imaging_report.finalize') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ── Signed: read-only text, distribute, amend ─────────────────────── --}}
@if($signed)
<div class="od-card" data-section="report-signed">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('imaging_report.form_title') }}</h2>
    </div>
    <div class="od-card__body">
        <div class="form-grid">
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_author') }}</div>
                <div>{{ $report->reportedBy?->name ?? __('imaging_report.unknown') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_finalized') }}</div>
                <div>{{ $report->finalized_at?->translatedFormat('d M Y H:i') ?? '—' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_distributed') }}</div>
                <div>{{ $report->distributed_at?->translatedFormat('d M Y H:i') ?? __('imaging_report.panel_not_distributed') }}</div>
            </div>
            @if($report->status === 'amended' && filled($report->amendment_reason))
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.reason') }}</div>
                <div>{{ $report->amendment_reason }}</div>
            </div>
            @endif
        </div>

        @if(filled($report->clinical_indication))
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.indication') }}</div>
            <div>{!! nl2br(e($report->clinical_indication)) !!}</div>
        </div>
        @endif
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.findings') }}</div>
            <div>{!! nl2br(e($report->findings)) !!}</div>
        </div>
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.impression') }}</div>
            <div class="od-strong">{!! nl2br(e($report->impression)) !!}</div>
        </div>
        @if(filled($report->recommendation))
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.recommendation') }}</div>
            <div>{!! nl2br(e($report->recommendation)) !!}</div>
        </div>
        @endif
        @if($attachment && $attachment->fileAsset)
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.attachment_current') }}</div>
            <a href="{{ route('portals.imaging.report.attachment', $order->id) }}" class="od-btn od-btn--secondary od-btn--sm">
                <i data-lucide="paperclip"></i> {{ $attachment->fileAsset->original_name }}
            </a>
        </div>
        @endif

        <p class="od-muted">{{ __('imaging_report.readonly_note') }}</p>
        @if($priorDistributed)
        <div class="od-flash od-flash--info"><i data-lucide="info"></i><div>{{ __('imaging_report.redistribute_hint') }}</div></div>
        @endif

        <div class="od-row">
            @if(! $distributed)
            <form method="POST" action="{{ route('portals.imaging.report.distribute', $order->id) }}">
                @csrf
                <button type="submit" class="od-btn od-btn--primary" data-confirm="{{ __('imaging_report.distribute_confirm') }}">
                    <i data-lucide="send"></i> <span>{{ __('imaging_report.distribute') }}</span>
                </button>
            </form>
            @endif
            <details>
                <summary class="od-btn od-btn--secondary"><i data-lucide="file-diff"></i> {{ __('imaging_report.form_amend_title') }}</summary>
                <form method="POST" action="{{ route('portals.imaging.report.amend', $order->id) }}" enctype="multipart/form-data" class="mt-2" data-section="amend-form">
                    @csrf
                    <p class="od-muted">{{ __('imaging_report.form_amend_hint') }}</p>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="am-reason">{{ __('imaging_report.reason') }}</label>
                        <input type="text" id="am-reason" name="reason" class="od-input" required minlength="3" maxlength="1000" value="{{ old('reason') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="am-indication">{{ __('imaging_report.indication') }}</label>
                        <textarea id="am-indication" name="clinical_indication" rows="2" class="od-input" maxlength="5000">{{ old('clinical_indication', $report->clinical_indication) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="am-findings">{{ __('imaging_report.findings') }}</label>
                        <textarea id="am-findings" name="findings" rows="8" class="od-input" required minlength="3" maxlength="20000">{{ old('findings', $report->findings) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="am-impression">{{ __('imaging_report.impression') }}</label>
                        <textarea id="am-impression" name="impression" rows="3" class="od-input" required minlength="3" maxlength="5000">{{ old('impression', $report->impression) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="am-recommendation">{{ __('imaging_report.recommendation') }}</label>
                        <textarea id="am-recommendation" name="recommendation" rows="2" class="od-input" maxlength="5000">{{ old('recommendation', $report->recommendation) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="am-attachment">{{ __('imaging_report.attachment') }}</label>
                        <input type="file" id="am-attachment" name="attachment" class="od-input" accept="application/pdf">
                        <div class="form-hint">{{ __('imaging_report.attachment_hint') }}</div>
                    </div>
                    <button type="submit" class="od-btn od-btn--primary" data-confirm="{{ __('imaging_report.amend_confirm') }}">
                        <i data-lucide="file-diff"></i> <span>{{ __('imaging_report.amend') }}</span>
                    </button>
                </form>
            </details>
        </div>
    </div>
</div>
@endif

{{-- ── Earlier versions, kept in full ────────────────────────────────── --}}
@if($earlier->isNotEmpty())
<div class="od-card" data-section="report-versions">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('imaging_report.panel_versions') }}</h2></div>
    <div class="od-card__body">
        @foreach($earlier as $v)
        <details class="mb-2">
            <summary class="od-strong">{{ __('imaging_report.panel_version_row', [
                'n'      => (int) $v->version,
                'status' => __('imaging_report.status.' . ($v->superseded_at ? 'superseded' : $v->status)),
                'date'   => ($v->finalized_at ?? $v->updated_at)?->translatedFormat('d M Y H:i') ?? '—',
            ]) }} &middot; {{ $v->reportedBy?->name ?? __('imaging_report.unknown') }}</summary>
            <div class="form-group mt-2">
                <div class="form-label">{{ __('imaging_report.findings') }}</div>
                <div>{!! nl2br(e($v->findings)) !!}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.impression') }}</div>
                <div>{!! nl2br(e($v->impression)) !!}</div>
            </div>
            @if(filled($v->amendment_reason))
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.reason') }}</div>
                <div class="od-muted">{{ $v->amendment_reason }}</div>
            </div>
            @endif
        </details>
        @endforeach
    </div>
</div>
@endif

</div>
@endsection
