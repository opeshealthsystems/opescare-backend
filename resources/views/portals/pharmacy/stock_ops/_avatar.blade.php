{{-- 36px row avatar (OPES desk contract, .os-av).
     $pack: true  → the pill-bottle pack glyph, tinted by stock state
     $icon        → a Lucide glyph at 20px
     otherwise    → the initials of $name.
     $tone: green | red | amber | blue | teal | rose | grey  ('' / sky → blue). --}}
@php
    $tone = match ($tone ?? '') { '', 'sky' => 'blue', default => $tone };
    $icon = $icon ?? null;
    $pack = $pack ?? false;
    $big  = $big ?? false;
    $ini  = '';
    if (! $icon && ! $pack) {
        $words = preg_split('/[\s\-·]+/u', trim((string) ($name ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (array_slice($words, 0, 2) as $w) {
            $ini .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        $ini = $ini !== '' ? $ini : '—';
    }
@endphp
<span class="os-av os-av--{{ $tone }}{{ $big ? ' os-av--lg' : '' }}" aria-hidden="true">@if($pack)@include('portals.pharmacy.stock_ops._glyph', ['glyph' => 'pack'])@elseif($icon)<i data-lucide="{{ $icon }}"></i>@else{{ $ini }}@endif</span>
