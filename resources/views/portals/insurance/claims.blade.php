@extends('layouts.portal')
@php $l = app()->getLocale(); @endphp

@section('title', __('public.insurance_portal.claims_page_title', [], $l) ?: 'Insurance Claims')

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
@section('breadcrumb_section', __('public.insurance_portal.claims_breadcrumb', [], $l) ?: 'Claims')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.insurance_portal.claims_page_title', [], $l) ?: 'Insurance Claims' }}</h1>
        <p class="page-subtitle">{{ __('public.insurance_portal.claims_subtitle', [], $l) ?: 'Create, submit, and track insurance claims with payers.' }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openClaimModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.insurance_portal.claims_btn_new', [], $l) ?: 'New Claim' }}
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
<form method="GET" action="{{ route('portals.insurance.claims') }}" class="filter-bar">
    <select name="status" class="filter-select">
        <option value="">{{ __('public.insurance_portal.claims_filter_all_statuses', [], $l) ?: 'All Statuses' }}</option>
        @foreach(['draft','submitted','under_review','more_information_required','approved','partially_approved','rejected','paid','partially_paid','cancelled','disputed'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.insurance_portal.claims_btn_filter', [], $l) ?: 'Filter' }}
    </button>
    <a href="{{ route('portals.insurance.claims') }}" class="btn btn-ghost btn-sm">{{ __('public.insurance_portal.claims_btn_clear', [], $l) ?: 'Clear' }}</a>
</form>

<div class="panel">
    <div class="panel-body--flush">
        @if(count($claims) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="file-text"></i></div>
                <h3>{{ __('public.insurance_portal.claims_empty_title', [], $l) ?: 'No Claims' }}</h3>
                <p>{{ __('public.insurance_portal.claims_empty_desc', [], $l) ?: 'Create an insurance claim from a patient invoice to begin the reimbursement process.' }}</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="openClaimModal()">
                    {{ __('public.insurance_portal.claims_btn_new', [], $l) ?: 'New Claim' }}
                </button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.insurance_portal.claims_col_number', [], $l) ?: 'Claim #' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_policy_payer', [], $l) ?: 'Policy / Payer' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_claimed', [], $l) ?: 'Claimed' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_approved', [], $l) ?: 'Approved' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_paid', [], $l) ?: 'Paid' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_submitted', [], $l) ?: 'Submitted' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_status', [], $l) ?: 'Status' }}</th>
                            <th>{{ __('public.insurance_portal.claims_col_actions', [], $l) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($claims as $claim)
                        @php
                            $statusBadge = match($claim->status) {
                                'approved','paid'          => 'badge-success',
                                'partially_approved','partially_paid' => 'badge-teal',
                                'rejected','cancelled'     => 'badge-danger',
                                'submitted','under_review' => 'badge-primary',
                                'more_information_required','disputed' => 'badge-warning',
                                default                    => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.insurance_portal.claims_col_number', [], $l) ?: 'Claim #' }}">
                                <span class="td-mono">{{ $claim->claim_number }}</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_policy_payer', [], $l) ?: 'Policy / Payer' }}">
                                <span class="td-strong">{{ $claim->policy->plan->provider->name ?? '--' }}</span>
                                <div class="td-muted">{{ $claim->policy->policy_number ?? '--' }}</div>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_claimed', [], $l) ?: 'Claimed' }}">{{ number_format($claim->claimed_amount, 2) }}</td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_approved', [], $l) ?: 'Approved' }}">
                                {{ $claim->approved_amount !== null ? number_format($claim->approved_amount, 2) : '--' }}
                            </td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_paid', [], $l) ?: 'Paid' }}">
                                {{ $claim->paid_amount !== null ? number_format($claim->paid_amount, 2) : '--' }}
                            </td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_submitted', [], $l) ?: 'Submitted' }}">
                                {{ $claim->submitted_at ? \Carbon\Carbon::parse($claim->submitted_at)->format('M d, Y') : '--' }}
                            </td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_status', [], $l) ?: 'Status' }}">
                                <span class="badge {{ $statusBadge }}">@enum($claim->status)</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.claims_col_actions', [], $l) ?: 'Actions' }}" class="row-actions">
                                @if($claim->isDraft())
                                    <form method="POST" action="{{ route('portals.insurance.claims.submit', $claim->id) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i data-lucide="send"></i> {{ __('public.insurance_portal.claims_btn_submit', [], $l) ?: 'Submit' }}
                                        </button>
                                    </form>
                                @endif
                                @if($claim->canReceiveDecision())
                                    <button type="button" class="btn btn-teal btn-sm" onclick="openDecideModal('{{ $claim->id }}')">
                                        <i data-lucide="gavel"></i> {{ __('public.insurance_portal.claims_btn_decide', [], $l) ?: 'Decide' }}
                                    </button>
                                @endif
                                @if($claim->canReceivePayment())
                                    <button type="button" class="btn btn-success btn-sm" onclick="openPayModal('{{ $claim->id }}', {{ $claim->approved_amount ?? $claim->claimed_amount }})">
                                        <i data-lucide="banknote"></i> {{ __('public.insurance_portal.claims_btn_pay', [], $l) ?: 'Pay' }}
                                    </button>
                                @endif
                                @if(in_array($claim->status, ['draft','submitted','under_review','more_information_required']))
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="openCancelModal('{{ $claim->id }}')">{{ __('public.insurance_portal.claims_btn_cancel', [], $l) ?: 'Cancel' }}</button>
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

{{-- New Claim Modal --}}
<div id="claim-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--lg">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.claims_modal_new_title', [], $l) ?: 'Create Insurance Claim' }}</h3></div>
        <form method="POST" action="{{ route('portals.insurance.claims.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_patient_policy', [], $l) ?: 'Patient Policy' }}</label>
                @if(count($policies) > 0)
                    <select name="policy_id" class="form-control" required>
                        <option value="">{{ __('public.insurance_portal.claims_opt_select_policy', [], $l) ?: '— Select Policy —' }}</option>
                        @foreach($policies as $policy)
                            <option value="{{ $policy->id }}">
                                {{ $policy->plan->provider->name ?? '' }} — {{ $policy->plan->name ?? '' }}
                                ({{ $policy->policy_number }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="policy_id" class="form-control" required placeholder="{{ __('public.insurance_portal.claims_ph_policy_id', [], $l) ?: 'Policy ID' }}">
                @endif
            </div>

            <h3 class="modal-fixed__title mt-3 mb-3">{{ __('public.insurance_portal.claims_line_items_title', [], $l) ?: 'Claim Line Items' }}</h3>
            <div id="claim-items">
                <div class="line-item form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_description', [], $l) ?: 'Description' }}</label>
                        <input type="text" name="items[0][description]" class="form-control" required placeholder="{{ __('public.insurance_portal.claims_ph_description', [], $l) ?: 'Service or procedure…' }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.insurance_portal.claims_lbl_qty', [], $l) ?: 'Qty' }}</label>
                        <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" step="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_unit_price', [], $l) ?: 'Unit Price' }}</label>
                        <input type="number" name="items[0][unit_price]" class="form-control" value="0.00" min="0" step="0.01" required>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm mb-3" onclick="addClaimItem()">
                <i data-lucide="plus"></i> {{ __('public.insurance_portal.claims_btn_add_item', [], $l) ?: 'Add Item' }}
            </button>

            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.claims_lbl_notes', [], $l) ?: 'Notes' }}</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
            </div>

            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeClaimModal()">{{ __('public.insurance_portal.claims_btn_cancel_modal', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="file-plus"></i> {{ __('public.insurance_portal.claims_btn_create', [], $l) ?: 'Create Claim' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Decide Modal --}}
<div id="decide-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.claims_modal_decide_title', [], $l) ?: 'Record Claim Decision' }}</h3></div>
        <form id="decide-form" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_decision', [], $l) ?: 'Decision' }}</label>
                <select name="decision" class="form-control" required>
                    <option value="approved">{{ __('public.insurance_portal.claims_opt_approved', [], $l) ?: 'Approved' }}</option>
                    <option value="partially_approved">{{ __('public.insurance_portal.claims_opt_partial_approved', [], $l) ?: 'Partially Approved' }}</option>
                    <option value="rejected">{{ __('public.insurance_portal.claims_opt_rejected', [], $l) ?: 'Rejected' }}</option>
                    <option value="more_information_required">{{ __('public.insurance_portal.claims_opt_more_info', [], $l) ?: 'More Information Required' }}</option>
                    <option value="disputed">{{ __('public.insurance_portal.claims_opt_disputed', [], $l) ?: 'Disputed' }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.claims_lbl_approved_amount', [], $l) ?: 'Approved Amount' }}</label>
                <input type="number" name="approved_amount" class="form-control" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_reason', [], $l) ?: 'Reason' }}</label>
                <textarea name="reason" class="form-control" rows="3" required maxlength="1000"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.claims_lbl_missing_info', [], $l) ?: 'Missing Information (if applicable)' }}</label>
                <textarea name="missing_information" class="form-control" rows="2" maxlength="1000"></textarea>
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDecideModal()">{{ __('public.insurance_portal.claims_btn_cancel_modal', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="gavel"></i> {{ __('public.insurance_portal.claims_btn_record_decision', [], $l) ?: 'Record Decision' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Payment Modal --}}
<div id="pay-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.claims_modal_pay_title', [], $l) ?: 'Record Claim Payment' }}</h3></div>
        <form id="pay-form" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_amount', [], $l) ?: 'Amount' }}</label>
                <input type="number" id="pay-amount" name="amount" class="form-control" min="0.01" step="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.claims_lbl_payment_method', [], $l) ?: 'Payment Method' }}</label>
                <select name="payment_method" class="form-control" required>
                    <option value="bank_transfer">{{ __('public.insurance_portal.claims_opt_bank_transfer', [], $l) ?: 'Bank Transfer' }}</option>
                    <option value="cheque">{{ __('public.insurance_portal.claims_opt_cheque', [], $l) ?: 'Cheque' }}</option>
                    <option value="eft">{{ __('public.insurance_portal.claims_opt_eft', [], $l) ?: 'EFT' }}</option>
                    <option value="cash">{{ __('public.insurance_portal.claims_opt_cash', [], $l) ?: 'Cash' }}</option>
                    <option value="other">{{ __('public.insurance_portal.claims_opt_other', [], $l) ?: 'Other' }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.claims_lbl_reference', [], $l) ?: 'Reference Number' }}</label>
                <input type="text" name="reference_number" class="form-control" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.claims_lbl_notes', [], $l) ?: 'Notes' }}</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closePayModal()">{{ __('public.insurance_portal.claims_btn_cancel_modal', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-success btn-sm">
                    <i data-lucide="banknote"></i> {{ __('public.insurance_portal.claims_btn_record_payment', [], $l) ?: 'Record Payment' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Cancel Claim Modal --}}
<div id="cancel-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title"><i data-lucide="alert-triangle"></i> {{ __('public.insurance_portal.claims_modal_cancel_title', [], $l) ?: 'Cancel claim' }}</h3></div>
        <form id="cancel-form" method="POST" action="">
            @csrf
            <p>{{ __('public.insurance_portal.claims_cancel_confirm_text', [], $l) ?: 'Cancel this claim? This action cannot be undone.' }}</p>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeCancelModal()">{{ __('public.insurance_portal.claims_btn_keep', [], $l) ?: 'Keep claim' }}</button>
                <button type="submit" class="btn btn-danger btn-sm">{{ __('public.insurance_portal.claims_btn_cancel_confirm', [], $l) ?: 'Cancel claim' }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    var claimItemCount = 1;
    var addItemLabel = @json(__('public.insurance_portal.claims_ph_description', [], $l) ?: 'Service or procedure…');

    function addClaimItem() {
        var container = document.getElementById('claim-items');
        var idx = claimItemCount++;
        var row = document.createElement('div');
        row.className = 'line-item form-row';
        row.innerHTML =
            '<div class="form-group"><input type="text" name="items[' + idx + '][description]" class="form-control" required placeholder="' + addItemLabel + '"></div>' +
            '<div class="form-group"><input type="number" name="items[' + idx + '][quantity]" class="form-control" value="1" min="1" step="1" required></div>' +
            '<div class="form-group"><input type="number" name="items[' + idx + '][unit_price]" class="form-control" value="0.00" min="0" step="0.01" required></div>' +
            '<div class="form-group"><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest(\'.line-item\').remove()">' +
            '<i data-lucide="trash-2"></i></button></div>';
        container.appendChild(row);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openClaimModal() { document.getElementById('claim-modal').classList.add('open'); }
    function closeClaimModal() { document.getElementById('claim-modal').classList.remove('open'); }
    document.getElementById('claim-modal').addEventListener('click', function(e) {
        if (e.target === this) closeClaimModal();
    });

    function openDecideModal(id) {
        document.getElementById('decide-form').setAttribute('action',
            '{{ url("/portals/insurance/claims") }}/' + id + '/decide');
        document.getElementById('decide-modal').classList.add('open');
    }
    function closeDecideModal() { document.getElementById('decide-modal').classList.remove('open'); }
    document.getElementById('decide-modal').addEventListener('click', function(e) {
        if (e.target === this) closeDecideModal();
    });

    function openPayModal(id, amount) {
        document.getElementById('pay-form').setAttribute('action',
            '{{ url("/portals/insurance/claims") }}/' + id + '/pay');
        document.getElementById('pay-amount').value = amount;
        document.getElementById('pay-modal').classList.add('open');
    }
    function closePayModal() { document.getElementById('pay-modal').classList.remove('open'); }
    document.getElementById('pay-modal').addEventListener('click', function(e) {
        if (e.target === this) closePayModal();
    });

    function openCancelModal(id) {
        document.getElementById('cancel-form').setAttribute('action',
            '{{ url("/portals/insurance/claims") }}/' + id + '/cancel');
        document.getElementById('cancel-modal').classList.add('open');
    }
    function closeCancelModal() { document.getElementById('cancel-modal').classList.remove('open'); }
    document.getElementById('cancel-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });
</script>
@endsection
