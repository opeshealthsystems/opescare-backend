{{-- Pain-site rows (patient_pain_sites). $rows rows named {$name}[i][field];
     a row with no region is skipped, so blank rows cost nothing. Left and right
     are the PATIENT'S. --}}
@php $name = $name ?? 'pain_sites'; $rows = $rows ?? 3; @endphp
<fieldset class="odc-field">
    <legend class="odc-label">{{ __('pain.fieldset') }}</legend>
    <p class="odc-hint">{{ __('pain.fieldset_hint') }}</p>
    @for($i = 0; $i < $rows; $i++)
    <div class="odc-form-grid">
        <div class="odc-field">
            <label class="odc-label" for="{{ $name }}-{{ $i }}-region">{{ __('pain.region_label') }}</label>
            <select id="{{ $name }}-{{ $i }}-region" name="{{ $name }}[{{ $i }}][region]" class="od-select">
                <option value="">—</option>
                @foreach(\App\Enums\PainRegion::cases() as $region)
                    <option value="{{ $region->value }}" @selected(old($name . '.' . $i . '.region') === $region->value)>{{ $region->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="odc-field">
            <label class="odc-label" for="{{ $name }}-{{ $i }}-severity">{{ __('pain.severity_label') }}</label>
            <input type="number" id="{{ $name }}-{{ $i }}-severity" name="{{ $name }}[{{ $i }}][severity]" class="od-input" min="0" max="10" step="1" value="{{ old($name . '.' . $i . '.severity') }}">
        </div>
        <div class="odc-field">
            <label class="odc-label" for="{{ $name }}-{{ $i }}-onset">{{ __('pain.onset_label') }}</label>
            <input type="datetime-local" id="{{ $name }}-{{ $i }}-onset" name="{{ $name }}[{{ $i }}][onset_at]" class="od-input" value="{{ old($name . '.' . $i . '.onset_at') }}">
        </div>
        <div class="odc-field">
            <label class="odc-label" for="{{ $name }}-{{ $i }}-trend">{{ __('pain.trend_label') }}</label>
            <select id="{{ $name }}-{{ $i }}-trend" name="{{ $name }}[{{ $i }}][trend]" class="od-select">
                <option value="">—</option>
                @foreach(\App\Enums\PainTrend::cases() as $trend)
                    <option value="{{ $trend->value }}" @selected(old($name . '.' . $i . '.trend') === $trend->value)>{{ $trend->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="odc-field">
            <label class="odc-label" for="{{ $name }}-{{ $i }}-note">{{ __('pain.note_label') }}</label>
            <input type="text" id="{{ $name }}-{{ $i }}-note" name="{{ $name }}[{{ $i }}][note]" class="od-input" maxlength="500" value="{{ old($name . '.' . $i . '.note') }}">
        </div>
    </div>
    @endfor
</fieldset>
