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
    $flagBadge = fn (string $severity) => 'badge badge-' . match ($severity) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'success'  => 'success',
        default    => 'neutral',
    };
    $facilityBadge = \App\Enums\VitalProvenance::FacilityMeasured;
    $selfBadge     = \App\Enums\VitalProvenance::SelfReported;
@endphp

<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="activity"></i> {{ __('chart.facility_vitals_title') }}</h2>
        @if($bands->eligible)
            <span class="badge badge-neutral">{{ __('chart.advisory_source') }}</span>
        @endif
    </div>
    <div class="panel-body">

        @if(! $bands->eligible)
        <div class="alert alert-warning mb-4" role="alert">
            <i data-lucide="shield-alert"></i>
            <div>
                <strong>{{ __('chart.no_bands_title') }}</strong>
                <p>{{ __('chart.no_bands.' . $bands->reason) }}</p>
            </div>
        </div>
        @else
            <p class="form-hint mb-4">{{ __('chart.advisory_note') }}</p>
        @endif

        @if($rows->isEmpty())
            <p class="td-muted">{{ __('chart.facility_vitals_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                                    return '<span class="td-muted">—</span>';
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
                            <td class="td-muted">
                                {{ $v['recorded_at']?->format('d M Y H:i') ?? '—' }}
                                @if($v['time_is_entry'])<br><small>{{ __('chart.entered_at_note') }}</small>@endif
                            </td>
                            <td>
                                <span class="badge badge-success">{{ __($facilityBadge->labelKey()) }}</span>
                                @if($bands->eligible && $v['pregnancy'])
                                    <br><small class="td-muted">{{ __('chart.row_no_bands_pregnancy') }}</small>
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

<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="smartphone"></i> {{ __('chart.self_reported_title') }}</h2>
        <span class="badge badge-warning">{{ __($selfBadge->labelKey()) }}</span>
    </div>
    <div class="panel-body">
        <p class="form-hint mb-4">{{ __('chart.self_reported_note') }} {{ __($selfBadge->descriptionKey()) }}</p>

        @if($selfReported->isEmpty())
            <p class="td-muted">{{ __('chart.self_reported_empty') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
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
                            <td class="td-muted">{{ $reading->measured_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td><span class="badge badge-warning">{{ __($selfBadge->labelKey()) }}</span></td>
                            <td class="td-strong">{{ $reading->measure ? __($reading->measure->labelKey()) : '—' }}</td>
                            <td>
                                {{ $reading->value_primary }}@if($reading->value_secondary !== null)/{{ $reading->value_secondary }}@endif
                                <span class="td-muted">{{ $reading->unit }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
