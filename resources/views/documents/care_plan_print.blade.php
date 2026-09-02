@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Plan de Soins du Patient' : 'Patient Care Plan' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Plan de soins officiel — CPL' : 'Official Coordinated Care Plan — CPL' }}
@endsection

@section('content')
@php
    // --- HARDCODED DUMMY DATA ---
    $planTitle   = 'Comprehensive Diabetes & Hypertension Management Plan';
    $planStatus  = 'ACTIVE';
    $planCreated = '07 June 2026';
    $planReview  = '07 September 2026';

    $teamLead = ['name' => 'Dr. MBASSI ATEBA', 'role' => 'Lead Physician — Internal Medicine'];
    $teamSupport = [
        ['name' => 'Dietitian FOUDA ZANG', 'role' => 'Nutritional Counselling'],
    ];
    $teamPending = [
        ['name' => 'Nephrology Department', 'role' => 'Pending Referral — Renal Assessment'],
    ];

    $goals = [
        ['num' => 1, 'goal' => 'Achieve HbA1c < 7.0% within 6 months', 'target' => 'Dec 2026', 'priority' => 'high'],
        ['num' => 2, 'goal' => 'Maintain blood pressure < 130/80 mmHg consistently', 'target' => 'Ongoing', 'priority' => 'high'],
        ['num' => 3, 'goal' => 'Prevent progression of microalbuminuria (urine ACR < 30 mg/g)', 'target' => 'Sep 2026', 'priority' => 'medium'],
        ['num' => 4, 'goal' => 'Achieve LDL cholesterol < 2.6 mmol/L', 'target' => 'Sep 2026', 'priority' => 'medium'],
        ['num' => 5, 'goal' => 'Patient self-management: blood glucose monitoring 2× per day', 'target' => 'Ongoing', 'priority' => 'high'],
    ];

    $interventions = [
        ['type' => 'Medication',   'intervention' => 'Amlodipine 10 mg + Losartan 50 mg',     'frequency' => 'Daily',           'responsible' => 'Patient',        'target_date' => 'Ongoing'],
        ['type' => 'Monitoring',   'intervention' => 'Blood glucose self-monitoring',           'frequency' => 'Twice daily',     'responsible' => 'Patient',        'target_date' => 'Ongoing'],
        ['type' => 'Laboratory',   'intervention' => 'HbA1c + Renal function panel (eGFR/ACR)','frequency' => 'Every 3 months',  'responsible' => 'Lab Team',       'target_date' => 'Sep 2026'],
        ['type' => 'Referral',     'intervention' => 'Nephrology review — renal assessment',   'frequency' => 'Once',            'responsible' => 'Dr. MBASSI ATEBA','target_date' => 'Jul 2026'],
        ['type' => 'Education',    'intervention' => 'Dietary counselling (low sodium, low GI)','frequency' => 'Monthly × 3',    'responsible' => 'Dietitian FOUDA','target_date' => 'Aug 2026'],
    ];

    $educationTopics = [
        ['title' => 'Understanding Hypertension & Diabetes', 'color' => '#0F766E', 'bg' => '#F0FDFA'],
        ['title' => 'Recognising Hypoglycaemia Symptoms',    'color' => '#D97706', 'bg' => '#FFFBEB'],
        ['title' => 'Dietary Modifications (Low Sodium / Low GI)', 'color' => '#0369A1', 'bg' => '#F0F9FF'],
        ['title' => 'Medication Adherence & Side Effects',   'color' => '#7C3AED', 'bg' => '#F5F3FF'],
        ['title' => 'When to Seek Emergency Care',           'color' => '#DC2626', 'bg' => '#FEF2F2'],
    ];

    $progressNotes = [
        ['date' => '07 Jun 2026', 'author' => 'Dr. MBASSI ATEBA', 'note' => 'Care plan activated following hypertensive emergency admission and discharge. Patient counselled on dietary changes, medication regimen, and self-monitoring protocol. HBPM device issued.'],
        ['date' => '20 Jun 2026 (Scheduled)', 'author' => 'Dr. MBASSI ATEBA', 'note' => 'Outpatient follow-up scheduled. BP, weight, glucose diary review. Dietitian referral to commence.'],
    ];

    $patientName = 'NJOMO EKAMBI, Marie Claire';
    $patientHid  = 'CMR-2024-00429871';
@endphp

<style>
    .cpl-plan-banner {
        background: linear-gradient(135deg, #0F766E 0%, #0D9488 100%);
        color: #FFFFFF;
        padding: 4mm 5mm;
        border-radius: 6px;
        margin-bottom: 4mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cpl-plan-banner .pb-title { font-size: 14px; font-weight: 800; letter-spacing: 0.5px; }
    .cpl-plan-banner .pb-subtitle { font-size: 9px; opacity: 0.8; margin-top: 0.5mm; }
    .cpl-plan-banner .pb-right { text-align: right; font-size: 9px; opacity: 0.9; }
    .cpl-plan-banner .pb-right .status-badge-active {
        background: #FFFFFF;
        color: #0F766E;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-weight: 800;
        font-size: 10px;
        letter-spacing: 0.5px;
        display: inline-block;
        margin-bottom: 1.5mm;
    }

    .cpl-team-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 4mm;
    }
    .cpl-team-card {
        border-radius: 5px;
        padding: 3mm;
        overflow: hidden;
    }
    .cpl-team-card.lead    { background: #F0FDFA; border: 1.5px solid #5EEAD4; }
    .cpl-team-card.support { background: #F0F9FF; border: 1.5px solid #BAE6FD; }
    .cpl-team-card.pending { background: #FEF3C7; border: 1.5px dashed #FCD34D; }
    .cpl-team-card .tc-role  { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1mm; }
    .cpl-team-card.lead    .tc-role { color: #0F766E; }
    .cpl-team-card.support .tc-role { color: #0369A1; }
    .cpl-team-card.pending .tc-role { color: #B45309; }
    .cpl-team-card .tc-name  { font-size: 11px; font-weight: 800; color: #1E293B; margin-bottom: 0.5mm; }
    .cpl-team-card .tc-desc  { font-size: 9px; color: #64748B; }

    .cpl-section-header {
        background: #0F766E;
        color: #FFFFFF;
        padding: 2.5mm 4mm;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 4px 4px 0 0;
    }

    .cpl-goals-list {
        border: 1.5px solid #CCFBF1;
        border-top: none;
        border-radius: 0 0 5px 5px;
        margin-bottom: 4mm;
        overflow: hidden;
    }
    .cpl-goal-item {
        display: flex;
        align-items: flex-start;
        gap: 3mm;
        padding: 3mm 4mm;
        border-bottom: 1px solid #F0FDFA;
    }
    .cpl-goal-item:last-child { border-bottom: none; }
    .goal-num {
        width: 6mm; height: 6mm; border-radius: 50%;
        background: #0F766E; color: #FFFFFF;
        font-weight: 800; font-size: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .goal-num.high   { background: #0F766E; }
    .goal-num.medium { background: #0D9488; opacity: 0.85; }
    .goal-body { flex: 1; }
    .goal-body .g-text   { font-size: 10.5px; font-weight: 600; color: #1E293B; }
    .goal-body .g-target { font-size: 8.5px; color: #64748B; margin-top: 0.5mm; }
    .goal-priority-high   { background: #CCFBF1; color: #0F766E; padding: 0.3mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }
    .goal-priority-medium { background: #E0F2FE; color: #0369A1; padding: 0.3mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }

    .cpl-interventions-table {
        width: 100%;
        border-collapse: collapse;
        border: 1.5px solid #CCFBF1;
        border-top: none;
        margin-bottom: 4mm;
    }
    .cpl-interventions-table th {
        background: #F0FDFA;
        color: #0F766E;
        font-weight: 700;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 2.5mm 3mm;
        border-bottom: 2px solid #CCFBF1;
        text-align: left;
    }
    .cpl-interventions-table td {
        padding: 2.5mm 3mm;
        font-size: 10px;
        border-bottom: 1px solid #F0FDFA;
        color: #1E293B;
    }
    .cpl-interventions-table tr:last-child td { border-bottom: none; }
    .type-badge-medication  { background: #EDE9FE; color: #5B21B6; padding: 0.5mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }
    .type-badge-monitoring  { background: #DBEAFE; color: #1D4ED8; padding: 0.5mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }
    .type-badge-laboratory  { background: #FEF3C7; color: #92400E; padding: 0.5mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }
    .type-badge-referral    { background: #FEE2E2; color: #B91C1C; padding: 0.5mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }
    .type-badge-education   { background: #D1FAE5; color: #065F46; padding: 0.5mm 1.5mm; border-radius: 3px; font-size: 8px; font-weight: 700; }

    .cpl-education-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5mm;
        border: 1.5px solid #CCFBF1;
        border-top: none;
        border-radius: 0 0 5px 5px;
        padding: 3mm;
        margin-bottom: 4mm;
    }
    .cpl-edu-card {
        border-radius: 5px;
        padding: 2.5mm 3mm;
        font-size: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 2mm;
    }
    .cpl-edu-card::before { content: '●'; font-size: 8px; flex-shrink: 0; }

    .cpl-timeline {
        border: 1.5px solid #CCFBF1;
        border-top: none;
        border-radius: 0 0 5px 5px;
        margin-bottom: 4mm;
        overflow: hidden;
    }
    .cpl-timeline-item {
        display: flex;
        gap: 3mm;
        padding: 3mm 4mm;
        border-bottom: 1px solid #F0FDFA;
    }
    .cpl-timeline-item:last-child { border-bottom: none; }
    .tl-dot { width: 3mm; height: 3mm; background: #0F766E; border-radius: 50%; margin-top: 1.5mm; flex-shrink: 0; }
    .tl-date { font-size: 9px; font-weight: 700; color: #0F766E; white-space: nowrap; min-width: 30mm; }
    .tl-author { font-size: 8.5px; color: #64748B; margin-top: 0.5mm; }
    .tl-note { font-size: 10px; color: #374151; margin-top: 1mm; line-height: 1.5; }

    .cpl-acknowledgement {
        border: 2px solid #CCFBF1;
        border-radius: 6px;
        padding: 4mm;
        margin-bottom: 3mm;
    }
    .cpl-acknowledgement .ack-header {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #0F766E;
        letter-spacing: 0.5px;
        margin-bottom: 2mm;
    }
    .cpl-acknowledgement .ack-text {
        font-size: 10px;
        color: #374151;
        line-height: 1.65;
        margin-bottom: 4mm;
    }
    .cpl-sig-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5mm;
    }
    .cpl-sig-block { text-align: center; font-size: 9.5px; color: #374151; }
    .cpl-sig-area {
        height: 20mm;
        border: 1.5px dashed #5EEAD4;
        border-radius: 4px;
        margin-bottom: 2mm;
        background: repeating-linear-gradient(transparent, transparent 9mm, #F0FDFA 9mm, #F0FDFA 10mm);
    }
    .cpl-sig-line {
        border-top: 1px solid #94A3B8;
        padding-top: 1.5mm;
        font-size: 9px;
    }
</style>

{{-- SECTION 1: Plan Banner --}}
<div class="cpl-plan-banner">
    <div>
        <div class="pb-title">{{ $planTitle }}</div>
        <div class="pb-subtitle">
            {{ $language === 'fr' ? 'Patient:' : 'Patient:' }} {{ $patientName }} &nbsp;|&nbsp; ID: {{ $patientHid }}
        </div>
    </div>
    <div class="pb-right">
        <div><span class="status-badge-active">{{ $planStatus }}</span></div>
        <div>{{ $language === 'fr' ? 'Créé le:' : 'Created:' }} {{ $planCreated }}</div>
        <div>{{ $language === 'fr' ? 'Révision:' : 'Review:' }} <strong>{{ $planReview }}</strong></div>
    </div>
</div>

{{-- SECTION 2: Care Team --}}
<div class="cpl-team-grid">
    <div class="cpl-team-card lead">
        <div class="tc-role">{{ $language === 'fr' ? 'Médecin Responsable' : 'Lead Provider' }}</div>
        <div class="tc-name">{{ $teamLead['name'] }}</div>
        <div class="tc-desc">{{ $teamLead['role'] }}</div>
    </div>
    @foreach($teamSupport as $s)
    <div class="cpl-team-card support">
        <div class="tc-role">{{ $language === 'fr' ? 'Services de Soutien' : 'Support Services' }}</div>
        <div class="tc-name">{{ $s['name'] }}</div>
        <div class="tc-desc">{{ $s['role'] }}</div>
    </div>
    @endforeach
    @foreach($teamPending as $p)
    <div class="cpl-team-card pending">
        <div class="tc-role">{{ $language === 'fr' ? 'Référence en Attente' : 'Pending Referral' }}</div>
        <div class="tc-name">{{ $p['name'] }}</div>
        <div class="tc-desc">{{ $p['role'] }}</div>
    </div>
    @endforeach
</div>

{{-- SECTION 3: Goals --}}
<div class="cpl-section-header">
    {{ $language === 'fr' ? 'OBJECTIFS DU PLAN DE SOINS / CARE PLAN GOALS' : 'CARE PLAN GOALS' }}
</div>
<div class="cpl-goals-list">
    @foreach($goals as $g)
    <div class="cpl-goal-item">
        <div class="goal-num {{ $g['priority'] }}">{{ $g['num'] }}</div>
        <div class="goal-body">
            <div class="g-text">{{ $g['goal'] }}</div>
            <div class="g-target">
                {{ $language === 'fr' ? 'Date cible:' : 'Target:' }} {{ $g['target'] }}
                &nbsp;
                <span class="goal-priority-{{ $g['priority'] }}">{{ strtoupper($g['priority']) }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- SECTION 4: Interventions --}}
<div class="cpl-section-header">
    {{ $language === 'fr' ? 'INTERVENTIONS / PLAN D\'INTERVENTIONS' : 'INTERVENTIONS' }}
</div>
<table class="cpl-interventions-table">
    <thead>
        <tr>
            <th>{{ $language === 'fr' ? 'TYPE' : 'TYPE' }}</th>
            <th>{{ $language === 'fr' ? 'INTERVENTION' : 'INTERVENTION' }}</th>
            <th>{{ $language === 'fr' ? 'FRÉQUENCE' : 'FREQUENCY' }}</th>
            <th>{{ $language === 'fr' ? 'RESPONSABLE' : 'RESPONSIBLE' }}</th>
            <th>{{ $language === 'fr' ? 'ÉCHÉANCE' : 'TARGET DATE' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($interventions as $int)
        <tr>
            <td>
                @php $typeKey = strtolower($int['type']); @endphp
                <span class="type-badge-{{ $typeKey }}">{{ strtoupper($int['type']) }}</span>
            </td>
            <td style="font-weight: 500;">{{ $int['intervention'] }}</td>
            <td>{{ $int['frequency'] }}</td>
            <td>{{ $int['responsible'] }}</td>
            <td style="font-weight: 600; color: #0F766E;">{{ $int['target_date'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- SECTION 5: Patient Education --}}
<div class="cpl-section-header">
    {{ $language === 'fr' ? 'ÉDUCATION DU PATIENT / PATIENT EDUCATION TOPICS' : 'PATIENT EDUCATION' }}
</div>
<div class="cpl-education-grid">
    @foreach($educationTopics as $edu)
    <div class="cpl-edu-card" style="background: {{ $edu['bg'] }}; color: {{ $edu['color'] }};">
        {{ $edu['title'] }}
    </div>
    @endforeach
</div>

{{-- SECTION 6: Progress Notes --}}
<div class="cpl-section-header">
    {{ $language === 'fr' ? 'NOTES DE PROGRESSION / PROGRESS NOTES' : 'PROGRESS NOTES' }}
</div>
<div class="cpl-timeline">
    @foreach($progressNotes as $note)
    <div class="cpl-timeline-item">
        <div class="tl-dot"></div>
        <div>
            <div class="tl-date">{{ $note['date'] }}</div>
            <div class="tl-author">{{ $note['author'] }}</div>
            <div class="tl-note">{{ $note['note'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- SECTION 7: Patient Acknowledgement --}}
<div class="cpl-acknowledgement">
    <div class="ack-header">
        {{ $language === 'fr' ? 'RECONNAISSANCE DU PATIENT / PATIENT ACKNOWLEDGEMENT' : 'PATIENT ACKNOWLEDGEMENT & AGREEMENT' }}
    </div>
    <div class="ack-text">
        {{ $language === 'fr'
            ? 'Je confirme avoir été informé(e) de ce plan de soins, avoir compris ses objectifs, et accepter de participer activement à sa mise en œuvre. J\'ai eu l\'opportunité de poser des questions et d\'en discuter avec mon équipe soignante.'
            : 'I confirm that I have been informed of this care plan, understand its goals, and agree to actively participate in its implementation. I have had the opportunity to ask questions and discuss this plan with my care team.' }}
    </div>
    <div class="cpl-sig-row">
        <div class="cpl-sig-block">
            <div class="cpl-sig-area"></div>
            <div class="cpl-sig-line">
                <strong>{{ $patientName }}</strong><br>
                {{ $language === 'fr' ? 'Signature du Patient / Date' : 'Patient Signature / Date' }}
            </div>
        </div>
        <div class="cpl-sig-block">
            <div class="cpl-sig-area"></div>
            <div class="cpl-sig-line">
                <strong>{{ $teamLead['name'] }}</strong><br>
                {{ $language === 'fr' ? 'Signature du Prestataire / Date' : 'Provider Signature / Date' }}
            </div>
        </div>
    </div>
</div>
@endsection
