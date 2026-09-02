@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge" style="background:rgba(55,48,163,.3);border-color:rgba(55,48,163,.5);color:#A5B4FC;">
    <i data-lucide="shield-check" style="width:.75rem;height:.75rem;display:inline;vertical-align:middle;margin-right:4px;"></i>
    {{ __('public.portal.insurance_reviewer_role', [], $l) ?: 'Insurance Reviewer' }}
</div>
<div style="margin-bottom:var(--p-space-3);"></div>
{{-- Insurance is frozen out of the V1 launch scope (config/features.php).
     Every route below 404s while the flag is off, so the whole nav goes with
     it — no dead links, and the module does not advertise that it exists. --}}
@feature('insurance')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_dashboard', [], $l) ?: 'Overview' }}</div>
    </div>
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_operations', [], $l) ?: 'Insurance' }}</div>
                </div>
@endfeature
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</div>
    <a href="{{ route('public.help') }}" class="sidebar-link">
        <i data-lucide="help-circle"></i>
        <span>{{ __('public.portal.nav_help', [], $l) ?: 'Help' }}</span>
    </a>
</div>
