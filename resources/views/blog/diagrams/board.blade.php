{{-- A blog "board" diagram: boxes, groups and arrows from App\Support\Blog\BlogDiagrams::board().
     Presentation attributes are written inline (not only CSS classes) so the same SVG
     renders in dompdf for the PDF download; blog-diagrams.css adds motion on the web. --}}
@php
    $bd   = \App\Support\Blog\BlogDiagrams::board($name);
    $uid  = 'bdg-' . $name;
    $p    = 'blog_diagrams.' . $name . '.';
    $tone = [
        'client' => ['#FFFFFF', '#94A3B8', '#0F172A', '#64748B'],
        'edge'   => ['#0F4C81', '#0F4C81', '#FFFFFF', '#D6E4F2'],
        'gate'   => ['#E8F5EE', '#7CC4A0', '#0B5E3C', '#3F6F57'],
        'svc'    => ['#FFFFFF', '#5EA3DC', '#0F172A', '#64748B'],
        'data'   => ['#0F172A', '#0F172A', '#FFFFFF', '#CBD5E1'],
        'fail'   => ['#FDECEA', '#F5C2BC', '#B42318', '#8A3B33'],
    ];
@endphp
<figure class="dg dg-board" data-dg="{{ $uid }}">
    <div class="dg-scroll" tabindex="0" aria-label="{{ __('blog_diagrams.scroll') }}">
        <svg class="dg-svg" viewBox="0 0 {{ $bd['w'] }} {{ $bd['h'] }}" role="img" aria-labelledby="{{ $uid }}-t {{ $uid }}-d" preserveAspectRatio="xMidYMin meet" font-family="Inter, 'DejaVu Sans', sans-serif">
            <title id="{{ $uid }}-t">{{ __($p.'title') }}</title>
            <desc id="{{ $uid }}-d">{{ __($p.'summary') }}</desc>
            <defs>
                <marker id="{{ $uid }}-a" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" fill="#0F4C81"/></marker>
                <marker id="{{ $uid }}-f" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" fill="#B42318"/></marker>
            </defs>
            @foreach ($bd['groups'] as [$g, $x, $y, $w, $h])
                <g class="dg-group">
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="14" fill="#F5F9FD" stroke="#D6E1EC" stroke-width="1.25" stroke-dasharray="5 5"/>
                    <text x="{{ $x + 14 }}" y="{{ $y + 20 }}" class="dg-group-label" font-size="10.5" font-weight="bold" letter-spacing="1.2" fill="#64748B">{{ mb_strtoupper(__($p.'group.'.$g)) }}</text>
                </g>
            @endforeach
            @foreach ($bd['edges'] as [$d, $step, $kind])
                <g class="dg-edge dg-{{ $kind }}" data-step="{{ $step }}">
                    <path d="{{ $d }}" class="dg-line" pathLength="1" fill="none" stroke="{{ $kind === 'fail' ? '#B42318' : '#0F4C81' }}" stroke-width="1.75"{!! $kind === 'fail' ? ' stroke-dasharray="6 5"' : '' !!} marker-end="url(#{{ $uid }}-{{ $kind === 'fail' ? 'f' : 'a' }})"/>
                    <path d="{{ $d }}" class="dg-pulse" fill="none" stroke="none"/>
                </g>
            @endforeach
            @foreach ($bd['boxes'] as [$key, $x, $y, $w, $h, $t, $step])
                @php [$fill, $stroke, $ink, $sub] = $tone[$t]; @endphp
                <g class="dg-node dg-tone-{{ $t }}" data-step="{{ $step }}">
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="10" fill="{{ $fill }}" stroke="{{ $stroke }}" stroke-width="1.25"/>
                    <text x="{{ $x + $w / 2 }}" y="{{ $y + $h / 2 - 3 }}" text-anchor="middle" class="dg-box-title" font-size="13" font-weight="bold" fill="{{ $ink }}">{{ __($p.'box.'.$key) }}</text>
                    <text x="{{ $x + $w / 2 }}" y="{{ $y + $h / 2 + 14 }}" text-anchor="middle" class="dg-box-sub" font-size="10.5" fill="{{ $sub }}">{{ __($p.'box.'.$key.'_sub') }}</text>
                </g>
            @endforeach
        </svg>
    </div>
</figure>
@if (empty($static))
<ol class="dg-steps" data-dg-steps="{{ $uid }}">
    @for ($i = 1; $i <= $bd['steps']; $i++)
        <li data-step="{{ $i }}" class="dg-step">
            <strong>{{ __($p.'s'.$i) }}</strong>
            <span>{{ __($p.'d'.$i) }}</span>
        </li>
    @endfor
</ol>
@endif
