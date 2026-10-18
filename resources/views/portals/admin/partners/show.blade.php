@extends('layouts.portal')
@section('title', __('partners.detail_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('partners.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('partners.detail_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@php
    $gcTones = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gcTone = $gcTones[crc32((string) $client->client_id) % count($gcTones)];
@endphp

@section('content')
<div class="od-page">

<div class="od-card od-detail oda-gc-detail">
    <div class="od-detail__head">
        <span class="od-avatar od-avatar--lg od-tint--{{ $gcTone }}" aria-hidden="true"><i data-lucide="key-round"></i></span>
        <div class="od-detail__main">
            <h1 class="od-detail__name">{{ $client->name ?: $client->client_id }}</h1>
            <div class="od-row">
                <span class="od-pill od-pill--dot {{ $client->status === 'active' ? 'od-pill--ready' : 'od-pill--neutral' }}">{{ __('partners.status_' . $client->status) }}</span>
                <code class="oda-mono">{{ $client->client_id }}</code>
            </div>
        </div>
        <div class="od-row">
            <a href="{{ route('portals.admin.partners.index') }}" class="od-btn od-btn--ghost">
                <i data-lucide="arrow-left"></i> {{ __('partners.back_to_list') }}
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check"></i><div>{{ session('success') }}</div></div>
@endif

{{-- THE ONE SHOWING. This block renders only on the request that created or
     rotated the secret, from a one-request flash. There is no route that can
     fetch it again, because after this response nothing on the platform holds
     the plaintext — only an Argon2id hash of it. --}}
@if($newSecret)
<div class="oda-gc-flash oda-gc-flash--warn">
    <i data-lucide="key-round"></i>
    <div>
        <strong>{{ __('partners.secret_once_title') }}</strong>
        <p>{{ __('partners.secret_once_body') }}</p>
        <p><span class="oda-gc-muted">{{ __('partners.secret_client_id') }}:</span> <code class="oda-mono">{{ $client->client_id }}</code></p>
        <p><span class="oda-gc-muted">{{ __('partners.secret_value') }}:</span> <code class="oda-mono oda-gc-break">{{ $newSecret }}</code></p>
    </div>
</div>
@endif

<div class="od-grid od-grid--main">
<div class="od-stack">
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('partners.section_scopes') }}</h2></div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.admin.partners.scopes', $client->id) }}" class="od-stack">
            @csrf
            <div class="oda-gc-checks">
                @foreach($scopeLabels as $key => $label)
                <label class="oda-check">
                    <input type="checkbox" name="scopes[]" value="{{ $key }}"
                           @checked(in_array($key, (array) ($client->scopes ?? []), true))>
                    <span><code class="oda-mono">{{ $key }}</code> — {{ $label }}</span>
                </label>
                @endforeach
            </div>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--primary">
                    <i data-lucide="save"></i> {{ __('partners.act_scopes_save') }}
                </button>
            </div>
        </form>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('partners.section_trail') }}</h2></div>
    @if($trail->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="history"></i></span>
            <p class="od-empty__text">{{ __('partners.trail_none') }}</p>
        </div>
    @else
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('partners.trail_col_when') }}</th>
                    <th>{{ __('partners.trail_col_action') }}</th>
                    <th>{{ __('partners.trail_col_actor') }}</th>
                    <th>{{ __('partners.trail_col_reason') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($trail as $event)
                <tr>
                    <td class="oda-gc-muted">{{ optional($event->created_at)->toDayDateTimeString() }}</td>
                    <td><code class="oda-mono">{{ $event->action_type }}</code></td>
                    <td>{{ $event->actor_role }}</td>
                    <td>{{ $event->reason }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>
</div>

<div class="od-stack">
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('partners.section_identity') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('partners.col_client_id') }}</dt><dd><code class="oda-mono">{{ $client->client_id }}</code></dd>
            <dt>{{ __('partners.col_organisation') }}</dt><dd>{{ $facility?->name ?? __('partners.no_organisation') }}</dd>
            <dt>{{ __('partners.col_environment') }}</dt><dd>{{ __('partners.env_' . ($client->environment === 'production' ? 'production' : 'sandbox')) }}</dd>
            <dt>{{ __('partners.col_status') }}</dt>
            <dd><span class="od-pill {{ $client->status === 'active' ? 'od-pill--ready' : 'od-pill--neutral' }}">{{ __('partners.status_' . $client->status) }}</span></dd>
            <dt>{{ __('partners.contact') }}</dt><dd>{{ $client->contact_email }}</dd>
            <dt>{{ __('partners.created_at') }}</dt><dd>{{ optional($client->created_at)->toDayDateTimeString() }}</dd>
            <dt>{{ __('partners.col_last_used') }}</dt>
            <dd>{{ $client->last_used_at ? $client->last_used_at->toDayDateTimeString() : __('partners.never_used') }}</dd>
            <dt>{{ __('partners.request_count') }}</dt><dd>{{ number_format($client->request_count ?? 0) }}</dd>
        </dl>
    </div>
</section>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('partners.section_creds') }}</h2></div>
    <div class="od-card__body od-stack">
        <p class="oda-help">{{ __('partners.secret_not_stored') }}</p>

        <form method="POST" action="{{ route('portals.admin.partners.rotate', $client->id) }}" class="od-stack">
            @csrf
            <p class="oda-gc-note">{{ __('partners.act_rotate_help') }}</p>
            <div>
                <button type="submit" class="od-btn od-btn--secondary">
                    <i data-lucide="refresh-cw"></i> {{ __('partners.act_rotate') }}
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('portals.admin.partners.status', $client->id) }}" class="od-stack">
            @csrf
            @if($client->status === 'active')
                <p class="oda-gc-note">{{ __('partners.act_suspend_help') }}</p>
                <p class="oda-gc-note">{{ __('partners.act_revoke_help') }}</p>
                <label class="oda-label" for="reason">
                    <span class="oda-label__text">{{ __('partners.act_reason') }}</span>
                    <input type="text" id="reason" name="reason" class="od-input" maxlength="500" required>
                </label>
                <div class="od-row">
                    <button type="submit" name="status" value="suspended" class="od-btn od-btn--secondary">
                        <i data-lucide="pause"></i> {{ __('partners.act_suspend') }}
                    </button>
                    <button type="submit" name="status" value="revoked" class="od-btn od-btn--danger">
                        <i data-lucide="ban"></i> {{ __('partners.act_revoke') }}
                    </button>
                </div>
            @else
                <div>
                    <button type="submit" name="status" value="active" class="od-btn od-btn--primary">
                        <i data-lucide="play"></i> {{ __('partners.act_reinstate') }}
                    </button>
                </div>
            @endif
        </form>
    </div>
</section>
</div>
</div>

</div>
@endsection
