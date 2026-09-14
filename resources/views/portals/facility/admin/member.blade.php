@extends('layouts.portal')

@section('title', __('facility_admin.member_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.member_title'))

@section('content')

@php
    $orRaw = function (string $key, string $raw): string {
        $label = __($key);
        return is_string($label) && $label !== $key ? $label : $raw;
    };
    $roleLabel   = fn (?string $name): string => $name ? $orRaw('team.roles.' . $name, $name) : '—';
    $statusLabel = fn (?string $status): string => $orRaw('team.user_status_' . ($status ?: 'active'), $status ?: 'active');

    $isSelf     = auth()->id() === $member->id;
    $isHomeHere = $member->primary_facility_id === $facilityId;
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $member->name }}</h1>
        <p class="page-subtitle">{{ __('facility_admin.member_subtitle', ['name' => $member->name]) }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.facility.team') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('facility_admin.back_to_team') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;" class="mb-4">
    {{-- Account --}}
    <div class="panel" style="margin:0;">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="user"></i> {{ __('facility_admin.section_identity') }}</h3>
        </div>
        <div class="panel-body">
            <dl class="dl-grid">
                <dt>{{ __('facility_admin.lbl_name') }}</dt><dd>{{ $member->name }}</dd>
                <dt>{{ __('facility_admin.lbl_email') }}</dt><dd>{{ $member->email }}</dd>
                <dt>{{ __('facility_admin.lbl_account_status') }}</dt>
                <dd><span class="badge {{ $member->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ $statusLabel($member->status) }}</span></dd>
                <dt>{{ __('facility_admin.lbl_home_facility') }}</dt>
                <dd>{{ $isHomeHere ? __('facility_admin.home_facility_here') : __('facility_admin.home_facility_other') }}</dd>
            </dl>
        </div>
    </div>

    {{-- Posting here --}}
    <div class="panel" style="margin:0;">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="badge-check"></i> {{ __('facility_admin.section_posting') }}</h3>
        </div>
        <div class="panel-body">
            <dl class="dl-grid">
                <dt>{{ __('facility_admin.lbl_role_here') }}</dt><dd><strong>{{ $roleLabel($roleHere) }}</strong></dd>
                <dt>{{ __('facility_admin.lbl_posting_status') }}</dt>
                <dd>
                    @if($assignment)
                        <span class="badge badge-success">{{ __('facility_admin.posting_active') }}</span>
                    @else
                        <span class="badge badge-neutral">{{ __('facility_admin.posting_ended') }}</span>
                    @endif
                </dd>
                @if($assignment?->assigned_at)
                    <dt>{{ __('facility_admin.lbl_since') }}</dt><dd>{{ $assignment->assigned_at->isoFormat('LL') }}</dd>
                @endif
            </dl>
            @if(! $assignment && $isHomeHere && $postings->where('facility_id', $facilityId)->isEmpty())
                <p class="page-subtitle" style="margin:.5rem 0 0;">{{ __('facility_admin.posting_none') }}</p>
            @endif
        </div>
    </div>
</div>

{{-- Actions: the two writes this desk owns. Self and non-invitable roles are
     shown the reason rather than a disabled button. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="settings-2"></i> {{ __('facility_admin.section_actions') }}</h3>
    </div>
    <div class="panel-body">
        @if($isSelf)
            <div class="alert alert-info" style="margin:0;"><i data-lucide="info"></i><div>{{ __('facility_admin.not_manageable_self') }}</div></div>
        @elseif(! $manageable)
            <div class="alert alert-info" style="margin:0;"><i data-lucide="shield"></i><div>{{ __('facility_admin.not_manageable_role') }}</div></div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;">
                @if($assignment)
                    <form method="POST" action="{{ route('portals.facility.admin.member.role', $member->id) }}">
                        @csrf
                        <h4 style="margin:0 0 .35rem;">{{ __('facility_admin.change_role_title') }}</h4>
                        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.change_role_help') }}</p>
                        <label class="form-label" for="role">{{ __('team.field_role') }}</label>
                        <select id="role" name="role" class="form-control" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected($role->name === $roleHere)>{{ $roleLabel($role->name) }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-primary fs-tap" style="margin-top:.75rem;">
                            <i data-lucide="user-cog"></i> {{ __('facility_admin.change_role_submit') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('portals.facility.admin.member.deactivate', $member->id) }}">
                        @csrf
                        <h4 style="margin:0 0 .35rem;">{{ __('facility_admin.deactivate_title') }}</h4>
                        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.deactivate_help') }}</p>
                        <button type="submit" class="btn btn-danger fs-tap">
                            <i data-lucide="user-x"></i> {{ __('facility_admin.deactivate_submit') }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('portals.facility.admin.member.reactivate', $member->id) }}">
                        @csrf
                        <h4 style="margin:0 0 .35rem;">{{ __('facility_admin.reactivate_title') }}</h4>
                        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.reactivate_help') }}</p>
                        <button type="submit" class="btn btn-primary fs-tap">
                            <i data-lucide="user-check"></i> {{ __('facility_admin.reactivate_submit') }}
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Credentials: what the professional recorded about themselves, with the
     verification and expiry state. Read-only here — the holder edits. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="award"></i> {{ __('facility_admin.section_credentials') }}</h3>
    </div>
    <div class="panel-body">
        @if($credentials->isEmpty())
            <div class="empty-state"><i data-lucide="award"></i><p>{{ __('facility_admin.credentials_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('facility_admin.col_credential') }}</th>
                    <th>{{ __('facility_admin.col_number') }}</th>
                    <th>{{ __('facility_admin.col_issuer') }}</th>
                    <th>{{ __('facility_admin.col_expiry') }}</th>
                    <th>{{ __('facility_admin.col_verified') }}</th>
                </tr></thead>
                <tbody>
                @foreach($credentials as $c)
                    @php $expired = $c->expiry_date && $c->expiry_date->isPast(); @endphp
                    <tr>
                        <td data-label="{{ __('facility_admin.col_credential') }}">{{ $c->credential_type }}</td>
                        <td data-label="{{ __('facility_admin.col_number') }}"><span class="td-mono">{{ $c->credential_number }}</span></td>
                        <td data-label="{{ __('facility_admin.col_issuer') }}">{{ $c->issuing_body ?: '—' }}</td>
                        <td data-label="{{ __('facility_admin.col_expiry') }}">
                            {{ $c->expiry_date?->isoFormat('LL') ?? '—' }}
                            @if($expired)<span class="badge badge-danger">{{ __('facility_admin.credential_expired') }}</span>@endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_verified') }}">
                            @if($c->verified_at)
                                <span class="badge badge-success">{{ __('facility_admin.credential_verified') }}</span>
                            @else
                                <span class="badge badge-warning">{{ __('facility_admin.credential_unverified') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- Staff record (HR) --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="id-card"></i> {{ __('facility_admin.section_profile') }}</h3>
    </div>
    <div class="panel-body">
        @if(! $profile)
            <div class="empty-state"><i data-lucide="id-card"></i><p>{{ __('facility_admin.profile_empty') }}</p></div>
        @else
            <dl class="dl-grid">
                <dt>{{ __('facility_admin.lbl_job_title') }}</dt><dd>{{ $profile->job_title ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_department') }}</dt><dd>{{ $profile->department ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_employee_number') }}</dt><dd>{{ $profile->employee_number ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_employment_type') }}</dt><dd>{{ $profile->employment_type ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_phone') }}</dt><dd>{{ $profile->phone ?: '—' }}</dd>
            </dl>
        @endif
    </div>
</div>

{{-- Every posting, every facility: a multi-facility clinician is shown
     whole, so "deactivate here" is read against where else they work. --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('facility_admin.section_postings') }}</h3>
    </div>
    <div class="panel-body">
        @if($postings->isEmpty())
            <div class="empty-state"><i data-lucide="building-2"></i><p>{{ __('facility_admin.postings_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('facility_admin.col_facility') }}</th>
                    <th>{{ __('facility_admin.col_role') }}</th>
                    <th>{{ __('facility_admin.col_status') }}</th>
                    <th>{{ __('facility_admin.col_assigned') }}</th>
                </tr></thead>
                <tbody>
                @foreach($postings as $p)
                    <tr>
                        <td data-label="{{ __('facility_admin.col_facility') }}">
                            {{ $p->facility?->name ?? '—' }}
                            @if($p->facility_id === $facilityId)<span class="badge badge-neutral">{{ __('facility_admin.home_facility_here') }}</span>@endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_role') }}">{{ $roleLabel($p->role?->name) }}</td>
                        <td data-label="{{ __('facility_admin.col_status') }}">
                            @if($p->is_active && ($p->expires_at === null || $p->expires_at->isFuture()))
                                <span class="badge badge-success">{{ __('facility_admin.posting_active') }}</span>
                            @else
                                <span class="badge badge-neutral">{{ __('facility_admin.posting_ended') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_assigned') }}">{{ $p->assigned_at?->isoFormat('LL') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

<style>
    .dl-grid { display:grid; grid-template-columns:max-content 1fr; gap:.35rem 1rem; margin:0; }
    .dl-grid dt { color:var(--p-text-muted); font-size:.85rem; }
    .dl-grid dd { margin:0; }
</style>

@endsection
