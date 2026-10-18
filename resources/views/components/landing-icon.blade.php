{{--
    Landing-page glyph set — custom 24x24 line icons, stroke 1.75, round caps,
    currentColor. Used only by public/landing.blade.php.
        <x-landing-icon name="medicine" />   <x-landing-icon name="blood" :size="20" />
--}}
@props(['name', 'size' => 24])
<svg {{ $attributes->merge(['class' => 'lx-icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
    @case('medicine')
        <rect x="3.2" y="8.6" width="17.6" height="6.8" rx="3.4" transform="rotate(-38 12 12)"/>
        <path d="M9.9 9.3l4.2 5.4"/>
        <path d="M5.6 14.4l2.4 3"/>
        @break
    @case('blood')
        <path d="M12 3.2c3.2 3.9 6 7.5 6 10.6a6 6 0 0 1-12 0c0-3.1 2.8-6.7 6-10.6z"/>
        <path d="M9.3 14.6a2.8 2.8 0 0 0 2.4 2.4"/>
        @break
    @case('facility')
        <path d="M4 20.5V8.2L12 4l8 4.2v12.3"/>
        <path d="M2.8 20.5h18.4"/>
        <path d="M12 8.5v5M9.5 11h5"/>
        <path d="M10 20.5v-3.6h4v3.6"/>
        @break
    @case('calendar')
        <rect x="3.5" y="5" width="17" height="15.5" rx="3"/>
        <path d="M3.5 9.8h17M8 3v4M16 3v4"/>
        <path d="M9 14.6l2 2 4-4"/>
        @break
    @case('health-id')
        <rect x="2.8" y="5" width="18.4" height="14" rx="2.8"/>
        <circle cx="8.6" cy="11" r="2.2"/>
        <path d="M5.6 16.2c.6-1.6 1.7-2.4 3-2.4s2.4.8 3 2.4"/>
        <path d="M14.4 10h3.8M14.4 13.2h2.6"/>
        @break
    @case('qr')
        <rect x="3.5" y="3.5" width="6.5" height="6.5" rx="1.4"/>
        <rect x="14" y="3.5" width="6.5" height="6.5" rx="1.4"/>
        <rect x="3.5" y="14" width="6.5" height="6.5" rx="1.4"/>
        <path d="M14 14h2.6v2.6H14zM18.6 18.6h1.9v1.9M14 20.5h1.4M20.5 14v1.6"/>
        @break
    @case('flask')
        <path d="M9.4 3.5h5.2M10.2 3.5v5.6L4.8 18.3a1.6 1.6 0 0 0 1.4 2.2h11.6a1.6 1.6 0 0 0 1.4-2.2l-5.4-9.2V3.5"/>
        <path d="M7.4 14.3h9.2"/>
        @break
    @case('imaging')
        <rect x="3.5" y="3.5" width="17" height="17" rx="3"/>
        <path d="M12 7v10M8.6 9.2c1.1.6 2.2.8 3.4.8s2.3-.2 3.4-.8M8.2 12.4c1.2.6 2.5.9 3.8.9s2.6-.3 3.8-.9M8.8 15.6c1 .5 2 .7 3.2.7s2.2-.2 3.2-.7"/>
        @break
    @case('shield')
        <path d="M12 3l7.5 3v5.6c0 4.6-3.2 8.3-7.5 9.4-4.3-1.1-7.5-4.8-7.5-9.4V6z"/>
        <path d="M8.8 12.2l2.2 2.2 4.2-4.4"/>
        @break
    @case('api')
        <path d="M8.5 7.5L4 12l4.5 4.5M15.5 7.5L20 12l-4.5 4.5"/>
        <path d="M13.2 5.5l-2.4 13"/>
        @break
    @case('payment')
        <rect x="6.5" y="2.8" width="11" height="18.4" rx="2.6"/>
        <path d="M10.6 18h2.8"/>
        <path d="M9.6 10.4l1.8 1.8 3.2-3.4"/>
        @break
    @case('consent')
        <path d="M7.5 11V7.5a4.5 4.5 0 0 1 9 0V11"/>
        <rect x="4.5" y="11" width="15" height="9.5" rx="2.6"/>
        <path d="M12 14.6v2.4"/>
        @break
    @case('log')
        <path d="M6.5 3.5h8l3.5 3.5v13.5h-11.5z"/>
        <path d="M14.5 3.5V7H18M9.5 11.5h5M9.5 14.5h5M9.5 17.5h3"/>
        @break
    @case('key')
        <circle cx="8" cy="15.5" r="4"/>
        <path d="M10.9 12.6l8.6-8.6M16.5 7l2.5 2.5M14 9.5l2 2"/>
        @break
    @case('alert')
        <path d="M12 3.5l9 15.5H3z"/>
        <path d="M12 10v4M12 16.8v.1"/>
        @break
    @case('search')
        <circle cx="10.8" cy="10.8" r="6.3"/>
        <path d="M15.4 15.4l5.1 5.1"/>
        @break
    @case('arrow-right')
        <path d="M4.5 12h15M13.5 6l6 6-6 6"/>
        @break
    @case('check')
        <path d="M5 12.5l4.5 4.5L19 7.5"/>
        @break
    @case('menu')
        <path d="M4 7h16M4 12h16M4 17h16"/>
        @break
    @case('globe')
        <circle cx="12" cy="12" r="8.5"/>
        <path d="M3.5 12h17M12 3.5c2.4 2.6 3.6 5.4 3.6 8.5s-1.2 5.9-3.6 8.5c-2.4-2.6-3.6-5.4-3.6-8.5s1.2-5.9 3.6-8.5z"/>
        @break
    @default
        <circle cx="12" cy="12" r="8"/>
@endswitch
</svg>
