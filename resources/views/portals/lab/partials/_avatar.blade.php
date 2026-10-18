{{--
    36px initials circle (desk design system, .od-avatar). The tint comes from
    a fixed six-colour rotation hashed on the name: blue, amber, teal, rose,
    sky, green — never purple. Pass `name`; optional `size` ('lg' = 64px).
--}}
@php
    $odName     = trim((string) ($name ?? ''));
    $odParts    = preg_split('/\s+/u', $odName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $odInitials = mb_strtoupper(collect($odParts)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '—';
    $odTints    = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $odTint     = $odTints[crc32(mb_strtolower($odName)) % count($odTints)];
@endphp
<span class="od-avatar od-tint--{{ $odTint }}{{ ($size ?? null) === 'lg' ? ' od-avatar--lg' : '' }}" aria-hidden="true">{{ $odInitials }}</span>
