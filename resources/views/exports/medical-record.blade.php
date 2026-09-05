{{--
    Medical record export (POST /api/mobile/medical-records/export/pdf).

    Sits on the OpesCare Secure sheet — see pdf/layouts/secure.blade.php for
    the dompdf constraints. Every string comes from lang/*/export.php; enum
    values (status, severity) go through @enum so they read in the patient's
    language. Dates use translatedFormat for the same reason.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('export.record_title'))

@php
    $dash = __('export.not_recorded');
    $date = fn ($d) => $d ? $d->translatedFormat('d M Y') : null;
    $sexLabel = match ($patient->sex) {
        'male'   => __('export.sex_male'),
        'female' => __('export.sex_female'),
        null, '' => null,
        default  => __('export.sex_other'),
    };
    $flagTone = fn (?string $flag) => match (strtoupper((string) $flag)) {
        'H', 'L', 'A', 'ABNORMAL', 'HIGH', 'LOW' => 'warn',
        'HH', 'LL', 'C', 'CRITICAL', 'PANIC'      => 'bad',
        ''                                        => 'mute',
        default                                   => 'info',
    };
    $statusTone = fn (?string $status) => match (strtolower((string) $status)) {
        'active', 'dispensed', 'completed', 'resolved' => 'ok',
        'pending', 'chronic', 'partially_dispensed'    => 'warn',
        'inactive', 'cancelled', 'voided', 'expired'   => 'mute',
        default                                        => 'info',
    };
    $severityTone = fn (?string $severity) => match (strtolower((string) $severity)) {
        'severe', 'critical', 'life_threatening', 'high' => 'bad',
        'moderate', 'medium'                             => 'warn',
        default                                          => 'info',
    };
@endphp

@section('content')

<h1>{{ __('export.record_title') }}</h1>
<p class="subtitle">{{ __('export.record_subtitle') }}</p>

<h2>{{ __('export.section_identity') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('export.full_name') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('export.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('export.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('export.sex') }}</td><td class="v">{{ $sexLabel ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('export.blood_group') }}</td><td class="v">{{ $patient->blood_group ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('export.phone') }}</td><td class="v">{{ $patient->phone_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('export.generated') }}</td><td class="v">{{ $issuedAt->translatedFormat('d M Y, H:i') }}</td></tr>
</table>

<h2>{{ __('export.section_allergies') }}</h2>
@if($patient->allergies && $patient->allergies->isNotEmpty())
<table class="data">
    <thead><tr><th>{{ __('export.allergen') }}</th><th>{{ __('export.severity') }}</th><th>{{ __('export.status') }}</th><th>{{ __('export.recorded') }}</th></tr></thead>
    @foreach($patient->allergies as $allergy)
    <tr>
        <td>{{ $allergy->substance ?: $dash }}</td>
        <td><span class="pill {{ $severityTone($allergy->severity) }}">@enum($allergy->severity, 'severity')</span></td>
        <td><span class="pill {{ $statusTone($allergy->status) }}">@enum($allergy->status ?? 'active')</span></td>
        <td class="muted">{{ $date($allergy->created_at) ?? $dash }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="empty">{{ __('export.allergies_none') }}</p>
@endif

@if($options['include_diagnoses'] && $patient->diagnoses && $patient->diagnoses->isNotEmpty())
<h2>{{ __('export.section_diagnoses') }}</h2>
<table class="data">
    <thead><tr><th>{{ __('export.diagnosis') }}</th><th>{{ __('export.code') }}</th><th>{{ __('export.status') }}</th><th>{{ __('export.recorded') }}</th></tr></thead>
    @foreach($patient->diagnoses as $dx)
    <tr>
        <td>{{ $dx->display_name ?: ($dx->snomed_display ?: ($dx->code ?: $dash)) }}</td>
        <td class="muted">{{ $dx->code ? trim(($dx->code_system ? $dx->code_system . ' ' : '') . $dx->code) : $dash }}</td>
        <td><span class="pill {{ $statusTone($dx->status) }}">@enum($dx->status ?? 'active')</span></td>
        <td class="muted">{{ $date($dx->created_at) ?? $dash }}</td>
    </tr>
    @endforeach
</table>
@endif

@if($options['include_medications'] && $patient->prescriptions && $patient->prescriptions->isNotEmpty())
<h2>{{ __('export.section_medications') }}</h2>
<table class="data">
    <thead><tr><th>{{ __('export.medicine') }}</th><th>{{ __('export.dose') }}</th><th>{{ __('export.frequency') }}</th><th>{{ __('export.duration') }}</th><th>{{ __('export.status') }}</th></tr></thead>
    @foreach($patient->prescriptions as $rx)
    <tr>
        <td class="group" colspan="5">
            @if($rx->reference)<span class="ref">{{ $rx->reference }}</span> &nbsp;·&nbsp; @endif
            @if($rx->facility?->name)
                {{ __('export.prescribed_at_facility', ['date' => $date($rx->prescribed_at ?? $rx->created_at) ?? $dash, 'facility' => $rx->facility->name]) }}
            @else
                {{ __('export.prescribed') }} {{ $date($rx->prescribed_at ?? $rx->created_at) ?? $dash }}
            @endif
            @if($rx->notes)<span class="muted"> &nbsp;·&nbsp; {{ __('export.prescription_notes') }}: {{ $rx->notes }}</span>@endif
        </td>
    </tr>
    @forelse($rx->items as $item)
    <tr>
        <td>{{ $item->drug_name ?: $dash }}</td>
        <td>{{ $item->dose ?: $dash }}</td>
        <td>{{ $item->frequency ?: $dash }}</td>
        <td>{{ $item->duration_days ? trans_choice('export.days', (int) $item->duration_days) : $dash }}</td>
        <td><span class="pill {{ $statusTone($item->status) }}">@enum($item->status ?? 'pending')</span></td>
    </tr>
    @empty
    <tr><td colspan="5" class="empty">{{ $dash }}</td></tr>
    @endforelse
    @endforeach
</table>
@endif

@if($options['include_vitals'] && $patient->vitals && $patient->vitals->isNotEmpty())
<h2>{{ __('export.section_vitals') }} <span class="hint">{{ __('export.vitals_hint', ['count' => $patient->vitals->count()]) }}</span></h2>
<table class="data">
    <thead><tr>
        <th>{{ __('export.date') }}</th><th>{{ __('export.blood_pressure') }}</th><th>{{ __('export.pulse') }}</th>
        <th>{{ __('export.temperature') }}</th><th>{{ __('export.spo2') }}</th><th>{{ __('export.weight') }}</th>
    </tr></thead>
    @foreach($patient->vitals as $v)
    <tr>
        <td>{{ $date($v->recorded_at ?? $v->created_at) ?? $dash }}</td>
        <td class="num">{{ isset($v->systolic_bp, $v->diastolic_bp) ? "{$v->systolic_bp}/{$v->diastolic_bp}" : $dash }}</td>
        <td class="num">{{ $v->pulse_rate ?? $dash }}</td>
        <td class="num">{{ $v->temperature ?? $dash }}</td>
        <td class="num">{{ $v->oxygen_saturation ?? $dash }}</td>
        <td class="num">{{ $v->weight_kg ?? $dash }}</td>
    </tr>
    @endforeach
</table>
@endif

@if($options['include_labs'] && $patient->labResults && $patient->labResults->isNotEmpty())
<h2>{{ __('export.section_labs') }} <span class="hint">{{ __('export.labs_hint', ['count' => $patient->labResults->count()]) }}</span></h2>
<table class="data">
    <thead><tr>
        <th>{{ __('export.test') }}</th><th>{{ __('export.result') }}</th><th>{{ __('export.unit') }}</th>
        <th>{{ __('export.reference_range') }}</th><th>{{ __('export.date') }}</th><th>{{ __('export.flag') }}</th>
    </tr></thead>
    @foreach($patient->labResults as $lr)
    <tr>
        <td>{{ $lr->parameter_name ?: ($lr->loinc_display ?: $dash) }}</td>
        <td class="num">{{ $lr->value ?? $dash }}</td>
        <td>{{ $lr->unit ?: '' }}</td>
        <td class="muted">{{ $lr->reference_range ?: $dash }}</td>
        <td>{{ $date($lr->resulted_at ?? $lr->created_at) ?? $dash }}</td>
        <td>@if($lr->flag)<span class="pill {{ $flagTone($lr->flag) }}">{{ $lr->flag }}</span>@endif</td>
    </tr>
    @endforeach
</table>
@endif

@if($options['include_immunizations'] && $patient->immunizations && $patient->immunizations->isNotEmpty())
<h2>{{ __('export.section_immunizations') }}</h2>
<table class="data">
    <thead><tr><th>{{ __('export.vaccine') }}</th><th>{{ __('export.date_given') }}</th><th>{{ __('export.dose_number') }}</th><th>{{ __('export.lot_number') }}</th></tr></thead>
    @foreach($patient->immunizations as $imm)
    <tr>
        <td>{{ $imm->vaccine_name ?: ($imm->vaccine_code ?: $dash) }}</td>
        <td>{{ $date($imm->administered_at) ?? $dash }}</td>
        <td class="num">{{ $imm->dose_number ?? $dash }}</td>
        <td class="muted">{{ $imm->lot_number ?: $dash }}</td>
    </tr>
    @endforeach
</table>
@endif

@endsection
