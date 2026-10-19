@extends('layouts.portal')

@section('title', __('team.join_page_title') . ' — OpesCare')


@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pro.css') }}?v={{ @filemtime(public_path('css/od-pro.css')) ?: '1' }}">
@endsection

@section('content')

@php
    /* A role label may legitimately fall outside the invitable allow-list, so
       fall back to the raw name rather than printing a translation key. */
    $roleLabel = function (?string $name): string {
        if (! $name) {
            return '—';
        }

        $label = __('team.roles.' . $name);

        return is_string($label) && $label !== 'team.roles.' . $name ? $label : $name;
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('team.join_page_title') }}</h1>
        <p class="page-subtitle">{{ __('team.join_page_subtitle') }}</p>
    </div>
</div>

<div class="alert alert-info mb-4" role="status">
    <i data-lucide="shield-check"></i>
    <div>{{ __('team.join_role_notice') }}</div>
</div>

@if(session('success'))
<div class="alert alert-success mb-4" role="alert"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-4" role="alert">
    <i data-lucide="alert-triangle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif

{{-- ── Where I already work ─────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('team.join_teams_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($currentTeams->isEmpty() && ! $user->primary_facility_id)
            <div class="empty-state">
                <i data-lucide="building-2"></i>
                <p>{{ __('team.join_teams_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('team.join_col_facility') }}</th>
                        <th>{{ __('team.col_role') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($currentTeams as $team)
                    <tr>
                        <td data-label="{{ __('team.join_col_facility') }}">{{ $team->facility?->name ?? '—' }}</td>
                        <td data-label="{{ __('team.col_role') }}">{{ $roleLabel($team->role?->name) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Search + request ─────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="search"></i> {{ __('team.join_search_label') }}</h3>
    </div>
    <div class="panel-body">
        <form class="ods-dea18dc" method="GET" action="{{ route('professional.join') }}">
            <div class="ods-9c68c41">
                <label class="form-label" for="q">{{ __('team.join_search_label') }}</label>
                <input type="search" id="q" name="q" class="form-control" maxlength="120"
                       placeholder="{{ __('team.join_search_placeholder') }}" value="{{ $query }}">
            </div>
            <div>
                <button type="submit" class="btn btn-secondary fs-tap">
                    <i data-lucide="search"></i> {{ __('team.join_search_submit') }}
                </button>
            </div>
        </form>

        @if($query === '')
            <p class="page-subtitle ods-b14a326">{{ __('team.join_search_prompt') }}</p>
        @elseif($facilities->isEmpty())
            <div class="empty-state ods-9dae1d0">
                <i data-lucide="search-x"></i>
                <p>{{ __('team.join_search_empty') }}</p>
            </div>
        @else
            <form class="ods-1f36178" method="POST" action="{{ route('professional.join.store') }}">
                @csrf

                <fieldset class="ods-8a27a29">
                    <legend class="form-label ods-57ce1f1">{{ __('team.join_search_label') }}</legend>
                    <div class="ods-4857901">
                        @foreach($facilities as $facility)
                            <label class="ods-0811055">
                                <input type="radio" name="facility_id" value="{{ $facility->id }}"
                                       @checked(old('facility_id') === $facility->id) required>
                                <span>
                                    <strong>{{ $facility->name }}</strong>
                                    @if($facility->type)
                                        <span class="page-subtitle ods-a53f4a4">{{ $facility->type }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="page-subtitle ods-394b59a">{{ __('team.join_search_hint', ['limit' => $pickerLimit]) }}</p>
                </fieldset>

                <div class="ods-b21e5af">
                    <div>
                        <label class="form-label" for="requested_role">{{ __('team.join_field_requested_role') }}</label>
                        <input type="text" id="requested_role" name="requested_role" class="form-control" maxlength="120"
                               placeholder="{{ __('team.join_field_requested_role_ph') }}" value="{{ old('requested_role') }}">
                        <p class="page-subtitle ods-9ea1f75">{{ __('team.join_field_requested_role_help') }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="message">{{ __('team.join_field_message') }}</label>
                        <textarea id="message" name="message" class="form-control" rows="3" maxlength="1000"
                                  placeholder="{{ __('team.join_field_message_ph') }}">{{ old('message') }}</textarea>
                    </div>
                </div>

                <div class="ods-9dae1d0">
                    <button type="submit" class="btn btn-primary fs-tap">
                        <i data-lucide="send"></i> {{ __('team.join_submit') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

{{-- ── My requests ──────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('team.join_my_requests_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($requests->isEmpty())
            <div class="empty-state">
                <i data-lucide="clipboard-list"></i>
                <p>{{ __('team.join_my_requests_empty') }}</p>
            </div>
        @else
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('team.join_col_facility') }}</th>
                        <th>{{ __('team.join_col_requested') }}</th>
                        <th>{{ __('team.join_col_granted') }}</th>
                        <th>{{ __('team.col_status') }}</th>
                        <th>{{ __('team.join_col_submitted') }}</th>
                        <th>{{ __('team.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($requests as $req)
                    <tr>
                        <td data-label="{{ __('team.join_col_facility') }}">{{ $req->facility?->name ?? '—' }}</td>
                        <td data-label="{{ __('team.join_col_requested') }}">{{ $req->requested_role ?: '—' }}</td>
                        <td data-label="{{ __('team.join_col_granted') }}">{{ $roleLabel($req->grantedRole?->name) }}</td>
                        <td data-label="{{ __('team.col_status') }}">
                            <span class="badge {{ $req->status->badgeClass() }}">{{ __($req->status->labelKey()) }}</span>
                        </td>
                        <td data-label="{{ __('team.join_col_submitted') }}">{{ $req->created_at?->isoFormat('LLL') }}</td>
                        <td class="ods-4ddbe90" data-label="{{ __('team.col_actions') }}">
                            @if($req->isPending())
                                <form class="ods-5677b98" method="POST" action="{{ route('professional.join.withdraw', $req->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm fs-tap">
                                        <i data-lucide="undo-2"></i> {{ __('team.join_action_withdraw') }}
                                    </button>
                                </form>
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

@endsection
