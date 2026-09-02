@extends('layouts.portal')
@php $l = app()->getLocale(); @endphp

@section('title', __('public.insurance_portal.preauths_page_title', [], $l) ?: 'Preauthorization Requests')

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
@section('breadcrumb_section', __('public.insurance_portal.preauths_breadcrumb', [], $l) ?: 'Preauthorization')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.insurance_portal.preauths_page_title', [], $l) ?: 'Preauthorization Requests' }}</h1>
        <p class="page-subtitle">{{ __('public.insurance_portal.preauths_subtitle', [], $l) ?: 'Request and track prior approvals for services and procedures.' }}</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openPreauthModal()">
        <i data-lucide="plus-circle"></i>
        {{ __('public.insurance_portal.preauths_btn_new', [], $l) ?: 'New Request' }}
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
<form method="GET" action="{{ route('portals.insurance.preauths') }}" class="filter-bar">
    <select name="status" class="filter-select">
        <option value="">{{ __('public.insurance_portal.preauths_filter_all_statuses', [], $l) ?: 'All Statuses' }}</option>
        @foreach(['draft','submitted','under_review','approved','rejected','more_information_required','expired','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('public.insurance_portal.preauths_btn_filter', [], $l) ?: 'Filter' }}
    </button>
    <a href="{{ route('portals.insurance.preauths') }}" class="btn btn-ghost btn-sm">{{ __('public.insurance_portal.preauths_btn_clear', [], $l) ?: 'Clear' }}</a>
</form>

<div class="panel">
    <div class="panel-body--flush">
        @if(count($preauths) === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
                <h3>{{ __('public.insurance_portal.preauths_empty_title', [], $l) ?: 'No Preauthorization Requests' }}</h3>
                <p>{{ __('public.insurance_portal.preauths_empty_desc', [], $l) ?: 'Create a preauthorization request for services that require prior approval.' }}</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="openPreauthModal()">
                    {{ __('public.insurance_portal.preauths_btn_new', [], $l) ?: 'New Request' }}
                </button>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.insurance_portal.preauths_col_service', [], $l) ?: 'Service' }}</th>
                            <th>{{ __('public.insurance_portal.preauths_col_policy', [], $l) ?: 'Policy' }}</th>
                            <th>{{ __('public.insurance_portal.preauths_col_estimated', [], $l) ?: 'Estimated' }}</th>
                            <th>{{ __('public.insurance_portal.preauths_col_submitted', [], $l) ?: 'Submitted' }}</th>
                            <th>{{ __('public.insurance_portal.preauths_col_status', [], $l) ?: 'Status' }}</th>
                            <th>{{ __('public.insurance_portal.preauths_col_decision', [], $l) ?: 'Decision' }}</th>
                            <th>{{ __('public.insurance_portal.preauths_col_actions', [], $l) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preauths as $pa)
                        @php
                            $statusBadge = match($pa->status) {
                                'approved'                  => 'badge-success',
                                'rejected'                  => 'badge-danger',
                                'submitted','under_review'  => 'badge-primary',
                                'more_information_required' => 'badge-warning',
                                'expired','cancelled'       => 'badge-neutral',
                                default                     => 'badge-neutral',
                            };
                            $decision = $pa->latestDecision;
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_service', [], $l) ?: 'Service' }}">
                                <span class="td-strong">{{ Str::limit($pa->service_description, 60) }}</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_policy', [], $l) ?: 'Policy' }}">
                                <span class="td-strong">{{ $pa->policy->plan->provider->name ?? '--' }}</span>
                                <div class="td-muted">{{ $pa->policy->policy_number ?? '--' }}</div>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_estimated', [], $l) ?: 'Estimated' }}">
                                {{ $pa->estimated_amount ? number_format($pa->estimated_amount, 2) : '--' }}
                            </td>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_submitted', [], $l) ?: 'Submitted' }}">
                                {{ $pa->submitted_at ? \Carbon\Carbon::parse($pa->submitted_at)->format('M d, Y') : '--' }}
                            </td>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_status', [], $l) ?: 'Status' }}">
                                <span class="badge {{ $statusBadge }}">@enum($pa->status)</span>
                            </td>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_decision', [], $l) ?: 'Decision' }}">
                                @if($decision)
                                    <span class="td-strong">@enum($decision->decision, 'decision')</span>
                                    @if($decision->approved_amount)
                                        <div class="td-muted">{{ number_format($decision->approved_amount, 2) }}</div>
                                    @endif
                                @else
                                    <span class="badge badge-neutral">{{ __('public.insurance_portal.preauths_lbl_pending', [], $l) ?: 'Pending' }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.insurance_portal.preauths_col_actions', [], $l) ?: 'Actions' }}" class="row-actions">
                                @if($pa->status === 'draft')
                                    <form method="POST" action="{{ route('portals.insurance.preauths.submit', $pa->id) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i data-lucide="send"></i> {{ __('public.insurance_portal.preauths_btn_submit', [], $l) ?: 'Submit' }}
                                        </button>
                                    </form>
                                @endif
                                @if(in_array($pa->status, ['submitted','under_review','more_information_required']))
                                    <button type="button" class="btn btn-teal btn-sm" onclick="openDecideModal('{{ $pa->id }}')">
                                        <i data-lucide="gavel"></i> {{ __('public.insurance_portal.preauths_btn_decide', [], $l) ?: 'Decide' }}
                                    </button>
                                @endif
                                @if($pa->isPending())
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="openCancelModal('{{ $pa->id }}')">{{ __('public.insurance_portal.preauths_btn_cancel', [], $l) ?: 'Cancel' }}</button>
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

{{-- New Preauth Modal --}}
<div id="preauth-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--lg">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.preauths_modal_new_title', [], $l) ?: 'New Preauthorization Request' }}</h3></div>
        <form method="POST" action="{{ route('portals.insurance.preauths.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.preauths_lbl_policy', [], $l) ?: 'Patient Policy' }}</label>
                @if(count($policies) > 0)
                    <select name="policy_id" class="form-control" required>
                        <option value="">{{ __('public.insurance_portal.preauths_opt_select_policy', [], $l) ?: '— Select Policy —' }}</option>
                        @foreach($policies as $policy)
                            <option value="{{ $policy->id }}">
                                {{ $policy->plan->provider->name ?? '' }} — {{ $policy->plan->name ?? '' }}
                                ({{ $policy->policy_number }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="policy_id" class="form-control" required placeholder="{{ __('public.insurance_portal.preauths_ph_policy_id', [], $l) ?: 'Policy ID' }}">
                @endif
            </div>
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.preauths_lbl_service_desc', [], $l) ?: 'Service / Procedure Description' }}</label>
                <input type="text" name="service_description" class="form-control" required maxlength="500"
                    placeholder="{{ __('public.insurance_portal.preauths_ph_service_desc', [], $l) ?: 'e.g. MRI Brain Scan, Surgical Procedure…' }}">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.preauths_lbl_clinical_just', [], $l) ?: 'Clinical Justification' }}</label>
                <textarea name="clinical_justification" class="form-control" rows="3" maxlength="2000"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.preauths_lbl_estimated', [], $l) ?: 'Estimated Amount' }}</label>
                <input type="number" name="estimated_amount" class="form-control" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closePreauthModal()">{{ __('public.insurance_portal.preauths_btn_cancel_modal', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="clipboard-list"></i> {{ __('public.insurance_portal.preauths_btn_create', [], $l) ?: 'Create Request' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Decide Modal --}}
<div id="decide-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title">{{ __('public.insurance_portal.preauths_modal_decide_title', [], $l) ?: 'Record Decision' }}</h3></div>
        <form id="decide-form" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.preauths_lbl_decision', [], $l) ?: 'Decision' }}</label>
                <select name="decision" class="form-control" required>
                    <option value="approved">{{ __('public.insurance_portal.preauths_opt_approved', [], $l) ?: 'Approved' }}</option>
                    <option value="rejected">{{ __('public.insurance_portal.preauths_opt_rejected', [], $l) ?: 'Rejected' }}</option>
                    <option value="more_information_required">{{ __('public.insurance_portal.preauths_opt_more_info', [], $l) ?: 'More Information Required' }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.preauths_lbl_approved_amount', [], $l) ?: 'Approved Amount' }}</label>
                <input type="number" name="approved_amount" class="form-control" min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.insurance_portal.preauths_lbl_auth_number', [], $l) ?: 'Authorization Number' }}</label>
                <input type="text" name="authorization_number" class="form-control" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label form-label-required">{{ __('public.insurance_portal.preauths_lbl_reason', [], $l) ?: 'Reason' }}</label>
                <textarea name="reason" class="form-control" rows="3" required maxlength="1000"></textarea>
            </div>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeDecideModal()">{{ __('public.insurance_portal.preauths_btn_cancel_modal', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="gavel"></i> {{ __('public.insurance_portal.preauths_btn_record_decision', [], $l) ?: 'Record Decision' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Cancel Request Modal --}}
<div id="cancel-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head"><h3 class="modal-fixed__title"><i data-lucide="alert-triangle"></i> {{ __('public.insurance_portal.preauths_modal_cancel_title', [], $l) ?: 'Cancel request' }}</h3></div>
        <form id="cancel-form" method="POST" action="">
            @csrf
            <p>{{ __('public.insurance_portal.preauths_cancel_confirm_text', [], $l) ?: 'Cancel this request?' }}</p>
            <div class="form-actions-end">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeCancelModal()">{{ __('public.insurance_portal.preauths_btn_keep', [], $l) ?: 'Keep request' }}</button>
                <button type="submit" class="btn btn-danger btn-sm">{{ __('public.insurance_portal.preauths_btn_cancel_confirm', [], $l) ?: 'Cancel request' }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openPreauthModal() { document.getElementById('preauth-modal').classList.add('open'); }
    function closePreauthModal() { document.getElementById('preauth-modal').classList.remove('open'); }
    document.getElementById('preauth-modal').addEventListener('click', function(e) {
        if (e.target === this) closePreauthModal();
    });

    function openDecideModal(id) {
        document.getElementById('decide-form').setAttribute('action',
            '{{ url("/portals/insurance/preauths") }}/' + id + '/decide');
        document.getElementById('decide-modal').classList.add('open');
    }
    function closeDecideModal() { document.getElementById('decide-modal').classList.remove('open'); }
    document.getElementById('decide-modal').addEventListener('click', function(e) {
        if (e.target === this) closeDecideModal();
    });

    function openCancelModal(id) {
        document.getElementById('cancel-form').setAttribute('action',
            '{{ url("/portals/insurance/preauths") }}/' + id + '/cancel');
        document.getElementById('cancel-modal').classList.add('open');
    }
    function closeCancelModal() { document.getElementById('cancel-modal').classList.remove('open'); }
    document.getElementById('cancel-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });
</script>
@endsection
