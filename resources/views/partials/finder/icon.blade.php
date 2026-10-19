{{--
    Inline Lucide icon — the ONLY icon mechanism for Finder pages (no emoji,
    no icon font, no external JS). Path data is hand-copied from Lucide
    (ISC license), 24x24 grid, stroke-based.

    Usage:
        @include('partials.finder.icon', ['name' => 'search', 'size' => 20])

    Params:
        name  (required)  icon key from the map below
        size  (optional)  rendered px size, default 20
        class (optional)  extra classes appended to .f-icon
        label (optional)  accessible label; without it the icon is decorative
                          (aria-hidden). Unknown names render nothing.
--}}
@php
    $fIcons = \App\Support\Icons\Lucide::ICONS;
    $fIconSize  = (int) ($size ?? 20);
    $fIconPaths = $fIcons[$name ?? ''] ?? null;
@endphp
@if ($fIconPaths !== null)
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $fIconSize }}" height="{{ $fIconSize }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="f-icon{{ isset($class) ? ' ' . $class : '' }}"@if (!empty($label)) role="img" aria-label="{{ $label }}"@else aria-hidden="true" focusable="false"@endif>{!! $fIconPaths !!}</svg>
@endif
