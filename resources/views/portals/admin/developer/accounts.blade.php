@extends('layouts.portal')
@section('title', __('public.adm_dev_acc_title'))
@section('sidebar') @include('portals.admin.connect._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.connect') }}">{{ __('public.adm_dev_acc_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_dev_acc_breadcrumb_section') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_dev_acc_heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('public.adm_dev_acc_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['total'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_acc_stat_total') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['active'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_acc_stat_active') }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['sandbox_only'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_acc_stat_sandbox') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $stats['suspended'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_acc_stat_suspended') }}</div>
    </div>
</div>

@if($accounts->isEmpty())
<div class="empty-state">
    <div class="empty-state-icon"><i data-lucide="users"></i></div>
    <p>{{ __('public.adm_dev_acc_empty') }}</p>
</div>
@else
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('public.adm_dev_acc_col_developer') }}</th>
                <th>{{ __('public.adm_dev_acc_col_email') }}</th>
                <th>{{ __('public.adm_dev_acc_col_company') }}</th>
                <th>{{ __('public.adm_dev_acc_col_apps') }}</th>
                <th>{{ __('public.adm_dev_acc_col_access') }}</th>
                <th>{{ __('public.adm_dev_acc_col_status') }}</th>
                <th>{{ __('public.adm_dev_acc_col_joined') }}</th>
                <th class="row-actions">{{ __('public.adm_dev_acc_col_actions') }}</th>
            </tr></thead>
            <tbody>
            @foreach($accounts as $account)
            <tr>
                <td data-label="{{ __('public.adm_dev_acc_col_developer') }}">
                    <div class="td-strong">{{ $account->display_name ?? '—' }}</div>
                    @if($account->website_url)
                    <div class="td-muted"><a href="{{ $account->website_url }}" target="_blank">{{ Str::limit($account->website_url, 30) }}</a></div>
                    @endif
                </td>
                <td data-label="{{ __('public.adm_dev_acc_col_email') }}"><span class="code-token">{{ $account->email }}</span></td>
                <td data-label="{{ __('public.adm_dev_acc_col_company') }}">{{ $account->company_name ?? '—' }}</td>
                <td data-label="{{ __('public.adm_dev_acc_col_apps') }}"><span class="badge badge-neutral">{{ $account->integrationClients_count ?? 0 }}</span></td>
                <td data-label="{{ __('public.adm_dev_acc_col_access') }}">
                    @if($account->sandbox_only)
                    <span class="badge badge-primary">{{ __('public.adm_dev_acc_badge_sandbox') }}</span>
                    @else
                    <span class="badge badge-success">{{ __('public.adm_dev_acc_badge_production') }}</span>
                    @endif
                </td>
                <td data-label="{{ __('public.adm_dev_acc_col_status') }}">
                    <span class="{{ $account->statusBadgeClass() }}">@enum($account->status)</span>
                    @if($account->status === 'suspended' && $account->suspend_reason)
                    <div class="td-muted">{{ Str::limit($account->suspend_reason, 30) }}</div>
                    @endif
                </td>
                <td data-label="{{ __('public.adm_dev_acc_col_joined') }}">
                    {{ $account->created_at->format('d M Y') }}
                    @if($account->api_terms_accepted)<div class="td-muted">{{ __('public.adm_dev_acc_terms_accepted') }}</div>@endif
                </td>
                <td class="row-actions" data-label="{{ __('public.adm_dev_acc_col_actions') }}">
                    @if($account->status !== 'suspended')
                    <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('suspend-{{ $account->id }}')">{{ __('public.adm_dev_acc_btn_suspend') }}</button>
                    @else
                    <span class="td-muted">{{ __('public.adm_dev_acc_label_suspended') }}</span>
                    @if($account->suspended_at)<div class="td-muted">{{ $account->suspended_at->format('d M Y') }}</div>@endif
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $accounts->links() }}</div>
</div>

{{-- Suspend confirm modals --}}
@foreach($accounts as $account)
    @if($account->status !== 'suspended')
    <div id="suspend-{{ $account->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="suspend-{{ $account->id }}-title">
            <h3 class="modal__title" id="suspend-{{ $account->id }}-title"><i data-lucide="ban"></i> {{ __('public.adm_dev_acc_modal_heading') }}</h3>
            <form method="POST" action="{{ route('portals.admin.developer.accounts.suspend', $account->id) }}">
                @csrf
                <div class="modal__body">
                    <p>{{ __('public.adm_dev_acc_modal_body') }}</p>
                    <textarea name="reason" rows="3" required placeholder="{{ __('public.adm_dev_acc_modal_ph_reason') }}" class="form-control"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('suspend-{{ $account->id }}')">{{ __('public.adm_dev_acc_modal_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.adm_dev_acc_modal_btn_confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endif

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection