{{--
    Imaging (radiology) report — rendered when the patient asks to download
    it, never stored. See App\Services\Patient\PatientDocumentService
    (IMAGING_REPORT) and App\Services\Lab\RadiologyReportService.

    Sits on the OpesCare Secure sheet; see pdf/layouts/secure.blade.php for
    the dompdf constraints (tables only, local images, DejaVu Sans).

    Only the CURRENT, distributed version reaches this page: the service 404s
    a draft, an undistributed final and a superseded version. The text is
    printed exactly as the radiologist signed it; nothing is summarised here.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('imaging_report.doc.title'))

@php
    $dash  = __('document.not_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $order = $report->imagingOrder;
@endphp

@section('content')

<h1>{{ __('imaging_report.doc.title') }}</h1>
<p class="subtitle">{{ __('imaging_report.doc.subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.modality') }}</td><td class="v">{{ __('imaging_report.modality.' . $report->modality) }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.body_part') }}</td><td class="v">{{ $report->body_part ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.accession') }}</td><td class="v id">{{ $order?->accession_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.study_date') }}</td><td class="v">{{ $date($report->study_date) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.reported_by') }}</td><td class="v">{{ $report->reportedBy?->name ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.finalized') }}</td><td class="v">{{ $stamp($report->finalized_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('imaging_report.doc.version') }}</td><td class="v">{{ __('imaging_report.doc.version_n', ['n' => (int) $report->version]) }}</td></tr>
    <tr><td class="k">{{ __('document.facility') }}</td><td class="v">{{ $report->facility->name ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

@if($report->status === 'amended')
<p class="note"><span class="pill warn">{{ __('imaging_report.status.amended') }}</span>
    {{ __('imaging_report.doc.amended_note', ['date' => $stamp($report->amended_at) ?? $dash]) }}
    @if(filled($report->amendment_reason)) &mdash; {{ $report->amendment_reason }} @endif
</p>
@endif

@if(filled($report->clinical_indication))
<h2>{{ __('imaging_report.doc.indication') }}</h2>
<p>{!! nl2br(e($report->clinical_indication)) !!}</p>
@endif

<h2>{{ __('imaging_report.doc.findings') }}</h2>
<p>{!! nl2br(e($report->findings)) !!}</p>

<h2>{{ __('imaging_report.doc.impression') }}</h2>
<p><strong>{!! nl2br(e($report->impression)) !!}</strong></p>

@if(filled($report->recommendation))
<h2>{{ __('imaging_report.doc.recommendation') }}</h2>
<p>{!! nl2br(e($report->recommendation)) !!}</p>
@endif

<p class="note">{{ __('imaging_report.doc.advice') }}</p>
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
