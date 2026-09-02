@extends('documents.base')

@section('title', 'Mortuary Cold Storage Log / Registre de Stockage à la Morgue')
@section('subtitle', 'INTERNAL RECORD — NOT FOR EXTERNAL DISTRIBUTION')

@section('content')
{{-- ============================================================
     MORTUARY COLD STORAGE LOG / REGISTRE DE STOCKAGE À LA MORGUE
     Slug: mortuary-storage-log | Code: MSL | Color: #1a3c5e
     ============================================================ --}}

<style>
    :root {
        --msl-dark:   #1a3c5e;
        --msl-darker: #0f2338;
        --msl-mid:    #4B5563;
        --msl-light:  #F8FAFC;
        --msl-border: #D1D5DB;
        --msl-black:  #111827;
        --msl-red:    #991B1B;
        --msl-amber:  #D97706;
        --msl-green:  #065F46;
        --msl-cold:   #1D4ED8;
    }

    .msl-wrap { font-family: 'Times New Roman', serif; color: var(--msl-black); }

    /* ── Internal banner ── */
    .msl-internal-banner {
        background: #FEF3C7;
        border: 1.5px solid var(--msl-amber);
        border-radius: 4px;
        padding: 7px 14px;
        font-size: 10px;
        font-weight: 700;
        color: #92400E;
        text-align: center;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 14px;
        font-family: Arial, sans-serif;
    }

    /* ── Document title ── */
    .msl-doc-title {
        text-align: center;
        margin: 4px 0 4px;
        padding: 12px 0;
        border-top: 2px solid var(--msl-dark);
        border-bottom: 2px solid var(--msl-dark);
    }
    .msl-doc-title h1 {
        font-size: 17px; font-weight: 900;
        letter-spacing: 1.5px; text-transform: uppercase;
        margin: 0; color: var(--msl-darker);
    }
    .msl-doc-title h2 {
        font-size: 13px; font-weight: 600;
        font-style: italic; margin: 3px 0 0;
        color: var(--msl-mid);
    }
    .msl-doc-meta { margin-top: 6px; font-size: 10px; color: var(--msl-mid); font-style: italic; }

    /* ── Section ── */
    .msl-section { margin: 14px 0 0; }
    .msl-section-title {
        background: var(--msl-dark);
        color: #fff;
        padding: 6px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 3px 3px 0 0;
        font-family: Arial, sans-serif;
    }
    .msl-section-body {
        border: 1.5px solid var(--msl-dark);
        border-top: none;
        padding: 12px 14px;
        border-radius: 0 0 3px 3px;
    }

    /* ── Info grid ── */
    .msl-grid { display: grid; gap: 10px 20px; }
    .msl-grid-2 { grid-template-columns: 1fr 1fr; }
    .msl-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .msl-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .msl-label {
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--msl-mid);
        font-weight: 700;
        margin-bottom: 2px;
        font-family: Arial, sans-serif;
    }
    .msl-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--msl-black);
        border-bottom: 1px solid var(--msl-border);
        padding-bottom: 3px;
        min-height: 20px;
    }

    /* ── Badges ── */
    .msl-badge {
        display: inline-block;
        background: var(--msl-darker);
        color: #fff;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .msl-badge-cold {
        display: inline-block;
        background: var(--msl-cold);
        color: #fff;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* ── Condition badge colours ── */
    .msl-condition-good       { color: var(--msl-green); font-weight: 700; }
    .msl-condition-fair       { color: var(--msl-amber); font-weight: 700; }
    .msl-condition-decomposed { color: var(--msl-red);   font-weight: 700; }
    .msl-condition-mutilated  { color: var(--msl-red);   font-weight: 700; }

    /* ── Tables ── */
    .msl-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    .msl-table th {
        background: var(--msl-dark);
        color: #fff;
        padding: 7px 8px;
        text-align: left;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: Arial, sans-serif;
        white-space: nowrap;
    }
    .msl-table td {
        padding: 7px 8px;
        border-bottom: 1px solid var(--msl-border);
        vertical-align: top;
        font-size: 10.5px;
    }
    .msl-table tr:nth-child(even) td { background: var(--msl-light); }
    .msl-table-empty {
        text-align: center;
        font-style: italic;
        color: var(--msl-mid);
        font-size: 11px;
        padding: 14px !important;
    }

    /* ── Sig block ── */
    .msl-sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 14px; }
    .msl-sig-area {
        height: 55px;
        border: 1px dashed var(--msl-border);
        border-radius: 4px;
        margin-bottom: 6px;
        background: #FAFAFA;
    }
    .msl-sig-line { border-bottom: 1.5px solid var(--msl-dark); margin-bottom: 4px; }
    .msl-sig-name { font-size: 12px; font-weight: 700; color: var(--msl-black); }
    .msl-sig-sub  { font-size: 10px; color: var(--msl-mid); font-style: italic; }

    /* ── Remarks box ── */
    .msl-remarks {
        background: var(--msl-light);
        border-left: 4px solid var(--msl-dark);
        padding: 10px 14px;
        font-size: 11px;
        color: #374151;
        line-height: 1.7;
        margin: 10px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
        min-height: 40px;
    }

    /* ── Legal box ── */
    .msl-legal {
        background: var(--msl-light);
        border-left: 4px solid var(--msl-dark);
        padding: 11px 14px;
        font-size: 10px;
        color: #4B5563;
        line-height: 1.7;
        margin: 14px 0;
        border-radius: 0 4px 4px 0;
        font-family: Arial, sans-serif;
    }

    /* ── Verification strip ── */
    .msl-verify-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        margin-top: 14px;
        border-top: 1px dashed var(--msl-border);
        font-size: 9px;
        color: var(--msl-mid);
        font-family: Arial, sans-serif;
    }
</style>

<div class="msl-wrap">

    {{-- ── INTERNAL USE BANNER ── --}}
    <div class="msl-internal-banner">
        &#128274; Internal Record — Not for External Distribution · Registre Interne — Ne Pas Diffuser à l'Extérieur
    </div>

    {{-- ── DOCUMENT TITLE ── --}}
    <div class="msl-doc-title">
        <h1>Mortuary Cold Storage Log</h1>
        <h2>Registre de Stockage à la Morgue</h2>
        <div class="msl-doc-meta">
            Document No.: {{ $document_number }} &bull;
            Body Tag: {{ $payload['body_tag_number'] ?? '—' }} &bull;
            Facility: {{ $facility_name }}
        </div>
    </div>

    {{-- ── I. PATIENT / DECEASED IDENTITY ── --}}
    <div class="msl-section">
        <div class="msl-section-title">I. Patient / Deceased Identity / Identité du Patient / Défunt</div>
        <div class="msl-section-body">
            <div class="msl-grid msl-grid-3">
                <div>
                    <div class="msl-label">Patient Name on Record / Nom du Patient</div>
                    <div class="msl-value">{{ $patient_name }}</div>
                </div>
                <div>
                    <div class="msl-label">Health ID / Identifiant de Santé</div>
                    <div class="msl-value">{{ $health_id }}</div>
                </div>
                <div>
                    <div class="msl-label">Date of Birth / Date de Naissance</div>
                    <div class="msl-value">{{ $patient_dob }}</div>
                </div>
                <div>
                    <div class="msl-label">Sex / Sexe</div>
                    <div class="msl-value">{{ $patient_sex }}</div>
                </div>
                <div>
                    <div class="msl-label">Body Tag Number / Numéro d'Étiquette du Corps</div>
                    <div class="msl-value">{{ $payload['body_tag_number'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. STORAGE UNIT ── --}}
    <div class="msl-section">
        <div class="msl-section-title">II. Storage Unit &amp; Temperature Zone / Unité de Stockage &amp; Zone de Température</div>
        <div class="msl-section-body">
            <div class="msl-grid msl-grid-3">
                <div>
                    <div class="msl-label">Storage Unit / Unité de Stockage</div>
                    <div class="msl-value">{{ $payload['storage_unit'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="msl-label">Temperature Zone / Zone de Température</div>
                    <div style="margin-top:5px;">
                        <span class="msl-badge-cold">&#10052; {{ $payload['temperature_zone'] ?? '—' }}</span>
                    </div>
                </div>
                <div>
                    <div class="msl-label">Preservation Method / Méthode de Conservation</div>
                    <div style="margin-top:5px;">
                        <span class="msl-badge">{{ $payload['preservation_method'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. ADMISSION ── --}}
    <div class="msl-section">
        <div class="msl-section-title">III. Admission Details / Détails de l'Admission</div>
        <div class="msl-section-body">
            <div class="msl-grid msl-grid-4">
                <div>
                    <div class="msl-label">Admission Date &amp; Time / Date &amp; Heure d'Admission</div>
                    <div class="msl-value">{{ $payload['admission_datetime'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="msl-label">Expected Release Date / Date de Sortie Prévue</div>
                    <div class="msl-value">{{ $payload['expected_release_date'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="msl-label">Body Condition on Admission / État du Corps à l'Admission</div>
                    @php
                        $cond = strtolower($payload['body_condition_on_admission'] ?? '');
                        $condClass = match(true) {
                            str_contains($cond, 'good')       => 'msl-condition-good',
                            str_contains($cond, 'fair')       => 'msl-condition-fair',
                            str_contains($cond, 'decomp')     => 'msl-condition-decomposed',
                            str_contains($cond, 'mutilat')    => 'msl-condition-mutilated',
                            default                            => '',
                        };
                    @endphp
                    <div class="msl-value {{ $condClass }}">{{ $payload['body_condition_on_admission'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="msl-label">Issued By / Délivré Par</div>
                    <div class="msl-value">{{ $issuer_name }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IV. DAILY INSPECTION LOG ── --}}
    <div class="msl-section">
        <div class="msl-section-title">IV. Daily Inspection Log / Journal d'Inspection Quotidienne</div>
        <div class="msl-section-body">
            <table class="msl-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time / Heure</th>
                        <th>Inspector / Inspecteur</th>
                        <th>Condition Noted / État Constaté</th>
                        <th>Temp. Recorded / Temp. Relevée</th>
                        <th>Action Taken / Action Prise</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($payload['daily_inspection_log']) && is_array($payload['daily_inspection_log']))
                        @foreach($payload['daily_inspection_log'] as $entry)
                        <tr>
                            <td>{{ $entry['date'] ?? '—' }}</td>
                            <td>{{ $entry['time'] ?? '—' }}</td>
                            <td>{{ $entry['inspector_name'] ?? '—' }}</td>
                            <td>{{ $entry['condition_noted'] ?? '—' }}</td>
                            <td>
                                @if(!empty($entry['temperature_recorded']))
                                    <span class="msl-badge-cold" style="font-size:10px;padding:2px 8px;">{{ $entry['temperature_recorded'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $entry['action_taken'] ?? '—' }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="msl-table-empty">No inspection entries recorded. / Aucune entrée d'inspection enregistrée.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── V. RELEASE DETAILS ── --}}
    <div class="msl-section">
        <div class="msl-section-title">V. Release Details / Détails de la Sortie</div>
        <div class="msl-section-body">
            <div class="msl-grid msl-grid-3">
                <div>
                    <div class="msl-label">Release Date &amp; Time / Date &amp; Heure de Sortie</div>
                    <div class="msl-value">{{ $payload['release_datetime'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="msl-label">Released To / Remis À</div>
                    <div class="msl-value">{{ $payload['released_to'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="msl-label">Release Authorized By / Autorisation de Sortie Par</div>
                    <div class="msl-value">{{ $payload['release_authorized_by'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VI. REMARKS ── --}}
    @if(!empty($payload['remarks']))
    <div class="msl-section">
        <div class="msl-section-title">VI. Remarks / Remarques</div>
        <div class="msl-section-body">
            <div class="msl-remarks">{{ $payload['remarks'] }}</div>
        </div>
    </div>
    @endif

    {{-- ── VII. AUTHORIZATION & SIGNATURES ── --}}
    <div class="msl-section">
        <div class="msl-section-title">VII. Authorization &amp; Signatures / Autorisation &amp; Signatures</div>
        <div class="msl-section-body">
            <div class="msl-sig-grid">
                <div>
                    <div class="msl-sig-area"></div>
                    <div class="msl-sig-line"></div>
                    <div class="msl-sig-name">{{ $issuer_name }}</div>
                    <div class="msl-sig-sub">{{ $issuer_role }}</div>
                    <div class="msl-sig-sub">{{ $facility_name }}</div>
                    <div class="msl-sig-sub">Date: {{ $issued_at }}</div>
                </div>
                <div>
                    <div class="msl-sig-area"></div>
                    <div class="msl-sig-line"></div>
                    <div class="msl-sig-name">{{ $payload['release_authorized_by'] ?? '—' }}</div>
                    <div class="msl-sig-sub">Release Authorizing Officer / Officier Autorisant la Sortie</div>
                    <div class="msl-sig-sub">{{ $facility_name }}</div>
                    <div class="msl-sig-sub">Date: {{ $payload['release_datetime'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── LEGAL / CONFIDENTIALITY NOTICE ── --}}
    <div class="msl-legal">
        <strong>Confidentiality Notice / Avis de Confidentialité:</strong>
        This cold storage log is an official internal mortuary record of <strong>{{ $facility_name }}</strong>.
        Information contained herein is strictly confidential and is governed by applicable Cameroon health
        legislation and institutional policies. Unauthorized access, disclosure, alteration, or reproduction
        of this document is a criminal offence. Body tag number
        <strong>{{ $payload['body_tag_number'] ?? 'N/A' }}</strong> must be quoted in all subsequent
        mortuary correspondence. This record must be retained for a minimum of <strong>10 years</strong>
        from the date of issue.
        <br><br>
        <em>Ce registre de stockage constitue un document interne officiel de la morgue de
        {{ $facility_name }}. Toute divulgation non autorisée constitue une infraction pénale.</em>
    </div>

    {{-- ── VERIFICATION STRIP ── --}}
    <div class="msl-verify-strip">
        <div>{!! $qr_svg !!}</div>
        <div style="text-align:right;">
            <div style="font-weight:700;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#1a3c5e;">Verification Code / Code de Vérification</div>
            <div style="font-family:monospace;font-size:13px;font-weight:800;letter-spacing:2px;color:#0f2338;">{{ $verification_code }}</div>
            <div style="margin-top:2px;">Document No.: {{ $document_number }} &bull; {{ $issued_at }}</div>
            <div>{{ $facility_name }} &bull; License: {{ $facility_license }}</div>
            <div style="margin-top:2px;color:#92400E;font-weight:700;font-size:9px;">
                INTERNAL USE ONLY — USAGE INTERNE UNIQUEMENT
            </div>
        </div>
    </div>

</div>
@endsection
