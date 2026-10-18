@extends('layouts.portal')
@section('title', __('public.adm_roles_title') . ' — ' . $role->name)
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_roles_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_roles_breadcrumb_section'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
    $gcInit = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
@endphp
@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="breadcrumb">
    <a href="{{ route('portals.admin.roles.index') }}">{{ __('public.adm_roles_breadcrumb_section') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $role->name }}</span>
</nav>

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $role->name }}</h1>
        <p class="od-page__sub od-row">
            <span class="od-pill od-pill--normal">{{ ucfirst(str_replace('_',' ',$role->portal??'—')) }}</span>
            @if($role->is_protected)<span class="od-pill od-pill--pending">{{ __('public.adm_roles_badge_protected') }}</span>@endif
        </p>
    </div>
</div>

<div class="od-kpis oda-gc-kpis--3">
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--blue"><i data-lucide="users"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_roles_stat_total_users') }}</span>
            <span class="od-kpi__value">{{ $users->total() }}</span>
        </div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--teal"><i data-lucide="layout-grid"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_roles_stat_portal') }}</span>
            <span class="od-kpi__value">{{ ucfirst(str_replace('_',' ',$role->portal??'—')) }}</span>
        </div>
    </div>
    <div class="od-kpi">
        <span class="od-kpi__icon od-tint--amber"><i data-lucide="lock"></i></span>
        <div class="od-kpi__body">
            <span class="od-kpi__label">{{ __('public.adm_roles_stat_protected') }}</span>
            <span class="od-kpi__value">{{ $role->is_protected ? __('public.adm_roles_yes') : __('public.adm_roles_no') }}</span>
        </div>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="users"></i> {{ __('public.adm_roles_panel_users_title') }} <span class="od-card__count">({{ $users->total() }})</span></h2></div>
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_roles_col_name') }} / {{ __('public.adm_roles_col_email') }}</th>
                    <th>{{ __('public.adm_roles_col_facility') }}</th>
                    <th>{{ __('public.adm_roles_col_status') }}</th>
                    <th>{{ __('public.adm_roles_col_joined') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td data-label="{{ __('public.adm_roles_col_name') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($user->email) }}" aria-hidden="true">{{ $gcInit($user->name) }}</span>
                            <div>
                                <span class="od-table__name">{{ $user->name ?? '—' }}</span>
                                <span class="od-table__sub">{{ $user->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_roles_col_facility') }}" class="oda-gc-muted">{{ $user->facility?->name ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_roles_col_status') }}">
                        @if($user->is_active ?? true)<span class="od-pill od-pill--ready od-pill--dot">{{ __('public.adm_roles_status_active') }}</span>
                        @else<span class="od-pill od-pill--neutral od-pill--dot">{{ __('public.adm_roles_status_inactive') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_roles_col_joined') }}" class="oda-gc-muted">{{ $user->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4">
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="users"></i></span>
                        <p class="od-empty__text">{{ __('public.adm_roles_empty') }}</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="od-card__foot">{{ $users->links() }}</div>
</section>

</div>
@endsection
