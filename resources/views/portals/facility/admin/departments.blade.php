@extends('layouts.portal')

@section('title', __('facility_admin.departments_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.departments_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('facility_admin.departments_title') }}</h1>
        <p class="page-subtitle">{{ __('facility_admin.departments_subtitle') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="btn btn-secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
        <a href="{{ route('portals.facility.team') }}" class="btn btn-secondary">
            <i data-lucide="users"></i> {{ __('facility_admin.action_open_team') }}
        </a>
    </div>
</div>

{{-- Plain statement of what this page is: a grouping of a free-text field,
     not a department register. --}}
<div class="alert alert-info mb-4" style="display:flex;gap:.5rem;align-items:flex-start;">
    <i data-lucide="info"></i>
    <span>{{ __('facility_admin.departments_free_text') }}</span>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="layout-grid"></i> {{ __('facility_admin.departments_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($departments === [])
            <div class="empty-state">
                <i data-lucide="layout-grid"></i>
                <p>{{ __('facility_admin.departments_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="departments">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_department') }}</th>
                        <th>{{ __('facility_admin.col_department_count') }}</th>
                        <th>{{ __('facility_admin.col_department_roles') }}</th>
                        <th>{{ __('facility_admin.col_department_members') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($departments as $name => $group)
                    <tr data-department="{{ $name === '' ? '__unassigned' : $name }}">
                        <td data-label="{{ __('facility_admin.col_department') }}">
                            @if($name === '')
                                <span class="td-muted"><i data-lucide="circle-dashed"></i> {{ __('facility_admin.department_unassigned') }}</span>
                            @else
                                <span class="td-strong">{{ $name }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_department_count') }}" data-department-count="{{ $group['count'] }}">
                            {{ __('facility_admin.department_members', ['count' => $group['count']]) }}
                        </td>
                        <td data-label="{{ __('facility_admin.col_department_roles') }}">
                            @foreach($group['roles'] as $role => $n)
                                <span class="badge badge-neutral">{{ __('portal.' . $role . '_role') !== 'portal.' . $role . '_role' ? __('portal.' . $role . '_role') : $role }} × {{ $n }}</span>
                            @endforeach
                        </td>
                        <td data-label="{{ __('facility_admin.col_department_members') }}">
                            @foreach($group['members'] as $member)
                                <a href="{{ route('portals.facility.admin.member', $member['user_id']) }}">{{ $member['name'] }}</a>@if($member['job_title']) <span class="td-muted">— {{ $member['job_title'] }}</span>@endif{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

@endsection
