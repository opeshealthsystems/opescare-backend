{{--
    The printable prescription — an A4 sheet a patient carries to a pharmacy.

    Deliberately NOT extending layouts/portal: this document leaves the building.
    No sidebar, no nav, no brand furniture — just the script, and a QR that takes
    whoever holds the paper to the live record.

    Print discipline: black on white, rules instead of fills (a shaded table head
    is a solid bar of toner on a shared clinic printer and photocopies to mud),
    a repeating table header so a long script survives a page break, and every
    on-screen control inside .no-print.

    CSS at-rules are written @@media / @@page — Blade compiles @@ to a literal @,
    which is the only way to be certain a directive-shaped token in a <style>
    block reaches the browser intact in this repo.
--}}
@php
    $rx  = $prescription;
    $ref = (string) ($rx->reference ?? '');

    $patientName = $patient
        ? trim(((string) ($patient->first_name ?? '')) . ' ' . ((string) ($patient->last_name ?? '')))
        : '';

    $prescriberName = trim((string) ($prescriber->name ?? ''));

    // Status label from the prescriber namespace, falling back to the shared
    // enum labeller — the same resolution the on-screen detail view uses, so
    // paper and screen never disagree about what a prescription is called.
    $label = function (?string $value): string {
        $value = (string) $value;
        if ($value === '') {
            return '—';
        }
        $key  = 'prescriber.st_' . $value;
        $text = __($key);

        return $text !== $key ? $text : \App\Support\Enums::label($value);
    };

    $facilityName = (string) ($facility->name ?? ($registry->name ?? ''));

    // Contact block: whatever the care-map registry row actually holds. Blank
    // fields are dropped rather than printed as em dashes.
    $contactLines = array_values(array_filter([
        trim(implode(', ', array_filter([
            (string) ($registry->address ?? ''),
            (string) ($registry->city ?? ''),
            (string) ($registry->region ?? ''),
        ]))),
        (string) ($registry->phone ?? ''),
        (string) ($registry->email ?? ''),
    ], fn ($line) => trim((string) $line) !== ''));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $ref !== '' ? __('prescriber.print_doc_title', ['reference' => $ref]) : __('prescriber.detail_title') }}</title>
<style>
    :root { --ink: #111; --rule: #444; --brand: #0F4C81; }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        background: #eef1f5;
        color: var(--ink);
        font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 11pt;
        line-height: 1.45;
    }

    /* ── On-screen controls; never printed ─────────────────────────────── */
    .toolbar {
        display: flex; gap: 10px; align-items: center;
        max-width: 210mm; margin: 16px auto 0; padding: 0 6mm;
    }
    .toolbar .spacer { flex: 1; }
    .tb-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 6px; border: 1px solid #c7d0da;
        background: #fff; color: var(--ink); font: inherit; font-size: 10.5pt;
        text-decoration: none; cursor: pointer;
    }
    .tb-btn svg { width: 16px; height: 16px; }
    .tb-btn--primary { background: var(--brand); border-color: var(--brand); color: #fff; }

    /* ── The sheet ─────────────────────────────────────────────────────── */
    .sheet {
        max-width: 210mm; min-height: 297mm; margin: 16px auto 40px;
        background: #fff; padding: 14mm 14mm 12mm;
        box-shadow: 0 2px 14px rgba(15, 23, 42, .16);
    }

    .head { display: flex; gap: 14mm; align-items: flex-start; }
    .head__facility { flex: 1; min-width: 0; }
    .head__facility h1 {
        margin: 0 0 3px; font-size: 15pt; line-height: 1.2;
        color: var(--brand); letter-spacing: .2px;
    }
    .head__contact { font-size: 9pt; line-height: 1.5; }
    .head__contact div { word-break: break-word; }

    .head__doc { text-align: right; flex-shrink: 0; }
    .head__doc .kind {
        margin: 0; font-size: 12pt; font-weight: 700;
        text-transform: uppercase; letter-spacing: 2px;
    }
    .head__doc .ref-label { font-size: 8pt; text-transform: uppercase; letter-spacing: 1.2px; margin-top: 6px; }
    .head__doc .ref {
        font-family: "Courier New", Courier, monospace;
        font-size: 19pt; font-weight: 700; letter-spacing: 1.5px; line-height: 1.1;
    }

    .rule { border: 0; border-top: 2px solid var(--ink); margin: 8px 0 0; }

    /* ── The unmissable banner for a script that must not be dispensed ─── */
    .void-banner {
        margin: 10px 0 0; padding: 8px 10px;
        border: 3px solid var(--ink);
        display: flex; gap: 10px; align-items: flex-start;
    }
    .void-banner svg { width: 22px; height: 22px; flex-shrink: 0; margin-top: 1px; }
    .void-banner .vb-title {
        font-size: 13pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        line-height: 1.2;
    }
    .void-banner .vb-detail { font-size: 9.5pt; }

    /* ── Facts ─────────────────────────────────────────────────────────── */
    .facts { display: flex; gap: 10mm; margin-top: 10px; }
    .facts__col { flex: 1; min-width: 0; }
    .facts h2 {
        margin: 0 0 4px; font-size: 8.5pt; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.2px;
        border-bottom: 1px solid var(--rule); padding-bottom: 2px;
    }
    .kv { width: 100%; border-collapse: collapse; font-size: 10pt; }
    .kv td { padding: 2px 0; vertical-align: top; }
    .kv td:first-child { width: 42%; padding-right: 6px; }
    .kv .v { font-weight: 600; word-break: break-word; }
    .mono { font-family: "Courier New", Courier, monospace; letter-spacing: .5px; }

    /* ── Medicines ─────────────────────────────────────────────────────── */
    h2.section {
        margin: 12px 0 4px; font-size: 8.5pt; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.2px;
        border-bottom: 1px solid var(--rule); padding-bottom: 2px;
    }
    table.items { width: 100%; border-collapse: collapse; font-size: 10pt; }
    table.items thead th {
        text-align: left; font-size: 8.5pt; font-weight: 700;
        text-transform: uppercase; letter-spacing: .6px;
        padding: 4px 5px; border-bottom: 2px solid var(--ink);
        background: transparent;
    }
    table.items tbody td {
        padding: 5px; border-bottom: 1px solid #bbb; vertical-align: top;
    }
    table.items tbody tr:last-child td { border-bottom: 1px solid var(--rule); }
    table.items .num { text-align: right; white-space: nowrap; }
    table.items .idx { width: 6mm; text-align: right; color: #444; }
    table.items .drug { font-weight: 700; }

    .notes { margin-top: 10px; font-size: 10pt; }
    .notes .notes__label {
        font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px;
    }
    .notes .notes__body {
        border: 1px solid var(--rule); padding: 6px 8px; margin-top: 3px; min-height: 12mm;
        white-space: pre-wrap; word-break: break-word;
    }

    /* ── Verify + signature ────────────────────────────────────────────── */
    .close { display: flex; gap: 10mm; margin-top: 14px; align-items: stretch; }
    .verify { width: 62mm; flex-shrink: 0; text-align: center; }
    .verify .qr { width: 36mm; height: 36mm; margin: 0 auto; background: #fff; }
    .verify .qr svg { width: 100%; height: 100%; display: block; }
    .verify .scan { font-size: 8.5pt; font-weight: 700; margin-top: 4px; line-height: 1.3; }
    .verify .url {
        font-family: "Courier New", Courier, monospace; font-size: 7pt;
        word-break: break-all; line-height: 1.3; margin-top: 3px;
    }
    .verify--none {
        display: flex; align-items: center; justify-content: center;
        border: 1px dashed var(--rule); padding: 8px; font-size: 8.5pt; text-align: left;
    }

    .sign { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; }
    .sign__line { border-bottom: 1px solid var(--ink); height: 20mm; }
    .sign__caption { font-size: 8.5pt; margin-top: 3px; }
    .sign__who { font-size: 10pt; font-weight: 700; }

    .foot {
        margin-top: 12px; padding-top: 5px; border-top: 1px solid var(--rule);
        font-size: 7.5pt; line-height: 1.5;
        display: flex; gap: 8mm; align-items: flex-start;
    }
    .foot__legal { flex: 1; }
    .foot__stamp { text-align: right; white-space: nowrap; }

    /* ── Print ─────────────────────────────────────────────────────────── */
    @@page { size: A4; margin: 12mm; }

    @@media print {
        /* One ink. Colour heads and coloured rules photocopy unpredictably and
           a mono laser renders the brand blue as a muddy grey. */
        * { color: #000 !important; background: transparent !important; box-shadow: none !important; }
        body { background: #fff; font-size: 10.5pt; }
        .no-print { display: none !important; }
        .sheet {
            max-width: none; min-height: 0; margin: 0; padding: 0;
            box-shadow: none;
        }
        /* A script that runs past one page keeps its column headings and never
           tears a medicine line in half. */
        table.items { page-break-inside: auto; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; break-inside: avoid; }
        .void-banner, .close, .foot { page-break-inside: avoid; break-inside: avoid; }
        a { text-decoration: none; }
    }

    @@media screen and (max-width: 820px) {
        .head, .facts, .close { flex-direction: column; gap: 6mm; }
        .head__doc { text-align: left; }
        .verify { width: auto; }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <button type="button" class="tb-btn tb-btn--primary" onclick="window.print()">
        {{-- Lucide "printer", inlined: a print sheet must not depend on a CDN icon font. --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect width="12" height="8" x="6" y="14"></rect></svg>
        {{ __('prescriber.print_action') }}
    </button>
    <a class="tb-btn" href="{{ route('portals.prescriber.show', $rx->id) }}">
        {{-- Lucide "arrow-left" --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>
        {{ __('prescriber.back') }}
    </a>
    <div class="spacer"></div>
</div>

<div class="sheet">

    {{-- Who issued it, and what this piece of paper is --}}
    <div class="head">
        <div class="head__facility">
            <h1>{{ $facilityName !== '' ? $facilityName : __('prescriber.print_facility') }}</h1>
            <div class="head__contact">
                @foreach($contactLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
                @if($facility && filled($facility->license_number))
                    <div>{{ __('prescriber.print_facility_license') }}: {{ $facility->license_number }}</div>
                @endif
            </div>
        </div>
        <div class="head__doc">
            <p class="kind">{{ __('prescriber.detail_title') }}</p>
            <div class="ref-label">{{ __('prescriber.print_reference') }}</div>
            <div class="ref">{{ $ref !== '' ? $ref : '—' }}</div>
        </div>
    </div>
    <hr class="rule">

    {{--
        A terminal or lapsed script must be impossible to mistake for a live
        one. Heavy border, all-caps, and it repeats beside the signature so a
        second page or a folded sheet still carries the warning.
    --}}
    @if($isNotValid || $isExpired)
    <div class="void-banner">
        {{-- Lucide "triangle-alert" --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
        <div>
            <div class="vb-title">
                @if($isNotValid)
                    {{ __('prescriber.print_void_banner') }}
                @else
                    {{ __('prescriber.print_expired_banner') }}
                @endif
            </div>
            <div class="vb-detail">
                @if($isNotValid)
                    {{ __('prescriber.print_void_detail', [
                        'status' => $label($rxStatus),
                        'date'   => $rx->voided_at?->format('d M Y H:i') ?? ($rx->updated_at?->format('d M Y H:i') ?? '—'),
                    ]) }}
                    @if(filled($rx->void_reason))
                        <div>{{ __('prescriber.void_reason') }}: {{ $rx->void_reason }}</div>
                    @endif
                @else
                    {{ __('prescriber.print_expired_detail', [
                        'date' => $rx->expires_at?->format('d M Y') ?? '—',
                    ]) }}
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Who it is for, and who ordered it --}}
    <div class="facts">
        <div class="facts__col">
            <h2>{{ __('prescriber.patient') }}</h2>
            <table class="kv">
                <tr>
                    <td>{{ __('prescriber.col_patient') }}</td>
                    <td class="v">{{ $patientName !== '' ? $patientName : '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.health_id') }}</td>
                    <td class="v mono">{{ $patient?->health_id ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.print_date_of_birth') }}</td>
                    <td class="v">{{ $patient?->date_of_birth?->format('d M Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.print_sex') }}</td>
                    <td class="v">@if($patient && filled($patient->sex))@enum($patient->sex, 'sex')@else—@endif</td>
                </tr>
            </table>
        </div>
        <div class="facts__col">
            <h2>{{ __('prescriber.detail_title') }}</h2>
            <table class="kv">
                <tr>
                    <td>{{ __('prescriber.status') }}</td>
                    <td class="v">{{ $label($rxStatus) }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.issued_at') }}</td>
                    <td class="v">{{ $rx->prescribed_at?->format('d M Y H:i') ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.expires_at') }}</td>
                    <td class="v">{{ $rx->expires_at?->format('d M Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.col_prescriber') }}</td>
                    <td class="v">
                        {{ $prescriberName !== '' ? $prescriberName : '—' }}
                        @if(filled($credential))<div>{{ $credential }}</div>@endif
                        @if(filled($licenseNumber))<div class="mono">{{ __('prescriber.print_license') }}: {{ $licenseNumber }}</div>@endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- The order itself --}}
    <h2 class="section">{{ __('prescriber.medicines') }}</h2>
    <table class="items">
        <thead>
            <tr>
                <th class="idx">#</th>
                <th>{{ __('prescriber.drug_name') }}</th>
                <th>{{ __('prescriber.dose') }}</th>
                <th>{{ __('prescriber.frequency') }}</th>
                <th>{{ __('prescriber.route') }}</th>
                <th class="num">{{ __('prescriber.duration_days') }}</th>
                <th class="num">{{ __('prescriber.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $i => $item)
            <tr>
                <td class="idx">{{ $i + 1 }}</td>
                <td class="drug">{{ $item->drug_name ?: '—' }}</td>
                <td>{{ $item->dose ?: '—' }}</td>
                <td>{{ $item->frequency ?: '—' }}</td>
                <td>{{ $item->route ? \App\Support\Enums::label($item->route, 'route') : '—' }}</td>
                <td class="num">{{ $item->duration_days !== null ? $item->duration_days : '—' }}</td>
                <td class="num">{{ $item->quantity !== null ? $item->quantity : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">{{ __('prescriber.print_no_items') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    @if(filled($rx->notes))
    <div class="notes">
        <div class="notes__label">{{ __('prescriber.notes') }}</div>
        <div class="notes__body">{{ $rx->notes }}</div>
    </div>
    @endif

    {{-- Scan-to-verify, and the hand that signed it --}}
    <div class="close">
        <div class="verify">
            @if($qrSvg && $verifyUrl)
                <div class="qr">{!! $qrSvg !!}</div>
                <div class="scan">{{ __('prescriber.print_scan_to_verify') }}</div>
                <div class="url">{{ $verifyUrl }}</div>
            @else
                <div class="verify--none">{{ __('prescriber.print_verify_unavailable') }}</div>
            @endif
        </div>
        <div class="sign">
            @if($isNotValid || $isExpired)
            <div class="sign__caption" style="font-weight:700;text-transform:uppercase;letter-spacing:1px;">
                @if($isNotValid){{ __('prescriber.print_void_banner') }}@else{{ __('prescriber.print_expired_banner') }}@endif
            </div>
            @endif
            <div class="sign__line"></div>
            <div class="sign__who">{{ $prescriberName !== '' ? $prescriberName : '—' }}@if(filled($credential)) — {{ $credential }}@endif</div>
            <div class="sign__caption">{{ __('prescriber.print_signature') }}</div>
        </div>
    </div>

    <div class="foot">
        <div class="foot__legal">{{ __('prescriber.print_footer') }}</div>
        <div class="foot__stamp">
            <div class="mono">{{ $ref !== '' ? $ref : '—' }}</div>
            <div>{{ __('prescriber.print_generated', ['datetime' => now()->format('d M Y H:i')]) }}</div>
        </div>
    </div>

</div>

</body>
</html>
