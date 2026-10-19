{{-- Sequence diagram for one flow. Data: App\Support\Docs\DocsDiagrams::sequence(). --}}
@php
    $seq   = \App\Support\Docs\DocsDiagrams::sequence($flow);
    $lanes = $seq['lanes'];
    $msgs  = $seq['msgs'];
    $n     = count($lanes);
    $W     = 960;
    $left  = 130;
    $right = 840;
    $laneX = [];
    foreach ($lanes as $i => $l) { $laneX[$i] = $n > 1 ? (int) round($left + $i * ($right - $left) / ($n - 1)) : 480; }
    $top   = 112;
    $step  = 62;
    $H     = $top + count($msgs) * $step + 10;
    $uid   = 'dg-' . $flow;
    $p     = 'docs_ui.dg.' . $flow . '.';
@endphp
<figure class="dg" data-dg="{{ $uid }}">
    <div class="dg-scroll" tabindex="0" aria-label="{{ __('docs_ui.diagram_scroll') }}">
        <svg class="dg-svg dg-seq" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-labelledby="{{ $uid }}-t {{ $uid }}-d" preserveAspectRatio="xMidYMin meet">
            <title id="{{ $uid }}-t">{{ __($p.'title') }}</title>
            <desc id="{{ $uid }}-d">{{ __($p.'summary') }}</desc>
            <defs>
                <marker id="{{ $uid }}-a" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" class="dg-head"/></marker>
                <marker id="{{ $uid }}-f" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" class="dg-head-fail"/></marker>
            </defs>

            {{-- Lanes: header + lifeline --}}
            @foreach ($lanes as $i => $lane)
                <g class="dg-lane">
                    <line x1="{{ $laneX[$i] }}" y1="70" x2="{{ $laneX[$i] }}" y2="{{ $H - 6 }}" class="dg-lifeline"/>
                    <rect x="{{ $laneX[$i] - 92 }}" y="14" width="184" height="50" rx="10" class="dg-lane-box"/>
                    <text x="{{ $laneX[$i] }}" y="36" text-anchor="middle" class="dg-lane-title">{{ __($p.'lane.'.$lane) }}</text>
                    <text x="{{ $laneX[$i] }}" y="54" text-anchor="middle" class="dg-lane-sub">{{ __($p.'lane.'.$lane.'_sub') }}</text>
                </g>
            @endforeach

            {{-- Messages --}}
            @foreach ($msgs as $k => [$from, $to, $kind])
                @php
                    $y   = $top + $k * $step;
                    $x1  = $laneX[$from];
                    $x2  = $laneX[$to];
                    $num = $k + 1;
                    $mk  = $kind === 'fail' ? $uid.'-f' : $uid.'-a';
                    $self = $from === $to;
                    $flip = $self && $from === $n - 1;
                    if ($self) {
                        $dx = $flip ? -46 : 46;
                        $d  = "M{$x1} " . ($y - 6) . " h{$dx} v24 h" . (-$dx + ($flip ? -4 : 4));
                        $lx = $flip ? $x1 - 56 : $x1 + 56;
                        $anchor = $flip ? 'end' : 'start';
                        $ly = $y + 10;
                    } else {
                        $dir = $x2 > $x1 ? 1 : -1;
                        $d  = 'M' . ($x1 + 4 * $dir) . " {$y} H" . ($x2 - 4 * $dir);
                        $lx = (int) round(($x1 + $x2) / 2);
                        $anchor = 'middle';
                        $ly = $y - 9;
                    }
                @endphp
                <g class="dg-msg dg-{{ $kind }}" data-step="{{ $num }}">
                    <circle cx="34" cy="{{ $self ? $y + 6 : $y }}" r="13" class="dg-num-bg"/>
                    <text x="34" y="{{ ($self ? $y + 6 : $y) + 4 }}" text-anchor="middle" class="dg-num">{{ $num }}</text>
                    <path d="{{ $d }}" class="dg-line" marker-end="url(#{{ $mk }})"/>
                    <path d="{{ $d }}" class="dg-pulse"/>
                    <text x="{{ $lx }}" y="{{ $ly }}" text-anchor="{{ $anchor }}" class="dg-label">{{ __($p.'m'.$num) }}</text>
                </g>
            @endforeach
        </svg>
    </div>
    <figcaption class="dg-legend">
        <span class="dg-key dg-key-req">{{ __('docs_ui.legend.request') }}</span>
        <span class="dg-key dg-key-res">{{ __('docs_ui.legend.response') }}</span>
        <span class="dg-key dg-key-fail">{{ __('docs_ui.legend.refusal') }}</span>
    </figcaption>
</figure>

<ol class="dg-steps" data-dg-steps="{{ $uid }}">
    @foreach ($msgs as $k => $m)
        <li data-step="{{ $k + 1 }}" class="dg-step dg-step-{{ $m[2] }}">
            <strong>{{ __($p.'m'.($k + 1)) }}</strong>
            <span>{{ __($p.'d'.($k + 1)) }}</span>
        </li>
    @endforeach
</ol>
