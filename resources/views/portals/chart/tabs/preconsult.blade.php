{{-- PATIENT-REPORTED. Its own tab and its own audit category, never a section of
     the clinical record: folding self-reported symptom text in beside coded
     diagnoses is how a sentence a patient typed becomes a finding somebody
     prescribes against (see MobileDoctorPreconsultController). --}}
<div class="odc-note odc-note--pending" role="note">
    <i data-lucide="message-square-text"></i>
    <div>
        <strong>{{ __('chart.preconsult_badge') }}</strong>
        <p>{{ __('chart.preconsult_note') }}</p>
    </div>
</div>

@if($submissions->isEmpty())
<div class="od-card"><div class="od-card__body"><p class="od-muted">{{ __('chart.preconsult_none') }}</p></div></div>
@else
    @foreach($submissions as $s)
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">
                <i data-lucide="clipboard-pen-line"></i>
                {{ __('chart.col_submitted') }}: {{ $s['submitted_at'] ? \Illuminate\Support\Carbon::parse($s['submitted_at'])->translatedFormat('d M Y H:i') : '—' }}
            </h2>
            <span class="od-pill od-pill--pending">{{ __('chart.preconsult_badge') }}</span>
        </div>
        <div class="od-card__body">
            <div class="od-muted">{{ __('chart.answers_count', ['count' => $s['answered_count']]) }}</div>
            @if(empty($s['answers']))
                <p class="od-muted">—</p>
            @else
                <div class="od-table-wrap">
                    <table class="od-table">
                        <tbody>
                        @foreach($s['answers'] as $answer)
                            <tr>
                                <td class="odc-strong">{{ $answer['question_label'] }}</td>
                                <td>{{ $answer['value_label'] ?? $answer['value'] }}</td>
                                <td class="od-muted"><small>{{ $answer['provenance']['label'] }}</small></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endforeach
@endif
