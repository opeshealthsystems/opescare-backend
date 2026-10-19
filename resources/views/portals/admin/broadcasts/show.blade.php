@extends('layouts.portal')

@section('title', $broadcast->title . ' — OpesCare')

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $broadcast->title }}</h1>
        <p class="od-page__sub">
            <span class="od-pill od-pill--neutral">{{ __('broadcasts.type_' . $broadcast->broadcast_type) }}</span>
            <span class="od-pill od-pill--normal">@enum($broadcast->status)</span>
            <span>{{ __('broadcasts.audience_' . $broadcast->target_type) }}</span>
        </p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.broadcasts') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('broadcasts.back') }}</a>
    </div>
</div>

<div class="od-grid od-grid--main">
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title"><i data-lucide="list-checks"></i> {{ __('broadcasts.acks_title') }}</h2>
            <span class="od-card__count">{{ $acks->count() }}</span>
        </div>
        @if(! $broadcast->requires_acknowledgement)
            <div class="od-card__body"><div class="od-empty"><p class="od-empty__text">{{ __('broadcasts.ack_not_required') }}</p></div></div>
        @elseif($acks->isEmpty())
            <div class="od-card__body"><div class="od-empty"><span class="od-empty__icon"><i data-lucide="inbox"></i></span><p class="od-empty__text">{{ __('broadcasts.acks_empty') }}</p></div></div>
        @else
        <div class="od-card__body od-card__body--flush">
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead><tr><th>{{ __('broadcasts.col_user') }}</th><th>{{ __('broadcasts.col_facility') }}</th><th>{{ __('broadcasts.col_acknowledged_at') }}</th></tr></thead>
                    <tbody>
                        @foreach($acks as $ack)
                        @php $uName = $users[$ack->user_id]->name ?? '—'; @endphp
                        <tr>
                            <td data-label="{{ __('broadcasts.col_user') }}">
                                <div class="od-table__person">
                                    <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($ack->user_id) }}">{{ $gdInit($uName) }}</span>
                                    <span class="od-table__name">{{ $uName }}</span>
                                </div>
                            </td>
                            <td data-label="{{ __('broadcasts.col_facility') }}">{{ $facilities[$ack->facility_id] ?? '—' }}</td>
                            <td data-label="{{ __('broadcasts.col_acknowledged_at') }}">{{ $ack->acknowledged_at?->isoFormat('lll') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="megaphone"></i> {{ __('broadcasts.field_body') }}</h2></div>
        <div class="od-card__body">
            <p class="oda-gd-text oda-gd-prewrap">{{ $broadcast->body }}</p>
            <dl class="oda-dl">
                <dt>{{ __('broadcasts.field_publish_at') }}</dt><dd>{{ $broadcast->publish_at?->isoFormat('lll') ?? '—' }}</dd>
                <dt>{{ __('broadcasts.field_expires') }}</dt><dd>{{ $broadcast->expires_at?->isoFormat('lll') ?? '—' }}</dd>
            </dl>
        </div>
    </div>
</div>

</div>
@endsection
