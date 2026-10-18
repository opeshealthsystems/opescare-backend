{{-- Round avatar for a list row: the initials of $name, or a Lucide $icon.
     $tone: '' | green | amber | teal | red | sky | grey (see pharmacy.css .ph-avatar--*). --}}
@php
    $tone = $tone ?? '';
    $icon = $icon ?? null;
    $ini  = '';
    if (! $icon) {
        $words = preg_split('/[\s\-·]+/u', trim((string) ($name ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (array_slice($words, 0, 2) as $w) {
            $ini .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        $ini = $ini !== '' ? $ini : '—';
    }
@endphp
<span class="ph-avatar{{ $tone !== '' ? ' ph-avatar--' . $tone : '' }}" aria-hidden="true">@if($icon)<i data-lucide="{{ $icon }}"></i>@else{{ $ini }}@endif</span>
