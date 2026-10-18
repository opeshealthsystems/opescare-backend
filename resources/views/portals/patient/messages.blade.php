@extends('layouts.portal')

@section('title', __('messaging.inbox_title') . ' — OpesCare')

@section('breadcrumb_home', __('messaging.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner', ['viewingNote' => __('family.inbox_messages_note', ['name' => request()->attributes->get('viewing_patient')?->first_name])])
@endsection

@section('content')
@php
    $readOnly    = $readOnly ?? false;
    $viewing     = $viewing ?? null;
    $viewingName = $viewing ? trim(($viewing->first_name ?? '') . ' ' . ($viewing->last_name ?? '')) : '';
@endphp

<div class="pf">

    <div class="pf-head">
        <div>
            <h1>{{ __('messaging.inbox_title') }}</h1>
            <p class="pf-sub">{{ __('messaging.inbox_subtitle') }}</p>
        </div>
        @if($readOnly)
            <button type="button" class="f-btn f-btn--primary" disabled aria-disabled="true" data-section="compose-disabled">
                {{ __('messaging.compose_btn') }}
                @include('partials.finder.icon', ['name' => 'send', 'size' => 16])
            </button>
        @else
            <a href="{{ route('portals.patient.messages.compose') }}" class="f-btn f-btn--primary">
                {{ __('messaging.compose_btn') }}
                @include('partials.finder.icon', ['name' => 'send', 'size' => 16])
            </a>
        @endif
    </div>

    @if($readOnly)
    <div class="pf-alert" data-section="read-only-note">
        <i class="ods-d9ea87f" data-lucide="lock" aria-hidden="true"></i>
        <p>{{ __('family.inbox_read_only_compose', ['name' => $viewingName]) }}</p>
    </div>
    @endif

    @if($threads->isEmpty())
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'message-square', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('messaging.empty_title') }}</h2>
        <p class="f-muted ods-7307c03">{{ __('messaging.empty_body') }}</p>
        @unless($readOnly)
        <a href="{{ route('portals.patient.messages.compose') }}" class="f-btn f-btn--primary ods-a27b458">
            {{ __('messaging.compose_btn') }}
        </a>
        @endunless
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
