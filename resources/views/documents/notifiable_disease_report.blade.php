@extends('documents.base')

@section('title', 'NOTIFIABLE DISEASE REPORT')

@section('subtitle', 'Cameroon Public Health Law No. 96/03 | Code: NDR | {{ $document_number }}')

@section('content')
<style>
    .ndr-disease-banner { background-color:#DC2626; color:#FFF; border-radius:6px; padding:4mm 6mm; margin-bottom:5mm; display:flex; align-items:center; justify-content:space-between; }
    .ndr-disease-name { font-size:20px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; }
    .ndr-icd { font-size:10px; opacity:.8; margin-top:1mm; }
    .class-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .class-suspected  { background-color:#FEF3C7; color:#92400E; border:1px solid #FCD34D; }
    .class-probable   { background-color:#FED7AA; color:#9A3412; border:1px solid #FDBA74; }
    .class-confirmed  { background-color:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }
    .lab-confirm-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .lab-confirm-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#4B5563; }
    .contact-trace-yes { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .contact-trace-no  { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEF3C7; color:#92400E; }
    .iso-badge { display:inline-block; padding:1.5mm 4mm; border-radius:4px; font-size:10px; font-weight:600; background-color:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE; }
    .outbreak-banner { background-color:#FEE2E2; border:2px solid #DC2626; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:10.5px; color:#7F1D1D; font-weight:600; }
    .notif-row { display:flex; align-items:center; padding:1.5mm 0; border-bottom:1px solid #E5E7EB; font-size:10px; }
    .notif-row:last-child { border-bottom:none; }
    .notif-dot { width:5px; height:5px; border-radius:50%; background-color:#DC2626; margin-right:3mm; flex-shrink:0; }
    .legal-notice { background-color:#FEE2E2; border:1px solid #FCA5A5; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:9.5px; color:#7F1D1D; }
    .outcome-tx-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; }
    .outcome-tx-treating  { background-color:#DBEAFE; color:#1D4ED8; }
    .outcome-tx-recovered { background-color:#D1FAE5; color:#065F46; }
    .outcome-tx-deceased  { background-color:#FEE2E2; color:#991B1B; }
    .outcome-tx-unknown   { background-color:#F3F4F6; color:#4B5563; }
    .vacc-badge { display:inline-block; padding:.8mm 2.5mm; border-radius:4px; font-size:9.5px; font-weight:600; }
    .vacc-yes   { background-color:#D1FAE5; color:#065F46; }
    .vacc-no    { background-color:#FEE2E2; color:#991B1B; }
    .vacc-unk   { background-color:#F3F4F6; color:#4B5563; }
    .dr { display:flex; justify-content:space-between; margin-bottom:1.5mm; font-size:10.5px; }
    .dl { color:#6B7280; font-weight:500; }
    .dv { color:#111827; font-weight:600; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:5mm; }
    .sig-area { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:5mm; }
    .sig-line { border-top:1px solid #DC2626; padding-top:1.5mm; font-size:9.5px; color:#374151; min-width:55mm; margin-top:10mm; }
</style>

@php
    $classification = $payload['case_classification'] ?? 'Suspected';
    $classMap = ['Suspected' => 'class-suspected', 'Probable' => 'class-probable', 'Confirmed' => 'class-confirmed'];
    $classClass = $classMap[$classification] ?? 'class-suspected';
    $outcome = $payload['outcome'] ?? 'Unknown';
    $outcomeClass = match($outcome) {
        'Under treatment' => 'outcome-tx-treating',
        'Recovered'       => 'outcome-tx-recovered',
        'Deceased'        => 'outcome-tx-deceased',
        default           => 'outcome-tx-unknown',
    };
    $vaccStatus = $payload['vaccination_status'] ?? 'Unknown';
    $vaccClass = match($vaccStatus) {
        'Vaccinated'   => 'vacc-yes',
        'Unvaccinated' => 'vacc-no',
        default        => 'vacc-unk',
    };
@endphp

{{-- 1. Disease banner + classification badge --}}
<div class="ndr-disease-banner">
    <div>
        <div class="ndr-disease-name">{{ $payload['disease_name'] ?? 'Notifiable Disease' }}</div>
        <div class="ndr-icd">ICD-10: {{ $payload['icd10_code'] ?? 'N/A' }} &nbsp;|&nbsp; Notification: {{ $payload['notification_date'] ?? 'N/A' }} {{ $payload['notification_time'] ?? '' }}</div>
    </div>
    <span class="class-badge {{ $classClass }}">{{ $classification }}</span>
</div>

{{-- 2. Patient + symptom onset --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Patient Details</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Patient:</span><span class="dv">{{ $patient_name }}</span></div>
                <div class="dr"><span class="dl">Health ID:</span><span class="dv">{{ $health_id ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Sex:</span><span class="dv">{{ $patient_sex ?? 'N/A' }}</span></div>
            </div>
            <div>
                <div class="dr"><span class="dl">Date of Birth:</span><span class="dv">{{ $patient_dob ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Symptom Onset:</span><span class="dv">{{ $payload['symptom_onset_date'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Facility:</span><span class="dv">{{ $facility_name }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- 3. Laboratory confirmation --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Laboratory Confirmation</div>
    <div class="card-body">
        <div class="dr">
            <span class="dl">Lab Confirmation:</span>
            <span class="dv">
                <span class="{{ !empty($payload['laboratory_confirmation']) ? 'lab-confirm-yes' : 'lab-confirm-no' }}">
                    {{ !empty($payload['laboratory_confirmation']) ? 'Confirmed' : 'Not Confirmed' }}
                </span>
            </span>
        </div>
        @if(!empty($payload['laboratory_confirmation']))
        <div class="dr"><span class="dl">Test Used:</span><span class="dv">{{ $payload['lab_test_used'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Result:</span><span class="dv">{{ $payload['lab_result'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Lab Date:</span><span class="dv">{{ $payload['lab_date'] ?? 'N/A' }}</span></div>
        @endif
    </div>
</div>

{{-- 4. Clinical findings --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Clinical Findings</div>
    <div class="card-body">
        <p style="font-size:10.5px; color:#111827; margin:0;">{{ $payload['clinical_findings'] ?? 'Not documented' }}</p>
    </div>
</div>

{{-- 5. Travel history + vaccination status --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Exposure History</div>
    <div class="card-body">
        <div class="dr">
            <span class="dl">Travel History (21 days):</span>
            <span class="dv">{{ $payload['patient_travel_history'] ?? 'None reported' }}</span>
        </div>
        <div class="dr">
            <span class="dl">Vaccination Status:</span>
            <span class="dv"><span class="vacc-badge {{ $vaccClass }}">{{ $vaccStatus }}</span></span>
        </div>
        @if(!empty($payload['source_of_infection']))
        <div class="dr"><span class="dl">Source of Infection:</span><span class="dv">{{ $payload['source_of_infection'] }}</span></div>
        @endif
    </div>
</div>

{{-- 6. Contact tracing --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Contact Tracing</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr">
                    <span class="dl">Tracing Initiated:</span>
                    <span class="dv">
                        <span class="{{ !empty($payload['contact_tracing_initiated']) ? 'contact-trace-yes' : 'contact-trace-no' }}">
                            {{ !empty($payload['contact_tracing_initiated']) ? 'Yes' : 'No' }}
                        </span>
                    </span>
                </div>
            </div>
            <div>
                <div class="dr"><span class="dl">Contacts Identified:</span><span class="dv">{{ $payload['contacts_identified'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Under Follow-up:</span><span class="dv">{{ $payload['contacts_under_follow_up'] ?? 'N/A' }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- 7. Isolation status --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Isolation Status:</span>
    <span class="iso-badge">{{ $payload['isolation_status'] ?? 'Not required' }}</span>
</div>

{{-- 8. Treatment --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Treatment</div>
    <div class="card-body">
        <div class="dr">
            <span class="dl">Treatment Initiated:</span>
            <span class="dv">
                <span class="{{ !empty($payload['treatment_initiated']) ? 'lab-confirm-yes' : 'lab-confirm-no' }}">
                    {{ !empty($payload['treatment_initiated']) ? 'Yes' : 'No' }}
                </span>
            </span>
        </div>
        @if(!empty($payload['treatment_details']))
        <div class="dr"><span class="dl">Details:</span><span class="dv">{{ $payload['treatment_details'] }}</span></div>
        @endif
        <div class="dr">
            <span class="dl">Outcome:</span>
            <span class="dv"><span class="outcome-tx-badge {{ $outcomeClass }}">{{ $outcome }}</span></span>
        </div>
    </div>
</div>

{{-- 9. Cluster / Outbreak alert --}}
@if(!empty($payload['cluster']))
<div class="outbreak-banner">
    &#9888; CLUSTER / OUTBREAK ALERT — This case is part of a cluster or outbreak.<br>
    @if(!empty($payload['cluster_details']))
    <span style="font-weight:400;">{{ $payload['cluster_details'] }}</span>
    @endif
</div>
@endif

{{-- 10. Notification chain --}}
<div class="content-card">
    <div class="card-header" style="background-color:#FEE2E2; color:#991B1B;">Notification Chain</div>
    <div class="card-body">
        @forelse($payload['notified_to'] ?? [] as $recipient)
        <div class="notif-row">
            <span class="notif-dot"></span>
            <span style="color:#111827;">{{ $recipient }}</span>
        </div>
        @empty
        <span style="font-size:10px; color:#6B7280;">No recipients recorded</span>
        @endforelse
        <div style="margin-top:2mm; font-size:10px; color:#6B7280;">
            Notification Date: {{ $payload['notification_date'] ?? 'N/A' }} {{ $payload['notification_time'] ?? '' }}
        </div>
    </div>
</div>

{{-- 11. Notifier signature --}}
<div class="sig-area">
    <div>
        <div class="dr"><span class="dl">Notified By:</span><span class="dv">{{ $payload['notified_by'] ?? $issuer_name }}</span></div>
        <div class="dr"><span class="dl">Designation:</span><span class="dv">{{ $payload['notifier_designation'] ?? $issuer_role }}</span></div>
        <div class="dr"><span class="dl">Facility:</span><span class="dv">{{ $facility_name }}</span></div>
        <div class="dr"><span class="dl">Date:</span><span class="dv">{{ $issued_at }}</span></div>
    </div>
    <div><div class="sig-line">Notifier Signature</div></div>
</div>

{{-- 12. Legal notice --}}
<div class="legal-notice">
    <strong>Legal Notice:</strong>
    Notification of notifiable diseases is mandatory under <em>Cameroon Public Health Law No. 96/03</em>.
    Failure to notify is a criminal offence. All cases must be reported to the MINSANTE District Health Officer immediately upon diagnosis.
</div>
@endsection
