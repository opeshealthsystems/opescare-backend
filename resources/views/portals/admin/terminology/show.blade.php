@extends('layouts.portal')
@section('title', __('terminology.show_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.show_title'))

@section('content')
<div class="page-head">
    <h2>{{ $mapping->local_name ?: $mapping->local_code }}</h2>
    <a href="{{ route('portals.admin.terminology.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('terminology.btn_back') }}
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

{{-- A `review` confidence means an alternative code is defensible and the
     approver has to CHOOSE. It is stated loudly and at the top, before the
     approve button is anywhere on screen. --}}
@if($mapping->mapping_confidence === 'review' || filled($mapping->notes))
<div class="alert alert-warning mb-6">
    <i data-lucide="alert-triangle"></i>
    <div>
        <strong>{{ __('terminology.review_banner_heading') }}</strong>
        <div>{{ $mapping->notes ?: __('terminology.review_banner_body') }}</div>
    </div>
</div>
@endif

@unless($wellFormed)
<div class="alert alert-danger mb-6">
    <i data-lucide="shield-x"></i>
    <div>{{ __('terminology.malformed_banner') }}</div>
</div>
@endunless

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="book-marked"></i> {{ __('terminology.panel_mapping') }}</h3>
        <span class="badge {{ $mapping->statusBadgeClass() }}">{{ __('terminology.status_' . $mapping->status) }}</span>
    </div>
    <div class="panel-body">
        <dl class="detail-list">
            <dt>{{ __('terminology.col_local_term') }}</dt>
            {{-- Analyte / test names are clinical vocabulary and are printed as stored. --}}
            <dd>{{ $mapping->local_name ?: $mapping->local_code }} <code>{{ $mapping->local_code }}</code></dd>

            <dt>{{ __('terminology.field_unit') }}</dt>
            <dd>
                @if($mapping->local_unit)
                    <span class="badge badge--info">{{ $mapping->local_unit }}</span>
                    <span class="td-muted">{{ __('terminology.field_unit_hint') }}</span>
                @else
                    <span class="td-muted">{{ __('terminology.field_unit_none') }}</span>
                @endif
            </dd>

            <dt>{{ __('terminology.col_resource_type') }}</dt>
            <dd>{{ __('terminology.resource_' . $mapping->resource_type) }}</dd>

            <dt>{{ __('terminology.col_standard') }}</dt>
            <dd><span class="badge">{{ $mapping->standard_system }}</span> <code>{{ $mapping->standard_code }}</code></dd>

            <dt>{{ __('terminology.field_display') }}</dt>
            {{-- The official LOINC / ICD-10 display string. Never translated. --}}
            <dd>{{ $mapping->standard_display }}</dd>

            <dt>{{ __('terminology.col_confidence') }}</dt>
            <dd>
                @if($mapping->mapping_confidence === 'review')
                    <span class="badge badge--warning">{{ __('terminology.confidence_review') }}</span>
                @else
                    <span class="badge badge--success">{{ __('terminology.confidence_high') }}</span>
                @endif
            </dd>

            <dt>{{ __('terminology.field_reviewer') }}</dt>
            <dd>{{ $mapping->approved_by ?: __('terminology.field_reviewer_none') }}
                @if($mapping->approved_at)<span class="td-muted">{{ $mapping->approved_at->toDayDateTimeString() }}</span>@endif
            </dd>
        </dl>
    </div>
</div>

{{-- Blast radius, BEFORE the decision. Approving is cheap; applying is not,
     and a reviewer who cannot see how many records a code lands on is being
     asked to decide blind. --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="target"></i> {{ __('terminology.panel_radius') }}</h3>
    </div>
    <div class="panel-body">
        <p class="td-muted">{{ __('terminology.radius_summary', [
            'total'    => $radius['total'],
            'write'    => $radius['write'],
            'already'  => $radius['already'],
            'conflict' => $radius['conflict'],
            'mismatch' => $radius['unit_mismatch'],
        ]) }}</p>
    </div>
    <div class="panel-body panel-body--flush">
        @if($candidates->isEmpty())
            <div class="td-muted empty-cell">{{ __('terminology.radius_none') }}</div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('terminology.col_target') }}</th>
                    <th>{{ __('terminology.col_row_unit') }}</th>
                    <th>{{ __('terminology.col_current_code') }}</th>
                    <th>{{ __('terminology.col_decision') }}</th>
                </tr></thead>
                <tbody>
                @foreach($candidates as $row)
                <tr>
                    <td data-label="{{ __('terminology.col_target') }}" class="td-strong">
                        {{ $row['label'] }}
                        {{-- No patient name and no Health ID: a truncated row id only. --}}
                        <div class="td-muted"><code>{{ $row['table'] }} · {{ substr($row['id'], 0, 8) }}</code></div>
                    </td>
                    <td data-label="{{ __('terminology.col_row_unit') }}">{{ $row['unit'] ?: '—' }}</td>
                    <td data-label="{{ __('terminology.col_current_code') }}">
                        {{ $row['current_code'] ? $row['current_code'] : '—' }}
                    </td>
                    <td data-label="{{ __('terminology.col_decision') }}">
                        @php $cls = match($row['decision']) {
                            'write' => 'badge--success',
                            'already' => 'badge--info',
                            'conflict' => 'badge--danger',
                            default => 'badge--warning',
                        }; @endphp
                        <span class="badge {{ $cls }}">{{ __('terminology.decision_' . $row['decision']) }}</span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Two separate decisions, deliberately two separate panels. Approving says
     the code is right; applying writes it onto records. --}}
@if($mapping->isPending())
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="gavel"></i> {{ __('terminology.panel_review') }}</h3>
    </div>
    <div class="panel-body">
        <p class="td-muted mb-4">{{ __('terminology.review_hint') }}</p>
        <form method="POST" action="{{ route('portals.admin.terminology.approve', $mapping->id) }}" class="mb-6">
            @csrf
            <button type="submit" class="btn btn-primary" @disabled(! $wellFormed)>
                <i data-lucide="check"></i> {{ __('terminology.btn_approve') }}
            </button>
        </form>
        <form method="POST" action="{{ route('portals.admin.terminology.reject', $mapping->id) }}">
            @csrf
            <label class="form-label" for="reject-reason">{{ __('terminology.field_reject_reason') }}</label>
            <textarea id="reject-reason" name="reason" class="form-control" rows="2"
                      placeholder="{{ __('terminology.field_reject_placeholder') }}" required></textarea>
            <button type="submit" class="btn btn-danger mt-4">
                <i data-lucide="x"></i> {{ __('terminology.btn_reject') }}
            </button>
        </form>
    </div>
</div>
@endif

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stamp"></i> {{ __('terminology.panel_apply') }}</h3>
    </div>
    <div class="panel-body">
        @if($refusal)
            <div class="alert alert-warning">
                <i data-lucide="hand"></i><div>{{ __($refusal) }}</div>
            </div>
        @else
            <p class="td-muted mb-4">{{ __('terminology.apply_hint') }}</p>
            <form method="POST" action="{{ route('portals.admin.terminology.apply', $mapping->id) }}">
                @csrf
                @if($radius['conflict'] > 0)
                {{-- A row already carrying a DIFFERENT code is never overwritten
                     unless the operator says so here, in as many words. --}}
                <label class="form-check">
                    <input type="checkbox" name="confirm_recode" value="1">
                    {{ __('terminology.confirm_recode', ['n' => $radius['conflict']]) }}
                </label>
                @endif
                <button type="submit" class="btn btn-primary mt-4">
                    <i data-lucide="stamp"></i> {{ __('terminology.btn_apply', ['n' => $radius['write']]) }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="history"></i> {{ __('terminology.panel_history') }}</h3>
    </div>
    <div class="panel-body panel-body--flush">
        @if($history->isEmpty())
            <div class="td-muted empty-cell">{{ __('terminology.history_none') }}</div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
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
                    <td data-label="{{ __('terminology.col_action') }}"><code>{{ $ev->action_type }}</code></td>
                    <td data-label="{{ __('terminology.col_actor') }}">{{ $ev->actor_role ?: '—' }}</td>
                    <td data-label="{{ __('terminology.col_detail') }}" class="td-muted">
                        {{ $ev->reason ?: json_encode($ev->after_state) }}
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
