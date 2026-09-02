@extends('layouts.portal')

@section('title', __('staff_data.title_audit', [], app()->getLocale()) ?: 'Import Audit Log')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">{{ __('public.staff_portal.cdss_sidebar_role') }}</div>
@endsection
@section('sidebar_user_role', __('public.staff_portal.cdss_sidebar_role'))

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_overview') }}</div>
    <a href="{{ route('portals.staff') }}" class="sidebar-link"><i data-lucide="layout-dashboard"></i><span>{{ __('public.portal.nav_dashboard') }}</span></a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.staff_portal.cdss_sidebar_operations') }}</div>
    <a href="{{ route('portals.staff.data_import.index') }}" class="sidebar-link active"><i data-lucide="upload-cloud"></i><span>{{ __('public.portal.nav_data_import') }}</span></a>
</div>
    @feature('clinical_decision_support')
    @endfeature
    @feature('inventory_ops')
    <a href="{{ route('portals.staff.supply') }}" class="sidebar-link {{ request()->routeIs('portals.staff.supply*') ? 'active' : '' }}">
        <i data-lucide="package"></i> {{ __('public.portal.nav_supply') }}</a>
    @endfeature
@endsection

@section('breadcrumb_home', __('staff_data.bc_home', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('staff_data.bc_section', [], app()->getLocale()) ?: 'Data Import')

@section('content')

<div style="max-width:680px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
        <div>
            <h1 class="panel-heading">{{ __('public.stf_import_audit_log') }}</h1>
            <p class="text-sm text-muted" style="margin:0;">
                {{ $job->original_filename }} · {{ $importTypes[$job->import_type]['label'] ?? $job->import_type }}
            </p>
        </div>
        <a href="{{ route('portals.staff.data_import.index') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_import_back') }}</a>
    </div>

    <div class="panel">
        <div class="panel-body" style="padding:0;">
            @if($job->auditEvents->count() === 0)
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="scroll-text"></i></div>
                    <h3>{{ __('public.stf_import_no_audit_title') }}</h3>
                    <p>{{ __('public.stf_import_no_audit_desc') }}</p>
                </div>
            @else
                <div style="padding:.75rem 1.25rem;">
                    @foreach($job->auditEvents->sortBy('occurred_at') as $event)
                    <div style="display:flex;gap:.85rem;padding:.75rem 0;border-bottom:1px solid var(--p-border);align-items:flex-start;">
                        <div style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:var(--p-surface-2,#f1f5f9);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="activity" style="width:13px;height:13px;color:var(--p-primary);"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;">
                                <span style="font-weight:600;font-size:.88rem;text-transform:capitalize;">@enum($event->action)</span>
                                <span style="font-size:.75rem;color:var(--p-text-muted);">{{ \Carbon\Carbon::parse($event->occurred_at)->format('M d, Y H:i:s') }}</span>
                            </div>
                            @if($event->actor_id)
                                <div style="font-size:.78rem;color:var(--p-text-muted);margin-top:.15rem;">{{ __('staff_data.audit_by', ['id' => $event->actor_id], app()->getLocale()) ?: 'by '.$event->actor_id }}</div>
                            @endif
                            @if($event->details)
                                <div style="margin-top:.4rem;font-size:.78rem;background:var(--p-surface-2,#f1f5f9);border-radius:var(--p-radius);padding:.4rem .6rem;font-family:monospace;overflow-x:auto;">
                                    {{ json_encode($event->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
