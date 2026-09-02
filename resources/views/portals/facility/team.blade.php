@extends('layouts.portal')

@section('title', __('team.page_title') . ' — OpesCare')

@section('breadcrumb_home', __('team.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('team.page_title'))

@section('content')

@php
    /* A staff member may legitimately hold a role outside the invitable
       allow-list (the facility admin themselves, for one), so fall back to the
       role name rather than printing a raw translation key. */
    $orRaw = function (string $key, string $raw): string {
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : $raw;
    };

    $roleLabel = function (?string $name) use ($orRaw): string {
        return $name ? $orRaw('team.roles.' . $name, $name) : '—';
    };

    $statusLabel = function (?string $status) use ($orRaw): string {
        $status = $status ?: 'active';

        return $orRaw('team.user_status_' . $status, $status);
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('team.page_title') }}</h1>
        <p class="page-subtitle">{{ __('team.page_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if(session('invite_link'))
{{-- Production has no SMTP host, so the link is delivered here, to the person
     who issued it, rather than into a mailbox that never receives it. --}}
<div class="panel mb-4" style="border-left:4px solid #0F4C81;">
    <div class="panel-body">
        <h3 class="panel-title" style="margin:0 0 .5rem;">
            <i data-lucide="link"></i> {{ __('team.invite_link_title') }}
        </h3>
        <p class="page-subtitle" style="margin:0 0 .75rem;">
            {{ __('team.invite_link_help', ['email' => session('invite_link_for')]) }}
        </p>
        <input type="text" class="form-control" readonly onclick="this.select()"
               value="{{ session('invite_link') }}"
               aria-label="{{ __('team.invite_link_title') }}">
    </div>
</div>
@endif

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="user-plus"></i> {{ __('team.invite_form_title') }}</h3>
    </div>
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 1rem;">{{ __('team.invite_form_help') }}</p>

        <form method="POST" action="{{ route('portals.facility.team.invite') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                <div>
                    <label class="form-label" for="email">{{ __('team.field_email') }}</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           maxlength="255" value="{{ old('email') }}">
                    @error('email')<p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="name">{{ __('team.field_name') }}</label>
                    <input type="text" id="name" name="name" class="form-control"
                           maxlength="255" value="{{ old('name') }}">
                    @error('name')<p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="role">{{ __('team.field_role') }}</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">{{ __('team.field_role_placeholder') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                {{ $roleLabel($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;">{{ $message }}</p>@enderror
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="send"></i> {{ __('team.invite_submit') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="mail"></i> {{ __('team.invites_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($invites->isEmpty())
            <div class="empty-state">
                <i data-lucide="mail"></i>
                <p>{{ __('team.invites_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('team.col_email') }}</th>
                        <th>{{ __('team.col_role') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                        <th>{{ __('team.col_expires') }}</th>
                        <th>{{ __('team.col_invited_by') }}</th>
                        <th>{{ __('team.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($invites as $invite)
                    @php
                        $reason = $invite->failureReason();
                        $badge  = match ($reason) {
                            'used'    => 'badge-success',
                            'revoked' => 'badge-danger',
                            'expired' => 'badge-neutral',
                            default   => 'badge-warning',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('team.col_email') }}">{{ $invite->email }}</td>
                        <td data-label="{{ __('team.col_role') }}">{{ $roleLabel($invite->role?->name) }}</td>
                        <td data-label="{{ __('team.col_status') }}"><span class="badge {{ $badge }}">{{ __('team.status_' . ($reason ?? 'pending')) }}</span></td>
                        <td data-label="{{ __('team.col_expires') }}">{{ $invite->expires_at?->isoFormat('LLL') }}</td>
                        <td data-label="{{ __('team.col_invited_by') }}">{{ $invite->inviter?->name ?? '—' }}</td>
                        <td data-label="{{ __('team.col_actions') }}" style="white-space:nowrap;">
                            @unless($invite->isAccepted())
                                <form method="POST" action="{{ route('portals.facility.team.invite.reissue', $invite->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i data-lucide="refresh-cw"></i> {{ __('team.action_reissue') }}
                                    </button>
                                </form>
                                @unless($invite->isRevoked())
                                    <form method="POST" action="{{ route('portals.facility.team.invite.revoke', $invite->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i data-lucide="ban"></i> {{ __('team.action_revoke') }}
                                        </button>
                                    </form>
                                @endunless
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> {{ __('team.staff_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($staff->isEmpty())
            <div class="empty-state">
                <i data-lucide="users"></i>
                <p>{{ __('team.staff_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('team.col_name') }}</th>
                        <th>{{ __('team.col_email') }}</th>
                        <th>{{ __('team.col_role') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($staff as $member)
                    <tr>
                        <td data-label="{{ __('team.col_name') }}">{{ $member->name }}</td>
                        <td data-label="{{ __('team.col_email') }}">{{ $member->email }}</td>
                        <td data-label="{{ __('team.col_role') }}">{{ $roleLabel($member->role?->name) }}</td>
                        <td data-label="{{ __('team.col_status') }}">
                            <span class="badge {{ $member->status === 'active' ? 'badge-success' : 'badge-neutral' }}">
                                {{ $statusLabel($member->status) }}
                            </span>
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
