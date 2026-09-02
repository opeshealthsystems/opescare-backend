@extends('documents.base')

@section('title', 'Perinatal Mortality Review / Revue de Mortalité Périnatale')

@section('subtitle')
    <span style="color:#c0392b;font-weight:700;letter-spacing:1px;">CONFIDENTIAL — RESTRICTED CLINICAL DOCUMENT</span><br>
    Case Reference: {{ $payload['case_reference'] ?? 'PMR-000000' }} &nbsp;|&nbsp;
    Review Date: {{ $payload['review_date'] ?? '—' }} &nbsp;|&nbsp;
    Reviewer: {{ $payload['reviewer_name'] ?? '—' }}, {{ $payload['reviewer_role'] ?? '—' }}
@endsection

@section('content')
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#222;max-width:900px;margin:0 auto;">

    {{-- CONFIDENTIAL BANNER --}}
    <div style="background:#c0392b;color:#fff;text-align:center;padding:8px 0;font-size:14px;font-weight:700;letter-spacing:3px;margin-bottom:18px;border-radius:3px;">
        CONFIDENTIAL — PERINATAL AUDIT RECORD / CONFIDENTIEL — AUDIT PÉRINATAL
    </div>

    {{-- FACILITY & DOCUMENT INFO --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Facility</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $facility_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">License No.</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $facility_license }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Patient Name</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $patient_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Health ID</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $health_id }}</td>
        </tr>
    </table>

    {{-- BIRTH DETAILS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        1. Birth Details / Détails de la Naissance
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Date of Birth</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['birth_date'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Birth Weight</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['birth_weight_grams'] ?? '—' }} g</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Gestational Age</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['gestational_age_weeks'] ?? '—' }} weeks</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Birth Outcome</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:700;color:#721c24;">{{ $payload['birth_outcome'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Time of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;" colspan="3">{{ $payload['time_of_death'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- ANTENATAL RISK FACTORS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        2. Antenatal Risk Factors / Facteurs de Risque Anténatals
    </div>
    <div style="border:1px solid #dee2e6;padding:12px 14px;margin-bottom:16px;background:#f8f9fa;">
        @php $risks = $payload['antenatal_risk_factors'] ?? []; @endphp
        @if(count($risks) > 0)
            <ul style="margin:0;padding-left:18px;">
                @foreach($risks as $risk)
                    <li style="margin-bottom:3px;">{{ $risk }}</li>
                @endforeach
            </ul>
        @else
            <em style="color:#888;">No antenatal risk factors recorded.</em>
        @endif
    </div>

    {{-- INTRAPARTUM EVENTS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        3. Intrapartum Events / Événements Intrapartum
    </div>
    <div style="border:1px solid #dee2e6;padding:12px 14px;margin-bottom:16px;background:#fff;min-height:50px;">
        {{ $payload['intrapartum_events'] ?? '—' }}
    </div>

    {{-- CAUSE & CLASSIFICATION --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        4. Cause of Death &amp; Classification / Cause du Décès et Classification
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Cause of Perinatal Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['cause_of_perinatal_death'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Classification</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:700;">{{ $payload['classification'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Avoidable?</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @if(($payload['avoidable'] ?? false))
                    <span style="color:#721c24;font-weight:700;">&#9888; YES — Potentially Avoidable</span>
                @else
                    <span style="color:#155724;font-weight:700;">Not Identified as Avoidable</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;vertical-align:top;">Contributory Factors</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @php $contrib = $payload['contributory_factors'] ?? []; @endphp
                @if(count($contrib) > 0)
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($contrib as $cf)
                            <li style="margin-bottom:3px;">{{ $cf }}</li>
                        @endforeach
                    </ul>
                @else
                    <em style="color:#888;">None recorded.</em>
                @endif
            </td>
        </tr>
    </table>

    {{-- RECOMMENDATIONS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        5. Recommendations / Recommandations
    </div>
    <div style="border:1px solid #dee2e6;padding:12px 14px;margin-bottom:20px;background:#f8f9fa;">
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

    {{-- ISSUER / VERIFICATION --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Reviewer</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['reviewer_name'] ?? $issuer_name }} — {{ $payload['reviewer_role'] ?? $issuer_role }}</td>
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
