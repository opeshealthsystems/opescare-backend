@extends('documents.base')

@section('title', 'Drug Dispensing / Pharmacy Record')
@section('subtitle', 'DPR — Pharmacy Dispensing &amp; Traceability Record')

@section('content')
@php
    $accentColor      = '#059669';
    $dispDate         = $payload['dispensing_date']        ?? '—';
    $dispType         = $payload['dispensing_type']        ?? '—';
    $rxRef            = $payload['prescription_ref']       ?? '—';
    $prescriber       = $payload['prescriber']             ?? '—';
    $prescriberReg    = $payload['prescriber_reg']         ?? '—';
    $items            = $payload['items']                  ?? [];
    $totalItems       = $payload['total_items']            ?? count($items);
    $controlledCount  = $payload['controlled_items_count'] ?? 0;
    $counselled       = $payload['patient_counselled']     ?? false;
    $counselLang      = $payload['counselling_language']   ?? '—';
    $understood       = $payload['patient_understood']     ?? false;
    $dispensedBy      = $payload['dispensed_by']           ?? '—';
    $dispensedByReg   = $payload['dispensed_by_reg']       ?? '—';
    $checkedBy        = $payload['checked_by']             ?? '—';
    $collectionBy     = $payload['collection_by']          ?? 'Patient';
    $repName          = $payload['representative_name']    ?? null;
    $repId            = $payload['representative_id']      ?? null;

    $dispTypeColors = [
        'Inpatient'            => '#0369a1',
        'Outpatient'           => '#059669',
        'Emergency'            => '#dc2626',
        'Discharge Medications'=> '#7c3aed',
    ];
    $dispTypeBg = $dispTypeColors[$dispType] ?? '#374151';

    $storageColors = [
        'Refrigerate (2–8°C)' => '#0369a1',
        'Controlled'          => '#dc2626',
        'Room temp'           => '#6b7280',
    ];
@endphp

{{-- ── Section 1: Header ── --}}
<div style="background:{{ $accentColor }};color:#fff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="background:{{ $dispTypeBg }};border:2px solid rgba(255,255,255,.4);padding:4px 12px;border-radius:4px;font-weight:700;font-size:13px;">
            {{ $dispType }}
        </span>
        <span style="font-size:13px;"><strong>Date:</strong> {{ $dispDate }}</span>
        <span style="font-size:13px;"><strong>Rx Ref:</strong> {{ $rxRef }}</span>
        <span style="font-size:13px;"><strong>Prescriber:</strong> {{ $prescriber }} ({{ $prescriberReg }})</span>
    </div>
</div>

{{-- ── Section 2: Dispensing Items Table ── --}}
<div style="margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:#065f46;text-transform:uppercase;border-bottom:2px solid #a7f3d0;padding-bottom:4px;margin-bottom:10px;">
        Dispensed Items
    </h3>
    @if(count($items) > 0)
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#d1fae5;color:#065f46;">
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">#</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Drug Name</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Generic</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Strength</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Form</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">Qty</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Unit</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Batch</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Expiry</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Manufacturer</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Storage</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Patient Counselling</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">Flags</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $item)
                @php
                    $isHighAlert  = !empty($item['high_alert']);
                    $isControlled = !empty($item['controlled_substance']);
                    $storage      = $item['storage_condition'] ?? 'Room temp';
                    $storageBg    = $storageColors[$storage] ?? '#6b7280';

                    // Near-expiry: expiry_date as string — simple check if year/month within 3 months from today
                    $expiry       = $item['expiry_date'] ?? '';
                    $nearExpiry   = false;
                    if ($expiry) {
                        try {
                            $expiryTs = strtotime($expiry);
                            $nearExpiry = $expiryTs !== false && $expiryTs < strtotime('+3 months');
                        } catch (\Throwable $e) {}
                    }

                    if ($isControlled) {
                        $rowBg     = '#fffbeb';
                        $rowBorder = '2px solid #f59e0b';
                    } elseif ($isHighAlert) {
                        $rowBg     = '#fff7ed';
                        $rowBorder = '2px solid #f97316';
                    } else {
                        $rowBg     = ($i % 2 === 0) ? '#f0fdf4' : '#fff';
                        $rowBorder = '1px solid #a7f3d0';
                    }
                @endphp
                <tr style="background:{{ $rowBg }};border-left:{{ $rowBorder }};">
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;font-weight:700;">{{ $i + 1 }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;font-weight:700;white-space:nowrap;">{{ $item['drug_name'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;font-style:italic;">{{ $item['generic_name'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;white-space:nowrap;">{{ $item['strength'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;">{{ $item['form'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;font-weight:700;">{{ $item['quantity_dispensed'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;">{{ $item['unit'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;font-family:monospace;font-size:10px;">{{ $item['batch_number'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;white-space:nowrap;">
                        <span style="{{ $nearExpiry ? 'color:#d97706;font-weight:700;' : '' }}">
                            {{ $expiry ?: '—' }}
                        </span>
                        @if($nearExpiry)
                            <span style="color:#d97706;font-size:9px;display:block;">&#9888; Near expiry</span>
                        @endif
                    </td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;font-size:10px;">{{ $item['manufacturer'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;">
                        <span style="background:{{ $storageBg }};color:#fff;padding:2px 6px;border-radius:10px;font-size:9px;white-space:nowrap;">
                            {{ $storage }}
                        </span>
                    </td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;font-size:10px;">
                        {{ $item['patient_counselling'] ?? '—' }}
                        @if($isControlled && !empty($item['serial_number']))
                            <br><span style="font-family:monospace;color:#92400e;font-size:9px;">S/N: {{ $item['serial_number'] }}</span>
                        @endif
                    </td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">
                        @if($isHighAlert)
                            <span style="background:#ea580c;color:#fff;padding:1px 5px;border-radius:4px;font-size:9px;display:block;margin-bottom:2px;">HIGH ALERT</span>
                        @endif
                        @if($isControlled)
                            <span style="background:#b45309;color:#fff;padding:1px 5px;border-radius:4px;font-size:9px;display:block;">CONTROLLED</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                {{-- Summary row --}}
                <tr style="background:#d1fae5;font-weight:700;font-size:12px;">
                    <td colspan="5" style="padding:6px 8px;border:1px solid #a7f3d0;text-align:right;">Total Items:</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;font-size:14px;">{{ $totalItems }}</td>
                    <td colspan="6" style="padding:6px 8px;border:1px solid #a7f3d0;color:#b45309;">
                        @if($controlledCount > 0)
                            Controlled substances: {{ $controlledCount }} item(s)
                        @endif
                    </td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;"></td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <p style="font-size:12px;color:#6b7280;font-style:italic;">No items recorded.</p>
    @endif
</div>

{{-- ── Section 3: Patient Counselling ── --}}
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <div style="flex:1;min-width:200px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:6px;padding:10px 14px;">
        <h3 style="font-size:11px;font-weight:700;color:#065f46;text-transform:uppercase;margin-bottom:8px;">Patient Counselling</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <span style="background:{{ $counselled ? '#15803d' : '#6b7280' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
                {{ $counselled ? 'Counselled' : 'Not Counselled' }}
            </span>
            @if($counselled)
                <span style="font-size:12px;"><strong>Language:</strong> {{ $counselLang }}</span>
                <span style="background:{{ $understood ? '#15803d' : '#d97706' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">
                    {{ $understood ? 'Understood' : 'Requires follow-up' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Section 4: Collection --}}
    <div style="flex:1;min-width:200px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:6px;padding:10px 14px;">
        <h3 style="font-size:11px;font-weight:700;color:#065f46;text-transform:uppercase;margin-bottom:8px;">Collection</h3>
        <div style="font-size:12px;">
            <strong>Collected by:</strong>
            <span style="background:#059669;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:4px;">
                {{ $collectionBy }}
            </span>
            @if($repName)
                <p style="margin:6px 0 0;"><strong>Representative:</strong> {{ $repName }}</p>
                <p style="margin:3px 0 0;font-size:11px;color:#6b7280;"><strong>ID:</strong> {{ $repId ?? '—' }}</p>
            @endif
        </div>
    </div>
</div>

{{-- ── Section 5: Dispensed By / Checked By ── --}}
<div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:20px;border:1px solid #a7f3d0;border-radius:6px;padding:12px 16px;background:#f0fdf4;">
    <div style="flex:1;min-width:160px;">
        <p style="font-size:10px;text-transform:uppercase;color:#065f46;font-weight:700;margin:0 0 4px;">Dispensed By</p>
        <p style="font-size:13px;font-weight:600;margin:0;">{{ $dispensedBy }}</p>
        <p style="font-size:11px;color:#6b7280;margin:2px 0;">Reg: {{ $dispensedByReg }}</p>
        <p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">Signature: _______________________</p>
    </div>
    <div style="flex:1;min-width:160px;">
        <p style="font-size:10px;text-transform:uppercase;color:#065f46;font-weight:700;margin:0 0 4px;">Checked By (2nd Pharmacist)</p>
        <p style="font-size:13px;font-weight:600;margin:0;">{{ $checkedBy }}</p>
        <p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">Signature: _______________________</p>
    </div>
    <div style="flex:1;min-width:140px;">
        <p style="font-size:10px;text-transform:uppercase;color:#065f46;font-weight:700;margin:0 0 4px;">Date / Time</p>
        <p style="font-size:12px;margin:0;">{{ $dispDate }}</p>
    </div>
</div>

{{-- ── Section 6: Patient / Representative Signature ── --}}
<div style="margin-bottom:20px;display:flex;align-items:flex-end;gap:40px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
        <div style="border-bottom:1px solid #374151;height:36px;margin-bottom:4px;"></div>
        <p style="font-size:11px;color:#6b7280;margin:0;">Patient / Authorized Representative Signature</p>
        <p style="font-size:10px;color:#9ca3af;margin:2px 0 0;">Date: _____________</p>
    </div>
</div>

{{-- ── Section 7: Pharmacovigilance Notice ── --}}
<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:10px 14px;">
    <p style="font-size:11px;color:#92400e;margin:0;">
        <strong>&#9888; Pharmacovigilance Notice:</strong>
        Please report any adverse drug reactions to the hospital pharmacist or the
        NAFDAC National Pharmacovigilance Centre.
        Early reporting helps ensure patient safety and drug quality monitoring across Cameroon.
    </p>
</div>
@endsection
