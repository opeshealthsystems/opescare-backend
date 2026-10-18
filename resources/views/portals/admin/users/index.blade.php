@extends('layouts.portal')
@section('title', __('public.adm_users_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('admin_extra.breadcrumb_admin', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_users_idx_breadcrumb_section'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
    $gcInit = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="breadcrumb">
    <a href="{{ route('admin.users.index') }}">{{ __('public.adm_users_idx_breadcrumb_users') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_users_idx_breadcrumb_dir') }}</span>
</nav>

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_users_idx_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_users_idx_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="#new_user_name" class="od-btn od-btn--primary"><i data-lucide="user-plus"></i> {{ __('admin_extra.users_create_title') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-stack">
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_users_idx_breadcrumb_users') }} <span class="od-card__count">({{ $users->total() }})</span></h2>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="oda-toolbar">
        <label class="od-field">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_users_idx_ph_search') }}" aria-label="{{ __('public.aria_search_users') }}">
        </label>
        <select name="role_id" class="od-select" aria-label="{{ __('admin_extra.aria_role', [], app()->getLocale()) }}" data-autosubmit>
            <option value="">{{ __('public.adm_users_idx_filter_all_roles') }}</option>
            @foreach($roles as $role)<option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>@endforeach
        </select>
        <select name="status" class="od-select" aria-label="{{ __('public.aria_status') }}" data-autosubmit>
            <option value="">{{ __('public.adm_users_idx_filter_all') }}</option>
            <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>{{ __('public.adm_users_idx_filter_active') }}</option>
            <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>{{ __('public.adm_users_idx_filter_pending') }}</option>
            <option value="suspended" {{ request('status')==='suspended' ? 'selected' : '' }}>{{ __('public.adm_users_idx_filter_suspended') }}</option>
        </select>
        <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('public.adm_users_idx_btn_filter') }}</button>
        <a href="{{ route('admin.users.index') }}" class="od-btn od-btn--ghost">{{ __('public.adm_users_idx_btn_reset') }}</a>
    </form>

    <p class="oda-gc-note"><i data-lucide="users"></i> {{ __('admin_extra.count_users', ['n' => $users->total()], app()->getLocale()) ?: $users->total().' users' }}</p>

    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_users_idx_col_name') }} / {{ __('public.adm_users_idx_col_email') }}</th>
                    <th>{{ __('public.adm_users_idx_col_role') }}</th>
                    <th>{{ __('admin_extra.users_col_facility') }}</th>
                    <th>{{ __('public.adm_users_idx_col_status') }}</th>
                    <th>{{ __('public.adm_users_idx_col_created') }}</th>
                    <th class="od-table__actions">{{ __('public.adm_users_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td data-label="{{ __('public.adm_users_idx_col_name') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($user->email) }}" aria-hidden="true">{{ $gcInit($user->name) }}</span>
                            <div>
                                <a href="{{ route('admin.users.show', $user->id) }}" class="od-table__name">{{ $user->name }}</a>
                                <span class="od-table__sub">{{ $user->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_users_idx_col_role') }}"><span class="od-pill od-pill--neutral">{{ $user->role?->name ?? (__('admin_extra.users_role_none', [], app()->getLocale())) }}</span></td>
                    <td data-label="{{ __('admin_extra.users_col_facility') }}">
                        @if($user->primaryFacility)
                            {{ $user->primaryFacility->name }}
                        @else
                            <span class="od-pill od-pill--pending">{{ __('admin_extra.users_facility_none') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_users_idx_col_status') }}">
                        @if($user->status==='active')<span class="od-pill od-pill--ready od-pill--dot">{{ __('public.adm_users_idx_badge_active') }}</span>
                        @elseif($user->status==='suspended')<span class="od-pill od-pill--urgent od-pill--dot">{{ __('public.adm_users_idx_badge_suspended') }}</span>
                        @else<span class="od-pill od-pill--pending od-pill--dot">@enum($user->status)</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_users_idx_col_created') }}" class="oda-gc-muted">{{ $user->created_at?->translatedFormat('d M Y') }}</td>
                    <td class="od-table__actions" data-label="{{ __('public.adm_users_idx_col_actions') }}">
                        <div class="oda-gc-actions">
                        <a href="{{ route('admin.users.show', $user->id) }}" class="od-btn od-btn--row" aria-label="{{ __('public.aria_view_user') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) }}">{{ __('admin_extra.title_view', [], app()->getLocale()) }}</a>
                        @if($user->status!=='active')
                        <form method="POST" action="{{ route('admin.users.activate',$user->id) }}" class="oda-inline-form">@csrf
                            <button type="submit" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.aria_activate_user') }}" title="{{ __('public.aria_activate') }}"><i data-lucide="check-circle"></i></button>
                        </form>
                        @endif
                        @if($user->status!=='suspended')
                        <form method="POST" action="{{ route('admin.users.suspend',$user->id) }}" data-confirm="{{ __('admin_desk.confirm_suspend_user') }}" class="oda-inline-form">@csrf
                            <button type="submit" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.aria_suspend_user') }}" title="{{ __('public.aria_suspend') }}"><i data-lucide="ban"></i></button>
                        </form>
                        @endif
                        <button type="button" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.aria_delete_user') }}" title="{{ __('public.aria_delete') }}" data-call="opOpenModal" data-args="[&quot;delete-user-{{ $user->id }}&quot;]"><i data-lucide="trash-2"></i></button>
                        </div>
                        <div id="delete-user-{{ $user->id }}" class="modal-backdrop" hidden>
                            <div class="od-card oda-modal" role="dialog" aria-modal="true">
                                <div class="od-card__head"><h3 class="od-card__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_users_idx_modal_delete_title') }}</h3></div>
                                <form method="POST" action="{{ route('admin.users.destroy',$user->id) }}" class="od-card__body">@csrf @method('DELETE')
                                    <p>{{ __('public.adm_users_idx_modal_delete_title') }} <strong>{{ $user->name }}</strong>?</p>
                                    <div class="oda-form__actions">
                                        <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;delete-user-{{ $user->id }}&quot;]">{{ __('public.adm_users_idx_modal_delete_btn_cancel') }}</button>
                                        <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_users_idx_modal_delete_btn_delete') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="users"></i></span>
                        <p class="od-empty__text">{{ __('public.adm_users_idx_empty') }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="od-card__foot">{{ $users->links() }}</div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="user-plus"></i> {{ __('admin_extra.users_create_title') }}</h2>
    </div>
    <div class="od-card__body">
        @include('portals.admin.users._facility_finder', [
            'searchAction' => route('admin.users.index'),
            'carry'        => [
                'search'  => request('search'),
                'role_id' => request('role_id'),
                'status'  => request('status'),
            ],
        ])

        <form method="POST" action="{{ route('admin.users.store') }}" class="oda-form">
            @csrf
            <label class="oda-label" for="new_user_name">
                <span class="oda-label__text">{{ __('admin_extra.users_create_name') }} *</span>
                <input type="text" id="new_user_name" name="name" class="od-input" value="{{ old('name') }}" required>
                @error('name')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label" for="new_user_email">
                <span class="oda-label__text">{{ __('admin_extra.users_create_email') }} *</span>
                <input type="email" id="new_user_email" name="email" class="od-input" value="{{ old('email') }}" required>
                @error('email')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label" for="new_user_password">
                <span class="oda-label__text">{{ __('admin_extra.users_create_pw') }} *</span>
                <input type="password" id="new_user_password" name="password" class="od-input" required>
                @error('password')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label" for="new_user_role">
                <span class="oda-label__text">{{ __('admin_extra.users_create_role') }} *</span>
                <select id="new_user_role" name="role_id" class="od-select" required>
                    <option value="">{{ __('admin_extra.users_create_role_ph') }}</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id')<span class="oda-error">{{ $message }}</span>@enderror
            </label>

            @include('portals.admin.users._facility_picker', ['selected' => null])

            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="user-plus"></i> {{ __('admin_extra.users_create_btn') }}</button>
            </div>
        </form>
    </div>
</section>
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
