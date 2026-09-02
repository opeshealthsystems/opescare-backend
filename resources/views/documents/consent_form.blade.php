@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Formulaire de Consentement Éclairé' : 'Informed Consent Form' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Consentement officiel du patient — CON' : 'Official Patient Consent Document — CON' }}
@endsection

@section('content')
<style>
    .con-procedure-banner {
        background: linear-gradient(135deg, #4F46E5 0%, #3730A3 100%);
        color: #FFFFFF;
        padding: 5mm 6mm;
        border-radius: 6px;
        margin-bottom: 5mm;
        text-align: center;
    }
    .con-procedure-banner .proc-label {
        font-size: 9px;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        opacity: 0.8;
        margin-bottom: 1.5mm;
    }
    .con-procedure-banner .proc-name {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .con-capacity-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .con-capacity-cell {
        border: 1px solid #E2E8F0;
        border-radius: 5px;
        padding: 3mm;
        background: #F8FAFC;
        text-align: center;
    }
    .con-capacity-cell .cap-label {
        font-size: 8.5px;
        font-weight: 600;
        text-transform: uppercase;
        color: #64748B;
        letter-spacing: 0.5px;
        margin-bottom: 1.5mm;
    }
    .con-capacity-cell .cap-value {
        font-size: 12px;
        font-weight: 700;
        color: #0F172A;
    }
    .badge-yes { background: #D1FAE5; color: #065F46; padding: 0.5mm 2.5mm; border-radius: 9999px; font-size: 10px; font-weight: 700; }
    .badge-no  { background: #FEE2E2; color: #991B1B; padding: 0.5mm 2.5mm; border-radius: 9999px; font-size: 10px; font-weight: 700; }
    .badge-interp { background: #EDE9FE; color: #5B21B6; padding: 0.5mm 2.5mm; border-radius: 9999px; font-size: 10px; font-weight: 700; }

    .con-obtained-bar {
        display: flex;
        align-items: center;
        gap: 6mm;
        background: #EEF2FF;
        border: 1px solid #C7D2FE;
        border-radius: 5px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10px;
    }
    .con-obtained-bar .obt-label { color: #4338CA; font-weight: 600; text-transform: uppercase; font-size: 8.5px; letter-spacing: 0.5px; }
    .con-obtained-bar .obt-val   { color: #0F172A; font-weight: 700; }

    .con-risks-card {
        margin-bottom: 5mm;
    }
    .con-risks-header {
        background: #4F46E5;
        color: #FFFFFF;
        padding: 2.5mm 4mm;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 5px 5px 0 0;
    }
    .con-risks-intro {
        font-size: 9.5px;
        color: #475569;
        font-style: italic;
        background: #F8FAFC;
        padding: 2.5mm 4mm;
        border-left: 1px solid #C7D2FE;
        border-right: 1px solid #C7D2FE;
    }
    .con-risk-item {
        display: flex;
        align-items: flex-start;
        gap: 3mm;
        padding: 2mm 4mm;
        border-left: 3px solid #F59E0B;
        border-bottom: 1px solid #F1F5F9;
        background: #FFFDF7;
        font-size: 10px;
    }
    .con-risk-item:last-child { border-bottom: none; border-radius: 0 0 5px 5px; }
    .con-risk-num {
        background: #F59E0B;
        color: #FFFFFF;
        font-weight: 700;
        font-size: 8px;
        width: 5mm;
        height: 5mm;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 0.5mm;
    }

    .con-info-card {
        border: 1px solid #E2E8F0;
        border-radius: 5px;
        margin-bottom: 4mm;
        overflow: hidden;
    }
    .con-info-card .ci-header {
        background: #EEF2FF;
        color: #3730A3;
        font-weight: 700;
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 4mm;
    }
    .con-info-card .ci-body {
        padding: 3mm 4mm;
        font-size: 10px;
        color: #374151;
        line-height: 1.6;
    }

    .con-declarations-box {
        border: 2px solid #4F46E5;
        border-radius: 6px;
        margin-bottom: 5mm;
        overflow: hidden;
    }
    .con-declarations-header {
        background: #4F46E5;
        color: #FFFFFF;
        padding: 3mm 4mm;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
    }
    .con-declarations-subheader {
        background: #EEF2FF;
        color: #4338CA;
        padding: 2mm 4mm;
        font-size: 9px;
        font-style: italic;
        text-align: center;
        border-bottom: 1px solid #C7D2FE;
    }
    .con-declaration-item {
        display: flex;
        align-items: flex-start;
        gap: 3mm;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #EEF2FF;
        font-size: 10px;
        color: #1E293B;
    }
    .con-declaration-item:last-child { border-bottom: none; }
    .checkbox-checked {
        width: 4.5mm; height: 4.5mm; border: 1.5px solid #4F46E5; border-radius: 2px;
        background: #4F46E5; color: #FFFFFF; display: flex; align-items: center; justify-content: center;
        font-size: 8px; font-weight: 700; flex-shrink: 0; margin-top: 0.5mm;
    }
    .checkbox-unchecked {
        width: 4.5mm; height: 4.5mm; border: 1.5px solid #94A3B8; border-radius: 2px;
        background: #FFFFFF; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; margin-top: 0.5mm;
    }

    .con-patient-quote {
        background: #F5F3FF;
        border-left: 4px solid #6366F1;
        border-radius: 0 5px 5px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10px;
        font-style: italic;
        color: #374151;
    }
    .con-patient-quote .quote-attr {
        font-weight: 700;
        font-style: normal;
        color: #4338CA;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1mm;
    }

    .con-signatures-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5mm;
        margin-bottom: 5mm;
    }
    .con-sig-box {
        border: 2px dashed #6366F1;
        border-radius: 6px;
        padding: 3mm;
    }
    .con-sig-title {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #4338CA;
        text-align: center;
        padding-bottom: 2mm;
        border-bottom: 1px solid #C7D2FE;
        margin-bottom: 2mm;
    }
    .con-sig-area {
        height: 45mm;
        background: repeating-linear-gradient(
            transparent, transparent 9mm, #EEF2FF 9mm, #EEF2FF 10mm
        );
        border-radius: 3px;
        margin-bottom: 2mm;
    }
    .con-sig-meta {
        font-size: 9px;
        color: #475569;
        border-top: 1px solid #CBD5E1;
        padding-top: 1.5mm;
    }
    .con-sig-meta span { font-weight: 600; color: #1E293B; }

    .con-clinician-declaration {
        background: #F8FAFC;
        border: 1px solid #C7D2FE;
        border-radius: 5px;
        padding: 3.5mm 4mm;
        margin-bottom: 5mm;
        font-size: 10px;
        color: #374151;
        line-height: 1.65;
    }
    .con-clinician-declaration .cd-header {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        color: #4338CA;
        letter-spacing: 0.5px;
        margin-bottom: 2mm;
    }
    .con-clinician-sig-line {
        border-top: 1.5px solid #94A3B8;
        margin-top: 8mm;
        padding-top: 1.5mm;
        font-size: 9px;
        color: #64748B;
        display: flex;
        justify-content: space-between;
    }

    .con-legal-footer {
        background: #FEF3C7;
        border: 1px solid #FDE68A;
        border-radius: 5px;
        padding: 3mm 4mm;
        font-size: 8.5px;
        color: #78350F;
        line-height: 1.55;
    }
    .con-legal-footer strong { color: #92400E; }
</style>

{{-- SECTION 1: Procedure Banner --}}
<div class="con-procedure-banner">
    <div class="proc-label">{{ $language === 'fr' ? 'PROCÉDURE / PROCEDURE' : 'PROCEDURE' }}</div>
    <div class="proc-name">{{ $payload['procedure_name'] ?? 'Procedure Not Specified' }}</div>
</div>

{{-- SECTION 2: Legal Capacity Block --}}
<div class="con-capacity-grid">
    <div class="con-capacity-cell">
        <div class="cap-label">{{ $language === 'fr' ? 'Capacité Mentale' : 'Mental Capacity' }}</div>
        <div class="cap-value">
            @if($payload['mental_capacity_confirmed'] ?? false)
                <span class="badge-yes">✓ {{ $language === 'fr' ? 'CONFIRMÉE' : 'CONFIRMED' }}</span>
            @else
                <span class="badge-no">✗ {{ $language === 'fr' ? 'NON CONFIRMÉE' : 'NOT CONFIRMED' }}</span>
            @endif
        </div>
    </div>
    <div class="con-capacity-cell">
        <div class="cap-label">{{ $language === 'fr' ? 'Décideur' : 'Decision Maker' }}</div>
        <div class="cap-value" style="font-size: 10px;">
            {{ $payload['decision_maker'] ?? $patient_name ?? 'PATIENT (SELF)' }}
        </div>
    </div>
    <div class="con-capacity-cell">
        <div class="cap-label">{{ $language === 'fr' ? 'Interprète' : 'Interpreter Used' }}</div>
        <div class="cap-value">
            @if($payload['interpreter_used'] ?? false)
                <span class="badge-interp">{{ $language === 'fr' ? 'OUI' : 'YES' }}</span>
            @else
                <span class="badge-no">{{ $language === 'fr' ? 'NON' : 'NO' }}</span>
            @endif
        </div>
    </div>
</div>

{{-- SECTION 3: Consent Obtained By --}}
<div class="con-obtained-bar">
    <div>
        <div class="obt-label">{{ $language === 'fr' ? 'Type de Consentement' : 'Consent Type' }}</div>
        <div class="obt-val">{{ strtoupper($payload['consent_type'] ?? 'Informed Consent') }}</div>
    </div>
    <div style="width: 1px; background: #A5B4FC; height: 8mm;"></div>
    <div>
        <div class="obt-label">{{ $language === 'fr' ? 'Obtenu par' : 'Consent Obtained By' }}</div>
        <div class="obt-val">{{ $payload['clinician_who_obtained'] ?? $issuer_name }}</div>
    </div>
    <div style="width: 1px; background: #A5B4FC; height: 8mm;"></div>
    <div>
        <div class="obt-label">{{ $language === 'fr' ? 'Date du Consentement' : 'Consent Date' }}</div>
        <div class="obt-val">{{ $payload['consent_date'] ?? $issued_at }}</div>
    </div>
</div>

{{-- SECTION 4: Risks Discussed --}}
@if(!empty($payload['risks_discussed']))
<div class="con-risks-card">
    <div class="con-risks-header">
        {{ $language === 'fr' ? '⚠ RISQUES DISCUTÉS / RISKS DISCUSSED' : '⚠ RISKS DISCUSSED WITH PATIENT' }}
    </div>
    <div class="con-risks-intro">
        {{ $language === 'fr'
            ? 'Les risques suivants ont été expliqués au patient en termes clairs et compréhensibles :'
            : 'The following risks were explained to the patient in clear and comprehensible terms:' }}
    </div>
    @foreach($payload['risks_discussed'] as $i => $risk)
        <div class="con-risk-item">
            <div class="con-risk-num">{{ $i + 1 }}</div>
            <div>{{ $risk }}</div>
        </div>
    @endforeach
</div>
@endif

{{-- SECTION 5: Benefits --}}
@if(!empty($payload['benefits_discussed']))
<div class="con-info-card">
    <div class="ci-header">{{ $language === 'fr' ? 'BÉNÉFICES DISCUTÉS / BENEFITS DISCUSSED' : 'BENEFITS DISCUSSED' }}</div>
    <div class="ci-body">{{ $payload['benefits_discussed'] }}</div>
</div>
@endif

{{-- SECTION 6: Alternatives --}}
@if(!empty($payload['alternatives_discussed']))
<div class="con-info-card">
    <div class="ci-header">{{ $language === 'fr' ? 'ALTERNATIVES DISCUTÉES / ALTERNATIVES DISCUSSED' : 'ALTERNATIVES DISCUSSED' }}</div>
    <div class="ci-body">{{ $payload['alternatives_discussed'] }}</div>
</div>
@endif

{{-- SECTION 7: Declarations --}}
<div class="con-declarations-box">
    <div class="con-declarations-header">
        {{ $language === 'fr' ? 'DÉCLARATIONS OFFICIELLES' : 'FORMAL PATIENT DECLARATIONS' }}
    </div>
    <div class="con-declarations-subheader">
        {{ $language === 'fr'
            ? 'En signant ci-dessous, le patient ou son représentant légal déclare et confirme ce qui suit :'
            : 'By signing below, the patient or their authorised representative declares and confirms the following:' }}
    </div>
    @php $agreed = $payload['patient_agreed'] ?? true; @endphp
    <div class="con-declaration-item">
        <div class="checkbox-checked">✓</div>
        <div>{{ $language === 'fr'
            ? 'La nature, le but, les risques et les alternatives de la procédure susmentionnée m\'ont été pleinement expliqués.'
            : 'The nature, purpose, risks and alternatives of the above procedure were fully explained to me.' }}
        </div>
    </div>
    <div class="con-declaration-item">
        <div class="checkbox-checked">✓</div>
        <div>{{ $language === 'fr'
            ? 'J\'ai eu suffisamment de temps et d\'opportunité pour poser des questions et j\'ai reçu des réponses satisfaisantes.'
            : 'I had sufficient time and opportunity to ask questions and received satisfactory answers.' }}
        </div>
    </div>
    @if($payload['right_to_withdraw_explained'] ?? true)
    <div class="con-declaration-item">
        <div class="checkbox-checked">✓</div>
        <div>{{ $language === 'fr'
            ? 'Je comprends que je conserve le droit de retirer mon consentement à tout moment avant la procédure.'
            : 'I understand that I retain the right to withdraw consent at any time prior to the procedure.' }}
        </div>
    </div>
    @endif
    <div class="con-declaration-item">
        <div class="{{ $agreed ? 'checkbox-checked' : 'checkbox-unchecked' }}">{{ $agreed ? '✓' : '' }}</div>
        <div>{{ $language === 'fr'
            ? 'Je consens volontairement et librement à ce que la procédure nommée soit réalisée.'
            : 'I voluntarily and freely consent to the named procedure being performed.' }}
        </div>
    </div>
    <div class="con-declaration-item">
        <div class="{{ $payload['patient_understood'] ?? true ? 'checkbox-checked' : 'checkbox-unchecked' }}">{{ ($payload['patient_understood'] ?? true) ? '✓' : '' }}</div>
        <div>{{ $language === 'fr'
            ? 'Je consens à la photographie/vidéo à des fins d\'éducation médicale (optionnel).'
            : 'I consent to photography/video for medical education purposes (optional).' }}
        </div>
    </div>
</div>

{{-- SECTION 8: Patient Questions --}}
@if(!empty($payload['patient_questions']))
<div class="con-patient-quote">
    <div class="quote-attr">{{ $language === 'fr' ? 'Questions du patient / Patient Questions' : 'Patient Statement / Questions Raised' }}</div>
    &ldquo;{{ $payload['patient_questions'] }}&rdquo;
</div>
@endif

{{-- ADDITIONAL NOTES --}}
@if(!empty($payload['additional_notes']))
<div class="con-info-card" style="margin-bottom: 5mm;">
    <div class="ci-header">{{ $language === 'fr' ? 'NOTES ADDITIONNELLES' : 'ADDITIONAL CLINICAL NOTES' }}</div>
    <div class="ci-body">{{ $payload['additional_notes'] }}</div>
</div>
@endif

{{-- SECTION 9: Signature Blocks --}}
<div class="con-signatures-grid">
    <div class="con-sig-box">
        <div class="con-sig-title">
            PATIENT SIGNATURE<br>SIGNATURE DU PATIENT
        </div>
        <div class="con-sig-area"></div>
        <div class="con-sig-meta">
            Name / Nom: <span>{{ $patient_name }}</span><br>
            Date: <span style="border-bottom: 1px solid #94A3B8; display: inline-block; min-width: 30mm;">&nbsp;</span>
        </div>
    </div>
    <div class="con-sig-box">
        <div class="con-sig-title">
            WITNESS SIGNATURE<br>SIGNATURE DU TÉMOIN
        </div>
        <div class="con-sig-area"></div>
        <div class="con-sig-meta">
            Name / Nom: <span>{{ $payload['witness_name'] ?? '________________________________' }}</span><br>
            Date: <span style="border-bottom: 1px solid #94A3B8; display: inline-block; min-width: 30mm;">&nbsp;</span>
        </div>
    </div>
</div>

{{-- SECTION 10: Clinician Declaration --}}
<div class="con-clinician-declaration">
    <div class="cd-header">
        {{ $language === 'fr' ? 'DÉCLARATION DU CLINICIEN / CLINICIAN DECLARATION' : 'CLINICIAN DECLARATION' }}
    </div>
    <div>
        {{ $language === 'fr'
            ? 'Je soussigné(e), ' . ($payload['clinician_who_obtained'] ?? $issuer_name) . ', confirme avoir fourni des informations complètes et exactes concernant la procédure susmentionnée au patient ou à son représentant autorisé, et que le consentement du patient/représentant a été donné librement et sans contrainte.'
            : 'I, ' . ($payload['clinician_who_obtained'] ?? $issuer_name) . ', confirm that I have provided complete and accurate information regarding the above procedure to the patient or their authorised representative, and that the patient\'s/representative\'s consent was given freely and without coercion.' }}
    </div>
    <div class="con-clinician-sig-line">
        <span>{{ $language === 'fr' ? 'Signature du Clinicien:' : 'Clinician Signature:' }} ________________________________</span>
        <span>{{ $language === 'fr' ? 'Date:' : 'Date:' }} ________________________________</span>
    </div>
</div>

{{-- SECTION 11: Legal Footer Note --}}
<div class="con-legal-footer">
    <strong>{{ $language === 'fr' ? '⚖ NOTE JURIDIQUE / LEGAL NOTICE:' : '⚖ LEGAL NOTICE:' }}</strong>
    {{ $language === 'fr'
        ? ' Ce formulaire de consentement est régi par la Loi camerounaise n° 2010/012 relative à la protection des données personnelles et le cadre de gouvernance clinique OpesCare. Le patient conserve une copie de ce document. Ordonnance sur l\'état civil No. 81/02 de juin 1981 s\'applique le cas échéant.'
        : ' This consent form is governed by Cameroon Law No. 2010/012 on Personal Data Protection and the OpesCare Clinical Governance Framework. The patient retains a copy of this document. Any dispute shall be resolved in accordance with the laws of the Republic of Cameroon.' }}
</div>
@endsection
