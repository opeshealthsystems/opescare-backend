@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Résumé de Sortie' : 'Discharge Summary' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Document de sortie officiel — DIS' : 'Official Inpatient Discharge Document — DIS' }}
@endsection

@section('content')
<style>
    .discharge-condition-banner {
        text-align: center;
        padding: 4mm 6mm;
        border-radius: 8px;
        margin-bottom: 6mm;
        border: 2px solid;
    }
    .condition-stable {
        background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        border-color: #6EE7B7;
        color: #065F46;
    }
    .condition-critical {
        background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
        border-color: #FCA5A5;
        color: #7F1D1D;
    }
    .condition-improved {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        border-color: #93C5FD;
        color: #1E3A5F;
    }
    .condition-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1.5mm;
        opacity: 0.75;
    }
    .condition-value {
        font-size: 22px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    /* Admission stat boxes */
    .admission-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 4mm;
        margin-bottom: 6mm;
    }
    .stat-box {
        background-color: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3mm 4mm;
        text-align: center;
    }
    .stat-box-label {
        font-size: 8.5px;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 1.5mm;
    }
    .stat-box-value {
        font-size: 13px;
        font-weight: 800;
        color: #0F172A;
    }
    .stat-box-sub {
        font-size: 8.5px;
        color: #94A3B8;
        margin-top: 0.5mm;
    }

    /* Diagnosis list */
    .diagnosis-item {
        display: flex;
        align-items: flex-start;
        gap: 2.5mm;
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .diagnosis-item:last-child { border-bottom: none; }
    .diag-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-top: 1mm;
        flex-shrink: 0;
    }
    .diag-dot-primary { background-color: #DC2626; }
    .diag-dot-secondary { background-color: #D97706; }

    /* Procedures */
    .procedure-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        padding: 1.5mm 0;
        font-size: 10.5px;
    }
    .procedure-check {
        color: #059669;
        font-weight: 700;
        flex-shrink: 0;
        margin-top: 0.2mm;
    }

    /* Medications table accent */
    .med-row-drug { font-weight: 700; color: #0F172A; }

    /* Follow-up timeline */
    .followup-timeline { padding-left: 3mm; }
    .followup-item {
        display: flex;
        align-items: flex-start;
        gap: 3mm;
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
        position: relative;
    }
    .followup-item:last-child { border-bottom: none; }
    .followup-dot {
        width: 10px;
        height: 10px;
        background-color: #DC2626;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 0.5mm;
    }
    .followup-when {
        font-size: 9px;
        font-weight: 700;
        color: #DC2626;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .followup-specialist {
        font-size: 10.5px;
        font-weight: 600;
        color: #0F172A;
    }

    /* Red flags box */
    .red-flags-box {
        background-color: #FFFBEB;
        border: 2px solid #FCD34D;
        border-left: 5px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 6mm;
    }
    .red-flags-title {
        font-size: 11px;
        font-weight: 800;
        color: #DC2626;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2mm;
    }
    .red-flag-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        padding: 1mm 0;
        font-size: 10px;
        color: #7F1D1D;
        font-weight: 500;
    }
    .red-flag-bullet {
        color: #DC2626;
        font-weight: 900;
        flex-shrink: 0;
    }
</style>

<!-- CONDITION AT DISCHARGE BANNER -->
@php
    $condClass = 'condition-improved';
    $condLabel = $payload['condition_at_discharge'] ?? 'STABLE';
    if (strtolower($condLabel) === 'stable') $condClass = 'condition-stable';
    elseif (in_array(strtolower($condLabel), ['critical', 'poor', 'deteriorating'])) $condClass = 'condition-critical';
@endphp
<div class="discharge-condition-banner {{ $condClass }}">
    <div class="condition-label">{{ $language === 'fr' ? 'ÉTAT À LA SORTIE' : 'CONDITION AT DISCHARGE' }}</div>
    <div class="condition-value">{{ strtoupper($condLabel) }}</div>
</div>

<!-- Admission Stats -->
<div class="admission-stats">
    <div class="stat-box">
        <div class="stat-box-label">{{ $language === 'fr' ? 'Admis le' : 'Admitted' }}</div>
        <div class="stat-box-value" style="font-size: 11px;">{{ $payload['admission_date'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-label">{{ $language === 'fr' ? 'Sorti le' : 'Discharged' }}</div>
        <div class="stat-box-value" style="font-size: 11px;">{{ $payload['discharge_date'] }}</div>
    </div>
    <div class="stat-box" style="background-color: #FEF2F2; border-color: #FCA5A5;">
        <div class="stat-box-label" style="color: #991B1B;">{{ $language === 'fr' ? 'Durée du séjour' : 'Length of Stay' }}</div>
        <div class="stat-box-value" style="color: #DC2626;">{{ $payload['length_of_stay'] }}</div>
        <div class="stat-box-sub">{{ $language === 'fr' ? 'Jours' : 'Days' }}</div>
    </div>
</div>

<!-- Ward / Bed Info -->
<div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 2.5mm 4mm; margin-bottom: 6mm; display: flex; gap: 8mm; font-size: 10.5px;">
    <span><strong style="color: #64748B;">{{ $language === 'fr' ? 'Service :' : 'Ward:' }}</strong> <strong>{{ $payload['ward'] }}</strong></span>
    <span><strong style="color: #64748B;">{{ $language === 'fr' ? 'Lit N° :' : 'Bed No.:' }}</strong> <strong>{{ $payload['bed_number'] }}</strong></span>
</div>

<!-- Admission Diagnosis -->
<div style="background-color: #FEF2F2; border-left: 5px solid #DC2626; border-radius: 0 6px 6px 0; padding: 3mm 4mm; margin-bottom: 6mm;">
    <div style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #991B1B; margin-bottom: 1mm;">
        {{ $language === 'fr' ? 'DIAGNOSTIC D\'ADMISSION' : 'ADMISSION DIAGNOSIS' }}
    </div>
    <div style="font-size: 12px; font-weight: 700; color: #7F1D1D;">{{ $payload['admission_diagnosis'] }}</div>
</div>

<!-- Final Diagnoses -->
<div class="content-card">
    <div class="card-header" style="background-color: #FEF2F2; color: #991B1B;">
        {{ $language === 'fr' ? 'DIAGNOSTICS FINAUX' : 'FINAL DIAGNOSES' }}
    </div>
    <div class="card-body">
        @foreach($payload['final_diagnoses'] ?? [] as $index => $diagnosis)
        <div class="diagnosis-item">
            <div class="diag-dot {{ $index === 0 ? 'diag-dot-primary' : 'diag-dot-secondary' }}"></div>
            <div>
                <div style="font-size: 10.5px; font-weight: 600; color: #0F172A;">
                    {{ ($index + 1) }}. {{ $diagnosis }}
                </div>
                @if($index === 0)
                <div style="font-size: 8.5px; color: #DC2626; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 0.3mm;">
                    {{ $language === 'fr' ? 'Diagnostic principal' : 'Primary Diagnosis' }}
                </div>
                @else
                <div style="font-size: 8.5px; color: #D97706; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 0.3mm;">
                    {{ $language === 'fr' ? 'Diagnostic secondaire' : 'Secondary Diagnosis' }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Procedures Performed -->
@if(!empty($payload['procedures_performed']))
<div class="content-card">
    <div class="card-header" style="background-color: #FEF2F2; color: #991B1B;">
        {{ $language === 'fr' ? 'PROCÉDURES RÉALISÉES' : 'PROCEDURES PERFORMED' }}
    </div>
    <div class="card-body">
        @foreach($payload['procedures_performed'] as $procedure)
        <div class="procedure-item">
            <span class="procedure-check">✓</span>
            <span>{{ $procedure }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Discharge Medications -->
@if(!empty($payload['discharge_medications']))
<div class="content-card">
    <div class="card-header" style="background-color: #FEF2F2; color: #991B1B;">
        {{ $language === 'fr' ? 'MÉDICAMENTS À LA SORTIE' : 'DISCHARGE MEDICATIONS' }}
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="doc-table">
            <thead>
                <tr>
                    <th>{{ $language === 'fr' ? 'MÉDICAMENT' : 'DRUG' }}</th>
                    <th>{{ $language === 'fr' ? 'FRÉQUENCE' : 'FREQUENCY' }}</th>
                    <th>{{ $language === 'fr' ? 'DURÉE' : 'DURATION' }}</th>
                    <th>{{ $language === 'fr' ? 'REMARQUES' : 'NOTES' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['discharge_medications'] as $med)
                <tr>
                    <td class="med-row-drug">{{ $med['drug'] }}</td>
                    <td>{{ $med['frequency'] }}</td>
                    <td>{{ $med['duration'] }}</td>
                    <td style="font-size: 9.5px; color: #64748B;">{{ $med['notes'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Follow-Up Schedule -->
@if(!empty($payload['follow_up']))
<div class="content-card">
    <div class="card-header" style="background-color: #FEF2F2; color: #991B1B;">
        {{ $language === 'fr' ? 'PLANIFICATION DES SUIVIS' : 'FOLLOW-UP SCHEDULE' }}
    </div>
    <div class="card-body">
        <div class="followup-timeline">
            @foreach($payload['follow_up'] as $fu)
            <div class="followup-item">
                <div class="followup-dot"></div>
                <div>
                    <div class="followup-when">{{ $fu['when'] }}</div>
                    <div class="followup-specialist">{{ $fu['specialist'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- RED FLAGS -->
@if(!empty($payload['red_flags']))
<div class="red-flags-box">
    <div class="red-flags-title">⚠️ {{ $language === 'fr' ? 'SIGNES D\'ALARME — CONSULTEZ IMMÉDIATEMENT SI :' : 'RED FLAGS — RETURN IMMEDIATELY IF:' }}</div>
    @foreach($payload['red_flags'] as $flag)
    <div class="red-flag-item">
        <span class="red-flag-bullet">▶</span>
        <span>{{ $flag }}</span>
    </div>
    @endforeach
</div>
@endif

<!-- Diet & Lifestyle -->
@if(!empty($payload['diet_instructions']))
<div class="content-card">
    <div class="card-header" style="background-color: #FEF2F2; color: #991B1B;">
        {{ $language === 'fr' ? 'CONSEILS DIÉTÉTIQUES ET HYGIÈNE DE VIE' : 'DIETARY & LIFESTYLE INSTRUCTIONS' }}
    </div>
    <div class="card-body">
        <p style="margin: 0; font-size: 10.5px; line-height: 1.6; color: #334155;">{{ $payload['diet_instructions'] }}</p>
    </div>
</div>
@endif

<div style="text-align: center; font-size: 9px; color: #94A3B8; font-style: italic; margin-top: 3mm;">
    {{ $language === 'fr'
        ? 'Ce résumé de sortie a été préparé par l\'équipe clinique traitante.'
        : 'This discharge summary was prepared by the attending clinical team.' }}
</div>
@endsection
