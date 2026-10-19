@extends('layouts.portal')
@section('title', __('governance_desk.log_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('governance_desk.log_title') }}</h1>
        <p class="od-page__sub">{{ __('governance_desk.log_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.governance.index') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('governance_desk.back_to_queue') }}
        </a>
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="scroll-text"></i> {{ __('governance_desk.log_title') }}</h2>
        <span class="od-card__count">{{ method_exists($events, 'total') ? $events->total() : $events->count() }}</span>
    </div>
    @if($events->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="scroll-text"></i></span>
                <h3 class="od-empty__title">{{ __('governance_desk.empty_log_heading') }}</h3>
                <p class="od-empty__text">{{ __('governance_desk.empty_log_body') }}</p>
            </div>
        </div>
    @else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_when') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                    <th>{{ __('governance_desk.col_actor') }}</th>
                    <th>{{ __('governance_desk.col_reason') }}</th>
                </tr></thead>
                <tbody>
                @foreach($events as $ev)
                    <tr>
                        <td>{{ optional($ev->created_at)->format('Y-m-d H:i') }}</td>
                        <td><code class="oda-mono">{{ $ev->action_type }}</code></td>
                        <td>{{ $ev->actor_role ?: '—' }}</td>
                        <td>{{ $ev->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $events->links() }}</div>
    @endif
</div>

</div>
@endsection
