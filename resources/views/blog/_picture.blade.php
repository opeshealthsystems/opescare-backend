{{-- Blog image: WebP first, PNG fallback. Params: $src (public-relative png path),
     $alt, $eager (bool), $priority (bool → fetchpriority=high). --}}
@php
    $bpWebp = preg_replace('/\.png$/i', '.webp', $src);
    $bpHasWebp = $bpWebp !== $src && is_file(public_path($bpWebp));
@endphp
<picture>
    @if($bpHasWebp)<source srcset="{{ asset($bpWebp) }}" type="image/webp">@endif
    <img src="{{ asset($src) }}" alt="{{ $alt }}" width="1200" height="630" @if(empty($eager)) loading="lazy" @endif @if(!empty($priority)) fetchpriority="high" @endif decoding="async">
</picture>
