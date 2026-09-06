@extends('layouts.portal')

@section('title', __('family.alg_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.alg_title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $viewing = request()->attributes->get('viewing_patient');
    $tone = fn (?string $s) => in_array(strtolower((string) $s), ['severe', 'high', 'life-threatening', 'life_threatening', 'critical'], true) ? 'f-badge--danger' : (in_array(strtolower((string) $s), ['moderate', 'medium'], true) ? 'f-badge--warn' : 'pf-badge--muted');
@endphp

@section('content')
<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.alg_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.alg_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $allergies->isEmpty())
    <div class="f-card" style="text-align:center;padding:40px 20px;" data-section="allergies-empty">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 24])
        </div>
        <p class="f-muted" style="margin:0;">{{ __('family.alg_empty') }}</p>
    </div>
    @else
    <div class="f-card" data-section="allergies">
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_substance') }}</th>
                        <th>{{ __('family.col_severity') }}</th>
                        <th>{{ __('family.col_status') }}</th>
                        <th>{{ __('family.col_recorded') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($allergies as $a)
                    <tr>
                        <td style="font-weight:650;color:var(--f-ink);">{{ $a->substance }}</td>
                        <td><span class="f-badge {{ $tone($a->severity) }}">@enum($a->severity, 'severity')</span></td>
                        <td>@enum($a->status)</td>
                        <td>{{ $a->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
