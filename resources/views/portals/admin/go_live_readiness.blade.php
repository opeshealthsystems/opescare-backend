{{-- Imported from the platform (portals/admin/go_live_readiness). Finder: /admin/facilities/{id}/readiness,
     keys in lang/{en,fr}/readiness.php, automatic checks + stored score snapshots added. --}}
@extends('layouts.portal')
@section('title', $facility->name . ' — ' . __('readiness.page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('onboarding.admin_portal.idx_title'))
@section('breadcrumb_home_url', route('portals.admin.onboarding'))
@section('breadcrumb_section', $facility->name)

@section('content')

@php
    $total = count($labels);
    $done  = collect($checklist)->filter(fn ($v) => $v === true)->count();
    $pct   = $total > 0 ? round($done / $total * 100) : 0;
@endphp

<div class="page-head">
    <h2><i data-lucide="rocket"></i> {{ $facility->name }} — {{ __('readiness.page_title') }}</h2>
    <span class="badge badge-neutral badge-sm">{{ $facility->type }}</span>
    <span class="badge {{ $readiness->can_go_live ? 'badge-success' : 'badge-danger' }}">{{ __('onboarding.admin_portal.status_'.$readiness->status) }}</span>
    <div class="page-head__spacer"></div>
    <form method="POST" action="{{ route('portals.admin.facilities.readiness.score', $facility->id) }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="refresh-cw"></i> {{ __('readiness.btn_compute') }}</button>
    </form>
    <a href="{{ route('portals.admin.onboarding.show', $facility->id) }}" class="btn btn-primary btn-sm"><i data-lucide="list-checks"></i> {{ __('readiness.btn_manage') }}</a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

@include('portals.admin._readiness_checks', ['automatic' => $automatic])

<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('readiness.latest_snapshot') }}</h3></div>
    <div class="panel-body">
        @if($latestScore)
            <table class="kv-table">
                <tr><td>{{ __('readiness.snap_overall') }}</td><td class="kv-strong">{{ (int) $latestScore->overall_score }}%</td></tr>
                <tr><td>{{ __('readiness.snap_staff') }}</td><td>{{ (int) $latestScore->staff_score }}%</td></tr>
                <tr><td>{{ __('readiness.snap_config') }}</td><td>{{ (int) $latestScore->config_score }}%</td></tr>
                <tr><td>{{ __('readiness.snap_data') }}</td><td>{{ (int) $latestScore->data_score }}%</td></tr>
                <tr><td>{{ __('readiness.snap_when') }}</td><td>{{ $latestScore->calculated_at?->format('d/m/Y H:i') }}</td></tr>
            </table>
        @else
            <p class="td-muted">{{ __('readiness.no_snapshot') }}</p>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="list-checks"></i> {{ __('readiness.section_checklist') }}</h3>
        <span class="td-muted">{{ $done }}/{{ $total }} ({{ $pct }}%)</span>
    </div>
    <div class="panel-body">
        <div class="checklist">
            @foreach ($labels as $key => $label)
                @php $ok = $checklist[$key] ?? false; @endphp
                <div class="checklist__item">
                    <span class="checklist__icon {{ $ok ? 'ok' : 'fail' }}">
                        <i data-lucide="{{ $ok ? 'check-circle-2' : 'x-circle' }}"></i>
                    </span>
                    <div class="checklist__body"><div class="checklist__title">{{ $label }}</div></div>
                    <span class="badge {{ $ok ? 'badge-success' : 'badge-danger' }} badge-sm">
                        {{ $ok ? __('readiness.done') : __('readiness.todo') }}
                    </span>
                </div>
            @endforeach
        </div>
        @if ($readiness->approval_note)
            <p class="mt-3"><strong>{{ __('onboarding.admin_portal.note') }}</strong> {{ $readiness->approval_note }}</p>
        @endif
    </div>
</div>

@endsection
