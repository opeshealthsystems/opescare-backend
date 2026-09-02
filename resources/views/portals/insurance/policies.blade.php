@extends('layouts.portal')
@php $l = app()->getLocale(); @endphp

@section('title', __('public.insurance_portal.policies_page_title', [], $l) ?: 'Patient Insurance Policies')

@section('sidebar_role_badge')
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    {{ __('public.insurance_portal.role_badge', [], $l) ?: 'Insurance' }}
</div>
@endsection
@section('sidebar_user_role', __('public.insurance_portal.role_label', [], $l) ?: 'Insurance Admin')

@section('sidebar_nav')
@include('portals.insurance._sidebar_nav')
@endsection

@section('breadcrumb_home', __('public.insurance_portal.page_title', [], $l) ?: 'Insurance Portal')
@section('breadcrumb_home_url', route('portals.insurance.dashboard'))
@section('breadcrumb_section', __('public.insurance_portal.policies_breadcrumb', [], $l) ?: 'Patient Policies')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.insurance_portal.policies_page_title', [], $l) ?: 'Patient Insurance Policies' }}</h1>
        <p class="page-subtitle">{{ __('public.insurance_portal.policies_subtitle', [], $l) ?: 'Register and manage patient insurance coverage.' }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openPolicyModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.insurance_portal.policies_btn_register', [], $l) ?: 'Register Policy' }}
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4">
        <i data-lucide="check-circle"></i><div>{{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('portals.insurance.policies') }}" class="filter-bar">
    <select name="status" class="filter-select">
        <option value="">{{ __('public.insurance_portal.policies_filter_all_statuses', [], $l) ?: 'All Statuses' }}</option>
        @foreach(['pending','active','inactive','expired','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords($s) }}</option>
        @endforeach
    </select>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="patient_id" placeholder="{{ __('public.insurance_portal.policies_ph_patient_id', [], $l) ?: 'Patient ID…' }}" value="{{ request('patient_id') }}">
    </label>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.insurance_portal.policies_btn_filter', [], $l) ?: 'Filter' }}
    </button>
    <a href="{{ route('portals.insurance.policies') }}" class="btn btn-ghost btn-sm">{{ __('public.insurance_portal.policies_btn_clear', [], $l) ?: 'Clear' }}</a>
</form>

<div class="panel">
    <div class="panel-body--flush">
        @if(count($policies) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('public.insurance_portal.policies_empty_title', [], $l) ?: 'No Patient Policies' }}</h3>
                <p>{{ __('public.insurance_portal.policies_empty_desc', [], $l) ?: 'Register a patient insurance policy to begin tracking coverage.' }}</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="openPolicyModal()">
                    {{ __('public.insurance_portal.policies_btn_register', [], $l) ?: 'Register Policy' }}
                </button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.insurance_portal.policies_col_patient', [], $l) ?: 'Patient' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_provider_plan', [], $l) ?: 'Provider / Plan' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_policy_no', [], $l) ?: 'Policy #' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_member_id', [], $l) ?: 'Member ID' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_expiry', [], $l) ?: 'Expiry' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_eligibility', [], $l) ?: 'Eligibility' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_status', [], $l) ?: 'Status' }}</th>
                            <th>{{ __('public.insurance_portal.policies_col_actions', [], $l) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policies as $policy)
                        @php
                            $statusBadge = match($policy->status) {
                                'active'   => 'badge-success',
                                'pending'  => 'badge-warning',
                                'expired'  => 'badge-danger',
                                'cancelled'=> 'badge-neutral',
                                default    => 'badge-neutral',
                            };
                            $eligibility = $policy->latestEligibility;
                            $eligBadge = match($eligibility->status ?? '') {
                                'eligible'     => 'badge-success',
                                'not_eligible' => 'badge-danger',
                                'expired'      => 'badge-warning',
                                default        => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.insurance_portal.policies_col_patient', [], $l) ?: 'Patient' }}">
                                <span class="td-mono">{{ $policy->patient_id }}</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_provider_plan', [], $l) ?: 'Provider / Plan' }}">
                                <span class="td-strong">{{ $policy->plan->provider->name ?? '--' }}</span>
                                <div class="td-muted">{{ $policy->plan->name ?? '--' }}</div>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_policy_no', [], $l) ?: 'Policy #' }}">
                                <span class="td-mono">{{ $policy->policy_number }}</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_member_id', [], $l) ?: 'Member ID' }}">{{ $policy->member_id ?? '--' }}</td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_expiry', [], $l) ?: 'Expiry' }}">
                                {{ $policy->expiry_date ? $policy->expiry_date->format('M d, Y') : '--' }}
                            </td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_eligibility', [], $l) ?: 'Eligibility' }}">
                                @if($eligibility)
                                    <span class="badge {{ $eligBadge }}">@enum($eligibility->status)</span>
                                @else
                                    <span class="badge badge-neutral">{{ __('public.insurance_portal.policies_lbl_not_checked', [], $l) ?: 'Not Checked' }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_status', [], $l) ?: 'Status' }}">
                                <span class="badge {{ $statusBadge }}">@enum($policy->status)</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.policies_col_actions', [], $l) ?: 'Actions' }}" class="row-actions">
                                {{-- Eligibility check --}}
                                <button type="button" class="btn btn-ghost btn-sm" onclick="openEligModal('{{ $policy->id }}')">
                                    <i data-lucide="activity"></i> {{ __('public.insurance_portal.policies_btn_check', [], $l) ?: 'Check' }}
                                </button>
                                @if($policy->status === 'pending')
                                    <form method="POST" action="{{ route('portals.insurance.policies.activate', $policy->id) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i data-lucide="check-circle"></i> {{ __('public.insurance_portal.policies_btn_activate', [], $l) ?: 'Activate' }}
                                        </button>
                                    </form>
                                @elseif($policy->status === 'active')
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="openDeactivateModal('{{ $policy->id }}')">
                                        <i data-lucide="pause-circle"></i> {{ __('public.insurance_portal.policies_btn_deactivate', [], $l) ?: 'Deactivate' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Register Policy Modal --}}
<div id="policy-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--lg">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.policies_modal_register_title', [], $l) ?: 'Register Patient Insurance Policy' }}</h3></div>
        <form method="POST" action="{{ route('portals.insurance.policies.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.policies_lbl_patient', [], $l) ?: 'Patient' }}</label>
                @if(count($patients) > 0)
                    <select name="patient_id" class="form-control" required>
                        <option value="">{{ __('public.insurance_portal.policies_opt_select_patient', [], $l) ?: '— Select Patient —' }}</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}">
                                {{ $p->health_id ?? $p->id }} ({{ $p->first_name ?? '' }} {{ $p->last_name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="patient_id" class="form-control" required placeholder="{{ __('public.insurance_portal.policies_ph_patient_id_input', [], $l) ?: 'Patient ID' }}">
                @endif
            </div>
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.policies_lbl_plan', [], $l) ?: 'Insurance Plan' }}</label>
                @if(count($plans) > 0)
                    <select name="insurance_plan_id" class="form-control" required>
                        <option value="">{{ __('public.insurance_portal.policies_opt_select_plan', [], $l) ?: '— Select Plan —' }}</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">
                                {{ $plan->provider->name ?? '' }} — {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <p class="form-hint">{{ __('public.insurance_portal.policies_hint_no_plans', [], $l) ?: 'No active plans found. Add a provider and plan first.' }}</p>
                    <input type="text" name="insurance_plan_id" class="form-control" required placeholder="{{ __('public.insurance_portal.policies_ph_plan_id', [], $l) ?: 'Plan ID' }}">
                @endif
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.insurance_portal.policies_lbl_policy_number', [], $l) ?: 'Policy Number' }}</label>
                    <input type="text" name="policy_number" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.policies_lbl_member_id_field', [], $l) ?: 'Member ID' }}</label>
                    <input type="text" name="member_id" class="form-control" maxlength="100">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.policies_lbl_effective_date', [], $l) ?: 'Effective Date' }}</label>
                    <input type="date" name="effective_date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.policies_lbl_expiry_date', [], $l) ?: 'Expiry Date' }}</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.policies_lbl_relationship', [], $l) ?: 'Relationship to Primary' }}</label>
                <select name="relationship_to_primary" class="form-control">
                    <option value="self">{{ __('public.insurance_portal.policies_opt_self', [], $l) ?: 'Self' }}</option>
                    <option value="spouse">{{ __('public.insurance_portal.policies_opt_spouse', [], $l) ?: 'Spouse' }}</option>
                    <option value="child">{{ __('public.insurance_portal.policies_opt_child', [], $l) ?: 'Child' }}</option>
                    <option value="other">{{ __('public.insurance_portal.policies_opt_other', [], $l) ?: 'Other' }}</option>
                </select>
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closePolicyModal()">{{ __('public.insurance_portal.policies_btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="shield-check"></i> {{ __('public.insurance_portal.policies_btn_register_submit', [], $l) ?: 'Register Policy' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Eligibility Check Modal --}}
<div id="elig-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.policies_modal_elig_title', [], $l) ?: 'Eligibility Check' }}</h3></div>
        <form id="elig-form" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.policies_lbl_elig_result', [], $l) ?: 'Eligibility Result' }}</label>
                <select name="status" class="form-control" required>
                    <option value="eligible">{{ __('public.insurance_portal.policies_opt_eligible', [], $l) ?: 'Eligible' }}</option>
                    <option value="not_eligible">{{ __('public.insurance_portal.policies_opt_not_eligible', [], $l) ?: 'Not Eligible' }}</option>
                    <option value="unknown">{{ __('public.insurance_portal.policies_opt_unknown', [], $l) ?: 'Unknown' }}</option>
                    <option value="expired">{{ __('public.insurance_portal.policies_opt_expired', [], $l) ?: 'Expired' }}</option>
                    <option value="failed">{{ __('public.insurance_portal.policies_opt_failed', [], $l) ?: 'Failed to Verify' }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.policies_lbl_notes', [], $l) ?: 'Notes' }}</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="500"></textarea>
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeEligModal()">{{ __('public.insurance_portal.policies_btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="activity"></i> {{ __('public.insurance_portal.policies_btn_save_check', [], $l) ?: 'Save Check' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Deactivate Policy Modal --}}
<div id="deactivate-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title"><i data-lucide="alert-triangle"></i> {{ __('public.insurance_portal.policies_modal_deact_title', [], $l) ?: 'Deactivate policy' }}</h3></div>
        <form id="deactivate-form" method="POST" action="">
            @csrf
            <p>{{ __('public.insurance_portal.policies_deact_confirm_text', [], $l) ?: 'Deactivate this policy?' }}</p>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDeactivateModal()">{{ __('public.insurance_portal.policies_btn_deact_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-danger btn-sm">{{ __('public.insurance_portal.policies_btn_deact_confirm', [], $l) ?: 'Deactivate' }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openPolicyModal() { document.getElementById('policy-modal').classList.add('open'); }
    function closePolicyModal() { document.getElementById('policy-modal').classList.remove('open'); }
    document.getElementById('policy-modal').addEventListener('click', function(e) {
        if (e.target === this) closePolicyModal();
    });

    function openEligModal(policyId) {
        var form = document.getElementById('elig-form');
        form.setAttribute('action', '{{ url("/portals/insurance/policies") }}/' + policyId + '/eligibility');
        document.getElementById('elig-modal').classList.add('open');
    }
    function closeEligModal() { document.getElementById('elig-modal').classList.remove('open'); }
    document.getElementById('elig-modal').addEventListener('click', function(e) {
        if (e.target === this) closeEligModal();
    });

    function openDeactivateModal(policyId) {
        document.getElementById('deactivate-form').setAttribute('action',
            '{{ url("/portals/insurance/policies") }}/' + policyId + '/deactivate');
        document.getElementById('deactivate-modal').classList.add('open');
    }
    function closeDeactivateModal() { document.getElementById('deactivate-modal').classList.remove('open'); }
    document.getElementById('deactivate-modal').addEventListener('click', function(e) {
        if (e.target === this) closeDeactivateModal();
    });
</script>
@endsection
