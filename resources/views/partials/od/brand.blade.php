{{-- OPES desk lockup (.od-brand): custom mark + "OPES" + product word + "by OPES
     Health Systems". $odProduct is the area: pharmacy | lab | clinic | admin | patient | pro. No emoji, no Lucide. --}}
@php $odLoc = app()->getLocale(); @endphp
<a href="{{ $odHome }}" class="od-brand" aria-label="{{ __('od.brand_home', [], $odLoc) }}">
    <svg class="od-brand__mark" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <g class="od-brand__ring" stroke="currentColor">
            <path d="M54.5 20.5A26 26 0 1 0 57 36" stroke-width="3.5"/>
            <path d="M49 13.5A26 26 0 0 1 53 17.5" stroke-width="3.5"/>
            <circle cx="57.2" cy="28" r="2.2" fill="currentColor" stroke="none"/>
        </g>
        @if($odProduct === 'clinic')
            {{-- care: medical cross --}}
            <g stroke="#FFFFFF" stroke-width="2.6">
                <path d="M27 17h10v10h10v10H37v10H27V37H17V27h10z"/>
            </g>
            <g stroke="#4FA3FF" stroke-width="2.4">
                <path d="M28 32h3l2-4 2.5 8 1.5-4h3"/>
            </g>
        @elseif($odProduct === 'admin')
            {{-- admin: shield with check --}}
            <g stroke="#FFFFFF" stroke-width="2.6">
                <path d="M32 14l14 5v11c0 9.5-6 16-14 20-8-4-14-10.5-14-20V19z"/>
            </g>
            <g stroke="#4FA3FF" stroke-width="2.6">
                <path d="M25.5 32l4.5 4.5 8.5-9"/>
            </g>
        @elseif($odProduct === 'patient')
            {{-- health: ID card --}}
            <g stroke="#FFFFFF" stroke-width="2.6">
                <rect x="15" y="20" width="34" height="24" rx="4"/>
                <circle cx="25" cy="30" r="3.6"/>
                <path d="M19.5 39.5c1-3 3-4.5 5.5-4.5s4.5 1.5 5.5 4.5"/>
            </g>
            <g stroke="#4FA3FF" stroke-width="2.4">
                <path d="M35 28h9"/>
                <path d="M35 33h9"/>
                <path d="M35 38h5"/>
            </g>
        @elseif($odProduct === 'pro')
            {{-- pro: stethoscope --}}
            <g stroke="#FFFFFF" stroke-width="2.6">
                <path d="M21 15h-2v10a8 8 0 0 0 16 0V15h-2"/>
                <path d="M27 33v4a9 9 0 0 0 18 0v-5"/>
            </g>
            <g stroke="#4FA3FF" stroke-width="2.4">
                <circle cx="45" cy="28.5" r="3.5"/>
            </g>
        @elseif($odProduct === 'lab')
            <g stroke="#FFFFFF" stroke-width="2.6">
                <path d="M19 48h22"/>
                <path d="M24 43.5h10"/>
                <path d="M33 43.5a10 10 0 0 0 3.5-17.5"/>
                <path d="M22.5 17.5l6-3.4 7.2 12.4-6 3.4z"/>
                <path d="M27.2 30.2l3 5.2"/>
                <path d="M24 36h8"/>
                <path d="M21 15.5l1.6 2.8"/>
            </g>
            <g stroke="#4FA3FF" stroke-width="2.4">
                <path d="M41 29h6"/>
                <path d="M42.5 29v6l-3.6 6.4a1.6 1.6 0 0 0 1.4 2.4h7.4a1.6 1.6 0 0 0 1.4-2.4L45.5 35v-6"/>
                <path d="M40.6 39h8.8"/>
            </g>
        @else
            <g stroke="#FFFFFF" stroke-width="2.6">
                <path d="M32 14v36"/>
                <circle cx="32" cy="12.5" r="2.4"/>
                <path d="M20 19c4 0 8 1.6 12 4 4-2.4 8-4 12-4"/>
                <path d="M22 19.5c1.5 2.6 5 3.5 10 3.5"/>
                <path d="M42 19.5c-1.5 2.6-5 3.5-10 3.5"/>
            </g>
            <g stroke="#4FA3FF" stroke-width="2.4">
                <path d="M37 27c0 3-10 4-10 7.6S37 38 37 41.6 30 45.5 27.5 45"/>
                <path d="M27 27c0 3 10 4 10 7.6"/>
            </g>
        @endif
    </svg>
    <span class="od-brand__text">
        <span class="od-brand__opes">{{ __('od.brand_opes', [], $odLoc) }}</span>
        <span class="od-brand__product">{{ match ($odProduct) {
            'lab'     => __('od.brand_lab', [], $odLoc),
            'clinic'  => __('od.brand_care', [], $odLoc),
            'admin'   => __('od.brand_admin', [], $odLoc),
            'patient' => __('od.brand_health', [], $odLoc),
            'pro'     => __('od.brand_pro', [], $odLoc),
            default   => __('od.brand_pharmacy', [], $odLoc),
        } }}</span>
        <span class="od-brand__by">{{ __('od.brand_by', [], $odLoc) }}</span>
    </span>
</a>
