@extends('layouts.portal')
@section('title', __('public.adm_sub_inv_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_sub_inv_breadcrumb_section'))
@section('content')

@php $badgeMap = ['success'=>'success','warning'=>'warning','danger'=>'danger','info'=>'primary','default'=>'neutral']; @endphp

<div class="breadcrumb">
    <a href="{{ route('portals.admin.subscription') }}">{{ __('public.adm_sub_inv_breadcrumb_subs') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_sub_inv_breadcrumb_invoices') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_sub_inv_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.subscription') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> {{ __('public.adm_sub_inv_btn_back') }}</a>
</div>

<p class="td-muted mb-6">{{ __('public.adm_sub_inv_desc') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

{{-- KPI Strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_inv_stat_paid_month') }}</div><div class="stat-card__value">FCFA {{ number_format($stats['paid_this_month'] / 100, 0) }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_inv_stat_pending') }}</div><div class="stat-card__value">{{ $stats['pending_count'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_inv_stat_overdue') }}</div><div class="stat-card__value">{{ $stats['overdue_count'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">{{ __('public.adm_sub_inv_stat_overdue_amount') }}</div><div class="stat-card__value">FCFA {{ number_format($stats['overdue_amount'] / 100, 0) }}</div></div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('portals.admin.subscription.invoices') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_sub_inv_ph_search') }}" aria-label="{{ __('public.aria_search') }}">
    </label>
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}">
        <option value="">{{ __('public.adm_sub_inv_filter_all_statuses') }}</option>
        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('public.adm_sub_inv_filter_sent') }}</option>
        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('public.adm_sub_inv_filter_paid') }}</option>
        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>{{ __('public.adm_sub_inv_filter_overdue') }}</option>
        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>{{ __('public.adm_sub_inv_filter_void') }}</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_sub_inv_btn_filter') }}</button>
    <a href="{{ route('portals.admin.subscription.invoices') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_sub_inv_btn_clear') }}</a>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_sub_inv_col_invoice_num') }}</th>
                    <th>{{ __('public.adm_sub_inv_col_org') }}</th>
                    <th>{{ __('public.adm_sub_inv_col_invoice_date') }}</th>
                    <th>{{ __('public.adm_sub_inv_col_due_date') }}</th>
                    <th>{{ __('public.adm_sub_inv_col_amount') }}</th>
                    <th>{{ __('public.adm_sub_inv_col_status') }}</th>
                    <th class="row-actions">{{ __('public.adm_sub_inv_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td data-label="{{ __('public.adm_sub_inv_col_invoice_num') }}"><span class="mono">{{ $inv->invoice_number }}</span></td>
                        <td data-label="{{ __('public.adm_sub_inv_col_org') }}">{{ $inv->subscription?->organization_name ?? '—' }}</td>
                        <td data-label="{{ __('public.adm_sub_inv_col_invoice_date') }}">{{ $inv->invoice_date->format('d M Y') }}</td>
                        <td data-label="{{ __('public.adm_sub_inv_col_due_date') }}">
                            @if($inv->isOverdue())
                                <span class="badge badge-danger">{{ $inv->due_date->format('d M Y') }} · {{ __('public.adm_sub_inv_badge_overdue') }}</span>
                            @else
                                {{ $inv->due_date->format('d M Y') }}
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_sub_inv_col_amount') }}"><strong>{{ $inv->totalFormatted() }}</strong></td>
                        <td data-label="{{ __('public.adm_sub_inv_col_status') }}"><span class="badge badge-{{ $badgeMap[$inv->statusColor()] ?? 'neutral' }}">@enum($inv->status)</span></td>
                        <td class="row-actions" data-label="{{ __('public.adm_sub_inv_col_actions') }}">
                            <div class="row-actions-inline">
                                @if(in_array($inv->status, ['sent','overdue']))
                                    <button type="button" class="btn btn-success btn-sm" onclick="openPayModal('{{ $inv->id }}')"><i data-lucide="check"></i> {{ __('public.adm_sub_inv_btn_mark_paid') }}</button>
                                @endif
                                <a href="{{ route('portals.admin.subscription.detail', $inv->subscription_id) }}" class="btn btn-secondary btn-sm">{{ __('public.adm_sub_inv_btn_view_sub') }}</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td-muted empty-cell">{{ __('public.adm_sub_inv_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())<div class="panel-body">{{ $invoices->links() }}</div>@endif
</div>

{{-- Mark Paid Modal --}}
<div id="payModal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="payModal-title">
        <h3 class="modal__title" id="payModal-title"><i data-lucide="check-circle"></i> {{ __('public.adm_sub_inv_modal_title') }}</h3>
        <form id="payForm" method="POST" action="">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_inv_modal_lbl_reference') }}</label>
                    <input type="text" name="payment_reference" class="form-control" required placeholder="{{ __('public.adm_sub_inv_modal_ph_reference') }}">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_sub_inv_modal_lbl_method') }}</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="bank_transfer">{{ __('public.adm_sub_inv_modal_opt_bank') }}</option>
                        <option value="card">{{ __('public.adm_sub_inv_modal_opt_card') }}</option>
                        <option value="ussd">{{ __('public.adm_sub_inv_modal_opt_ussd') }}</option>
                        <option value="cash">{{ __('public.adm_sub_inv_modal_opt_cash') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('payModal')">{{ __('public.adm_sub_inv_modal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_sub_inv_modal_btn_confirm') }}</button>
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
