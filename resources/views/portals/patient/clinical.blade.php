@extends('layouts.portal')

@section('title', __('family.cl_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.cl_title'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php $viewing = request()->attributes->get('viewing_patient'); @endphp

@section('content')
<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.cl_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.cl_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $conditions->isEmpty())
    <div class="f-card ods-99e8a36" data-section="conditions-empty">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'stethoscope', 'size' => 24])
        </div>
        <p class="f-muted ods-1386d50">{{ __('family.cl_empty') }}</p>
    </div>
    @else
    <div class="f-card" data-section="conditions">
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_condition') }}</th>
                        <th>{{ __('family.col_code') }}</th>
                        <th>{{ __('family.col_status') }}</th>
                        <th>{{ __('family.col_primary') }}</th>
                        <th>{{ __('family.col_recorded') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($conditions as $c)
                    <tr>
                        <td class="ods-749274d">{{ $c->display_name ?? $c->snomed_display ?? '—' }}</td>
                        <td class="ods-9709411">{{ $c->code ?? $c->snomed_code ?? '—' }}</td>
                        <td><span class="f-badge {{ in_array($c->status, ['active', 'chronic'], true) ? 'f-badge--warn' : 'pf-badge--muted' }}">@enum($c->status)</span></td>
                        <td>{{ $c->is_primary ? __('family.yes') : '—' }}</td>
                        <td>{{ $c->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
