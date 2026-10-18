@extends('layouts.portal')

@section('title', __('family.imm_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.imm_title'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php $viewing = request()->attributes->get('viewing_patient'); @endphp

@section('content')
<div class="pf pf--wide">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.imm_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.imm_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $immunizations->isEmpty())
    <div class="f-card ods-99e8a36" data-section="immunizations-empty">
        <div class="pf-qa__icon ods-4ef0061">
            <i class="ods-f70f93d" data-lucide="syringe" aria-hidden="true"></i>
        </div>
        <p class="f-muted ods-1386d50">{{ __('family.imm_empty') }}</p>
    </div>
    @else
    <div class="f-card" data-section="immunizations">
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_vaccine') }}</th>
                        <th>{{ __('family.col_date') }}</th>
                        <th>{{ __('family.col_dose') }}</th>
                        <th>{{ __('family.col_lot') }}</th>
                        <th>{{ __('family.col_facility') }}</th>
                        <th>{{ __('family.col_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($immunizations as $shot)
                    <tr>
                        <td class="ods-749274d">{{ $shot->vaccine_name ?? $shot->vaccine_code ?? '—' }}</td>
                        <td>{{ $shot->administered_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td>{{ $shot->dose_number ?? $shot->dose_sequence ?? '—' }}</td>
                        <td class="ods-9709411">{{ $shot->lot_number ?? '—' }}</td>
                        <td>{{ $shot->facility?->name ?? '—' }}</td>
                        <td><span class="f-badge {{ ($shot->status ?? 'completed') === 'completed' ? 'f-badge--good' : 'pf-badge--muted' }}">@enum($shot->status ?? 'completed')</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
