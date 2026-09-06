{{--
    A certificate a facility issued — rendered when the patient asks to download
    it, never stored. See App\Services\Patient\PatientDocumentService.

    This one differs from the other sheets: the others are printed from clinical
    records, while a certificate IS the document. Its content lives on the
    `official_documents` row and in that row's payload, so this sheet reproduces
    what was issued and adds nothing to it.

    Whether the certificate is still good is decided the way
    DocumentVerificationService decides it, so this sheet and the code-verifier
    can never disagree in front of an employer or a border officer.
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

    // The payload carries whatever the issuing surface recorded. Only scalar
    // entries are printed: this sheet reproduces the certificate, it does not
    // interpret it.
    $payload = $document->payload_json;
    $payload = is_string($payload) ? json_decode($payload, true) : $payload;
    $payload = is_array($payload) ? $payload : [];
    $details = array_filter(
        $payload,
        fn ($v, $k) => is_scalar($v) && trim((string) $v) !== '' && ! in_array($k, ['patient_id', 'prescription_id', 'items'], true),
        ARRAY_FILTER_USE_BOTH
    );
@endphp

@section('content')

<h1>{{ $document->title ?: __('document.cert_title') }}</h1>
<p class="subtitle">{{ __('document.cert_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.document_id') }}</td><td class="v id">{{ $document->document_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $date($document->issued_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.cert_valid_until') }}</td><td class="v">{{ $date($document->expires_at) ?? __('document.cert_no_expiry') }}</td></tr>
    <tr><td class="k">{{ __('document.facility') }}</td><td class="v">{{ $facilityName ?: $dash }}</td></tr>
    <tr>
        <td class="k">{{ __('document.status') }}</td>
        <td class="v"><span class="pill {{ $state[1] }}">{{ __('document.cert_state_' . $state[0]) }}</span></td>
    </tr>
    <tr><td class="k">{{ __('document.cert_printed') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

@if($details !== [])
<h2>{{ __('document.cert_particulars') }}</h2>
<table class="kv">
    @foreach($details as $key => $value)
    <tr>
        <td class="k">{{ ucfirst(str_replace('_', ' ', (string) $key)) }}</td>
        <td class="v">{{ $value }}</td>
    </tr>
    @endforeach
</table>
@endif

@if($document->verification_code)
<p class="note">{{ __('document.cert_verify', ['code' => $document->verification_code]) }}</p>
@endif

<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
