{{--
    Prescription — rendered when the patient asks to download it, never stored.
    See App\Services\Patient\PatientDocumentService.
    Spec: docs/specs/documents/01-prescription.md.

    A prescription is a legal instrument, so this sheet says plainly what it is
    and what it is not: it records what was written, and it is not evidence that
    anything was supplied. A voided or amended script still prints, carrying its
    status and a watermark, because a record that quietly drops a corrected
    script is not a record.

    Its facts come from App\Services\Documents\PrescriptionSheetFacts, the same
    source as the clinic's printed sheet (portals/prescriber/print), so the
    patient's copy and the clinic's copy carry the same prescriber, licence,
    patient particulars and medicine lines.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('document.rx_title'))

@php
    $dash  = __('document.not_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    $statusTone = fn (?string $status) => match (strtolower(trim((string) $status))) {
        'active', 'dispensed'                            => 'ok',
        'partially_dispensed', 'pending'                 => 'warn',
        'cancelled', 'expired', 'voided', 'entered_in_error' => 'mute',
        default                                          => 'info',
    };

    $facts = $facts ?? app(\App\Services\Documents\PrescriptionSheetFacts::class)->for($prescription);

    // A script that must not be dispensed carries a watermark on every page
    // (read by the secure layout), as the clinic sheet carries its banner.
    $rxStatus  = strtolower((string) ($prescription->status instanceof \BackedEnum ? $prescription->status->value : $prescription->status));
    $watermark = match (true) {
        in_array($rxStatus, ['voided', 'entered_in_error', 'cancelled', 'amended'], true) => __('doc_ex_rx.watermark_void'),
        $rxStatus === 'expired' || ($prescription->expires_at && \Illuminate\Support\Carbon::parse($prescription->expires_at)->isPast()) => __('doc_ex_rx.watermark_expired'),
        default => null,
    };
@endphp

@section('content')

<h1>{{ __('document.rx_title') }}</h1>
<p class="subtitle">{{ __('document.rx_subtitle') }}</p>

<h2>{{ __('doc_ex_rx.issuer') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facts['facility']])
<table class="kv">
    <tr>
        <td class="k">{{ __('document.rx_prescriber') }}</td>
        <td class="v">
            {{ $facts['prescriberName'] ?: $dash }}
            @if(filled($facts['credential']))<span class="muted"> — {{ $facts['credential'] }}</span>@endif
        </td>
    </tr>
    <tr><td class="k">{{ __('doc_ex_sheet.registration_no') }}</td><td class="v">{{ $facts['licenseNumber'] ?: $dash }}</td></tr>
</table>

<h2>{{ __('doc_ex_rx.patient') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_ex_rx.age') }}</td><td class="v">{{ $facts['patientAge'] !== null ? __('doc_ex_rx.age_years', ['count' => $facts['patientAge']]) : $dash }}</td></tr>
    <tr><td class="k">{{ __('document.sex') }}</td><td class="v">@if(filled($patient->sex))@enum($patient->sex, 'sex')@else{{ $dash }}@endif</td></tr>
    <tr>
        <td class="k">{{ __('doc_ex_rx.weight') }}</td>
        <td class="v">
            @if($facts['weightKg'] !== null)
                {{ __('doc_ex_rx.weight_kg', ['kg' => $facts['weightKg']]) }}
                @if($facts['weightAt'])<span class="muted"> — {{ __('doc_ex_rx.measured_on', ['date' => $facts['weightAt']->translatedFormat('d M Y')]) }}</span>@endif
            @else
                {{ $dash }}
            @endif
        </td>
    </tr>
    <tr><td class="k">{{ __('doc_ex_rx.allergies') }}</td><td class="v">{{ ! empty($facts['allergies']) ? implode(', ', $facts['allergies']) : __('doc_ex_rx.no_known_allergies') }}</td></tr>
</table>

<h2>{{ __('doc_ex_rx.order') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.reference') }}</td><td class="v id">{{ $prescription->reference ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.rx_prescribed') }}</td><td class="v">{{ $stamp($prescription->prescribed_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.rx_expires') }}</td><td class="v">{{ $date($prescription->expires_at) ?? $dash }}</td></tr>
    <tr>
        <td class="k">{{ __('document.status') }}</td>
        <td class="v"><span class="pill {{ $statusTone($rxStatus) }}">@enum($prescription->status)</span></td>
    </tr>
</table>

<h2>{{ __('document.rx_drug') }} <span class="hint">{{ __('doc_ex_rx.items_count', ['count' => count($facts['lines'])]) }}</span></h2>
@if(! empty($facts['lines']))
<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('doc_ex_rx.inn') }}</th>
            <th>{{ __('doc_ex_rx.strength_form') }}</th>
            <th>{{ __('document.rx_dose') }}</th>
            <th>{{ __('document.rx_route') }}</th>
            <th>{{ __('document.rx_frequency') }}</th>
            <th>{{ __('document.rx_duration') }}</th>
            <th>{{ __('document.rx_quantity') }}</th>
        </tr>
    </thead>
    @foreach($facts['lines'] as $i => $line)
    <tr>
        <td class="muted">{{ $i + 1 }}</td>
        <td><strong>{{ $line['inn'] ?: $dash }}</strong>@if($line['brand'])<br><span class="muted">{{ $line['brand'] }}</span>@endif</td>
        <td>{{ trim(($line['strength'] ?? '') . ' ' . ($line['form'] ?? '')) ?: $dash }}</td>
        <td>{{ $line['dose'] ?: $dash }}</td>
        <td>{{ $line['route'] ?: $dash }}</td>
        <td>{{ $line['frequency'] ?: $dash }}</td>
        <td>{{ $line['duration'] ? __('document.rx_days', ['count' => $line['duration']]) : $dash }}</td>
        <td class="num">{{ $line['quantity'] !== null ? $line['quantity'] : $dash }}</td>
    </tr>
    @endforeach
</table>
<p class="muted">{{ __('doc_ex_rx.items_end', ['count' => count($facts['lines'])]) }}</p>
@else
<p class="muted">{{ __('document.rx_no_items') }}</p>
@endif

@if(filled($prescription->notes))
<h2>{{ __('doc_ex_rx.instructions') }}</h2>
<div class="box">{{ $prescription->notes }}</div>
@endif

@if($prescription->dispensed_at)
<h2>{{ __('document.rx_dispensed') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.rx_dispensed') }}</td><td class="v">{{ $date($prescription->dispensed_at) }}</td></tr>
    <tr><td class="k">{{ __('document.rx_pharmacy') }}</td><td class="v">{{ $prescription->dispensing_pharmacy_name ?: $dash }}</td></tr>
</table>
@endif

@include('pdf.layouts.secure-signature', [
    'name'         => $facts['prescriberName'],
    'role'         => $facts['credential'],
    'registration' => $facts['licenseNumber'],
    'date'         => $prescription->prescribed_at,
    'label'        => __('doc_ex_rx.signature'),
])

<p class="note">{{ __('document.rx_not_a_supply') }}</p>
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
