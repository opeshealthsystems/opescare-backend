@extends('layouts.portal')

@section('title', __('team.page_title') . ' — OpesCare')

@section('breadcrumb_home', __('team.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('team.page_title'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

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

@if(session('mail_status'))
{{-- What actually happened to the email, reported separately from what happened
     to the invitation. The invitation succeeded either way. --}}
<div class="alert {{ session('mail_status_ok') ? 'alert-info' : 'alert-warning' }} mb-4" role="status">
    <i data-lucide="{{ session('mail_status_ok') ? 'mail-check' : 'mail-x' }}"></i>
    <div>{{ session('mail_status') }}</div>
</div>
@endif

@if(session('invite_link'))
{{-- The link is the channel of record. This host relays direct to MX with no
     relayhost and the sending domain publishes a nullMX, so delivery is
     best-effort and unbounceable; the copyable link is what actually works. --}}
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
                    @error('email')<p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="name">{{ __('team.field_name') }}</label>
                    <input type="text" id="name" name="name" class="form-control"
                           maxlength="255" value="{{ old('name') }}">
                    @error('name')<p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;">{{ $message }}</p>@enderror
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
                    @error('role')<p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;">{{ $message }}</p>@enderror
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary fs-tap">
                    <i data-lucide="send"></i> {{ __('team.invite_submit') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Join requests: clinicians who asked to be added here ─────────────
     The mirror image of the invite form above. The requester's own words are
     shown in "Role asked for" and go nowhere near the grant: the role that is
     applied comes from the select in the Actions column, which is filled from
     the same allow-list the invite form uses. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="user-plus-2"></i> {{ __('team.join_queue_title') }}</h3>
        @if($pendingJoins > 0)
            <span class="badge badge-warning">{{ __('team.join_queue_badge', ['count' => $pendingJoins]) }}</span>
        @endif
    </div>
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 1rem;">{{ __('team.join_queue_help') }}</p>

        @if($joinRequests->isEmpty())
            <div class="empty-state">
                <i data-lucide="user-plus-2"></i>
                <p>{{ __('team.join_queue_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('team.join_col_professional') }}</th>
                        <th>{{ __('team.join_col_requested') }}</th>
                        <th>{{ __('team.join_col_message') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                        <th>{{ __('team.join_col_submitted') }}</th>
                        <th>{{ __('team.join_col_decision') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($joinRequests as $req)
                    <tr>
                        <td data-label="{{ __('team.join_col_professional') }}">
                            <strong>{{ $req->user?->name ?? '—' }}</strong>
                            <span class="page-subtitle" style="display:block;margin:0;">{{ $req->user?->email }}</span>
                        </td>
                        <td data-label="{{ __('team.join_col_requested') }}">{{ $req->requested_role ?: '—' }}</td>
                        <td data-label="{{ __('team.join_col_message') }}">
                            {{ $req->message ?: __('team.join_no_message') }}
                        </td>
                        <td data-label="{{ __('team.col_status') }}">
                            <span class="badge {{ $req->status->badgeClass() }}">{{ __($req->status->labelKey()) }}</span>
                        </td>
                        <td data-label="{{ __('team.join_col_submitted') }}">{{ $req->created_at?->isoFormat('LLL') }}</td>
                        <td data-label="{{ __('team.join_col_decision') }}">
                            @if($req->isPending())
                                <form method="POST" action="{{ route('portals.facility.team.join.approve', $req->id) }}"
                                      style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin:0 0 .5rem;">
                                    @csrf
                                    <select name="role" class="form-control" required style="min-width:11rem;"
                                            aria-label="{{ __('team.join_grant_role_label') }}">
                                        <option value="">{{ __('team.field_role_placeholder') }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">{{ $roleLabel($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm fs-tap">
                                        <i data-lucide="user-check"></i> {{ __('team.join_action_approve') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('portals.facility.team.join.decline', $req->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm fs-tap">
                                        <i data-lucide="user-x"></i> {{ __('team.join_action_decline') }}
                                    </button>
                                </form>
                            @else
                                @if($req->grantedRole)
                                    <div>{{ __('team.join_col_granted') }}: <strong>{{ $roleLabel($req->grantedRole->name) }}</strong></div>
                                @endif
                                @if($req->decider)
                                    <div class="page-subtitle" style="margin:0;">{{ __('team.join_decided_by', ['name' => $req->decider->name]) }}</div>
                                @endif
                                @if($req->decided_at)
                                    <div class="page-subtitle" style="margin:0;">{{ $req->decided_at->isoFormat('LLL') }}</div>
                                @endif
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
                                    <button type="submit" class="btn btn-secondary btn-sm fs-tap">
                                        <i data-lucide="refresh-cw"></i> {{ __('team.action_reissue') }}
                                    </button>
                                </form>
                                @unless($invite->isRevoked())
                                    <form method="POST" action="{{ route('portals.facility.team.invite.revoke', $invite->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm fs-tap">
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
                        {{-- The role held AT THIS FACILITY, which is not always
                             the role on the account: a clinician whose home
                             facility is elsewhere holds it through their active
                             assignment here. --}}
                        <td data-label="{{ __('team.col_role') }}">{{ $roleLabel($facilityRoleFor[$member->id] ?? $member->role?->name) }}</td>
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
