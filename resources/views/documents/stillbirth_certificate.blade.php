@extends('documents.base')

@section('title', 'STILLBIRTH CERTIFICATE / CERTIFICAT DE MORTINAISSANCE')

@section('subtitle', 'REPUBLIC OF CAMEROON — RÉPUBLIQUE DU CAMEROUN | Code: SBC | {{ $document_number }}')

@section('content')
<style>
    .sbc-solemn-header { background-color:#374151; color:#FFF; text-align:center; padding:5mm 6mm; border-radius:6px; margin-bottom:5mm; }
    .sbc-solemn-header .h-en { font-size:17px; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin:0 0 1mm 0; }
    .sbc-solemn-header .h-fr { font-size:13px; font-weight:500; margin:0 0 2mm 0; opacity:.88; }
    .sbc-solemn-header .h-rep { font-size:9.5px; opacity:.75; letter-spacing:1px; text-transform:uppercase; }
    .cert-type-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; background-color:#374151; color:#FFF; margin-bottom:4mm; }
    .maceration-badge { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#FEF3C7; color:#92400E; border:1px solid #FDE68A; }
    .cod-box { border:2px solid #374151; border-radius:6px; margin-bottom:6mm; overflow:hidden; }
    .cod-hdr { background-color:#374151; color:#FFF; padding:2.5mm 4mm; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .cod-row { display:flex; align-items:flex-start; padding:2.5mm 4mm; border-bottom:1px solid #E5E7EB; }
    .cod-row:last-child { border-bottom:none; }
    .cod-lbl { min-width:14mm; font-weight:700; font-size:10.5px; color:#374151; }
    .cod-sub { font-size:9px; color:#6B7280; }
    .cod-val { font-size:10.5px; color:#111827; }
    .timing-badge { display:inline-block; padding:1.5mm 4mm; border-radius:4px; font-size:10px; font-weight:600; background-color:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE; }
    .autopsy-yes { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#D1FAE5; color:#065F46; }
    .autopsy-no  { display:inline-block; padding:1mm 3mm; border-radius:4px; font-size:9.5px; font-weight:600; background-color:#F3F4F6; color:#4B5563; }
    .review-badge { display:inline-block; padding:1.5mm 4mm; border-radius:9999px; font-size:10px; font-weight:700; background-color:#FFFBEB; color:#B45309; border:1px solid #FCD34D; }
    .notif-box { background-color:#F9FAFB; border:1px solid #D1D5DB; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:10px; }
    .legal-notice { background-color:#FEF3C7; border:1px solid #FCD34D; border-radius:6px; padding:3mm 4mm; margin-bottom:5mm; font-size:9.5px; color:#78350F; }
    .dr { display:flex; justify-content:space-between; margin-bottom:1.5mm; font-size:10.5px; }
    .dl { color:#6B7280; font-weight:500; }
    .dv { color:#111827; font-weight:600; }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:5mm; }
    .sig-area { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:6mm; }
    .sig-line { border-top:1px solid #374151; padding-top:1.5mm; font-size:9.5px; color:#374151; min-width:55mm; margin-top:10mm; }
</style>

{{-- 1. Solemn bilingual header --}}
<div class="sbc-solemn-header">
    <p class="h-en">Stillbirth Certificate</p>
    <p class="h-fr">Certificat de Mortinaissance</p>
    <p class="h-rep">Republic of Cameroon &nbsp;|&nbsp; République du Cameroun</p>
</div>

{{-- 2. Certificate type badge --}}
<div style="text-align:center; margin-bottom:5mm;">
    <span class="cert-type-badge">{{ $payload['certificate_type'] ?? 'Stillbirth' }}</span>
</div>

{{-- 3. Foetal details --}}
<div class="content-card">
    <div class="card-header">Foetal Details / Détails du Foetus</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Sex / Sexe:</span><span class="dv">{{ $payload['foetus_sex'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Gestational Age:</span><span class="dv">{{ $payload['gestational_age_weeks'] ?? 'N/A' }} weeks</span></div>
                <div class="dr"><span class="dl">Birth Weight:</span><span class="dv">{{ $payload['birth_weight_grams'] ?? 'N/A' }} g</span></div>
            </div>
            <div>
                <div class="dr"><span class="dl">Date of Delivery:</span><span class="dv">{{ $payload['date_of_delivery'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Time of Delivery:</span><span class="dv">{{ $payload['time_of_delivery'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Place of Delivery:</span><span class="dv">{{ $payload['place_of_delivery'] ?? 'N/A' }}</span></div>
            </div>
        </div>
        <div class="dr" style="margin-top:2mm;">
            <span class="dl">Delivery Type / Mode:</span>
            <span class="dv">{{ $payload['delivery_type'] ?? 'N/A' }}</span>
        </div>
        @if(!empty($payload['maceration_present']))
        <div style="margin-top:2mm;">
            <span class="maceration-badge">
                Maceration Present
                @if(!empty($payload['maceration_grade'])) — {{ $payload['maceration_grade'] }}@endif
            </span>
        </div>
        @endif
    </div>
</div>

{{-- 4 & 5. Timing + delivery, Cause of death WHO ICD format --}}
<div class="cod-box">
    <div class="cod-hdr">Cause of Foetal Death / Cause du Décès Foetal (WHO ICD-PM)</div>
    <div class="cod-row">
        <span class="cod-lbl">A</span>
        <div>
            <span class="cod-sub">Immediate cause / Cause immédiate:</span><br>
            <span class="cod-val">{{ $payload['cause_of_foetal_death_a'] ?? 'Not stated' }}</span>
        </div>
    </div>
    @if(!empty($payload['cause_of_foetal_death_b']))
    <div class="cod-row">
        <span class="cod-lbl">B</span>
        <div>
            <span class="cod-sub">Due to / Dû à:</span><br>
            <span class="cod-val">{{ $payload['cause_of_foetal_death_b'] }}</span>
        </div>
    </div>
    @endif
    @if(!empty($payload['cause_of_foetal_death_c']))
    <div class="cod-row">
        <span class="cod-lbl">C</span>
        <div>
            <span class="cod-sub">Due to / Dû à:</span><br>
            <span class="cod-val">{{ $payload['cause_of_foetal_death_c'] }}</span>
        </div>
    </div>
    @endif
    @if(!empty($payload['contributing_conditions']) && is_array($payload['contributing_conditions']))
    <div class="cod-row">
        <span class="cod-lbl">Other</span>
        <div>
            <span class="cod-sub">Contributing conditions / Conditions contributives:</span><br>
            <span class="cod-val">{{ implode('; ', $payload['contributing_conditions']) }}</span>
        </div>
    </div>
    @endif
</div>

{{-- 6. Timing of death badge --}}
<div style="margin-bottom:5mm;">
    <span style="font-size:10px; font-weight:600; color:#374151; margin-right:3mm;">Timing of Death / Moment du décès:</span>
    <span class="timing-badge">{{ $payload['timing_of_death'] ?? 'Unknown' }}</span>
</div>

{{-- 7. Congenital anomalies --}}
<div class="content-card">
    <div class="card-header">Congenital Anomalies / Anomalies Congénitales</div>
    <div class="card-body">
        <span style="font-size:10.5px; color:#111827;">{{ $payload['congenital_anomalies'] ?? 'None detected' }}</span>
    </div>
</div>

{{-- 8. Maternal details --}}
<div class="content-card">
    <div class="card-header">Maternal Information / Informations Maternelles</div>
    <div class="card-body">
        <div class="two-col">
            <div>
                <div class="dr"><span class="dl">Mother Name:</span><span class="dv">{{ $payload['mother_name'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Age:</span><span class="dv">{{ $payload['mother_age'] ?? 'N/A' }} yrs</span></div>
                <div class="dr"><span class="dl">Gravida/Para:</span><span class="dv">{{ $payload['mother_gravida'] ?? 'N/A' }}</span></div>
            </div>
            <div>
                <div class="dr"><span class="dl">Mother Health ID:</span><span class="dv">{{ $payload['mother_health_id'] ?? 'N/A' }}</span></div>
                <div class="dr"><span class="dl">Antenatal Care:</span><span class="dv">{{ $payload['antenatal_care'] ?? 'Unknown' }}</span></div>
            </div>
        </div>
    </div>
</div>

{{-- 9. Autopsy section --}}
<div class="content-card">
    <div class="card-header">Autopsy / Autopsie</div>
    <div class="card-body">
        @if(!empty($payload['autopsy_performed']))
            <span class="autopsy-yes">Autopsy Performed</span>
            @if(!empty($payload['autopsy_findings']))
            <p style="margin:2mm 0 0 0; font-size:10.5px; color:#111827;">{{ $payload['autopsy_findings'] }}</p>
            @endif
        @else
            <span class="autopsy-no">No Autopsy Performed</span>
        @endif
    </div>
</div>

{{-- 10. Perinatal mortality review badge --}}
@if(!empty($payload['maternity_review_required']))
<div style="margin-bottom:5mm; text-align:center;">
    <span class="review-badge">&#9888; Perinatal Mortality Review Required</span>
</div>
@endif

{{-- 11. Certifying physician signature --}}
<div class="sig-area">
    <div>
        <div class="dr"><span class="dl">Certifying Physician:</span><span class="dv">{{ $payload['certifying_physician'] ?? $issuer_name }}</span></div>
        <div class="dr"><span class="dl">Registration No:</span><span class="dv">{{ $payload['physician_reg'] ?? 'N/A' }}</span></div>
        <div class="dr"><span class="dl">Facility:</span><span class="dv">{{ $facility_name }}</span></div>
        <div class="dr"><span class="dl">Date:</span><span class="dv">{{ $issued_at }}</span></div>
    </div>
    <div><div class="sig-line">Signature</div></div>
</div>

{{-- 12. Notification recipients --}}
<div class="notif-box">
    <strong>Notification for / Notification destinée à:</strong><br>
    {{ $payload['notification_for'] ?? 'Civil Registration + MINSANTE District Health Officer' }}
    @if(!empty($payload['minsante_district']))
    <br><strong>MINSANTE District:</strong> {{ $payload['minsante_district'] }}
    @endif
</div>

{{-- 13. Legal notice --}}
<div class="legal-notice">
    <strong>Legal Notice / Avis Juridique:</strong>
    This certificate must be submitted to the Civil Registrar within 30 days.
    Ce certificat doit être soumis au Registre Civil dans les 30 jours.
    <em>Cameroon Ordonnance No. 81/02 — Civil Status.</em>
</div>
@endsection
