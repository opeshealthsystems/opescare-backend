@extends('documents.base')

@section('title', 'Body Release Certificate')
@section('subtitle', 'BRC · Ref: ' . ($payload['body_reference_number'] ?? 'OC-BRC-2026-000001') . ' · RELEASED')

@section('content')
{{-- ============================================================
     BODY RELEASE CERTIFICATE / CERTIFICAT DE REMISE DU CORPS
     Slug: body-release | Code: BRC | Color: #1a3c5e
     ============================================================ --}}

<style>
    :root {
        --brc-dark:   #1a3c5e;
        --brc-mid:    #4B5563;
        --brc-light:  #f8f9fa;
        --brc-border: #D1D5DB;
        --brc-black:  #111827;
    }
    .brc-wrap { font-family: 'Times New Roman', serif; color: var(--brc-black); }
    .brc-doc-title {
        text-align: center;
        margin: 18px 0 4px;
        padding: 12px 0;
        border-top: 2px solid var(--brc-dark);
        border-bottom: 2px solid var(--brc-dark);
    }
    .brc-doc-title h1 { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; margin: 0; color: var(--brc-dark); }
    .brc-doc-title h2 { font-size: 13px; font-weight: 600; font-style: italic; margin: 3px 0 0; color: var(--brc-mid); }
    .brc-doc-meta { margin-top: 6px; font-size: 10px; color: var(--brc-mid); font-style: italic; }
    .brc-section { margin: 14px 0 0; }
    .brc-section-title {
        background: var(--brc-dark); color: #fff; padding: 6px 14px;
        font-size: 9.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1.5px; border-radius: 3px 3px 0 0; font-family: Arial, sans-serif;
    }
    .brc-section-body {
        border: 1.5px solid var(--brc-dark); border-top: none;
        padding: 12px 14px; border-radius: 0 0 3px 3px;
    }
    .brc-grid { display: grid; gap: 10px 20px; }
    .brc-grid-2 { grid-template-columns: 1fr 1fr; }
    .brc-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .brc-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .brc-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--brc-mid); font-weight: 700; margin-bottom: 2px; font-family: Arial, sans-serif; }
    .brc-value { font-size: 12px; font-weight: 600; color: var(--brc-black); border-bottom: 1px solid var(--brc-border); padding-bottom: 3px; min-height: 20px; }
    .brc-badge-yes { display: inline-block; background: #065F46; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .brc-badge-no  { display: inline-block; background: #991B1B; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .brc-sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 14px; }
    .brc-sig-area { height: 55px; border: 1px dashed var(--brc-border); border-radius: 4px; margin-bottom: 6px; background: #FAFAFA; }
    .brc-sig-line { border-bottom: 1.5px solid var(--brc-dark); margin-bottom: 4px; }
    .brc-sig-name { font-size: 12px; font-weight: 700; }
    .brc-sig-sub  { font-size: 10px; color: var(--brc-mid); font-style: italic; }
    .brc-legal { background: var(--brc-light); border-left: 4px solid var(--brc-dark); padding: 10px 14px; font-size: 10px; color: #4B5563; line-height: 1.7; margin: 14px 0; border-radius: 0 4px 4px 0; font-family: Arial, sans-serif; }
    .brc-declaration { background: #EFF6FF; border: 1.5px solid #3B82F6; border-radius: 4px; padding: 10px 14px; font-size: 11px; color: #1E3A5F; line-height: 1.7; margin: 10px 0; }
</style>

<div class="brc-wrap">

    <div class="brc-doc-title">
        <h1>Body Release Certificate</h1>
        <h2>Certificat de Remise du Corps</h2>
        <div class="brc-doc-meta">Ref. No.: {{ $payload['body_reference_number'] ?? 'N/A' }} &bull; Released: {{ $payload['release_date'] ?? $issued_at }} &bull; Doc: {{ $document_number }}</div>
    </div>

    {{-- ── I. DECEASED DETAILS ── --}}
    <div class="brc-section">
        <div class="brc-section-title">I. Deceased Details / Informations sur le Défunt</div>
        <div class="brc-section-body">
            <div class="brc-grid brc-grid-3">
                <div>
                    <div class="brc-label">Body Reference No. / Réf. Corps</div>
                    <div class="brc-value">{{ $payload['body_reference_number'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="brc-label">Deceased Name / Nom du Défunt</div>
                    <div class="brc-value">{{ $payload['deceased_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Date of Death / Date du Décès</div>
                    <div class="brc-value">{{ $payload['date_of_death'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="brc-label">Cause of Death / Cause du Décès</div>
                    <div class="brc-value">{{ $payload['cause_of_death'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Storage Duration / Durée de Conservation</div>
                    <div class="brc-value">{{ $payload['storage_duration_days'] ?? '—' }} day(s)</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="brc-label">Body Condition at Release / État du Corps à la Remise</div>
                    <div class="brc-value">{{ $payload['body_condition_at_release'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. RELEASED TO ── --}}
    <div class="brc-section">
        <div class="brc-section-title">II. Released To / Remis À</div>
        <div class="brc-section-body">
            <div class="brc-grid brc-grid-3">
                <div>
                    <div class="brc-label">Full Name / Nom Complet</div>
                    <div class="brc-value">{{ $payload['released_to_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Relationship / Lien de Parenté</div>
                    <div class="brc-value">{{ $payload['released_to_relationship'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">ID Type / Type de Pièce</div>
                    <div class="brc-value">{{ $payload['released_to_id_type'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">ID Number / Numéro de Pièce</div>
                    <div class="brc-value">{{ $payload['released_to_id_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Release Date / Date de Remise</div>
                    <div class="brc-value">{{ $payload['release_date'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Release Time / Heure de Remise</div>
                    <div class="brc-value">{{ $payload['release_time'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. FUNERAL ARRANGEMENTS ── --}}
    <div class="brc-section">
        <div class="brc-section-title">III. Funeral Arrangements / Dispositions Funèbres</div>
        <div class="brc-section-body">
            <div class="brc-grid brc-grid-3">
                <div>
                    <div class="brc-label">Funeral Home / Pompes Funèbres</div>
                    <div class="brc-value">{{ $payload['funeral_home'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Burial Permit No. / Permis d'Inhumer</div>
                    <div class="brc-value">{{ $payload['burial_permit_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="brc-label">Police Clearance / Autorisation Policière</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['police_clearance']))
                            <span class="brc-badge-yes">Cleared — No. {{ $payload['police_clearance_number'] ?? 'N/A' }}</span>
                        @else
                            <span class="brc-badge-no">Not Required</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IV. DECLARATION ── --}}
    <div class="brc-declaration">
        I, <strong>{{ $payload['released_to_name'] ?? '___________________' }}</strong>, hereby acknowledge receipt of the body of
        <strong>{{ $payload['deceased_name'] ?? '___________________' }}</strong> (Ref: {{ $payload['body_reference_number'] ?? 'N/A' }})
        from <strong>{{ $facility_name }}</strong> on <strong>{{ $payload['release_date'] ?? $issued_at }}</strong> at
        <strong>{{ $payload['release_time'] ?? '—' }}</strong>.
        I confirm that the body has been received in satisfactory condition as described above.
    </div>

    {{-- ── V. AUTHORIZATION ── --}}
    <div class="brc-section">
        <div class="brc-section-title">V. Authorization / Autorisation</div>
        <div class="brc-section-body">
            <div class="brc-sig-grid">
                <div>
                    <div class="brc-sig-area"></div>
                    <div class="brc-sig-line"></div>
                    <div class="brc-sig-name">{{ $payload['release_authorized_by'] ?? $issuer_name }}</div>
                    <div class="brc-sig-sub">{{ $payload['release_authorized_role'] ?? $issuer_role }}</div>
                    <div class="brc-sig-sub">{{ $facility_name }}</div>
                    <div class="brc-sig-sub">Date: {{ $issued_at }}</div>
                </div>
                <div>
                    <div class="brc-sig-area"></div>
                    <div class="brc-sig-line"></div>
                    <div class="brc-sig-name">{{ $payload['released_to_name'] ?? '—' }}</div>
                    <div class="brc-sig-sub">Recipient Signature / Signature du Récipiendaire</div>
                    <div class="brc-sig-sub">ID: {{ $payload['released_to_id_type'] ?? '' }} {{ $payload['released_to_id_number'] ?? '' }}</div>
                    <div class="brc-sig-sub">Date: {{ $payload['release_date'] ?? $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="brc-legal">
        <strong>Legal Notice:</strong> This certificate constitutes the official record of body release from {{ $facility_name }}. The receiving party assumes full responsibility for the body from the date and time stated herein. This document must be retained for civil registration and burial proceedings. Any dispute regarding body condition must be raised at the time of release.
    </div>

</div>
@endsection
