@extends('layouts.portal')

@section('title', __('team.page_title') . ' — OpesCare')


@section('head')
@include('portals.pharmacy._supply_styles')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
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

    // Legacy badge classes from the enums, mapped onto od pills.
    $pill = fn (?string $badge): string => match (str_replace('badge-', '', (string) $badge)) {
        'success' => 'ready',
        'danger'  => 'urgent',
        'warning' => 'pending',
        'info', 'primary' => 'normal',
        default   => 'neutral',
    };
    $initials = function (?string $name): string {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $s = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr(count($parts) > 1 ? end($parts) : '', 0, 1));
        return $s !== '' ? $s : '—';
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('team.page_title') }}</h1>
        <p class="od-page__sub">{{ __('team.page_subtitle') }}</p>
    </div>
</header>

@if(session('success'))
<div class="odc-note" role="status"><i data-lucide="check-circle-2"></i><div class="odc-note__text">{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="odc-note odc-note--error" role="alert"><i data-lucide="alert-circle"></i><div class="odc-note__text">{{ session('error') }}</div></div>
@endif

@if(session('mail_status'))
{{-- What actually happened to the email, reported separately from what happened
     to the invitation. The invitation succeeded either way. --}}
<div class="odc-note {{ session('mail_status_ok') ? 'odc-note--info' : 'odc-note--warn' }}" role="status">
    <i data-lucide="{{ session('mail_status_ok') ? 'mail-check' : 'mail-x' }}"></i>
    <div class="odc-note__text">{{ session('mail_status') }}</div>
</div>
@endif

@if(session('invite_link'))
{{-- The link is the channel of record. This host relays direct to MX with no
     relayhost and the sending domain publishes a nullMX, so delivery is
     best-effort and unbounceable; the copyable link is what actually works. --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('team.invite_link_title') }}</h2>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('team.invite_link_help', ['email' => session('invite_link_for')]) }}</p>
        <label class="od-field">
            <i data-lucide="link"></i>
            <input type="text" class="od-input" readonly data-select-on-click
                   value="{{ session('invite_link') }}"
                   aria-label="{{ __('team.invite_link_title') }}">
        </label>
    </div>
</section>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('team.invite_form_title') }}</h2>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('team.invite_form_help') }}</p>

        <form method="POST" action="{{ route('portals.facility.team.invite') }}">
            @csrf
            <div class="odc-form">
                <div>
                    <label class="odc-label" for="email">{{ __('team.field_email') }}</label>
                    <input type="email" id="email" name="email" class="od-input" required
                           maxlength="255" value="{{ old('email') }}">
                    @error('email')<p class="odc-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="odc-label" for="name">{{ __('team.field_name') }}</label>
                    <input type="text" id="name" name="name" class="od-input"
                           maxlength="255" value="{{ old('name') }}">
                    @error('name')<p class="odc-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="odc-label" for="role">{{ __('team.field_role') }}</label>
                    <select id="role" name="role" class="od-select" required>
                        <option value="">{{ __('team.field_role_placeholder') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                {{ $roleLabel($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<p class="odc-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="odc-form-foot">
                <button type="submit" class="od-btn od-btn--primary fs-tap">
                    <i data-lucide="send"></i> {{ __('team.invite_submit') }}
                </button>
            </div>
        </form>
    </div>
</section>

{{-- ── Join requests: clinicians who asked to be added here ─────────────
     The mirror image of the invite form above. The requester's own words are
     shown in "Role asked for" and go nowhere near the grant: the role that is
     applied comes from the select in the Actions column, which is filled from
     the same allow-list the invite form uses. --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('team.join_queue_title') }} <span class="od-card__count">({{ $joinRequests->count() }})</span></h2>
        @if($pendingJoins > 0)
            <span class="od-pill od-pill--pending">{{ __('team.join_queue_badge', ['count' => $pendingJoins]) }}</span>
        @endif
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('team.join_queue_help') }}</p>

        @if($joinRequests->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="user-plus"></i></span>
                <p class="od-empty__text">{{ __('team.join_queue_empty') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('team.join_col_professional') }}</th>
                        <th>{{ __('team.join_col_requested') }}</th>
                        <th>{{ __('team.join_col_message') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                        <th>{{ __('team.join_col_submitted') }}</th>
                        <th>{{ __('team.join_col_decision') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($joinRequests as $i => $req)
                    @php $rn = (string) ($req->user?->name ?? ''); @endphp
                    <tr>
                        <td class="od-table__num">{{ $i + 1 }}</td>
                        <td data-label="{{ __('team.join_col_professional') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone($rn !== '' ? $rn : (string) $req->id) }}" aria-hidden="true">{{ $initials($rn) }}</span>
                                <div>
                                    <span class="od-table__name">{{ $rn !== '' ? $rn : '—' }}</span>
                                    <span class="od-table__sub">{{ $req->user?->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('team.join_col_requested') }}">{{ $req->requested_role ?: '—' }}</td>
                        <td data-label="{{ __('team.join_col_message') }}">
                            {{ $req->message ?: __('team.join_no_message') }}
                        </td>
                        <td data-label="{{ __('team.col_status') }}">
                            <span class="od-pill od-pill--{{ $pill($req->status->badgeClass()) }}">{{ __($req->status->labelKey()) }}</span>
                        </td>
                        <td data-label="{{ __('team.join_col_submitted') }}" class="odc-nowrap">{{ $req->created_at?->isoFormat('LLL') }}</td>
                        <td data-label="{{ __('team.join_col_decision') }}">
                            @if($req->isPending())
                                <div class="odc-stack-sm">
                                <form method="POST" action="{{ route('portals.facility.team.join.approve', $req->id) }}" data-confirm="{{ __('hospital_desk.confirm_team_approve') }}" class="odc-inline">
                                    @csrf
                                    <select name="role" class="od-select" required
                                            aria-label="{{ __('team.join_grant_role_label') }}">
                                        <option value="">{{ __('team.field_role_placeholder') }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">{{ $roleLabel($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="od-btn od-btn--row fs-tap">
                                        {{ __('team.join_action_approve') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('portals.facility.team.join.decline', $req->id) }}" class="odc-inline" data-confirm="{{ __('hospital_desk.confirm_team_decline') }}">
                                    @csrf
                                    <button type="submit" class="od-btn od-btn--secondary od-btn--sm fs-tap">
                                        <i data-lucide="user-x"></i> {{ __('team.join_action_decline') }}
                                    </button>
                                </form>
                                </div>
                            @else
                                @if($req->grantedRole)
                                    <div>{{ __('team.join_col_granted') }}: <strong>{{ $roleLabel($req->grantedRole->name) }}</strong></div>
                                @endif
                                @if($req->decider)
                                    <span class="od-table__sub">{{ __('team.join_decided_by', ['name' => $req->decider->name]) }}</span>
                                @endif
                                @if($req->decided_at)
                                    <span class="od-table__sub">{{ $req->decided_at->isoFormat('LLL') }}</span>
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
</section>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('team.invites_title') }} <span class="od-card__count">({{ $invites->count() }})</span></h2>
    </header>
    <div class="od-card__body">
        @if($invites->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="mail"></i></span>
                <p class="od-empty__text">{{ __('team.invites_empty') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('team.col_email') }}</th>
                        <th>{{ __('team.col_role') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                        <th>{{ __('team.col_expires') }}</th>
                        <th>{{ __('team.col_invited_by') }}</th>
                        <th>{{ __('team.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($invites as $i => $invite)
                    @php
                        $reason = $invite->failureReason();
                        $badge  = match ($reason) {
                            'used'    => 'ready',
                            'revoked' => 'urgent',
                            'expired' => 'neutral',
                            default   => 'pending',
                        };
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $i + 1 }}</td>
                        <td data-label="{{ __('team.col_email') }}"><span class="od-table__name">{{ $invite->email }}</span></td>
                        <td data-label="{{ __('team.col_role') }}">{{ $roleLabel($invite->role?->name) }}</td>
                        <td data-label="{{ __('team.col_status') }}"><span class="od-pill od-pill--{{ $badge }}">{{ __('team.status_' . ($reason ?? 'pending')) }}</span></td>
                        <td data-label="{{ __('team.col_expires') }}" class="odc-nowrap">{{ $invite->expires_at?->isoFormat('LLL') }}</td>
                        <td data-label="{{ __('team.col_invited_by') }}">{{ $invite->inviter?->name ?? '—' }}</td>
                        <td data-label="{{ __('team.col_actions') }}" class="od-table__actions">
                            @unless($invite->isAccepted())
                                <div class="odc-row-actions">
                                <form method="POST" action="{{ route('portals.facility.team.invite.reissue', $invite->id) }}" class="odc-inline" data-confirm="{{ __('hospital_desk.confirm_invite_reissue') }}">
                                    @csrf
                                    <button type="submit" class="od-btn od-btn--row fs-tap">
                                        {{ __('team.action_reissue') }}
                                    </button>
                                </form>
                                @unless($invite->isRevoked())
                                    <form method="POST" action="{{ route('portals.facility.team.invite.revoke', $invite->id) }}" class="odc-inline" data-confirm="{{ __('hospital_desk.confirm_invite_revoke') }}">
                                        @csrf
                                        <button type="submit" class="od-btn od-btn--secondary od-btn--sm fs-tap">
                                            <i data-lucide="ban"></i> {{ __('team.action_revoke') }}
                                        </button>
                                    </form>
                                @endunless
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('team.staff_title') }} <span class="od-card__count">({{ $staff->count() }})</span></h2>
    </header>
    <div class="od-card__body od-card__body--flush">
        @if($staff->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="users"></i></span>
                <p class="od-empty__text">{{ __('team.staff_empty') }}</p>
            </div>
        @else
            <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('team.col_name') }}</th>
                        <th>{{ __('team.col_role') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                        <th>{{ __('facility_admin.col_manage') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($staff as $i => $member)
                    <tr>
                        <td class="od-table__num">{{ $i + 1 }}</td>
                        <td data-label="{{ __('team.col_name') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $tone((string) ($member->name ?: $member->id)) }}" aria-hidden="true">{{ $initials($member->name ?: $member->email) }}</span>
                                <div>
                                    <a href="{{ route('portals.facility.admin.member', $member->id) }}" class="od-table__name">{{ filled($member->name) ? $member->name : \Illuminate\Support\Str::before((string) $member->email, '@') }}</a>
                                    <span class="od-table__sub">{{ $member->email }}</span>
                                </div>
                            </div>
                        </td>
                        {{-- The role held AT THIS FACILITY, which is not always
                             the role on the account: a clinician whose home
                             facility is elsewhere holds it through their active
                             assignment here. --}}
                        <td data-label="{{ __('team.col_role') }}">{{ $roleLabel($facilityRoleFor[$member->id] ?? $member->role?->name) }}</td>
                        <td data-label="{{ __('team.col_status') }}">
                            <span class="od-pill od-pill--{{ $member->status === 'active' ? 'ready' : 'neutral' }}">
                                {{ $statusLabel($member->status) }}
                            </span>
                        </td>
                        {{-- The member page: profile, credentials, change role,
                             end / reopen the posting (routes/facility_admin.php). --}}
                        <td data-label="{{ __('facility_admin.col_manage') }}" class="od-table__actions">
                            <a href="{{ route('portals.facility.admin.member', $member->id) }}" class="od-btn od-btn--row fs-tap">
                                {{ __('facility_admin.action_open') }}
                            </a>
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
