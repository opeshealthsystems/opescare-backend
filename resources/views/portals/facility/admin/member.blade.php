@extends('layouts.portal')

@section('title', __('facility_admin.member_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.member_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

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

    $parts    = preg_split('/\s+/', trim((string) $member->name)) ?: [];
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr(count($parts) > 1 ? end($parts) : '', 0, 1)) ?: '—';
    $tone     = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32((string) ($member->name ?: $member->id)) % 6];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $member->name }}</h1>
        <p class="od-page__sub">{{ __('facility_admin.member_subtitle', ['name' => $member->name]) }}</p>
    </div>
    <div class="odc-actions">
        <a href="{{ route('portals.facility.team') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('facility_admin.back_to_team') }}
        </a>
    </div>
</header>

@if(session('success'))
<div class="odc-note" role="status"><i data-lucide="check-circle-2"></i><div class="odc-note__text">{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="odc-note odc-note--error" role="alert"><i data-lucide="alert-circle"></i><div class="odc-note__text">{{ session('error') }}</div></div>
@endif

<div class="od-grid od-grid--split">

<div class="od-stack">
{{-- Credentials: what the professional recorded about themselves, with the
     verification and expiry state. Read-only here — the holder edits. --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_credentials') }} <span class="od-card__count">({{ $credentials->count() }})</span></h2>
    </header>
    <div class="od-card__body od-card__body--flush">
        @if($credentials->isEmpty())
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="award"></i></span><p class="od-empty__text">{{ __('facility_admin.credentials_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('facility_admin.col_credential') }}</th>
                    <th>{{ __('facility_admin.col_issuer') }}</th>
                    <th>{{ __('facility_admin.col_expiry') }}</th>
                    <th>{{ __('facility_admin.col_verified') }}</th>
                </tr></thead>
                <tbody>
                @foreach($credentials as $c)
                    @php $expired = $c->expiry_date && $c->expiry_date->isPast(); @endphp
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('facility_admin.col_credential') }}">
                            <span class="od-table__name">{{ $c->credential_type }}</span>
                            <span class="od-table__sub">{{ $c->credential_number }}</span>
                        </td>
                        <td data-label="{{ __('facility_admin.col_issuer') }}">{{ $c->issuing_body ?: '—' }}</td>
                        <td data-label="{{ __('facility_admin.col_expiry') }}" class="odc-nowrap">
                            {{ $c->expiry_date?->isoFormat('LL') ?? '—' }}
                            @if($expired)<span class="od-pill od-pill--urgent">{{ __('facility_admin.credential_expired') }}</span>@endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_verified') }}">
                            @if($c->verified_at)
                                <span class="od-pill od-pill--ready">{{ __('facility_admin.credential_verified') }}</span>
                            @else
                                <span class="od-pill od-pill--pending">{{ __('facility_admin.credential_unverified') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

{{-- Staff record (HR) --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_profile') }}</h2>
    </header>
    <div class="od-card__body">
        @if(! $profile)
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="id-card"></i></span><p class="od-empty__text">{{ __('facility_admin.profile_empty') }}</p></div>
        @else
            <dl class="odc-dl">
                <dt>{{ __('facility_admin.lbl_job_title') }}</dt><dd>{{ $profile->job_title ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_department') }}</dt><dd>{{ $profile->department ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_employee_number') }}</dt><dd>{{ $profile->employee_number ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_employment_type') }}</dt><dd>{{ $profile->employment_type ?: '—' }}</dd>
                <dt>{{ __('facility_admin.lbl_phone') }}</dt><dd>{{ $profile->phone ?: '—' }}</dd>
            </dl>
        @endif
    </div>
</section>

{{-- Every posting, every facility: a multi-facility clinician is shown
     whole, so "deactivate here" is read against where else they work. --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_postings') }} <span class="od-card__count">({{ $postings->count() }})</span></h2>
    </header>
    <div class="od-card__body od-card__body--flush">
        @if($postings->isEmpty())
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="building-2"></i></span><p class="od-empty__text">{{ __('facility_admin.postings_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('facility_admin.col_facility') }}</th>
                    <th>{{ __('facility_admin.col_role') }}</th>
                    <th>{{ __('facility_admin.col_status') }}</th>
                    <th>{{ __('facility_admin.col_assigned') }}</th>
                </tr></thead>
                <tbody>
                @foreach($postings as $p)
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('facility_admin.col_facility') }}">
                            <span class="od-table__name">{{ $p->facility?->name ?? '—' }}</span>
                            @if($p->facility_id === $facilityId)<span class="od-table__sub">{{ __('facility_admin.home_facility_here') }}</span>@endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_role') }}">{{ $roleLabel($p->role?->name) }}</td>
                        <td data-label="{{ __('facility_admin.col_status') }}">
                            @if($p->is_active && ($p->expires_at === null || $p->expires_at->isFuture()))
                                <span class="od-pill od-pill--ready">{{ __('facility_admin.posting_active') }}</span>
                            @else
                                <span class="od-pill od-pill--neutral">{{ __('facility_admin.posting_ended') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('facility_admin.col_assigned') }}" class="odc-nowrap">{{ $p->assigned_at?->isoFormat('LL') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>
</div>

{{-- Right-hand detail panel: who, the posting here, and the two writes. --}}
<aside class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_identity') }}</h2>
        <span class="od-pill od-pill--{{ $member->status === 'active' ? 'ready' : 'neutral' }}">{{ $statusLabel($member->status) }}</span>
    </header>
    <div class="od-card__body od-detail">
        <div class="od-detail__head">
            <span class="od-avatar od-avatar--lg od-tint--{{ $tone }}" aria-hidden="true">{{ $initials }}</span>
            <div class="od-detail__main">
                <h3 class="od-detail__name">{{ $member->name }}</h3>
                <div class="od-detail__ref">
                    <span>{{ $roleLabel($roleHere) }}</span>
                    @if($assignment?->assigned_at)<span>{{ __('facility_admin.lbl_since') }} {{ $assignment->assigned_at->isoFormat('LL') }}</span>@endif
                </div>
                <div class="od-detail__facts">
                    <span class="od-detail__fact"><i data-lucide="mail"></i> {{ $member->email }}</span>
                    <span class="od-detail__fact"><i data-lucide="building-2"></i> {{ $isHomeHere ? __('facility_admin.home_facility_here') : __('facility_admin.home_facility_other') }}</span>
                </div>
            </div>
        </div>

        <dl class="odc-dl">
            <dt>{{ __('facility_admin.lbl_name') }}</dt><dd>{{ $member->name }}</dd>
            <dt>{{ __('facility_admin.lbl_account_status') }}</dt><dd>{{ $statusLabel($member->status) }}</dd>
            <dt>{{ __('facility_admin.lbl_home_facility') }}</dt><dd>{{ $isHomeHere ? __('facility_admin.home_facility_here') : __('facility_admin.home_facility_other') }}</dd>
            <dt>{{ __('facility_admin.lbl_role_here') }}</dt><dd><strong>{{ $roleLabel($roleHere) }}</strong></dd>
            <dt>{{ __('facility_admin.lbl_posting_status') }}</dt>
            <dd>
                @if($assignment)
                    <span class="od-pill od-pill--ready">{{ __('facility_admin.posting_active') }}</span>
                @else
                    <span class="od-pill od-pill--neutral">{{ __('facility_admin.posting_ended') }}</span>
                @endif
            </dd>
        </dl>
        @if(! $assignment && $isHomeHere && $postings->where('facility_id', $facilityId)->isEmpty())
            <p class="odc-muted odc-muted--tight">{{ __('facility_admin.posting_none') }}</p>
        @endif

        {{-- Actions: the two writes this desk owns. Self and non-invitable roles are
             shown the reason rather than a disabled button. --}}
        <h3 class="od-card__title">{{ __('facility_admin.section_actions') }}</h3>
        @if($isSelf)
            <div class="odc-note odc-note--info"><i data-lucide="info"></i><div class="odc-note__text">{{ __('facility_admin.not_manageable_self') }}</div></div>
        @elseif(! $manageable)
            <div class="odc-note odc-note--info"><i data-lucide="shield"></i><div class="odc-note__text">{{ __('facility_admin.not_manageable_role') }}</div></div>
        @else
            @if($assignment)
                <form method="POST" action="{{ route('portals.facility.admin.member.role', $member->id) }}" data-confirm="{{ __('hospital_desk.confirm_member_role') }}">
                    @csrf
                    <label class="odc-label" for="role">{{ __('facility_admin.change_role_title') }}</label>
                    <p class="odc-muted">{{ __('facility_admin.change_role_help') }}</p>
                    <select id="role" name="role" class="od-select" required aria-label="{{ __('team.field_role') }}">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected($role->name === $roleHere)>{{ $roleLabel($role->name) }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="odc-error">{{ $message }}</p>@enderror
                    <div class="odc-form-foot">
                        <button type="submit" class="od-btn od-btn--primary fs-tap">
                            <i data-lucide="user-cog"></i> {{ __('facility_admin.change_role_submit') }}
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('portals.facility.admin.member.deactivate', $member->id) }}" data-confirm="{{ __('hospital_desk.confirm_member_deactivate') }}">
                    @csrf
                    <label class="odc-label">{{ __('facility_admin.deactivate_title') }}</label>
                    <p class="odc-muted">{{ __('facility_admin.deactivate_help') }}</p>
                    <div class="odc-form-foot">
                        <button type="submit" class="od-btn od-btn--danger fs-tap">
                            <i data-lucide="user-x"></i> {{ __('facility_admin.deactivate_submit') }}
                        </button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('portals.facility.admin.member.reactivate', $member->id) }}" data-confirm="{{ __('hospital_desk.confirm_member_reactivate') }}">
                    @csrf
                    <label class="odc-label">{{ __('facility_admin.reactivate_title') }}</label>
                    <p class="odc-muted">{{ __('facility_admin.reactivate_help') }}</p>
                    <div class="odc-form-foot">
                        <button type="submit" class="od-btn od-btn--primary fs-tap">
                            <i data-lucide="user-check"></i> {{ __('facility_admin.reactivate_submit') }}
                        </button>
                    </div>
                </form>
            @endif
        @endif
    </div>
</aside>

</div>
</div>
@endsection
