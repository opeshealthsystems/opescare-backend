{{-- 25 Emergency summary. Emergency-profile page verbatim + recorded emergency contact. docs/specs/documents/25-emergency-summary.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.em_title'))
@section('head')
<style>.cut { border: .6mm dashed #0F4C81; padding: 4mm; width: 85.6mm; } .cut .n { font-size: 13px; font-weight: bold; color: #0E1729; } .cut .a { color: #9B1C1C; font-weight: bold; }</style>
@endsection
@php
    $dash = __('doc_new_docs.not_recorded');
    $allergySection = collect($page['sections'] ?? [])->first(fn ($s) => ($s['layout'] ?? '') === 'table' && ($s['icon'] ?? '') === 'alert-triangle');
    $allergens = collect($allergySection['rows'] ?? [])->map(fn ($r) => is_array($r[0] ?? null) ? ($r[0]['v'] ?? '') : '')->filter()->implode(', ');
    $c = fn ($k) => is_scalar($contact[$k] ?? null) && $contact[$k] !== '' ? $contact[$k] : $dash;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.em_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.em_subtitle') }}</p>

<div class="cut">
    <div class="n">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</div>
    <div>{{ __('doc_new_docs.health_id') }}: <strong>{{ $patient->health_id ?? $dash }}</strong></div>
    <div class="a">{{ __('doc_new_docs.em_allergies') }}: {{ $allergens !== '' ? $allergens : __('doc_new_docs.none_recorded') }}</div>
    <div class="muted">{{ __('doc_new_docs.em_cut') }}</div>
</div>

<h2>{{ __('doc_new_docs.em_contact') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.em_contact_name') }}</td><td class="v">{{ $c('name') }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.em_contact_phone') }}</td><td class="v">{{ $c('phone') }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.em_contact_relationship') }}</td><td class="v">{{ $c('relationship') }}</td></tr>
</table>

@include('documents.new._sections', ['sections' => $page['sections'] ?? []])
<p class="note">{{ __('doc_new_docs.em_no_blood_group') }}</p>
@endsection
