@extends('layouts.portal')

@section('title', __('referral.title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('referral.title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('referral.title') }}</h1>
        <p class="page-subtitle">{{ __('referral.subtitle') }}</p>
    </div>
</div>

{{-- Invite link --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="gift"></i> {{ __('referral.your_link') }}</h3>
        <span class="badge badge-info">{{ __('referral.your_code') }}: {{ $code }}</span>
    </div>
    <div class="panel-body">
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
            <input id="refer-link" type="text" class="form-control" readonly
                   value="{{ $link }}" style="flex:1;min-width:240px;" onclick="this.select();">
            <button type="button" class="btn btn-primary" id="refer-copy-btn"
                    data-copied="{{ __('referral.copied') }}"
                    onclick="opesCopyReferLink()">
                <i data-lucide="copy"></i> <span>{{ __('referral.copy') }}</span>
            </button>
        </div>
    </div>
</div>

{{-- How it works --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="sparkles"></i> {{ __('referral.how_title') }}</h3>
    </div>
    <div class="panel-body">
        <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.75rem;">
            <li style="display:flex;align-items:flex-start;gap:.6rem;">
                <i data-lucide="share-2" style="width:1.1rem;height:1.1rem;color:#0F4C81;flex:none;margin-top:.15rem;"></i>
                <span>{{ __('referral.how_step1') }}</span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:.6rem;">
                <i data-lucide="user-plus" style="width:1.1rem;height:1.1rem;color:#0F4C81;flex:none;margin-top:.15rem;"></i>
                <span>{{ __('referral.how_step2') }}</span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:.6rem;">
                <i data-lucide="party-popper" style="width:1.1rem;height:1.1rem;color:#0F4C81;flex:none;margin-top:.15rem;"></i>
                <span>{{ __('referral.how_step3', ['referrer' => $referrerDays, 'referee' => $refereeDays]) }}</span>
            </li>
        </ul>
    </div>
</div>

{{-- Stats --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> {{ __('referral.stats_title') }}</h3>
    </div>
    <div class="panel-body">
        <div style="display:flex;flex-wrap:wrap;gap:2rem;">
            <div>
                <div style="font-size:1.75rem;font-weight:700;color:#0F4C81;">{{ $joinedCount }}</div>
                <div class="page-subtitle">{{ __('referral.stat_invites') }}</div>
            </div>
            <div>
                <div style="font-size:1.75rem;font-weight:700;color:#0F4C81;">{{ $rewardedCount }}</div>
                <div class="page-subtitle">{{ __('referral.stat_rewarded') }}</div>
            </div>
            <div>
                <div style="font-size:1.75rem;font-weight:700;color:#0F4C81;">{{ $rewardedDays }}</div>
                <div class="page-subtitle">{{ __('referral.stat_days_earned') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Invite history --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="history"></i> {{ __('referral.invites_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($invites->isEmpty())
            <p class="page-subtitle" style="margin:0;">{{ __('referral.no_invites') }}</p>
        @else
            <table class="table">
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
                            <td><span class="badge">{{ __('referral.status_' . $invite->status) }}</span></td>
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
        @endif
    </div>
</div>

<script>
function opesCopyReferLink() {
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
