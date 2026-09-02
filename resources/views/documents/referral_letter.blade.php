@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Lettre d\'Orientation' : 'Letter of Referral' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Lettre d\'orientation médicale officielle — REF' : 'Official Medical Referral Letter — REF' }}
@endsection

@section('content')
<style>
    /* Urgency badges */
    .urgency-badge {
        display: inline-block;
        padding: 2mm 4mm;
        border-radius: 6px;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .urgency-routine  { background-color: #F1F5F9; color: #475569; border: 1.5px solid #CBD5E1; }
    .urgency-urgent   { background-color: #FFFBEB; color: #B45309; border: 1.5px solid #FCD34D; }
    .urgency-emergency { background-color: #FEE2E2; color: #B91C1C; border: 2px solid #FCA5A5; }

    /* Referral type badge */
    .ref-type-badge {
        display: inline-block;
        padding: 0.5mm 2mm;
        border-radius: 9999px;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #FEF3C7;
        color: #92400E;
        border: 1px solid #FDE68A;
    }
    .ref-type-internal { background-color: #EFF6FF; color: #1D4ED8; border-color: #BFDBFE; }

    /* Addressed-to block */
    .letter-address-block {
        border: 1px solid #E2E8F0;
        background-color: #F8FAFC;
        border-radius: 6px;
        padding: 3.5mm 5mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        line-height: 1.7;
    }
    .letter-address-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        margin-bottom: 1.5mm;
    }

    /* RE: subject line */
    .letter-subject {
        background-color: #FFFBEB;
        border-left: 4px solid #D97706;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-weight: 700;
        font-size: 11px;
        color: #0F172A;
    }
    .letter-subject-label {
        font-size: 8.5px;
        font-weight: 700;
        color: #D97706;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-right: 2mm;
    }

    /* Body paragraphs */
    .letter-salutation {
        font-weight: 600;
        font-size: 11px;
        color: #0F172A;
        margin-bottom: 3mm;
    }
    .letter-para {
        font-size: 10.5px;
        line-height: 1.7;
        color: #334155;
        margin-bottom: 4mm;
        text-align: justify;
    }

    /* Clinical summary card */
    .clinical-summary-card {
        background-color: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3.5mm 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        line-height: 1.7;
        color: #334155;
    }
    .clinical-summary-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        margin-bottom: 1.5mm;
        border-bottom: 1px solid #E2E8F0;
        padding-bottom: 1mm;
    }

    /* Specific request highlight */
    .specific-request-block {
        background-color: #FFFBEB;
        border: 1.5px solid #FCD34D;
        border-left: 5px solid #D97706;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #0F172A;
    }
    .specific-request-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #D97706;
        margin-bottom: 1.5mm;
    }

    /* Appointment date */
    .appointment-box {
        display: flex;
        align-items: center;
        gap: 3mm;
        background-color: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #1E3A5F;
        font-weight: 600;
    }

    /* Medication list */
    .med-list-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        padding: 1mm 0;
        font-size: 10.5px;
        color: #334155;
        border-bottom: 1px solid #F1F5F9;
    }
    .med-list-item:last-child { border-bottom: none; }
    .med-bullet { color: #D97706; font-weight: 900; flex-shrink: 0; }

    /* Closing */
    .letter-closing {
        font-size: 10.5px;
        line-height: 1.7;
        color: #334155;
        margin-bottom: 4mm;
        text-align: justify;
    }
    .letter-salutation-close { font-weight: 600; color: #0F172A; margin-bottom: 2mm; }
</style>

<!-- Header row: LETTER OF REFERRAL + urgency badge -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6mm; padding: 3mm 4mm; background-color: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 8px;">
    <div>
        <div style="font-size: 14px; font-weight: 900; color: #0F172A; text-transform: uppercase; letter-spacing: 1px;">
            {{ $language === 'fr' ? 'LETTRE D\'ORIENTATION MÉDICALE' : 'LETTER OF REFERRAL' }}
        </div>
        <div style="font-size: 9.5px; color: #92400E; margin-top: 0.5mm; font-style: italic;">
            {{ $language === 'fr' ? 'Lettre d\'orientation / Letter of Referral' : 'Lettre d\'orientation / Letter of Referral' }}
            &nbsp;|&nbsp;
            <span class="ref-type-badge {{ ($payload['referral_type'] ?? 'external') === 'internal' ? 'ref-type-internal' : '' }}">
                {{ ucfirst($payload['referral_type'] ?? 'external') }}
            </span>
        </div>
    </div>
    @php
        $urgency = strtolower($payload['referral_urgency'] ?? 'routine');
        $urgencyLabels = ['routine' => 'ROUTINE', 'urgent' => 'URGENT', 'emergency' => 'EMERGENCY / URGENCE'];
    @endphp
    <div>
        <span class="urgency-badge urgency-{{ $urgency }}">
            {{ $urgencyLabels[$urgency] ?? strtoupper($urgency) }}
        </span>
    </div>
</div>

<!-- Addressed-to block -->
<div class="letter-address-block">
    <div class="letter-address-label">{{ $language === 'fr' ? 'DESTINATAIRE' : 'ADDRESSED TO' }}</div>
    <div><strong>{{ $language === 'fr' ? 'À : Le Spécialiste/Consultant' : 'To: The Specialist / Consultant' }}</strong></div>
    <div><strong>{{ $language === 'fr' ? 'Dr./Dép. :' : 'Dr./Dept:' }}</strong> {{ $payload['to_specialist'] }}</div>
    @if(!empty($payload['to_facility']))
    <div>{{ $payload['to_facility'] }}</div>
    @endif
</div>

<!-- RE: Subject line -->
<div class="letter-subject">
    <span class="letter-subject-label">RE / OBJET :</span>
    {{ $language === 'fr' ? 'ORIENTATION DU PATIENT' : 'REFERRAL OF PATIENT' }}
    {{ strtoupper($patient_name) }}
    —
    {{ strtoupper($payload['provisional_diagnosis'] ?? '') }}
</div>

<!-- Salutation -->
<div class="letter-salutation">
    {{ $language === 'fr' ? 'Cher Confrère / Chère Consœur,' : 'Dear Colleague,' }}
</div>

<!-- Opening paragraph -->
@php
    $age = '';
    if (!empty($patient_dob)) {
        try {
            $dob = new \DateTime($patient_dob);
            $now = new \DateTime();
            $age = $dob->diff($now)->y . '-year-old';
        } catch (\Exception $e) {
            $age = '';
        }
    }
@endphp
<div class="letter-para">
    {{ $language === 'fr'
        ? 'J\'ai l\'honneur de vous adresser en consultation/prise en charge le (la) patient(e) mentionné(e) ci-dessus, ' . $patient_name . ', ' . ($age ? $age . ', ' : '') . ($patient_sex ? strtolower($patient_sex) . ', ' : '') . 'pour évaluation spécialisée et prise en charge de ' . ($payload['reason_for_referral'] ?? '') . '.'
        : 'I am writing to refer the above-named patient, ' . $patient_name . ($age ? ', a ' . $age : '') . ($patient_sex ? ' ' . strtolower($patient_sex) : '') . ', for specialist evaluation and management of ' . ($payload['reason_for_referral'] ?? '') . '.' }}
</div>

<!-- Clinical Summary -->
<div class="clinical-summary-card">
    <div class="clinical-summary-label">
        {{ $language === 'fr' ? 'RÉSUMÉ CLINIQUE' : 'CLINICAL SUMMARY' }}
    </div>
    {{ $payload['clinical_summary'] ?? '' }}
</div>

<!-- Relevant Investigations -->
@if(!empty($payload['relevant_investigations']))
<div class="content-card">
    <div class="card-header" style="background-color: #FFFBEB; color: #92400E;">
        {{ $language === 'fr' ? 'EXAMENS COMPLÉMENTAIRES PERTINENTS' : 'RELEVANT INVESTIGATIONS' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'EXAMEN' : 'TEST' }}</th>
                    <th>{{ $language === 'fr' ? 'RÉSULTAT' : 'RESULT' }}</th>
                    <th>{{ $language === 'fr' ? 'DATE' : 'DATE' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['relevant_investigations'] as $inv)
                <tr>
                    <td style="font-weight: 600;">{{ $inv['test'] }}</td>
                    <td>{{ $inv['result'] }}</td>
                    <td style="color: #64748B; font-size: 9.5px;">{{ $inv['date'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Current Medications -->
@if(!empty($payload['current_medications']))
<div class="content-card">
    <div class="card-header" style="background-color: #FFFBEB; color: #92400E;">
        {{ $language === 'fr' ? 'TRAITEMENTS EN COURS' : 'CURRENT MEDICATIONS' }}
    </div>
    <div class="card-body">
        @foreach($payload['current_medications'] as $med)
        <div class="med-list-item">
            <span class="med-bullet">•</span>
            <span>{{ $med }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Specific Request -->
<div class="specific-request-block">
    <div class="specific-request-label">
        {{ $language === 'fr' ? 'DEMANDE SPÉCIFIQUE AU SPÉCIALISTE' : 'SPECIFIC REQUEST TO SPECIALIST' }}
    </div>
    {{ $payload['specific_request'] ?? '' }}
</div>

<!-- Appointment date if known -->
@if(!empty($payload['appointment_date']))
<div class="appointment-box">
    <span style="font-size: 16px;">📅</span>
    <div>
        <div style="font-size: 8.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #3B82F6; margin-bottom: 0.5mm;">
            {{ $language === 'fr' ? 'RENDEZ-VOUS PRÉVU' : 'APPOINTMENT ARRANGED' }}
        </div>
        <div>{{ $payload['appointment_date'] }}</div>
    </div>
</div>
@endif

<!-- Closing paragraph -->
<div class="letter-closing">
    {{ $language === 'fr'
        ? 'Je reste disponible pour tout renseignement complémentaire que vous pourriez souhaiter concernant ce patient. En vous remerciant de votre précieuse collaboration, je vous prie d\'agréer, Cher(e) Confrère/Consœur, l\'expression de ma haute considération.'
        : 'Please do not hesitate to contact me at ' . ($payload['referring_contact'] ?? 'the contact listed above') . ' should you require further clinical information. I look forward to your expert review and management of this patient, and I thank you sincerely for your valued professional collaboration.' }}
</div>

<!-- Yours sincerely -->
<div class="letter-salutation-close">
    {{ $language === 'fr' ? 'Veuillez agréer mes salutations distinguées,' : 'Yours sincerely,' }}
</div>
<div style="font-size: 10.5px; font-weight: 700; color: #0F172A; margin-bottom: 0.5mm;">{{ $payload['referring_doctor'] ?? $issuer_name }}</div>
<div style="font-size: 9.5px; color: #64748B;">{{ $payload['referring_contact'] ?? '' }}</div>
@endsection
