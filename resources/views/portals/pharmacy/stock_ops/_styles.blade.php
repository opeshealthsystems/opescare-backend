{{-- Stock-desk page stylesheet (scoped body.od--pharmacy), cache-busted. --}}
<link rel="stylesheet" href="{{ asset('css/od-stock.css') }}?v={{ @filemtime(public_path('css/od-stock.css')) ?: 1 }}">
