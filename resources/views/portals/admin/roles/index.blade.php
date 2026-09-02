@extends('layouts.portal')
@section('title', __('public.adm_roles_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_roles_idx_breadcrumb'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.roles.index') }}">{{ __('public.adm_roles_idx_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_roles_idx_breadcrumb_sub') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_roles_idx_breadcrumb') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary" onclick="opOpenModal('create-role-modal')">
        <i data-lucide="plus"></i> {{ __('public.adm_roles_idx_btn_create') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="shield"></i> {{ __('public.adm_roles_idx_panel_title') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_roles_idx_col_role_name') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_tier') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_description') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_portal') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_users') }}</th>
                    <th class="row-actions">{{ __('public.adm_roles_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                @php
                    $platformPortals = ['admin','finance'];
                    $isPlatform = in_array($role->portal ?? '', $platformPortals, true);
                    $pColors=['patient_family'=>'badge-primary','clinical'=>'badge-success','admin'=>'badge-danger','finance'=>'badge-warning','connect'=>'badge-teal','public'=>'badge-neutral'];
                    $pc=$pColors[$role->portal??'']??'badge-neutral';
                @endphp
                <tr>
                    <td data-label="{{ __('public.adm_roles_idx_col_role_name') }}">
                        <span class="td-strong">{{ $role->name }}</span>
                        @if($role->is_protected)<span class="badge badge-neutral">{{ __('public.adm_roles_idx_badge_protected') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_roles_idx_col_tier') }}">
                        @if($isPlatform)<span class="badge badge-primary">{{ __('public.adm_roles_idx_badge_platform') }}</span>
                        @else<span class="badge badge-teal">{{ __('public.adm_roles_idx_badge_facility') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_roles_idx_col_description') }}" class="td-muted">{{ $role->description ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_roles_idx_col_portal') }}"><span class="badge {{ $pc }}">{{ ucfirst(str_replace('_',' ',$role->portal??'—')) }}</span></td>
                    <td data-label="{{ __('public.adm_roles_idx_col_users') }}">{{ $role->users_count ?? 0 }}</td>
                    <td class="row-actions" data-label="{{ __('public.adm_roles_idx_col_actions') }}">
                        <a href="{{ route('portals.admin.roles.users', $role) }}" class="icon-btn" aria-label="{{ __('public.adm_roles_idx_aria_view_users') }}" title="{{ __('public.adm_roles_idx_aria_view_users') }}"><i data-lucide="users"></i></a>
                        <button type="button" class="icon-btn" aria-label="{{ __('public.adm_roles_idx_aria_edit') }}" title="{{ __('public.adm_roles_idx_aria_edit') }}"
                            onclick="openEditRole('{{ $role->id }}','{{ addslashes($role->name) }}','{{ addslashes($role->description ?? '') }}','{{ $role->portal }}','{{ $role->is_protected ? '1':'0' }}')">
                            <i data-lucide="pencil"></i>
                        </button>
                        @if(!$role->is_protected)
                        <button type="button" class="icon-btn" aria-label="{{ __('public.adm_roles_idx_aria_delete') }}" title="{{ __('public.adm_roles_idx_aria_delete') }}" onclick="opOpenModal('delete-role-{{ $role->id }}')"><i data-lucide="trash-2"></i></button>
                        <div id="delete-role-{{ $role->id }}" class="modal-backdrop mt-6" hidden>
                            <div class="modal" role="dialog" aria-modal="true">
                                <h3 class="modal__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_roles_idx_modal_delete_title') }}</h3>
                                <form method="POST" action="{{ route('portals.admin.roles.destroy', $role) }}">@csrf @method('DELETE')
                                    <div class="modal__body"><p>{{ __('public.adm_roles_idx_modal_delete_body_before') }} <strong>{{ $role->name }}</strong>? {{ __('public.adm_roles_idx_cannot_undo') }}</p></div>
                                    <div class="modal__footer">
                                        <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-role-{{ $role->id }}')">{{ __('public.adm_roles_idx_btn_cancel') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ __('public.adm_roles_idx_btn_delete') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_roles_idx_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $roles->links() }}</div>
</div>

{{-- Create Role Modal --}}
<div id="create-role-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-role-title">
        <h3 class="modal__title" id="create-role-title"><i data-lucide="plus"></i> {{ __('public.adm_roles_idx_modal_create_title') }}</h3>
        <form method="POST" action="{{ route('portals.admin.roles.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-group mb-6">
                    <label class="form-label form-label-required">{{ __('public.adm_roles_idx_lbl_role_name') }}</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. senior_nurse">
                    <div class="form-hint">{{ __('public.adm_roles_idx_hint_role_name') }}</div>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label">{{ __('public.adm_roles_idx_lbl_description') }}</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="{{ __('public.adm_roles_idx_ph_description') }}"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_roles_idx_lbl_portal') }}</label>
                    <select name="portal" class="form-control" required>
                        <option value="">{{ __('public.adm_roles_idx_ph_select_portal') }}</option>
                        <option value="patient_family">{{ __('public.adm_roles_idx_opt_patient_family') }}</option>
                        <option value="clinical">{{ __('public.adm_roles_idx_opt_clinical') }}</option>
                        <option value="admin">{{ __('public.adm_roles_idx_opt_admin') }}</option>
                        <option value="finance">{{ __('public.adm_roles_idx_opt_finance') }}</option>
                        <option value="connect">{{ __('public.adm_roles_idx_opt_connect') }}</option>
                        <option value="public">{{ __('public.adm_roles_idx_opt_public') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('create-role-modal')">{{ __('public.adm_roles_idx_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_roles_idx_modal_create_title') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Role Modal --}}
<div id="edit-role-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-role-title">
        <h3 class="modal__title" id="edit-role-title"><i data-lucide="pencil"></i> {{ __('public.adm_roles_idx_modal_edit_title') }}</h3>
        <form method="POST" id="edit-role-form" action="">
            @csrf @method('PUT')
            <div class="modal__body">
                <div class="form-group mb-6">
                    <label class="form-label">{{ __('public.adm_roles_idx_lbl_role_name') }}</label>
                    <input type="text" id="edit-role-name" class="form-control" disabled>
                    <div id="edit-role-note" class="form-hint"></div>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label">{{ __('public.adm_roles_idx_lbl_description') }}</label>
                    <textarea name="description" id="edit-role-desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_roles_idx_lbl_portal') }}</label>
                    <select name="portal" id="edit-role-portal" class="form-control" required>
                        <option value="patient_family">{{ __('public.adm_roles_idx_opt_patient_family') }}</option>
                        <option value="clinical">{{ __('public.adm_roles_idx_opt_clinical') }}</option>
                        <option value="admin">{{ __('public.adm_roles_idx_opt_admin') }}</option>
                        <option value="finance">{{ __('public.adm_roles_idx_opt_finance') }}</option>
                        <option value="connect">{{ __('public.adm_roles_idx_opt_connect') }}</option>
                        <option value="public">{{ __('public.adm_roles_idx_opt_public') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('edit-role-modal')">{{ __('public.adm_roles_idx_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_roles_idx_btn_save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function openEditRole(id, name, description, portal, isProtected) {
    document.getElementById('edit-role-name').value    = name;
    document.getElementById('edit-role-desc').value    = description;
    document.getElementById('edit-role-portal').value  = portal;
    const protectedEl = document.getElementById('edit-role-portal');
    const noteEl = document.getElementById('edit-role-note');
    if (isProtected === '1') {
        protectedEl.disabled = true;
        noteEl.textContent = '{{ __('public.adm_roles_idx_protected_note') }}';
    } else {
        protectedEl.disabled = false;
        noteEl.textContent = '';
    }
    document.getElementById('edit-role-form').action = '/portals/admin/roles/' + id;
    opOpenModal('edit-role-modal');
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
