{{-- Lab / imaging desk page rules (public/css/od-lab.css), cache-busted by mtime. --}}
<link rel="stylesheet" href="{{ asset('css/od-lab.css') }}?v={{ is_file(public_path('css/od-lab.css')) ? filemtime(public_path('css/od-lab.css')) : 1 }}">
