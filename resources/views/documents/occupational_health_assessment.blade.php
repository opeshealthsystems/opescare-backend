@extends('documents.base')

@section('title', 'Occupational Health Assessment / Évaluation de Santé au Travail')
@section('subtitle', 'OCCUPATIONAL HEALTH — CONFIDENTIAL · Doc: ' . ($document_number ?? 'N/A') . ' · ' . strtoupper($payload['assessment_type'] ?? 'ASSESSMENT'))

@section('content')
{{-- ============================================================
     OCCUPATIONAL HEALTH ASSESSMENT / ÉVALUATION DE SANTÉ AU TRAVAIL
     Slug: occupational-health-assessment | Code: OHA | Color: #1a4e3a
     ============================================================ --}}

<style>
    :root {
        --oha-dark:   #1a4e3a;
        --oha-mid:    #4B5563;
        --oha-light:  #f8f9fa;
        --oha-border: #D1D5DB;
        --oha-black:  #111827;
        --oha-red:    #DC2626;
        --oha-amber:  #92400E;
        --oha-green:  #065F46;
        --oha-blue:   #1E3A5F;
    }
    .oha-wrap { font-family: 'Times New Roman', serif; color: var(--oha-black); }
    .oha-doc-title {
        text-align: center;
        margin: 18px 0 4px;
        padding: 12px 0;
        border-top: 3px solid var(--oha-dark);
        border-bottom: 3px solid var(--oha-dark);
    }
    .oha-doc-title h1 { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; margin: 0; color: var(--oha-dark); }
    .oha-doc-title h2 { font-size: 13px; font-weight: 600; font-style: italic; margin: 3px 0 0; color: var(--oha-mid); }
    .oha-doc-title .oha-confidential { font-size: 10px; font-weight: 800; letter-spacing: 2px; color: var(--oha-red); text-transform: uppercase; margin-top: 6px; }
    .oha-doc-meta { margin-top: 4px; font-size: 10px; color: var(--oha-mid); font-style: italic; }
    .oha-section { margin: 14px 0 0; }
    .oha-section-title {
        background: var(--oha-dark);
        color: #fff;
        padding: 6px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 3px 3px 0 0;
        font-family: Arial, sans-serif;
    }
    .oha-section-body {
        border: 1.5px solid var(--oha-dark);
        border-top: none;
        padding: 12px 14px;
        border-radius: 0 0 3px 3px;
    }
    .oha-grid { display: grid; gap: 10px 20px; }
    .oha-grid-2 { grid-template-columns: 1fr 1fr; }
    .oha-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .oha-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .oha-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--oha-mid); font-weight: 700; margin-bottom: 2px; font-family: Arial, sans-serif; }
    .oha-value { font-size: 12px; font-weight: 600; color: var(--oha-black); border-bottom: 1px solid var(--oha-border); padding-bottom: 3px; min-height: 20px; }
    .oha-value-block { font-size: 12px; color: var(--oha-black); border: 1px solid var(--oha-border); padding: 8px 10px; border-radius: 3px; min-height: 52px; background: var(--oha-light); line-height: 1.6; }
    .oha-badge { display: inline-block; border-radius: 3px; padding: 3px 12px; font-size: 11px; font-weight: 700; font-family: Arial, sans-serif; }
    .oha-badge-fit          { background: var(--oha-green); color: #fff; }
    .oha-badge-restrictions { background: #D97706; color: #fff; }
    .oha-badge-temp-unfit   { background: #DC2626; color: #fff; }
    .oha-badge-perm-unfit   { background: #1F2937; color: #fff; }
    .oha-badge-pending      { background: #6B7280; color: #fff; }
    .oha-type-tag { display: inline-block; background: #EFF6FF; border: 1px solid #3B82F6; color: var(--oha-blue); border-radius: 3px; padding: 2px 8px; font-size: 10px; font-weight: 700; font-family: Arial, sans-serif; }
    .oha-sig-area { height: 55px; border: 1px dashed var(--oha-border); border-radius: 4px; margin-bottom: 6px; background: #FAFAFA; }
    .oha-sig-line { border-bottom: 1.5px solid var(--oha-dark); margin-bottom: 4px; }
    .oha-sig-name { font-size: 12px; font-weight: 700; }
    .oha-sig-sub  { font-size: 10px; color: var(--oha-mid); font-style: italic; }
    .oha-legal {
        background: var(--oha-light);
        border-left: 4px solid var(--oha-dark);
        padding: 10px 14px;
        font-size: 10px;
        color: #4B5563;
        line-height: 1.7;
        margin: 14px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
    }
    .oha-checklist { list-style: none; padding: 0; margin: 0; }
    .oha-checklist li { font-size: 11px; padding: 3px 0; display: flex; align-items: flex-start; gap: 8px; }
    .oha-checklist li::before { content: '☐'; font-size: 13px; flex-shrink: 0; color: var(--oha-mid); }
    .oha-checklist li.checked::before { content: '☑'; color: var(--oha-green); }
</style>

<div class="oha-wrap">

    {{-- ── DOCUMENT TITLE ── --}}
    <div class="oha-doc-title">
        <h1>Occupational Health Assessment</h1>
        <h2>Évaluation de Santé au Travail</h2>
        <div class="oha-confidential">&#9632; Occupational Health — Confidential / Santé au Travail — Confidentiel &#9632;</div>
        <div class="oha-doc-meta">
            Doc No.: {{ $document_number }}
            &bull; Assessment Date: {{ $payload['assessment_date'] ?? $issued_at }}
            &bull; Issued: {{ $issued_at }}
            &bull; Facility: {{ $facility_name }}
        </div>
    </div>

    {{-- ── I. PATIENT INFORMATION ── --}}
    <div class="oha-section">
        <div class="oha-section-title">I. Patient Information / Informations du Patient</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-4">
                <div style="grid-column: span 2;">
                    <div class="oha-label">Full Name / Nom Complet</div>
                    <div class="oha-value">{{ $patient_name }}</div>
                </div>
                <div>
                    <div class="oha-label">Date of Birth / Date de Naissance</div>
                    <div class="oha-value">{{ $patient_dob }}</div>
                </div>
                <div>
                    <div class="oha-label">Sex / Sexe</div>
                    <div class="oha-value">{{ $patient_sex }}</div>
                </div>
                <div>
                    <div class="oha-label">Health ID / Identifiant Santé</div>
                    <div class="oha-value">{{ $health_id }}</div>
                </div>
                <div>
                    <div class="oha-label">Facility / Établissement</div>
                    <div class="oha-value">{{ $facility_name }}</div>
                </div>
                <div>
                    <div class="oha-label">Facility Licence</div>
                    <div class="oha-value">{{ $facility_license }}</div>
                </div>
                <div>
                    <div class="oha-label">Document No.</div>
                    <div class="oha-value">{{ $document_number }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. EMPLOYER INFORMATION ── --}}
    <div class="oha-section">
        <div class="oha-section-title">II. Employer Information / Informations de l'Employeur</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-3">
                <div style="grid-column: span 2;">
                    <div class="oha-label">Employer Name / Nom de l'Employeur</div>
                    <div class="oha-value">{{ $payload['employer_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Job Title / Poste Occupé</div>
                    <div class="oha-value">{{ $payload['job_title'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Department / Service</div>
                    <div class="oha-value">{{ $payload['department'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Work Location / Lieu de Travail</div>
                    <div class="oha-value">{{ $payload['work_location'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Employment Duration / Durée d'Emploi</div>
                    <div class="oha-value">{{ $payload['employment_duration'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. ASSESSMENT DETAILS ── --}}
    <div class="oha-section">
        <div class="oha-section-title">III. Assessment Details / Détails de l'Évaluation</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-3">
                <div>
                    <div class="oha-label">Assessment Type / Type d'Évaluation</div>
                    <div style="margin-top:5px;">
                        @php
                            $assessmentType = strtolower($payload['assessment_type'] ?? '');
                            $typeLabels = [
                                'pre-employment'  => 'Pre-Employment / Pré-Emploi',
                                'periodic'        => 'Periodic / Périodique',
                                'post-incident'   => 'Post-Incident / Post-Incident',
                                'return-to-work'  => 'Return to Work / Reprise du Travail',
                                'exit'            => 'Exit / Départ',
                            ];
                        @endphp
                        <span class="oha-type-tag">{{ $typeLabels[$assessmentType] ?? ($payload['assessment_type'] ?? '—') }}</span>
                    </div>
                </div>
                <div>
                    <div class="oha-label">Assessment Date / Date de l'Évaluation</div>
                    <div class="oha-value">{{ $payload['assessment_date'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Next Review Date / Date de Prochain Contrôle</div>
                    <div class="oha-value">{{ $payload['next_review_date'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="oha-label">Examiner Name / Nom de l'Examinateur</div>
                    <div class="oha-value">{{ $payload['examiner_name'] ?? $issuer_name }}</div>
                </div>
                <div>
                    <div class="oha-label">Examiner Qualification / Qualification</div>
                    <div class="oha-value">{{ $payload['examiner_qualification'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IV. WORKPLACE EXPOSURE HISTORY ── --}}
    <div class="oha-section">
        <div class="oha-section-title">IV. Workplace Exposure History / Historique des Expositions Professionnelles</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-2" style="margin-bottom:10px;">
                <div>
                    <div class="oha-label">Hazards / Risques Professionnels</div>
                    @php
                        $hazardList = [
                            'chemical'     => 'Chemical / Chimique',
                            'biological'   => 'Biological / Biologique',
                            'physical'     => 'Physical (noise, vibration) / Physique (bruit, vibration)',
                            'ergonomic'    => 'Ergonomic / Ergonomique',
                            'psychosocial' => 'Psychosocial / Psychosocial',
                            'radiation'    => 'Radiation / Radiation',
                            'dust'         => 'Dust / Poussières',
                            'heat_cold'    => 'Extreme Temperatures / Températures Extrêmes',
                        ];
                        $exposures = $payload['workplace_hazards'] ?? [];
                        if (!is_array($exposures)) { $exposures = []; }
                    @endphp
                    <ul class="oha-checklist" style="margin-top:6px;">
                        @foreach($hazardList as $key => $label)
                            <li class="{{ in_array($key, $exposures) ? 'checked' : '' }}">{{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <div class="oha-label">Exposure Details / Détails des Expositions</div>
                    <div class="oha-value-block" style="margin-top:4px;">{{ $payload['exposure_details'] ?? '—' }}</div>
                    <div style="margin-top:10px;">
                        <div class="oha-label">Duration of Exposure / Durée d'Exposition</div>
                        <div class="oha-value">{{ $payload['exposure_duration'] ?? '—' }}</div>
                    </div>
                    <div style="margin-top:10px;">
                        <div class="oha-label">Personal Protective Equipment Used / EPI Utilisés</div>
                        <div class="oha-value">{{ $payload['ppe_used'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── V. CLINICAL FINDINGS ── --}}
    <div class="oha-section">
        <div class="oha-section-title">V. Clinical Findings / Résultats Cliniques</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-4" style="margin-bottom:10px;">
                <div>
                    <div class="oha-label">Blood Pressure / Tension Artérielle</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['blood_pressure'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Pulse / Pouls (bpm)</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['pulse'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Weight / Poids (kg)</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['weight_kg'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Height / Taille (cm)</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['height_cm'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">BMI</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['bmi'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Visual Acuity / Acuité Visuelle</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['visual_acuity'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Hearing / Audition</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['hearing'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Respiratory / Respiratoire</div>
                    <div class="oha-value">{{ $payload['clinical_findings']['respiratory'] ?? '—' }}</div>
                </div>
            </div>
            <div>
                <div class="oha-label">Physical Examination Findings / Résultats de l'Examen Physique</div>
                <div class="oha-value-block" style="margin-top:4px;">{{ $payload['clinical_findings']['physical_examination'] ?? '—' }}</div>
            </div>
            <div style="margin-top:10px;">
                <div class="oha-label">Laboratory / Investigations Results / Résultats des Investigations</div>
                <div class="oha-value-block">{{ $payload['clinical_findings']['investigations'] ?? '—' }}</div>
            </div>
            <div style="margin-top:10px;">
                <div class="oha-label">Relevant Medical History / Antécédents Médicaux Pertinents</div>
                <div class="oha-value-block">{{ $payload['clinical_findings']['medical_history'] ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ── VI. FITNESS CONCLUSION ── --}}
    <div class="oha-section">
        <div class="oha-section-title">VI. Fitness Conclusion / Conclusion d'Aptitude</div>
        <div class="oha-section-body">
            <div style="margin-bottom:12px;">
                <div class="oha-label">Fitness Status / Statut d'Aptitude</div>
                <div style="margin-top:6px;">
                    @php
                        $fitness = strtolower($payload['fitness_conclusion'] ?? '');
                    @endphp
                    @if($fitness === 'fit')
                        <span class="oha-badge oha-badge-fit">&#10003; FIT / APTE</span>
                    @elseif($fitness === 'fit_with_restrictions' || $fitness === 'fit-with-restrictions')
                        <span class="oha-badge oha-badge-restrictions">&#9888; FIT WITH RESTRICTIONS / APTE AVEC RESTRICTIONS</span>
                    @elseif($fitness === 'temporarily_unfit' || $fitness === 'temporarily-unfit')
                        <span class="oha-badge oha-badge-temp-unfit">&#9747; TEMPORARILY UNFIT / TEMPORAIREMENT INAPTE</span>
                    @elseif($fitness === 'permanently_unfit' || $fitness === 'permanently-unfit')
                        <span class="oha-badge oha-badge-perm-unfit">&#9747; PERMANENTLY UNFIT / DÉFINITIVEMENT INAPTE</span>
                    @else
                        <span class="oha-badge oha-badge-pending">PENDING / EN ATTENTE</span>
                    @endif
                </div>
            </div>
            <div class="oha-grid oha-grid-2">
                <div>
                    <div class="oha-label">Work Restrictions / Restrictions de Travail</div>
                    <div class="oha-value-block">{{ $payload['work_restrictions'] ?? 'None / Aucune' }}</div>
                </div>
                <div>
                    <div class="oha-label">Recommended Accommodations / Aménagements Recommandés</div>
                    <div class="oha-value-block">{{ $payload['recommended_accommodations'] ?? '—' }}</div>
                </div>
            </div>
            <div style="margin-top:10px;">
                <div class="oha-label">Clinical Justification / Justification Clinique</div>
                <div class="oha-value-block">{{ $payload['fitness_justification'] ?? '—' }}</div>
            </div>
            <div class="oha-grid oha-grid-2" style="margin-top:10px;">
                <div>
                    <div class="oha-label">Unfit Period From / Inapte du</div>
                    <div class="oha-value">{{ $payload['unfit_from'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Unfit Period To / Inapte jusqu'au</div>
                    <div class="oha-value">{{ $payload['unfit_to'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VII. NEXT REVIEW & FOLLOW-UP ── --}}
    <div class="oha-section">
        <div class="oha-section-title">VII. Next Review &amp; Follow-Up / Prochain Contrôle &amp; Suivi</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-3">
                <div>
                    <div class="oha-label">Next Review Date / Date du Prochain Contrôle</div>
                    <div class="oha-value">{{ $payload['next_review_date'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Review Type / Type de Contrôle</div>
                    <div class="oha-value">{{ $payload['next_review_type'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="oha-label">Referred To / Référé À</div>
                    <div class="oha-value">{{ $payload['referred_to'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 3;">
                    <div class="oha-label">Follow-Up Actions / Actions de Suivi</div>
                    <div class="oha-value-block">{{ $payload['follow_up_actions'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VIII. EXAMINER SIGNATURE ── --}}
    <div class="oha-section">
        <div class="oha-section-title">VIII. Examiner Signature / Signature de l'Examinateur</div>
        <div class="oha-section-body">
            <div class="oha-grid oha-grid-2">
                <div>
                    <div class="oha-sig-area"></div>
                    <div class="oha-sig-line"></div>
                    <div class="oha-sig-name">{{ $payload['examiner_name'] ?? $issuer_name }}</div>
                    <div class="oha-sig-sub">{{ $payload['examiner_qualification'] ?? $issuer_role }}</div>
                    <div class="oha-sig-sub">{{ $facility_name }}</div>
                    <div class="oha-sig-sub">Date: {{ $payload['assessment_date'] ?? $issued_at }}</div>
                </div>
                <div>
                    <div class="oha-sig-area"></div>
                    <div class="oha-sig-line"></div>
                    <div class="oha-sig-name">{{ $patient_name }}</div>
                    <div class="oha-sig-sub">Patient / Employé(e)</div>
                    <div class="oha-sig-sub">Health ID: {{ $health_id }}</div>
                    <div class="oha-sig-sub">Date: {{ $payload['assessment_date'] ?? $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VERIFICATION QR ── --}}
    @if(!empty($qr_svg))
        <div style="margin-top:14px; display:flex; align-items:flex-start; gap:16px;">
            <div style="flex-shrink:0;">{!! $qr_svg !!}</div>
            <div style="font-size:9.5px; color:var(--oha-mid); font-family:Arial, sans-serif; line-height:1.7;">
                <strong>Verification Code / Code de Vérification:</strong> {{ $verification_code }}<br>
                <strong>Document No.:</strong> {{ $document_number }}<br>
                Scan to verify authenticity at {{ $facility_name }}.
            </div>
        </div>
    @endif

    <div class="oha-legal">
        <strong>Confidentiality &amp; Legal Notice / Avis de Confidentialité &amp; Légal:</strong>
        This Occupational Health Assessment is a strictly confidential medical document of <strong>{{ $facility_name }}</strong>
        (Licence: {{ $facility_license }}). It is governed by applicable occupational health legislation, Cameroon Labour Code,
        and data protection laws including Cameroon Law No. 2010/012. This document is issued solely for occupational health
        purposes and must not be used for any other purpose without the written consent of the patient. Unauthorized disclosure,
        reproduction, or alteration is a criminal offence. Status: <strong>{{ strtoupper($status) }}</strong>.
        Language: {{ $language }}.
    </div>

</div>
@endsection
