{{--
    The observation fields — shared by the assessment form and the
    reassessment form.

    Every field is EMPTY by default and stays empty on a bounce unless the
    nurse typed something. Nothing here is pre-filled from the patient's own
    `patient_recorded_vitals` readings, from a previous triage row, or from
    anywhere else: a blank field means "not measured", and a form that helpfully
    filled it in would turn that into a measurement nobody took.

    $consciousness — the AVPU list from TriageDeskService::CONSCIOUSNESS_LEVELS.
--}}
<div class="form-grid">
    @foreach([
        'temperature'       => ['step' => '0.1', 'min' => '20',  'max' => '45'],
        'pulse_rate'        => ['step' => '1',   'min' => '10',  'max' => '300'],
        'respiratory_rate'  => ['step' => '1',   'min' => '2',   'max' => '99'],
        'systolic_bp'       => ['step' => '1',   'min' => '40',  'max' => '300'],
        'diastolic_bp'      => ['step' => '1',   'min' => '20',  'max' => '200'],
        'oxygen_saturation' => ['step' => '1',   'min' => '20',  'max' => '100'],
        'weight_kg'         => ['step' => '0.1', 'min' => '0.3', 'max' => '400'],
        'height_cm'         => ['step' => '0.1', 'min' => '20',  'max' => '260'],
        'gcs_score'         => ['step' => '1',   'min' => '3',   'max' => '15'],
    ] as $field => $bounds)
        <div class="form-group">
            <label class="form-label" for="triage-{{ $field }}">{{ __('triage.' . $field) }}</label>
            <input type="number"
                   id="triage-{{ $field }}"
                   name="{{ $field }}"
                   class="form-control"
                   step="{{ $bounds['step'] }}"
                   min="{{ $bounds['min'] }}"
                   max="{{ $bounds['max'] }}"
                   value="{{ old($field) }}"
                   autocomplete="off">
        </div>
    @endforeach

    <div class="form-group">
        <label class="form-label" for="triage-consciousness">{{ __('triage.consciousness_level') }}</label>
        <select id="triage-consciousness" name="consciousness_level" class="form-control">
            <option value="">—</option>
            @foreach($consciousness as $level)
                <option value="{{ $level }}" @selected(old('consciousness_level') === $level)>
                    {{ __('triage.consciousness.' . $level) }}
                </option>
            @endforeach
        </select>
    </div>
</div>
