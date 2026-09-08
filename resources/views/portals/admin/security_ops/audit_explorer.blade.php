@extends('layouts.portal')
@section('title', __('security_ops.audit_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.audit_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.audit_title') }}</h2>
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.audit_subtitle') }}</p>

{{-- Filters. The actor field takes an exact ID: this is a trail explorer, not
     a directory, and there is no name or patient lookup here by design. --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.admin.security.audit_explorer') }}" class="field-grid">
            <div class="form-group">
                <label class="form-label" for="f-action">{{ __('security_ops.audit_filter_action_type') }}</label>
                <select id="f-action" name="action_type" class="form-control">
                    <option value="">{{ __('security_ops.audit_filter_all_actions') }}</option>
                    @foreach($actionTypes as $at)
                        <option value="{{ $at }}" {{ request('action_type') === $at ? 'selected' : '' }}>{{ $at }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="f-resource">{{ __('security_ops.audit_filter_resource_type') }}</label>
                <select id="f-resource" name="resource_type" class="form-control">
                    <option value="">{{ __('security_ops.audit_filter_all_resources') }}</option>
                    @foreach($resourceTypes as $rt)
                        <option value="{{ $rt }}" {{ request('resource_type') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="f-actor">{{ __('security_ops.audit_filter_actor_id') }}</label>
                <input id="f-actor" type="text" name="actor_id" value="{{ request('actor_id') }}" class="form-control"
                       placeholder="{{ __('security_ops.audit_filter_actor_placeholder') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="f-from">{{ __('security_ops.audit_filter_date_from') }}</label>
                <input id="f-from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label" for="f-to">{{ __('security_ops.audit_filter_date_to') }}</label>
                <input id="f-to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('security_ops.audit_filter_emergency_label') }}</label>
                <label class="form-check">
                    <input type="checkbox" name="emergency_only" value="1" {{ request('emergency_only') ? 'checked' : '' }}>
                    {{ __('security_ops.audit_filter_emergency_only') }}
                </label>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search"></i> {{ __('security_ops.btn_search') }}</button>
                <a href="{{ route('portals.admin.security.audit_explorer') }}" class="btn btn-ghost btn-sm">{{ __('security_ops.btn_clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="search-code"></i> {{ __('security_ops.audit_events_title') }}</h3>
        <span class="badge badge-neutral badge-sm">{{ $events->total() }} {{ __('security_ops.audit_total_suffix') }}</span>
    </div>
    <div class="panel-body panel-body--flush">
        @if($events->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
                <h3>{{ __('security_ops.audit_empty') }}</h3>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.audit_col_action') }}</th>
                    <th>{{ __('security_ops.audit_col_resource') }}</th>
                    <th>{{ __('security_ops.audit_col_actor') }}</th>
                    <th>{{ __('security_ops.audit_col_patient') }}</th>
                    <th>{{ __('security_ops.audit_col_ip') }}</th>
                    <th>{{ __('security_ops.audit_col_authority') }}</th>
                    <th>{{ __('security_ops.audit_col_when') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @foreach($events as $ev)
                    <tr>
                        <td data-label="{{ __('security_ops.audit_col_action') }}" class="td-strong">{{ $ev->action_type }}</td>
                        <td data-label="{{ __('security_ops.audit_col_resource') }}">
                            @if($ev->resource_type)
                                <span class="badge badge-neutral badge-sm">{{ $ev->resource_type }}</span>
                                @if($ev->resource_id)
                                    <span class="code-muted">{{ substr($ev->resource_id, 0, 8) }}…</span>
                                @endif
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.audit_col_actor') }}" class="td-muted">
                            <span class="code-muted">{{ $ev->actor_id ? substr($ev->actor_id, 0, 8).'…' : '—' }}</span>
                            @if($ev->actor_role)
                                <div class="code-muted">{{ $ev->actor_role }}</div>
                            @endif
                        </td>
                        {{-- Masked: this desk never renders a patient name, and an
                             audit row is not a reason to resolve one. --}}
                        <td data-label="{{ __('security_ops.audit_col_patient') }}" class="td-muted">
                            <span class="code-muted">{{ $ev->patient_id ? substr($ev->patient_id, 0, 8).'…' : '—' }}</span>
                        </td>
                        <td data-label="{{ __('security_ops.audit_col_ip') }}" class="td-muted">{{ $ev->ip_address ?? '—' }}</td>
                        <td data-label="{{ __('security_ops.audit_col_authority') }}">
                            @if($ev->emergency_override)
                                <span class="badge badge-danger badge-sm">{{ __('security_ops.audit_badge_emergency') }}</span>
                            @else
                                <span class="badge badge-neutral badge-sm">{{ __('security_ops.audit_badge_consented') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('security_ops.audit_col_when') }}" class="td-muted">
                            {{ $ev->created_at?->format('M d, Y H:i') }}
                        </td>
                        <td class="row-actions">
                            @if($ev->before_state || $ev->after_state || $ev->reason)
                                <button type="button" class="icon-btn" aria-label="{{ __('security_ops.audit_view_details') }}"
                                    onclick="showDetail({{ json_encode($ev->before_state) }}, {{ json_encode($ev->after_state) }}, {{ json_encode($ev->action_type) }}, {{ json_encode($ev->reason ?? '') }})">
                                    <i data-lucide="eye"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            {{ $events->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Detail modal --}}
<div id="detail-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 id="detail-title" class="modal-fixed__title"></h3>
            <button type="button" class="icon-btn" aria-label="{{ __('security_ops.btn_close') }}" onclick="closeDetail()"><i data-lucide="x"></i></button>
        </div>
        <div id="detail-reason" class="text-muted text-sm mb-3"></div>
        <div class="form-row">
            <div class="form-group">
                <div class="form-label">{{ __('security_ops.audit_diff_before') }}</div>
                <pre id="detail-before" class="code-block"></pre>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('security_ops.audit_diff_after') }}</div>
                <pre id="detail-after" class="code-block"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var AUDIT_REASON_LABEL = {{ Js::from(__('security_ops.audit_reason_label')) }};
function showDetail(before, after, action, reason) {
    document.getElementById('detail-title').textContent = action;
    document.getElementById('detail-reason').textContent = reason ? AUDIT_REASON_LABEL + ': ' + reason : '';
    document.getElementById('detail-before').textContent = before ? JSON.stringify(before, null, 2) : '—';
    document.getElementById('detail-after').textContent = after ? JSON.stringify(after, null, 2) : '—';
    document.getElementById('detail-modal').style.display = 'flex';
}
function closeDetail() { document.getElementById('detail-modal').style.display = 'none'; }
document.getElementById('detail-modal').addEventListener('click', function (e) { if (e.target === this) closeDetail(); });
</script>
@endsection
