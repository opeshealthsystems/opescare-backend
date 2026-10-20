{{-- Inlines a small blog-only stylesheet (public/css/{file}) into <head>.

     Audit F9: article pages had 3-4 render-blocking stylesheets. The blog-only
     sheets are small (~11 KB / ~6 KB) and used on blog pages only, so they are
     inlined (style-src allows inline styles; scripts stay nonce-only) and the
     shared finder.css/od-public.css remain the only blocking requests.
     Relative ../ urls are rewritten to absolute asset urls first. --}}
@php
    $bicPath = public_path('css/' . $file);
    $bicCss = is_file($bicPath) ? (string) file_get_contents($bicPath) : '';
    $bicCss = str_replace(['url("../', "url('../", 'url(../'], ['url("' . asset('') , "url('" . asset(''), 'url(' . asset('')], $bicCss);
    // Never let stylesheet content close the element.
    $bicCss = str_ireplace('</style', '<\/style', $bicCss);
@endphp
<style data-inline="{{ $file }}">{!! $bicCss !!}</style>
