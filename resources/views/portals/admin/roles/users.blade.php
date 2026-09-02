@extends('layouts.portal')
@section('title', __('public.adm_roles_title') . ' — ' . $role->name)
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_roles_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_roles_breadcrumb_section'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.roles.index') }}">{{ __('public.adm_roles_breadcrumb_section') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $role->name }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="shield"></i></div>
    <h2 class="entity-head__title">{{ $role->name }}</h2>
    <span class="badge badge-primary">{{ ucfirst(str_replace('_',' ',$role->portal??'—')) }}</span>
    @if($role->is_protected)<span class="badge badge-warning">{{ __('public.adm_roles_badge_protected') }}</span>@endif
    <div class="entity-head__spacer"></div>
</div>

<div class="stat-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_roles_stat_total_users') }}</div>
        <div class="stat-card__value">{{ $users->total() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_roles_stat_portal') }}</div>
        <div class="stat-card__value">{{ ucfirst(str_replace('_',' ',$role->portal??'—')) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_roles_stat_protected') }}</div>
        <div class="stat-card__value">{{ $role->is_protected ? __('public.adm_roles_yes') : __('public.adm_roles_no') }}</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="users"></i> {{ __('public.adm_roles_panel_users_title') }}</h3></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_roles_col_name') }}</th>
                    <th>{{ __('public.adm_roles_col_email') }}</th>
                    <th>{{ __('public.adm_roles_col_facility') }}</th>
                    <th>{{ __('public.adm_roles_col_status') }}</th>
                    <th>{{ __('public.adm_roles_col_joined') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td data-label="{{ __('public.adm_roles_col_name') }}"><span class="td-strong">{{ $user->name ?? '—' }}</span></td>
                    <td data-label="{{ __('public.adm_roles_col_email') }}">{{ $user->email }}</td>
                    <td data-label="{{ __('public.adm_roles_col_facility') }}" class="td-muted">{{ $user->facility?->name ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_roles_col_status') }}">
                        @if($user->is_active ?? true)<span class="badge badge-success">{{ __('public.adm_roles_status_active') }}</span>
                        @else<span class="badge badge-neutral">{{ __('public.adm_roles_status_inactive') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_roles_col_joined') }}" class="td-muted">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ __('public.adm_roles_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $users->links() }}</div>
</div>
@endsection
