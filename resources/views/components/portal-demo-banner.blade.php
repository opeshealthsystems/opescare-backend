@php $l = app()->getLocale(); @endphp
@auth
@if(auth()->user()->is_demo && !request()->routeIs('portals.developer.*'))

<style>
.demo-banner {
    position: sticky;
    top: 0;
    z-index: 9999;
    width: 100%;
    background: linear-gradient(90deg, rgba(245,158,11,.95) 0%, rgba(217,119,6,.95) 100%);
    color: #1c1300;
    font-size: 0.8125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.45rem 1.25rem;
    letter-spacing: 0.01em;
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.demo-banner-msg {
    display: flex;
    align-items: center;
    gap: .5rem;
}
.demo-banner-msg i {
    width: .9rem;
    height: .9rem;
    flex-shrink: 0;
}
.demo-banner-expiry {
    font-size: 0.75rem;
    opacity: .85;
    white-space: nowrap;
    margin-left: 1rem;
    display: flex;
    align-items: center;
    gap: .25rem;
}
.demo-banner-expiry i {
    width: .75rem;
    height: .75rem;
}
</style>

<div id="portal-demo-banner" class="demo-banner">
    <span class="demo-banner-msg">
        <i data-lucide="flask-conical" aria-hidden="true"></i>
        {{ __('public.portal.demo_banner', [], $l) ?: 'Demo Mode — You are viewing sample data only. No real patient records are accessible.' }}
    </span>
    @if(session('demo_session_expires_at'))
    <span class="demo-banner-expiry">
        <i data-lucide="clock" aria-hidden="true"></i>
        {{ __('public.portal.demo_expires', [], $l) ?: 'Session expires' }}:
        {{ \Carbon\Carbon::parse(session('demo_session_expires_at'))->diffForHumans() }}
    </span>
    @endif
</div>
@endif
@endauth
