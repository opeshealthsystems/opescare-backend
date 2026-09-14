<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{ trim((string) config('opescare.support_email')) !== '' ? __('mail.chrome.no_reply', ['email' => trim((string) config('opescare.support_email'))]) : __('mail.chrome.no_reply_plain') }}

{{ __('mail.chrome.copyright', ['year' => date('Y')]) }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
