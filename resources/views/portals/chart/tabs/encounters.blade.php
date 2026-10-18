@if($encounters->isEmpty())
<div class="od-card"><div class="od-card__body"><p class="od-muted">{{ __('chart.encounters_none') }}</p></div></div>
@else
    @foreach($encounters as $e)
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">
                <i data-lucide="stethoscope"></i>
                {{ $e['started_at']?->translatedFormat('d M Y H:i') ?? '—' }}
                @if($e['type']) &middot; @enum($e['type']) @endif
            </h2>
            @if($e['status'])<span class="od-pill od-pill--neutral">@enum($e['status'])</span>@endif
            <a href="{{ route('documents.new.encounter', $e['id']) }}" class="od-btn od-btn--secondary od-btn--sm" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_encounter_report') }}</a>
        </div>
        <div class="od-card__body">
            <div class="od-muted">
                {{ __('chart.col_facility') }}: {{ $e['facility_name'] ?: '—' }}
                &middot; {{ __('chart.col_doctor') }}: {{ $e['doctor_name'] ?: '—' }}
                @if($e['ended_at']) &middot; {{ __('chart.col_ended') }}: {{ $e['ended_at']->translatedFormat('d M Y H:i') }} @endif
            </div>

            <div class="odc-facts">
                <div>
                    <div class="od-muted">{{ __('chart.reason') }}</div>
                    <div>{{ $e['reason'] ?: __('chart.not_recorded') }}</div>
                </div>
                <div>
                    <div class="od-muted">{{ __('chart.discharge') }}</div>
                    <div>{{ $e['discharge'] ?: __('chart.not_recorded') }}</div>
                </div>
            </div>

            @if($e['history'] === null && $e['examination'] === null && $e['plan'] === null)
                <p class="od-muted odc-mt">{{ __('chart.note_none') }}</p>
            @else
                <div class="odc-mt">
                    <div class="od-muted">{{ __('chart.history') }}</div>
                    <p>{{ $e['history'] ?: __('chart.not_recorded') }}</p>
                    <div class="od-muted">{{ __('chart.examination') }}</div>
                    <p>{{ $e['examination'] ?: __('chart.not_recorded') }}</p>
                    <div class="od-muted">{{ __('chart.plan') }}</div>
                    <p>{{ $e['plan'] ?: __('chart.not_recorded') }}</p>
                </div>
            @endif
        </div>
    </div>
    @endforeach
@endif
