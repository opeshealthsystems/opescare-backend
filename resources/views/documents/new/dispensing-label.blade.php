{{-- 27 Dispensing label, 100 x 50 mm, one per dispensed item. Not on the secure sheet. docs/specs/documents/27-dispensing-label.md --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ __('doc_new_docs.lbl_title') }}</title>
<style>
    @page { margin: 3mm; }
    body { margin: 0; font-family: "DejaVu Sans", sans-serif; font-size: 8px; color: #000; }
    .label { page-break-after: always; }
    .label.last { page-break-after: auto; }
    .top { border-bottom: .4mm solid #0F4C81; padding-bottom: 1mm; font-size: 7px; }
    .top b { color: #0F4C81; }
    .drug { font-size: 11px; font-weight: bold; margin-top: 1.5mm; }
    .dir { font-size: 9px; margin-top: 1mm; }
    .meta { font-size: 7px; margin-top: 1.2mm; }
</style>
</head>
<body>
@php $dash = __('doc_new_docs.not_recorded'); @endphp
@foreach($items as $item)
<div class="label{{ $loop->last ? ' last' : '' }}">
    <div class="top"><b>{{ $facility->name }}</b> — {{ __('doc_new_docs.lbl_rx') }} {{ $reference ?? $dash }}</div>
    <div class="drug">{{ $item->displayName() }}</div>
    <div class="dir">{{ $item->dose ?: $dash }} — {{ $item->frequency ?: $dash }}@if($item->route) — {{ $item->route }}@endif</div>
    <div class="dir">{{ __('doc_new_docs.duration') }}: {{ $item->duration_days ? __('doc_new_docs.days', ['count' => $item->duration_days]) : $dash }} · {{ __('doc_new_docs.lbl_qty') }}: {{ $item->quantity ?? $dash }}</div>
    <div class="meta">{{ __('doc_new_docs.patient') }}: <b>{{ $patient ? trim($patient->first_name . ' ' . $patient->last_name) : $dash }}</b> · {{ $patient?->health_id }}</div>
    <div class="meta">{{ __('doc_new_docs.lbl_dispensed') }}: {{ $item->dispensed_at?->translatedFormat('d M Y') }} · {{ __('doc_new_docs.lbl_keep') }}</div>
</div>
@endforeach
</body>
</html>
