{{--
    OPES desk topbar (.od-topbar) — docs/superpowers/specs/2026-10-18-desk-design-system.md.
    Rendered by layouts/portal.blade.php instead of the classic topbar on the
    pharmacy / lab / imaging / diagnostic desks. Keeps the ids the layout JS
    drives (#sidebar-toggle, #topbar-user-toggle, #topbar-user-menu) and the
    existing bell partial (#notif-bell, polled by notifications.js).

    Search: pharmacy sends GET ?q= to the counter lookup (prescription reference
    or Health ID); the lab family sends it to the staff global search. The
    barcode button submits the same form — a USB/Bluetooth scanner types into
    the field and sends Enter, so nothing else is needed.

    Facility: the ACTIVE facility (session, as set by select-facility) falling
    back to the primary one. It links to the switcher only when the user holds
    more than one posting (the same FacilityMembershipService gate as
    partials/sidebars/_switch_facility).
--}}
@php
    $odLoc       = app()->getLocale();
    $odUser      = auth()->user();
    $odSearchUrl = $odPharmacy ? route('portals.pharmacy.prescriptions.lookup') : route('portals.staff.search');
    $odActiveId  = session('active_facility_id');
    $odFacility  = (is_string($odActiveId) && \Illuminate\Support\Str::isUuid($odActiveId))
        ? \App\Models\Facility::withoutGlobalScope('isolate_demo')->whereKey($odActiveId)->value('name')
        : null;
    $odFacility  = $odFacility ?: $topbarFacilityName;
    $odCanSwitch = $odUser ? app(\App\Services\Portal\FacilityMembershipService::class)->hasChoice($odUser) : false;
    $odName      = $odUser->name ?? '';
    $odWords     = preg_split('/\s+/u', trim(preg_replace('/^(dr|pharm|mr|mrs|ms)\.?\s+/iu', '', $odName))) ?: [];
    $odInitials  = mb_strtoupper(mb_substr($odWords[0] ?? 'U', 0, 1) . (count($odWords) > 1 ? mb_substr(end($odWords), 0, 1) : ''));
    $odOther     = $odLoc === 'fr' ? 'en' : 'fr';
    $odOtherLbl  = $odLoc === 'fr' ? 'EN' : 'FR';
    $odFacTag    = $odCanSwitch ? 'a' : 'div';
@endphp
<header class="portal-topbar od-topbar" role="banner">
    <button class="topbar-menu-btn" id="sidebar-toggle" type="button" aria-label="{{ __('od.open_navigation', [], $odLoc) }}" aria-expanded="false" aria-controls="portal-sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
    </button>

    <form class="od-search" method="GET" action="{{ $odSearchUrl }}" role="search">
        <svg class="od-search__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <label for="od-search-q" class="od-sr">{{ __('od.search_label', [], $odLoc) }}</label>
        <input id="od-search-q" class="od-search__input" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}" autocomplete="off"
               placeholder="{{ $odPharmacy ? __('od.search_pharmacy', [], $odLoc) : __('od.search_lab', [], $odLoc) }}">
        <button type="submit" class="od-search__scan" aria-label="{{ __('od.scan_barcode', [], $odLoc) }}" title="{{ __('od.scan_barcode', [], $odLoc) }}">
            <x-od-icon name="barcode"/>
        </button>
    </form>

    <div class="od-topbar__actions">
        <div class="od-bell">
            @includeIf('partials.notifications.bell')
        </div>

        @if($odFacility)
            <{{ $odFacTag }} @if($odCanSwitch) href="{{ route('select-facility') }}" title="{{ __('od.facility_switch', [], $odLoc) }}: {{ $odFacility }}" @else title="{{ __('od.facility_label', [], $odLoc) }}: {{ $odFacility }}" @endif class="od-facility">
                <svg class="od-facility__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21V7l8-4 8 4v14"/><path d="M2 21h20"/><path d="M10 21v-4h4v4"/><path d="M12 7v4"/><path d="M10 9h4"/><path d="M7.5 13h.01"/><path d="M16.5 13h.01"/></svg>
                <span class="od-facility__name">{{ $odFacility }}</span>
                @if($odCanSwitch)
                    <svg class="od-facility__chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                @endif
            </{{ $odFacTag }}>
        @endif

        <a href="{{ url('/lang/' . $odOther) }}" class="od-lang" aria-label="{{ __('od.language_switch', ['lang' => $odOtherLbl], $odLoc) }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/></svg>
            <span>{{ $odOtherLbl }}</span>
        </a>

        <div class="od-user topbar-user">
            <button type="button" class="od-user__btn" id="topbar-user-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="topbar-user-menu" aria-label="{{ __('od.account_menu', [], $odLoc) }}">
                <span class="od-user__avatar" aria-hidden="true">{{ $odInitials }}</span>
                <span class="od-user__meta">
                    <span class="od-user__name">{{ $odName }}</span>
                    <span class="od-user__role">@hasSection('sidebar_user_role')@yield('sidebar_user_role')@else{{ $sidebarRoleLabel }}@endif</span>
                </span>
                <svg class="od-user__chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="topbar-user-menu" id="topbar-user-menu" hidden>
                <div class="topbar-user-meta">
                    <div class="topbar-user-name">{{ $odName }}</div>
                    <div class="topbar-user-role">@hasSection('sidebar_user_role')@yield('sidebar_user_role')@else{{ $sidebarRoleLabel }}@endif</div>
                    @if($odFacility)
                        <div class="topbar-user-facility">{{ $odFacility }}</div>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="topbar-user-signout">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        {{ __('public.portal.sign_out', [], $odLoc) }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
