@extends('layouts.portal')
@section('title', __('public.adm_sub_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_sub_idx_breadcrumb_section'))
@section('content')

@php $badgeMap = ['success'=>'success','warning'=>'warning','danger'=>'danger','info'=>'primary','default'=>'neutral']; @endphp

<div class="breadcrumb">
    <a href="{{ route('portals.admin.subscription') }}">{{ __('public.adm_sub_idx_breadcrumb_subs') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_sub_idx_breadcrumb_dir') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_sub_idx_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary" onclick="opOpenModal('createSubModal')"><i data-lucide="plus"></i> {{ __('public.adm_sub_idx_btn_new') }}</button>
</div>

<p class="td-muted mb-6">{{ __('public.adm_sub_idx_desc') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- KPI Strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_idx_stat_active') }}</div><div class="stat-card__value">{{ $stats['active'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_idx_stat_trialing') }}</div><div class="stat-card__value">{{ $stats['trialing'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_idx_stat_past_due') }}</div><div class="stat-card__value">{{ $stats['past_due'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_idx_stat_mrr') }}</div><div class="stat-card__value">FCFA {{ number_format($stats['mrr_kobo'] / 100, 0) }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_idx_stat_overdue_inv') }}</div><div class="stat-card__value">{{ $stats['overdue_invoices'] }}</div></div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('portals.admin.subscription') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_sub_idx_ph_search') }}" aria-label="{{ __('public.aria_search') }}">
    </label>
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}">
        <option value="">{{ __('public.adm_sub_idx_filter_all_statuses') }}</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('public.adm_sub_idx_filter_active') }}</option>
        <option value="trialing" {{ request('status') === 'trialing' ? 'selected' : '' }}>{{ __('public.adm_sub_idx_filter_trialing') }}</option>
        <option value="past_due" {{ request('status') === 'past_due' ? 'selected' : '' }}>{{ __('public.adm_sub_idx_filter_past_due') }}</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('public.adm_sub_idx_filter_cancelled') }}</option>
        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('public.adm_sub_idx_filter_expired') }}</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_sub_idx_btn_filter') }}</button>
    <a href="{{ route('portals.admin.subscription') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_sub_idx_btn_clear') }}</a>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_sub_idx_col_org') }}</th>
                    <th>{{ __('public.adm_sub_idx_col_plan') }}</th>
                    <th>{{ __('public.adm_sub_idx_col_status') }}</th>
                    <th>{{ __('public.adm_sub_idx_col_period') }}</th>
                    <th>{{ __('public.adm_sub_idx_col_days_left') }}</th>
                    <th>{{ __('public.adm_sub_idx_col_auto_renew') }}</th>
                    <th class="row-actions">{{ __('public.adm_sub_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    <tr>
                        <td data-label="{{ __('public.adm_sub_idx_col_org') }}">
                            <div class="td-strong">{{ $sub->organization_name }}</div>
                            @if($sub->billing_email)<div class="td-muted">{{ $sub->billing_email }}</div>@endif
                        </td>
                        <td data-label="{{ __('public.adm_sub_idx_col_plan') }}">
                            <span class="td-strong">{{ $sub->plan->name ?? '—' }}</span>
                            @if($sub->plan)<div class="td-muted">{{ ucfirst($sub->plan->billing_cycle) }}</div>@endif
                        </td>
                        <td data-label="{{ __('public.adm_sub_idx_col_status') }}">
                            <span class="badge badge-{{ $badgeMap[$sub->statusColor()] ?? 'neutral' }}">@enum($sub->status)</span>
                        </td>
                        <td data-label="{{ __('public.adm_sub_idx_col_period') }}">
                            <div>{{ $sub->current_period_start->format('d M Y') }}</div>
                            <div class="td-muted">→ {{ $sub->current_period_end->format('d M Y') }}</div>
                        </td>
                        <td data-label="{{ __('public.adm_sub_idx_col_days_left') }}">
                            @php $days = $sub->daysUntilExpiry(); $dc = $days < 7 ? 'danger' : ($days < 30 ? 'warning' : 'success'); @endphp
                            <span class="badge badge-{{ $dc }}">{{ $days }}d</span>
                        </td>
                        <td data-label="{{ __('public.adm_sub_idx_col_auto_renew') }}">
                            @if($sub->auto_renew)<span class="cell-with-icon"><i data-lucide="check"></i> {{ __('public.adm_sub_idx_auto_renew_yes') }}</span>@else<span class="cell-with-icon"><i data-lucide="x"></i> {{ __('public.adm_sub_idx_auto_renew_no') }}</span>@endif
                        </td>
                        <td class="row-actions" data-label="{{ __('public.adm_sub_idx_col_actions') }}">
                            <div class="row-actions-inline">
                                <a href="{{ route('portals.admin.subscription.detail', $sub->id) }}" class="btn btn-secondary btn-sm">{{ __('public.adm_sub_idx_btn_view') }}</a>
                                @if($sub->status === 'active')
                                    <button type="button" class="btn btn-warning btn-sm" onclick="opOpenModal('pause-modal-{{ $sub->id }}')">{{ __('public.adm_sub_idx_btn_pause') }}</button>
                                @elseif(in_array($sub->status, ['paused','past_due']))
                                    <form method="POST" action="{{ route('portals.admin.subscription.reactivate', $sub->id) }}" class="inline-form">@csrf
                                        <button type="submit" class="btn btn-success btn-sm">{{ __('public.adm_sub_idx_btn_reactivate') }}</button>
                                    </form>
                                @endif
                                @if(!in_array($sub->status, ['cancelled','expired']))
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="opOpenModal('renew-modal-{{ $sub->id }}')">{{ __('public.adm_sub_idx_btn_renew') }}</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td-muted empty-cell">{{ __('public.adm_sub_idx_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())<div class="panel-body">{{ $subscriptions->links() }}</div>@endif
</div>

{{-- Confirm modals --}}
@foreach($subscriptions as $sub)
    @if($sub->status === 'active')
    <div id="pause-modal-{{ $sub->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="pause-modal-title-{{ $sub->id }}">
            <h3 class="modal__title" id="pause-modal-title-{{ $sub->id }}"><i data-lucide="pause-circle"></i> {{ __('public.adm_sub_idx_modal_pause_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.subscription.pause', $sub->id) }}">@csrf
                <div class="modal__body"><p>{{ __('public.adm_sub_idx_btn_pause') }} <strong>{{ $sub->organization_name }}</strong>?</p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('pause-modal-{{ $sub->id }}')">{{ __('public.adm_sub_idx_modal_pause_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('public.adm_sub_idx_modal_pause_btn_pause') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @if(!in_array($sub->status, ['cancelled','expired']))
    <div id="renew-modal-{{ $sub->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="renew-modal-title-{{ $sub->id }}">
            <h3 class="modal__title" id="renew-modal-title-{{ $sub->id }}"><i data-lucide="refresh-cw"></i> {{ __('public.adm_sub_idx_modal_renew_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.subscription.renew', $sub->id) }}">@csrf
                <div class="modal__body"><p>{{ __('public.adm_sub_idx_btn_renew') }} <strong>{{ $sub->organization_name }}</strong>?</p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('renew-modal-{{ $sub->id }}')">{{ __('public.adm_sub_idx_modal_renew_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('public.adm_sub_idx_modal_renew_btn_renew') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

{{-- New Subscription Modal --}}
<div id="createSubModal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="createSubModal-title">
        <h3 class="modal__title" id="createSubModal-title"><i data-lucide="credit-card"></i> {{ __('public.adm_sub_idx_create_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.subscription.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_idx_lbl_facility') }}</label>
                    <select name="organization_id" class="form-control" required onchange="fillOrgName(this)">
                        <option value="">{{ __('public.adm_sub_idx_ph_facility') }}</option>
                        @foreach($facilities as $f)
                            <option value="{{ $f->id }}" data-name="{{ $f->name }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="organization_name" id="orgNameInput">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_idx_lbl_plan') }}</label>
                    <select name="plan_id" class="form-control" required>
                        <option value="">{{ __('public.adm_sub_idx_ph_plan') }}</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->priceFormatted() }}/{{ $plan->billing_cycle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-grid">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_idx_lbl_billing_name') }}</label>
                        <input type="text" name="billing_name" class="form-control" placeholder="{{ __('public.adm_sub_idx_ph_billing_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_idx_lbl_billing_email') }}</label>
                        <input type="email" name="billing_email" class="form-control" placeholder="billing@facility.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_idx_lbl_payment_method') }}</label>
                        <select name="payment_method" class="form-control">
                            <option value="">{{ __('public.adm_sub_idx_ph_payment_method') }}</option>
                            <option value="bank_transfer">{{ __('public.adm_sub_idx_opt_bank') }}</option>
                            <option value="card">{{ __('public.adm_sub_idx_opt_card') }}</option>
                            <option value="ussd">{{ __('public.adm_sub_idx_opt_ussd') }}</option>
                            <option value="cash">{{ __('public.adm_sub_idx_opt_cash') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.adm_sub_idx_lbl_discount') }}</label>
                        <input type="number" name="discount_percent" class="form-control" min="0" max="100" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_sub_idx_lbl_notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('public.adm_sub_idx_ph_notes') }}"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('createSubModal')">{{ __('public.adm_sub_idx_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_sub_idx_btn_create') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function fillOrgName(sel){
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('orgNameInput').value = opt.dataset.name || '';
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
