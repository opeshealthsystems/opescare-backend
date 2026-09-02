@extends('documents.base')

@section('title', 'Body Identification Record / Fiche d\'Identification du Corps')
@section('subtitle', 'MORTUARY — CONFIDENTIAL · Tag: ' . ($payload['body_tag_number'] ?? 'N/A') . ' · ' . strtoupper($payload['identification_status'] ?? 'PENDING'))

@section('content')
{{-- ============================================================
     BODY IDENTIFICATION RECORD / FICHE D'IDENTIFICATION DU CORPS
     Slug: body-identification | Code: BID | Color: #1a3c5e
     ============================================================ --}}

<style>
    :root {
        --bid-dark:   #1a3c5e;
        --bid-mid:    #4B5563;
        --bid-light:  #f8f9fa;
        --bid-border: #D1D5DB;
        --bid-black:  #111827;
        --bid-red:    #DC2626;
        --bid-amber:  #92400E;
        --bid-green:  #065F46;
    }
    .bid-wrap { font-family: 'Times New Roman', serif; color: var(--bid-black); }
    .bid-doc-title {
        text-align: center;
        margin: 18px 0 4px;
        padding: 12px 0;
        border-top: 3px solid var(--bid-dark);
        border-bottom: 3px solid var(--bid-dark);
    }
    .bid-doc-title h1 { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; margin: 0; color: var(--bid-dark); }
    .bid-doc-title h2 { font-size: 13px; font-weight: 600; font-style: italic; margin: 3px 0 0; color: var(--bid-mid); }
    .bid-doc-title .bid-confidential { font-size: 10px; font-weight: 800; letter-spacing: 2px; color: var(--bid-red); text-transform: uppercase; margin-top: 6px; }
    .bid-doc-meta { margin-top: 4px; font-size: 10px; color: var(--bid-mid); font-style: italic; }
    .bid-section { margin: 14px 0 0; }
    .bid-section-title {
        background: var(--bid-dark);
        color: #fff;
        padding: 6px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 3px 3px 0 0;
        font-family: Arial, sans-serif;
    }
    .bid-section-body {
        border: 1.5px solid var(--bid-dark);
        border-top: none;
        padding: 12px 14px;
        border-radius: 0 0 3px 3px;
    }
    .bid-grid { display: grid; gap: 10px 20px; }
    .bid-grid-2 { grid-template-columns: 1fr 1fr; }
    .bid-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .bid-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .bid-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--bid-mid); font-weight: 700; margin-bottom: 2px; font-family: Arial, sans-serif; }
    .bid-value { font-size: 12px; font-weight: 600; color: var(--bid-black); border-bottom: 1px solid var(--bid-border); padding-bottom: 3px; min-height: 20px; }
    .bid-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .bid-table th { background: var(--bid-dark); color: #fff; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; font-family: Arial, sans-serif; }
    .bid-table td { padding: 8px 10px; border-bottom: 1px solid var(--bid-border); vertical-align: top; }
    .bid-table tr:nth-child(even) td { background: var(--bid-light); }
    .bid-badge { display: inline-block; border-radius: 3px; padding: 2px 10px; font-size: 11px; font-weight: 700; font-family: Arial, sans-serif; }
    .bid-badge-positive  { background: var(--bid-green); color: #fff; }
    .bid-badge-tentative { background: var(--bid-amber); color: #fff; }
    .bid-badge-unidentified { background: var(--bid-red); color: #fff; }
    .bid-badge-yes { background: var(--bid-green); color: #fff; }
    .bid-badge-no  { background: #6B7280; color: #fff; }
    .bid-method-tag { display: inline-block; background: #EFF6FF; border: 1px solid #3B82F6; color: #1E3A5F; border-radius: 3px; padding: 2px 7px; font-size: 10px; font-weight: 700; margin: 2px 2px 2px 0; font-family: Arial, sans-serif; }
    .bid-photo-box {
        border: 2px dashed var(--bid-red);
        border-radius: 4px;
        min-height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #FFF5F5;
        padding: 16px;
        text-align: center;
    }
    .bid-photo-box-label { font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: var(--bid-red); font-family: Arial, sans-serif; line-height: 1.6; }
    .bid-photo-box-sub { font-size: 9px; color: var(--bid-mid); font-style: italic; margin-top: 6px; }
    .bid-sig-area { height: 55px; border: 1px dashed var(--bid-border); border-radius: 4px; margin-bottom: 6px; background: #FAFAFA; }
    .bid-sig-line { border-bottom: 1.5px solid var(--bid-dark); margin-bottom: 4px; }
    .bid-sig-name { font-size: 12px; font-weight: 700; }
    .bid-sig-sub  { font-size: 10px; color: var(--bid-mid); font-style: italic; }
    .bid-alert-discrepancy {
        background: #FEF3C7;
        border: 1.5px solid #F59E0B;
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 11px;
        color: #78350F;
        line-height: 1.7;
        margin-top: 10px;
        font-family: Arial, sans-serif;
    }
    .bid-alert-discrepancy strong { text-transform: uppercase; letter-spacing: 0.5px; }
    .bid-legal {
        background: var(--bid-light);
        border-left: 4px solid var(--bid-dark);
        padding: 10px 14px;
        font-size: 10px;
        color: #4B5563;
        line-height: 1.7;
        margin: 14px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
    }
</style>

<div class="bid-wrap">

    {{-- ── DOCUMENT TITLE ── --}}
    <div class="bid-doc-title">
        <h1>Body Identification Record</h1>
        <h2>Fiche d'Identification du Corps</h2>
        <div class="bid-confidential">&#9632; Mortuary — Strictly Confidential / Morgue — Strictement Confidentiel &#9632;</div>
        <div class="bid-doc-meta">
            Tag No.: {{ $payload['body_tag_number'] ?? 'N/A' }}
            &bull; Admission No.: {{ $payload['mortuary_admission_number'] ?? 'N/A' }}
            &bull; Doc: {{ $document_number }}
            &bull; Issued: {{ $issued_at }}
        </div>
    </div>

    {{-- ── I. IDENTIFICATION STATUS & METHOD ── --}}
    <div class="bid-section">
        <div class="bid-section-title">I. Identification Status &amp; Method / Statut &amp; Méthode d'Identification</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-4">
                <div>
                    <div class="bid-label">Body Tag Number / Étiquette Corps</div>
                    <div class="bid-value">{{ $payload['body_tag_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Mortuary Admission No. / N° Admission Morgue</div>
                    <div class="bid-value">{{ $payload['mortuary_admission_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Date of Admission / Date d'Admission</div>
                    <div class="bid-value">{{ $payload['date_of_admission'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Identification Status / Statut</div>
                    <div style="margin-top:4px;">
                        @php $idStatus = strtolower($payload['identification_status'] ?? ''); @endphp
                        @if($idStatus === 'positive')
                            <span class="bid-badge bid-badge-positive">POSITIVE</span>
                        @elseif($idStatus === 'tentative')
                            <span class="bid-badge bid-badge-tentative">TENTATIVE</span>
                        @else
                            <span class="bid-badge bid-badge-unidentified">UNIDENTIFIED</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="margin-top:12px;">
                <div class="bid-label">Identification Method(s) / Méthode(s) d'Identification</div>
                <div style="margin-top:5px;">
                    @if(!empty($payload['identification_method']) && is_array($payload['identification_method']))
                        @foreach($payload['identification_method'] as $method)
                            <span class="bid-method-tag">{{ $method }}</span>
                        @endforeach
                    @else
                        <span class="bid-method-tag">{{ $payload['identification_method'] ?? '—' }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. PHOTOGRAPH PLACEHOLDER ── --}}
    <div class="bid-section">
        <div class="bid-section-title">II. Photograph / Photographie</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-2" style="align-items:start;">
                <div class="bid-photo-box">
                    <div class="bid-photo-box-label">Photograph of Deceased<br>— Attach Securely —<br>Photographie du Défunt<br>— Joindre Solidement —</div>
                    <div class="bid-photo-box-sub">Full face &amp; profile view required<br>Vue de face et de profil requises</div>
                </div>
                <div>
                    <div class="bid-grid bid-grid-2">
                        <div>
                            <div class="bid-label">Facility / Établissement</div>
                            <div class="bid-value">{{ $facility_name }}</div>
                        </div>
                        <div>
                            <div class="bid-label">Licence / License</div>
                            <div class="bid-value">{{ $facility_license }}</div>
                        </div>
                        <div>
                            <div class="bid-label">Patient Name / Nom</div>
                            <div class="bid-value">{{ $patient_name }}</div>
                        </div>
                        <div>
                            <div class="bid-label">Health ID</div>
                            <div class="bid-value">{{ $health_id }}</div>
                        </div>
                        <div>
                            <div class="bid-label">Sex / Sexe</div>
                            <div class="bid-value">{{ $patient_sex }}</div>
                        </div>
                        <div>
                            <div class="bid-label">Date of Birth / Date de Naissance</div>
                            <div class="bid-value">{{ $patient_dob }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. PRIMARY IDENTIFIER ── --}}
    <div class="bid-section">
        <div class="bid-section-title">III. Primary Identifier / Identificateur Principal</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-3">
                <div>
                    <div class="bid-label">Full Name / Nom Complet</div>
                    <div class="bid-value">{{ $payload['primary_identifier']['name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Relationship / Lien de Parenté</div>
                    <div class="bid-value">{{ $payload['primary_identifier']['relationship'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Contact / Téléphone</div>
                    <div class="bid-value">{{ $payload['primary_identifier']['contact'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">ID Document Type / Type de Pièce</div>
                    <div class="bid-value">{{ $payload['primary_identifier']['id_document_type'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="bid-label">ID Number / Numéro de Pièce</div>
                    <div class="bid-value">{{ $payload['primary_identifier']['id_number'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IV. SECONDARY IDENTIFIER ── --}}
    <div class="bid-section">
        <div class="bid-section-title">IV. Secondary Identifier / Identificateur Secondaire</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-3">
                <div>
                    <div class="bid-label">Full Name / Nom Complet</div>
                    <div class="bid-value">{{ $payload['secondary_identifier']['name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Relationship / Lien de Parenté</div>
                    <div class="bid-value">{{ $payload['secondary_identifier']['relationship'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Contact / Téléphone</div>
                    <div class="bid-value">{{ $payload['secondary_identifier']['contact'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">ID Document Type / Type de Pièce</div>
                    <div class="bid-value">{{ $payload['secondary_identifier']['id_document_type'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="bid-label">ID Number / Numéro de Pièce</div>
                    <div class="bid-value">{{ $payload['secondary_identifier']['id_number'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── V. PHYSICAL DESCRIPTION ── --}}
    <div class="bid-section">
        <div class="bid-section-title">V. Physical Description / Description Physique</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-4">
                <div>
                    <div class="bid-label">Estimated Age / Âge Estimé</div>
                    <div class="bid-value">{{ $payload['physical_description']['estimated_age'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Height / Taille (cm)</div>
                    <div class="bid-value">{{ $payload['physical_description']['height_cm'] ?? '—' }} cm</div>
                </div>
                <div>
                    <div class="bid-label">Weight / Poids (kg)</div>
                    <div class="bid-value">{{ $payload['physical_description']['weight_kg'] ?? '—' }} kg</div>
                </div>
                <div>
                    <div class="bid-label">Build / Corpulence</div>
                    <div class="bid-value">{{ $payload['physical_description']['build'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Skin Complexion / Teint</div>
                    <div class="bid-value">{{ $payload['physical_description']['skin_complexion'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Hair Colour &amp; Type / Couleur &amp; Type de Cheveux</div>
                    <div class="bid-value">{{ $payload['physical_description']['hair_colour_type'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Eye Colour / Couleur des Yeux</div>
                    <div class="bid-value">{{ $payload['physical_description']['eye_colour'] ?? '—' }}</div>
                </div>
            </div>
            <div style="margin-top:10px;">
                <div class="bid-label">Scars, Marks &amp; Tattoos / Cicatrices, Marques &amp; Tatouages</div>
                <div class="bid-value" style="min-height:32px;">{{ $payload['physical_description']['scars_marks_tattoos'] ?? '—' }}</div>
            </div>
            <div style="margin-top:10px;">
                <div class="bid-label">Clothing Description / Description des Vêtements</div>
                <div class="bid-value" style="min-height:32px;">{{ $payload['physical_description']['clothing_description'] ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ── VI. FORENSIC EVIDENCE ── --}}
    <div class="bid-section">
        <div class="bid-section-title">VI. Forensic Evidence / Preuves Médico-Légales</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-3">
                <div style="grid-column: span 3;">
                    <div class="bid-label">Dental Chart Notes / Notes de la Carte Dentaire</div>
                    <div class="bid-value" style="min-height:40px;">{{ $payload['dental_chart_notes'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Fingerprint Reference No. / Réf. Empreintes</div>
                    <div class="bid-value">{{ $payload['fingerprint_reference_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">DNA Sample Collected / Prélèvement ADN</div>
                    <div style="margin-top:4px;">
                        @if(strtolower($payload['dna_sample_collected'] ?? 'no') === 'yes')
                            <span class="bid-badge bid-badge-yes">YES / OUI</span>
                        @else
                            <span class="bid-badge bid-badge-no">NO / NON</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="bid-label">DNA Lab Reference / Réf. Laboratoire ADN</div>
                    <div class="bid-value">{{ $payload['dna_lab_reference'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VII. PERSONAL EFFECTS ── --}}
    <div class="bid-section">
        <div class="bid-section-title">VII. Personal Effects / Effets Personnels</div>
        <div class="bid-section-body">
            @if(!empty($payload['personal_effects']) && is_array($payload['personal_effects']))
                <table class="bid-table">
                    <thead>
                        <tr>
                            <th>Item / Article</th>
                            <th>Description</th>
                            <th>Qty / Qté</th>
                            <th>Condition / État</th>
                            <th>Storage Location / Lieu de Stockage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payload['personal_effects'] as $effect)
                            <tr>
                                <td>{{ $effect['item'] ?? '—' }}</td>
                                <td>{{ $effect['description'] ?? '—' }}</td>
                                <td>{{ $effect['quantity'] ?? '—' }}</td>
                                <td>{{ $effect['condition'] ?? '—' }}</td>
                                <td>{{ $effect['storage_location'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="font-size:11px; color:var(--bid-mid); font-style:italic;">No personal effects recorded / Aucun effet personnel enregistré</div>
            @endif
        </div>
    </div>

    {{-- ── VIII. POLICE REFERENCE ── --}}
    <div class="bid-section">
        <div class="bid-section-title">VIII. Police Reference / Référence Policière</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-3">
                <div>
                    <div class="bid-label">Police Reference Number / N° de Référence</div>
                    <div class="bid-value">{{ $payload['police_reference_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Investigating Officer / Officier Enquêteur</div>
                    <div class="bid-value">{{ $payload['investigating_officer'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Police Station / Commissariat</div>
                    <div class="bid-value">{{ $payload['police_station'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IX. IDENTIFICATION CONFIRMED ── --}}
    <div class="bid-section">
        <div class="bid-section-title">IX. Identification Confirmed By / Identification Confirmée Par</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-3">
                <div>
                    <div class="bid-label">Name / Nom</div>
                    <div class="bid-value">{{ $payload['identification_confirmed_by']['name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Role / Fonction</div>
                    <div class="bid-value">{{ $payload['identification_confirmed_by']['role'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="bid-label">Date of Confirmation / Date de Confirmation</div>
                    <div class="bid-value">{{ $payload['identification_confirmed_by']['date'] ?? '—' }}</div>
                </div>
            </div>

            @if(!empty($payload['discrepancies_noted']))
                <div class="bid-alert-discrepancy" style="margin-top:12px;">
                    <strong>&#9888; Discrepancies Noted / Divergences Constatées :</strong><br>
                    {{ $payload['discrepancies_noted'] }}
                </div>
            @endif
        </div>
    </div>

    {{-- ── X. CHAIN OF CUSTODY ── --}}
    <div class="bid-section">
        <div class="bid-section-title">X. Chain of Custody / Chaîne de Responsabilité</div>
        <div class="bid-section-body">
            @if(!empty($payload['chain_of_custody']) && is_array($payload['chain_of_custody']))
                <table class="bid-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Action / Acte</th>
                            <th>Performed By / Exécuté Par</th>
                            <th>Date &amp; Time / Date &amp; Heure</th>
                            <th>Signature Reference / Réf. Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payload['chain_of_custody'] as $i => $entry)
                            <tr>
                                <td style="font-weight:700; color:var(--bid-mid);">{{ $i + 1 }}</td>
                                <td>{{ $entry['action'] ?? '—' }}</td>
                                <td>{{ $entry['performed_by'] ?? '—' }}</td>
                                <td>{{ $entry['date_time'] ?? '—' }}</td>
                                <td>{{ $entry['signature_reference'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="font-size:11px; color:var(--bid-mid); font-style:italic;">No custody entries recorded / Aucune entrée de garde enregistrée</div>
            @endif
        </div>
    </div>

    {{-- ── XI. AUTHORIZATION & ISSUER ── --}}
    <div class="bid-section">
        <div class="bid-section-title">XI. Authorization &amp; Issuer / Autorisation &amp; Émetteur</div>
        <div class="bid-section-body">
            <div class="bid-grid bid-grid-2">
                <div>
                    <div class="bid-sig-area"></div>
                    <div class="bid-sig-line"></div>
                    <div class="bid-sig-name">{{ $issuer_name }}</div>
                    <div class="bid-sig-sub">{{ $issuer_role }}</div>
                    <div class="bid-sig-sub">{{ $facility_name }}</div>
                    <div class="bid-sig-sub">Date: {{ $issued_at }}</div>
                </div>
                <div>
                    <div class="bid-sig-area"></div>
                    <div class="bid-sig-line"></div>
                    <div class="bid-sig-name">{{ $payload['identification_confirmed_by']['name'] ?? '—' }}</div>
                    <div class="bid-sig-sub">{{ $payload['identification_confirmed_by']['role'] ?? 'Identification Officer / Officier d\'Identification' }}</div>
                    <div class="bid-sig-sub">{{ $facility_name }}</div>
                    <div class="bid-sig-sub">Date: {{ $payload['identification_confirmed_by']['date'] ?? $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VERIFICATION QR ── --}}
    @if(!empty($qr_svg))
        <div style="margin-top:14px; display:flex; align-items:flex-start; gap:16px;">
            <div style="flex-shrink:0;">{!! $qr_svg !!}</div>
            <div style="font-size:9.5px; color:var(--bid-mid); font-family:Arial, sans-serif; line-height:1.7;">
                <strong>Verification Code / Code de Vérification:</strong> {{ $verification_code }}<br>
                <strong>Document No.:</strong> {{ $document_number }}<br>
                Scan to verify authenticity at {{ $facility_name }}.
            </div>
        </div>
    @endif

    <div class="bid-legal">
        <strong>Confidentiality &amp; Legal Notice / Avis de Confidentialité &amp; Légal:</strong>
        This Body Identification Record is a strictly confidential medico-legal document of <strong>{{ $facility_name }}</strong>
        (Licence: {{ $facility_license }}). It is governed by applicable health legislation, mortuary regulations, and data
        protection laws including Cameroon Law No. 2010/012. Unauthorized disclosure, reproduction, or alteration is a criminal
        offence. This document must accompany all subsequent mortuary, judicial, and civil registration proceedings relating to
        Tag No. <strong>{{ $payload['body_tag_number'] ?? 'N/A' }}</strong>. Status: <strong>{{ strtoupper($status) }}</strong>.
        Language: {{ $language }}.
    </div>

</div>
@endsection
