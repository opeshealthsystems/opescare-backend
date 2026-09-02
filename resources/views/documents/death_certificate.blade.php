@extends('documents.base')

@section('content')
{{-- ============================================================
     MEDICAL CERTIFICATE OF DEATH / CERTIFICAT MÉDICAL DE DÉCÈS
     Slug: death-certificate | Code: DTH | Color: #374151
     ============================================================ --}}

<style>
    :root {
        --dth-dark:   #374151;
        --dth-darker: #1F2937;
        --dth-mid:    #6B7280;
        --dth-light:  #F3F4F6;
        --dth-border: #D1D5DB;
        --dth-black:  #111827;
        --dth-red:    #DC2626;
    }

    .dth-wrap { font-family: 'Times New Roman', serif; color: var(--dth-black); }

    /* ── 1. Bilingual government header ── */
    .dth-gov-header {
        border: 2.5px solid var(--dth-dark);
        border-radius: 4px;
        padding: 0;
        margin-bottom: 0;
        text-align: center;
        overflow: hidden;
    }
    .dth-gov-top {
        background: var(--dth-darker);
        color: #fff;
        padding: 10px 20px;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 16px;
    }
    .dth-gov-left  { text-align: left; }
    .dth-gov-right { text-align: right; }
    .dth-gov-left p, .dth-gov-right p {
        margin: 1px 0;
        font-size: 10px;
        line-height: 1.4;
    }
    .dth-gov-left p strong, .dth-gov-right p strong {
        font-size: 11px;
        letter-spacing: 0.3px;
    }
    .dth-emblem {
        width: 68px;
        height: 68px;
        border: 2px solid rgba(255,255,255,0.4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 28px;
        color: #F3F4F6;
        background: rgba(255,255,255,0.07);
    }
    .dth-gov-tagline {
        background: #F9FAFB;
        border-top: 1px solid var(--dth-border);
        padding: 4px;
        font-size: 9px;
        color: var(--dth-mid);
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    /* ── Document title ── */
    .dth-doc-title {
        text-align: center;
        margin: 18px 0 4px;
        padding: 12px 0;
        border-top: 2px solid var(--dth-dark);
        border-bottom: 2px solid var(--dth-dark);
    }
    .dth-doc-title h1 {
        font-size: 17px;
        font-weight: 900;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin: 0;
        color: var(--dth-darker);
    }
    .dth-doc-title h2 {
        font-size: 13px;
        font-weight: 600;
        font-style: italic;
        margin: 3px 0 0;
        color: var(--dth-dark);
    }
    .dth-doc-meta {
        margin-top: 6px;
        font-size: 10px;
        color: var(--dth-mid);
        font-style: italic;
    }

    /* ── Section labels ── */
    .dth-section {
        margin: 14px 0 0;
    }
    .dth-section-title {
        background: var(--dth-dark);
        color: #fff;
        padding: 6px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 3px 3px 0 0;
    }
    .dth-section-body {
        border: 1.5px solid var(--dth-dark);
        border-top: none;
        padding: 12px 14px;
        border-radius: 0 0 3px 3px;
    }

    /* ── Info grid ── */
    .dth-grid {
        display: grid;
        gap: 10px 20px;
    }
    .dth-grid-2 { grid-template-columns: 1fr 1fr; }
    .dth-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .dth-field-label {
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--dth-mid);
        font-weight: 700;
        margin-bottom: 2px;
        font-family: Arial, sans-serif;
    }
    .dth-field-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--dth-black);
        border-bottom: 1px solid var(--dth-border);
        padding-bottom: 3px;
        min-height: 20px;
    }

    /* ── Cause of death ── */
    .dth-cod-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11.5px;
    }
    .dth-cod-table tr { border-bottom: 1px solid #E5E7EB; }
    .dth-cod-table td { padding: 8px 10px; vertical-align: top; }
    .dth-cod-line-letter {
        width: 28px;
        font-weight: 900;
        font-size: 14px;
        color: var(--dth-dark);
        vertical-align: middle;
    }
    .dth-cod-desc {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--dth-mid);
        margin-bottom: 2px;
        font-family: Arial, sans-serif;
    }
    .dth-cod-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--dth-black);
        border-bottom: 1.5px solid var(--dth-dark);
        min-height: 18px;
        padding-bottom: 2px;
    }
    .dth-cod-due-arrow {
        font-size: 10px;
        color: var(--dth-mid);
        font-style: italic;
        padding-top: 8px;
        vertical-align: top;
        white-space: nowrap;
    }
    .dth-cod-empty {
        font-size: 11px;
        color: #D1D5DB;
        font-style: italic;
    }
    .dth-part2-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--dth-mid);
        margin: 10px 0 6px;
        font-family: Arial, sans-serif;
    }
    .dth-contrib-list {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .dth-contrib-item {
        background: var(--dth-light);
        border: 1px solid var(--dth-border);
        border-radius: 3px;
        padding: 3px 9px;
        font-size: 11px;
        color: var(--dth-black);
    }

    /* ── Manner of death + Autopsy ── */
    .dth-manner-badge {
        display: inline-block;
        background: var(--dth-darker);
        color: #fff;
        padding: 5px 16px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }
    .dth-bool-yes {
        display: inline-block;
        background: #065F46;
        color: #fff;
        border-radius: 3px;
        padding: 2px 9px;
        font-size: 11px;
        font-weight: 700;
    }
    .dth-bool-no {
        display: inline-block;
        background: #991B1B;
        color: #fff;
        border-radius: 3px;
        padding: 2px 9px;
        font-size: 11px;
        font-weight: 700;
    }

    /* ── Dual signature block ── */
    .dth-sig-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 14px;
    }
    .dth-sig-block { }
    .dth-sig-area-box {
        height: 60px;
        border: 1px dashed var(--dth-border);
        border-radius: 4px;
        margin-bottom: 6px;
        background: #FAFAFA;
    }
    .dth-sig-line-under {
        border-bottom: 1.5px solid var(--dth-dark);
        margin-bottom: 4px;
    }
    .dth-sig-name   { font-size: 12px; font-weight: 700; color: var(--dth-black); }
    .dth-sig-sub    { font-size: 10px; color: var(--dth-mid); font-style: italic; }
    .dth-sig-stamp  {
        width: 80px; height: 80px;
        border: 1.5px dashed #9CA3AF;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: #9CA3AF;
        text-align: center;
        float: right;
    }

    /* ── Legal box ── */
    .dth-legal {
        background: var(--dth-light);
        border-left: 4px solid var(--dth-dark);
        padding: 11px 14px;
        font-size: 10px;
        color: #4B5563;
        line-height: 1.7;
        margin: 14px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
    }

    /* ── Notification for ── */
    .dth-notification-bar {
        background: #FEF3C7;
        border: 1.5px solid #D97706;
        border-radius: 4px;
        padding: 8px 14px;
        font-size: 11px;
        color: #92400E;
        margin: 10px 0;
    }
    .dth-notification-bar strong { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* ── Verification strip ── */
    .dth-verify-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        margin-top: 14px;
        border-top: 1px dashed var(--dth-border);
        font-size: 9px;
        color: var(--dth-mid);
        font-family: Arial, sans-serif;
    }
</style>

<div class="dth-wrap">

    {{-- ── 1. BILINGUAL GOVERNMENT HEADER ── --}}
    <div class="dth-gov-header">
        <div class="dth-gov-top">
            <div class="dth-gov-left">
                <p><strong>REPUBLIC OF CAMEROON</strong></p>
                <p>Peace – Work – Fatherland</p>
                <p style="margin-top:6px;"><strong>MINISTRY OF PUBLIC HEALTH</strong></p>
                <p>{{ $facility_name }}</p>
                <p>License: {{ $facility_license }}</p>
            </div>
            <div class="dth-emblem" aria-label="National Emblem of Cameroon">&#x1F981;</div>
            <div class="dth-gov-right">
                <p><strong>RÉPUBLIQUE DU CAMEROUN</strong></p>
                <p>Paix – Travail – Patrie</p>
                <p style="margin-top:6px;"><strong>MINISTÈRE DE LA SANTÉ PUBLIQUE</strong></p>
                <p>{{ $facility_name }}</p>
                <p>Réf. Doc.: {{ $document_number }}</p>
            </div>
        </div>
        <div class="dth-gov-tagline">Official Medical Documentation · Document Médical Officiel · DTH</div>
    </div>

    {{-- ── 2. DOCUMENT TITLE ── --}}
    <div class="dth-doc-title">
        <h1>Medical Certificate of Death</h1>
        <h2>Certificat Médical de Décès</h2>
        <div class="dth-doc-meta">Document No.: {{ $document_number }} &bull; Issued: {{ $issued_at }}</div>
    </div>

    {{-- ── 3. DECEASED INFORMATION ── --}}
    <div class="dth-section">
        <div class="dth-section-title">I. Deceased Information / Informations sur le Défunt</div>
        <div class="dth-section-body">
            <div class="dth-grid dth-grid-3">
                <div>
                    <div class="dth-field-label">Full Name / Nom Complet</div>
                    <div class="dth-field-value">{{ $payload['deceased_name'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">Date of Birth / Date de Naissance</div>
                    <div class="dth-field-value">{{ $payload['deceased_dob'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">Sex / Sexe</div>
                    <div class="dth-field-value">{{ $payload['deceased_sex'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">Nationality / Nationalité</div>
                    <div class="dth-field-value">{{ $payload['deceased_nationality'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">National ID / Carte Nationale d'Identité</div>
                    <div class="dth-field-value">{{ $payload['deceased_id_number'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">Next of Kin / Proche Parent</div>
                    <div class="dth-field-value">{{ $payload['next_of_kin'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 4. DEATH DETAILS ── --}}
    <div class="dth-section">
        <div class="dth-section-title">II. Details of Death / Détails du Décès</div>
        <div class="dth-section-body">
            <div class="dth-grid dth-grid-3">
                <div>
                    <div class="dth-field-label">Date of Death / Date du Décès</div>
                    <div class="dth-field-value">{{ $payload['date_of_death'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">Time of Death / Heure du Décès</div>
                    <div class="dth-field-value">{{ $payload['time_of_death'] }}</div>
                </div>
                <div>
                    <div class="dth-field-label">Place of Death / Lieu du Décès</div>
                    <div class="dth-field-value">{{ $payload['place_of_death'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 5. CAUSE OF DEATH (WHO ICD-10 FORMAT) ── --}}
    <div class="dth-section">
        <div class="dth-section-title">III. Cause of Death / Cause du Décès (ICD-10 / WHO Format)</div>
        <div class="dth-section-body">
            <div style="font-size:10px;color:var(--dth-mid);font-style:italic;margin-bottom:10px;font-family:Arial,sans-serif;">
                Part I: Disease or condition directly leading to death. State the chain of events — diseases or conditions — starting with the final disease or condition resulting in death. Do not enter mode of dying.
            </div>
            <table class="dth-cod-table">
                <tbody>
                    <tr>
                        <td class="dth-cod-line-letter">A</td>
                        <td>
                            <div class="dth-cod-desc">Immediate Cause (Direct cause of death) / Cause Immédiate</div>
                            <div class="dth-cod-value">{{ $payload['cause_of_death_a'] }}</div>
                        </td>
                    </tr>
                    @if(!empty($payload['cause_of_death_b']))
                    <tr>
                        <td class="dth-cod-line-letter">B</td>
                        <td>
                            <div class="dth-cod-desc">Due to (or as a consequence of) / Dû à</div>
                            <div class="dth-cod-value">{{ $payload['cause_of_death_b'] }}</div>
                        </td>
                    </tr>
                    @endif
                    @if(!empty($payload['cause_of_death_c']))
                    <tr>
                        <td class="dth-cod-line-letter">C</td>
                        <td>
                            <div class="dth-cod-desc">Due to (or as a consequence of) / Dû à</div>
                            <div class="dth-cod-value">{{ $payload['cause_of_death_c'] }}</div>
                        </td>
                    </tr>
                    @endif
                    @if(!empty($payload['cause_of_death_d']))
                    <tr>
                        <td class="dth-cod-line-letter">D</td>
                        <td>
                            <div class="dth-cod-desc">Underlying Cause / Cause Sous-jacente</div>
                            <div class="dth-cod-value">{{ $payload['cause_of_death_d'] }}</div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>

            @if(!empty($payload['contributing_conditions']))
            <div class="dth-part2-label">Part II: Other significant conditions contributing to death but not related to the disease/condition causing it</div>
            <ul class="dth-contrib-list">
                @foreach($payload['contributing_conditions'] as $condition)
                <li class="dth-contrib-item">{{ $condition }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- ── 6. MANNER OF DEATH + AUTOPSY ── --}}
    <div class="dth-section">
        <div class="dth-section-title">IV. Manner of Death &amp; Autopsy / Mode de Décès &amp; Autopsie</div>
        <div class="dth-section-body">
            <div class="dth-grid dth-grid-2">
                <div>
                    <div class="dth-field-label">Manner of Death / Mode de Décès</div>
                    <div style="margin-top:5px;">
                        <span class="dth-manner-badge">{{ $payload['manner_of_death'] }}</span>
                    </div>
                </div>
                <div>
                    <div class="dth-field-label">Autopsy Performed / Autopsie Réalisée</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['autopsy_performed']))
                            <span class="dth-bool-yes">Yes / Oui</span>
                        @else
                            <span class="dth-bool-no">No / Non</span>
                        @endif
                    </div>
                    @if(!empty($payload['autopsy_findings']))
                    <div style="margin-top:8px;font-size:11px;color:#374151;font-style:italic;">
                        <strong>Findings / Résultats:</strong> {{ $payload['autopsy_findings'] }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── NOTIFICATION FOR ── --}}
    <div class="dth-notification-bar">
        <strong>Notification For / Destinataire:</strong>&nbsp;{{ $payload['notification_for'] }}
    </div>

    {{-- ── 7. CERTIFYING PHYSICIAN INFO ── --}}
    <div class="dth-section">
        <div class="dth-section-title">V. Medical Certification / Certification Médicale</div>
        <div class="dth-section-body">
            <div style="font-size:11px;color:#374151;line-height:1.7;margin-bottom:12px;">
                I, the undersigned, certify that the information given above is correct to the best of my knowledge,
                and that the above-named person died at the time and place stated, from the causes indicated.
                <br>
                <em>Je soussigné, certifie que les informations ci-dessus sont exactes à ma connaissance,
                et que la personne sus-nommée est décédée à l'heure et au lieu indiqués, des causes mentionnées.</em>
            </div>

            <div class="dth-sig-grid">
                <div class="dth-sig-block">
                    <div class="dth-sig-stamp">Official<br>Stamp<br>Cachet</div>
                    <div class="dth-sig-area-box"></div>
                    <div class="dth-sig-line-under"></div>
                    <div class="dth-sig-name">{{ $payload['certifying_physician'] }}</div>
                    <div class="dth-sig-sub">Certifying Physician / Médecin Certificateur</div>
                    <div class="dth-sig-sub">Reg. No.: {{ $payload['physician_reg_number'] }}</div>
                    <div class="dth-sig-sub">{{ $facility_name }}</div>
                    <div class="dth-sig-sub">Date: {{ $issued_at }}</div>
                </div>
                <div class="dth-sig-block">
                    <div class="dth-sig-area-box"></div>
                    <div class="dth-sig-line-under"></div>
                    <div class="dth-sig-name">{{ $issuer_name }}</div>
                    <div class="dth-sig-sub">{{ $issuer_role }}</div>
                    <div class="dth-sig-sub">Hospital Director / CMO — {{ $facility_name }}</div>
                    <div class="dth-sig-sub">Date: {{ $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 8. LEGAL NOTICE ── --}}
    <div class="dth-legal">
        <strong>Legal Notice / Avis Légal:</strong> This certificate is issued for the purpose of civil registration proceedings only and has legal validity under <strong>Cameroon Ordinance No. 81/02</strong> and applicable public health statutes. Any fraudulent use, alteration, or reproduction of this document is a criminal offence. This certificate must be presented to the relevant civil registration authority (Mairie) within the legally prescribed period. The contents of this certificate are confidential and may only be disclosed in accordance with applicable law.
    </div>

    {{-- ── VERIFICATION STRIP ── --}}
    <div class="dth-verify-strip">
        <div>{!! $qr_svg !!}</div>
        <div style="text-align:right;">
            <div style="font-weight:700;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#374151;">Verification Code</div>
            <div style="font-family:monospace;font-size:13px;font-weight:800;letter-spacing:2px;color:#1F2937;">{{ $verification_code }}</div>
            <div style="margin-top:2px;">Document No.: {{ $document_number }} &bull; {{ $issued_at }}</div>
            <div>{{ $facility_name }} &bull; License: {{ $facility_license }}</div>
        </div>
    </div>

</div>
@endsection
