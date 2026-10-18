{{-- KPI tile (contract .od-kpi): one colour per tile. $tone: blue | green | amber | red | teal. --}}
@php $tag = isset($href) ? 'a' : 'div'; @endphp
<{{ $tag }} class="od-kpi od-kpi--{{ $tone ?? 'blue' }} od-tint--{{ $tone ?? 'blue' }}"@isset($href) href="{{ $href }}"@endisset>
    <span class="od-kpi__icon">@if(($icon ?? '') === 'pack')@include('portals.pharmacy.stock_ops._glyph', ['glyph' => 'pack'])@elseif(($icon ?? '') === 'box')@include('portals.pharmacy.stock_ops._glyph', ['glyph' => 'box'])@else<i data-lucide="{{ $icon }}"></i>@endif</span>
    <span class="od-kpi__body">
        <span class="od-kpi__label">{{ $label }}</span>
        <span class="od-kpi__value">{{ $value }}</span>
        @isset($sub)<span class="od-kpi__sub">{{ $sub }}</span>@endisset
    </span>
</{{ $tag }}>
