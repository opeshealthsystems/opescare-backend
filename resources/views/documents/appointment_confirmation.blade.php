@extends('documents.base')

@section('title', 'Appointment Confirmation')
@section('subtitle', 'APT — Scheduled Appointment Record')

@section('content')
@php
    $accentColor   = '#0F4C81';
    $apptId        = $payload['appointment_id']   ?? '—';
    $patientId     = $payload['patient_id']        ?? '—';
    $apptType      = $payload['appointment_type']  ?? '—';
    $scheduledAt   = $payload['scheduled_at']      ?? '—';
    $providerId    = $payload['provider_id']       ?? '—';
    $reason        = $payload['reason']            ?? '—';
    $facilityName  = $document->facility->name     ?? ($document->facility_id ?? '—');
    $issuedAt      = $document->issued_at?->format('d M Y H:i') ?? now()->format('d M Y H:i');
    $docNumber     = $document->document_number    ?? '—';
@endphp

{{-- Header banner --}}
<div style="background:{{ $accentColor }};color:#fff;padding:18px 24px;border-radius:6px 6px 0 0;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <div style="font-size:22px;font-weight:700;letter-spacing:0.5px;">Appointment Confirmation</div>
            <div style="font-size:12px;opacity:0.85;margin-top:3px;">{{ $facilityName }}</div>
        </div>
        <div style="text-align:right;font-size:11px;opacity:0.9;">
            <div>Ref: <strong>{{ $docNumber }}</strong></div>
            <div>Issued: {{ $issuedAt }}</div>
        </div>
    </div>
</div>

{{-- Body --}}
<div style="padding:24px;font-family:DejaVu Sans,sans-serif;font-size:13px;color:#1a1a1a;">

    {{-- Appointment details --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <thead>
            <tr style="background:{{ $accentColor }};color:#fff;">
                <th style="padding:8px 12px;text-align:left;font-size:12px;" colspan="2">APPOINTMENT DETAILS</th>
            </tr>
        </thead>
        <tbody>
            @php $rows = [
                ['Appointment Type', ucwords(str_replace('_',' ', $apptType))],
                ['Scheduled Date/Time', $scheduledAt],
                ['Reason for Appointment', $reason],
                ['Provider', $providerId],
                ['Reference Number', $docNumber],
            ]; @endphp
            @foreach($rows as $i => [$label, $value])
            <tr style="background:{{ $i % 2 === 0 ? '#f9fafb' : '#fff' }};">
                <td style="padding:7px 12px;font-weight:600;width:35%;border-bottom:1px solid #e5e7eb;">{{ $label }}</td>
                <td style="padding:7px 12px;border-bottom:1px solid #e5e7eb;">{{ $value }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Instructions box --}}
    <div style="border:1px solid #d1d5db;border-left:4px solid {{ $accentColor }};border-radius:4px;padding:14px 16px;margin-bottom:20px;background:#f0f4ff;">
        <div style="font-weight:700;font-size:12px;color:{{ $accentColor }};margin-bottom:6px;">PATIENT INSTRUCTIONS</div>
        <ul style="margin:0;padding-left:18px;font-size:12px;color:#374151;line-height:1.7;">
            <li>Please arrive <strong>15 minutes</strong> before your scheduled appointment time.</li>
            <li>Bring this confirmation document and a valid photo ID.</li>
            <li>Bring all current medications and any relevant medical records or test results.</li>
            <li>If you need to cancel or reschedule, please notify us at least 24 hours in advance.</li>
        </ul>
    </div>

    {{-- Bilingual footer note --}}
    <div style="font-size:11px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:12px;margin-top:8px;">
        <em>This document confirms your scheduled appointment. Please retain it for your records. /
        Ce document confirme votre rendez-vous planifié. Veuillez le conserver pour vos dossiers.</em>
    </div>
</div>
@endsection
