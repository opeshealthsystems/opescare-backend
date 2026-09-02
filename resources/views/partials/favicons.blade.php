{{--
    Every icon a browser or OS asks for, in one place.

    Included by all layouts and by the standalone views that render their own
    <head>. Kept as a partial because the set is easy to get half-right in one
    layout and wrong in the next six.

    favicon.ico carries 16/32/48 as embedded PNGs, so the tab icon is a real
    bitmap at each size rather than a browser downscale of one large image.
--}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('brand/favicon-16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/apple-touch-icon.png') }}">
<meta name="theme-color" content="#0F4C81">
