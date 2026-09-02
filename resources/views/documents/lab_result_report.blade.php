@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Rapport de résultat de laboratoire' : 'Laboratory Result Report' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Analyse clinique officielle' : 'Official Clinical Analysis Report' }}
@endsection

@section('content')
    <!-- Specimen Info Card -->
    <div class="content-card">
        <div class="card-header">
            {{ $language === 'fr' ? 'INFORMATIONS SUR L\'ÉCHANTILLON' : 'SPECIMEN / SAMPLE INFORMATION' }}
        </div>
        <div class="card-body" style="padding: 3mm 4mm;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4mm;">
                <div>
                    <span style="color: #64748B;">Sample ID / ID Échantillon:</span>
                    <strong style="display: block; color: #0F172A;">{{ $payload['sample_id'] ?? 'SMP-90123' }}</strong>
                </div>
                <div>
                    <span style="color: #64748B;">Specimen Type / Type d'échantillon:</span>
                    <strong style="display: block; color: #0F172A;">{{ $payload['specimen_type'] ?? 'Blood / Sang' }}</strong>
                </div>
                <div>
                    <span style="color: #64748B;">Condition / État:</span>
                    <strong style="display: block; color: #15803D;">{{ $payload['specimen_condition'] ?? 'Acceptable' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table Card -->
    <div class="content-card">
        <div class="card-header">
            {{ $language === 'fr' ? 'RÉSULTATS DES ANALYSES' : 'CLINICAL LAB TEST RESULTS' }}
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>{{ $language === 'fr' ? 'ANALYSE' : 'TEST NAME' }}</th>
                        <th>LOINC / CODE</th>
                        <th>{{ $language === 'fr' ? 'RÉSULTAT' : 'RESULT' }}</th>
                        <th>UNIT</th>
                        <th>{{ $language === 'fr' ? 'INTERVALLE DE RÉFÉRENCE' : 'REFERENCE RANGE' }}</th>
                        <th>FLAG</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['results'] ?? [] as $result)
                        <tr>
                            <td style="font-weight: 600; color: #0F4C81;">{{ $result['test_name'] }}</td>
                            <td style="font-family: monospace; font-size: 9.5px; color: #475569;">{{ $result['loinc_code'] ?? 'N/A' }}</td>
                            <td style="font-weight: 700; font-size: 11px; color: {{ ($result['flag'] ?? '') === 'critical' || ($result['flag'] ?? '') === 'high' || ($result['flag'] ?? '') === 'low' ? '#B91C1C' : '#0F172A' }};">
                                {{ $result['result_value'] }}
                            </td>
                            <td>{{ $result['unit'] }}</td>
                            <td style="color: #475569;">{{ $result['reference_range'] }}</td>
                            <td>
                                @if(($result['flag'] ?? '') === 'high')
                                    <span style="background-color: #FEE2E2; color: #B91C1C; padding: 0.5mm 1.5mm; font-size: 8px; font-weight: 700; border-radius: 3px;">HIGH</span>
                                @elseif(($result['flag'] ?? '') === 'low')
                                    <span style="background-color: #FEE2E2; color: #B91C1C; padding: 0.5mm 1.5mm; font-size: 8px; font-weight: 700; border-radius: 3px;">LOW</span>
                                @elseif(($result['flag'] ?? '') === 'critical')
                                    <span style="background-color: #7F1D1D; color: #FFFFFF; padding: 0.5mm 1.5mm; font-size: 8px; font-weight: 700; border-radius: 3px;">CRITICAL</span>
                                @else
                                    <span style="background-color: #E6F7F5; color: #0F766E; padding: 0.5mm 1.5mm; font-size: 8px; font-weight: 700; border-radius: 3px;">NORMAL</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clinical Interpretive Notes -->
    @if(!empty($payload['interpretive_notes']))
        <div class="content-card">
            <div class="card-header">
                {{ $language === 'fr' ? 'NOTES D\'INTERPRÉTATION CLINIQUE' : 'CLINICAL INTERPRETIVE NOTES' }}
            </div>
            <div class="card-body" style="font-size: 10px; color: #475569; line-height: 1.5;">
                {{ $payload['interpretive_notes'] }}
            </div>
        </div>
    @endif

    <!-- Critical Alert notice if critical flag is present -->
    @php
        $hasCritical = false;
        foreach($payload['results'] ?? [] as $r) {
            if (($r['flag'] ?? '') === 'critical') {
                $hasCritical = true;
                break;
            }
        }
    @endphp
    @if($hasCritical)
        <div style="background-color: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 6px; padding: 3mm 4mm; margin-bottom: 6mm; color: #7F1D1D; font-weight: 600; font-size: 10px;">
            ⚠️ {{ $language === 'fr' 
                ? 'RÉSULTAT CRITIQUE : Ce rapport contient des valeurs critiques. Veuillez consulter immédiatement un professionnel de la santé.' 
                : 'CRITICAL ALERT: This report contains critical values. Please consult with the requesting physician immediately.' }}
        </div>
    @endif
@endsection
