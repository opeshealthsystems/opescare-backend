{{--
    Discharge summary — rendered when the patient asks to download it, never
    stored. See App\Services\Patient\PatientDocumentService::dischargeData().
    Spec: docs/specs/documents/04-discharge-summary.md.

    Only a discharged admission produces this sheet; the service refuses one
    that is still open. A "discharge summary" for a patient who has not been
    discharged would be read as a discharge.

    NEVER INVENT. Every section is printed from a real row. A section the
    record holds nothing for says so in one line; it is never filled with a
    plausible default. The medicines listed are the prescriptions issued during
    the stay, labelled as such, not a reconciled discharge list the record does
    not hold.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('document.dis_title'))

@php
    $dash  = __('document.not_recorded');
    $none  = __('doc_ex_dis.none_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    $admitted   = $admission->admitted_at ? \Illuminate\Support\Carbon::parse($admission->admitted_at) : null;
    $discharged = $admission->discharged_at ? \Illuminate\Support\Carbon::parse($admission->discharged_at) : null;

    // Whole days between the two, only when the record carries both.
    $nights = $admitted && $discharged ? (int) $admitted->diffInDays($discharged) : null;

    $diagnoses     = $diagnoses ?? collect();
    $prescriptions = $prescriptions ?? collect();
    $plan          = $plan ?? null;
    $allergies     = $allergies ?? [];
    $clinician     = $clinician ?? ['name' => null, 'credential' => null, 'licence' => null];
@endphp

@section('content')

<h1>{{ __('document.dis_title') }}</h1>
<p class="subtitle">{{ __('document.dis_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.sex') }}</td><td class="v">@if(filled($patient->sex))@enum($patient->sex, 'sex')@else{{ $dash }}@endif</td></tr>
    <tr><td class="k">{{ __('doc_ex_rx.allergies') }}</td><td class="v">{{ ! empty($allergies) ? implode(', ', $allergies) : __('doc_ex_rx.no_known_allergies') }}</td></tr>
</table>

<h2>{{ __('doc_ex_dis.stay') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facility ?? null])
<table class="kv">
    <tr><td class="k">{{ __('document.dis_ward') }}</td><td class="v">{{ $ward ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.dis_admitted') }}</td><td class="v">{{ $stamp($admission->admitted_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.dis_discharged') }}</td><td class="v">{{ $stamp($admission->discharged_at) ?? $dash }}</td></tr>
    @if($nights !== null)
    <tr><td class="k">{{ __('document.dis_length') }}</td><td class="v">{{ __('document.rx_days', ['count' => $nights]) }}</td></tr>
    @endif
</table>

<h2>{{ __('document.dis_reason') }}</h2>
<p>{{ $admission->admission_reason ?: $dash }}</p>

<h2>{{ __('doc_ex_dis.diagnoses') }}</h2>
@if($diagnoses->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>{{ __('doc_ex_dis.code') }}</th>
            <th>{{ __('doc_ex_dis.diagnosis') }}</th>
            <th>{{ __('document.status') }}</th>
        </tr>
    </thead>
    @foreach($diagnoses as $dx)
    <tr>
        <td class="muted">{{ trim(strtoupper((string) $dx->code_system) . ' ' . $dx->code) ?: $dash }}</td>
        <td><strong>{{ $dx->display_name ?: ($dx->snomed_display ?: $dash) }}</strong>@if($dx->is_primary) <span class="pill info">{{ __('doc_ex_dis.primary') }}</span>@endif</td>
        <td>@if(filled($dx->status))@enum($dx->status)@endif</td>
    </tr>
    @endforeach
</table>
@else
<p class="empty">{{ $none }}</p>
@endif

<h2>{{ __('doc_ex_dis.medicines') }} <span class="hint">{{ __('doc_ex_dis.medicines_hint') }}</span></h2>
@php $medLines = $prescriptions->flatMap(fn ($rx) => $rx->items->map(fn ($i) => [$rx, $i])); @endphp
@if($medLines->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>{{ __('document.rx_drug') }}</th>
            <th>{{ __('document.rx_dose') }}</th>
            <th>{{ __('document.rx_frequency') }}</th>
            <th>{{ __('document.rx_duration') }}</th>
            <th>{{ __('document.reference') }}</th>
        </tr>
    </thead>
    @foreach($medLines as [$rx, $item])
    <tr>
        <td><strong>{{ $item->drug_name ?: $dash }}</strong>@if(filled($item->strength)) <span class="muted">{{ $item->strength }}</span>@endif</td>
        <td>{{ $item->dose ?: $dash }}</td>
        <td>{{ $item->frequency ?: $dash }}</td>
        <td>{{ $item->duration_days ? __('document.rx_days', ['count' => $item->duration_days]) : $dash }}</td>
        <td class="muted">{{ $rx->reference ?: '' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="empty">{{ $none }}</p>
@endif

<h2>{{ __('document.dis_outcome') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.dis_outcome') }}</td><td class="v">{{ $admission->discharge_reason ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.dis_destination') }}</td><td class="v">{{ $admission->discharge_destination ?: ($plan?->discharge_disposition ?: $dash) }}</td></tr>
</table>

<h2>{{ __('doc_ex_dis.follow_up') }}</h2>
@if($plan && (filled($plan->follow_up_plan) || filled($plan->medication_reconciliation_notes) || filled($plan->patient_education_notes)))
<table class="kv">
    @if(filled($plan->follow_up_plan))<tr><td class="k">{{ __('doc_ex_dis.follow_up') }}</td><td class="v">{{ $plan->follow_up_plan }}</td></tr>@endif
    @if(filled($plan->medication_reconciliation_notes))<tr><td class="k">{{ __('doc_ex_dis.med_notes') }}</td><td class="v">{{ $plan->medication_reconciliation_notes }}</td></tr>@endif
    @if(filled($plan->patient_education_notes))<tr><td class="k">{{ __('doc_ex_dis.education') }}</td><td class="v">{{ $plan->patient_education_notes }}</td></tr>@endif
</table>
@else
<p class="empty">{{ $none }}</p>
@endif

@include('pdf.layouts.secure-signature', [
    'name'         => $clinician['name'],
    'role'         => $clinician['credential'],
    'registration' => $clinician['licence'],
    'date'         => $admission->discharged_at,
    'label'        => __('doc_ex_dis.signature'),
])

<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
