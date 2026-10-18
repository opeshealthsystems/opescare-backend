{{-- Empty state (contract .od-empty). $icon, $text, optional $href + $btn (+ $btnIcon). In a table pass $cols. --}}
@php $inner = isset($cols); @endphp
@if($inner)<tr class="os-empty-row"><td colspan="{{ $cols }}">@endif
<div class="od-empty">
    <span class="od-empty__icon"><i data-lucide="{{ $icon }}"></i></span>
    @isset($title)<div class="od-empty__title">{{ $title }}</div>@endisset
    <p class="od-empty__text">{{ $text }}</p>
    @if(! empty($href))
        <a href="{{ $href }}" class="od-btn od-btn--primary">@isset($btnIcon)<i data-lucide="{{ $btnIcon }}"></i>@endisset {{ $btn }}</a>
    @endif
</div>
@if($inner)</td></tr>@endif
