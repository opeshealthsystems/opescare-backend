{{--
    Two tables that must never merge.

    1. Facility-measured observations (`triage_vital_signs`, legacy
       `vital_signs`) — VitalProvenance::FacilityMeasured on every row.
       Advisory flags appear ONLY when $rows[n]['flags'] is non-empty, which
       TriageBandContext guarantees is never the case for a child, an unknown
       date of birth or a recorded pregnancy. When the patient-level context
       refuses, the refusal is printed and no flag of any kind is rendered.

    2. The patient's own readings (`patient_recorded_vitals`) —
       VitalProvenance::SelfReported on every row, never interpreted.
--}}
@php
    $flagBadge = fn (string $severity) => 'od-pill od-pill--' . match ($severity) {
        'critical' => 'urgent',
        'warning'  => 'pending',
        'success'  => 'ready',
        default    => 'neutral',
    };
    $facilityBadge = \App\Enums\VitalProvenance::FacilityMeasured;
    $selfBadge     = \App\Enums\VitalProvenance::SelfReported;
@endphp

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.facility_vitals_title') }}</h2>
        @if($bands->eligible)
            <span class="od-pill od-pill--neutral">{{ __('chart.advisory_source') }}</span>
        @endif
    </div>
    <div class="od-card__body">

        @if(! $bands->eligible)
        <div class="odc-note odc-note--pending" role="alert">
            <i data-lucide="shield-alert"></i>
            <div>
                <strong>{{ __('chart.no_bands_title') }}</strong>
                <p>{{ __('chart.no_bands.' . $bands->reason) }}</p>
            </div>
        </div>
        @else
            <p class="odc-hint">{{ __('chart.advisory_note') }}</p>
        @endif

        @if($rows->isEmpty())
            <p class="od-muted">{{ __('chart.facility_vitals_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_recorded_at') }}</th>
                        <th>{{ __('chart.col_provenance') }}</th>
                        <th>{{ __('chart.measure.temperature') }}</th>
                        <th>{{ __('chart.measure.pulse_rate') }}</th>
                        <th>{{ __('chart.measure.respiratory_rate') }}</th>
                        <th>{{ __('chart.measure.blood_pressure') }}</th>
                        <th>{{ __('chart.measure.oxygen_saturation') }}</th>
                        <th>{{ __('chart.measure.weight_kg') }}</th>
                        <th>{{ __('chart.measure.height_cm') }}</th>
                        <th>{{ __('chart.measure.gcs_score') }}</th>
                        <th>{{ __('chart.col_recorded_by') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $v)
                        @php
                            $flag = fn (string $measure) => $v['flags'][$measure] ?? null;
                            $cell = function (string $measure, $value) use ($flag, $flagBadge) {
                                if ($value === null) {
                                    return '<span class="od-muted">—</span>';
                                }
                                $out = e($value);
                                $f   = $flag($measure);
                                if ($f && $f['label_key']) {
                                    $out .= ' <span class="' . e($flagBadge($f['severity'])) . '">' . e(__($f['label_key'])) . '</span>';
                                }
                                return $out;
                            };
                            $bp = ($v['systolic_bp'] !== null && $v['diastolic_bp'] !== null)
                                ? $v['systolic_bp'] . '/' . $v['diastolic_bp']
                                : ($v['systolic_bp'] ?? null);
                        @endphp
                        <tr>
                            <td class="od-muted">
                                {{ $v['recorded_at']?->translatedFormat('d M Y H:i') ?? '—' }}
                                @if($v['time_is_entry'])<br><small>{{ __('chart.entered_at_note') }}</small>@endif
                            </td>
                            <td>
                                <span class="od-pill od-pill--ready">{{ __($facilityBadge->labelKey()) }}</span>
                                @if($bands->eligible && $v['pregnancy'])
                                    <br><small class="od-muted">{{ __('chart.row_no_bands_pregnancy') }}</small>
                                @endif
                            </td>
                            <td>{!! $cell('temperature', $v['temperature']) !!}</td>
                            <td>{!! $cell('pulse_rate', $v['pulse_rate']) !!}</td>
                            <td>{!! $cell('respiratory_rate', $v['respiratory_rate']) !!}</td>
                            <td>{!! $cell('blood_pressure', $bp) !!}</td>
                            <td>{!! $cell('oxygen_saturation', $v['oxygen_saturation']) !!}</td>
                            <td>{{ $v['weight_kg'] ?? '—' }}</td>
                            <td>{{ $v['height_cm'] ?? '—' }}</td>
                            <td>{{ $v['gcs_score'] ?? '—' }}</td>
                            <td>{{ $v['recorded_by'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.self_reported_title') }}</h2>
        <span class="od-pill od-pill--pending">{{ __($selfBadge->labelKey()) }}</span>
    </div>
    <div class="od-card__body">
        <p class="odc-hint">{{ __('chart.self_reported_note') }} {{ __($selfBadge->descriptionKey()) }}</p>

        @if($selfReported->isEmpty())
            <p class="od-muted">{{ __('chart.self_reported_empty') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.measured_at') }}</th>
                        <th>{{ __('chart.col_provenance') }}</th>
                        <th>{{ __('chart.col_measure') }}</th>
                        <th>{{ __('chart.col_value') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($selfReported as $reading)
                        <tr>
                            <td class="od-muted">{{ $reading->measured_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                            <td><span class="od-pill od-pill--pending">{{ __($selfBadge->labelKey()) }}</span></td>
                            <td class="odc-strong">{{ $reading->measure ? __($reading->measure->labelKey()) : '—' }}</td>
                            <td>
                                {{ $reading->value_primary }}@if($reading->value_secondary !== null)/{{ $reading->value_secondary }}@endif
                                <span class="od-muted">{{ $reading->unit }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
