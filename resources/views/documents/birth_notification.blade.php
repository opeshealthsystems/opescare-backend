@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Notification de Naissance' : 'Birth Notification' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Document officiel MINSANTE — BRN' : 'Official MINSANTE Birth Record — BRN' }}
@endsection

@section('content')
<style>
    .brn-gov-header {
        border-top: 5px solid #D97706;
        border-bottom: 3px double #D97706;
        padding: 3.5mm 4mm;
        text-align: center;
        margin-bottom: 5mm;
        background: linear-gradient(to bottom, #FFFBEB, #FFFFFF);
    }
    .brn-gov-header .gov-country-en {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        color: #92400E;
        letter-spacing: 1.5px;
    }
    .brn-gov-header .gov-country-fr {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        color: #78350F;
        letter-spacing: 1.5px;
    }
    .brn-gov-header .gov-ministry {
        font-size: 10px;
        font-weight: 600;
        color: #B45309;
        margin-top: 1mm;
    }
    .brn-gov-header .gov-motto {
        font-size: 9px;
        font-style: italic;
        color: #92400E;
        margin-top: 1.5mm;
        letter-spacing: 0.5px;
    }
    .brn-gov-header .gov-separator {
        border: none;
        border-top: 1px solid #FDE68A;
        margin: 1.5mm 10mm;
    }

    .brn-title-block {
        text-align: center;
        margin-bottom: 4mm;
    }
    .brn-main-title {
        font-size: 16px;
        font-weight: 900;
        text-transform: uppercase;
        color: #92400E;
        letter-spacing: 2px;
    }
    .brn-main-title-fr {
        font-size: 13px;
        font-weight: 700;
        color: #B45309;
        letter-spacing: 1px;
        margin-top: 0.5mm;
    }
    .brn-ref-number {
        display: inline-block;
        background: #D97706;
        color: #FFFFFF;
        font-weight: 800;
        font-size: 11px;
        padding: 1.5mm 4mm;
        border-radius: 4px;
        letter-spacing: 1px;
        margin-top: 2mm;
    }

    .brn-biometrics-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 2.5mm;
        margin-bottom: 5mm;
    }
    .brn-bio-card {
        border: 1.5px solid #FDE68A;
        border-radius: 5px;
        padding: 2.5mm;
        text-align: center;
        background: #FFFBEB;
    }
    .brn-bio-card .bio-label {
        font-size: 7.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #92400E;
        letter-spacing: 0.5px;
        margin-bottom: 1mm;
    }
    .brn-bio-card .bio-value {
        font-size: 14px;
        font-weight: 900;
        color: #78350F;
        line-height: 1;
    }
    .brn-bio-card .bio-unit {
        font-size: 8px;
        color: #B45309;
        font-weight: 600;
    }
    .apgar-badge-good { background: #D1FAE5; color: #065F46; padding: 0.5mm 1.5mm; border-radius: 9999px; font-size: 9px; font-weight: 700; }
    .apgar-badge-moderate { background: #FEF3C7; color: #92400E; padding: 0.5mm 1.5mm; border-radius: 9999px; font-size: 9px; font-weight: 700; }
    .sex-badge-female { background: #FCE7F3; color: #9D174D; padding: 0.5mm 1.5mm; border-radius: 9999px; font-size: 8px; font-weight: 700; }
    .sex-badge-male   { background: #DBEAFE; color: #1E40AF; padding: 0.5mm 1.5mm; border-radius: 9999px; font-size: 8px; font-weight: 700; }

    .brn-parents-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
        margin-bottom: 4mm;
    }
    .brn-parent-card {
        border: 1.5px solid #FDE68A;
        border-radius: 5px;
        overflow: hidden;
    }
    .brn-parent-header {
        background: #D97706;
        color: #FFFFFF;
        font-weight: 800;
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 2mm 3mm;
        text-align: center;
    }
    .brn-parent-body { padding: 3mm; }
    .brn-parent-row {
        display: flex;
        justify-content: space-between;
        font-size: 9.5px;
        margin-bottom: 1mm;
    }
    .brn-parent-row .pl { color: #78350F; font-weight: 600; }
    .brn-parent-row .pv { color: #1E293B; font-weight: 700; }

    .brn-delivery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2.5mm;
        margin-bottom: 4mm;
    }
    .brn-delivery-card {
        border: 1px solid #FDE68A;
        border-radius: 5px;
        padding: 3mm;
        background: #FFFBEB;
        text-align: center;
    }
    .brn-delivery-card .dc-label { font-size: 8px; text-transform: uppercase; color: #B45309; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 1.5mm; }
    .brn-delivery-card .dc-value { font-size: 11px; font-weight: 800; color: #78350F; }

    .brn-section-header {
        background: #D97706;
        color: #FFFFFF;
        padding: 2.5mm 4mm;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 4px 4px 0 0;
        margin-top: 4mm;
    }

    .brn-interventions-box {
        border: 1.5px solid #FDE68A;
        border-top: none;
        border-radius: 0 0 5px 5px;
        padding: 0;
        margin-bottom: 4mm;
        overflow: hidden;
    }
    .brn-intervention-row {
        display: flex;
        align-items: center;
        gap: 3mm;
        padding: 2mm 4mm;
        border-bottom: 1px solid #FEF3C7;
        font-size: 10px;
        color: #1E293B;
    }
    .brn-intervention-row:last-child { border-bottom: none; }
    .int-yes { color: #16A34A; font-weight: 800; font-size: 12px; }
    .int-no  { color: #DC2626; font-weight: 800; font-size: 12px; }

    .brn-health-id-box {
        border: 3px solid #D97706;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 4mm;
        text-align: center;
        background: #FFFBEB;
    }
    .brn-health-id-box .hid-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        color: #92400E;
        letter-spacing: 1.5px;
        margin-bottom: 1.5mm;
    }
    .brn-health-id-box .hid-value {
        font-size: 20px;
        font-weight: 900;
        color: #78350F;
        font-family: monospace;
        letter-spacing: 2px;
    }
    .brn-health-id-box .hid-note {
        font-size: 8.5px;
        color: #B45309;
        margin-top: 1mm;
        font-style: italic;
    }

    .brn-civil-alert {
        background: #FEF3C7;
        border: 2px solid #F59E0B;
        border-radius: 6px;
        padding: 3.5mm 4mm;
        margin-bottom: 4mm;
    }
    .brn-civil-alert .ca-title {
        font-size: 11px;
        font-weight: 800;
        color: #92400E;
        margin-bottom: 2mm;
    }
    .brn-civil-alert .ca-body {
        font-size: 10px;
        color: #78350F;
        line-height: 1.65;
    }
    .brn-civil-alert .ca-deadline {
        margin-top: 2mm;
        display: flex;
        gap: 6mm;
        font-size: 9.5px;
    }
    .brn-civil-alert .ca-deadline .cd-item .cd-l { color: #92400E; font-weight: 600; font-size: 8.5px; text-transform: uppercase; }
    .brn-civil-alert .ca-deadline .cd-item .cd-v { font-weight: 800; color: #78350F; font-size: 11px; }

    .brn-legal-declaration {
        font-size: 8.5px;
        color: #64748B;
        font-style: italic;
        text-align: center;
        margin-bottom: 3mm;
        line-height: 1.6;
    }

    .brn-midwife-sig {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-top: 4mm;
    }
    .brn-midwife-sig .sig-block {
        text-align: center;
        font-size: 9.5px;
        color: #374151;
    }
    .brn-midwife-sig .sig-line {
        border-top: 1.5px solid #94A3B8;
        width: 50mm;
        padding-top: 1.5mm;
    }
</style>

{{-- Government Header --}}
<div class="brn-gov-header">
    <div class="gov-country-en">REPUBLIC OF CAMEROON &mdash; MINISTRY OF PUBLIC HEALTH</div>
    <hr class="gov-separator">
    <div class="gov-country-fr">REPUBLIQUE DU CAMEROUN &mdash; MINISTERE DE LA SANTE PUBLIQUE (MINSANTE)</div>
    <div class="gov-motto">Peace &mdash; Work &mdash; Fatherland &nbsp;/&nbsp; Paix &mdash; Travail &mdash; Patrie</div>
</div>

{{-- Title --}}
<div class="brn-title-block">
    <div class="brn-main-title">BIRTH NOTIFICATION</div>
    <div class="brn-main-title-fr">NOTIFICATION DE NAISSANCE</div>
    <div>
        <span class="brn-ref-number">MINSANTE Ref: BRN-{{ $payload['notification_number'] ?? $document_number }}</span>
    </div>
</div>

{{-- Biometrics --}}
@php
    $neonSex = strtolower($payload['neonate_sex'] ?? '');
    $apgar1  = $payload['apgar_1min'] ?? 0;
    $apgar5  = $payload['apgar_5min'] ?? 0;
    $apgarClass = ($apgar5 >= 7) ? 'apgar-badge-good' : 'apgar-badge-moderate';
@endphp
<div class="brn-biometrics-grid">
    <div class="brn-bio-card">
        <div class="bio-label">Weight / Poids</div>
        <div class="bio-value">{{ $payload['neonate_weight_kg'] ?? '—' }}</div>
        <div class="bio-unit">kg</div>
    </div>
    <div class="brn-bio-card">
        <div class="bio-label">Length / Taille</div>
        <div class="bio-value">{{ $payload['neonate_length_cm'] ?? '—' }}</div>
        <div class="bio-unit">cm</div>
    </div>
    <div class="brn-bio-card">
        <div class="bio-label">Head Circ. / PC</div>
        <div class="bio-value">{{ $payload['neonate_head_circumference_cm'] ?? '—' }}</div>
        <div class="bio-unit">cm</div>
    </div>
    <div class="brn-bio-card">
        <div class="bio-label">APGAR 1'/5'</div>
        <div class="bio-value">
            <span class="{{ $apgarClass }}">{{ $apgar1 }}/{{ $apgar5 }}</span>
        </div>
    </div>
    <div class="brn-bio-card">
        <div class="bio-label">Sex / Sexe</div>
        <div class="bio-value" style="font-size: 10px;">
            @if($neonSex === 'female')
                <span class="sex-badge-female">♀ FEMALE / FÉMININ</span>
            @elseif($neonSex === 'male')
                <span class="sex-badge-male">♂ MALE / MASCULIN</span>
            @else
                {{-- The platform records two sexes. Anything else here means the
                     field was not supplied, so it renders as the same em dash
                     every other missing biometric on this document uses. --}}
                <span>&mdash;</span>
            @endif
        </div>
    </div>
    <div class="brn-bio-card">
        <div class="bio-label">Gest. Age</div>
        <div class="bio-value" style="font-size: 11px;">{{ $payload['gestational_age_at_delivery'] ?? '—' }}</div>
        <div class="bio-unit">wks</div>
    </div>
</div>

{{-- Parents --}}
<div class="brn-parents-grid">
    <div class="brn-parent-card">
        <div class="brn-parent-header">{{ $language === 'fr' ? 'MÈRE / MOTHER' : 'MOTHER' }}</div>
        <div class="brn-parent-body">
            <div class="brn-parent-row"><span class="pl">Name:</span><span class="pv">{{ $payload['mother_name'] ?? 'N/A' }}</span></div>
            <div class="brn-parent-row"><span class="pl">Health ID:</span><span class="pv" style="font-family:monospace;">{{ $payload['mother_health_id'] ?? 'N/A' }}</span></div>
            <div class="brn-parent-row"><span class="pl">Date of Birth:</span><span class="pv">{{ $payload['mother_dob'] ?? 'N/A' }}</span></div>
            <div class="brn-parent-row"><span class="pl">Nationality:</span><span class="pv">{{ $payload['mother_nationality'] ?? 'N/A' }}</span></div>
        </div>
    </div>
    <div class="brn-parent-card">
        <div class="brn-parent-header">{{ $language === 'fr' ? 'PÈRE / FATHER' : 'FATHER' }}</div>
        <div class="brn-parent-body">
            <div class="brn-parent-row"><span class="pl">Name:</span><span class="pv">{{ $payload['father_name'] ?? 'N/A' }}</span></div>
            <div class="brn-parent-row"><span class="pl">Health ID:</span><span class="pv" style="font-family:monospace;">{{ $payload['father_health_id'] ?? 'N/A' }}</span></div>
            <div class="brn-parent-row"><span class="pl">Intended Name:</span><span class="pv">{{ $payload['intended_name'] ?? '(to be registered)' }}</span></div>
            <div class="brn-parent-row"><span class="pl">Place of Delivery:</span><span class="pv">{{ $payload['delivery_place'] ?? 'N/A' }}</span></div>
        </div>
    </div>
</div>

{{-- Delivery Details --}}
<div class="brn-delivery-grid">
    <div class="brn-delivery-card">
        <div class="dc-label">{{ $language === 'fr' ? 'Type d\'Accouchement' : 'Delivery Type' }}</div>
        <div class="dc-value">{{ strtoupper($payload['delivery_type'] ?? 'N/A') }}</div>
    </div>
    <div class="brn-delivery-card">
        <div class="dc-label">{{ $language === 'fr' ? 'Date & Heure' : 'Date & Time' }}</div>
        <div class="dc-value" style="font-size: 9.5px;">
            {{ $payload['delivery_date'] ?? 'N/A' }}<br>
            {{ $payload['delivery_time'] ?? '' }}
        </div>
    </div>
    <div class="brn-delivery-card">
        <div class="dc-label">{{ $language === 'fr' ? 'Condition à la Naissance' : 'Condition at Birth' }}</div>
        <div class="dc-value" style="font-size: 10px;">{{ $payload['condition_at_birth'] ?? 'N/A' }}</div>
    </div>
</div>

{{-- Neonatal Interventions --}}
<div class="brn-section-header">
    {{ $language === 'fr' ? 'INTERVENTIONS NÉONATALES / NEONATAL INTERVENTIONS' : 'NEONATAL INTERVENTIONS' }}
</div>
<div class="brn-interventions-box">
    <div class="brn-intervention-row">
        <span class="{{ ($payload['vitamin_k_given'] ?? false) ? 'int-yes' : 'int-no' }}">{{ ($payload['vitamin_k_given'] ?? false) ? '✓' : '✗' }}</span>
        <span>{{ $language === 'fr' ? 'Vitamine K Administrée' : 'Vitamin K Administered' }}</span>
    </div>
    <div class="brn-intervention-row">
        <span class="{{ !empty($payload['eye_prophylaxis']) ? 'int-yes' : 'int-no' }}">{{ !empty($payload['eye_prophylaxis']) ? '✓' : '✗' }}</span>
        <span>{{ $language === 'fr' ? 'Prophylaxie Oculaire:' : 'Eye Prophylaxis:' }}
            @if(!empty($payload['eye_prophylaxis']))
                <strong>{{ $payload['eye_prophylaxis'] }}</strong>
            @endif
        </span>
    </div>
    <div class="brn-intervention-row">
        <span class="{{ ($payload['hbv_vaccine_given'] ?? false) ? 'int-yes' : 'int-no' }}">{{ ($payload['hbv_vaccine_given'] ?? false) ? '✓' : '✗' }}</span>
        <span>
            {{ $language === 'fr' ? 'Vaccin Hépatite B' : 'Hepatitis B Vaccine' }}
            @if(!empty($payload['hbv_vaccine_date']))
                &mdash; <strong>{{ $payload['hbv_vaccine_date'] }}</strong>
            @endif
        </span>
    </div>
    <div class="brn-intervention-row">
        <span class="{{ ($payload['bcg_given'] ?? false) ? 'int-yes' : 'int-no' }}">{{ ($payload['bcg_given'] ?? false) ? '✓' : '✗' }}</span>
        <span>
            {{ $language === 'fr' ? 'Vaccin BCG' : 'BCG Vaccine' }}
            @if(!empty($payload['bcg_site']))
                &mdash; {{ $language === 'fr' ? 'Site:' : 'Site:' }} <strong>{{ $payload['bcg_site'] }}</strong>
            @endif
        </span>
    </div>
</div>

{{-- Provisional Health ID --}}
@if(!empty($payload['neonatal_health_id']))
<div class="brn-health-id-box">
    <div class="hid-label">
        {{ $language === 'fr' ? 'IDENTIFIANT DE SANTÉ PROVISOIRE ÉMIS / PROVISIONAL HEALTH ID ISSUED' : 'PROVISIONAL HEALTH ID ISSUED' }}
    </div>
    <div class="hid-value">{{ $payload['neonatal_health_id'] }}</div>
    <div class="hid-note">
        {{ $language === 'fr'
            ? 'Cet identifiant est provisoire jusqu\'à l\'enregistrement civil officiel.'
            : 'This identifier is provisional until official civil registration is completed.' }}
    </div>
</div>
@endif

{{-- Civil Registration Alert --}}
<div class="brn-civil-alert">
    <div class="ca-title">
        ⚠ {{ $language === 'fr' ? 'ENREGISTREMENT CIVIL OBLIGATOIRE / CIVIL REGISTRATION REQUIRED' : 'CIVIL REGISTRATION REQUIRED' }}
    </div>
    <div class="ca-body">
        {{ $language === 'fr'
            ? 'Cette naissance doit être enregistrée à l\'état civil dans les 30 jours suivant la naissance. Le défaut d\'enregistrement constitue une infraction en vertu du droit camerounais.'
            : 'This birth must be registered at the civil registry within 30 days of birth. Failure to register is an offence under Cameroonian law.' }}
    </div>
    <div class="ca-deadline">
        <div class="cd-item">
            <div class="cd-l">{{ $language === 'fr' ? 'Date Limite' : 'Deadline' }}</div>
            <div class="cd-v">{{ $payload['birth_registration_deadline'] ?? 'Within 30 days' }}</div>
        </div>
        <div class="cd-item">
            <div class="cd-l">{{ $language === 'fr' ? 'Bureau d\'État Civil' : 'Registry Office' }}</div>
            <div class="cd-v">{{ $payload['civil_registry_office'] ?? 'Local Civil Registry' }}</div>
        </div>
    </div>
</div>

{{-- Legal Declaration --}}
<div class="brn-legal-declaration">
    {{ $language === 'fr'
        ? 'Cette notification est émise conformément à l\'Ordonnance sur l\'état civil camerounais n° 81/02 de juin 1981 et à la directive MINSANTE sur la santé numérique 2024-DH-007.'
        : 'This notification is issued pursuant to Cameroon Civil Status Ordinance No. 81/02 of June 1981 and MINSANTE Digital Health Directive 2024-DH-007.' }}
</div>

{{-- Midwife Signature --}}
<div class="brn-midwife-sig">
    <div class="sig-block">
        <div style="font-size: 10px; font-weight: 700; margin-bottom: 8mm;">{{ $payload['midwife_name'] ?? $issuer_name }}</div>
        <div class="sig-line">
            <div>{{ $language === 'fr' ? 'Sage-femme / Prestataire' : 'Midwife / Delivery Provider' }}</div>
            <div>{{ $facility_name }}</div>
        </div>
    </div>
    <div class="sig-block">
        <div style="font-size: 10px; font-weight: 600; margin-bottom: 8mm;">{{ $payload['notification_date'] ?? $issued_at }}</div>
        <div class="sig-line">
            <div>{{ $language === 'fr' ? 'Date d\'émission' : 'Date of Issue' }}</div>
        </div>
    </div>
</div>
@endsection
