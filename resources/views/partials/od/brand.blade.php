{{-- OPES desk lockup (.od-brand): OpesCare mark (x-brand-logo) + "OPES" + product word + "by OPES
     Health Systems". $odProduct is the area: pharmacy | lab | clinic | admin | patient | pro. No emoji, no Lucide. --}}
@php $odLoc = app()->getLocale(); @endphp
<a href="{{ $odHome }}" class="od-brand" aria-label="{{ __('od.brand_home', [], $odLoc) }}">
    <x-brand-logo :size="64" tile class="od-brand__mark" />
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
