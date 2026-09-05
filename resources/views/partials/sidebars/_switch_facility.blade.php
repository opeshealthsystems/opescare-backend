{{--
    "Switch Facility" — the sidebar entry for a clinician posted at more than
    one facility.

    SELF-GATING ON PURPOSE. Every facility-scoped sidebar includes it
    unconditionally and this partial decides whether there is anything to show:
    it renders NOTHING unless FacilityMembershipService says the signed-in user
    holds more than one active posting. So a nurse at one hospital never sees a
    switch with one destination, a patient never sees it at all, and a platform
    admin — who holds no postings and whose portals are unscoped — never sees it
    either.

    That gate is the reason it can be included everywhere: 41 sidebars each
    deciding for themselves is 41 chances to get it wrong, and until now exactly
    one of them (multi_facility_admin) had the link at all — which is why a
    multi-facility DOCTOR had no way to change facility from the portal.

    The active facility is named above the link. A switcher that cannot tell you
    where you are is a guess with extra steps, and this is the only place in the
    portal chrome that says which building the data on screen belongs to.

    Lucide icons only (never emoji) — `repeat` matches the one
    multi_facility_admin already used, so the two read as the same control.
--}}
@php
    $switchUser  = auth()->user();
    $switchSvc   = app(\App\Services\Portal\FacilityMembershipService::class);
    $switchShow  = $switchSvc->hasChoice($switchUser);

    // Only look the name up when a facility has actually been chosen. A
    // multi-facility clinician who has not chosen yet reaches this partial with
    // an empty session value, and `id = <empty>` against a uuid column is a
    // Postgres 22P02 — a 500 in the sidebar of every page.
    $switchActiveId = session('active_facility_id');
    $switchNow      = ($switchShow && is_string($switchActiveId) && \Illuminate\Support\Str::isUuid($switchActiveId))
        ? \App\Models\Facility::withoutGlobalScope('isolate_demo')->whereKey($switchActiveId)->value('name')
        : null;

    $switchLocale = app()->getLocale();
@endphp

@if($switchShow)
    <div class="sidebar-nav-section">
        @if($switchNow)
            <div class="sidebar-nav-label">
                {{ __('facility_switch.acting_as', [], $switchLocale) }}: {{ $switchNow }}
            </div>
        @endif
        <a href="{{ route('select-facility') }}"
           class="sidebar-link {{ request()->routeIs('select-facility') ? 'active' : '' }}">
            <i data-lucide="repeat"></i>
            <span>{{ __('facility_switch.nav', [], $switchLocale) }}</span>
        </a>
    </div>
@endif
