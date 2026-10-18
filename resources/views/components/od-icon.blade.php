{{--
    OPES desk domain glyphs (docs/superpowers/specs/2026-10-18-desk-design-system.md).
    Hand-drawn 24×24, stroke currentColor 1.75, round caps — same family as Lucide.
    Usage: <x-od-icon name="rx" class="od-kpi__glyph"/>   Unknown names render nothing.
--}}
@props(['name'])
@php
    $paths = [
        'rx' => '<path d="M7 20V4h5a4 4 0 0 1 0 8H7"/><path d="M11 12l8 8"/><path d="M19 13l-6 7"/>',
        'pill-bottle' => '<rect x="6" y="3" width="12" height="4" rx="1"/><path d="M7 7h10v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7z"/><path d="M7 11h10"/><path d="M7 17h10"/><path d="M12 12.5v3"/><path d="M10.5 14h3"/>',
        'capsule' => '<rect x="2.6" y="8.2" width="18.8" height="7.6" rx="3.8" transform="rotate(-45 12 12)"/><path d="M8.6 8.6l6.8 6.8"/><path d="M6.3 12.7l2.3-2.3"/>',
        'mortar' => '<path d="M3 11h18"/><path d="M4.5 11a7.5 7.5 0 0 0 15 0"/><path d="M9 18.3L8 21h8l-1-2.7"/><path d="M13.5 9l6-6.5"/><circle cx="20" cy="2.8" r="1"/>',
        'test-tube' => '<path d="M9 3h7"/><path d="M14.5 3v13.5a3.5 3.5 0 0 1-7 0V3"/><path d="M7.5 11h7"/><path d="M10 14h2"/>',
        'flask' => '<path d="M9 3h6"/><path d="M10 3v6l-5.6 9.4A1.7 1.7 0 0 0 5.9 21h12.2a1.7 1.7 0 0 0 1.5-2.6L14 9V3"/><path d="M7.2 15h9.6"/><circle cx="11" cy="18" r=".6"/><circle cx="14" cy="17.6" r=".6"/>',
        'microscope' => '<path d="M6 21h12"/><path d="M9 18h6"/><path d="M14 18a5 5 0 0 0 1.5-9.4"/><path d="M8.5 4.5l3-1.7 3.5 6-3 1.7z"/><path d="M10.7 10.8l1.5 2.6"/><path d="M9 13.5h3.5"/><path d="M7.5 3.3l1 1.7"/>',
        'barcode' => '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 7v10"/><path d="M10 7v10"/><path d="M12.5 7v10"/><path d="M14.5 7v10"/><path d="M17 7v10"/>',
        'analyser' => '<rect x="3" y="3" width="18" height="13" rx="2"/><rect x="6" y="6" width="8" height="5" rx="1"/><path d="M17 6.5v.01"/><path d="M17 9.5v.01"/><path d="M7 16v3"/><path d="M17 16v3"/><path d="M4 21h16"/><path d="M6 13.5h8"/>',
        'fridge-thermo' => '<rect x="4" y="2.5" width="11" height="19" rx="2"/><path d="M4 9h11"/><path d="M7 5.5v1.5"/><path d="M7 11.5v3"/><path d="M19 4a1.5 1.5 0 0 1 1.5 1.5v8.2a2.8 2.8 0 1 1-3 0V5.5A1.5 1.5 0 0 1 19 4z"/><path d="M19 9v6"/>',
        'caduceus' => '<path d="M12 3v18"/><path d="M12 3.5a1.3 1.3 0 1 0 0 .01"/><path d="M6 6.5c2 0 4 .8 6 2 2-1.2 4-2 6-2"/><path d="M15 10c0 1.6-6 2.4-6 4.5s6 1.9 6 3.9c0 1.2-1.5 1.8-3 1.8"/><path d="M9 10c0 1.6 6 2.4 6 4.5"/>',
        'droplet' => '<path d="M12 2.8s6.5 7 6.5 11.7a6.5 6.5 0 0 1-13 0C5.5 9.8 12 2.8 12 2.8z"/><path d="M9 14.5a3 3 0 0 0 3 3"/>',
        'scan-doc' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M8.5 13h7"/><path d="M8.5 16.5h4"/><path d="M3 10.5h2"/><path d="M19 10.5h2"/>',
        'counselling' => '<path d="M14 4h5a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1l-2.5 2v-2H14a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><circle cx="7.5" cy="10" r="2.6"/><path d="M2.5 20a5 5 0 0 1 10 0"/>',
    ];
    $svg = $paths[$name] ?? null;
@endphp
@if($svg)
<svg {{ $attributes->merge(['class' => 'od-ico']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $svg !!}</svg>
@endif
