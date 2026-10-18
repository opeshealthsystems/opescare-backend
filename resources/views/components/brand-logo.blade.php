{{--
    The OpesCare mark — the ONE logo every web surface shows.

    Source of truth: public/brand/opescare-logo-512.png. The small rasters
    (opescare-mark-64/128/192) are downscales of it; never point a header at
    opescare-favicon.png (1.2 MB) or draw an SVG/Lucide stand-in.

    Props: size (CSS px, default 28), tile (white rounded backing for dark
    surfaces), alt ('' = decorative, the wordmark next to it carries the name).
--}}
@props(['size' => 28, 'tile' => false, 'alt' => ''])
@php
    $px  = (int) $size;
    $src = $px <= 32 ? 64 : ($px <= 64 ? 128 : 192);
@endphp
<img src="{{ asset('brand/opescare-mark-' . $src . '.png') }}"
     alt="{{ $alt }}" width="{{ $px }}" height="{{ $px }}" decoding="async"
     {{ $attributes->merge(['class' => 'brand-logo' . ($tile ? ' brand-logo--tile' : '')]) }}
     style="width:{{ $px }}px;height:{{ $px }}px;object-fit:contain;flex-shrink:0;@if($tile)background:#FFFFFF;border-radius:{{ max(6, intdiv($px, 4)) }}px;padding:{{ max(2, intdiv($px, 10)) }}px;box-sizing:border-box;@endif">
