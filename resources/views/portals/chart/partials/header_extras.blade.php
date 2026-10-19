{{-- Summary header extras: allergy pills (always open: the safety minimum),
     the consent pill (the facility's active grant), the guardian pill (only
     when family links are open) and the actions this reader's role holds at
     the active facility. Every action goes to an existing desk, which gates
     itself again on its own routes. --}}
<div class="cs-head">
    @foreach(($allergies ?? collect())->take(3) as $a)
        <span class="od-pill od-pill--pending"><i data-lucide="triangle-alert"></i>
            {{ __('chart_summary.allergy_pill', ['substance' => $a->substance ?: '—']) }}@if($a->severity) ({{ \App\Support\Enums::label($a->severity, 'severity') }})@endif
        </span>
    @endforeach

    @if(!empty($grant))
        <span class="od-pill od-pill--ready"><i data-lucide="shield-check"></i>
            {{ __('chart_summary.consent_pill', ['until' => $grant['expires_at']?->translatedFormat('d M H:i') ?? '—']) }}
        </span>
    @endif

    @if(!empty($open['family']))
        @foreach($guardians as $g)
            <span class="od-pill od-pill--normal"><i data-lucide="users"></i>
                {{ __('chart_summary.guardian_pill', ['name' => $g['name'], 'relationship' => \Illuminate\Support\Facades\Lang::has('family.relationship.' . $g['relationship']) ? __('family.relationship.' . $g['relationship']) : $g['relationship']]) }}
            </span>
        @endforeach
    @endif

    <div class="cs-actions">
        @capability('imaging')
        <a class="od-btn od-btn--secondary" href="{{ route('portals.imaging.create', ['health_id' => $identity['health_id']]) }}"><i data-lucide="scan-line"></i> {{ __('chart_summary.action_order_imaging') }}</a>
        @endcapability
        @capability('clinical_chart')
        <a class="od-btn od-btn--secondary" href="{{ route('portals.referrals.create', ['health_id' => $identity['health_id']]) }}"><i data-lucide="send"></i> {{ __('chart_summary.action_refer') }}</a>
        @endcapability
        @capability('triage')
        <a class="od-btn od-btn--primary" href="{{ route('portals.triage.create', ['health_id' => $identity['health_id']]) }}"><i data-lucide="stethoscope"></i> {{ __('chart_summary.action_new_visit') }}</a>
        @endcapability
    </div>
</div>
