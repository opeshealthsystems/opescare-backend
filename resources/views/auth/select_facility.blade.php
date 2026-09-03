@extends('layouts.auth')

@section('title', __('onboarding.facility_selector.title'))

@section('content')
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                @include('partials.finder.icon', ['name' => 'building-2', 'size' => 22])
            </div>
            <h1 class="f-auth-title">{{ __('onboarding.facility_selector.title') }}</h1>
            <p class="f-auth-sub">{{ __('onboarding.facility_selector.subtitle') }}</p>

            @if(session('error'))
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            {{-- The ids, the hidden "facility" input and the selectFacility()
                 wiring below are load-bearing — restyle only. --}}
            <form action="{{ route('select-facility.submit') }}" method="POST" id="facility-form">
                @csrf
                <input type="hidden" name="facility" id="selected-facility" value="">

                @foreach($facilities as $index => $facility)
                    @php
                        $cardId      = 'card-fac-' . $index;
                        $isSuspended = ($facility['status'] ?? '') === 'suspended';
                        $facilityVal = $isSuspended ? 'suspended' : $facility['id'];
                    @endphp
                    <div class="facility-card{{ $isSuspended ? ' disabled' : '' }}"
                         id="{{ $cardId }}"
                         onclick="{{ $isSuspended ? '' : "selectFacility('{$facilityVal}', '{$cardId}')" }}">
                        <div class="facility-card__icon">
                            @include('partials.finder.icon', ['name' => 'building-2', 'size' => 18])
                        </div>
                        <div class="facility-card__body">
                            <div class="facility-card__name">{{ $facility['name'] }}</div>
                            <div class="facility-card__meta">
                                {{ $facility['branch'] ?? '' }}
                                @if(!empty($facility['branch'])) &middot; @endif
                                {{ __('onboarding.facility_selector.role_label') }}: {{ $facility['role'] }}
                            </div>
                        </div>
                        @if($isSuspended)
                            <span class="f-badge f-badge--danger">{{ __('onboarding.facility_selector.status_suspended') }}</span>
                        @else
                            <span class="f-badge f-badge--good">{{ __('onboarding.facility_selector.status_active') }}</span>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="f-btn f-btn--primary f-btn--block" id="submit-btn" disabled>
                    @include('partials.finder.icon', ['name' => 'log-in', 'size' => 18])
                    {{ __('onboarding.facility_selector.cta_btn') }}
                </button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
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
