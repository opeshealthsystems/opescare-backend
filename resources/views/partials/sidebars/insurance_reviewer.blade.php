@php $l = app()->getLocale(); @endphp
<div class="sidebar-role-badge">
    <i data-lucide="shield-check"></i>
    {{ __('public.portal.insurance_reviewer_role', [], $l) ?: 'Insurance Reviewer' }}
</div>
<div class="sidebar-spacer"></div>
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
