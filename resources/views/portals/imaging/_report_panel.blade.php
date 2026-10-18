{{--
    Report panel on the imaging order detail page (portals/imaging/show).

    Contract with the detail page: included with `$order` in scope INSIDE a
    <section class="od-card"> whose header is already drawn, so this partial
    renders panel bodies only. Reads the CURRENT report version through
    RadiologyReportService; earlier versions are listed, never hidden.
--}}
@php
    $reportSvc = app(\App\Services\Lab\RadiologyReportService::class);
    $current   = $reportSvc->currentForOrder($order);
    $versions  = $reportSvc->versionsForOrder($order);
    $earlier   = $versions->filter(fn ($v) => $current === null || $v->id !== $current->id);
    $statusKey = $current?->status ?? 'none';
    $statusBadge = 'od-pill od-pill--' . match ($statusKey) {
        'final', 'amended', 'corrected' => 'success',
        'draft', 'preliminary'          => 'warning',
        default                         => 'neutral',
    };
    $attachment = $current?->attachment()->with('fileAsset')->first();
@endphp

<div class="od-card__body" data-section="report-panel">
    @if($current === null)
        <p class="od-strong">{{ __('imaging_report.panel_none') }}</p>
        <p class="od-muted">{{ __('imaging_report.panel_none_hint') }}</p>
    @else
        <div class="form-grid">
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_status') }}</div>
                <div>
                    <span class="{{ $statusBadge }}">{{ __('imaging_report.status.' . $statusKey) }}</span>
                    <span class="od-pill od-pill--neutral">{{ __('imaging_report.version_n', ['n' => (int) $current->version]) }}</span>
                </div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_author') }}</div>
                <div>{{ $current->reportedBy?->name ?? __('imaging_report.unknown') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_finalized') }}</div>
                <div>{{ $current->finalized_at?->translatedFormat('d M Y H:i') ?? __('imaging_report.not_yet') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('imaging_report.panel_distributed') }}</div>
                <div>{{ $current->distributed_at?->translatedFormat('d M Y H:i') ?? __('imaging_report.panel_not_distributed') }}</div>
            </div>
        </div>

        @if($current->isReleased() && filled($current->impression))
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.panel_impression') }}</div>
            <div>{{ $current->impression }}</div>
        </div>
        @endif

        @if($earlier->isNotEmpty())
        <div class="form-group">
            <div class="form-label">{{ __('imaging_report.panel_versions') }}</div>
            <ul class="od-muted">
                @foreach($earlier as $v)
                <li>{{ __('imaging_report.panel_version_row', [
                    'n'      => (int) $v->version,
                    'status' => __('imaging_report.status.' . ($v->superseded_at ? 'superseded' : $v->status)),
                    'date'   => ($v->finalized_at ?? $v->updated_at)?->translatedFormat('d M Y H:i') ?? '—',
                ]) }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    @endif

    @capability('imaging_report')
    <div class="od-row mt-2">
        <a href="{{ route('portals.imaging.report.edit', $order->id) }}" class="od-btn od-btn--primary od-btn--sm">
            <i data-lucide="file-pen-line"></i> <span>{{ $current === null ? __('imaging_report.write_report') : __('imaging_report.panel_open') }}</span>
        </a>
        @if($attachment && $attachment->fileAsset)
        <a href="{{ route('portals.imaging.report.attachment', $order->id) }}" class="od-btn od-btn--secondary od-btn--sm">
            <i data-lucide="paperclip"></i> <span>{{ __('imaging_report.download_attachment') }}</span>
        </a>
        @endif
    </div>
    @endcapability
</div>
