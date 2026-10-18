@extends('layouts.portal')

@section('title', __('family.cp_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.cp_title'))

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
            <h1>{{ __('family.cp_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.cp_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $carePlans->isEmpty())
    <div class="f-card ods-99e8a36" data-section="care-plans-empty">
        <div class="pf-qa__icon ods-4ef0061">
            <i class="ods-f70f93d" data-lucide="clipboard-list" aria-hidden="true"></i>
        </div>
        <p class="f-muted ods-1386d50">{{ __('family.cp_empty') }}</p>
    </div>
    @else
    <div class="pf-stack" data-section="care-plans">
        @foreach($carePlans as $plan)
        @php
            $goals    = $plan->goals ?? collect();
            $achieved = $goals->where('status', 'achieved')->count();
            $pct      = $goals->count() ? (int) round($achieved / $goals->count() * 100) : 0;
        @endphp
        <div class="f-card">
            <h2 class="pf-card-head">
                <i class="ods-50a4375" data-lucide="clipboard-list" aria-hidden="true"></i>
                {{ $plan->title }}
                <span class="f-badge f-badge--good">@enum($plan->status)</span>
            </h2>
            @if($plan->description)<p class="f-muted f-small ods-4c7b292">{{ $plan->description }}</p>@endif
            <p class="f-small f-muted ods-0998cfa">
                {{ __('family.cp_period', ['from' => $plan->start_date?->translatedFormat('d M Y') ?? '—', 'to' => $plan->end_date?->translatedFormat('d M Y') ?? __('family.cp_ongoing')]) }}
                @if($plan->facility?->name) &middot; {{ $plan->facility->name }} @endif
            </p>
            @if($goals->isNotEmpty())
            <progress class="odpt-progress odpt-progress--plain" max="100" value="{{ $pct }}">{{ $pct }}%</progress>
            <h3 class="f-small ods-a74d077">{{ __('family.cp_goals') }}</h3>
            <ul class="ods-248b6a9">
                @foreach($goals as $g)
                <li class="ods-a58b070">{{ $g->goal_text }} <span class="f-badge {{ $g->status === 'achieved' ? 'f-badge--good' : 'pf-badge--muted' }}">@enum($g->status)</span>
                    @if($g->target_date)<span class="f-small f-muted"> &middot; {{ $g->target_date->translatedFormat('d M Y') }}</span>@endif
                </li>
                @endforeach
            </ul>
            @endif
            @if(($plan->interventions ?? collect())->isNotEmpty())
            <h3 class="f-small ods-a74d077">{{ __('family.cp_interventions') }}</h3>
            <ul class="ods-72af536">
                @foreach($plan->interventions as $i)
                <li class="ods-a58b070">{{ $i->description }}@if($i->frequency) <span class="f-small f-muted">&middot; {{ $i->frequency }}</span>@endif</li>
                @endforeach
            </ul>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
