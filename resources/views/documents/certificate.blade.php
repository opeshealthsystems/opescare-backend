{{--
    A certificate a facility issued — rendered when the patient (or the
    issuing facility) asks to download it, never stored. See
    App\Services\Patient\PatientDocumentService and, for issuing,
    App\Http\Controllers\MedicalId\CertificateIssuanceController.
    Specs: docs/specs/documents/06-medical-certificate.md, 07-sick-note.md.

    This one differs from the other sheets: the others are printed from clinical
    records, while a certificate IS the document. Its content lives on the
    `official_documents` row and in that row's payload, so this sheet reproduces
    what was issued and adds nothing to it.

    Known payload keys print under translated labels (doc_ex_cert.field.*);
    an unknown scalar key from an older issuing surface still prints, with
    its key humanised, so nothing issued is silently hidden.

    Whether the certificate is still good is decided the way
    DocumentVerificationService decides it, so this sheet and the code-verifier
    can never disagree in front of an employer or a border officer. A revoked
    or expired certificate carries a watermark on every page.
--}}
@extends('pdf.layouts.secure')

@section('document_title', $document->title ?: __('document.cert_title'))

@php
    $dash  = __('document.not_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    $expiry  = $document->expires_at ? \Illuminate\Support\Carbon::parse($document->expires_at) : null;
    $revoked = $document->revoked_at || strtolower((string) $document->status) === 'revoked';
    $state   = $revoked
        ? ['revoked', 'bad']
        : ($expiry && $expiry->isPast() ? ['expired', 'bad'] : ['active', 'ok']);

    // Read by the secure layout (get_defined_vars flows to @extends).
    $watermark = $state[0] === 'active' ? null : __('document.cert_state_' . $state[0]);

    $payload = $document->payload_json;
    $payload = is_string($payload) ? json_decode($payload, true) : $payload;
    $payload = is_array($payload) ? $payload : [];

    $kind = (string) ($payload['certificate_kind'] ?? $document->document_type);

    // The issuer as snapshotted at issue; the live profile only fills gaps.
    $live   = \App\Services\Documents\ClinicianCredential::for($document->issuer_user_id);
    $issuer = [
        'name'       => $payload['issuer_name'] ?? $live['name'],
        'credential' => $payload['issuer_credential'] ?? $live['credential'],
        'licence'    => $payload['issuer_licence'] ?? $live['licence'],
    ];

    $dateKeys = ['examined_on', 'rest_from', 'rest_to'];
    $skip     = ['patient_id', 'prescription_id', 'items', 'certificate_kind', 'statement',
                 'issuer_name', 'issuer_credential', 'issuer_licence'];

    $details = [];
    foreach ($payload as $key => $value) {
        if (! is_scalar($value) || trim((string) $value) === '' || in_array($key, $skip, true)) {
            continue;
        }
        $labelKey = 'doc_ex_cert.field.' . $key;
        $fieldName = __($labelKey) !== $labelKey ? __($labelKey) : ucfirst(str_replace('_', ' ', (string) $key));
        $shown    = match (true) {
            in_array($key, $dateKeys, true) => $date($value) ?? (string) $value,
            $key === 'purpose'              => __('doc_ex_cert.purpose.' . $value) !== 'doc_ex_cert.purpose.' . $value ? __('doc_ex_cert.purpose.' . $value) : (string) $value,
            $key === 'rest_days'            => __('document.rx_days', ['count' => (int) $value]),
            default                         => (string) $value,
        };
        $details[] = [$fieldName, $shown];
    }

    $holder = trim($patient->first_name . ' ' . $patient->last_name);
@endphp

@section('content')

<h1>{{ $document->title ?: __('document.cert_title') }}</h1>
<p class="subtitle">{{ __('document.cert_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ $holder }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.sex') }}</td><td class="v">@if(filled($patient->sex))@enum($patient->sex, 'sex')@else{{ $dash }}@endif</td></tr>
    <tr><td class="k">{{ __('document.document_id') }}</td><td class="v id">{{ $document->document_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $date($document->issued_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.cert_valid_until') }}</td><td class="v">{{ $date($document->expires_at) ?? __('document.cert_no_expiry') }}</td></tr>
    <tr>
        <td class="k">{{ __('document.status') }}</td>
        <td class="v"><span class="pill {{ $state[1] }}">{{ __('document.cert_state_' . $state[0]) }}</span></td>
    </tr>
    <tr><td class="k">{{ __('document.cert_printed') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

<h2>{{ __('doc_ex_sheet.issuing_facility') }}</h2>
@if(isset($facility) && $facility)
    @include('pdf.layouts.secure-facility', ['facility' => $facility])
@else
<table class="kv"><tr><td class="k">{{ __('document.facility') }}</td><td class="v">{{ $facilityName ?: $dash }}</td></tr></table>
@endif

@if(filled($payload['statement'] ?? null))
<h2>{{ __('doc_ex_cert.attestation') }}</h2>
@if($kind === 'sick_note' && filled($payload['rest_from'] ?? null) && filled($payload['rest_to'] ?? null))
<p>{{ __('doc_ex_cert.sick_note_body', [
    'issuer' => $issuer['name'] ?: $dash,
    'holder' => $holder,
    'days'   => (int) ($payload['rest_days'] ?? 0),
    'from'   => $date($payload['rest_from']),
    'to'     => $date($payload['rest_to']),
]) }}</p>
@elseif($kind === 'medical_certificate')
<p>{{ __('doc_ex_cert.certificate_body', [
    'issuer' => $issuer['name'] ?: $dash,
    'holder' => $holder,
    'date'   => $date($payload['examined_on'] ?? null) ?? $dash,
]) }}</p>
@endif
<div class="box">{!! nl2br(e($payload['statement'])) !!}</div>
@endif

@if($details !== [])
<h2>{{ __('document.cert_particulars') }}</h2>
<table class="kv">
    @foreach($details as [$fieldLabel, $fieldValue])
    <tr><td class="k">{{ $fieldLabel }}</td><td class="v">{{ $fieldValue }}</td></tr>
    @endforeach
</table>
@endif

@if($kind === 'sick_note')
<p class="note muted">{{ __('doc_ex_cert.sick_note_privacy') }}</p>
@endif

@include('pdf.layouts.secure-signature', [
    'name'         => $issuer['name'],
    'role'         => $issuer['credential'],
    'registration' => $issuer['licence'],
    'date'         => $document->issued_at,
])

@if($document->verification_code)
<p class="note">{{ __('document.cert_verify', ['code' => $document->verification_code]) }}</p>
@endif
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
