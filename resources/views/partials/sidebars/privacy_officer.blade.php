@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(109,40,217,.3);border-color:rgba(109,40,217,.5);color:#C4B5FD;">
    <i data-lucide="eye-off" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('portal.privacy_officer_role', [], $l) ?: 'Privacy Officer' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_administration', [], $l) ?: 'Administration' }}</div>
    <a href="{{ route('portals.admin') }}" class="sidebar-link">
        <i data-lucide="layout-dashboard"></i>
        <span>{{ __('public.portal.nav_dashboard', [], $l) ?: 'Dashboard' }}</span>
    </a>
</div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_compliance', [], $l) ?: 'Compliance' }}</div>
    <a href="{{ route('portals.admin.security') }}" class="sidebar-link">
        <i data-lucide="shield-check"></i>
        <span>{{ __('public.portal.nav_security', [], $l) ?: 'Security' }}</span>
    </a>
    <a href="{{ route('portals.admin.security.audit_explorer') }}" class="sidebar-link">
        <i data-lucide="file-search"></i>
        <span>{{ __('public.portal.nav_audit_log', [], $l) ?: 'Audit Log' }}</span>
    </a>
    <a href="{{ route('portals.admin.security.incidents') }}" class="sidebar-link">
        <i data-lucide="alert-octagon"></i>
        <span>{{ __('public.portal.nav_incidents', [], $l) ?: 'Security Incidents' }}</span>
    </a>
    <a href="{{ route('portals.admin.legal') }}" class="sidebar-link">
        <i data-lucide="scale"></i>
        <span>{{ __('public.portal.nav_legal', [], $l) ?: 'Legal Documents' }}</span>
    </a>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>
