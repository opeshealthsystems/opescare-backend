@extends('layouts.portal')
@section('title', __('public.adm_org_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_org_idx_title'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.organizations.index') }}">{{ __('public.adm_org_idx_title') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_org_idx_breadcrumb_dir') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_org_idx_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.organizations.pending') }}" class="btn btn-primary">
        <i data-lucide="clock"></i>
        {{ __('public.adm_org_pend_title') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($pendingCount > 0)
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> {{ $pendingCount }} organization{{ $pendingCount>1?'s':'' }} awaiting approval</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_org_idx_col_name') }}</th>
                    <th>{{ __('public.adm_org_idx_col_type') }}</th>
                    <th>{{ __('public.adm_org_idx_col_license') }}</th>
                    <th class="row-actions">{{ __('public.adm_org_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($pending as $p)
                <tr>
                    <td data-label="{{ __('public.adm_org_idx_col_name') }}"><span class="td-strong">{{ $p->name }}</span></td>
                    <td data-label="{{ __('public.adm_org_idx_col_type') }}">{{ ucfirst($p->type??'') }}</td>
                    <td data-label="{{ __('public.adm_org_idx_col_license') }}" class="td-mono">{{ $p->license_number??'—' }}</td>
                    <td class="row-actions" data-label="{{ __('public.adm_org_idx_col_actions') }}">
                        <form method="POST" action="{{ route('admin.organizations.approve',$p->id) }}" class="inline-form">@csrf
                            <button type="submit" class="btn btn-success btn-sm"><i data-lucide="check-circle"></i> {{ __('public.adm_org_idx_btn_approve') }}</button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('reject-pending-{{ $p->id }}')"><i data-lucide="x-circle"></i> {{ __('public.adm_org_idx_btn_reject') }}</button>
                        <div id="reject-pending-{{ $p->id }}" class="modal-backdrop mt-6" hidden>
                            <div class="modal" role="dialog" aria-modal="true">
                                <h3 class="modal__title"><i data-lucide="x-circle"></i> {{ __('public.adm_org_idx_modal_reject_title') }}</h3>
                                <form method="POST" action="{{ route('admin.organizations.reject',$p->id) }}">@csrf
                                    <div class="modal__body"><p>{{ __('public.adm_org_idx_modal_reject_q') }} <strong>{{ $p->name }}</strong>?</p></div>
                                    <div class="modal__footer">
                                        <button type="button" class="btn btn-ghost" onclick="opCloseModal('reject-pending-{{ $p->id }}')">{{ __('public.adm_org_idx_btn_cancel') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ __('public.adm_org_idx_btn_reject') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> Total: {{ $total }}</h3>
        <div class="page-head__spacer"></div>
        @foreach($byType as $type => $count)<span class="badge badge-neutral">{{ $count }} {{ $type }}</span> @endforeach
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_org_idx_col_name') }}</th>
                    <th>{{ __('public.adm_org_idx_col_type') }}</th>
                    <th>{{ __('public.adm_org_idx_col_license') }}</th>
                    <th>{{ __('public.adm_org_idx_col_status') }}</th>
                    <th>{{ __('public.adm_org_idx_col_since') }}</th>
                    <th class="row-actions">{{ __('public.adm_org_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($facilities as $f)
                <tr>
                    <td data-label="{{ __('public.adm_org_idx_col_name') }}"><span class="td-strong">{{ $f->name }}</span></td>
                    <td data-label="{{ __('public.adm_org_idx_col_type') }}">{{ ucfirst($f->type??'') }}</td>
                    <td data-label="{{ __('public.adm_org_idx_col_license') }}" class="td-mono">{{ $f->license_number??'—' }}</td>
                    <td data-label="{{ __('public.adm_org_idx_col_status') }}">
                        @if($f->status==='active')<span class="badge badge-success">@enum('active')</span>
                        @elseif(in_array($f->status,['suspended','rejected']))<span class="badge badge-danger">@enum($f->status)</span>
                        @else<span class="badge badge-warning">@enum($f->status ?? 'pending')</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_org_idx_col_since') }}" class="td-muted">{{ $f->created_at?->format('d M Y') }}</td>
                    <td class="row-actions" data-label="{{ __('public.adm_org_idx_col_actions') }}">
                        @if($f->status==='pending')
                        <form method="POST" action="{{ route('admin.organizations.approve',$f->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.adm_org_idx_btn_approve') }}" title="{{ __('public.adm_org_idx_btn_approve') }}"><i data-lucide="check-circle"></i></button>
                        </form>
                        @endif
                        <button type="button" class="icon-btn" aria-label="{{ __('public.adm_org_idx_btn_delete') }}" title="{{ __('public.adm_org_idx_btn_delete') }}" onclick="opOpenModal('delete-org-{{ $f->id }}')"><i data-lucide="trash-2"></i></button>
                        <div id="delete-org-{{ $f->id }}" class="modal-backdrop mt-6" hidden>
                            <div class="modal" role="dialog" aria-modal="true">
                                <h3 class="modal__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_org_idx_modal_delete_title') }}</h3>
                                <form method="POST" action="{{ route('admin.organizations.destroy',$f->id) }}">@csrf @method('DELETE')
                                    <div class="modal__body"><p>{{ __('public.adm_org_idx_modal_delete_body_before') }} <strong>{{ $f->name }}</strong>? {{ __('public.adm_org_idx_cannot_undo') }}</p></div>
                                    <div class="modal__footer">
                                        <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-org-{{ $f->id }}')">{{ __('public.adm_org_idx_btn_cancel') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ __('public.adm_org_idx_btn_delete') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_org_idx_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $facilities->links() }}</div>
</div>

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
