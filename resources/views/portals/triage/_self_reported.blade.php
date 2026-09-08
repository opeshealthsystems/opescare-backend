{{--
    The patient's OWN readings — context, never evidence.

    This panel is separated from the observations by more than a heading: the
    rows come from `patient_recorded_vitals` (App\Enums\VitalProvenance
    self_reported), they are never written into `triage_vital_signs`, they never
    pre-fill the observation form, and they are never passed to
    TriageBandContext::interpret(). A home blood-pressure cuff nobody checked
    must not be able to move a triage priority, so the only thing this panel
    does is show the nurse what the patient has been seeing.

    $selfReported — a collection of PatientRecordedVital.
--}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="smartphone"></i>
            {{ __('triage.self_reported_title') }}
        </h2>
        <span class="badge badge-neutral">{{ __('triage.self_reported_badge') }}</span>
    </div>
    <div class="panel-body">
        <p class="form-hint mb-4">{{ __('triage.self_reported_note') }}</p>

        @if($selfReported->isEmpty())
            <p class="td-muted">{{ __('triage.self_reported_empty') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <tbody>
                    @foreach($selfReported as $reading)
                        <tr>
                            <td class="td-strong">@enum($reading->measure?->value ?? '—')</td>
                            <td>
                                {{ $reading->value_primary }}@if($reading->value_secondary !== null)/{{ $reading->value_secondary }}@endif
                                <span class="td-muted">{{ $reading->unit }}</span>
                            </td>
                            <td class="td-muted">
                                {{ __('triage.measured_at') }}: {{ $reading->measured_at?->format('d M Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
