{{-- Platform architecture overview. Data: App\Support\Docs\DocsDiagrams::overview(). --}}
@php
    $ov = \App\Support\Docs\DocsDiagrams::overview();
    $p  = 'docs_ui.dg.overview.';
@endphp
<figure class="dg dg-overview" data-dg="dg-overview">
    <div class="dg-scroll" tabindex="0" aria-label="{{ __('docs_ui.diagram_scroll') }}">
        <svg class="dg-svg" viewBox="0 0 960 560" role="img" aria-labelledby="dg-overview-t dg-overview-d" preserveAspectRatio="xMidYMin meet">
            <title id="dg-overview-t">{{ __($p.'title') }}</title>
            <desc id="dg-overview-d">{{ __($p.'summary') }}</desc>
            <defs>
                <marker id="dg-overview-a" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" class="dg-head"/></marker>
            </defs>
            @foreach ($ov['groups'] as [$g, $x, $y, $w, $h])
                <g class="dg-group dg-group-{{ $g }}">
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="14"/>
                    <text x="{{ $x + 12 }}" y="{{ $y + 18 }}" class="dg-group-label">{{ __($p.'group.'.$g) }}</text>
                </g>
            @endforeach
            @foreach ($ov['edges'] as $d)
                @php $isBus = str_starts_with($d, 'M720 67 V'); @endphp
                <path d="{{ $d }}" class="dg-line"{!! $isBus ? '' : ' marker-end="url(#dg-overview-a)"' !!}/>
                <path d="{{ $d }}" class="dg-pulse"/>
            @endforeach
            @foreach ($ov['boxes'] as [$key, $x, $y, $w, $h, $tone])
                <g class="dg-box dg-tone-{{ $tone }}">
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="10"/>
                    <text x="{{ $x + $w / 2 }}" y="{{ $y + $h / 2 - 3 }}" text-anchor="middle" class="dg-box-title">{{ __($p.'box.'.$key) }}</text>
                    <text x="{{ $x + $w / 2 }}" y="{{ $y + $h / 2 + 14 }}" text-anchor="middle" class="dg-box-sub">{{ __($p.'box.'.$key.'_sub') }}</text>
                </g>
            @endforeach
        </svg>
    </div>
</figure>
