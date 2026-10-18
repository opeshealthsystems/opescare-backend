@extends('layouts.portal')
@section('title', __('public.adm_cc_audit_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_audit_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_audit_breadcrumb_section'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_audit_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_audit_subtitle') }}</p>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cc_audit_breadcrumb_section') }} <span class="od-card__count">({{ method_exists($logs, 'total') ? $logs->total() : $logs->count() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($logs->count() === 0)
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="scroll-text"></i></span>
                <h3 class="od-empty__title">{{ __('public.adm_cc_audit_empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('public.adm_cc_audit_empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_audit_col_action') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_resource') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_resource_id') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_actor') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_ip') }}</th>
                    <th>{{ __('public.adm_cc_audit_col_when') }}</th>
                    <th class="od-table__actions"></th>
                </tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td data-label="{{ __('public.adm_cc_audit_col_action') }}"><span class="oda-mono">{{ $log->action }}</span></td>
                        <td data-label="{{ __('public.adm_cc_audit_col_resource') }}">
                            @if($log->resource_type)
                                <span class="od-pill od-pill--neutral">{{ $log->resource_type }}</span>
                            @else
                                <span class="oda-ga-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_cc_audit_col_resource_id') }}" class="oda-ga-muted">
                            @if($log->resource_id)
                                <span class="oda-mono">{{ Str::limit($log->resource_id, 12) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_cc_audit_col_actor') }}"><span class="oda-mono">{{ $log->actor_id }}</span></td>
                        <td data-label="{{ __('public.adm_cc_audit_col_ip') }}" class="oda-ga-muted">{{ $log->ip_address ?? '—' }}</td>
                        <td data-label="{{ __('public.adm_cc_audit_col_when') }}">
                            <span class="od-table__name">{{ \Carbon\Carbon::parse($log->occurred_at)->translatedFormat('M d, Y H:i') }}</span>
                            <span class="od-table__sub">{{ \Carbon\Carbon::parse($log->occurred_at)->diffForHumans() }}</span>
                        </td>
                        <td class="od-table__actions">
                            @if($log->after || $log->before)
                                <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm" aria-label="{{ __('public.aria_view_diff') }}"
                                    data-call="showDiff" data-args="{{ json_encode([$log->before, $log->after, $log->action]) }}" data-show="diff-modal" data-display="flex">
                                    <i data-lucide="eye"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    {{-- Pagination --}}
    @if($logs->count() > 0 && method_exists($logs, 'links'))
    <div class="od-card__foot">
        {{ $logs->links() }}
    </div>
    @endif
</section>
</div>

{{-- Diff Modal --}}
<div id="diff-modal" class="modal-fixed">
    <div class="od-card oda-modal oda-ga-modal--wide">
        <div class="od-card__head">
            <h3 id="diff-title" class="od-card__title"></h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('public.aria_close') }}" data-hide="diff-modal">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="od-card__body oda-ga-diff">
            <div>
                <p class="oda-ga-diff__label">{{ __('public.adm_cc_audit_diff_label_before') }}</p>
                <pre id="diff-before" class="oda-pre"></pre>
            </div>
            <div>
                <p class="oda-ga-diff__label">{{ __('public.adm_cc_audit_diff_label_after') }}</p>
                <pre id="diff-after" class="oda-pre"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
    var _ag = {
        diffTitlePrefix: @json(__('public.adm_cc_audit_js_diff_title_prefix')),
        empty: @json(__('public.adm_cc_audit_js_empty')),
    };
    function showDiff(before, after, action) {
        document.getElementById('diff-title').textContent = _ag.diffTitlePrefix + action;
        document.getElementById('diff-before').textContent = before ? JSON.stringify(before, null, 2) : _ag.empty;
        document.getElementById('diff-after').textContent  = after  ? JSON.stringify(after,  null, 2) : _ag.empty;
    }
</script>
@include('portals.admin._modal')
@endsection
