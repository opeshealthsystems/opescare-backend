@extends('documents.base')

@section('content')
{{-- ============================================================
     CLINICAL DEATH SUMMARY
     Slug: death-summary | Code: DSU | Color: #1F2937
     ============================================================ --}}

<style>
    :root {
        --dsu-dark:   #1F2937;
        --dsu-mid:    #374151;
        --dsu-gray:   #6B7280;
        --dsu-light:  #F9FAFB;
        --dsu-border: #E5E7EB;
        --dsu-red:    #DC2626;
        --dsu-red-bg: #FEF2F2;
        --dsu-red-bdr:#FCA5A5;
        --dsu-green:  #065F46;
        --dsu-amber:  #B45309;
    }

    .dsu-wrap { font-family: Arial, Helvetica, sans-serif; color: var(--dsu-dark); font-size: 12px; }

    /* ── 1. Dark solemn header ── */
    .dsu-header {
        background: var(--dsu-dark);
        color: #fff;
        border-radius: 5px 5px 0 0;
        padding: 0;
        overflow: hidden;
    }
    .dsu-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px 10px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .dsu-header-doc-type {
        font-size: 16px;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .dsu-header-conf {
        background: var(--dsu-red);
        color: #fff;
        padding: 4px 12px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .dsu-header-bottom {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        padding: 10px 20px 14px;
    }
    .dsu-header-field {
        padding-right: 16px;
    }
    .dsu-header-field .lbl {
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.6;
        margin-bottom: 2px;
    }
    .dsu-header-field .val {
        font-size: 12px;
        font-weight: 700;
        opacity: 0.95;
    }
    .dsu-header-facility {
        padding: 6px 20px;
        background: rgba(0,0,0,0.25);
        font-size: 10px;
        opacity: 0.8;
        font-style: italic;
        display: flex;
        justify-content: space-between;
    }

    /* ── Section cards ── */
    .dsu-card {
        border: 1.5px solid var(--dsu-border);
        border-radius: 4px;
        margin: 12px 0;
        overflow: hidden;
    }
    .dsu-card-header {
        background: var(--dsu-mid);
        color: #fff;
        padding: 7px 14px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }
    .dsu-card-body {
        padding: 12px 14px;
        background: #fff;
    }

    /* ── 2. Admission-to-Death timeline ── */
    .dsu-timeline {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        padding: 14px 0;
        background: var(--dsu-light);
        border: 1.5px solid var(--dsu-border);
        border-radius: 4px;
        margin: 12px 0;
    }
    .dsu-tl-node {
        text-align: center;
        flex: 1;
    }
    .dsu-tl-dot {
        width: 14px; height: 14px;
        border-radius: 50%;
        background: var(--dsu-mid);
        margin: 0 auto 5px;
    }
    .dsu-tl-dot.death { background: var(--dsu-red); }
    .dsu-tl-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: var(--dsu-gray); font-weight: 700; }
    .dsu-tl-date  { font-size: 12px; font-weight: 700; color: var(--dsu-dark); margin-top: 2px; }
    .dsu-tl-sub   { font-size: 10px; color: var(--dsu-gray); }
    .dsu-tl-line  {
        flex: 0 0 80px;
        height: 2px;
        background: linear-gradient(to right, var(--dsu-mid), var(--dsu-red));
        position: relative;
        top: -12px;
    }
    .dsu-tl-duration {
        text-align: center;
        position: relative;
        top: -10px;
        font-size: 10px;
        color: var(--dsu-gray);
        font-style: italic;
        background: var(--dsu-light);
        padding: 0 4px;
    }

    /* ── 3. Final cause of death highlighted box ── */
    .dsu-cod-box {
        border: 3px solid var(--dsu-red);
        border-radius: 5px;
        padding: 16px 18px;
        background: var(--dsu-red-bg);
        margin: 12px 0;
    }
    .dsu-cod-box-label {
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--dsu-red);
        margin-bottom: 6px;
    }
    .dsu-cod-cause {
        font-size: 16px;
        font-weight: 900;
        color: var(--dsu-dark);
        line-height: 1.3;
        margin-bottom: 6px;
    }
    .dsu-cod-icd {
        display: inline-block;
        background: var(--dsu-red);
        color: #fff;
        border-radius: 4px;
        padding: 4px 12px;
        font-size: 13px;
        font-weight: 700;
        font-family: monospace;
        letter-spacing: 1px;
    }

    /* ── Info grid ── */
    .dsu-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; }
    .dsu-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px 16px; }
    .dsu-field-label {
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--dsu-gray);
        font-weight: 700;
        margin-bottom: 2px;
    }
    .dsu-field-value {
        font-size: 12px;
        font-weight: 600;
        color: var(--dsu-dark);
    }

    /* ── Boolean badges ── */
    .dsu-yes {
        display: inline-flex; align-items: center; gap: 4px;
        background: #D1FAE5; color: var(--dsu-green);
        border: 1px solid #6EE7B7;
        border-radius: 3px; padding: 2px 9px;
        font-size: 11px; font-weight: 700;
    }
    .dsu-no {
        display: inline-flex; align-items: center; gap: 4px;
        background: #FEE2E2; color: var(--dsu-red);
        border: 1px solid var(--dsu-red-bdr);
        border-radius: 3px; padding: 2px 9px;
        font-size: 11px; font-weight: 700;
    }

    /* ── Tag lists ── */
    .dsu-tag-list {
        display: flex; flex-wrap: wrap; gap: 5px;
        list-style: none; margin: 6px 0 0; padding: 0;
    }
    .dsu-tag {
        background: var(--dsu-light);
        border: 1px solid var(--dsu-border);
        border-radius: 3px;
        padding: 3px 9px;
        font-size: 11px;
        color: var(--dsu-dark);
    }
    .dsu-tag-red {
        background: #FEF2F2;
        border-color: var(--dsu-red-bdr);
        color: #991B1B;
    }

    /* ── Notifications list ── */
    .dsu-notif-list {
        list-style: none;
        padding: 0; margin: 0;
    }
    .dsu-notif-item {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 0;
        border-bottom: 1px solid var(--dsu-border);
        font-size: 11.5px;
    }
    .dsu-notif-item:last-child { border-bottom: none; }
    .dsu-notif-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--dsu-green);
        flex-shrink: 0;
    }

    /* ── Signature ── */
    .dsu-sig-wrap {
        border: 1.5px solid var(--dsu-border);
        border-radius: 4px;
        padding: 14px 18px;
        margin: 12px 0;
        display: flex;
        align-items: flex-end;
        gap: 40px;
    }
    .dsu-sig-block { flex: 1; }
    .dsu-sig-line {
        border-bottom: 1.5px solid var(--dsu-dark);
        min-height: 50px;
        margin-bottom: 6px;
    }
    .dsu-sig-name { font-size: 12px; font-weight: 700; color: var(--dsu-dark); }
    .dsu-sig-sub  { font-size: 10px; color: var(--dsu-gray); font-style: italic; }
    .dsu-sig-stamp {
        width: 80px; height: 80px;
        border: 1.5px dashed #9CA3AF;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 9px; color: #9CA3AF; text-align: center;
        flex-shrink: 0;
    }

    /* ── Empty state ── */
    .dsu-empty { font-size: 11px; color: #D1D5DB; font-style: italic; }

    /* ── Verify strip ── */
    .dsu-verify {
        display: flex; align-items: center; justify-content: space-between;
        padding-top: 10px; margin-top: 12px;
        border-top: 1px dashed var(--dsu-border);
        font-size: 9px; color: var(--dsu-gray);
    }
</style>

<div class="dsu-wrap">

    {{-- ── 1. SOLEMN DARK HEADER ── --}}
    <div class="dsu-header">
        <div class="dsu-header-top">
            <div>
                <div class="dsu-header-doc-type">Clinical Death Summary</div>
                <div style="font-size:11px;opacity:0.7;margin-top:2px;">Résumé Clinique de Décès &bull; DSU &bull; {{ $document_number }}</div>
            </div>
            <div class="dsu-header-conf">CONFIDENTIEL</div>
        </div>
        <div class="dsu-header-bottom">
            <div class="dsu-header-field">
                <div class="lbl">Patient Name</div>
                <div class="val">{{ $patient_name }}</div>
            </div>
            <div class="dsu-header-field">
                <div class="lbl">Health ID</div>
                <div class="val">{{ $health_id }}</div>
            </div>
            <div class="dsu-header-field">
                <div class="lbl">Sex / DOB</div>
                <div class="val">{{ $patient_sex }} &bull; {{ $patient_dob }}</div>
            </div>
            <div class="dsu-header-field">
                <div class="lbl">Ward / Unit</div>
                <div class="val">{{ $payload['ward'] }}</div>
            </div>
        </div>
        <div class="dsu-header-facility">
            <span>{{ $facility_name }} &bull; License: {{ $facility_license }}</span>
            <span>Issued: {{ $issued_at }}</span>
        </div>
    </div>

    {{-- ── 2. ADMISSION-TO-DEATH TIMELINE ── --}}
    <div class="dsu-timeline">
        <div class="dsu-tl-node">
            <div class="dsu-tl-dot"></div>
            <div class="dsu-tl-label">Admitted</div>
            <div class="dsu-tl-date">{{ $payload['admission_date'] }}</div>
            <div class="dsu-tl-sub">Admission Diagnosis:</div>
            <div class="dsu-tl-sub" style="font-weight:600;color:#374151;max-width:140px;margin:0 auto;">{{ $payload['admission_diagnosis'] }}</div>
        </div>
        <div style="flex:1;">
            <div class="dsu-tl-line"></div>
            <div class="dsu-tl-duration">{{ $payload['total_length_of_stay'] }}</div>
        </div>
        <div class="dsu-tl-node">
            <div class="dsu-tl-dot death"></div>
            <div class="dsu-tl-label" style="color:var(--dsu-red);">Death</div>
            <div class="dsu-tl-date" style="color:var(--dsu-red);">{{ $payload['death_date'] }}</div>
            <div class="dsu-tl-sub">{{ $payload['death_time'] }}</div>
        </div>
    </div>

    {{-- ── 3. FINAL CAUSE OF DEATH ── --}}
    <div class="dsu-cod-box">
        <div class="dsu-cod-box-label">⚕ Final Cause of Death / Cause Finale du Décès</div>
        <div class="dsu-cod-cause">{{ $payload['final_cause_of_death'] }}</div>
        <span class="dsu-cod-icd">ICD-10: {{ $payload['icd10_code'] }}</span>
    </div>

    {{-- ── 4. CLINICAL NARRATIVE ── --}}
    <div class="dsu-card">
        <div class="dsu-card-header">Clinical Narrative / Résumé Clinique</div>
        <div class="dsu-card-body" style="line-height:1.75;font-size:12px;color:#1F2937;">
            {{ $payload['clinical_narrative'] }}
        </div>
    </div>

    {{-- ── 5. RESUSCITATION RECORD ── --}}
    <div class="dsu-card">
        <div class="dsu-card-header">Resuscitation Record / Réanimation</div>
        <div class="dsu-card-body">
            <div class="dsu-grid-3" style="margin-bottom:10px;">
                <div>
                    <div class="dsu-field-label">Resuscitation Attempted / Réanimation Tentée</div>
                    <div style="margin-top:4px;">
                        @if(!empty($payload['resuscitation_attempted']))
                            <span class="dsu-yes">&#10003; Yes / Oui</span>
                        @else
                            <span class="dsu-no">&#10007; No / Non</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="dsu-field-label">DNR Order in Place / Directives de Non-Réanimation</div>
                    <div style="margin-top:4px;">
                        @if(!empty($payload['dnr_order_in_place']))
                            <span class="dsu-yes">&#10003; Yes / Oui</span>
                        @else
                            <span class="dsu-no">&#10007; No / Non</span>
                        @endif
                    </div>
                </div>
                <div></div>
            </div>
            @if(!empty($payload['resuscitation_details']))
            <div style="background:var(--dsu-light);border:1px solid var(--dsu-border);border-radius:4px;padding:10px 12px;font-size:11.5px;color:#374151;line-height:1.6;">
                <strong style="font-size:9.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--dsu-gray);">Details / Détails:</strong><br>
                {{ $payload['resuscitation_details'] }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── 6. END-OF-LIFE CARE ── --}}
    <div class="dsu-card">
        <div class="dsu-card-header">End-of-Life Care / Soins de Fin de Vie</div>
        <div class="dsu-card-body">
            <div class="dsu-grid-2">
                <div>
                    <div class="dsu-field-label">Family Informed / Famille Informée</div>
                    <div style="margin-top:4px;">
                        @if(!empty($payload['family_informed']))
                            <span class="dsu-yes">&#10003; Yes / Oui</span>
                            @if(!empty($payload['family_informed_at']))
                                <span style="font-size:10px;color:var(--dsu-gray);margin-left:8px;">at {{ $payload['family_informed_at'] }}</span>
                            @endif
                        @else
                            <span class="dsu-no">&#10007; No / Non</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="dsu-field-label">Next of Kin Present at Death / Proche Présent</div>
                    <div style="margin-top:4px;">
                        @if(!empty($payload['next_of_kin_present']))
                            <span class="dsu-yes">&#10003; Yes / Oui</span>
                        @else
                            <span class="dsu-no">&#10007; No / Non</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 7. ACTIVE STATUS AT TIME OF DEATH ── --}}
    <div class="dsu-card">
        <div class="dsu-card-header">Active Status at Time of Death / État Actif au Moment du Décès</div>
        <div class="dsu-card-body">
            {{-- Procedures --}}
            <div class="dsu-field-label" style="margin-bottom:4px;">Active Procedures / Procédures Actives</div>
            @if(!empty($payload['procedures_at_death']))
                <ul class="dsu-tag-list">
                    @foreach($payload['procedures_at_death'] as $proc)
                    <li class="dsu-tag">{{ $proc }}</li>
                    @endforeach
                </ul>
            @else
                <span class="dsu-empty">None documented</span>
            @endif

            {{-- Medications --}}
            <div class="dsu-field-label" style="margin:12px 0 4px;">Active Medications / Médicaments Actifs</div>
            @if(!empty($payload['active_medications_at_death']))
                <ul class="dsu-tag-list">
                    @foreach($payload['active_medications_at_death'] as $drug)
                    <li class="dsu-tag dsu-tag-red">{{ $drug }}</li>
                    @endforeach
                </ul>
            @else
                <span class="dsu-empty">None documented</span>
            @endif

            {{-- Comorbidities --}}
            <div class="dsu-field-label" style="margin:12px 0 4px;">Comorbidities / Comorbidités</div>
            @if(!empty($payload['comorbidities']))
                <ul class="dsu-tag-list">
                    @foreach($payload['comorbidities'] as $comorb)
                    <li class="dsu-tag">{{ $comorb }}</li>
                    @endforeach
                </ul>
            @else
                <span class="dsu-empty">None documented</span>
            @endif

            {{-- Autopsy --}}
            <div class="dsu-field-label" style="margin:12px 0 4px;">Autopsy Requested / Autopsie Demandée</div>
            @if(!empty($payload['autopsy_requested']))
                <span class="dsu-yes">&#10003; Yes / Oui</span>
            @else
                <span class="dsu-no">&#10007; No / Non</span>
            @endif
        </div>
    </div>

    {{-- ── 8. NOTIFICATIONS ISSUED ── --}}
    <div class="dsu-card">
        <div class="dsu-card-header">Notifications Issued / Notifications Émises</div>
        <div class="dsu-card-body">
            @if(!empty($payload['notification_sent_to']))
                <ul class="dsu-notif-list">
                    @foreach($payload['notification_sent_to'] as $recipient)
                    <li class="dsu-notif-item">
                        <span class="dsu-notif-dot"></span>
                        {{ $recipient }}
                    </li>
                    @endforeach
                </ul>
            @else
                <span class="dsu-empty">No notifications recorded</span>
            @endif
        </div>
    </div>

    {{-- ── 9. SIGNATURE ── --}}
    <div class="dsu-sig-wrap">
        <div class="dsu-sig-block">
            <div class="dsu-sig-line"></div>
            <div class="dsu-sig-name">{{ $issuer_name }}</div>
            <div class="dsu-sig-sub">{{ $issuer_role }}</div>
            <div class="dsu-sig-sub">Attending Physician / Médecin Traitant</div>
            <div class="dsu-sig-sub" style="margin-top:3px;">{{ $facility_name }}</div>
            <div class="dsu-sig-sub">Signed: {{ $issued_at }}</div>
        </div>
        <div class="dsu-sig-stamp">Official<br>Stamp<br>Cachet</div>
    </div>

    {{-- ── VERIFICATION STRIP ── --}}
    <div class="dsu-verify">
        <div>{!! $qr_svg !!}</div>
        <div style="text-align:right;">
            <div style="font-weight:700;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#374151;">Verification Code</div>
            <div style="font-family:monospace;font-size:13px;font-weight:800;letter-spacing:2px;color:#1F2937;">{{ $verification_code }}</div>
            <div style="margin-top:2px;">{{ $document_number }} &bull; {{ $issued_at }}</div>
            <div>{{ $facility_name }} &bull; License: {{ $facility_license }}</div>
            <div style="margin-top:3px;font-style:italic;">CONFIDENTIEL — Internal Clinical Record / Dossier Clinique Interne</div>
        </div>
    </div>

</div>
@endsection
