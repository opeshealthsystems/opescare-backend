{{-- PATIENT-REPORTED. Its own tab and its own audit category, never a section of
     the clinical record: folding self-reported symptom text in beside coded
     diagnoses is how a sentence a patient typed becomes a finding somebody
     prescribes against (see MobileDoctorPreconsultController). --}}
<div class="alert alert-warning mb-4" role="note">
    <i data-lucide="message-square-text"></i>
    <div>
        <strong>{{ __('chart.preconsult_badge') }}</strong>
        <p>{{ __('chart.preconsult_note') }}</p>
    </div>
</div>

@if($submissions->isEmpty())
<div class="panel mb-4"><div class="panel-body"><p class="td-muted">{{ __('chart.preconsult_none') }}</p></div></div>
@else
    @foreach($submissions as $s)
    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="clipboard-pen-line"></i>
                {{ __('chart.col_submitted') }}: {{ $s['submitted_at'] ? \Illuminate\Support\Carbon::parse($s['submitted_at'])->format('d M Y H:i') : '—' }}
            </h2>
            <span class="badge badge-warning">{{ __('chart.preconsult_badge') }}</span>
        </div>
        <div class="panel-body">
            <div class="td-muted mb-3">{{ __('chart.answers_count', ['count' => $s['answered_count']]) }}</div>
            @if(empty($s['answers']))
                <p class="td-muted">—</p>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <tbody>
                        @foreach($s['answers'] as $answer)
                            <tr>
                                <td class="td-strong">{{ $answer['question_label'] }}</td>
                                <td>{{ $answer['value_label'] ?? $answer['value'] }}</td>
                                <td class="td-muted"><small>{{ $answer['provenance']['label'] }}</small></td>
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
