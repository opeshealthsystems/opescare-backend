@extends('documents.base')

@section('title', 'Verbal Autopsy Questionnaire / Questionnaire d\'Autopsie Verbale')

@section('subtitle')
    <span style="color:#4a235a;font-weight:700;letter-spacing:1px;">EPIDEMIOLOGICAL RECORD — FOR PUBLIC HEALTH USE ONLY</span><br>
    Interview Date: {{ $payload['interview_date'] ?? '—' }} &nbsp;|&nbsp;
    Interviewer: {{ $payload['interviewer_name'] ?? '—' }} (ID: {{ $payload['interviewer_id'] ?? '—' }})
@endsection

@section('content')
<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#222;max-width:900px;margin:0 auto;">

    {{-- EPIDEMIOLOGICAL BANNER --}}
    <div style="background:#4a235a;color:#fff;text-align:center;padding:8px 0;font-size:14px;font-weight:700;letter-spacing:3px;margin-bottom:18px;border-radius:3px;">
        VERBAL AUTOPSY — CONFIDENTIAL EPIDEMIOLOGICAL DATA / DONNÉES ÉPIDÉMIOLOGIQUES CONFIDENTIELLES
    </div>

    {{-- INFORMANT DETAILS --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        1. Informant Details / Informations sur le Répondant
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Informant Name</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['informant_name'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Relationship to Deceased</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['informant_relationship'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Informant Contact</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;" colspan="3">{{ $payload['informant_contact'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- DECEASED INFORMATION --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        2. Deceased Person Information / Informations sur le Défunt
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Deceased Name</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_name'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Age</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_age'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Sex</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_sex'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Occupation</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['deceased_occupation'] ?? '—' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Date of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['date_of_death'] ?? '—' }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Place of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['place_of_death'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Facility</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $facility_name }}</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Health ID</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $health_id }}</td>
        </tr>
    </table>

    {{-- ILLNESS HISTORY --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        3. Illness History / Histoire de la Maladie
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:0;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Duration of Illness (days)</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['illness_duration_days'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;vertical-align:top;">Main Symptoms</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @php $symptoms = $payload['main_symptoms'] ?? []; @endphp
                @if(count($symptoms) > 0)
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($symptoms as $symptom)
                            <li style="margin-bottom:3px;">{{ $symptom }}</li>
                        @endforeach
                    </ul>
                @else
                    <em style="color:#888;">No symptoms recorded.</em>
                @endif
            </td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Treatment Sought?</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @if(($payload['treatment_sought'] ?? false))
                    <span style="color:#155724;font-weight:700;">&#10003; Yes</span>
                @else
                    <span style="color:#856404;font-weight:700;">&#10007; No</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Treatment Location</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['treatment_location'] ?? 'N/A' }}</td>
        </tr>
    </table>
    <div style="margin-bottom:16px;"></div>

    {{-- NARRATIVE HISTORY --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        4. Narrative History (Informant Account) / Récit Narratif (Témoignage du Répondant)
    </div>
    <div style="border:1px solid #dee2e6;padding:14px;margin-bottom:16px;background:#fff;min-height:100px;line-height:1.7;white-space:pre-wrap;">{{ $payload['narrative_history'] ?? '—' }}</div>

    {{-- CLASSIFICATION --}}
    <div style="background:#1a3c5e;color:#fff;padding:8px 14px;font-weight:700;font-size:13px;margin-bottom:0;border-radius:3px 3px 0 0;">
        5. Cause Assignment &amp; Classification / Attribution de la Cause et Classification
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:30%;font-weight:600;">Probable Cause of Death</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:700;color:#1a3c5e;">{{ $payload['probable_cause_of_death'] ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Classification Method</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['classification_method'] ?? 'WHO 2016 Verbal Autopsy' }}</td>
        </tr>
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;font-weight:600;">Data Quality Score</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">
                @php $score = (int)($payload['data_quality_score'] ?? 0); @endphp
                @for($i = 1; $i <= 5; $i++)
                    <span style="color:{{ $i <= $score ? '#f39c12' : '#ccc' }};font-size:16px;">&#9733;</span>
                @endfor
                &nbsp;({{ $score }}/5)
            </td>
        </tr>
    </table>

    {{-- ISSUER --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
        <tr style="background:#f8f9fa;">
            <td style="padding:7px 12px;border:1px solid #dee2e6;width:25%;font-weight:600;">Interviewer</td>
            <td style="padding:7px 12px;border:1px solid #dee2e6;">{{ $payload['interviewer_name'] ?? $issuer_name }}</td>
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
