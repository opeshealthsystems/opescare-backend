@extends('layouts.portal')
@section('title', __('terminology.show_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.show_title'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gbStatusPill = match ($mapping->status) {
        'pending' => 'pending', 'approved' => 'ready', 'rejected' => 'urgent', default => 'neutral',
    };
@endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $mapping->local_name ?: $mapping->local_code }}</h1>
        <p class="od-page__sub">{{ __('terminology.show_title') }} · <code class="oda-mono">{{ $mapping->local_code }}</code></p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.terminology.index') }}" class="od-btn od-btn--ghost od-btn--sm">
            <i data-lucide="arrow-left"></i> {{ __('terminology.btn_back') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

{{-- A `review` confidence means an alternative code is defensible and the
     approver has to CHOOSE. It is stated loudly and at the top, before the
     approve button is anywhere on screen. --}}
@if($mapping->mapping_confidence === 'review' || filled($mapping->notes))
<div class="oda-gb-alert oda-gb-alert--warn">
    <i data-lucide="alert-triangle"></i>
    <div>
        <strong>{{ __('terminology.review_banner_heading') }}</strong>
        <div>{{ $mapping->notes ?: __('terminology.review_banner_body') }}</div>
    </div>
</div>
@endif

@unless($wellFormed)
<div class="oda-gb-alert oda-gb-alert--bad">
    <i data-lucide="shield-x"></i>
    <div>{{ __('terminology.malformed_banner') }}</div>
</div>
@endunless

<div class="oda-split">
<div class="od-stack">

{{-- Blast radius, BEFORE the decision. Approving is cheap; applying is not,
     and a reviewer who cannot see how many records a code lands on is being
     asked to decide blind. --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="target"></i> {{ __('terminology.panel_radius') }}</h2>
        <span class="od-card__count">{{ $radius['total'] }}</span>
    </div>
    <div class="od-card__body">
        <p class="oda-help">{{ __('terminology.radius_summary', [
            'total'    => $radius['total'],
            'write'    => $radius['write'],
            'already'  => $radius['already'],
            'conflict' => $radius['conflict'],
            'mismatch' => $radius['unit_mismatch'],
        ]) }}</p>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($candidates->isEmpty())
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="target"></i></div>
                <p class="od-empty__text">{{ __('terminology.radius_none') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('terminology.col_target') }}</th>
                    <th>{{ __('terminology.col_row_unit') }}</th>
                    <th>{{ __('terminology.col_current_code') }}</th>
                    <th>{{ __('terminology.col_decision') }}</th>
                </tr></thead>
                <tbody>
                @foreach($candidates as $row)
                <tr>
                    <td data-label="{{ __('terminology.col_target') }}">
                        <div class="od-table__name">{{ $row['label'] }}</div>
                        {{-- No patient name and no Health ID: a truncated row id only. --}}
                        <div class="od-table__sub"><code class="oda-mono">{{ $row['table'] }} · {{ substr($row['id'], 0, 8) }}</code></div>
                    </td>
                    <td data-label="{{ __('terminology.col_row_unit') }}">{{ $row['unit'] ?: '—' }}</td>
                    <td data-label="{{ __('terminology.col_current_code') }}">
                        {{ $row['current_code'] ? $row['current_code'] : '—' }}
                    </td>
                    <td data-label="{{ __('terminology.col_decision') }}">
                        @php $cls = match($row['decision']) {
                            'write' => 'ready',
                            'already' => 'normal',
                            'conflict' => 'urgent',
                            default => 'pending',
                        }; @endphp
                        <span class="od-pill od-pill--{{ $cls }}">{{ __('terminology.decision_' . $row['decision']) }}</span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="history"></i> {{ __('terminology.panel_history') }}</h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($history->isEmpty())
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="history"></i></div>
                <p class="od-empty__text">{{ __('terminology.history_none') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('terminology.col_when') }}</th>
                    <th>{{ __('terminology.col_action') }}</th>
                    <th>{{ __('terminology.col_actor') }}</th>
                    <th>{{ __('terminology.col_detail') }}</th>
                </tr></thead>
                <tbody>
                @foreach($history as $ev)
                <tr>
                    <td data-label="{{ __('terminology.col_when') }}">{{ $ev->created_at?->toDayDateTimeString() }}</td>
                    <td data-label="{{ __('terminology.col_action') }}"><code class="oda-mono">{{ $ev->action_type }}</code></td>
                    <td data-label="{{ __('terminology.col_actor') }}">{{ $ev->actor_role ?: '—' }}</td>
                    <td data-label="{{ __('terminology.col_detail') }}">
                        <span class="od-table__sub">{{ $ev->reason ?: json_encode($ev->after_state) }}</span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

</div>
<div class="od-stack">

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="book-marked"></i> {{ __('terminology.panel_mapping') }}</h2>
        <span class="od-pill od-pill--{{ $gbStatusPill }}">{{ __('terminology.status_' . $mapping->status) }}</span>
    </div>
    <div class="od-card__body">
        <dl class="oda-dl">
            <dt>{{ __('terminology.col_local_term') }}</dt>
            {{-- Analyte / test names are clinical vocabulary and are printed as stored. --}}
            <dd>{{ $mapping->local_name ?: $mapping->local_code }} <code class="oda-mono">{{ $mapping->local_code }}</code></dd>

            <dt>{{ __('terminology.field_unit') }}</dt>
            <dd>
                @if($mapping->local_unit)
                    <span class="od-pill od-pill--normal">{{ $mapping->local_unit }}</span>
                    <div class="od-table__sub">{{ __('terminology.field_unit_hint') }}</div>
                @else
                    <span class="od-table__sub">{{ __('terminology.field_unit_none') }}</span>
                @endif
            </dd>

            <dt>{{ __('terminology.col_resource_type') }}</dt>
            <dd>{{ __('terminology.resource_' . $mapping->resource_type) }}</dd>

            <dt>{{ __('terminology.col_standard') }}</dt>
            <dd><span class="od-pill od-pill--neutral">{{ $mapping->standard_system }}</span> <code class="oda-mono">{{ $mapping->standard_code }}</code></dd>

            <dt>{{ __('terminology.field_display') }}</dt>
            {{-- The official LOINC / ICD-10 display string. Never translated. --}}
            <dd>{{ $mapping->standard_display }}</dd>

            <dt>{{ __('terminology.col_confidence') }}</dt>
            <dd>
                @if($mapping->mapping_confidence === 'review')
                    <span class="od-pill od-pill--pending">{{ __('terminology.confidence_review') }}</span>
                @else
                    <span class="od-pill od-pill--ready">{{ __('terminology.confidence_high') }}</span>
                @endif
            </dd>

            <dt>{{ __('terminology.field_reviewer') }}</dt>
            <dd>{{ $mapping->approved_by ?: __('terminology.field_reviewer_none') }}
                @if($mapping->approved_at)<div class="od-table__sub">{{ $mapping->approved_at->toDayDateTimeString() }}</div>@endif
            </dd>
        </dl>
    </div>
</section>

{{-- Two separate decisions, deliberately two separate panels. Approving says
     the code is right; applying writes it onto records. --}}
@if($mapping->isPending())
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="gavel"></i> {{ __('terminology.panel_review') }}</h2>
    </div>
    <div class="od-card__body od-stack">
        <p class="oda-help">{{ __('terminology.review_hint') }}</p>
        <form method="POST" action="{{ route('portals.admin.terminology.approve', $mapping->id) }}">
            @csrf
            <button type="submit" class="od-btn od-btn--primary" @disabled(! $wellFormed)>
                <i data-lucide="check"></i> {{ __('terminology.btn_approve') }}
            </button>
        </form>
        <form method="POST" action="{{ route('portals.admin.terminology.reject', $mapping->id) }}" data-confirm="{{ __('admin_desk.confirm_reject_mapping') }}" class="od-stack">
            @csrf
            <label class="oda-label" for="reject-reason">
                <span class="oda-label__text">{{ __('terminology.field_reject_reason') }}</span>
                <textarea id="reject-reason" name="reason" class="oda-textarea" rows="2"
                          placeholder="{{ __('terminology.field_reject_placeholder') }}" required></textarea>
            </label>
            <div class="od-row">
                <button type="submit" class="od-btn od-btn--danger">
                    <i data-lucide="x"></i> {{ __('terminology.btn_reject') }}
                </button>
            </div>
        </form>
    </div>
</section>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="stamp"></i> {{ __('terminology.panel_apply') }}</h2>
    </div>
    <div class="od-card__body">
        @if($refusal)
            <div class="oda-gb-alert oda-gb-alert--warn">
                <i data-lucide="hand"></i><div>{{ __($refusal) }}</div>
            </div>
        @else
            <form method="POST" action="{{ route('portals.admin.terminology.apply', $mapping->id) }}" class="od-stack">
                @csrf
                <p class="oda-help">{{ __('terminology.apply_hint') }}</p>
                @if($radius['conflict'] > 0)
                {{-- A row already carrying a DIFFERENT code is never overwritten
                     unless the operator says so here, in as many words. --}}
                <label class="oda-check">
                    <input type="checkbox" name="confirm_recode" value="1">
                    {{ __('terminology.confirm_recode', ['n' => $radius['conflict']]) }}
                </label>
                @endif
                <div class="od-row">
                    <button type="submit" class="od-btn od-btn--primary">
                        <i data-lucide="stamp"></i> {{ __('terminology.btn_apply', ['n' => $radius['write']]) }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</section>

</div>
</div>
</div>
@endsection
