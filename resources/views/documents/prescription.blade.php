{{--
    Prescription — rendered when the patient asks to download it, never stored.
    See App\Services\Patient\PatientDocumentService.

    A prescription is a legal instrument, so this sheet says plainly what it is
    and what it is not: it records what was written, and it is not evidence that
    anything was supplied. A voided or amended script still prints, carrying its
    status, because a record that quietly drops a corrected script is not a
    record.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('document.rx_title'))

@php
    $dash  = __('document.not_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    $statusTone = fn (?string $status) => match (strtolower(trim((string) $status))) {
        'active', 'dispensed'                            => 'ok',
        'partially_dispensed', 'pending'                 => 'warn',
        'cancelled', 'expired', 'voided', 'entered_in_error' => 'mute',
        default                                          => 'info',
    };

    $prescriber = optional($prescription->prescriber)->name
        ?? \App\Models\User::find($prescription->prescribed_by)?->name;
@endphp

@section('content')

<h1>{{ __('document.rx_title') }}</h1>
<p class="subtitle">{{ __('document.rx_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.reference') }}</td><td class="v id">{{ $prescription->reference ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.rx_prescriber') }}</td><td class="v">{{ $prescriber ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.rx_prescribed') }}</td><td class="v">{{ $date($prescription->prescribed_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.rx_expires') }}</td><td class="v">{{ $date($prescription->expires_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.facility') }}</td><td class="v">{{ $prescription->facility->name ?? $dash }}</td></tr>
    <tr>
        <td class="k">{{ __('document.status') }}</td>
        <td class="v"><span class="pill {{ $statusTone($prescription->status) }}">@enum($prescription->status)</span></td>
    </tr>
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

<h2>{{ __('document.rx_drug') }}</h2>
@if($prescription->items && $prescription->items->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>{{ __('document.rx_drug') }}</th>
            <th>{{ __('document.rx_dose') }}</th>
            <th>{{ __('document.rx_route') }}</th>
            <th>{{ __('document.rx_frequency') }}</th>
            <th>{{ __('document.rx_duration') }}</th>
            <th>{{ __('document.rx_quantity') }}</th>
        </tr>
    </thead>
    @foreach($prescription->items as $item)
    <tr>
        <td><strong>{{ $item->drug_name ?: $dash }}</strong></td>
        <td>{{ $item->dose ?: $dash }}</td>
        <td>{{ $item->route ?: '' }}</td>
        <td>{{ $item->frequency ?: $dash }}</td>
        <td class="muted">{{ $item->duration_days ? __('document.rx_days', ['count' => $item->duration_days]) : '' }}</td>
        <td class="muted">{{ $item->quantity ?: '' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="muted">{{ __('document.rx_no_items') }}</p>
@endif

@if($prescription->dispensed_at)
<h2>{{ __('document.rx_dispensed') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.rx_dispensed') }}</td><td class="v">{{ $date($prescription->dispensed_at) }}</td></tr>
    <tr><td class="k">{{ __('document.rx_pharmacy') }}</td><td class="v">{{ $prescription->dispensing_pharmacy_name ?: $dash }}</td></tr>
</table>
@endif

<p class="note">{{ __('document.rx_not_a_supply') }}</p>
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
