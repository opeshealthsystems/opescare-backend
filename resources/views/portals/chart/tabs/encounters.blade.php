@if($encounters->isEmpty())
<div class="panel mb-4"><div class="panel-body"><p class="td-muted">{{ __('chart.encounters_none') }}</p></div></div>
@else
    @foreach($encounters as $e)
    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="stethoscope"></i>
                {{ $e['started_at']?->translatedFormat('d M Y H:i') ?? '—' }}
                @if($e['type']) &middot; @enum($e['type']) @endif
            </h2>
            @if($e['status'])<span class="badge badge-neutral">@enum($e['status'])</span>@endif
        </div>
        <div class="panel-body">
            <div class="td-muted mb-3">
                {{ __('chart.col_facility') }}: {{ $e['facility_name'] ?: '—' }}
                &middot; {{ __('chart.col_doctor') }}: {{ $e['doctor_name'] ?: '—' }}
                @if($e['ended_at']) &middot; {{ __('chart.col_ended') }}: {{ $e['ended_at']->translatedFormat('d M Y H:i') }} @endif
            </div>

            <div class="field-grid">
                <div>
                    <div class="td-muted">{{ __('chart.reason') }}</div>
                    <div>{{ $e['reason'] ?: __('chart.not_recorded') }}</div>
                </div>
                <div>
                    <div class="td-muted">{{ __('chart.discharge') }}</div>
                    <div>{{ $e['discharge'] ?: __('chart.not_recorded') }}</div>
                </div>
            </div>

            @if($e['history'] === null && $e['examination'] === null && $e['plan'] === null)
                <p class="td-muted mt-3">{{ __('chart.note_none') }}</p>
            @else
                <div class="mt-3">
                    <div class="td-muted">{{ __('chart.history') }}</div>
                    <p>{{ $e['history'] ?: __('chart.not_recorded') }}</p>
                    <div class="td-muted">{{ __('chart.examination') }}</div>
                    <p>{{ $e['examination'] ?: __('chart.not_recorded') }}</p>
                    <div class="td-muted">{{ __('chart.plan') }}</div>
                    <p>{{ $e['plan'] ?: __('chart.not_recorded') }}</p>
                </div>
            @endif
        </div>
    </div>
    @endforeach
@endif
