@extends('layouts.portal')

@section('title', __('messaging.inbox_title') . ' — OpesCare')

@section('breadcrumb_home', __('messaging.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('content')

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('messaging.inbox_title') }}</h1>
            <p class="pf-sub">{{ __('messaging.inbox_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.messages.compose') }}" class="f-btn f-btn--primary">
            {{ __('messaging.compose_btn') }}
            @include('partials.finder.icon', ['name' => 'send', 'size' => 16])
        </a>
    </div>

    @if($threads->isEmpty())
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'message-square', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('messaging.empty_title') }}</h2>
        <p class="f-muted" style="margin:0 0 18px;">{{ __('messaging.empty_body') }}</p>
        <a href="{{ route('portals.patient.messages.compose') }}" class="f-btn f-btn--primary" style="min-height:48px;">
            {{ __('messaging.compose_btn') }}
        </a>
    </div>
    @else
    <div class="pf-rows">
        @foreach($threads as $row)
        <a href="{{ route('portals.patient.messages.show', $row['uuid']) }}"
           class="f-card pf-row {{ $row['unread'] ? 'pf-unread' : '' }}"
           aria-label="{{ __('finder_patient.open_conversation') }}">
            <div class="pf-row__body">
                <div class="pf-row__title">
                    {{ $row['title'] ?: __('messaging.no_subject') }}
                    @if($row['unread'])
                        <span class="f-badge">{{ __('messaging.unread') }}</span>
                    @endif
                    <span class="f-badge {{ $row['status'] === 'closed' ? 'pf-badge--muted' : 'f-badge--good' }}">
                        {{ $row['status'] === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
                    </span>
                    @if(!empty($row['context_type']))
                        @php $ctxKey = ['lab_result'=>'lab','prescription'=>'rx','appointment'=>'appt','visit'=>'visit','insurance_policy'=>'insurance'][$row['context_type']] ?? 'generic'; @endphp
                        <span class="f-badge pf-badge--muted">{{ __('messaging.ctx_' . $ctxKey) }}</span>
                    @endif
                </div>
                @if($row['snippet'])
                    <p class="pf-row__snippet">{{ $row['snippet'] }}</p>
                @endif
            </div>
            <div class="pf-row__aside">
                @if($row['last_at'])
                    <span>{{ $row['last_at']->isoFormat('D MMM, HH:mm') }}</span>
                @endif
                @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>

@endsection
