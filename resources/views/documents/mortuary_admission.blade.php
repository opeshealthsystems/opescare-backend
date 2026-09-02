@extends('documents.base')

@section('title', 'Mortuary Admission Register')
@section('subtitle', 'BRF · Ref: ' . ($payload['body_reference_number'] ?? 'OC-MRT-2026-000001') . ' · ADMITTED')

@section('content')
{{-- ============================================================
     MORTUARY ADMISSION REGISTER / FICHE D'ADMISSION À LA MORGUE
     Slug: mortuary-admission | Code: MRT | Color: #1a3c5e
     ============================================================ --}}

<style>
    :root {
        --mrt-dark:   #1a3c5e;
        --mrt-mid:    #4B5563;
        --mrt-light:  #f8f9fa;
        --mrt-border: #D1D5DB;
        --mrt-black:  #111827;
        --mrt-red:    #DC2626;
    }
    .mrt-wrap { font-family: 'Times New Roman', serif; color: var(--mrt-black); }
    .mrt-doc-title {
        text-align: center;
        margin: 18px 0 4px;
        padding: 12px 0;
        border-top: 2px solid var(--mrt-dark);
        border-bottom: 2px solid var(--mrt-dark);
    }
    .mrt-doc-title h1 { font-size: 17px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; margin: 0; color: var(--mrt-dark); }
    .mrt-doc-title h2 { font-size: 13px; font-weight: 600; font-style: italic; margin: 3px 0 0; color: var(--mrt-mid); }
    .mrt-doc-meta { margin-top: 6px; font-size: 10px; color: var(--mrt-mid); font-style: italic; }
    .mrt-section { margin: 14px 0 0; }
    .mrt-section-title {
        background: var(--mrt-dark);
        color: #fff;
        padding: 6px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 3px 3px 0 0;
        font-family: Arial, sans-serif;
    }
    .mrt-section-body {
        border: 1.5px solid var(--mrt-dark);
        border-top: none;
        padding: 12px 14px;
        border-radius: 0 0 3px 3px;
    }
    .mrt-grid { display: grid; gap: 10px 20px; }
    .mrt-grid-2 { grid-template-columns: 1fr 1fr; }
    .mrt-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .mrt-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .mrt-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--mrt-mid); font-weight: 700; margin-bottom: 2px; font-family: Arial, sans-serif; }
    .mrt-value { font-size: 12px; font-weight: 600; color: var(--mrt-black); border-bottom: 1px solid var(--mrt-border); padding-bottom: 3px; min-height: 20px; }
    .mrt-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .mrt-table th { background: var(--mrt-dark); color: #fff; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; font-family: Arial, sans-serif; }
    .mrt-table td { padding: 8px 10px; border-bottom: 1px solid var(--mrt-border); vertical-align: top; }
    .mrt-table tr:nth-child(even) td { background: var(--mrt-light); }
    .mrt-badge-yes { display: inline-block; background: #991B1B; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .mrt-badge-no  { display: inline-block; background: #065F46; color: #fff; border-radius: 3px; padding: 2px 9px; font-size: 11px; font-weight: 700; }
    .mrt-sig-block { margin-top: 14px; }
    .mrt-sig-area { height: 55px; border: 1px dashed var(--mrt-border); border-radius: 4px; margin-bottom: 6px; background: #FAFAFA; }
    .mrt-sig-line { border-bottom: 1.5px solid var(--mrt-dark); margin-bottom: 4px; }
    .mrt-sig-name { font-size: 12px; font-weight: 700; }
    .mrt-sig-sub  { font-size: 10px; color: var(--mrt-mid); font-style: italic; }
    .mrt-legal { background: var(--mrt-light); border-left: 4px solid var(--mrt-dark); padding: 10px 14px; font-size: 10px; color: #4B5563; line-height: 1.7; margin: 14px 0; border-radius: 0 4px 4px 0; font-family: Arial, sans-serif; }
</style>

<div class="mrt-wrap">

    {{-- ── DOCUMENT TITLE ── --}}
    <div class="mrt-doc-title">
        <h1>Mortuary Admission Register</h1>
        <h2>Fiche d'Admission à la Morgue</h2>
        <div class="mrt-doc-meta">Ref. No.: {{ $payload['body_reference_number'] ?? 'N/A' }} &bull; Admitted: {{ $payload['admission_date'] ?? $issued_at }} &bull; Doc: {{ $document_number }}</div>
    </div>

    {{-- ── I. ADMISSION DETAILS ── --}}
    <div class="mrt-section">
        <div class="mrt-section-title">I. Admission Details / Détails de l'Admission</div>
        <div class="mrt-section-body">
            <div class="mrt-grid mrt-grid-4">
                <div>
                    <div class="mrt-label">Admission Date / Date</div>
                    <div class="mrt-value">{{ $payload['admission_date'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Admission Time / Heure</div>
                    <div class="mrt-value">{{ $payload['admission_time'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Body Reference No. / Réf. Corps</div>
                    <div class="mrt-value">{{ $payload['body_reference_number'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Storage Compartment / Compartiment</div>
                    <div class="mrt-value">{{ $payload['storage_compartment'] ?? '—' }}</div>
                </div>
            </div>
            <div class="mrt-grid mrt-grid-3" style="margin-top:10px;">
                <div>
                    <div class="mrt-label">Referring Ward / Facility</div>
                    <div class="mrt-value">{{ $payload['referring_ward_or_facility'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Admitting Officer / Agent d'Admission</div>
                    <div class="mrt-value">{{ $payload['admitting_officer'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Body Condition / État du Corps</div>
                    <div class="mrt-value">{{ $payload['body_condition'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── II. DECEASED INFORMATION ── --}}
    <div class="mrt-section">
        <div class="mrt-section-title">II. Deceased Information / Informations sur le Défunt</div>
        <div class="mrt-section-body">
            <div class="mrt-grid mrt-grid-3">
                <div>
                    <div class="mrt-label">Full Name / Nom Complet</div>
                    <div class="mrt-value">{{ $payload['deceased_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Age / Âge</div>
                    <div class="mrt-value">{{ $payload['deceased_age'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Sex / Sexe</div>
                    <div class="mrt-value">{{ $payload['deceased_sex'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Nationality / Nationalité</div>
                    <div class="mrt-value">{{ $payload['deceased_nationality'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Date of Death / Date du Décès</div>
                    <div class="mrt-value">{{ $payload['date_of_death'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Time of Death / Heure du Décès</div>
                    <div class="mrt-value">{{ $payload['time_of_death'] ?? '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="mrt-label">Place of Death / Lieu du Décès</div>
                    <div class="mrt-value">{{ $payload['place_of_death'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Provisional Cause of Death / Cause Provisoire</div>
                    <div class="mrt-value">{{ $payload['cause_of_death_provisional'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── III. NEXT OF KIN ── --}}
    <div class="mrt-section">
        <div class="mrt-section-title">III. Next of Kin / Proche Parent</div>
        <div class="mrt-section-body">
            <div class="mrt-grid mrt-grid-3">
                <div>
                    <div class="mrt-label">Name / Nom</div>
                    <div class="mrt-value">{{ $payload['next_of_kin_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Relationship / Lien de Parenté</div>
                    <div class="mrt-value">{{ $payload['next_of_kin_relationship'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="mrt-label">Contact / Téléphone</div>
                    <div class="mrt-value">{{ $payload['next_of_kin_contact'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── IV. POLICE CASE & PERSONAL EFFECTS ── --}}
    <div class="mrt-section">
        <div class="mrt-section-title">IV. Police Case &amp; Personal Effects / Affaire Policière &amp; Effets Personnels</div>
        <div class="mrt-section-body">
            <div class="mrt-grid mrt-grid-2">
                <div>
                    <div class="mrt-label">Police Case / Affaire Policière</div>
                    <div style="margin-top:5px;">
                        @if(!empty($payload['police_case']))
                            <span class="mrt-badge-yes">YES — Police Case No. {{ $payload['police_case_number'] ?? 'N/A' }}</span>
                        @else
                            <span class="mrt-badge-no">No</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="mrt-label">Personal Effects / Effets Personnels</div>
                    <div class="mrt-value" style="min-height:40px;">{{ $payload['personal_effects'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── V. AUTHORISATION & SIGNATURE ── --}}
    <div class="mrt-section">
        <div class="mrt-section-title">V. Authorization &amp; Signature / Autorisation &amp; Signature</div>
        <div class="mrt-section-body">
            <div class="mrt-grid mrt-grid-2">
                <div class="mrt-sig-block">
                    <div class="mrt-sig-area"></div>
                    <div class="mrt-sig-line"></div>
                    <div class="mrt-sig-name">{{ $issuer_name }}</div>
                    <div class="mrt-sig-sub">{{ $issuer_role }}</div>
                    <div class="mrt-sig-sub">{{ $facility_name }}</div>
                    <div class="mrt-sig-sub">Date: {{ $issued_at }}</div>
                </div>
                <div class="mrt-sig-block">
                    <div class="mrt-sig-area"></div>
                    <div class="mrt-sig-line"></div>
                    <div class="mrt-sig-name">{{ $payload['admitting_officer'] ?? '—' }}</div>
                    <div class="mrt-sig-sub">Admitting Officer / Agent d'Admission</div>
                    <div class="mrt-sig-sub">{{ $facility_name }}</div>
                    <div class="mrt-sig-sub">Date: {{ $payload['admission_date'] ?? $issued_at }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mrt-legal">
        <strong>Confidentiality Notice:</strong> This register is an official mortuary record of {{ $facility_name }}. Information contained herein is strictly confidential and governed by applicable health legislation. Unauthorized disclosure is a criminal offence. Body reference number <strong>{{ $payload['body_reference_number'] ?? 'N/A' }}</strong> must be quoted in all subsequent correspondence.
    </div>

</div>
@endsection
