@extends('layouts.portal')
@php $l = app()->getLocale(); @endphp

@section('title', __('public.insurance_portal.providers_page_title', [], $l) ?: 'Insurance Providers & Plans')

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
@section('breadcrumb_section', __('public.insurance_portal.providers_breadcrumb', [], $l) ?: 'Providers & Plans')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.insurance_portal.providers_page_title', [], $l) ?: 'Insurance Providers & Plans' }}</h1>
        <p class="page-subtitle">{{ __('public.insurance_portal.providers_subtitle', [], $l) ?: 'Manage insurance companies and their coverage plans.' }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openProviderModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.insurance_portal.providers_btn_add', [], $l) ?: 'Add Provider' }}
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

@if(count($providers) === 0)
    <div class="panel">
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="building-2"></i></div>
                <h3>{{ __('public.insurance_portal.providers_empty_title', [], $l) ?: 'No Insurance Providers' }}</h3>
                <p>{{ __('public.insurance_portal.providers_empty_desc', [], $l) ?: 'Add insurance companies to start managing patient policies and claims.' }}</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="openProviderModal()">
                    {{ __('public.insurance_portal.providers_btn_add', [], $l) ?: 'Add Provider' }}
                </button>
            </div>
        </div>
    </div>
@else
    @foreach($providers as $provider)
    <div class="panel mb-6">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i data-lucide="building-2"></i>
                    {{ $provider->name }}
                    @if($provider->code)
                        <span class="badge badge-neutral">{{ $provider->code }}</span>
                    @endif
                    <span class="badge {{ $provider->status === 'active' ? 'badge-success' : 'badge-neutral' }}">
                        @enum($provider->status)
                    </span>
                </h2>
                @if($provider->contact_email || $provider->contact_phone)
                <p class="text-sm text-muted">
                    {{ $provider->contact_email }} {{ $provider->contact_phone ? '· ' . $provider->contact_phone : '' }}
                </p>
                @endif
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="openPlanModal('{{ $provider->id }}')">
                <i data-lucide="plus"></i> {{ __('public.insurance_portal.providers_btn_add_plan', [], $l) ?: 'Add Plan' }}
            </button>
        </div>
        <div class="panel-body--flush">
            @if($provider->activePlans->isEmpty())
                <p class="text-sm text-muted" style="padding:1rem;">{{ __('public.insurance_portal.providers_lbl_no_plans', [], $l) ?: 'No plans yet.' }}</p>
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('public.insurance_portal.providers_col_plan_name', [], $l) ?: 'Plan Name' }}</th>
                                <th>{{ __('public.insurance_portal.providers_col_code', [], $l) ?: 'Code' }}</th>
                                <th>{{ __('public.insurance_portal.providers_col_type', [], $l) ?: 'Type' }}</th>
                                <th>{{ __('public.insurance_portal.providers_col_preauth', [], $l) ?: 'Pre-auth Required' }}</th>
                                <th>{{ __('public.insurance_portal.providers_col_cashless', [], $l) ?: 'Cashless' }}</th>
                                <th>{{ __('public.insurance_portal.providers_col_copay', [], $l) ?: 'Copay %' }}</th>
                                <th>{{ __('public.insurance_portal.providers_col_status', [], $l) ?: 'Status' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($provider->activePlans as $plan)
                            <tr>
                                <td data-label="{{ __('public.insurance_portal.providers_col_plan_name', [], $l) ?: 'Plan Name' }}"><span class="td-strong">{{ $plan->name }}</span></td>
                                <td data-label="{{ __('public.insurance_portal.providers_col_code', [], $l) ?: 'Code' }}"><span class="td-mono">{{ $plan->plan_code ?? '--' }}</span></td>
                                <td data-label="{{ __('public.insurance_portal.providers_col_type', [], $l) ?: 'Type' }}">@enum($plan->plan_type ?? '--')</td>
                                <td data-label="{{ __('public.insurance_portal.providers_col_preauth', [], $l) ?: 'Pre-auth Required' }}">
                                    <span class="badge {{ $plan->requires_preauthorization ? 'badge-warning' : 'badge-neutral' }}">
                                        {{ $plan->requires_preauthorization ? __('public.insurance_portal.providers_lbl_yes', [], $l) : __('public.insurance_portal.providers_lbl_no', [], $l) }}
                                    </span>
                                </td>
                                <td data-label="{{ __('public.insurance_portal.providers_col_cashless', [], $l) ?: 'Cashless' }}">
                                    <span class="badge {{ $plan->cashless_available ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $plan->cashless_available ? __('public.insurance_portal.providers_lbl_yes', [], $l) : __('public.insurance_portal.providers_lbl_no', [], $l) }}
                                    </span>
                                </td>
                                <td data-label="{{ __('public.insurance_portal.providers_col_copay', [], $l) ?: 'Copay %' }}">{{ $plan->copay_percentage ? $plan->copay_percentage . '%' : '--' }}</td>
                                <td data-label="{{ __('public.insurance_portal.providers_col_status', [], $l) ?: 'Status' }}"><span class="badge badge-success">@enum($plan->status)</span></td>
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

{{-- Add Provider Modal --}}
<div id="provider-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.providers_modal_add_title', [], $l) ?: 'Add Insurance Provider' }}</h3></div>
        <form method="POST" action="{{ route('portals.insurance.providers.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.providers_lbl_name', [], $l) ?: 'Provider Name' }}</label>
                <input type="text" name="name" class="form-control" required maxlength="200" placeholder="{{ __('public.insurance_portal.providers_ph_name', [], $l) ?: 'e.g. NHIA, Activa, Sunu' }}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.providers_lbl_code', [], $l) ?: 'Code' }}</label>
                    <input type="text" name="code" class="form-control" maxlength="50" placeholder="{{ __('public.insurance_portal.providers_ph_code', [], $l) ?: 'NHIA' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.providers_lbl_country_code', [], $l) ?: 'Country Code' }}</label>
                    <input type="text" name="country_code" class="form-control" maxlength="3" value="CM">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.providers_lbl_contact_email', [], $l) ?: 'Contact Email' }}</label>
                <input type="email" name="contact_email" class="form-control" maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.providers_lbl_contact_phone', [], $l) ?: 'Contact Phone' }}</label>
                <input type="text" name="contact_phone" class="form-control" maxlength="30">
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeProviderModal()">{{ __('public.insurance_portal.providers_btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="save"></i> {{ __('public.insurance_portal.providers_btn_save', [], $l) ?: 'Save Provider' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Plan Modal --}}
<div id="plan-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.providers_modal_plan_title', [], $l) ?: 'Add Insurance Plan' }}</h3></div>
        <form id="plan-form" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.providers_lbl_plan_name', [], $l) ?: 'Plan Name' }}</label>
                <input type="text" name="name" class="form-control" required maxlength="200">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.providers_lbl_plan_code', [], $l) ?: 'Plan Code' }}</label>
                    <input type="text" name="plan_code" class="form-control" maxlength="50">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.providers_lbl_plan_type', [], $l) ?: 'Plan Type' }}</label>
                    <select name="plan_type" class="form-control">
                        <option value="">{{ __('public.insurance_portal.providers_opt_select', [], $l) ?: 'Select…' }}</option>
                        <option value="nhia">{{ __('public.insurance_portal.providers_opt_nhia', [], $l) ?: 'NHIA' }}</option>
                        <option value="private">{{ __('public.insurance_portal.providers_opt_private', [], $l) ?: 'Private' }}</option>
                        <option value="employer">{{ __('public.insurance_portal.providers_opt_employer', [], $l) ?: 'Employer' }}</option>
                        <option value="mutual">{{ __('public.insurance_portal.providers_opt_mutual', [], $l) ?: 'Mutual' }}</option>
                        <option value="other">{{ __('public.insurance_portal.providers_opt_other', [], $l) ?: 'Other' }}</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.providers_lbl_preauth_req', [], $l) ?: 'Pre-auth Required' }}</label>
                    <select name="requires_preauthorization" class="form-control">
                        <option value="0">{{ __('public.insurance_portal.providers_opt_no', [], $l) ?: 'No' }}</option>
                        <option value="1">{{ __('public.insurance_portal.providers_opt_yes', [], $l) ?: 'Yes' }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.insurance_portal.providers_lbl_cashless', [], $l) ?: 'Cashless Available' }}</label>
                    <select name="cashless_available" class="form-control">
                        <option value="0">{{ __('public.insurance_portal.providers_opt_no', [], $l) ?: 'No' }}</option>
                        <option value="1">{{ __('public.insurance_portal.providers_opt_yes', [], $l) ?: 'Yes' }}</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.providers_lbl_copay', [], $l) ?: 'Co-pay %' }}</label>
                <input type="number" name="copay_percentage" class="form-control" min="0" max="100" step="0.01" placeholder="0.00">
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closePlanModal()">{{ __('public.insurance_portal.providers_btn_plan_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="save"></i> {{ __('public.insurance_portal.providers_btn_plan_save', [], $l) ?: 'Save Plan' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openProviderModal() { document.getElementById('provider-modal').classList.add('open'); }
    function closeProviderModal() { document.getElementById('provider-modal').classList.remove('open'); }
    document.getElementById('provider-modal').addEventListener('click', function(e) {
        if (e.target === this) closeProviderModal();
    });

    function openPlanModal(providerId) {
        var form = document.getElementById('plan-form');
        form.setAttribute('action', '{{ url("/portals/insurance/providers") }}/' + providerId + '/plans');
        document.getElementById('plan-modal').classList.add('open');
    }
    function closePlanModal() { document.getElementById('plan-modal').classList.remove('open'); }
    document.getElementById('plan-modal').addEventListener('click', function(e) {
        if (e.target === this) closePlanModal();
    });
</script>
@endsection
