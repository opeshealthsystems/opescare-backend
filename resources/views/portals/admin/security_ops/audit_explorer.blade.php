@extends('layouts.portal')
@section('title', __('public.adm_secops_audit_title'))
@include('portals.admin.security_ops._sidebar')
@section('breadcrumb_home', __('public.adm_secops_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_secops_audit_title'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_secops_audit_title') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_secops_audit_subtitle') }}</p>
    </div>
</div>

{{-- Filter panel --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.admin.security.audit_explorer') }}" class="field-grid">
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_secops_audit_filter_action_type') }}</label>
                <select name="action_type" class="form-control">
                    <option value="">{{ __('public.adm_secops_audit_filter_all_actions') }}</option>
                    @foreach($actionTypes as $at)
                        <option value="{{ $at }}" {{ request('action_type') === $at ? 'selected' : '' }}>{{ $at }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_secops_audit_filter_resource_type') }}</label>
                <select name="resource_type" class="form-control">
                    <option value="">{{ __('public.adm_secops_audit_filter_all_resources') }}</option>
                    @foreach($resourceTypes as $rt)
                        <option value="{{ $rt }}" {{ request('resource_type') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_secops_audit_filter_actor_id') }}</label>
                <input type="text" name="actor_id" value="{{ request('actor_id') }}"
                    class="form-control" placeholder="{{ __('public.adm_secops_audit_filter_actor_placeholder') }}">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_secops_audit_filter_date_from') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_secops_audit_filter_date_to') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_secops_audit_filter_emergency_label') }}</label>
                <label class="form-check">
                    <input type="checkbox" name="emergency_only" value="1" {{ request('emergency_only') ? 'checked' : '' }}>
                    {{ __('public.adm_secops_audit_filter_emergency_only') }}
                </label>
            </div>
            <div class="form-group form-actions-end">
                <div class="row-actions-inline">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('public.adm_secops_btn_search') }}</button>
                    <a href="{{ route('portals.admin.security.audit_explorer') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_secops_btn_clear') }}</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="search-code"></i> {{ __('public.adm_secops_audit_events_title') }}</h3>
        <span class="badge badge-neutral badge-sm">{{ $events->total() }} {{ __('public.adm_secops_audit_total_suffix') }}</span>
    </div>
    <div class="panel-body panel-body--flush">
        @if($events->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
                <h3>{{ __('public.adm_secops_audit_empty') }}</h3>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_secops_audit_col_action') }}</th><th>{{ __('public.adm_secops_audit_col_resource') }}</th><th>{{ __('public.adm_secops_audit_col_actor') }}</th><th>{{ __('public.adm_secops_audit_col_patient') }}</th><th>{{ __('public.adm_secops_audit_col_ip') }}</th><th>{{ __('public.adm_secops_audit_col_emergency') }}</th><th>{{ __('public.adm_secops_audit_col_when') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach($events as $ev)
                    <tr class="{{ $ev->emergency_override ? 'row-emergency' : '' }}">
                        <td data-label="{{ __('public.adm_secops_audit_col_action') }}"><span class="code-token">{{ $ev->action_type }}</span></td>
                        <td data-label="{{ __('public.adm_secops_audit_col_resource') }}">
                            @if($ev->resource_type)
                                <span class="badge badge-neutral badge-sm">{{ $ev->resource_type }}</span>
                                @if($ev->resource_id)
                                    <span class="code-muted">{{ substr($ev->resource_id,0,8) }}…</span>
                                @endif
                            @else —
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_secops_audit_col_actor') }}">{{ $ev->actor_id ? Str::limit($ev->actor_id,16) : '—' }}</td>
                        <td data-label="{{ __('public.adm_secops_audit_col_patient') }}" class="td-muted">{{ $ev->patient_id ? substr($ev->patient_id,0,8).'…' : '—' }}</td>
                        <td data-label="{{ __('public.adm_secops_audit_col_ip') }}" class="td-muted">{{ $ev->ip_address ?? '—' }}</td>
                        <td data-label="{{ __('public.adm_secops_audit_col_emergency') }}">
                            @if($ev->emergency_override)
                                <span class="badge badge-emergency badge-sm">{{ __('public.adm_secops_badge_emergency') }}</span>
                            @else
                                <span class="badge badge-allowed badge-sm">{{ __('public.adm_secops_badge_allowed') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_secops_audit_col_when') }}" class="td-muted">
                            {{ \Carbon\Carbon::parse($ev->created_at)->format('M d, H:i') }}
                        </td>
                        <td class="row-actions">
                            @if($ev->before_state || $ev->after_state)
                                <button type="button" class="icon-btn" aria-label="{{ __('public.adm_secops_audit_view_details') }}"
                                    onclick="showDetail({{ json_encode($ev->before_state) }}, {{ json_encode($ev->after_state) }}, '{{ addslashes($ev->action_type) }}', '{{ $ev->reason ? addslashes($ev->reason) : '' }}')">
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
            <button type="button" class="icon-btn" aria-label="{{ __('public.adm_secops_btn_close') }}" onclick="closeDetail()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div id="detail-reason" class="text-muted text-sm mb-3"></div>
        <div class="diff-grid">
            <div>
                <div class="diff-label">{{ __('public.adm_secops_audit_diff_before') }}</div>
                <pre id="detail-before" class="diff-pre"></pre>
            </div>
            <div>
                <div class="diff-label">{{ __('public.adm_secops_audit_diff_after') }}</div>
                <pre id="detail-after" class="diff-pre"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showDetail(before, after, action, reason) {
    document.getElementById('detail-title').textContent = action;
    document.getElementById('detail-reason').textContent = reason ? 'Reason: ' + reason : '';
    document.getElementById('detail-before').textContent = before ? JSON.stringify(before, null, 2) : '(empty)';
    document.getElementById('detail-after').textContent  = after  ? JSON.stringify(after,  null, 2) : '(empty)';
    document.getElementById('detail-modal').style.display = 'flex';
}
function closeDetail() { document.getElementById('detail-modal').style.display = 'none'; }
document.getElementById('detail-modal').addEventListener('click', function(e) { if(e.target===this) closeDetail(); });
</script>
@endsection
