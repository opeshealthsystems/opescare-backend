@extends('layouts.portal')
@section('title', __('governance_desk.export_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('governance_desk.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('governance_desk.export_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('governance_desk.export_title') }}</h2>
    <a href="{{ route('portals.admin.governance.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('governance_desk.back_to_queue') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('governance_desk.export_subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="download"></i> {{ __('governance_desk.export_title') }}</h3>
        <span class="badge">{{ __('governance_desk.status_' . $export->status) }}</span>
    </div>
    <div class="panel-body">
        <dl class="detail-list">
            <dt>{{ __('governance_desk.col_filed') }}</dt>
            <dd>{{ optional($export->created_at)->format('Y-m-d H:i') }}</dd>

            <dt>{{ __('governance_desk.export_type') }}</dt>
            <dd><code>{{ $export->export_type }}</code></dd>

            <dt>{{ __('governance_desk.export_scope') }}</dt>
            <dd><code>{{ json_encode($export->scope_json ?: []) }}</code></dd>

            <dt>{{ __('governance_desk.export_expires') }}</dt>
            <dd>{{ $export->expires_at ? $export->expires_at->format('Y-m-d H:i') : __('governance_desk.export_no_expiry') }}</dd>

            @if(filled($export->decision_reason))
            <dt>{{ __('governance_desk.export_decision_reason') }}</dt>
            <dd>{{ $export->decision_reason }}</dd>
            @endif
        </dl>
    </div>
</div>

@if($export->status === 'pending')
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="gavel"></i> {{ __('governance_desk.col_decision') }}</h3>
    </div>
    <div class="panel-body">
        {{-- One reason box, two buttons. The reason is required on the GRANT as
             well as the refusal: an unexplained approval of a full-record
             disclosure is not a decision anyone can review later. --}}
        <form method="POST" action="{{ route('portals.admin.governance.exports.approve', $export->id) }}" class="mb-6">
            @csrf
            <label class="form-label" for="approve-reason">{{ __('governance_desk.form_reason') }}</label>
            <textarea id="approve-reason" name="reason" class="form-control" rows="2" required minlength="5" maxlength="1000"
                      placeholder="{{ __('governance_desk.form_reason_placeholder') }}"></textarea>
            <p class="form-help">{{ __('governance_desk.form_reason_help') }}</p>
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="check"></i> {{ __('governance_desk.btn_approve') }}
            </button>
        </form>

        <form method="POST" action="{{ route('portals.admin.governance.exports.refuse', $export->id) }}">
            @csrf
            <label class="form-label" for="refuse-reason">{{ __('governance_desk.form_reason') }}</label>
            <textarea id="refuse-reason" name="reason" class="form-control" rows="2" required minlength="5" maxlength="1000"
                      placeholder="{{ __('governance_desk.form_reason_placeholder') }}"></textarea>
            <button type="submit" class="btn btn-danger btn-sm">
                <i data-lucide="x"></i> {{ __('governance_desk.btn_refuse') }}
            </button>
        </form>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="history"></i> {{ __('governance_desk.history_title') }}</h3>
    </div>
    <div class="panel-body panel-body--flush">
        @if($history->isEmpty())
            <div class="td-muted empty-cell p-4">{{ __('governance_desk.history_empty') }}</div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_when') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                    <th>{{ __('governance_desk.col_actor') }}</th>
                    <th>{{ __('governance_desk.col_reason') }}</th>
                </tr></thead>
                <tbody>
                @foreach($history as $ev)
                    <tr>
                        <td>{{ optional($ev->created_at)->format('Y-m-d H:i') }}</td>
                        <td><code>{{ $ev->action_type }}</code></td>
                        <td>{{ $ev->actor_role ?: '—' }}</td>
                        <td>{{ $ev->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
