@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_careplan_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_careplan_breadcrumb', [], $l))

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_careplan_title', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('public.pat_careplan_subtitle', [], $l) }}</p>
    </div>
</div>

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@elseif($carePlans->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
        <h3>{{ __('public.pat_careplan_empty_title', [], $l) }}</h3>
        <p>{{ __('public.pat_careplan_empty_desc', [], $l) }}</p>
    </div>
</div>
@else
@foreach($carePlans as $plan)
@php
    $totalGoals    = $plan->goals->count();
    $achievedGoals = $plan->goals->where('status', 'achieved')->count();
    $progressPct   = $totalGoals > 0 ? (int) round(($achievedGoals / $totalGoals) * 100) : 0;
@endphp
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="clipboard-list"></i> {{ $plan->title ?: '—' }}</h2>
        <span class="badge badge-primary">{{ $progressPct }}%</span>
    </div>
    <div class="panel-body">
        @if($plan->description)
        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ $plan->description }}</p>
        @endif
        <p class="td-muted" style="margin:0 0 1rem;">
            <i data-lucide="calendar" style="width:.9rem;height:.9rem;display:inline;vertical-align:middle;"></i>
            {{ __('public.pat_careplan_period', [], $l) }}:
            {{ $plan->start_date?->isoFormat('LL') ?? '—' }} —
            {{ $plan->end_date?->isoFormat('LL') ?? __('public.pat_careplan_ongoing', [], $l) }}
        </p>

        <h3 style="font-size:.95rem;font-weight:700;margin:1rem 0 .5rem;color:#0F4C81;">
            <i data-lucide="target" style="width:1rem;height:1rem;display:inline;vertical-align:middle;"></i>
            {{ __('public.pat_careplan_goals', [], $l) }} ({{ $totalGoals }})
        </h3>
        @if($plan->goals->isEmpty())
            <p class="td-muted" style="margin:0 0 1rem;">{{ __('public.pat_careplan_no_goals', [], $l) }}</p>
        @else
            <ul style="list-style:none;margin:0 0 1rem;padding:0;display:grid;gap:.4rem;">
                @foreach($plan->goals as $goal)
                @php
                    $goalBadge = match($goal->status) {
                        'achieved'    => 'badge-success',
                        'in_progress' => 'badge-info',
                        'cancelled'   => 'badge-danger',
                        default       => 'badge',
                    };
                @endphp
                <li style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;">
                    <span class="badge {{ $goalBadge }}">@enum($goal->status ?? 'pending')</span>
                    <span class="td-strong">{{ $goal->goal_text ?: '—' }}</span>
                    @if($goal->target_date)
                        <span class="td-muted">· {{ __('public.pat_careplan_target', [], $l) }}: {{ $goal->target_date->isoFormat('LL') }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
        @endif

        <h3 style="font-size:.95rem;font-weight:700;margin:1rem 0 .5rem;color:#0F4C81;">
            <i data-lucide="list-checks" style="width:1rem;height:1rem;display:inline;vertical-align:middle;"></i>
            {{ __('public.pat_careplan_interventions', [], $l) }} ({{ $plan->interventions->count() }})
        </h3>
        @if($plan->interventions->isEmpty())
            <p class="td-muted" style="margin:0;">{{ __('public.pat_careplan_no_interventions', [], $l) }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.pat_careplan_interventions', [], $l) }}</th>
                            <th>{{ __('public.pat_careplan_frequency', [], $l) }}</th>
                            <th>{{ __('public.pat_careplan_responsible', [], $l) }}</th>
                            <th>{{ __('public.pat_referral_col_status', [], $l) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan->interventions as $iv)
                        <tr>
                            <td data-label="{{ __('public.pat_careplan_interventions', [], $l) }}">
                                <span class="td-strong">{{ $iv->description ?: ($iv->intervention_type ?? '—') }}</span>
                            </td>
                            <td data-label="{{ __('public.pat_careplan_frequency', [], $l) }}"><span class="td-muted">{{ $iv->frequency ?? '—' }}</span></td>
                            <td data-label="{{ __('public.pat_careplan_responsible', [], $l) }}"><span class="td-muted">{{ $iv->responsible_party ?? '—' }}</span></td>
                            <td data-label="{{ __('public.pat_referral_col_status', [], $l) }}">
                                <span class="badge">@enum($iv->status ?? 'active')</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endforeach
@endif

@endsection
