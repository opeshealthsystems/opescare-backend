{{-- Group D import: web page over the platform's BreachWorkflowService
     (open → contain → notify authority → close; 48 h warning, 72 h deadline). --}}
@extends('layouts.portal')
@section('title', __('security_ops.br_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.br_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.br_title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.admin.security.incidents') }}" class="btn btn-ghost btn-sm"><i data-lucide="siren"></i> {{ __('security_ops.nav_incidents') }}</a>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.br_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(count($overdue) > 0)
    <div class="alert alert-danger mb-6"><i data-lucide="alarm-clock"></i><div>{{ __('security_ops.br_regulatory_warning', ['count' => count($overdue)]) }}</div></div>
@endif

<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-warning"></i> {{ __('security_ops.br_open') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.security.breaches.store') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label" for="br_title">{{ __('security_ops.br_lbl_title') }}</label>
                <input id="br_title" type="text" name="title" class="form-control" maxlength="255">
            </div>
            <div class="form-group mb-3">
                <label class="form-label form-label-required" for="br_desc">{{ __('security_ops.br_lbl_description') }}</label>
                <textarea id="br_desc" name="description" rows="3" required class="form-control"></textarea>
            </div>
            <div class="filter-bar">
                <select name="breach_type" class="filter-select" aria-label="{{ __('security_ops.br_lbl_type') }}">
                    @foreach(['unauthorized_access', 'data_leak', 'ransomware', 'lost_device', 'insider_threat'] as $t)
                        <option value="{{ $t }}">{{ __('security_ops.br_type_'.$t) }}</option>
                    @endforeach
                </select>
                <select name="severity" class="filter-select" aria-label="{{ __('security_ops.br_lbl_severity') }}">
                    @foreach(['critical', 'high', 'medium', 'low'] as $sev)
                        <option value="{{ $sev }}">@enum($sev, 'severity')</option>
                    @endforeach
                </select>
                <input type="number" min="0" name="estimated_affected_records" class="form-control" placeholder="{{ __('security_ops.br_lbl_affected') }}" aria-label="{{ __('security_ops.br_lbl_affected') }}">
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus-circle"></i> {{ __('security_ops.br_btn_open') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($breaches->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('security_ops.br_empty') }}</h3>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.br_lbl_title') }}</th>
                    <th>{{ __('security_ops.br_lbl_severity') }}</th>
                    <th>{{ __('security_ops.br_col_status') }}</th>
                    <th>{{ __('security_ops.br_col_discovered') }}</th>
                    <th>{{ __('security_ops.br_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($breaches as $breach)
                    <tr>
                        <td data-label="{{ __('security_ops.br_lbl_title') }}" class="td-strong">
                            {{ $breach->title }}
                            <div class="td-muted">{{ __('security_ops.br_type_'.$breach->breach_type) }}</div>
                            @if(in_array($breach->id, $overdue))<span class="badge badge-danger badge-sm">{{ __('security_ops.br_badge_regulatory') }}</span>@endif
                        </td>
                        <td data-label="{{ __('security_ops.br_lbl_severity') }}">@enum($breach->severity, 'severity')</td>
                        <td data-label="{{ __('security_ops.br_col_status') }}">
                            {{ __('security_ops.br_status_'.$breach->status) }}
                            @if($breach->reported_to_authority_at)<div class="code-muted">{{ __('security_ops.br_notified_at', ['when' => $breach->reported_to_authority_at->format('d/m/Y H:i')]) }}</div>@endif
                        </td>
                        <td data-label="{{ __('security_ops.br_col_discovered') }}" class="td-muted">{{ $breach->discovered_at?->format('d/m/Y H:i') }}</td>
                        <td data-label="{{ __('security_ops.br_col_actions') }}">
                            @if(in_array($breach->status, ['open', 'investigating']))
                                <form method="POST" action="{{ route('portals.admin.security.breaches.contain', $breach->id) }}" class="inline-form">
                                    @csrf
                                    <input type="text" name="notes" required class="form-control" placeholder="{{ __('security_ops.br_ph_contain') }}" aria-label="{{ __('security_ops.br_ph_contain') }}">
                                    <button type="submit" class="btn btn-secondary btn-sm">{{ __('security_ops.br_btn_contain') }}</button>
                                </form>
                            @endif
                            @if(! $breach->reported_to_authority_at && $breach->status !== 'closed')
                                <form method="POST" action="{{ route('portals.admin.security.breaches.notify', $breach->id) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="send"></i> {{ __('security_ops.br_btn_notify') }}</button>
                                </form>
                            @endif
                            @if($breach->status !== 'closed')
                                <form method="POST" action="{{ route('portals.admin.security.breaches.close', $breach->id) }}" class="inline-form">
                                    @csrf
                                    <input type="text" name="resolution" required class="form-control" placeholder="{{ __('security_ops.br_ph_resolution') }}" aria-label="{{ __('security_ops.br_ph_resolution') }}">
                                    <button type="submit" class="btn btn-ghost btn-sm">{{ __('security_ops.br_btn_close') }}</button>
                                </form>
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
<div class="mt-4">{{ $breaches->links() }}</div>
@endsection
