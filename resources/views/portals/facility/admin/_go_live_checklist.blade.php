{{-- Group D import: "Go-live checklist" panel on /facility/team/overview.
     $facility is the facility the controller resolved from PortalContextService
     (session facility only) — this partial never reads a facility id itself. --}}
@if(! empty($facility?->id))
    @php $automatic = app(\App\Modules\FacilityReadiness\Services\FacilityReadinessScoringService::class)->evaluate($facility->id); @endphp
    <div class="go-live-checklist" data-facility="{{ $facility->id }}">
        <p class="page-subtitle">{{ __('readiness.facility_intro') }}</p>
        @include('portals.admin._readiness_checks', ['automatic' => $automatic])
    </div>
@endif
