{{-- Domain glyph. Uses the shared <x-od-icon> when it exists (agent A's
     component); otherwise the same hand-drawn 24×24 pill-bottle / box. --}}
@php $glyph = $glyph ?? 'pack'; @endphp
@if($glyph === 'pack')
<x-od-icon name="pill-bottle" class="os-glyph"/>
@elseif($glyph === 'box')
<svg class="os-glyph" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/><path d="m7.5 5.5 9 5"/></svg>
@endif
