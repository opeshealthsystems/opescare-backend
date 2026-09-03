@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="shield-check"></i>
    {{ __('public.portal.insurance_reviewer_role', [], $l) ?: 'Insurance Reviewer' }}
</div>
<div class="sidebar-spacer"></div>

{{-- Insurance is frozen out of the V1 launch scope: no insurance routes exist
     in the finder, so this role's sidebar is intentionally minimal — the module
     does not advertise pages that would 404. --}}
