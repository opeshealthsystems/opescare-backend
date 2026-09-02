@extends('layouts.portal')
@section('title', __('public.adm_sub_plans_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_sub_plans_breadcrumb_section'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.subscription') }}">{{ __('public.adm_sub_plans_breadcrumb_subs') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_sub_plans_breadcrumb_plans') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_sub_plans_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary" onclick="opOpenModal('createPlanModal')"><i data-lucide="plus"></i> {{ __('public.adm_sub_plans_btn_new') }}</button>
</div>

<p class="td-muted mb-6">{{ __('public.adm_sub_plans_desc') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats --}}
<div class="stat-grid mb-6">
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_plans_stat_total') }}</div><div class="stat-card__value">{{ $stats['total'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_plans_stat_active') }}</div><div class="stat-card__value">{{ $stats['active'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_plans_stat_public') }}</div><div class="stat-card__value">{{ $stats['public'] }}</div></div>
</div>

{{-- Plan tier cards --}}
@if($plans->count())
<div class="plan-grid">
    @foreach($plans as $plan)
    <div class="plan-tier">
        <span class="plan-tier__name">{{ $plan->name }}</span>
        <span class="plan-tier__price">{{ $plan->priceFormatted() }}<small>/{{ $plan->billing_cycle }}</small></span>
        <div class="summary-bar">
            <span class="badge badge-{{ $plan->is_active ? 'success' : 'neutral' }}">{{ $plan->is_active ? __('public.adm_sub_plans_badge_active') : __('public.adm_sub_plans_badge_inactive') }}</span>
            @if($plan->is_public)<span class="badge badge-primary">{{ __('public.adm_sub_plans_badge_public') }}</span>@endif
            @if($plan->trial_days > 0)<span class="badge badge-teal">{{ $plan->trial_days }}d trial</span>@endif
        </div>
        <ul class="plan-tier__features">
            <li><i data-lucide="check"></i> {{ $plan->max_facilities }} facilit{{ $plan->max_facilities > 1 ? 'ies' : 'y' }}</li>
            <li><i data-lucide="check"></i> {{ $plan->max_staff ?? '∞' }} staff</li>
            <li><i data-lucide="check"></i> {{ $plan->max_patients_per_month ? number_format($plan->max_patients_per_month) . ' pts/mo' : '∞ patients' }}</li>
        </ul>
    </div>
    @endforeach
</div>
@endif

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="layers"></i> {{ __('public.adm_sub_plans_panel_title') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_sub_plans_col_name') }}</th>
                    <th>{{ __('public.adm_sub_plans_col_cycle') }}</th>
                    <th>{{ __('public.adm_sub_plans_col_price') }}</th>
                    <th>{{ __('public.adm_sub_plans_col_features') }}</th>
                    <th>{{ __('public.adm_sub_plans_col_limits') }}</th>
                    <th>{{ __('public.adm_sub_plans_col_trial') }}</th>
                    <th>{{ __('public.adm_sub_plans_col_status') }}</th>
                    <th class="row-actions">{{ __('public.adm_sub_plans_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td data-label="{{ __('public.adm_sub_plans_col_name') }}">
                            <div class="td-strong">{{ $plan->name }}</div>
                            <span class="mono td-muted">{{ $plan->slug }}</span>
                            @if($plan->description)<div class="td-muted">{{ Str::limit($plan->description, 50) }}</div>@endif
                        </td>
                        <td data-label="{{ __('public.adm_sub_plans_col_cycle') }}"><span class="badge badge-primary">{{ ucfirst($plan->billing_cycle) }}</span></td>
                        <td data-label="{{ __('public.adm_sub_plans_col_price') }}"><strong>{{ $plan->priceFormatted() }}</strong></td>
                        <td data-label="{{ __('public.adm_sub_plans_col_features') }}">
                            @if($plan->planFeatures->count())
                                @foreach($plan->planFeatures->take(3) as $f)
                                    <span class="feature-chip">{{ $f->feature_key }}</span>
                                @endforeach
                                @if($plan->planFeatures->count() > 3)
                                    <span class="td-muted">+{{ $plan->planFeatures->count() - 3 }} more</span>
                                @endif
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_sub_plans_col_limits') }}">
                            <div>{{ $plan->max_facilities }} facilit{{ $plan->max_facilities > 1 ? 'ies' : 'y' }}</div>
                            <div>{{ $plan->max_staff ?? '∞' }} staff</div>
                            <div>{{ $plan->max_patients_per_month ? number_format($plan->max_patients_per_month) . ' pts/mo' : '∞ pts' }}</div>
                        </td>
                        <td data-label="{{ __('public.adm_sub_plans_col_trial') }}">{{ $plan->trial_days > 0 ? $plan->trial_days . ' days' : '—' }}</td>
                        <td data-label="{{ __('public.adm_sub_plans_col_status') }}">
                            <span class="badge badge-{{ $plan->is_active ? 'success' : 'neutral' }}">{{ $plan->is_active ? __('public.adm_sub_plans_badge_active') : __('public.adm_sub_plans_badge_inactive') }}</span>
                            @if($plan->is_public)<span class="badge badge-primary">{{ __('public.adm_sub_plans_badge_public') }}</span>@endif
                        </td>
                        <td class="row-actions" data-label="{{ __('public.adm_sub_plans_col_actions') }}">
                            <button type="button" class="btn {{ $plan->is_active ? 'btn-warning' : 'btn-success' }} btn-sm" onclick="opOpenModal('toggle-modal-{{ $plan->id }}')">{{ $plan->is_active ? __('public.adm_sub_plans_btn_deactivate') : __('public.adm_sub_plans_btn_activate') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="td-muted empty-cell">{{ __('public.adm_sub_plans_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($plans->hasPages())<div class="panel-body">{{ $plans->links() }}</div>@endif
</div>

{{-- Toggle confirm modals --}}
@foreach($plans as $plan)
<div id="toggle-modal-{{ $plan->id }}" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="toggle-modal-title-{{ $plan->id }}">
        <h3 class="modal__title" id="toggle-modal-title-{{ $plan->id }}"><i data-lucide="alert-triangle"></i> {{ $plan->is_active ? __('public.adm_sub_plans_modal_toggle_title_deactivate') : __('public.adm_sub_plans_modal_toggle_title_activate') }}</h3>
        <form method="POST" action="{{ route('portals.admin.subscription.plans.toggle', $plan->id) }}">@csrf
            <div class="modal__body"><p>{{ $plan->is_active ? __('public.adm_sub_plans_btn_deactivate') : __('public.adm_sub_plans_btn_activate') }} <strong>{{ $plan->name }}</strong>?</p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('toggle-modal-{{ $plan->id }}')">{{ __('public.adm_sub_plans_modal_toggle_btn_cancel') }}</button>
                <button type="submit" class="btn {{ $plan->is_active ? 'btn-warning' : 'btn-success' }}">{{ $plan->is_active ? __('public.adm_sub_plans_btn_deactivate') : __('public.adm_sub_plans_btn_activate') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Create Plan Modal --}}
<div id="createPlanModal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="createPlanModal-title">
        <h3 class="modal__title" id="createPlanModal-title"><i data-lucide="layers"></i> {{ __('public.adm_sub_plans_create_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.subscription.plans.store') }}">
            @csrf
            <div class="modal__body">
                <div class="field-grid">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.adm_sub_plans_lbl_name') }}</label>
                        <input type="text" name="name" class="form-control" required placeholder="{{ __('public.adm_sub_plans_ph_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.adm_sub_plans_lbl_audience') }}</label>
                        <select name="audience" class="form-control" required>
                            <option value="facility">{{ __('public.adm_sub_plans_aud_facility') }}</option>
                            <option value="patient">{{ __('public.adm_sub_plans_aud_patient') }}</option>
                            <option value="household">{{ __('public.adm_sub_plans_aud_household') }}</option>
                            <option value="insurer">{{ __('public.adm_sub_plans_aud_insurer') }}</option>
                            <option value="lab">{{ __('public.adm_sub_plans_aud_lab') }}</option>
                            <option value="pharmacy">{{ __('public.adm_sub_plans_aud_pharmacy') }}</option>
                            <option value="healthorg">{{ __('public.adm_sub_plans_aud_healthorg') }}</option>
                            <option value="developer">{{ __('public.adm_sub_plans_aud_developer') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.adm_sub_plans_lbl_cycle') }}</label>
                        <select name="billing_cycle" class="form-control" required>
                            <option value="monthly">{{ __('public.adm_sub_plans_opt_monthly') }}</option>
                            <option value="annual">{{ __('public.adm_sub_plans_opt_annual') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.adm_sub_plans_lbl_price') }}</label>
                        <input type="number" name="price" class="form-control" required min="0" step="1" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_plans_lbl_annual_price') }}</label>
                        <input type="number" name="annual_price" class="form-control" min="0" step="1" placeholder="{{ __('public.adm_sub_plans_ph_annual_price') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_plans_lbl_trial_days') }}</label>
                        <input type="number" name="trial_days" class="form-control" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_plans_lbl_max_facilities') }}</label>
                        <input type="number" name="max_facilities" class="form-control" min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_plans_lbl_max_staff') }}</label>
                        <input type="number" name="max_staff" class="form-control" min="1" placeholder="{{ __('public.adm_sub_plans_ph_max_staff') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_plans_lbl_max_patients') }}</label>
                        <input type="number" name="max_patients_per_month" class="form-control" min="1" placeholder="{{ __('public.adm_sub_plans_ph_max_patients') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_plans_lbl_sort_order') }}</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_sub_plans_lbl_description') }}</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="{{ __('public.adm_sub_plans_ph_description') }}"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_sub_plans_lbl_features_raw') }}</label>
                    <textarea id="featureKeysRaw" class="form-control" rows="4" placeholder="MODULE_CDSS&#10;MODULE_BRIDGE&#10;API_SDK&#10;WEBHOOKS&#10;ANALYTICS_ADVANCED"></textarea>
                    <div class="form-hint">{{ __('public.adm_sub_plans_hint_features') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label"><input type="checkbox" name="is_public" value="1" id="isPub" checked> {{ __('public.adm_sub_plans_lbl_is_public') }}</label>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('createPlanModal')">{{ __('public.adm_sub_plans_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary" onclick="buildFeatureInputs()">{{ __('public.adm_sub_plans_btn_create') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function buildFeatureInputs(){
    const raw = document.getElementById('featureKeysRaw').value.trim();
    if (!raw) return;
    const form = document.querySelector('#createPlanModal form');
    raw.split('\n').forEach((line, i) => {
        const key = line.trim();
        if (!key) return;
        const kInput = document.createElement('input');
        kInput.type = 'hidden'; kInput.name = `feature_keys[${i}]`; kInput.value = key;
        const lInput = document.createElement('input');
        lInput.type = 'hidden'; lInput.name = `feature_labels[${i}]`; lInput.value = key.replace(/_/g,' ');
        form.appendChild(kInput);
        form.appendChild(lInput);
    });
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
