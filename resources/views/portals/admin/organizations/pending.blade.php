@extends('layouts.portal')
@section('title', __('public.adm_org_pend_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_org_idx_title'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.organizations.index') }}">{{ __('public.adm_org_idx_title') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_org_pend_title') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_org_pend_title') }}</h2>
    <div class="page-head__spacer"></div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($organizations->isEmpty())
<div class="panel">
    <div class="panel-body">
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
            <p>{{ __('public.adm_org_pend_empty') }}</p>
        </div>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> {{ $organizations->total() }} {{ __('public.adm_org_pend_panel_suffix') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_org_pend_col_name') }}</th>
                    <th>{{ __('public.adm_org_pend_col_type') }}</th>
                    <th>{{ __('public.adm_org_pend_col_region') }}</th>
                    <th>{{ __('public.adm_org_pend_col_status') }}</th>
                    <th>{{ __('public.adm_org_pend_col_applied') }}</th>
                    <th class="row-actions">{{ __('public.adm_org_pend_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($organizations as $org)
                @php $tBadge=match($org->type??''){'hospital'=>'badge-primary','clinic'=>'badge-success','pharmacy'=>'badge-warning','lab'=>'badge-neutral',default=>'badge-neutral'}; @endphp
                <tr>
                    <td data-label="{{ __('public.adm_org_pend_col_name') }}">
                        <span class="td-strong">{{ $org->name }}</span>
                        @if($org->email)<div class="td-muted">{{ $org->email }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.adm_org_pend_col_type') }}"><span class="badge {{ $tBadge }}">{{ ucfirst($org->type??'—') }}</span></td>
                    <td data-label="{{ __('public.adm_org_pend_col_region') }}" class="td-muted">{{ $org->region ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_org_pend_col_status') }}">
                        @if(($org->status??'')==='submitted')<span class="badge badge-primary">{{ __('public.adm_org_pend_status_submitted') }}</span>
                        @else<span class="badge badge-warning">{{ __('public.adm_org_pend_status_pending') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_org_pend_col_applied') }}" class="td-muted">{{ $org->created_at->format('d M Y') }}</td>
                    <td class="row-actions" data-label="{{ __('public.adm_org_pend_col_actions') }}">
                        <form method="POST" action="{{ route('portals.admin.organizations.approve', $org) }}" class="inline-form">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm"><i data-lucide="check"></i> {{ __('public.adm_org_pend_btn_approve') }}</button>
                        </form>
                        <button type="button" class="btn btn-warning btn-sm" onclick="openRejectModal('{{ $org->id }}','{{ addslashes($org->name) }}')">
                            <i data-lucide="x"></i> {{ __('public.adm_org_pend_btn_reject') }}
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($organizations->hasPages())
    <div class="panel-body">{{ $organizations->links() }}</div>
    @endif
</div>
@endif

{{-- Reject Modal --}}
<div id="reject-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reject-modal-title">
        <h3 class="modal__title" id="reject-modal-title"><i data-lucide="x-circle"></i> {{ __('public.adm_org_pend_modal_title') }}</h3>
        <form method="POST" id="reject-form" action="">
            @csrf @method('PATCH')
            <div class="modal__body">
                <p class="mb-6">{{ __('public.adm_org_pend_modal_rejecting') }} <strong id="reject-org-name"></strong></p>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_org_pend_modal_lbl_reason') }}</label>
                    <textarea name="reason" class="form-control" rows="4" placeholder="{{ __('public.adm_org_pend_modal_ph_reason') }}" required></textarea>
                    <div class="form-hint">{{ __('public.adm_org_pend_modal_hint') }}</div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('reject-modal')">{{ __('public.adm_org_pend_btn_cancel') }}</button>
                <button type="submit" class="btn btn-warning"><i data-lucide="x-circle"></i> {{ __('public.adm_org_pend_modal_btn_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function openRejectModal(id, name) {
    document.getElementById('reject-org-name').textContent = name;
    document.getElementById('reject-form').action = '/admin/organizations/' + id + '/reject';
    opOpenModal('reject-modal');
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
