@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Certificat Médical' : 'Medical Certificate' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Certificat médical officiel — MED-CERT' : 'Official Medical Certificate — MED-CERT' }}
@endsection

@section('content')
<style>
    /* Octagonal official seal */
    .official-seal-area {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 5mm;
    }
    .official-seal-octagon {
        width: 80px;
        height: 80px;
        background-color: #0F766E;
        clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        line-height: 1.3;
        font-variant: small-caps;
    }

    /* Certificate type banner */
    .cert-type-banner {
        width: 100%;
        text-align: center;
        padding: 5mm 6mm;
        border-radius: 8px;
        margin-bottom: 6mm;
        border: 2px solid;
    }
    .cert-fit {
        background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        border-color: #6EE7B7;
        color: #065F46;
    }
    .cert-unfit {
        background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
        border-color: #FCA5A5;
        color: #7F1D1D;
    }
    .cert-sick_note, .cert-return_to_work {
        background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
        border-color: #FCD34D;
        color: #78350F;
    }
    .cert-disability_assessment {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        border-color: #93C5FD;
        color: #1E3A5F;
    }
    .cert-type-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1.5mm;
        opacity: 0.75;
    }
    .cert-type-value {
        font-size: 20px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    /* Formal declaration */
    .formal-declaration-header {
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #64748B;
        border-top: 1px solid #E2E8F0;
        border-bottom: 1px solid #E2E8F0;
        padding: 2mm 0;
        margin-bottom: 5mm;
    }

    /* Declaration paragraph */
    .declaration-paragraph {
        font-size: 10.5px;
        line-height: 1.8;
        color: #0F172A;
        text-align: justify;
        background-color: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3.5mm 4mm;
        margin-bottom: 5mm;
    }

    /* Period of incapacity */
    .incapacity-period {
        display: flex;
        gap: 4mm;
        margin-bottom: 6mm;
    }
    .incapacity-box {
        flex: 1;
        text-align: center;
        background-color: #FEF2F2;
        border: 1.5px solid #FCA5A5;
        border-radius: 6px;
        padding: 3mm 2mm;
    }
    .incapacity-box-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #991B1B;
        margin-bottom: 1mm;
    }
    .incapacity-box-value { font-size: 12px; font-weight: 800; color: #7F1D1D; }
    .incapacity-days-box {
        background-color: #7F1D1D;
        color: #FFFFFF;
        border-color: #7F1D1D;
    }
    .incapacity-days-box .incapacity-box-label { color: #FCA5A5; }
    .incapacity-days-box .incapacity-box-value { font-size: 20px; }

    /* Restrictions list */
    .restriction-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        padding: 1.5mm 0;
        font-size: 10.5px;
        color: #334155;
        border-bottom: 1px solid #F1F5F9;
    }
    .restriction-item:last-child { border-bottom: none; }
    .restriction-bullet { color: #0F766E; font-weight: 900; flex-shrink: 0; }

    /* Return to work */
    .return-work-box {
        display: flex;
        align-items: center;
        gap: 3mm;
        background-color: #ECFDF5;
        border: 1.5px solid #6EE7B7;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #065F46;
        font-weight: 600;
    }

    /* Employer notice */
    .employer-notice {
        background-color: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-left: 4px solid #1D4ED8;
        border-radius: 0 6px 6px 0;
        padding: 3.5mm 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
    }
    .employer-notice-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1D4ED8;
        margin-bottom: 1.5mm;
    }

    /* Official stamp placeholder */
    .stamp-placeholder {
        width: 40mm;
        height: 40mm;
        border: 2px dashed #CBD5E1;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #CBD5E1;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        line-height: 1.4;
    }

    /* Medical council footer */
    .medical-council-footer {
        font-size: 8.5px;
        color: #64748B;
        text-align: center;
        border-top: 1px solid #E2E8F0;
        padding-top: 3mm;
        margin-top: 4mm;
    }
</style>

<!-- Official Seal Area (octagonal CSS shape, top center) -->
<div class="official-seal-area">
    <div class="official-seal-octagon">
        MEDICAL<br>CERT.
    </div>
    <div style="font-size: 8px; color: #0F766E; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 1.5mm;">
        {{ $language === 'fr' ? 'CERTIFICAT MÉDICAL OFFICIEL' : 'OFFICIAL MEDICAL CERTIFICATE' }}
    </div>
</div>

<!-- Certificate Type Banner -->
@php
    $certType = $payload['certificate_type'] ?? 'fitness';
    $certTypeLabels = [
        'fitness'             => ['en' => 'FIT FOR DUTY',            'fr' => 'APTE AU TRAVAIL'],
        'unfitness'           => ['en' => 'UNFIT FOR DUTY',          'fr' => 'INAPTE AU TRAVAIL'],
        'sick_note'           => ['en' => 'SICK LEAVE CERTIFICATE',  'fr' => 'CERTIFICAT D\'ARRÊT DE TRAVAIL'],
        'return_to_work'      => ['en' => 'RETURN TO WORK',          'fr' => 'REPRISE DU TRAVAIL'],
        'disability_assessment' => ['en' => 'DISABILITY ASSESSMENT', 'fr' => 'ÉVALUATION D\'INVALIDITÉ'],
    ];
    $certLabel = $certTypeLabels[$certType][$language ?? 'en'] ?? strtoupper(str_replace('_', ' ', $certType));
    $certClass = in_array($certType, ['fitness', 'return_to_work']) ? 'cert-fit' : 'cert-' . $certType;
@endphp
<div class="cert-type-banner {{ $certClass }}">
    <div class="cert-type-label">{{ $language === 'fr' ? 'TYPE DE CERTIFICAT' : 'CERTIFICATE TYPE' }}</div>
    <div class="cert-type-value">{{ $certLabel }}</div>
</div>

<!-- TO WHOM IT MAY CONCERN -->
<div class="formal-declaration-header">
    {{ $language === 'fr' ? 'À QUI DE DROIT' : 'TO WHOM IT MAY CONCERN' }}
</div>

<!-- Declaration paragraph -->
<div class="declaration-paragraph">
    {{ $language === 'fr'
        ? 'Je soussigné(e), ' . ($payload['issuer_name'] ?? $issuer_name) . ', certifie avoir examiné le (la) patient(e) ' . $patient_name . ', ' . ($patient_sex ?? '') . ', né(e) le ' . ($patient_dob ?? '') . ', le ' . ($payload['examination_date'] ?? '') . ' à ' . ($facility_name ?? '') . ' et l\'avoir trouvé(e) dans l\'état décrit ci-dessous.'
        : 'This is to certify that ' . $patient_name . ', ' . ($patient_sex ?? '') . ', born ' . ($patient_dob ?? '') . ', was examined on ' . ($payload['examination_date'] ?? '') . ' at ' . ($facility_name ?? '') . ' and found to be in the condition described herein.' }}
</div>

<!-- Fit/Unfit statement -->
<div style="text-align: center; padding: 3mm 0; margin-bottom: 5mm;">
    <span style="font-size: 13px; font-weight: 800; color: {{ in_array($certType, ['fitness','return_to_work']) ? '#065F46' : '#991B1B' }}; text-transform: uppercase; letter-spacing: 1px;">
        {{ in_array($certType, ['fitness', 'return_to_work'])
            ? ($language === 'fr' ? 'APTE À REPRENDRE SON TRAVAIL / SES ACTIVITÉS' : 'FIT TO RESUME WORK / NORMAL ACTIVITIES')
            : ($language === 'fr' ? 'INAPTE À EXERCER SON TRAVAIL / SES ACTIVITÉS' : 'UNFIT TO PERFORM WORK / NORMAL ACTIVITIES') }}
    </span>
</div>

<!-- Period of Incapacity (if unfit/sick) -->
@if(!in_array($certType, ['fitness']) && !empty($payload['unfit_from']))
<div class="incapacity-period">
    <div class="incapacity-box">
        <div class="incapacity-box-label">{{ $language === 'fr' ? 'Inapte à partir du' : 'Unfit From' }}</div>
        <div class="incapacity-box-value">{{ $payload['unfit_from'] }}</div>
    </div>
    <div class="incapacity-box">
        <div class="incapacity-box-label">{{ $language === 'fr' ? 'Inapte jusqu\'au' : 'Unfit Until' }}</div>
        <div class="incapacity-box-value">{{ $payload['unfit_until'] ?? '—' }}</div>
    </div>
    @if(!empty($payload['duration_days']))
    <div class="incapacity-box incapacity-days-box">
        <div class="incapacity-box-label">{{ $language === 'fr' ? 'Nombre de jours' : 'Duration' }}</div>
        <div class="incapacity-box-value">{{ $payload['duration_days'] }}</div>
        <div style="font-size: 9px; color: #FCA5A5; margin-top: 0.5mm;">{{ $language === 'fr' ? 'JOURS' : 'DAYS' }}</div>
    </div>
    @endif
</div>
@endif

<!-- Clinical Findings (if disclosed) -->
@if(($payload['diagnosis_disclosed'] ?? false) && !empty($payload['clinical_findings']))
<div class="content-card">
    <div class="card-header" style="background-color: #F0FDFA; color: #0F766E;">
        {{ $language === 'fr' ? 'CONSTATATIONS CLINIQUES' : 'CLINICAL FINDINGS' }}
    </div>
    <div class="card-body">
        <p style="margin: 0; font-size: 10.5px; line-height: 1.7; color: #334155;">{{ $payload['clinical_findings'] }}</p>
        @if(!empty($payload['diagnosis']))
        <div style="margin-top: 2mm; font-size: 9.5px; font-weight: 700; color: #0F766E;">
            {{ $language === 'fr' ? 'Diagnostic :' : 'Diagnosis:' }} {{ $payload['diagnosis'] }}
        </div>
        @endif
    </div>
</div>
@endif

<!-- Restrictions -->
@if(!empty($payload['restrictions']))
<div class="content-card">
    <div class="card-header" style="background-color: #F0FDFA; color: #0F766E;">
        {{ $language === 'fr' ? 'RESTRICTIONS ET CONTRE-INDICATIONS' : 'RESTRICTIONS & CONTRAINDICATIONS' }}
    </div>
    <div class="card-body">
        @foreach($payload['restrictions'] as $restriction)
        <div class="restriction-item">
            <span class="restriction-bullet">✗</span>
            <span>{{ $restriction }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Recommendations -->
@if(!empty($payload['recommendations']))
<div class="content-card">
    <div class="card-header" style="background-color: #F0FDFA; color: #0F766E;">
        {{ $language === 'fr' ? 'RECOMMANDATIONS MÉDICALES' : 'MEDICAL RECOMMENDATIONS' }}
    </div>
    <div class="card-body">
        <p style="margin: 0; font-size: 10.5px; line-height: 1.7; color: #334155;">{{ $payload['recommendations'] }}</p>
    </div>
</div>
@endif

<!-- Return to work -->
@if($payload['may_resume_work'] ?? false)
<div class="return-work-box">
    <span style="font-size: 18px;">✅</span>
    <div>
        <div>
            {{ $language === 'fr' ? 'REPRISE DU TRAVAIL AUTORISÉE' : 'CLEARED TO RETURN TO WORK' }}
            @if(!empty($payload['resume_date']))
            :
            <strong style="margin-left: 1mm;">{{ $payload['resume_date'] }}</strong>
            @endif
        </div>
        @if(!empty($payload['special_conditions']))
        <div style="font-size: 9px; font-weight: 400; margin-top: 0.5mm; color: #047857;">
            {{ $language === 'fr' ? 'Sous conditions :' : 'Special conditions:' }} {{ $payload['special_conditions'] }}
        </div>
        @endif
    </div>
</div>
@endif

<!-- Employer Notice -->
@if(!empty($payload['employer_name']))
<div class="employer-notice">
    <div class="employer-notice-label">
        {{ $language === 'fr' ? 'À L\'ATTENTION DES RESSOURCES HUMAINES' : 'EMPLOYER NOTICE — HR DEPARTMENT' }}
    </div>
    <div style="font-weight: 600; color: #1E3A5F; margin-bottom: 1mm;">
        {{ $language === 'fr' ? 'À : Service des Ressources Humaines,' : 'To: HR Department,' }}
        {{ $payload['employer_name'] }}
    </div>
    <div style="font-size: 10px; color: #334155; line-height: 1.6;">
        {{ $language === 'fr'
            ? 'Nous vous prions de bien vouloir prendre note que le (la) patient(e) susmentionné(e) est sous suivi médical à notre établissement. Veuillez traiter ce certificat conformément à vos procédures internes de gestion des absences pour raisons médicales.'
            : 'Please be advised that the above-named patient is under medical care at this facility. Kindly process this certificate in accordance with your internal procedures for medical leave management.' }}
    </div>
</div>
@endif

<!-- Occupation -->
@if(!empty($payload['patient_occupation']))
<div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 2.5mm 4mm; margin-bottom: 5mm; display: flex; gap: 8mm; font-size: 10.5px;">
    <span>
        <strong style="color: #64748B;">{{ $language === 'fr' ? 'Profession :' : 'Occupation:' }}</strong>
        <strong style="margin-left: 1mm;">{{ $payload['patient_occupation'] }}</strong>
    </span>
    <span>
        <strong style="color: #64748B;">{{ $language === 'fr' ? 'Objet du certificat :' : 'Certificate Purpose:' }}</strong>
        <strong style="margin-left: 1mm;">{{ $payload['certificate_purpose'] }}</strong>
    </span>
</div>
@endif

<!-- Bottom row: official stamp placeholder + medical council registration -->
<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 5mm;">
    <div class="stamp-placeholder">
        FACILITY<br>STAMP<br><span style="font-size: 7px; font-weight: 400; letter-spacing: 0;">CACHET DE L'ÉTABLISSEMENT</span>
    </div>
    <div style="text-align: right; max-width: 90mm;">
        <div style="font-size: 9px; color: #64748B; line-height: 1.6;">
            <div class="medical-council-footer">
                Dr. {{ $payload['issuer_name'] ?? $issuer_name }}
                &nbsp;|&nbsp; {{ $language === 'fr' ? 'Conseil Médical N°' : 'Medical Council No.' }} {{ $payload['doctor_medical_council_no'] ?? '—' }}
                &nbsp;|&nbsp; {{ $payload['doctor_speciality'] ?? '' }}
            </div>
        </div>
    </div>
</div>
@endsection
