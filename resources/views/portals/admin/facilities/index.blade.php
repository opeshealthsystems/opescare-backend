@extends('layouts.portal')
@section('title', __('public.adm_fac_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('admin_extra.breadcrumb_admin', [], app()->getLocale()) ?: 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_fac_breadcrumb'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.facilities.index') }}">{{ __('public.adm_fac_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_fac_breadcrumb_dir') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_fac_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('admin.facilities.index') }}#add" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('public.adm_fac_btn_add') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<form method="GET" action="{{ route('admin.facilities.index') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_fac_ph_search') }}" aria-label="{{ __('public.aria_search_facilities') }}">
    </label>
    <select name="type" class="filter-select" aria-label="{{ __('admin_extra.aria_type', [], app()->getLocale()) ?: 'Type' }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_fac_opt_all_types') }}</option>
        @foreach(['hospital','clinic','laboratory','pharmacy','radiology','specialist'] as $t)
        <option value="{{ $t }}" {{ request('type')===$t?'selected':'' }}>{{ ucfirst($t) }}</option>
        @endforeach
    </select>
    <select name="status" class="filter-select" aria-label="{{ __('public.aria_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_fac_opt_all_statuses') }}</option>
        <option value="active" {{ request('status')==='active'?'selected':'' }}>{{ __('public.adm_fac_opt_active') }}</option>
        <option value="pending_approval" {{ request('status')==='pending_approval'?'selected':'' }}>{{ __('public.adm_fac_opt_pending') }}</option>
        <option value="suspended" {{ request('status')==='suspended'?'selected':'' }}>{{ __('public.adm_fac_opt_suspended') }}</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_fac_btn_filter') }}</button>
    <a href="{{ route('admin.facilities.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_fac_btn_reset') }}</a>
</form>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('admin_extra.count_facilities', ['n' => $facilities->total()], app()->getLocale()) ?: $facilities->total().' facilities' }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_fac_col_facility') }}</th>
                    <th>{{ __('public.adm_fac_col_type') }}</th>
                    <th>{{ __('public.adm_fac_col_region') }}</th>
                    <th>{{ __('public.adm_fac_col_status') }}</th>
                    <th class="row-actions">{{ __('public.adm_fac_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($facilities as $f)
                <tr>
                    <td data-label="{{ __('public.adm_fac_col_facility') }}">
                        @php $typeIcon = ['hospital'=>'building-2','laboratory'=>'flask-conical','pharmacy'=>'pill','radiology'=>'scan'][$f->type] ?? 'building'; @endphp
                        <span class="cell-with-icon">
                            <i data-lucide="{{ $typeIcon }}"></i>
                            <span class="td-strong">{{ $f->name }}</span>
                        </span>
                    </td>
                    <td data-label="{{ __('public.adm_fac_col_type') }}">{{ ucfirst($f->type ?? '—') }}</td>
                    <td data-label="{{ __('public.adm_fac_col_region') }}">{{ $f->region ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_fac_col_status') }}">
                        @if($f->status==='active')<span class="badge badge-success">{{ __('public.adm_fac_badge_active') }}</span>
                        @elseif($f->status==='suspended')<span class="badge badge-danger">{{ __('public.adm_fac_badge_suspended') }}</span>
                        @elseif($f->status==='pending_approval')<span class="badge badge-warning">{{ __('public.adm_fac_badge_pending') }}</span>
                        @else<span class="badge badge-neutral">@enum($f->status ?? 'pending')</span>@endif
                    </td>
                    <td class="row-actions" data-label="{{ __('public.adm_fac_col_actions') }}">
                        <a href="{{ route('admin.facilities.show', $f->id) }}" class="icon-btn" aria-label="{{ __('public.aria_view_facility') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) ?: 'View' }}">
                            <i data-lucide="eye"></i>
                        </a>
                        @if(($f->status ?? '') !== 'active')
                        <form method="POST" action="{{ route('admin.facilities.approve', $f->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.aria_approve_facility') }}" title="{{ __('public.aria_approve') }}"><i data-lucide="check-circle"></i></button>
                        </form>
                        @endif
                        @if(($f->status ?? '') !== 'suspended')
                        <form method="POST" action="{{ route('admin.facilities.suspend', $f->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.aria_suspend_facility') }}" title="{{ __('public.aria_suspend') }}"><i data-lucide="pause-circle"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ __('public.adm_fac_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $facilities->links() }}</div>
</div>

@endsection
