{{-- Imported from the platform (portals/admin/onboarding/show). Finder: keys in onboarding.admin_portal.*,
     the automatic readiness checks are shown and must all pass before approval. --}}
@extends('layouts.portal')
@section('title', $facility->name . ' — ' . __('onboarding.admin_portal.show_title_suffix'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('onboarding.admin_portal.idx_title'))
@section('breadcrumb_home_url', route('portals.admin.onboarding'))
@section('breadcrumb_section', $facility->name)

@section('content')

@php
    $statusColor = match($readiness->status) {
        'approved' => 'success', 'ready_for_approval' => 'info', default => 'warning',
    };
@endphp

<div class="page-head">
    <h2><i data-lucide="rocket"></i> {{ $facility->name }}</h2>
    <span class="badge badge--{{ $statusColor }} badge-sm">{{ __('onboarding.admin_portal.status_'.$readiness->status) }}</span>
    <span class="td-muted text-sm">{{ __('onboarding.admin_portal.items_completed', ['done' => $completedCount, 'total' => $totalCount]) }}</span>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.facilities.readiness', $facility->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="gauge"></i> {{ __('onboarding.admin_portal.btn_readiness') }}</a>
    @if($readiness->status === 'approved')
    <span class="badge badge-success">
        <i data-lucide="check-circle-2"></i> {{ __('onboarding.admin_portal.badge_golive_approved') }}
    </span>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Progress --}}
@php $pct = $totalCount > 0 ? round($completedCount / $totalCount * 100) : 0; @endphp
<div class="panel mb-6">
    <div class="panel-body">
        <div class="flex-between mb-3">
            <span class="kv-strong">{{ __('onboarding.admin_portal.progress_label') }}</span>
            <span class="kv-strong">{{ $pct }}%</span>
        </div>
        <div class="breakdown__track breakdown__track--lg">
            <div class="breakdown__fill {{ $pct == 100 ? 'breakdown__fill--teal' : '' }}" style="width: {{ $pct }}%"></div>
        </div>
        @if(!empty($missingItems))
        <p class="text-sm trend-down mt-3">{{ __('onboarding.admin_portal.remaining') }} {{ implode(', ', array_map(fn ($k) => $labels[$k] ?? $k, $missingItems)) }}</p>
        @endif
    </div>
</div>

{{-- Automatic Finder readiness checks --}}
@include('portals.admin._readiness_checks', ['automatic' => $automatic])

{{-- Checklist --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="list-checks"></i> {{ __('onboarding.admin_portal.checklist_title') }}</h3>
    </div>
    <div class="panel-body">
        <div class="checklist">
            @foreach($labels as $key => $label)
                @php $done = $checklist[$key] ?? false; @endphp
                <div class="checklist__item">
                    <span class="checklist__icon {{ $done ? 'ok' : '' }}">
                        <i data-lucide="{{ $done ? 'check-circle-2' : 'circle' }}"></i>
                    </span>
                    <div class="checklist__body">
                        <div class="checklist__title {{ $done ? 'checklist__title--done' : '' }}">{{ $label }}</div>
                    </div>
                    @if($readiness->status !== 'approved' && $key !== 'go_live_approval_recorded')
                    <form method="POST" action="{{ route('portals.admin.onboarding.mark', $facility->id) }}" class="inline-form">
                        @csrf
                        <input type="hidden" name="item" value="{{ $key }}">
                        <input type="hidden" name="complete" value="{{ $done ? '0' : '1' }}">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            {{ $done ? __('onboarding.admin_portal.btn_mark_incomplete') : __('onboarding.admin_portal.btn_mark_complete') }}
                        </button>
                    </form>
                    @elseif($readiness->status === 'approved')
                    <span class="td-muted text-sm">{{ __('onboarding.admin_portal.locked') }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Approval info --}}
@if($readiness->approved_at)
<div class="panel mt-6">
    <div class="panel-body">
        <h3 class="panel-title"><i data-lucide="check-circle-2"></i> {{ __('onboarding.admin_portal.approved_head') }}</h3>
        <table class="kv-table">
            <tr><td>{{ __('onboarding.admin_portal.approved_on') }}</td><td class="kv-strong">{{ $readiness->approved_at->format('d/m/Y H:i') }}</td></tr>
            @if($readiness->approval_note)
            <tr><td>{{ __('onboarding.admin_portal.note') }}</td><td class="kv-strong">{{ $readiness->approval_note }}</td></tr>
            @endif
        </table>
    </div>
</div>
@endif

{{-- Risks --}}
@if(!empty($risks))
<div class="alert alert-warning mt-6">
    <i data-lucide="alert-triangle"></i>
    <div>
        <strong>{{ __('onboarding.admin_portal.risks_label') }}</strong>
        <ul class="alert-list">
            @foreach($risks as $risk)
            <li>{{ $risk }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- Approve go-live (inline form; the platform used a modal) --}}
@if($readiness->status === 'ready_for_approval')
<div class="panel mt-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="check-circle"></i> {{ __('onboarding.admin_portal.modal_title') }}</h3></div>
    <div class="panel-body">
        <p class="td-muted text-sm mb-4">{{ __('onboarding.admin_portal.modal_desc', ['name' => $facility->name]) }}</p>
        <form method="POST" action="{{ route('portals.admin.onboarding.approve', $facility->id) }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="approval_note">{{ __('onboarding.admin_portal.modal_lbl_note') }}</label>
                <textarea id="approval_note" name="approval_note" rows="4" required class="form-control"
                          placeholder="{{ __('onboarding.admin_portal.modal_ph_note') }}"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" @disabled(! empty($automatic['missing']))>{{ __('onboarding.admin_portal.modal_btn_confirm') }}</button>
        </form>
    </div>
</div>
@endif

@endsection
