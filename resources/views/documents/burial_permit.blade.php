@extends('documents.base')

@section('title', 'Burial Permit / Permis d\'Inhumation')
@section('subtitle', 'ISSUED UNDER CAMEROON VITAL STATISTICS REGULATIONS')

@section('content')
{{-- ============================================================
     BURIAL PERMIT / PERMIS D'INHUMATION
     Slug: burial-permit | Code: BRP | Color: #374151
     ============================================================ --}}

<style>
    :root {
        --brp-dark:   #374151;
        --brp-darker: #1F2937;
        --brp-mid:    #6B7280;
        --brp-light:  #F9FAFB;
        --brp-border: #D1D5DB;
        --brp-black:  #111827;
        --brp-red:    #991B1B;
        --brp-amber:  #D97706;
        --brp-green:  #065F46;
    }

    .brp-wrap { font-family: 'Times New Roman', serif; color: var(--brp-black); }

    /* ── Bilingual government header ── */
    .brp-gov-header {
        border: 2.5px solid var(--brp-dark);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0;
        text-align: center;
    }
    .brp-gov-top {
        background: var(--brp-darker);
        color: #fff;
        padding: 10px 20px;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 16px;
    }
    .brp-gov-left  { text-align: left; }
    .brp-gov-right { text-align: right; }
    .brp-gov-left p, .brp-gov-right p { margin: 1px 0; font-size: 10px; line-height: 1.4; }
    .brp-gov-left p strong, .brp-gov-right p strong { font-size: 11px; letter-spacing: 0.3px; }
    .brp-emblem {
        width: 68px; height: 68px;
        border: 2px solid rgba(255,255,255,0.4);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 28px; color: #F3F4F6;
        background: rgba(255,255,255,0.07);
    }
    .brp-gov-tagline {
        background: var(--brp-light);
        border-top: 1px solid var(--brp-border);
        padding: 4px;
        font-size: 9px;
        color: var(--brp-mid);
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    /* ── Document title ── */
    .brp-doc-title {
        text-align: center;
        margin: 18px 0 4px;
        padding: 12px 0;
        border-top: 2px solid var(--brp-dark);
        border-bottom: 2px solid var(--brp-dark);
    }
    .brp-doc-title h1 {
        font-size: 17px; font-weight: 900;
        letter-spacing: 1.5px; text-transform: uppercase;
        margin: 0; color: var(--brp-darker);
    }
    .brp-doc-title h2 {
        font-size: 13px; font-weight: 600;
        font-style: italic; margin: 3px 0 0;
        color: var(--brp-dark);
    }
    .brp-doc-meta { margin-top: 6px; font-size: 10px; color: var(--brp-mid); font-style: italic; }

    /* ── Section ── */
    .brp-section { margin: 14px 0 0; }
    .brp-section-title {
        background: var(--brp-dark);
        color: #fff;
        padding: 6px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 3px 3px 0 0;
        font-family: Arial, sans-serif;
    }
    .brp-section-body {
        border: 1.5px solid var(--brp-dark);
        border-top: none;
        padding: 12px 14px;
        border-radius: 0 0 3px 3px;
    }

    /* ── Info grid ── */
    .brp-grid { display: grid; gap: 10px 20px; }
    .brp-grid-2 { grid-template-columns: 1fr 1fr; }
    .brp-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .brp-label {
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--brp-mid);
        font-weight: 700;
        margin-bottom: 2px;
        font-family: Arial, sans-serif;
    }
    .brp-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--brp-black);
        border-bottom: 1px solid var(--brp-border);
        padding-bottom: 3px;
        min-height: 20px;
    }

    /* ── Badges ── */
    .brp-badge {
        display: inline-block;
        background: var(--brp-darker);
        color: #fff;
        padding: 5px 16px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .brp-badge-risk {
        display: inline-block;
        background: var(--brp-red);
        color: #fff;
        padding: 5px 16px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .brp-badge-safe {
        display: inline-block;
        background: var(--brp-green);
        color: #fff;
        padding: 5px 16px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* ── Chain of custody table ── */
    .brp-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .brp-table th {
        background: var(--brp-dark);
        color: #fff;
        padding: 7px 10px;
        text-align: left;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: Arial, sans-serif;
    }
    .brp-table td {
        padding: 8px 10px;
        border-bottom: 1px solid var(--brp-border);
        vertical-align: top;
    }
    .brp-table tr:nth-child(even) td { background: var(--brp-light); }

    /* ── Expiry warning bar ── */
    .brp-expiry-bar {
        background: #FEF3C7;
        border: 1.5px solid var(--brp-amber);
        border-radius: 4px;
        padding: 8px 14px;
        font-size: 11px;
        color: #92400E;
        margin: 10px 0;
    }
    .brp-expiry-bar strong { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* ── Special instructions box ── */
    .brp-instructions {
        background: #FEF2F2;
        border-left: 4px solid var(--brp-red);
        padding: 10px 14px;
        font-size: 11px;
        color: #7F1D1D;
        line-height: 1.7;
        margin: 10px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
    }

    /* ── Sig grid ── */
    .brp-sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 14px; }
    .brp-sig-area {
        height: 60px;
        border: 1px dashed var(--brp-border);
        border-radius: 4px;
        margin-bottom: 6px;
        background: #FAFAFA;
    }
    .brp-sig-line { border-bottom: 1.5px solid var(--brp-dark); margin-bottom: 4px; }
    .brp-sig-name { font-size: 12px; font-weight: 700; color: var(--brp-black); }
    .brp-sig-sub  { font-size: 10px; color: var(--brp-mid); font-style: italic; }
    .brp-sig-stamp {
        width: 80px; height: 80px;
        border: 1.5px dashed #9CA3AF;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 8px; color: #9CA3AF;
        text-align: center; float: right;
    }

    /* ── Legal box ── */
    .brp-legal {
        background: var(--brp-light);
        border-left: 4px solid var(--brp-dark);
        padding: 11px 14px;
        font-size: 10px;
        color: #4B5563;
        line-height: 1.7;
        margin: 14px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
    }

    /* ── Verification strip ── */
    .brp-verify-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        margin-top: 14px;
        border-top: 1px dashed var(--brp-border);
        font-size: 9px;
        color: var(--brp-mid);
        font-family: Arial, sans-serif;
    }
</style>

<div class="brp-wrap">

    {{-- ── 1. BILINGUAL GOVERNMENT HEADER ── --}}
    <div class="brp-gov-header">
        <div class="brp-gov-top">
            <div class="brp-gov-left">
                <p><strong>REPUBLIC OF CAMEROON</strong></p>
                <p>Peace – Work – Fatherland</p>
                <p style="margin-top:6px;"><strong>MINISTRY OF PUBLIC HEALTH</strong></p>
                <p>{{ $facility_name }}</p>
                <p>License: {{ $facility_license }}</p>
            </div>
            <div class="brp-emblem" aria-label="National Emblem of Cameroon">&#x1F981;</div>
            <div class="brp-gov-right">
                <p><strong>RÉPUBLIQUE DU CAMEROUN</strong></p>
                <p>Paix – Travail – Patrie</p>
                <p style="margin-top:6px;"><strong>MINISTÈRE DE LA SANTÉ PUBLIQUE</strong></p>
                <p>{{ $facility_name }}</p>
                <p>Réf. Doc.: {{ $document_number }}</p>
            </div>
        </div>
        <div class="brp-gov-tagline">Official Vital Statistics Document · Document Officiel d'État Civil · BRP</div>
    </div>

    {{-- ── 2. DOCUMENT TITLE ── --}}
    <div class="brp-doc-title">
        <h1>Burial Permit</h1>
        <h2>Permis d'Inhumation</h2>
        <div class="brp-doc-meta">
            Document No.: {{ $document_number }} &bull;
            Issued / Délivré le: {{ $issued_at }} &bull;
            Issued Under Cameroon Vital Statistics Regulations
        </div>
    </div>

    {{-- ── 3. DECEASED INFORMATION ── --}}
    <div class="brp-section">
        <div class="brp-section-title">I. Deceased Information / Informations sur le Défunt</div>
        <div class="brp-section-body">
            <div class="brp-grid brp-grid-3">
                <div>
                    <div class="brp-label">Full Name of Deceased / Nom Complet du Défunt</div>
                    <div class="brp-value">{{ $payload['deceased_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Date of Death / Date du Décès</div>
                    <div class="brp-value">{{ $payload['date_of_death'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Place of Death / Lieu du Décès</div>
                    <div class="brp-value">{{ $payload['place_of_death'] ?? '—' }}</div>
                </div>
            </div>
            <div class="brp-grid brp-grid-3" style="margin-top:10px;">
                <div>
                    <div class="brp-label">Patient Name on Record / Nom du Patient</div>
                    <div class="brp-value">{{ $patient_name }}</div>
                </div>
                <div>
                    <div class="brp-label">Health ID / Identifiant de Santé</div>
                    <div class="brp-value">{{ $health_id }}</div>
                </div>
                <div>
                    <div class="brp-label">Date of Birth / Date de Naissance</div>
                    <div class="brp-value">{{ $patient_dob }}</div>
                </div>
                <div>
                    <div class="brp-label">Sex / Sexe</div>
                    <div class="brp-value">{{ $patient_sex }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 4. MEDICAL CAUSE ── --}}
    <div class="brp-section">
        <div class="brp-section-title">II. Medical Cause &amp; Manner of Death / Cause Médicale &amp; Mode de Décès</div>
        <div class="brp-section-body">
            <div class="brp-grid brp-grid-2">
                <div>
                    <div class="brp-label">Primary Cause of Death / Cause Principale du Décès</div>
                    <div class="brp-value">{{ $payload['cause_of_death'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Manner of Death / Mode de Décès</div>
                    <div style="margin-top:5px;">
                        <span class="brp-badge">{{ $payload['manner_of_death'] ?? '—' }}</span>
                    </div>
                </div>
                <div>
                    <div class="brp-label">Death Certificate No. / No. Acte de Décès</div>
                    <div class="brp-value">{{ $payload['death_certificate_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Death Certificate Date / Date du Certificat</div>
                    <div class="brp-value">{{ $payload['death_certificate_date'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 5. NEXT OF KIN ── --}}
    <div class="brp-section">
        <div class="brp-section-title">III. Next of Kin / Proche Parent</div>
        <div class="brp-section-body">
            <div class="brp-grid brp-grid-3">
                <div>
                    <div class="brp-label">Name / Nom</div>
                    <div class="brp-value">{{ $payload['next_of_kin_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Relationship / Lien de Parenté</div>
                    <div class="brp-value">{{ $payload['next_of_kin_relationship'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Contact / Téléphone</div>
                    <div class="brp-value">{{ $payload['next_of_kin_contact'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 6. BURIAL DETAILS ── --}}
    <div class="brp-section">
        <div class="brp-section-title">IV. Burial Details / Détails de l'Inhumation</div>
        <div class="brp-section-body">
            <div class="brp-grid brp-grid-3">
                <div style="grid-column: span 2;">
                    <div class="brp-label">Burial Location — Cemetery &amp; Town / Lieu d'Inhumation — Cimetière &amp; Ville</div>
                    <div class="brp-value">{{ $payload['burial_location'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brp-label">Burial Type / Type d'Inhumation</div>
                    <div style="margin-top:5px;">
                        <span class="brp-badge">{{ $payload['burial_type'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 7. PATHOLOGICAL RISK ── --}}
    <div class="brp-section">
        <div class="brp-section-title">V. Pathological Risk &amp; Special Instructions / Risque Pathologique &amp; Instructions Spéciales</div>
        <div class="brp-section-body">
            <div class="brp-grid brp-grid-2" style="margin-bottom:10px;">
                <div>
                    <div class="brp-label">Pathological Risk / Risque Pathologique</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['pathological_risk']))
                            <span class="brp-badge-risk">&#9888; HIGH RISK — RISQUE ÉLEVÉ</span>
                        @else
                            <span class="brp-badge-safe">No Pathological Risk / Aucun Risque Pathologique</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="brp-label">Civil Status Officer / Officier d'État Civil</div>
                    <div class="brp-value">{{ $payload['civil_status_officer'] ?? '—' }}</div>
                </div>
            </div>
            @if(!empty($payload['special_instructions']))
            <div class="brp-instructions">
                <strong>Special Instructions / Instructions Spéciales:</strong><br>
                {{ $payload['special_instructions'] }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── 8. PERMIT VALIDITY ── --}}
    <div class="brp-expiry-bar">
        <strong>Permit Validity / Validité du Permis:</strong>&nbsp;
        This permit is valid for <strong>14 days</strong> from the date of issue and expires on
        <strong>{{ $payload['permit_expiry_date'] ?? '—' }}</strong>.
        Ce permis est valable <strong>14 jours</strong> à compter de la date de délivrance et expire le
        <strong>{{ $payload['permit_expiry_date'] ?? '—' }}</strong>.
    </div>

    {{-- ── 9. CHAIN OF CUSTODY ── --}}
    <div class="brp-section">
        <div class="brp-section-title">VI. Chain of Custody / Chaîne de Garde</div>
        <div class="brp-section-body">
            <table class="brp-table">
                <thead>
                    <tr>
                        <th>Step / Étape</th>
                        <th>Location / Lieu</th>
                        <th>Responsible Party / Responsable</th>
                        <th>Date / Time / Date Heure</th>
                        <th>Status / Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>1 — Hospital / Hôpital</strong></td>
                        <td>{{ $facility_name }}</td>
                        <td>{{ $issuer_name }} ({{ $issuer_role }})</td>
                        <td>{{ $payload['date_of_death'] ?? '—' }}</td>
                        <td><span style="color:var(--brp-green);font-weight:700;">&#10003; Certified / Certifié</span></td>
                    </tr>
                    <tr>
                        <td><strong>2 — Mortuary / Morgue</strong></td>
                        <td>{{ $facility_name }} — Mortuary</td>
                        <td>{{ $payload['civil_status_officer'] ?? '—' }}</td>
                        <td>{{ $issued_at }}</td>
                        <td><span style="color:var(--brp-green);font-weight:700;">&#10003; Received / Reçu</span></td>
                    </tr>
                    <tr>
                        <td><strong>3 — Burial / Inhumation</strong></td>
                        <td>{{ $payload['burial_location'] ?? '—' }}</td>
                        <td>{{ $payload['next_of_kin_name'] ?? '—' }}</td>
                        <td>—</td>
                        <td><span style="color:var(--brp-amber);font-weight:700;">&#9679; Pending / En Attente</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 10. CERTIFICATION ── --}}
    <div class="brp-section">
        <div class="brp-section-title">VII. Certification / Certification Officielle</div>
        <div class="brp-section-body">
            <div style="font-size:11px;color:#374151;line-height:1.7;margin-bottom:14px;">
                I, the undersigned, hereby authorize the burial of the above-named deceased and certify that all
                legal requirements under Cameroon vital statistics legislation have been fulfilled.
                <br>
                <em>Je soussigné, autorise par la présente l'inhumation du défunt susmentionné et certifie que toutes
                les exigences légales en vertu de la législation camerounaise sur l'état civil ont été satisfaites.</em>
            </div>
            <div class="brp-sig-grid">
                <div>
                    <div class="brp-sig-stamp">Official<br>Stamp<br>Cachet</div>
                    <div class="brp-sig-area"></div>
                    <div class="brp-sig-line"></div>
                    <div class="brp-sig-name">{{ $issuer_name }}</div>
                    <div class="brp-sig-sub">{{ $issuer_role }}</div>
                    <div class="brp-sig-sub">{{ $facility_name }}</div>
                    <div class="brp-sig-sub">Date: {{ $issued_at }}</div>
                </div>
                <div>
                    <div class="brp-sig-area"></div>
                    <div class="brp-sig-line"></div>
                    <div class="brp-sig-name">{{ $payload['civil_status_officer'] ?? '—' }}</div>
                    <div class="brp-sig-sub">Civil Status Officer / Officier d'État Civil</div>
                    <div class="brp-sig-sub">{{ $facility_name }}</div>
                    <div class="brp-sig-sub">Date: {{ $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── LEGAL NOTICE ── --}}
    <div class="brp-legal">
        <strong>Legal Notice / Avis Légal:</strong>
        This burial permit is issued pursuant to <strong>Cameroon Ordinance No. 81/02 of June 29, 1981</strong>
        on civil status and applicable public health statutes. It is valid only for the interment of the
        named deceased at the designated location, and must be surrendered to the cemetery authority
        upon burial. Any fraudulent use, alteration, or reproduction of this document constitutes a criminal
        offence. Burial must be completed within the validity period stated above.
        <br><br>
        <em>Ce permis d'inhumation est délivré conformément à l'Ordonnance camerounaise n° 81/02 du 29 juin 1981
        sur l'état civil. Il est valable uniquement pour l'inhumation de la personne désignée au lieu indiqué
        et doit être remis à l'autorité du cimetière lors de l'inhumation.</em>
    </div>

    {{-- ── VERIFICATION STRIP ── --}}
    <div class="brp-verify-strip">
        <div>{!! $qr_svg !!}</div>
        <div style="text-align:right;">
            <div style="font-weight:700;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#374151;">Verification Code / Code de Vérification</div>
            <div style="font-family:monospace;font-size:13px;font-weight:800;letter-spacing:2px;color:#1F2937;">{{ $verification_code }}</div>
            <div style="margin-top:2px;">Document No.: {{ $document_number }} &bull; {{ $issued_at }}</div>
            <div>{{ $facility_name }} &bull; License: {{ $facility_license }}</div>
            <div style="margin-top:2px;color:var(--brp-red);font-weight:700;">
                EXPIRES: {{ $payload['permit_expiry_date'] ?? '—' }}
            </div>
        </div>
    </div>

</div>
@endsection
