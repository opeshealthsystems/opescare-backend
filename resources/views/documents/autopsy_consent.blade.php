@extends('documents.base')

@section('title', 'Consent to Post-Mortem Examination')
@section('subtitle', 'APC · Ref: ' . ($document_number ?? 'OC-APC-2026-000001') . ' · CONSENTED')

@section('content')
{{-- ============================================================
     CONSENT TO POST-MORTEM / CONSENTEMENT À L'AUTOPSIE
     Slug: autopsy-consent | Code: APC | Color: #1a3c5e
     ============================================================ --}}

<style>
    :root {
        --apc-dark:   #1a3c5e;
        --apc-mid:    #4B5563;
        --apc-light:  #f8f9fa;
        --apc-border: #D1D5DB;
        --apc-black:  #111827;
    }
    .apc-wrap { font-family: 'Times New Roman', serif; color: var(--apc-black); }
    .apc-confidential {
        background: #7F1D1D; color: #fff; text-align: center;
        padding: 6px 0; font-size: 10px; font-weight: 700; letter-spacing: 3px;
        text-transform: uppercase; font-family: Arial, sans-serif; margin-bottom: 12px;
        border-radius: 3px;
    }
    .apc-doc-title {
        text-align: center; margin: 10px 0 4px; padding: 12px 0;
        border-top: 2px solid var(--apc-dark); border-bottom: 2px solid var(--apc-dark);
    }
    .apc-doc-title h1 { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; margin: 0; color: var(--apc-dark); }
    .apc-doc-title h2 { font-size: 13px; font-weight: 600; font-style: italic; margin: 3px 0 0; color: var(--apc-mid); }
    .apc-doc-meta { margin-top: 6px; font-size: 10px; color: var(--apc-mid); font-style: italic; }
    .apc-section { margin: 14px 0 0; }
    .apc-section-title {
        background: var(--apc-dark); color: #fff; padding: 6px 14px;
        font-size: 9.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1.5px; border-radius: 3px 3px 0 0; font-family: Arial, sans-serif;
    }
    .apc-section-body { border: 1.5px solid var(--apc-dark); border-top: none; padding: 12px 14px; border-radius: 0 0 3px 3px; }
    .apc-grid { display: grid; gap: 10px 20px; }
    .apc-grid-2 { grid-template-columns: 1fr 1fr; }
    .apc-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .apc-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--apc-mid); font-weight: 700; margin-bottom: 2px; font-family: Arial, sans-serif; }
    .apc-value { font-size: 12px; font-weight: 600; color: var(--apc-black); border-bottom: 1px solid var(--apc-border); padding-bottom: 3px; min-height: 20px; }
    .apc-badge-type { display: inline-block; background: var(--apc-dark); color: #fff; border-radius: 3px; padding: 3px 12px; font-size: 12px; font-weight: 700; }
    .apc-badge-yes { display: inline-block; background: #065F46; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .apc-badge-no  { display: inline-block; background: #991B1B; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .apc-consent-text { background: #F0F9FF; border: 1.5px solid #0EA5E9; border-radius: 4px; padding: 12px 14px; font-size: 11.5px; color: #0C4A6E; line-height: 1.9; margin: 10px 0; }
    .apc-sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 14px; }
    .apc-sig-area { height: 55px; border: 1px dashed var(--apc-border); border-radius: 4px; margin-bottom: 6px; background: #FAFAFA; }
    .apc-sig-line { border-bottom: 1.5px solid var(--apc-dark); margin-bottom: 4px; }
    .apc-sig-name { font-size: 12px; font-weight: 700; }
    .apc-sig-sub  { font-size: 10px; color: var(--apc-mid); font-style: italic; }
    .apc-legal { background: var(--apc-light); border-left: 4px solid var(--apc-dark); padding: 10px 14px; font-size: 10px; color: #4B5563; line-height: 1.7; margin: 14px 0; border-radius: 0 4px 4px 0; font-family: Arial, sans-serif; }
    .apc-restrictions { background: #FEF3C7; border: 1.5px solid #D97706; border-radius: 4px; padding: 10px 14px; font-size: 11px; color: #92400E; margin: 10px 0; }
</style>

<div class="apc-wrap">

    <div class="apc-confidential">&#9888; Confidential Medical Document — Document Médical Confidentiel &#9888;</div>

    <div class="apc-doc-title">
        <h1>Consent to Post-Mortem Examination</h1>
        <h2>Consentement à l'Autopsie</h2>
        <div class="apc-doc-meta">Doc No.: {{ $document_number }} &bull; Date: {{ $payload['consent_date'] ?? $issued_at }}</div>
    </div>

    {{-- ── I. DECEASED DETAILS ── --}}
    <div class="apc-section">
        <div class="apc-section-title">I. Deceased Details / Informations sur le Défunt</div>
        <div class="apc-section-body">
            <div class="apc-grid apc-grid-3">
                <div style="grid-column: span 2;">
                    <div class="apc-label">Deceased Name / Nom du Défunt</div>
                    <div class="apc-value">{{ $payload['deceased_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">Date of Death / Date du Décès</div>
                    <div class="apc-value">{{ $payload['date_of_death'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">Relationship to Deceased / Lien avec le Défunt</div>
                    <div class="apc-value">{{ $payload['relationship_to_deceased'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. CONSENTING PARTY ── --}}
    <div class="apc-section">
        <div class="apc-section-title">II. Consenting Party / Partie Consentante</div>
        <div class="apc-section-body">
            <div class="apc-grid apc-grid-3">
                <div>
                    <div class="apc-label">Full Name / Nom Complet</div>
                    <div class="apc-value">{{ $payload['consenting_party_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">ID Type / Type de Pièce</div>
                    <div class="apc-value">{{ $payload['consenting_party_id_type'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">ID Number / Numéro</div>
                    <div class="apc-value">{{ $payload['consenting_party_id_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">Contact / Téléphone</div>
                    <div class="apc-value">{{ $payload['consenting_party_contact'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. AUTOPSY DETAILS ── --}}
    <div class="apc-section">
        <div class="apc-section-title">III. Autopsy Details / Détails de l'Autopsie</div>
        <div class="apc-section-body">
            <div class="apc-grid apc-grid-2">
                <div>
                    <div class="apc-label">Autopsy Type / Type d'Autopsie</div>
                    <div style="margin-top:5px;"><span class="apc-badge-type">{{ $payload['autopsy_type'] ?? 'Clinical' }}</span></div>
                </div>
                <div>
                    <div class="apc-label">Purpose / Objectif</div>
                    <div class="apc-value" style="min-height:36px;">{{ $payload['purpose_of_autopsy'] ?? '—' }}</div>
                </div>
            </div>
            <div class="apc-grid apc-grid-2" style="margin-top:12px;">
                <div>
                    <div class="apc-label">Consent to Organ Retention / Consentement à la Conservation d'Organes</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['consented_to_organ_retention']))
                            <span class="apc-badge-yes">Yes / Oui</span>
                        @else
                            <span class="apc-badge-no">No / Non</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="apc-label">Consent to Tissue Sampling / Consentement au Prélèvement</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['consented_to_tissue_sampling']))
                            <span class="apc-badge-yes">Yes / Oui</span>
                        @else
                            <span class="apc-badge-no">No / Non</span>
                        @endif
                    </div>
                </div>
            </div>
            @if(!empty($payload['restrictions_noted']))
            <div class="apc-restrictions" style="margin-top:12px;">
                <strong>Restrictions / Restrictions:</strong> {{ $payload['restrictions_noted'] }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── IV. CONSENT STATEMENT ── --}}
    <div class="apc-consent-text">
        I, <strong>{{ $payload['consenting_party_name'] ?? '___________________' }}</strong>, as
        <strong>{{ $payload['relationship_to_deceased'] ?? '___________________' }}</strong> of the late
        <strong>{{ $payload['deceased_name'] ?? '___________________' }}</strong>, hereby give my informed consent
        to a <strong>{{ $payload['autopsy_type'] ?? 'Clinical' }}</strong> post-mortem examination to be performed by
        <strong>{{ $payload['pathologist_name'] ?? '___________________' }}</strong> at <strong>{{ $facility_name }}</strong>.
        I understand the nature and purpose of this examination and consent to the procedures as described, subject to any
        restrictions noted above. I have been given the opportunity to ask questions and have had all my queries satisfactorily addressed.
    </div>

    {{-- ── V. WITNESS & PATHOLOGIST ── --}}
    <div class="apc-section">
        <div class="apc-section-title">V. Witness &amp; Pathologist / Témoin &amp; Pathologiste</div>
        <div class="apc-section-body">
            <div class="apc-grid apc-grid-3" style="margin-bottom:14px;">
                <div>
                    <div class="apc-label">Witness Name / Nom du Témoin</div>
                    <div class="apc-value">{{ $payload['witness_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">Witness Relationship / Lien</div>
                    <div class="apc-value">{{ $payload['witness_relationship'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">Pathologist / Pathologiste</div>
                    <div class="apc-value">{{ $payload['pathologist_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="apc-label">Consent Date / Date</div>
                    <div class="apc-value">{{ $payload['consent_date'] ?? $issued_at }}</div>
                </div>
                <div>
                    <div class="apc-label">Consent Time / Heure</div>
                    <div class="apc-value">{{ $payload['consent_time'] ?? '—' }}</div>
                </div>
            </div>
            <div class="apc-sig-grid">
                <div>
                    <div class="apc-sig-area"></div>
                    <div class="apc-sig-line"></div>
                    <div class="apc-sig-name">{{ $payload['consenting_party_name'] ?? '—' }}</div>
                    <div class="apc-sig-sub">Consenting Party / Partie Consentante</div>
                    <div class="apc-sig-sub">Date: {{ $payload['consent_date'] ?? $issued_at }}</div>
                </div>
                <div>
                    <div class="apc-sig-area"></div>
                    <div class="apc-sig-line"></div>
                    <div class="apc-sig-name">{{ $payload['pathologist_name'] ?? $issuer_name }}</div>
                    <div class="apc-sig-sub">Pathologist / Pathologiste</div>
                    <div class="apc-sig-sub">{{ $facility_name }}</div>
                    <div class="apc-sig-sub">Date: {{ $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="apc-legal">
        <strong>Confidentiality:</strong> This consent form is a strictly confidential clinical document. It is retained as part of the deceased's permanent medical record at {{ $facility_name }} and may only be disclosed in accordance with applicable health legislation and the family's express instructions.
    </div>

</div>
@endsection
