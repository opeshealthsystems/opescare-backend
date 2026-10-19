{{-- Signed-in only (the controller redirects guests to /login), so it wears the
     portal shell like every other sidebar destination — audit P2-8. --}}
@extends('layouts.portal')

@section('title', __('onboarding.facility_selector.title'))

@section('head')
<style>
    .sf-list { display: grid; gap: .6rem; margin-bottom: 1rem; }
    .sf-card { display: flex; align-items: center; gap: .85rem; padding: .85rem 1rem; border: 1px solid var(--od-line, #E6EAF0); border-radius: 10px; background: var(--od-surface, #fff); cursor: pointer; min-width: 0; }
    .sf-card:hover { border-color: #0F4C81; }
    .sf-card.active { border-color: #0F4C81; box-shadow: 0 0 0 2px rgba(15,76,129,.18); }
    .sf-card.disabled { opacity: .6; cursor: not-allowed; }
    .sf-card__body { flex: 1; min-width: 0; }
    .sf-card__name { font-weight: 600; overflow-wrap: anywhere; }
    .sf-card__meta { font-size: .85rem; color: #5B6472; overflow-wrap: anywhere; }
    .sf-wrap { max-width: 640px; }
    .sf-wrap .od-card { margin-top: 1rem; }
    .sf-wrap .od-card__body { padding: 1rem; }
</style>
@endsection

@section('content')
<div class="sf-wrap">
    <div class="od-page__head">
        <div>
            <h1 class="od-page__title">{{ __('onboarding.facility_selector.title') }}</h1>
            <p class="od-page__sub">{{ __('onboarding.facility_selector.subtitle') }}</p>
        </div>
    </div>
    <div class="od-card"><div class="od-card__body">
        @if(session('error'))
                <div class="alert alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            {{-- The ids, the hidden "facility" input and the selectFacility()
                 wiring below are load-bearing — restyle only. --}}
            <form action="{{ route('select-facility.submit') }}" method="POST" id="facility-form">
                @csrf
                <input type="hidden" name="facility" id="selected-facility" value="">
                <div class="sf-list">

                @foreach($facilities as $index => $facility)
                    @php
                        $cardId      = 'card-fac-' . $index;
                        $isSuspended = ($facility['status'] ?? '') === 'suspended';
                        $facilityVal = $isSuspended ? 'suspended' : $facility['id'];
                    @endphp
                    <div class="sf-card facility-card{{ $isSuspended ? ' disabled' : '' }}"
                         id="{{ $cardId }}"
                         @unless($isSuspended) data-call="selectFacility" data-args="{{ json_encode([$facilityVal, $cardId]) }}" @endunless>
                        <div class="sf-card__icon">
                            @include('partials.finder.icon', ['name' => 'building-2', 'size' => 18])
                        </div>
                        <div class="sf-card__body">
                            <div class="sf-card__name">{{ $facility['name'] }}</div>
                            <div class="sf-card__meta">
                                {{ $facility['branch'] ?? '' }}
                                @if(!empty($facility['branch'])) &middot; @endif
                                {{ __('onboarding.facility_selector.role_label') }}: {{ $facility['role'] }}
                            </div>
                        </div>
                        @if($isSuspended)
                            <span class="od-pill od-pill--urgent">{{ __('onboarding.facility_selector.status_suspended') }}</span>
                        @else
                            <span class="od-pill od-pill--ready">{{ __('onboarding.facility_selector.status_active') }}</span>
                        @endif
                    </div>
                @endforeach
                </div>

                <button type="submit" class="od-btn od-btn--primary od-btn--block" id="submit-btn" disabled>
                    @include('partials.finder.icon', ['name' => 'log-in', 'size' => 18])
                    {{ __('onboarding.facility_selector.cta_btn') }}
                </button>
            </form>
    </div></div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
    function selectFacility(facilityVal, cardId) {
        document.getElementById('selected-facility').value = facilityVal;

        document.querySelectorAll('.facility-card').forEach(function(card) {
            card.classList.remove('active');
        });

        document.getElementById(cardId).classList.add('active');

        var submitBtn = document.getElementById('submit-btn');
        submitBtn.removeAttribute('disabled');
    }
</script>
@endsection
