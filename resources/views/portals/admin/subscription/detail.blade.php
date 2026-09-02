@extends('layouts.portal')
@section('title', __('public.adm_sub_title') . ' — ' . $subscription->organization_name)
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_sub_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_sub_breadcrumb_section'))
@section('content')

@php $badgeMap = ['success'=>'success','warning'=>'warning','danger'=>'danger','info'=>'primary','default'=>'neutral']; @endphp

<div class="breadcrumb">
    <a href="{{ route('portals.admin.subscription') }}">{{ __('public.adm_sub_breadcrumb_section') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $subscription->organization_name }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="credit-card"></i></div>
    <h2 class="entity-head__title">{{ $subscription->organization_name }}</h2>
    <span class="badge badge-{{ $badgeMap[$subscription->statusColor()] ?? 'neutral' }}">@enum($subscription->status)</span>
    <div class="entity-head__spacer"></div>
    <a href="{{ route('portals.admin.subscription') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('public.adm_sub_btn_all_subscriptions') }}</a>
    @if(!in_array($subscription->status, ['cancelled','expired']))
        <button type="button" class="btn btn-danger" onclick="opOpenModal('cancelModal')"><i data-lucide="x-circle"></i> {{ __('public.adm_sub_btn_cancel') }}</button>
    @endif
</div>

<p class="td-muted mb-6">{{ $subscription->plan->name ?? '—' }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

<div class="field-grid mb-6">

    {{-- Subscription Info --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('public.adm_sub_panel_details') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr><td>{{ __('public.adm_sub_kv_organization') }}</td><td class="kv-strong">{{ $subscription->organization_name }}</td></tr>
                <tr><td>{{ __('public.adm_sub_kv_plan') }}</td><td class="kv-strong">{{ $subscription->plan->name ?? '—' }}</td></tr>
                <tr><td>{{ __('public.adm_sub_kv_billing_cycle') }}</td><td>{{ ucfirst($subscription->plan->billing_cycle ?? '—') }}</td></tr>
                <tr><td>{{ __('public.adm_sub_kv_price') }}</td><td class="kv-strong">{{ $subscription->plan->priceFormatted() ?? '—' }}</td></tr>
                <tr><td>{{ __('public.adm_sub_kv_discount') }}</td><td>{{ $subscription->discount_percent }}%</td></tr>
                <tr><td>{{ __('public.adm_sub_kv_period') }}</td><td>{{ $subscription->current_period_start->format('d M Y') }} → {{ $subscription->current_period_end->format('d M Y') }}</td></tr>
                @php $days = $subscription->daysUntilExpiry(); @endphp
                <tr><td>{{ __('public.adm_sub_kv_days_left') }}</td><td><span class="badge badge-{{ $days < 7 ? 'danger' : 'success' }}">{{ $days }} {{ __('public.adm_sub_days_suffix') }}</span></td></tr>
                <tr><td>{{ __('public.adm_sub_kv_auto_renew') }}</td><td>@if($subscription->auto_renew)<span class="cell-with-icon"><i data-lucide="check"></i> {{ __('public.adm_sub_yes') }}</span>@else<span class="cell-with-icon"><i data-lucide="x"></i> {{ __('public.adm_sub_no') }}</span>@endif</td></tr>
                @if($subscription->billing_email)
                    <tr><td>{{ __('public.adm_sub_kv_billing_contact') }}</td><td>{{ $subscription->billing_name }}<br><span class="td-muted">{{ $subscription->billing_email }}</span></td></tr>
                @endif
                @if($subscription->notes)
                    <tr><td>{{ __('public.adm_sub_kv_notes') }}</td><td class="td-muted">{{ $subscription->notes }}</td></tr>
                @endif
            </table>

            @if(!in_array($subscription->status, ['cancelled','expired']))
                <div class="mt-6">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="opOpenModal('changePlanModal')"><i data-lucide="repeat"></i> {{ __('public.adm_sub_btn_change_plan') }}</button>
                </div>
            @endif
        </div>
    </div>

    {{-- Module Entitlements --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="layers"></i> {{ __('public.adm_sub_panel_entitlements') }}</h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_sub_ent_col_module') }}</th>
                    <th>{{ __('public.adm_sub_ent_col_status') }}</th>
                    <th>{{ __('public.adm_sub_ent_col_granted') }}</th>
                </tr></thead>
                <tbody>
                    @forelse($subscription->moduleEntitlements as $ent)
                        <tr>
                            <td data-label="{{ __('public.adm_sub_ent_col_module') }}"><span class="mono">{{ $ent->module_key }}</span></td>
                            <td data-label="{{ __('public.adm_sub_ent_col_status') }}"><span class="badge badge-{{ $ent->isActive() ? 'success' : 'neutral' }}">{{ $ent->isActive() ? __('public.adm_sub_ent_active') : __('public.adm_sub_ent_revoked') }}</span></td>
                            <td data-label="{{ __('public.adm_sub_ent_col_granted') }}">{{ $ent->granted_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="td-muted empty-cell">{{ __('public.adm_sub_ent_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Invoices --}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('public.adm_sub_panel_invoices') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('public.adm_sub_inv_col_number') }}</th>
                <th>{{ __('public.adm_sub_inv_col_date') }}</th>
                <th>{{ __('public.adm_sub_inv_col_due') }}</th>
                <th>{{ __('public.adm_sub_inv_col_amount') }}</th>
                <th>{{ __('public.adm_sub_inv_col_status') }}</th>
                <th class="row-actions">{{ __('public.adm_sub_inv_col_actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse($subscription->invoices->sortByDesc('invoice_date') as $inv)
                    <tr>
                        <td data-label="{{ __('public.adm_sub_inv_col_number') }}"><span class="mono">{{ $inv->invoice_number }}</span></td>
                        <td data-label="{{ __('public.adm_sub_inv_col_date') }}">{{ $inv->invoice_date->format('d M Y') }}</td>
                        <td data-label="{{ __('public.adm_sub_inv_col_due') }}">@if($inv->isOverdue())<span class="badge badge-danger">{{ $inv->due_date->format('d M Y') }}</span>@else{{ $inv->due_date->format('d M Y') }}@endif</td>
                        <td data-label="{{ __('public.adm_sub_inv_col_amount') }}"><strong>{{ $inv->totalFormatted() }}</strong></td>
                        <td data-label="{{ __('public.adm_sub_inv_col_status') }}"><span class="badge badge-{{ $badgeMap[$inv->statusColor()] ?? 'neutral' }}">@enum($inv->status)</span></td>
                        <td class="row-actions" data-label="{{ __('public.adm_sub_inv_col_actions') }}">
                            @if(in_array($inv->status, ['sent','overdue']))
                                <button type="button" class="btn btn-success btn-sm" onclick="openPayModal('{{ $inv->id }}')">{{ __('public.adm_sub_inv_btn_mark_paid') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_sub_inv_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Change Plan Modal --}}
@if(!in_array($subscription->status, ['cancelled','expired']))
<div id="changePlanModal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="changePlanModal-title">
        <h3 class="modal__title" id="changePlanModal-title"><i data-lucide="repeat"></i> {{ __('public.adm_sub_modal_change_plan_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.subscription.change_plan', $subscription->id) }}">
            @csrf
            <div class="modal__body">
                <p class="td-muted">{{ __('public.adm_sub_modal_change_plan_body') }} <strong>{{ $subscription->organization_name }}</strong>.</p>
                <div class="plan-grid">
                    @foreach($plans as $p)
                    <label class="plan-tier {{ $p->id === $subscription->plan_id ? 'plan-tier--current' : '' }}">
                        <span class="plan-tier__name">
                            <input type="radio" name="plan_id" value="{{ $p->id }}" {{ $p->id === $subscription->plan_id ? 'checked' : '' }}>
                            {{ $p->name }}
                        </span>
                        <span class="plan-tier__price">{{ $p->priceFormatted() }}<small>/{{ $p->billing_cycle }}</small></span>
                        @if($p->id === $subscription->plan_id)<span class="badge badge-primary">{{ __('public.adm_sub_badge_current') }}</span>@endif
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('changePlanModal')">{{ __('public.adm_sub_btn_modal_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_sub_btn_change_plan') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Cancel Modal --}}
<div id="cancelModal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancelModal-title">
        <h3 class="modal__title" id="cancelModal-title"><i data-lucide="x-circle"></i> {{ __('public.adm_sub_modal_cancel_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.subscription.cancel', $subscription->id) }}">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_modal_cancel_reason_label') }}</label>
                    <textarea name="reason" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="{{ __('public.adm_sub_modal_cancel_reason_placeholder') }}"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('cancelModal')">{{ __('public.adm_sub_modal_cancel_abort') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.adm_sub_modal_cancel_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Mark Paid Modal --}}
<div id="payModal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="payModal-title">
        <h3 class="modal__title" id="payModal-title"><i data-lucide="check-circle"></i> {{ __('public.adm_sub_modal_pay_title') }}</h3>
        <form id="payForm" method="POST" action="">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_modal_pay_ref_label') }}</label>
                    <input type="text" name="payment_reference" class="form-control" required placeholder="{{ __('public.adm_sub_modal_pay_ref_placeholder') }}">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_modal_pay_method_label') }}</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="bank_transfer">{{ __('public.adm_sub_pay_method_bank') }}</option>
                        <option value="card">{{ __('public.adm_sub_pay_method_card') }}</option>
                        <option value="ussd">{{ __('public.adm_sub_pay_method_ussd') }}</option>
                        <option value="cash">{{ __('public.adm_sub_pay_method_cash') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('payModal')">{{ __('public.adm_sub_btn_modal_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_sub_modal_pay_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function openPayModal(invoiceId){
    const base = '{{ url("portals/admin/subscription/invoices") }}';
    document.getElementById('payForm').action = base + '/' + invoiceId + '/mark-paid';
    opOpenModal('payModal');
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
