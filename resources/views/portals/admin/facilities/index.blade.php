@extends('layouts.portal')
@section('title', __('public.adm_fac_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('admin_extra.breadcrumb_admin', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_fac_breadcrumb'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = fn ($s) => $gcTones[crc32((string) $s) % count($gcTones)];
@endphp
@section('content')
<div class="od-page">

<nav class="oda-gc-crumbs" aria-label="breadcrumb">
    <a href="{{ route('admin.facilities.index') }}">{{ __('public.adm_fac_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_fac_breadcrumb_dir') }}</span>
</nav>

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_fac_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_fac_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('admin.facilities.index') }}#add" class="od-btn od-btn--primary">
            <i data-lucide="plus"></i> {{ __('public.adm_fac_btn_add') }}
        </a>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="building-2"></i> {{ __('admin_extra.count_facilities', ['n' => $facilities->total()], app()->getLocale()) ?: $facilities->total().' facilities' }}</h2>
    </div>

    <form method="GET" action="{{ route('admin.facilities.index') }}" class="oda-toolbar">
        <label class="od-field">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_fac_ph_search') }}" aria-label="{{ __('public.aria_search_facilities') }}">
        </label>
        <select name="type" class="od-select" aria-label="{{ __('admin_extra.aria_type', [], app()->getLocale()) }}" data-autosubmit>
            <option value="">{{ __('public.adm_fac_opt_all_types') }}</option>
            @foreach(['hospital','clinic','laboratory','pharmacy','radiology','specialist'] as $t)
            <option value="{{ $t }}" {{ request('type')===$t?'selected':'' }}>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        <select name="status" class="od-select" aria-label="{{ __('public.aria_status') }}" data-autosubmit>
            <option value="">{{ __('public.adm_fac_opt_all_statuses') }}</option>
            <option value="active" {{ request('status')==='active'?'selected':'' }}>{{ __('public.adm_fac_opt_active') }}</option>
            <option value="pending_approval" {{ request('status')==='pending_approval'?'selected':'' }}>{{ __('public.adm_fac_opt_pending') }}</option>
            <option value="suspended" {{ request('status')==='suspended'?'selected':'' }}>{{ __('public.adm_fac_opt_suspended') }}</option>
        </select>
        <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('public.adm_fac_btn_filter') }}</button>
        <a href="{{ route('admin.facilities.index') }}" class="od-btn od-btn--ghost">{{ __('public.adm_fac_btn_reset') }}</a>
    </form>

    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_fac_col_facility') }}</th>
                    <th>{{ __('public.adm_fac_col_type') }}</th>
                    <th>{{ __('public.adm_fac_col_region') }}</th>
                    <th>{{ __('public.adm_fac_col_status') }}</th>
                    <th class="od-table__actions">{{ __('public.adm_fac_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($facilities as $f)
                <tr>
                    <td data-label="{{ __('public.adm_fac_col_facility') }}">
                        @php $typeIcon = ['hospital'=>'building-2','laboratory'=>'flask-conical','pharmacy'=>'pill','radiology'=>'scan'][$f->type] ?? 'building'; @endphp
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $gcTone($f->name) }}" aria-hidden="true"><i data-lucide="{{ $typeIcon }}"></i></span>
                            <a href="{{ route('admin.facilities.show', $f->id) }}" class="od-table__name">{{ $f->name }}</a>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_fac_col_type') }}">{{ ucfirst($f->type ?? '—') }}</td>
                    <td data-label="{{ __('public.adm_fac_col_region') }}">{{ $f->region ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_fac_col_status') }}">
                        @if($f->status==='active')<span class="od-pill od-pill--ready od-pill--dot">{{ __('public.adm_fac_badge_active') }}</span>
                        @elseif($f->status==='suspended')<span class="od-pill od-pill--urgent od-pill--dot">{{ __('public.adm_fac_badge_suspended') }}</span>
                        @elseif($f->status==='pending_approval')<span class="od-pill od-pill--pending od-pill--dot">{{ __('public.adm_fac_badge_pending') }}</span>
                        @else<span class="od-pill od-pill--neutral od-pill--dot">@enum($f->status ?? 'pending')</span>@endif
                    </td>
                    <td class="od-table__actions" data-label="{{ __('public.adm_fac_col_actions') }}">
                        <div class="oda-gc-actions">
                        <a href="{{ route('admin.facilities.show', $f->id) }}" class="od-btn od-btn--row" aria-label="{{ __('public.aria_view_facility') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) }}">{{ __('admin_extra.title_view', [], app()->getLocale()) }}</a>
                        @if(($f->status ?? '') !== 'active')
                        <form method="POST" action="{{ route('admin.facilities.approve', $f->id) }}" class="oda-inline-form">@csrf
                            <button type="submit" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.aria_approve_facility') }}" title="{{ __('public.aria_approve') }}"><i data-lucide="check-circle"></i></button>
                        </form>
                        @endif
                        @if(($f->status ?? '') !== 'suspended')
                        <form method="POST" action="{{ route('admin.facilities.suspend', $f->id) }}" data-confirm="{{ __('admin_desk.confirm_suspend_facility') }}" class="oda-inline-form">@csrf
                            <button type="submit" class="od-btn od-btn--ghost od-btn--icon" aria-label="{{ __('public.aria_suspend_facility') }}" title="{{ __('public.aria_suspend') }}"><i data-lucide="pause-circle"></i></button>
                        </form>
                        @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">
                    <div class="od-empty">
                        <span class="od-empty__icon"><i data-lucide="building-2"></i></span>
                        <p class="od-empty__text">{{ __('public.adm_fac_empty') }}</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="od-card__foot">{{ $facilities->links() }}</div>
</section>

</div>
@endsection
