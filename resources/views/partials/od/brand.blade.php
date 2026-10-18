{{-- OPES desk lockup (.od-brand): custom mark + "OPES" + product word + "by OPES
     Health Systems". $odProduct is 'pharmacy' or 'lab'. No emoji, no Lucide. --}}
@php $odLoc = app()->getLocale(); @endphp
<a href="{{ $odHome }}" class="od-brand" aria-label="{{ __('od.brand_home', [], $odLoc) }}">
    <svg class="od-brand__mark" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <g class="od-brand__ring" stroke="currentColor">
            <path d="M54.5 20.5A26 26 0 1 0 57 36" stroke-width="3.5"/>
            <path d="M49 13.5A26 26 0 0 1 53 17.5" stroke-width="3.5"/>
            <circle cx="57.2" cy="28" r="2.2" fill="currentColor" stroke="none"/>
        </g>
        @if($odProduct === 'lab')
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
        <span class="od-brand__product">{{ $odProduct === 'lab' ? __('od.brand_lab', [], $odLoc) : __('od.brand_pharmacy', [], $odLoc) }}</span>
        <span class="od-brand__by">{{ __('od.brand_by', [], $odLoc) }}</span>
    </span>
</a>
