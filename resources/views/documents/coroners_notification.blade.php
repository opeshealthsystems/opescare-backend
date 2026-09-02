@extends('documents.base')

@section('title', "Coroner's / Medical Examiner Notification / Déclaration au Médecin Légiste")

@section('subtitle')
    <span style="color:#7b241c;font-weight:700;letter-spacing:1px;">OFFICIAL LEGAL DOCUMENT — MÉDICO-LÉGAL</span><br>
    Notification Date: {{ $payload['notification_date'] ?? '—' }} &nbsp;|&nbsp;
    Notification Time: {{ $payload['notification_time'] ?? '—' }} &nbsp;|&nbsp;
    Notifying Physician: {{ $payload['notifying_physician'] ?? '—' }}
@endsection

@section('content')
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#222;max-width:900px;margin:0 auto;">

    {{-- OFFICIAL LEGAL BANNER --}}
    <div style="background:#7b241c;color:#fff;text-align:center;padding:8px 0;font-size:14px;font-weight:700;letter-spacing:3px;margin-bottom:18px;border-radius:3px;">
        OFFICIAL CORONER'S NOTIFICATION — DO NOT DISCLOSE WITHOUT AUTHORITY
    </div>

    {{-- FACILITY INFO --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Reporting Facility</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $facility_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">License No.</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $facility_license }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Document No.</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $document_number }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Language</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $language }}</td>
        </tr>
    </table>

    {{-- DECEASED INFORMATION --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        1. Deceased Person Information / Informations sur le Défunt
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Full Name</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_name'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Age</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_age'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Sex</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_sex'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Date of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['date_of_death'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Time of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['time_of_death'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Place of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['place_of_death'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- CIRCUMSTANCES --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        2. Circumstances of Death / Circonstances du Décès
    </div>
    <div style="border:1px solid #dee2e6;padding:12px 14px;margin-bottom:16px;background:#fff;min-height:60px;">
        {{ $payload['circumstances_of_death'] ?? '—' }}
    </div>

    {{-- DEATH CATEGORY --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        3. Death Category &amp; Last Seen Alive / Catégorie et Dernier Contact
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Death Category</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:700;color:#1a3c5e;">{{ $payload['death_category'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Last Seen Alive Date</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['last_seen_alive_date'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Last Seen By</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['last_seen_by'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- POLICE NOTIFICATION --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        4. Police Notification / Notification à la Police
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Police Notified?</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @if(($payload['police_notified'] ?? false))
                    <span style="color:#155724;font-weight:700;">&#10003; Yes</span>
                @else
                    <span style="color:#856404;font-weight:700;">&#9888; No</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Police Reference No.</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-family:monospace;">{{ $payload['police_reference_number'] ?? 'N/A' }}</td>
        </tr>
    </table>

    {{-- CORONER DETAILS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        5. Coroner / Medical Examiner Details / Coordonnées du Médecin Légiste
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Coroner Name</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['coroner_name'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Coroner Reference No.</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-family:monospace;">{{ $payload['coroner_reference'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- ACTION REQUIRED --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        6. Action Required / Action Requise
    </div>
    <div style="border:2px solid #1a3c5e;padding:14px;margin-bottom:20px;background:#eaf0fb;font-size:14px;font-weight:700;color:#1a3c5e;text-align:center;border-radius:0 0 3px 3px;">
        {{ $payload['action_required'] ?? '—' }}
    </div>

    {{-- ISSUER --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Notifying Physician</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['notifying_physician'] ?? $issuer_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Issued At</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $issued_at }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Issuer Role</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $issuer_role }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Verification Code</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-family:monospace;">{{ $verification_code }}</td>
        </tr>
    </table>

    @if(!empty($qr_svg))
        <div style="text-align:right;margin-top:10px;">{!! $qr_svg !!}</div>
    @endif
</div>
@endsection
