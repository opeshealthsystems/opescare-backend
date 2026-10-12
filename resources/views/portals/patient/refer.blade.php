@extends('layouts.portal')

@section('title', __('referral.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('referral.title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('referral.title') }}</h1>
            <p class="pf-sub">{{ __('referral.subtitle') }}</p>
        </div>
    </div>

    {{-- Invite link --}}
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'gift', 'size' => 18])
            {{ __('referral.your_link') }}
            <span class="f-badge">{{ __('referral.your_code') }}: {{ $code }}</span>
        </h2>
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <input id="refer-link" type="text" class="f-input" readonly
                   value="{{ $link }}" style="flex:1;min-width:240px;" data-select-on-click>
            <button type="button" class="f-btn f-btn--primary" id="refer-copy-btn" style="min-height:48px;"
                    data-copied="{{ __('referral.copied') }}"
                    data-call="pfCopyReferLink">
                <span>{{ __('referral.copy') }}</span>
            </button>
        </div>
    </div>

    {{-- How it works --}}
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
            {{ __('referral.how_title') }}
        </h2>
        <ul style="list-style:none;display:grid;gap:12px;">
            <li style="display:flex;align-items:flex-start;gap:10px;">
                <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;">@include('partials.finder.icon', ['name' => 'external-link', 'size' => 15])</span>
                <span>{{ __('referral.how_step1') }}</span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:10px;">
                <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;">@include('partials.finder.icon', ['name' => 'users', 'size' => 15])</span>
                <span>{{ __('referral.how_step2') }}</span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:10px;">
                <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;">@include('partials.finder.icon', ['name' => 'gift', 'size' => 15])</span>
                <span>{{ __('referral.how_step3', ['referrer' => $referrerDays, 'referee' => $refereeDays]) }}</span>
            </li>
        </ul>
    </div>

    {{-- Stats --}}
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'users', 'size' => 18])
            {{ __('referral.stats_title') }}
        </h2>
        <div class="pf-tiles" style="grid-template-columns:repeat(3,1fr);">
            <div>
                <div class="pf-tile__value">{{ $joinedCount }}</div>
                <div class="pf-tile__label">{{ __('referral.stat_invites') }}</div>
            </div>
            <div>
                <div class="pf-tile__value">{{ $rewardedCount }}</div>
                <div class="pf-tile__label">{{ __('referral.stat_rewarded') }}</div>
            </div>
            <div>
                <div class="pf-tile__value">{{ $rewardedDays }}</div>
                <div class="pf-tile__label">{{ __('referral.stat_days_earned') }}</div>
            </div>
        </div>
    </div>

    {{-- Invite history --}}
    <div class="f-card">
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'clock', 'size' => 18])
            {{ __('referral.invites_title') }}
        </h2>
        @if($invites->isEmpty())
            <p class="f-muted f-small" style="margin:0;">{{ __('referral.no_invites') }}</p>
        @else
            <div class="pf-table-wrap">
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th>{{ __('referral.col_when') }}</th>
                            <th>{{ __('referral.col_email') }}</th>
                            <th>{{ __('referral.col_status') }}</th>
                            <th>{{ __('referral.col_reward') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invites as $invite)
                            <tr>
                                <td>{{ $invite->created_at?->isoFormat('LL') }}</td>
                                <td>{{ $invite->referee_email ?? '—' }}</td>
                                <td><span class="f-badge {{ $invite->status === 'rewarded' ? 'f-badge--good' : 'pf-badge--muted' }}">{{ __('referral.status_' . $invite->status) }}</span></td>
                                <td>
                                    @if($invite->status === 'rewarded')
                                        {{ __('referral.days', ['count' => $invite->referrer_reward_days]) }}
                                    @else
                                        —
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

<script @cspNonce>
function pfCopyReferLink() {
    var input = document.getElementById('refer-link');
    var btn = document.getElementById('refer-copy-btn');
    if (!input || !btn) return;
    input.select();
    input.setSelectionRange(0, 99999);
    var done = function () {
        var span = btn.querySelector('span');
        if (span) {
            var original = span.textContent;
            span.textContent = btn.getAttribute('data-copied');
            setTimeout(function () { span.textContent = original; }, 1800);
        }
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
    } else {
        document.execCommand('copy');
        done();
    }
}
</script>

@endsection
