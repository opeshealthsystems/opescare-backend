{{-- 21 Consultation / encounter report. docs/specs/documents/21-encounter-report.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.enc_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
    $rxItems = $prescriptions->flatMap->items;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.enc_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.enc_subtitle') }}</p>

<h2>{{ __('doc_new_docs.section_patient') }}</h2>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.enc_encounter') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facility])
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.enc_type') }}</td><td class="v">{{ $val($encounter->encounter_type) ?: $dash }}@if($encounter->encounter_class) ({{ $val($encounter->encounter_class) }})@endif</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $val($encounter->status) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_started') }}</td><td class="v">{{ $stamp($encounter->started_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_ended') }}</td><td class="v">{{ $stamp($encounter->ended_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_reason') }}</td><td class="v">{{ $encounter->reason_for_encounter ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_disposition') }}</td><td class="v">{{ $val($encounter->discharge_disposition) ?: $dash }}</td></tr>
</table>

<h2>{{ __('doc_new_docs.enc_findings') }}</h2>
@forelse($notes as $note)
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.enc_history') }}</td><td class="v">{{ $note->history_of_present_illness ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_exam') }}</td><td class="v">{{ $note->examination_findings ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_plan') }}</td><td class="v">{{ $note->treatment_plan ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.enc_signed') }}</td><td class="v">{{ $stamp($note->signed_at) }}</td></tr>
</table>
@empty
<p class="empty">{{ __('doc_new_docs.enc_no_notes') }}</p>
@endforelse

<h2>{{ __('doc_new_docs.enc_diagnoses') }}</h2>
@if($diagnoses->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.enc_code') }}</th><th>{{ __('doc_new_docs.enc_diagnosis') }}</th><th>{{ __('doc_new_docs.status') }}</th></tr></thead>
    @foreach($diagnoses as $d)
    <tr><td>{{ $d->code_system }} {{ $d->code }}</td><td>{{ $d->display_name ?: $dash }}@if($d->is_primary) <span class="pill info">{{ __('doc_new_docs.enc_primary') }}</span>@endif</td><td>{{ $val($d->status) ?: $dash }}</td></tr>
    @endforeach
</table>
@endif

<h2>{{ __('doc_new_docs.enc_orders') }}</h2>
@if($labOrders->isEmpty() && $imagingOrders->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.enc_order') }}</th><th>{{ __('doc_new_docs.status') }}</th></tr></thead>
    @foreach($labOrders as $o)<tr><td>{{ __('doc_new_docs.enc_lab') }}: {{ $o->test_name }}</td><td>{{ $val($o->status) }}</td></tr>@endforeach
    @foreach($imagingOrders as $o)<tr><td>{{ __('doc_new_docs.enc_imaging') }}: {{ $o->modality }} {{ $o->body_part }}</td><td>{{ $val($o->status) }}</td></tr>@endforeach
</table>
@endif

<h2>{{ __('doc_new_docs.enc_prescriptions') }}</h2>
@if($rxItems->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.medicine') }}</th><th>{{ __('doc_new_docs.dose') }}</th><th>{{ __('doc_new_docs.frequency') }}</th><th>{{ __('doc_new_docs.duration') }}</th></tr></thead>
    @foreach($rxItems as $i)
    <tr><td>{{ $i->drug_name }}</td><td>{{ $i->dose ?: $dash }}</td><td>{{ $i->frequency ?: $dash }}</td><td>{{ $i->duration_days ? __('doc_new_docs.days', ['count' => $i->duration_days]) : $dash }}</td></tr>
    @endforeach
</table>
@endif

@include('pdf.layouts.secure-signature', ['name' => $attending?->name, 'role' => __('doc_new_docs.enc_attending'), 'date' => $encounter->ended_at])
@endsection
