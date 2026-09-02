@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Ordonnance Médicale' : 'Prescription' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Prescription officielle' : 'Official Medical Prescription' }}
@endsection

@section('content')
    <!-- Clinical Directives / Diagnosis Summary -->
    <div class="content-card">
        <div class="card-header">
            {{ $language === 'fr' ? 'DIRECTIVES CLINIQUES ET DIAGNOSTIC' : 'CLINICAL DIRECTIVES & DIAGNOSIS SUMMARY' }}
        </div>
        <div class="card-body" style="padding: 3mm 4mm;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 4mm;">
                <div>
                    <span style="color: #64748B;">Indication / Diagnosis Summary:</span>
                    <strong style="display: block; color: #0F172A; margin-top: 0.5mm;">{{ $payload['clinical_indication'] ?? 'Clinical evaluation follow-up' }}</strong>
                </div>
                <div>
                    <span style="color: #64748B;">Validity / Validité:</span>
                    <strong style="display: block; color: #0F766E; margin-top: 0.5mm;">{{ $payload['validity_period'] ?? '30 Days / Jours' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Medication Table Card -->
    <div class="content-card">
        <div class="card-header">
            {{ $language === 'fr' ? 'MÉDICAMENTS PRESCRITS' : 'PRESCRIBED MEDICATIONS' }}
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>{{ $language === 'fr' ? 'MÉDICAMENT' : 'MEDICINE' }}</th>
                        <th>{{ $language === 'fr' ? 'FORME & DOSAGE' : 'STRENGTH & FORM' }}</th>
                        <th>POSOLOGY / POSOLOGIE</th>
                        <th>{{ $language === 'fr' ? 'DURÉE' : 'DURATION' }}</th>
                        <th>QTY</th>
                        <th>SUBST.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['medications'] ?? [] as $med)
                        <tr>
                            <td style="font-weight: 700; color: #0F4C81;">
                                {{ $med['name'] }}
                                <span style="font-size: 8.5px; color: #64748B; display: block; font-weight: normal; font-style: italic;">
                                    Generic: {{ $med['generic_name'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $med['strength'] }} ({{ $med['form'] }})</td>
                            <td style="font-weight: 500;">
                                {{ $med['dose'] }} via {{ $med['route'] }} - {{ $med['frequency'] }}
                                @if(!empty($med['instructions']))
                                    <span style="font-size: 8px; color: #475569; display: block; font-weight: normal;">
                                        Note: {{ $med['instructions'] }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $med['duration'] }}</td>
                            <td style="font-weight: 700; color: #0F172A;">{{ $med['quantity'] }}</td>
                            <td>
                                @if($med['substitution_allowed'] ?? false)
                                    <span style="background-color: #E6F7F5; color: #0F766E; padding: 0.5mm 1.5mm; font-size: 8px; font-weight: 600; border-radius: 3px;">ALLOWED</span>
                                @else
                                    <span style="background-color: #FEE2E2; color: #B91C1C; padding: 0.5mm 1.5mm; font-size: 8px; font-weight: 600; border-radius: 3px;">NO</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clinical Warnings & Allergies -->
    @if(!empty($payload['allergy_warnings']))
        <div style="background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 6px; padding: 3mm 4mm; margin-bottom: 6mm; color: #B45309; font-weight: 600; font-size: 10px;">
            ⚠️ {{ $language === 'fr' 
                ? 'AVERTISSEMENT D\'ALLERGIE : ' 
                : 'CLINICAL ALLERGY & WARNING DIRECTIVES: ' }}
            <span style="font-weight: normal;">{{ $payload['allergy_warnings'] }}</span>
        </div>
    @endif

    <div style="font-size: 9px; color: #64748B; font-style: italic; margin-top: 4mm; text-align: center;">
        "{{ $language === 'fr' 
            ? 'Utilisez les médicaments uniquement comme indiqué par le professionnel de la santé prescripteur.' 
            : 'Use medicines only as directed by the prescribing healthcare professional.' }}"
    </div>
@endsection
