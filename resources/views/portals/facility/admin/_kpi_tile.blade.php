{{-- One OPES desk KPI tile. $k = [label, value, kpi, tint, icon, url|null, text, sub]. A null url renders a plain tile (no capability, no link). --}}
@php $tag = $k['url'] ? 'a' : 'div'; @endphp
<{{ $tag }} @if($k['url']) href="{{ $k['url'] }}" @endif class="od-kpi od-tint--{{ $k['tint'] }}">
    <span class="od-kpi__icon"><i data-lucide="{{ $k['icon'] }}"></i></span>
    <span class="od-kpi__body">
        <span class="od-kpi__label">{{ $k['label'] }}</span>
        <span class="od-kpi__value stat-card__value {{ $k['text'] ? 'odc-kpi__value--text' : '' }}" data-kpi="{{ $k['kpi'] }}">{{ $k['value'] }}</span>
        @if($k['sub'])<span class="od-kpi__sub">{{ $k['sub'] }}</span>@endif
    </span>
</{{ $tag }}>
