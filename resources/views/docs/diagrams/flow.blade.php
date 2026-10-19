{{-- One flow diagram, drawn from App\Support\Diagrams\Specs (the single source of truth).
     Geometry: App\Support\Diagrams\Layout. Colours: App\Support\Diagrams\Palette.
     Presentation attributes are written inline so the same SVG prints through
     dompdf (App\Support\Blog\DiagramPdf); CSS only adds motion and focus.
     Params: $diagram (id), $static (PDF: no step list, no verification note). --}}
@php
    $dgSpec   = \App\Support\Diagrams\Specs::get($diagram);
    $dgL      = \App\Support\Diagrams\Layout::build($dgSpec);
    $dgUid    = 'dg-' . $diagram;
    $dgKinds  = \App\Support\Diagrams\Palette::KINDS;
    $dgBorder = \App\Support\Diagrams\Palette::BORDER;
    $dgEdges  = \App\Support\Diagrams\Palette::EDGES;
    [$vx, $vy, $vw, $vh] = $dgL['vb'];
    $dgN = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $dgSteps = fn (array $s) => $s === [] ? '' : ' ' . implode(' ', $s) . ' ';
    $dgIcon = function (string $name, float $x, float $y, string $colour) {
        $paths = \App\Support\Icons\Lucide::paths($name) ?? '';
        $paths = str_replace('currentColor', $colour, $paths);
        return '<g transform="translate(' . $x . ' ' . $y . ') scale(0.75)" fill="none" stroke="' . $colour . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</g>';
    };
    $dgStatic = ! empty($static);
@endphp
<figure class="dg dg-flow" data-dg="{{ $dgUid }}">
    <div class="dg-scroll" tabindex="0" aria-label="{{ __('flows.scroll') }}">
        <svg class="dg-svg" viewBox="{{ $dgN($vx) }} {{ $dgN($vy) }} {{ $dgN($vw) }} {{ $dgN($vh) }}" data-w="{{ $dgN($vw) }}" role="img" aria-labelledby="{{ $dgUid }}-t {{ $dgUid }}-d" preserveAspectRatio="xMidYMin meet" font-family="Inter, 'DejaVu Sans', sans-serif">
            <title id="{{ $dgUid }}-t">{{ $dgL['title'] }}</title>
            <desc id="{{ $dgUid }}-d">{{ $dgL['summary'] }}</desc>
            <defs>
                @foreach ($dgEdges as $ek => [$ec])
                    <marker id="{{ $dgUid }}-m-{{ $ek }}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" fill="{{ $ec }}"/></marker>
                @endforeach
            </defs>
            <rect class="dg-canvas" x="{{ $dgN($vx) }}" y="{{ $dgN($vy) }}" width="{{ $dgN($vw) }}" height="{{ $dgN($vh) }}" fill="{{ \App\Support\Diagrams\Palette::CANVAS }}"/>

            @foreach ($dgL['groups'] as $g)
                <g class="dg-group">
                    <rect x="{{ $dgN($g['x']) }}" y="{{ $dgN($g['y']) }}" width="{{ $dgN($g['w']) }}" height="{{ $dgN($g['h']) }}" rx="16" fill="#F8FAFC" stroke="#94A3B8" stroke-width="1" stroke-dasharray="4 4"/>
                    <text x="{{ $dgN($g['lx']) }}" y="{{ $dgN($g['ly']) }}" class="dg-group-label" font-size="10" font-weight="bold" letter-spacing="1.1" fill="#475569">{{ $g['label'] }}</text>
                </g>
            @endforeach

            @foreach ($dgL['edges'] as $e)
                @php [$ec, $ed, $esw] = $dgEdges[$e['kind']]; @endphp
                <g class="dg-edge dg-e-{{ $e['kind'] }}" data-steps="{{ $dgSteps($e['steps']) }}">
                    <path class="dg-line" d="{{ $e['d'] }}" fill="none" stroke="{{ $ec }}" stroke-width="{{ $esw }}"@if ($ed) stroke-dasharray="{{ $ed }}"@endif stroke-linejoin="round" marker-end="url(#{{ $dgUid }}-m-{{ $e['kind'] }})" pathLength="1"/>
                    <path class="dg-pulse" d="{{ $e['d'] }}" fill="none" stroke="none"/>
                </g>
            @endforeach

            @foreach ($dgL['nodes'] as $n)
                @php
                    [$fill, $stroke, $ink, $sub] = $dgKinds[$n['kind']];
                    $bs = $dgBorder[$n['kind']];
                    $rx = $bs === 'pill' ? 24 : 10;
                    $tl = count($n['title']);
                    $sl = count($n['sub']);
                    $block = 15 * $tl + ($sl ? 4 + 13 * $sl : 0);
                    $ty = $n['y'] + ($n['h'] - $block) / 2 + 11.5;
                    $tx = $n['x'] + 52;
                    $bx = $n['x'] + 12;
                    $by = $n['y'] + ($n['h'] - 30) / 2;
                @endphp
                <g class="dg-node dg-k-{{ $n['kind'] }}" data-steps="{{ $dgSteps($n['steps']) }}" data-node="{{ $n['id'] }}">
                    <rect class="dg-card" x="{{ $dgN($n['x']) }}" y="{{ $dgN($n['y']) }}" width="{{ $dgN($n['w']) }}" height="{{ $dgN($n['h']) }}" rx="{{ $rx }}" fill="{{ $fill }}" stroke="{{ $stroke }}" stroke-width="{{ $bs === 'dotted' ? '1.75' : '1.5' }}"@if ($bs === 'dashed') stroke-dasharray="6 4"@elseif ($bs === 'dotted') stroke-dasharray="2 3"@endif/>
                    @if ($bs === 'double')
                        <rect x="{{ $dgN($n['x'] + 3.5) }}" y="{{ $dgN($n['y'] + 3.5) }}" width="{{ $dgN($n['w'] - 7) }}" height="{{ $dgN($n['h'] - 7) }}" rx="7" fill="none" stroke="{{ $stroke }}" stroke-width="1"/>
                    @endif
                    <rect x="{{ $dgN($bx) }}" y="{{ $dgN($by) }}" width="30" height="30" rx="8" fill="#FFFFFF" stroke="{{ $stroke }}" stroke-width="1"/>
                    {!! $dgIcon($n['icon'], $bx + 6, $by + 6, $stroke) !!}
                    @foreach ($n['title'] as $li => $line)
                        <text x="{{ $dgN($tx) }}" y="{{ $dgN($ty + 15 * $li) }}" class="dg-box-title" font-size="12.5" font-weight="bold" fill="{{ $ink }}">{{ $line }}</text>
                    @endforeach
                    @foreach ($n['sub'] as $li => $line)
                        <text x="{{ $dgN($tx) }}" y="{{ $dgN($ty + 15 * $tl + 3 + 13 * $li) }}" class="dg-box-sub" font-size="10.5" fill="{{ $sub }}">{{ $line }}</text>
                    @endforeach
                </g>
            @endforeach

            @foreach ($dgL['edges'] as $e)
                @if ($e['lbox'])
                    @php $ec = $dgEdges[$e['kind']][0]; $lb = $e['lbox']; @endphp
                    <g class="dg-elabel dg-e-{{ $e['kind'] }}" data-steps="{{ $dgSteps($e['steps']) }}">
                        <rect x="{{ $dgN($lb['x']) }}" y="{{ $dgN($lb['y']) }}" width="{{ $dgN($lb['w']) }}" height="{{ $dgN($lb['h']) }}" rx="9" fill="#FFFFFF" stroke="{{ $ec }}" stroke-width="1"/>
                        <text x="{{ $dgN($lb['x'] + $lb['w'] / 2) }}" y="{{ $dgN($lb['y'] + 12.5) }}" text-anchor="middle" class="dg-elabel-text" font-size="10.5" font-weight="bold" fill="{{ $ec }}">{{ $e['label'] }}</text>
                    </g>
                @endif
            @endforeach

            {{-- Legend: kinds by fill + border + icon, edges by colour + dash. --}}
            <g class="dg-legend-svg">
                <line x1="{{ $dgN($vx + 16) }}" y1="{{ $dgN($dgL['legend_top']) }}" x2="{{ $dgN($vx + $vw - 16) }}" y2="{{ $dgN($dgL['legend_top']) }}" stroke="#E2E8F0" stroke-width="1"/>
                @foreach ($dgL['legend'] as $it)
                    @if ($it['type'] === 'node')
                        @php [$lf, $ls] = $dgKinds[$it['kind']]; $lbs = $dgBorder[$it['kind']]; @endphp
                        <rect x="{{ $dgN($it['x']) }}" y="{{ $dgN($it['y']) }}" width="30" height="16" rx="{{ $lbs === 'pill' ? 8 : 3 }}" fill="{{ $lf }}" stroke="{{ $ls }}" stroke-width="1.25"@if ($lbs === 'dashed') stroke-dasharray="4 3"@elseif ($lbs === 'dotted') stroke-dasharray="2 2"@endif/>
                        @if ($lbs === 'double')
                            <rect x="{{ $dgN($it['x'] + 2.5) }}" y="{{ $dgN($it['y'] + 2.5) }}" width="25" height="11" rx="2" fill="none" stroke="{{ $ls }}" stroke-width="0.75"/>
                        @endif
                    @else
                        @php [$lc, $ld] = $dgEdges[$it['kind']]; @endphp
                        <path d="M{{ $dgN($it['x']) }} {{ $dgN($it['y'] + 8) }} H{{ $dgN($it['x'] + 28) }}" fill="none" stroke="{{ $lc }}" stroke-width="1.75"@if ($ld) stroke-dasharray="{{ $ld }}"@endif marker-end="url(#{{ $dgUid }}-m-{{ $it['kind'] }})"/>
                    @endif
                    <text x="{{ $dgN($it['x'] + 38) }}" y="{{ $dgN($it['y'] + 12) }}" class="dg-legend-text" font-size="11" fill="#334155">{{ $it['text'] }}</text>
                @endforeach
            </g>
        </svg>
    </div>
</figure>
@if (! $dgStatic)
<ol class="dg-steps" data-dg-steps="{{ $dgUid }}" aria-label="{{ __('flows.steps_title') }}">
    @foreach ($dgL['steps'] as $s)
        <li data-step="{{ $s['n'] }}" class="dg-step dg-step-{{ $s['kind'] }}">
            <strong>{{ $s['title'] }}</strong>
            <span>{{ $s['text'] }}</span>
        </li>
    @endforeach
</ol>
@include('docs.diagrams.proof', ['diagram' => $diagram, 'dgSpec' => $dgSpec, 'dgL' => $dgL])
@endif
