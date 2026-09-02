@extends('layouts.portal')
@section('title', __('public.adm_dev_prod_title'))
@section('sidebar') @include('portals.admin.connect._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.connect') }}">{{ __('public.adm_dev_prod_breadcrumb_parent') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_dev_prod_breadcrumb_section') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_dev_prod_heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('public.adm_dev_prod_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $stats['pending'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_prod_stat_pending') }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['under_review'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_prod_stat_under_review') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $stats['approved'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_prod_stat_approved') }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $stats['rejected'] }}</div>
        <div class="stat-card__label">{{ __('public.adm_dev_prod_stat_rejected') }}</div>
    </div>
</div>

{{-- Filter tabs --}}
<div class="tabs mb-6">
    @foreach(['all'=>__('public.adm_dev_prod_tab_all'),'pending'=>__('public.adm_dev_prod_tab_pending'),'under_review'=>__('public.adm_dev_prod_tab_under_review'),'approved'=>__('public.adm_dev_prod_tab_approved'),'rejected'=>__('public.adm_dev_prod_tab_rejected')] as $val=>$label)
    @php $target = $val === 'all' ? null : $val; $isActive = request('status', $target) === $target; @endphp
    <a href="{{ request()->fullUrlWithQuery(['status'=>$target]) }}" class="tab {{ $isActive ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

@if($requests->isEmpty())
<div class="empty-state">
    <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
    <p>{{ __('public.adm_dev_prod_empty') }}</p>
</div>
@else
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('public.adm_dev_prod_col_developer') }}</th>
                <th>{{ __('public.adm_dev_prod_col_use_case') }}</th>
                <th>{{ __('public.adm_dev_prod_col_scopes') }}</th>
                <th>{{ __('public.adm_dev_prod_col_patient_data') }}</th>
                <th>{{ __('public.adm_dev_prod_col_status') }}</th>
                <th>{{ __('public.adm_dev_prod_col_submitted') }}</th>
                <th class="row-actions">{{ __('public.adm_dev_prod_col_actions') }}</th>
            </tr></thead>
            <tbody>
            @foreach($requests as $req)
            <tr>
                <td data-label="{{ __('public.adm_dev_prod_col_developer') }}">
                    <div class="td-strong">{{ $req->developerAccount->display_name ?? $req->developerAccount->email ?? '—' }}</div>
                    @if($req->integration_client_id)<div class="code-token">{{ Str::limit($req->integration_client_id, 28) }}</div>@endif
                </td>
                <td data-label="{{ __('public.adm_dev_prod_col_use_case') }}">
                    <div>{{ Str::limit($req->use_case, 55) }}</div>
                    @if($req->technical_description)<div class="td-muted">{{ Str::limit($req->technical_description, 60) }}</div>@endif
                </td>
                <td data-label="{{ __('public.adm_dev_prod_col_scopes') }}"><span class="badge badge-neutral">{{ count((array)$req->requested_scopes) }} {{ __('public.adm_dev_prod_scopes_label') }}</span></td>
                <td data-label="{{ __('public.adm_dev_prod_col_patient_data') }}">
                    @if($req->handles_patient_data)
                    <span class="badge badge-warning">{{ __('public.adm_dev_prod_badge_yes') }}</span>
                    @else
                    <span class="badge badge-neutral">{{ __('public.adm_dev_prod_badge_no') }}</span>
                    @endif
                </td>
                <td data-label="{{ __('public.adm_dev_prod_col_status') }}">
                    <span class="{{ $req->statusBadgeClass() }}">@enum($req->status)</span>
                    @if($req->reviewed_at)<div class="td-muted">{{ $req->reviewed_at->format('d M Y') }}</div>@endif
                </td>
                <td data-label="{{ __('public.adm_dev_prod_col_submitted') }}">
                    {{ $req->created_at->format('d M Y') }}
                    @if($req->estimated_daily_requests)<div class="td-muted">{{ $req->estimated_daily_requests }} {{ __('public.adm_dev_prod_req_per_day') }}</div>@endif
                </td>
                <td class="row-actions" data-label="{{ __('public.adm_dev_prod_col_actions') }}">
                    @if(in_array($req->status, ['pending','under_review']))
                        <button type="button" class="btn btn-success btn-sm" onclick="opOpenModal('approve-{{ $req->id }}')"><i data-lucide="check"></i> {{ __('public.adm_dev_prod_btn_approve') }}</button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('reject-{{ $req->id }}')"><i data-lucide="x"></i> {{ __('public.adm_dev_prod_btn_reject') }}</button>
                    @elseif($req->status === 'approved')
                        <span class="text-success">{{ __('public.adm_dev_prod_status_approved') }}</span>
                        @if($req->review_notes)<div class="td-muted">{{ Str::limit($req->review_notes, 30) }}</div>@endif
                    @else
                        <span class="text-danger">{{ __('public.adm_dev_prod_status_rejected') }}</span>
                        @if($req->rejected_reason)<div class="td-muted">{{ Str::limit($req->rejected_reason, 30) }}</div>@endif
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $requests->links() }}</div>
</div>

{{-- Approve / Reject confirm modals --}}
@foreach($requests as $req)
    @if(in_array($req->status, ['pending','under_review']))
    <div id="approve-{{ $req->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="approve-{{ $req->id }}-title">
            <h3 class="modal__title" id="approve-{{ $req->id }}-title"><i data-lucide="check-circle"></i> {{ __('public.adm_dev_prod_modal_approve_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.developer.production_requests.approve', $req->id) }}">
                @csrf
                <div class="modal__body"><p>{{ __('public.adm_dev_prod_modal_approve_body') }}</p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('approve-{{ $req->id }}')">{{ __('public.adm_dev_prod_modal_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('public.adm_dev_prod_btn_approve') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div id="reject-{{ $req->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reject-{{ $req->id }}-title">
            <h3 class="modal__title" id="reject-{{ $req->id }}-title"><i data-lucide="x-circle"></i> {{ __('public.adm_dev_prod_modal_reject_title') }}</h3>
            <form method="POST" action="{{ route('portals.admin.developer.production_requests.reject', $req->id) }}">
                @csrf
                <div class="modal__body">
                    <p>{{ __('public.adm_dev_prod_modal_reject_body') }}</p>
                    <textarea name="reason" rows="3" required placeholder="{{ __('public.adm_dev_prod_modal_reject_ph') }}" class="form-control"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('reject-{{ $req->id }}')">{{ __('public.adm_dev_prod_modal_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.adm_dev_prod_modal_btn_confirm_reject') }}</button>
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