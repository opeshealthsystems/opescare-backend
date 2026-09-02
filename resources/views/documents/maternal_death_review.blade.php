@extends('documents.base')

@section('title', 'Maternal Death Review Form / Fiche de Revue des Décès Maternels')

@section('subtitle')
    <span style="color:#c0392b;font-weight:700;letter-spacing:1px;">CONFIDENTIAL — RESTRICTED CLINICAL DOCUMENT</span><br>
    Case Reference: {{ $payload['case_reference'] ?? 'MDR-000000' }} &nbsp;|&nbsp;
    Review Date: {{ $payload['review_date'] ?? '—' }} &nbsp;|&nbsp;
    Committee Chair: {{ $payload['review_committee_chair'] ?? '—' }}
@endsection

@section('content')
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#222;max-width:900px;margin:0 auto;">

    {{-- CONFIDENTIAL BANNER --}}
    <div style="background:#c0392b;color:#fff;text-align:center;padding:8px 0;font-size:14px;font-weight:700;letter-spacing:3px;margin-bottom:18px;border-radius:3px;">
        CONFIDENTIAL — FOR COMMITTEE USE ONLY / CONFIDENTIEL — USAGE COMITÉ UNIQUEMENT
    </div>

    {{-- PATIENT DEMOGRAPHICS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        1. Patient Information / Informations du Patient
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Patient Name</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $patient_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Health ID</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $health_id }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Age</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['patient_age'] ?? '—' }} years</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Marital Status</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['marital_status'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Gravida</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['gravida'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Para</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['para'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Facility</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $facility_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Date of Birth</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $patient_dob }}</td>
        </tr>
    </table>

    {{-- ANTENATAL CARE --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        2. Antenatal Care / Soins Prénatals
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:33%;font-weight:600;">ANC Received</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @if(($payload['antenatal_care_received'] ?? false))
                    <span style="color:#155724;font-weight:700;">&#10003; Yes</span>
                @else
                    <span style="color:#721c24;font-weight:700;">&#10007; No</span>
                @endif
            </td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:33%;font-weight:600;">Number of ANC Visits</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['anc_visits_count'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Last ANC Date</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;" colspan="3">{{ $payload['last_anc_date'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- ADMISSION & DELIVERY --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        3. Admission &amp; Delivery / Admission et Accouchement
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:33%;font-weight:600;">Admission Date</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['admission_date'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:33%;font-weight:600;">Delivery Outcome</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['delivery_outcome'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- DEATH DETAILS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        4. Death Details / Détails du Décès
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Date of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['date_of_death'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Time of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['time_of_death'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Place of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;" colspan="3">{{ $payload['place_of_death'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Direct Cause of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;" colspan="3">{{ $payload['cause_of_death_direct'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Indirect Cause of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;" colspan="3">{{ $payload['cause_of_death_indirect'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- AVOIDABILITY --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        5. Avoidability Assessment / Évaluation de l'Évitabilité
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Death Avoidable?</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @if(($payload['avoidable'] ?? false))
                    <span style="color:#721c24;font-weight:700;">&#9888; YES — Potentially Avoidable</span>
                @else
                    <span style="color:#155724;font-weight:700;">Not Identified as Avoidable</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;vertical-align:top;">Avoidable Factors</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @php $factors = $payload['avoidable_factors'] ?? []; @endphp
                @if(count($factors) > 0)
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($factors as $factor)
                            <li style="margin-bottom:3px;">{{ $factor }}</li>
                        @endforeach
                    </ul>
                @else
                    <em style="color:#888;">None identified</em>
                @endif
            </td>
        </tr>
    </table>

    {{-- CONCLUSIONS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        6. Review Conclusions / Conclusions de la Revue
    </div>
    <div style="border:1px solid #dee2e6;padding:12px 14px;margin-bottom:16px;background:#fff;min-height:60px;">
        {{ $payload['review_conclusions'] ?? '—' }}
    </div>

    {{-- RECOMMENDATIONS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        7. Recommendations / Recommandations
    </div>
    <div style="border:1px solid #dee2e6;padding:12px 14px;margin-bottom:16px;background:#f8f9fa;">
        @php $recs = $payload['recommendations'] ?? []; @endphp
        @if(count($recs) > 0)
            <ol style="margin:0;padding-left:20px;">
                @foreach($recs as $rec)
                    <li style="margin-bottom:4px;">{{ $rec }}</li>
                @endforeach
            </ol>
        @else
            <em style="color:#888;">No recommendations recorded.</em>
        @endif
    </div>

    {{-- COMMITTEE MEMBERS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        8. Committee Members / Membres du Comité
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <thead>
            <tr style="background:#34495e;color:#fff;">
                <th style="padding:7px 12px;border:1px solid #dee2e6;text-align:left;">#</th>
                <th style="padding:7px 12px;border:1px solid #dee2e6;text-align:left;">Name / Nom</th>
                <th style="padding:7px 12px;border:1px solid #dee2e6;text-align:left;">Role / Rôle</th>
                <th style="padding:7px 12px;border:1px solid #dee2e6;text-align:left;">Signature</th>
            </tr>
        </thead>
        <tbody>
            @php $members = $payload['committee_members'] ?? []; @endphp
            @forelse($members as $i => $member)
                <tr style="background:{{ $i % 2 === 0 ? '#f8f9fa' : '#fff' }};">
                    <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $i + 1 }}</td>
                    <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $member['name'] ?? '—' }}</td>
                    <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $member['role'] ?? '—' }}</td>
                    <td style="padding:7px 12px;border:1px solid #dee2e6;color:#bbb;font-style:italic;">___________________</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="padding:10px 12px;border:1px solid #dee2e6;text-align:center;color:#888;font-style:italic;">No committee members recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ISSUER / VERIFICATION --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Issued By</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $issuer_name }} — {{ $issuer_role }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Issued At</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $issued_at }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Document No.</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $document_number }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Verification Code</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-family:monospace;">{{ $verification_code }}</td>
        </tr>
    </table>

    @if(!empty($qr_svg))
        <div style="text-align:right;margin-top:10px;">{!! $qr_svg !!}</div>
    @endif
</div>
@endsection
