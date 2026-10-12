@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    {{ __('public.adm_cc_sidebar_super_admin', [], $l) }}
</div>
<div class="sidebar-spacer"></div>

@include('portals.admin._admin_nav')
