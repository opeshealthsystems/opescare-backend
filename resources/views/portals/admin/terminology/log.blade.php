@extends('layouts.portal')
@section('title', __('terminology.log_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('terminology.log_title') }}</h1>
        {{-- This is what makes an applied code answerable. Every row carries the
             mapping, the actor and the state the record was in before the write, so a
             code on a result can always be traced back — and put back. --}}
        <p class="od-page__sub">{{ __('terminology.log_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.terminology.index') }}" class="od-btn od-btn--ghost od-btn--sm">
            <i data-lucide="arrow-left"></i> {{ __('terminology.btn_back') }}
        </a>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('terminology.log_title') }}</h2>
        <span class="od-card__count">{{ method_exists($events, 'total') ? $events->total() : $events->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($events->isEmpty())
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="scroll-text"></i></div>
                <h3 class="od-empty__title">{{ __('terminology.log_empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('terminology.log_empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('terminology.col_when') }}</th>
                    <th>{{ __('terminology.col_action') }}</th>
                    <th>{{ __('terminology.col_actor') }}</th>
                    <th>{{ __('terminology.col_mapping') }}</th>
                    <th>{{ __('terminology.col_before') }}</th>
                    <th>{{ __('terminology.col_after') }}</th>
                </tr></thead>
                <tbody>
                @foreach($events as $ev)
                <tr>
                    <td data-label="{{ __('terminology.col_when') }}">{{ $ev->created_at?->toDayDateTimeString() }}</td>
                    <td data-label="{{ __('terminology.col_action') }}"><code class="oda-mono">{{ $ev->action_type }}</code></td>
                    <td data-label="{{ __('terminology.col_actor') }}">{{ $ev->actor_role ?: '—' }}</td>
                    <td data-label="{{ __('terminology.col_mapping') }}">
                        @if($ev->resource_id)
                            <a href="{{ route('portals.admin.terminology.show', $ev->resource_id) }}">
                                <code class="oda-mono">{{ substr($ev->resource_id, 0, 8) }}</code>
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="{{ __('terminology.col_before') }}"><span class="od-table__sub oda-mono">{{ json_encode($ev->before_state) }}</span></td>
                    <td data-label="{{ __('terminology.col_after') }}"><span class="od-table__sub oda-mono">{{ json_encode($ev->after_state) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

<div class="oda-gb-pager">{{ $events->links() }}</div>
</div>
@endsection
