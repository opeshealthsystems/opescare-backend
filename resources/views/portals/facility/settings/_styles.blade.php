{{--
    Styles shared by the two "my facility" settings pages
    (portals/pharmacy/settings/index and portals/facility/settings/index).
    Include from @section('head'). Markup is OPES desk (od-*); the page-only
    helpers live in public/css/od-clinic.css.
--}}
@include('portals.pharmacy._supply_styles')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
