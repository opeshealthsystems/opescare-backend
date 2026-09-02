@extends('layouts.portal')
@section('title', __('public.adm_staff_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_staff_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_staff_breadcrumb_section'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.staff.index') }}">{{ __('public.adm_staff_breadcrumb_section') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_staff_breadcrumb_directory') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_staff_title') }}</h2>
    <div class="page-head__spacer"></div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<form method="GET" action="{{ route('admin.staff.index') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_staff_search_placeholder') }}" aria-label="{{ __('public.adm_staff_search_placeholder') }}">
    </label>
    <select name="status" class="filter-select" aria-label="{{ __('public.adm_staff_filter_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_staff_filter_all') }}</option>
        <option value="active" {{ request('status')==='active'?'selected':'' }}>{{ __('public.adm_staff_status_active') }}</option>
        <option value="suspended" {{ request('status')==='suspended'?'selected':'' }}>{{ __('public.adm_staff_status_suspended') }}</option>
        <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>{{ __('public.adm_staff_status_inactive') }}</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_staff_btn_filter') }}</button>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_staff_btn_reset') }}</a>
</form>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stethoscope"></i> {{ $staff->total() }} {{ __('public.adm_staff_members_suffix') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_staff_col_number') }}</th>
                    <th>{{ __('public.adm_staff_col_name') }}</th>
                    <th>{{ __('public.adm_staff_col_specialty') }}</th>
                    <th>{{ __('public.adm_staff_col_facility') }}</th>
                    <th>{{ __('public.adm_staff_col_status') }}</th>
                    <th class="row-actions">{{ __('public.adm_staff_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($staff as $s)
                <tr>
                    <td data-label="{{ __('public.adm_staff_col_number') }}"><span class="td-mono">{{ $s->staff_number??'—' }}</span></td>
                    <td data-label="{{ __('public.adm_staff_col_name') }}"><span class="td-strong">{{ ($s->first_name??'').' '.($s->last_name??'') }}</span></td>
                    <td data-label="{{ __('public.adm_staff_col_specialty') }}" class="td-muted">{{ $s->specialty??$s->designation??'—' }}</td>
                    <td data-label="{{ __('public.adm_staff_col_facility') }}" class="td-muted">{{ $s->facility?->name??'—' }}</td>
                    <td data-label="{{ __('public.adm_staff_col_status') }}">
                        @if(($s->status??'')==='active')<span class="badge badge-success">{{ __('public.adm_staff_status_active') }}</span>
                        @elseif(($s->status??'')==='suspended')<span class="badge badge-danger">{{ __('public.adm_staff_status_suspended') }}</span>
                        @else<span class="badge badge-warning">@enum($s->status ?? 'inactive')</span>@endif
                    </td>
                    <td class="row-actions" data-label="{{ __('public.adm_staff_col_actions') }}">
                        <a href="{{ route('admin.staff.show', $s->id) }}" class="icon-btn" aria-label="{{ __('public.aria_view_staff') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) ?: 'View' }}"><i data-lucide="eye"></i></a>
                        @if(($s->status??'')!=='active')
                        <form method="POST" action="{{ route('admin.staff.activate',$s->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.adm_staff_btn_activate') }}" title="{{ __('public.adm_staff_btn_activate') }}"><i data-lucide="check-circle"></i></button>
                        </form>
                        @endif
                        @if(($s->status??'')!=='suspended')
                        <form method="POST" action="{{ route('admin.staff.suspend',$s->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.adm_staff_btn_suspend') }}" title="{{ __('public.adm_staff_btn_suspend') }}"><i data-lucide="ban"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_staff_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $staff->links() }}</div>
</div>

@endsection
