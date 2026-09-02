<nav class="portal-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i data-lucide="plug-zap"></i>
            <span>{{ __('public.adm_connect_sidebar_title') }}</span>
        </div>
    </div>

    <div class="sidebar-section-label">{{ __('public.adm_connect_sidebar_section_overview') }}</div>
    
    <div class="sidebar-section-label">{{ __('public.adm_connect_sidebar_section_mgmt') }}</div>
    
    
    
    
    <div class="sidebar-section-label">{{ __('public.adm_connect_sidebar_section_navigate') }}</div>
    <a href="{{ route('portals.admin.cc') }}" class="sidebar-link">
        <i data-lucide="arrow-left"></i>
        <span>{{ __('public.adm_connect_sidebar_control_center') }}</span>
    </a>
    <a href="{{ route('portals.admin') }}" class="sidebar-link">
        <i data-lucide="home"></i>
        <span>{{ __('public.adm_connect_sidebar_admin_home') }}</span>
    </a>
</nav>
