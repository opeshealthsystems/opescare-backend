{{--
    Immunisation record — rendered when the patient asks to download it, never
    stored. See App\Services\Patient\PatientDocumentService.

    This is the document people are most often asked to produce — for a school,
    a border, an employer — so it lists every dose recorded rather than a
    summary, and it says on its face that a dose given elsewhere and never
    recorded here will not appear. A vaccination record that implies
    completeness it cannot know is the one that gets somebody turned away.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('document.imm_title'))

@php
    $dash  = __('document.not_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    $statusTone = fn (?string $status) => match (strtolower(trim((string) $status))) {
        'completed', 'administered', 'given' => 'ok',
        'due', 'scheduled'                   => 'warn',
        'not_given', 'refused', 'cancelled'  => 'mute',
        default                              => 'info',
    };
@endphp

@section('content')

<h1>{{ __('document.imm_title') }}</h1>
<p class="subtitle">{{ __('document.imm_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.sex') }}</td><td class="v">@if(filled($patient->sex))@enum($patient->sex, 'sex')@else{{ $dash }}@endif</td></tr>
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

<h2>{{ __('document.imm_title') }}</h2>
@if($doses->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>{{ __('document.imm_vaccine') }}</th>
            <th>{{ __('document.imm_dose') }}</th>
            <th>{{ __('document.imm_given') }}</th>
            <th>{{ __('doc_ex_imm.route_site') }}</th>
            <th>{{ __('document.imm_batch') }}</th>
            <th>{{ __('doc_ex_imm.given_by') }}</th>
            <th>{{ __('document.status') }}</th>
        </tr>
    </thead>
    @foreach($doses as $dose)
    <tr>
        <td><strong>{{ $dose->vaccine_name ?: $dash }}</strong>@if(filled($dose->vaccine_code))<br><span class="muted">{{ $dose->vaccine_code }}</span>@endif</td>
        <td class="muted">{{ $dose->dose_number ?: '' }}</td>
        <td>{{ $date($dose->administered_at) ?? $dash }}</td>
        <td class="muted">{{ trim(($dose->route ? \App\Support\Enums::label($dose->route, 'route') : '') . ($dose->route && $dose->site ? ' / ' : '') . ($dose->site ?: '')) }}</td>
        <td class="muted">{{ $dose->lot_number ?: '' }}</td>
        <td class="muted">{{ $dose->administeredBy?->name ?: '' }}@if($dose->facility?->name)<br>{{ $dose->facility->name }}@endif</td>
        <td>
            @if(filled($dose->status))
                <span class="pill {{ $statusTone($dose->status) }}">@enum($dose->status)</span>
            @endif
        </td>
    </tr>
    @endforeach
</table>
@else
<p class="muted">{{ __('document.imm_no_doses') }}</p>
@endif

<p class="note">{{ __('document.imm_completeness') }}</p>
<p class="note muted">{{ __('doc_ex_imm.not_icvp') }}</p>
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
