@extends('layouts.portal')
@section('title', __('public.adm_roles_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
@endphp
@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="{{ __('od.breadcrumb') }}">
    <a href="{{ route('portals.admin.roles.index') }}">{{ __('public.adm_roles_idx_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_roles_idx_breadcrumb_sub') }}</span>
</nav>

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_roles_idx_breadcrumb') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_roles_idx_subtitle') }}</p>
    </div>
    <div class="od-row">
        <button type="button" class="od-btn od-btn--primary" data-call="opOpenModal" data-args="[&quot;create-role-modal&quot;]">
            <i data-lucide="plus"></i> {{ __('public.adm_roles_idx_btn_create') }}
        </button>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="shield"></i> {{ __('public.adm_roles_idx_panel_title') }} <span class="od-card__count">({{ method_exists($roles, 'total') ? $roles->total() : $roles->count() }})</span></h2>
    </div>
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_roles_idx_col_role_name') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_tier') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_description') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_portal') }}</th>
                    <th>{{ __('public.adm_roles_idx_col_users') }}</th>
                    <th class="od-table__actions">{{ __('public.adm_roles_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                @php
                    $platformPortals = ['admin','finance'];
                    $isPlatform = in_array($role->portal ?? '', $platformPortals, true);
                    $pColors=['patient_family'=>'normal','clinical'=>'ready','admin'=>'urgent','finance'=>'pending','connect'=>'teal','public'=>'neutral'];
                    $pc=$pColors[$role->portal??'']??'neutral';
                @endphp
                <tr>
                    <td data-label="{{ __('public.adm_roles_idx_col_role_name') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($role->name) }}" aria-hidden="true"><i data-lucide="shield"></i></span>
                            <div>
                                <a href="{{ route('portals.admin.roles.users', $role) }}" class="od-table__name">{{ $role->name }}</a>
                                @if($role->is_protected)<span class="od-pill od-pill--neutral">{{ __('public.adm_roles_idx_badge_protected') }}</span>@endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_roles_idx_col_tier') }}">
                        @if($isPlatform)<span class="od-pill od-pill--normal">{{ __('public.adm_roles_idx_badge_platform') }}</span>
                        @else<span class="od-pill od-pill--teal">{{ __('public.adm_roles_idx_badge_facility') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_roles_idx_col_description') }}" class="oda-gc-muted">{{ $role->description ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_roles_idx_col_portal') }}"><span class="od-pill od-pill--{{ $pc }}">{{ ucfirst(str_replace('_',' ',$role->portal??'—')) }}</span></td>
                    <td data-label="{{ __('public.adm_roles_idx_col_users') }}">{{ $role->users_count ?? 0 }}</td>
                    <td class="od-table__actions" data-label="{{ __('public.adm_roles_idx_col_actions') }}">
                        <div class="oda-gc-actions">
                        <a href="{{ route('portals.admin.roles.users', $role) }}" class="od-btn od-btn--row" aria-label="{{ __('public.adm_roles_idx_aria_view_users') }}" title="{{ __('public.adm_roles_idx_aria_view_users') }}"><i data-lucide="users"></i></a>
                        <button type="button" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.adm_roles_idx_aria_edit') }}" title="{{ __('public.adm_roles_idx_aria_edit') }}"
                            data-call="openEditRole" data-args="{{ json_encode([$role->id, $role->name, $role->description ?? '', $role->portal, $role->is_protected ? '1' : '0']) }}">
                            <i data-lucide="pencil"></i>
                        </button>
                        @if(!$role->is_protected)
                        <button type="button" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.adm_roles_idx_aria_delete') }}" title="{{ __('public.adm_roles_idx_aria_delete') }}" data-call="opOpenModal" data-args="[&quot;delete-role-{{ $role->id }}&quot;]"><i data-lucide="trash-2"></i></button>
                        @endif
                        </div>
                        @if(!$role->is_protected)
                        <div id="delete-role-{{ $role->id }}" class="modal-backdrop" hidden>
                            <div class="od-card oda-modal" role="dialog" aria-modal="true">
                                <div class="od-card__head"><h3 class="od-card__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_roles_idx_modal_delete_title') }}</h3></div>
                                {{-- portals.admin.roles.destroy is POST {id}/delete, like store and
                                     update in the same group. @method('DELETE') spoofed the verb to
                                     one that route does not answer, so Delete returned 405. --}}
                                <form method="POST" action="{{ route('portals.admin.roles.destroy', $role) }}" class="od-card__body">@csrf
                                    <p>{{ __('public.adm_roles_idx_modal_delete_body_before') }} <strong>{{ $role->name }}</strong>? {{ __('public.adm_roles_idx_cannot_undo') }}</p>
                                    <div class="oda-form__actions">
                                        <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;delete-role-{{ $role->id }}&quot;]">{{ __('public.adm_roles_idx_btn_cancel') }}</button>
                                        <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_roles_idx_btn_delete') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="shield"></i></span>
                        <p class="od-empty__text">{{ __('public.adm_roles_idx_empty') }}</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="od-card__foot">{{ $roles->links() }}</div>
</section>

{{-- Create Role Modal --}}
<div id="create-role-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="create-role-title">
        <div class="od-card__head"><h3 class="od-card__title" id="create-role-title"><i data-lucide="plus"></i> {{ __('public.adm_roles_idx_modal_create_title') }}</h3></div>
        <form method="POST" action="{{ route('portals.admin.roles.store') }}" class="od-card__body oda-form">
            @csrf
            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_roles_idx_lbl_role_name') }} *</span>
                <input type="text" name="name" class="od-input" required placeholder="{{ __('od.role_slug_example') }}">
                <span class="oda-help">{{ __('public.adm_roles_idx_hint_role_name') }}</span>
            </label>
            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_roles_idx_lbl_description') }}</span>
                <textarea name="description" class="oda-textarea" rows="2" placeholder="{{ __('public.adm_roles_idx_ph_description') }}"></textarea>
            </label>
            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_roles_idx_lbl_portal') }} *</span>
                <select name="portal" class="od-select" required>
                    <option value="">{{ __('public.adm_roles_idx_ph_select_portal') }}</option>
                    <option value="patient_family">{{ __('public.adm_roles_idx_opt_patient_family') }}</option>
                    <option value="clinical">{{ __('public.adm_roles_idx_opt_clinical') }}</option>
                    <option value="admin">{{ __('public.adm_roles_idx_opt_admin') }}</option>
                    <option value="finance">{{ __('public.adm_roles_idx_opt_finance') }}</option>
                    <option value="connect">{{ __('public.adm_roles_idx_opt_connect') }}</option>
                    <option value="public">{{ __('public.adm_roles_idx_opt_public') }}</option>
                </select>
            </label>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;create-role-modal&quot;]">{{ __('public.adm_roles_idx_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--primary">{{ __('public.adm_roles_idx_modal_create_title') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Role Modal --}}
<div id="edit-role-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="edit-role-title">
        <div class="od-card__head"><h3 class="od-card__title" id="edit-role-title"><i data-lucide="pencil"></i> {{ __('public.adm_roles_idx_modal_edit_title') }}</h3></div>
        {{-- Same as Delete above: portals.admin.roles.update is POST {id}/update,
             so no method spoofing. The action is filled in by openEditRole(). --}}
        <form method="POST" id="edit-role-form" action="" class="od-card__body oda-form">
            @csrf
            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_roles_idx_lbl_role_name') }}</span>
                <input type="text" id="edit-role-name" class="od-input" disabled>
                <span id="edit-role-note" class="oda-help"></span>
            </label>
            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_roles_idx_lbl_description') }}</span>
                <textarea name="description" id="edit-role-desc" class="oda-textarea" rows="2"></textarea>
            </label>
            <label class="oda-label oda-form__full">
                <span class="oda-label__text">{{ __('public.adm_roles_idx_lbl_portal') }} *</span>
                <select name="portal" id="edit-role-portal" class="od-select" required>
                    <option value="patient_family">{{ __('public.adm_roles_idx_opt_patient_family') }}</option>
                    <option value="clinical">{{ __('public.adm_roles_idx_opt_clinical') }}</option>
                    <option value="admin">{{ __('public.adm_roles_idx_opt_admin') }}</option>
                    <option value="finance">{{ __('public.adm_roles_idx_opt_finance') }}</option>
                    <option value="connect">{{ __('public.adm_roles_idx_opt_connect') }}</option>
                    <option value="public">{{ __('public.adm_roles_idx_opt_public') }}</option>
                </select>
            </label>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;edit-role-modal&quot;]">{{ __('public.adm_roles_idx_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--primary">{{ __('public.adm_roles_idx_btn_save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
@section('scripts')
<script @cspNonce>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
// From the named route, id substituted client-side. The hand-written
// '/portals/admin/roles/' + id it replaces is not a route at all (the update
// endpoint is {id}/update), so Save answered 405.
var editRoleUrl = '{{ route('portals.admin.roles.update', ['id' => '__ID__']) }}';
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
    document.getElementById('edit-role-form').action = editRoleUrl.replace('__ID__', encodeURIComponent(id));
    opOpenModal('edit-role-modal');
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
