@extends('layouts.portal')
@section('title', __('partners.detail_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('partners.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('partners.detail_title'))

@section('content')
<div class="page-head">
    <h2>{{ $client->name ?: $client->client_id }}</h2>
    <a href="{{ route('portals.admin.partners.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('partners.back_to_list') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-success mb-6"><i data-lucide="check"></i><div>{{ session('success') }}</div></div>
@endif

{{-- THE ONE SHOWING. This block renders only on the request that created or
     rotated the secret, from a one-request flash. There is no route that can
     fetch it again, because after this response nothing on the platform holds
     the plaintext — only an Argon2id hash of it. --}}
@if($newSecret)
<div class="alert alert-warning mb-6">
    <i data-lucide="key-round"></i>
    <div>
        <strong>{{ __('partners.secret_once_title') }}</strong>
        <p>{{ __('partners.secret_once_body') }}</p>
        <p class="mb-1"><span class="text-muted">{{ __('partners.secret_client_id') }}:</span> <code>{{ $client->client_id }}</code></p>
        <p class="mb-0"><span class="text-muted">{{ __('partners.secret_value') }}:</span> <code>{{ $newSecret }}</code></p>
    </div>
</div>
@endif

<div class="card mb-6">
    <div class="card__head"><h3>{{ __('partners.section_identity') }}</h3></div>
    <div class="card__body">
        <dl class="detail-list">
            <dt>{{ __('partners.col_client_id') }}</dt><dd><code>{{ $client->client_id }}</code></dd>
            <dt>{{ __('partners.col_organisation') }}</dt><dd>{{ $facility?->name ?? __('partners.no_organisation') }}</dd>
            <dt>{{ __('partners.col_environment') }}</dt><dd>{{ __('partners.env_' . ($client->environment === 'production' ? 'production' : 'sandbox')) }}</dd>
            <dt>{{ __('partners.col_status') }}</dt>
            <dd><span class="pill {{ $client->status === 'active' ? 'pill--success' : 'pill--muted' }}">{{ __('partners.status_' . $client->status) }}</span></dd>
            <dt>{{ __('partners.contact') }}</dt><dd>{{ $client->contact_email }}</dd>
            <dt>{{ __('partners.created_at') }}</dt><dd>{{ optional($client->created_at)->toDayDateTimeString() }}</dd>
            <dt>{{ __('partners.col_last_used') }}</dt>
            <dd>{{ $client->last_used_at ? $client->last_used_at->toDayDateTimeString() : __('partners.never_used') }}</dd>
            <dt>{{ __('partners.request_count') }}</dt><dd>{{ number_format($client->request_count ?? 0) }}</dd>
        </dl>
    </div>
</div>

<div class="card mb-6">
    <div class="card__head"><h3>{{ __('partners.section_scopes') }}</h3></div>
    <div class="card__body">
        <form method="POST" action="{{ route('portals.admin.partners.scopes', $client->id) }}">
            @csrf
            <div class="checkbox-grid">
                @foreach($scopeLabels as $key => $label)
                <label class="checkbox">
                    <input type="checkbox" name="scopes[]" value="{{ $key }}"
                           @checked(in_array($key, (array) ($client->scopes ?? []), true))>
                    <span><code>{{ $key }}</code> — {{ $label }}</span>
                </label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary btn-sm mt-4">
                <i data-lucide="save"></i> {{ __('partners.act_scopes_save') }}
            </button>
        </form>
    </div>
</div>

<div class="card mb-6">
    <div class="card__head"><h3>{{ __('partners.section_creds') }}</h3></div>
    <div class="card__body">
        <p class="text-muted text-sm">{{ __('partners.secret_not_stored') }}</p>

        <form method="POST" action="{{ route('portals.admin.partners.rotate', $client->id) }}" class="mb-4">
            @csrf
            <p class="text-sm">{{ __('partners.act_rotate_help') }}</p>
            <button type="submit" class="btn btn-secondary btn-sm">
                <i data-lucide="refresh-cw"></i> {{ __('partners.act_rotate') }}
            </button>
        </form>

        <form method="POST" action="{{ route('portals.admin.partners.status', $client->id) }}">
            @csrf
            @if($client->status === 'active')
                <p class="text-sm">{{ __('partners.act_suspend_help') }}</p>
                <p class="text-sm">{{ __('partners.act_revoke_help') }}</p>
                <div class="form-group">
                    <label class="form-label" for="reason">{{ __('partners.act_reason') }}</label>
                    <input type="text" id="reason" name="reason" class="form-control" maxlength="500" required>
                </div>
                <button type="submit" name="status" value="suspended" class="btn btn-secondary btn-sm">
                    <i data-lucide="pause"></i> {{ __('partners.act_suspend') }}
                </button>
                <button type="submit" name="status" value="revoked" class="btn btn-danger btn-sm">
                    <i data-lucide="ban"></i> {{ __('partners.act_revoke') }}
                </button>
            @else
                <button type="submit" name="status" value="active" class="btn btn-primary btn-sm">
                    <i data-lucide="play"></i> {{ __('partners.act_reinstate') }}
                </button>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card__head"><h3>{{ __('partners.section_trail') }}</h3></div>
    <div class="card__body">
        @if($trail->isEmpty())
            <p class="text-muted">{{ __('partners.trail_none') }}</p>
        @else
        <div class="table-wrap">
            <table class="data-table">
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
                        <td>{{ optional($event->created_at)->toDayDateTimeString() }}</td>
                        <td><code>{{ $event->action_type }}</code></td>
                        <td>{{ $event->actor_role }}</td>
                        <td>{{ $event->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
