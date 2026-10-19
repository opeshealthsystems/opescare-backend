@extends('layouts.portal')
@section('title', 'User: ' . $user->name)
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $user->email) % count($gcTones)];
    $gcInit = mb_strtoupper(collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="{{ __('od.breadcrumb') }}">
    <a href="{{ route('admin.users.index') }}">{{ __('public.adm_users_show_breadcrumb_users') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $user->name }}</span>
</nav>

<div class="od-card od-detail oda-gc-detail">
    <div class="od-detail__head">
        <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true">{{ $gcInit }}</span>
        <div class="od-detail__main">
            <h1 class="od-detail__name">{{ $user->name }}</h1>
            <div class="od-detail__ref"><span>{{ $user->email }}</span><span>{{ $user->role?->name ?? 'no role' }}</span></div>
            <div>
                @if(($user->status??'')==='active')<span class="od-pill od-pill--ready od-pill--dot">{{ __('public.adm_users_show_badge_active') }}</span>
                @elseif(($user->status??'')==='suspended')<span class="od-pill od-pill--urgent od-pill--dot">{{ __('public.adm_users_show_badge_suspended') }}</span>
                @else<span class="od-pill od-pill--pending od-pill--dot">@enum($user->status ?? 'pending')</span>@endif
            </div>
        </div>
        <div class="od-row">
            @if(($user->status??'')==='suspended')
            <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="oda-inline-form">@csrf
                <button class="od-btn od-btn--primary"><i data-lucide="check-circle"></i> {{ __('public.adm_users_show_btn_activate') }}</button>
            </form>
            @else
            <button type="button" class="od-btn od-btn--secondary" data-call="opOpenModal" data-args="[&quot;suspend-modal&quot;]"><i data-lucide="ban"></i> {{ __('public.adm_users_show_btn_suspend') }}</button>
            @endif
            <button type="button" class="od-btn od-btn--danger" data-call="opOpenModal" data-args="[&quot;delete-modal&quot;]"><i data-lucide="trash-2"></i> {{ __('public.adm_users_show_btn_delete') }}</button>
        </div>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<nav class="od-tabs">
    <span class="od-tab is-active">{{ __('public.adm_users_show_tab_overview') }}</span>
</nav>

<div class="od-grid od-grid--main">
<div class="od-stack">
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="edit-2"></i> {{ __('public.adm_users_show_panel_edit') }}</h2></div>
    <div class="od-card__body">
        @include('portals.admin.users._facility_finder', [
            'searchAction' => route('admin.users.show', $user->id),
            'carry'        => [],
        ])

        {{-- admin.users.update is the PUT route. The form used to point at
             portals.admin.users.update, which is registered as a POST, so every
             save spoofed PUT at a POST-only route and came back 405. --}}
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="oda-form">
            @csrf @method('PUT')
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_users_show_lbl_name') }} *</span>
                <input type="text" name="name" class="od-input" value="{{ old('name', $user->name) }}" required>
                @error('name')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_users_show_lbl_email') }} *</span>
                <input type="email" name="email" class="od-input" value="{{ old('email', $user->email) }}" required>
                @error('email')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_users_show_lbl_role') }} *</span>
                <select name="role_id" class="od-select" required>
                    <option value="">{{ __('public.adm_users_show_ph_role') }}</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id',$user->role_id)==$role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            {{-- update() has always required `status`; the form never sent it,
                 so every save failed validation before it reached the model. --}}
            <label class="oda-label" for="user_status">
                <span class="oda-label__text">{{ __('admin_extra.users_lbl_status') }} *</span>
                <select name="status" id="user_status" class="od-select" required>
                    @foreach(['active', 'pending', 'suspended'] as $statusOption)
                    <option value="{{ $statusOption }}" @selected(old('status', $user->status ?? 'pending') === $statusOption)>@enum($statusOption)</option>
                    @endforeach
                </select>
                @error('status')<span class="oda-error">{{ $message }}</span>@enderror
            </label>

            @include('portals.admin.users._facility_picker', ['selected' => $user->primary_facility_id])

            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('public.adm_users_show_btn_save') }}</button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="key"></i> {{ __('public.adm_users_show_panel_reset_pw') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('admin.users.reset_password', $user->id) }}" class="oda-form">
            @csrf
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_users_show_lbl_new_pw') }} *</span>
                <input type="password" name="password" class="od-input" required>
                @error('password')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label">
                <span class="oda-label__text">{{ __('public.adm_users_show_lbl_confirm_pw') }} *</span>
                <input type="password" name="password_confirmation" class="od-input" required>
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="key"></i> {{ __('public.adm_users_show_btn_reset_pw') }}</button>
            </div>
        </form>
    </div>
</section>
</div>

<aside class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('public.adm_users_show_tab_overview') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('public.adm_users_show_stat_email') }}</dt><dd>{{ $user->email }}</dd>
            <dt>{{ __('public.adm_users_show_stat_role') }}</dt><dd>{{ $user->role?->name ?? 'no role' }}</dd>
            <dt>{{ __('admin_extra.users_stat_facility') }}</dt><dd>{{ $user->primaryFacility?->name ?? __('admin_extra.users_facility_none') }}</dd>
            <dt>{{ __('public.adm_users_show_stat_status') }}</dt><dd>@enum($user->status ?? 'pending')</dd>
            <dt>{{ __('public.adm_users_show_stat_joined') }}</dt><dd>{{ $user->created_at?->translatedFormat('M d, Y') ?? '—' }}</dd>
            <dt>{{ __('public.adm_users_show_stat_last_login') }}</dt><dd>{{ isset($user->last_login_at) ? $user->last_login_at->diffForHumans() : __('public.adm_users_show_stat_never') }}</dd>
        </dl>
    </div>
</aside>
</div>

<div id="suspend-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <div class="od-card__head"><h3 class="od-card__title" id="suspend-modal-title"><i data-lucide="ban"></i> {{ __('public.adm_users_show_modal_suspend_title') }}</h3></div>
        <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}" class="od-card__body">@csrf
            <p>{{ __('public.adm_users_show_modal_suspend_body', ['name' => $user->name]) }}</p>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;suspend-modal&quot;]">{{ __('public.adm_users_show_modal_suspend_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--danger"><i data-lucide="ban"></i> {{ __('public.adm_users_show_modal_suspend_btn_suspend') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="modal-backdrop" hidden>
    <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="od-card__head"><h3 class="od-card__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_users_show_modal_delete_title') }}</h3></div>
        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="od-card__body">@csrf @method('DELETE')
            <p>{{ __('public.adm_users_show_modal_delete_body', ['name' => $user->name]) }}</p>
            <div class="oda-form__actions">
                <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;delete-modal&quot;]">{{ __('public.adm_users_show_modal_delete_btn_cancel') }}</button>
                <button type="submit" class="od-btn od-btn--danger"><i data-lucide="trash-2"></i> {{ __('public.adm_users_show_modal_delete_btn_delete') }}</button>
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
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
