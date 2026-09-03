@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="globe"></i>
    {{ __('public.admin_governance.role_platform_owner', [], $l) ?: 'Platform Owner' }}
</div>
<div class="sidebar-spacer"></div>

@include('portals.admin._admin_nav')
