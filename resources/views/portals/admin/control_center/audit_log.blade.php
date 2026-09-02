@extends('layouts.portal')
@section('title', __('public.adm_cc_audit_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_audit_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_audit_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_audit_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_audit_subtitle') }}</p>
    </div>
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($logs->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="scroll-text"></i></div>
                <h3>{{ __('public.adm_cc_audit_empty_heading') }}</h3>
                <p>{{ __('public.adm_cc_audit_empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_audit_col_action') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_resource') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_resource_id') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_actor') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_ip') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_when') }}</th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td data-label="{{ __('public.adm_cc_audit_col_action') }}"><span class="code-token">{{ $log->action }}</span></td>
                        <td data-label="{{ __('public.adm_cc_audit_col_resource') }}">
                            @if($log->resource_type)
                                <span class="badge badge-neutral badge-sm">{{ $log->resource_type }}</span>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_cc_audit_col_resource_id') }}" class="td-muted">
                            @if($log->resource_id)
                                <span class="code-muted">{{ Str::limit($log->resource_id, 12) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_cc_audit_col_actor') }}">{{ $log->actor_id }}</td>
                        <td data-label="{{ __('public.adm_cc_audit_col_ip') }}" class="td-muted">{{ $log->ip_address ?? '—' }}</td>
                        <td data-label="{{ __('public.adm_cc_audit_col_when') }}" class="td-muted">
                            {{ \Carbon\Carbon::parse($log->occurred_at)->format('M d, Y H:i') }}
                            <div class="code-muted">{{ \Carbon\Carbon::parse($log->occurred_at)->diffForHumans() }}</div>
                        </td>
                        <td class="row-actions">
                            @if($log->after || $log->before)
                                <button type="button" class="icon-btn" aria-label="{{ __('public.aria_view_diff') }}"
                                    onclick="showDiff({{ json_encode($log->before) }}, {{ json_encode($log->after) }}, '{{ addslashes($log->action) }}')">
                                    <i data-lucide="eye"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($logs, 'links'))
        <div class="panel-footer">
            {{ $logs->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

{{-- Diff Modal --}}
<div id="diff-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 id="diff-title" class="modal-fixed__title"></h3>
            <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="closeDiff()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="diff-grid">
            <div>
                <div class="diff-label">{{ __('public.adm_cc_audit_diff_label_before') }}</div>
                <pre id="diff-before" class="diff-pre"></pre>
            </div>
            <div>
                <div class="diff-label">{{ __('public.adm_cc_audit_diff_label_after') }}</div>
                <pre id="diff-after" class="diff-pre"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var _ag = {
        diffTitlePrefix: @json(__('public.adm_cc_audit_js_diff_title_prefix')),
        empty: @json(__('public.adm_cc_audit_js_empty')),
    };
    function showDiff(before, after, action) {
        document.getElementById('diff-title').textContent = _ag.diffTitlePrefix + action;
        document.getElementById('diff-before').textContent = before ? JSON.stringify(before, null, 2) : _ag.empty;
        document.getElementById('diff-after').textContent  = after  ? JSON.stringify(after,  null, 2) : _ag.empty;
        document.getElementById('diff-modal').classList.add('open');
    }
    function closeDiff() { document.getElementById('diff-modal').classList.remove('open'); }
    document.getElementById('diff-modal').addEventListener('click', function(e) { if (e.target === this) closeDiff(); });
</script>
@endsection
