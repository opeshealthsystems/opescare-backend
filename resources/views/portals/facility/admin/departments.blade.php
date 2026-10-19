@extends('layouts.portal')

@section('title', __('facility_admin.departments_title') . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

@php
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('facility_admin.departments_title') }}</h1>
        <p class="od-page__sub">{{ __('facility_admin.departments_subtitle') }}</p>
    </div>
    <div class="odc-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="od-btn od-btn--secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
        <a href="{{ route('portals.facility.team') }}" class="od-btn od-btn--primary">
            <i data-lucide="users"></i> {{ __('facility_admin.action_open_team') }}
        </a>
    </div>
</header>

{{-- Plain statement of what this page is: a grouping of a free-text field,
     not a department register. --}}
<div class="odc-note odc-note--info">
    <i data-lucide="info"></i>
    <div class="odc-note__text">{{ __('facility_admin.departments_free_text') }}</div>
</div>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.departments_title') }} <span class="od-card__count">({{ count($departments) }})</span></h2>
    </header>
    <div class="od-card__body od-card__body--flush">
        @if($departments === [])
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="layout-grid"></i></span>
                <p class="od-empty__text">{{ __('facility_admin.departments_empty') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="departments">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('facility_admin.col_department') }}</th>
                        <th>{{ __('facility_admin.col_department_count') }}</th>
                        <th>{{ __('facility_admin.col_department_roles') }}</th>
                        <th>{{ __('facility_admin.col_department_members') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($departments as $name => $group)
                    <tr data-department="{{ $name === '' ? '__unassigned' : $name }}">
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('facility_admin.col_department') }}">
                            <div class="od-table__person">
                                <span class="od-avatar odc-ico-avatar od-tint--{{ $name === '' ? 'sky' : $tone((string) $name) }}" aria-hidden="true"><i data-lucide="{{ $name === '' ? 'circle-dashed' : 'layout-grid' }}"></i></span>
                                @if($name === '')
                                    <span class="od-table__sub">{{ __('facility_admin.department_unassigned') }}</span>
                                @else
                                    <span class="od-table__name">{{ $name }}</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="{{ __('facility_admin.col_department_count') }}" data-department-count="{{ $group['count'] }}" class="odc-nowrap">
                            {{ __('facility_admin.department_members', ['count' => $group['count']]) }}
                        </td>
                        <td data-label="{{ __('facility_admin.col_department_roles') }}">
                            <div class="odc-row-actions">
                            @foreach($group['roles'] as $role => $n)
                                <span class="od-pill od-pill--neutral">{{ __('portal.' . $role . '_role') !== 'portal.' . $role . '_role' ? __('portal.' . $role . '_role') : $role }} × {{ $n }}</span>
                            @endforeach
                            </div>
                        </td>
                        <td data-label="{{ __('facility_admin.col_department_members') }}">
                            @foreach($group['members'] as $member)
                                <a href="{{ route('portals.facility.admin.member', $member['user_id']) }}" class="od-table__name">{{ $member['name'] }}</a>@if($member['job_title'])<span class="od-table__sub">{{ $member['job_title'] }}</span>@endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

</div>
@endsection
