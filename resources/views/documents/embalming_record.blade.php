@extends('documents.base')

@section('title', 'Embalming & Preservation Record')
@section('subtitle', 'EMB · Ref: ' . ($payload['body_reference_number'] ?? 'OC-EMB-2026-000001') . ' · RECORDED')

@section('content')
{{-- ============================================================
     EMBALMING & PRESERVATION RECORD / FICHE D'EMBAUMEMENT
     Slug: embalming-record | Code: EMB | Color: #1a3c5e
     ============================================================ --}}

<style>
    :root {
        --emb-dark:   #1a3c5e;
        --emb-mid:    #4B5563;
        --emb-light:  #f8f9fa;
        --emb-border: #D1D5DB;
        --emb-black:  #111827;
    }
    .emb-wrap { font-family: 'Times New Roman', serif; color: var(--emb-black); }
    .emb-doc-title {
        text-align: center; margin: 18px 0 4px; padding: 12px 0;
        border-top: 2px solid var(--emb-dark); border-bottom: 2px solid var(--emb-dark);
    }
    .emb-doc-title h1 { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; margin: 0; color: var(--emb-dark); }
    .emb-doc-title h2 { font-size: 13px; font-weight: 600; font-style: italic; margin: 3px 0 0; color: var(--emb-mid); }
    .emb-doc-meta { margin-top: 6px; font-size: 10px; color: var(--emb-mid); font-style: italic; }
    .emb-section { margin: 14px 0 0; }
    .emb-section-title {
        background: var(--emb-dark); color: #fff; padding: 6px 14px;
        font-size: 9.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1.5px; border-radius: 3px 3px 0 0; font-family: Arial, sans-serif;
    }
    .emb-section-body { border: 1.5px solid var(--emb-dark); border-top: none; padding: 12px 14px; border-radius: 0 0 3px 3px; }
    .emb-grid { display: grid; gap: 10px 20px; }
    .emb-grid-2 { grid-template-columns: 1fr 1fr; }
    .emb-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .emb-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .emb-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--emb-mid); font-weight: 700; margin-bottom: 2px; font-family: Arial, sans-serif; }
    .emb-value { font-size: 12px; font-weight: 600; color: var(--emb-black); border-bottom: 1px solid var(--emb-border); padding-bottom: 3px; min-height: 20px; }
    .emb-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .emb-table th { background: var(--emb-dark); color: #fff; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; font-family: Arial, sans-serif; }
    .emb-table td { padding: 8px 10px; border-bottom: 1px solid var(--emb-border); vertical-align: top; }
    .emb-table tr:nth-child(even) td { background: var(--emb-light); }
    .emb-badge-pass { display: inline-block; background: #065F46; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .emb-badge-fail { display: inline-block; background: #991B1B; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .emb-badge-yes  { display: inline-block; background: #065F46; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .emb-badge-no   { display: inline-block; background: #6B7280; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .emb-sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 14px; }
    .emb-sig-area { height: 55px; border: 1px dashed var(--emb-border); border-radius: 4px; margin-bottom: 6px; background: #FAFAFA; }
    .emb-sig-line { border-bottom: 1.5px solid var(--emb-dark); margin-bottom: 4px; }
    .emb-sig-name { font-size: 12px; font-weight: 700; }
    .emb-sig-sub  { font-size: 10px; color: var(--emb-mid); font-style: italic; }
    .emb-legal { background: var(--emb-light); border-left: 4px solid var(--emb-dark); padding: 10px 14px; font-size: 10px; color: #4B5563; line-height: 1.7; margin: 14px 0; border-radius: 0 4px 4px 0; font-family: Arial, sans-serif; }
</style>

<div class="emb-wrap">

    <div class="emb-doc-title">
        <h1>Embalming &amp; Preservation Record</h1>
        <h2>Fiche d'Embaumement et de Conservation</h2>
        <div class="emb-doc-meta">Ref.: {{ $payload['body_reference_number'] ?? 'N/A' }} &bull; Date: {{ $payload['embalming_date'] ?? $issued_at }} &bull; Doc: {{ $document_number }}</div>
    </div>

    {{-- ── I. BODY & PROCEDURE DETAILS ── --}}
    <div class="emb-section">
        <div class="emb-section-title">I. Body &amp; Procedure Details / Détails du Corps et de la Procédure</div>
        <div class="emb-section-body">
            <div class="emb-grid emb-grid-3">
                <div>
                    <div class="emb-label">Body Reference No. / Réf. Corps</div>
                    <div class="emb-value">{{ $payload['body_reference_number'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="emb-label">Deceased Name / Nom du Défunt</div>
                    <div class="emb-value">{{ $payload['deceased_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">Embalming Date / Date</div>
                    <div class="emb-value">{{ $payload['embalming_date'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">Start Time / Heure Début</div>
                    <div class="emb-value">{{ $payload['embalming_start_time'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">End Time / Heure Fin</div>
                    <div class="emb-value">{{ $payload['embalming_end_time'] ?? '—' }}</div>
                </div>
            </div>
            <div class="emb-grid emb-grid-3" style="margin-top:10px;">
                <div>
                    <div class="emb-label">Embalmer Name / Embaumeur</div>
                    <div class="emb-value">{{ $payload['embalmer_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">License No. / N° de Licence</div>
                    <div class="emb-value">{{ $payload['embalmer_license_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">Method Used / Méthode Utilisée</div>
                    <div class="emb-value">{{ $payload['method_used'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. PRE-PROCEDURE ASSESSMENT ── --}}
    <div class="emb-section">
        <div class="emb-section-title">II. Pre-Procedure Assessment / Évaluation Avant Procédure</div>
        <div class="emb-section-body">
            <div class="emb-grid emb-grid-2">
                <div>
                    <div class="emb-label">Body Condition Prior / État Avant</div>
                    <div class="emb-value" style="min-height:40px;">{{ $payload['body_condition_prior'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">Incisions Made / Incisions Effectuées</div>
                    <div class="emb-value" style="min-height:40px;">{{ $payload['incisions_made'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="emb-label">Special Procedures / Procédures Spéciales</div>
                    <div class="emb-value" style="min-height:36px;">{{ $payload['special_procedures'] ?? 'None / Aucune' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. CHEMICALS USED ── --}}
    <div class="emb-section">
        <div class="emb-section-title">III. Chemicals Used / Produits Chimiques Utilisés</div>
        <div class="emb-section-body">
            @if(!empty($payload['chemicals_used']) && is_array($payload['chemicals_used']))
            <table class="emb-table">
                <thead>
                    <tr>
                        <th>Chemical Name / Produit</th>
                        <th>Concentration / Concentration</th>
                        <th>Volume (mL) / Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['chemicals_used'] as $chemical)
                    <tr>
                        <td>{{ $chemical['name'] ?? '—' }}</td>
                        <td>{{ $chemical['concentration'] ?? '—' }}</td>
                        <td>{{ $chemical['volume_ml'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="font-size:11px;color:var(--emb-mid);font-style:italic;">No chemical data recorded / Aucun produit enregistré</p>
            @endif
        </div>
    </div>

    {{-- ── IV. COSMETIC RESTORATION ── --}}
    <div class="emb-section">
        <div class="emb-section-title">IV. Cosmetic Restoration / Restauration Esthétique</div>
        <div class="emb-section-body">
            <div class="emb-grid emb-grid-2">
                <div>
                    <div class="emb-label">Cosmetic Restoration Performed</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['cosmetic_restoration']))
                            <span class="emb-badge-yes">Yes / Oui</span>
                        @else
                            <span class="emb-badge-no">No / Non</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="emb-label">Cosmetic Notes / Remarques</div>
                    <div class="emb-value" style="min-height:36px;">{{ $payload['cosmetic_notes'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── V. QUALITY CHECK ── --}}
    <div class="emb-section">
        <div class="emb-section-title">V. Quality Check &amp; Notification / Contrôle Qualité &amp; Notification</div>
        <div class="emb-section-body">
            <div class="emb-grid emb-grid-3">
                <div>
                    <div class="emb-label">Quality Check Passed / Contrôle Qualité</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['quality_check_passed']))
                            <span class="emb-badge-pass">PASSED / RÉUSSI</span>
                        @else
                            <span class="emb-badge-fail">FAILED / ÉCHOUÉ</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="emb-label">Quality Check Officer / Agent de Contrôle</div>
                    <div class="emb-value">{{ $payload['quality_check_officer'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="emb-label">Next of Kin Notified / Famille Notifiée</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['next_of_kin_notified']))
                            <span class="emb-badge-yes">Yes / Oui</span>
                        @else
                            <span class="emb-badge-no">No / Non</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VI. SIGNATURES ── --}}
    <div class="emb-section">
        <div class="emb-section-title">VI. Certification / Certification</div>
        <div class="emb-section-body">
            <div class="emb-sig-grid">
                <div>
                    <div class="emb-sig-area"></div>
                    <div class="emb-sig-line"></div>
                    <div class="emb-sig-name">{{ $payload['embalmer_name'] ?? '—' }}</div>
                    <div class="emb-sig-sub">Licensed Embalmer / Embaumeur Agréé</div>
                    <div class="emb-sig-sub">License: {{ $payload['embalmer_license_number'] ?? 'N/A' }}</div>
                    <div class="emb-sig-sub">Date: {{ $payload['embalming_date'] ?? $issued_at }}</div>
                </div>
                <div>
                    <div class="emb-sig-area"></div>
                    <div class="emb-sig-line"></div>
                    <div class="emb-sig-name">{{ $payload['quality_check_officer'] ?? $issuer_name }}</div>
                    <div class="emb-sig-sub">Quality Check Officer / Agent de Contrôle Qualité</div>
                    <div class="emb-sig-sub">{{ $facility_name }}</div>
                    <div class="emb-sig-sub">Date: {{ $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="emb-legal">
        <strong>Record Integrity Notice:</strong> This embalming record is a permanent clinical document of {{ $facility_name }}. All entries are made in accordance with professional embalming standards and applicable health regulations. Chemical usage is logged for bio-hazard compliance and next-of-kin information purposes.
    </div>

</div>
@endsection
