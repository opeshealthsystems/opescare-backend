{{-- "How this diagram is verified": the checks App\Support\Diagrams\Proof runs on
     this spec, and every card's evidence (route names, classes, methods,
     settings) so a reader can check the flow against the API reference. --}}
@php
    $pfChecks = \App\Support\Diagrams\Proof::all($diagram);
    $pfTotal  = count($pfChecks);
    $pfPassed = count(array_filter($pfChecks, fn ($v) => $v === []));
@endphp
<details class="dg-verify">
    <summary>
        @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16])
        <span>{{ __('flows.verify.title') }}</span>
        <span class="dg-verify-count">{{ __('flows.verify.proven', ['passed' => $pfPassed, 'total' => $pfTotal]) }}</span>
    </summary>
    <p>{{ __('flows.verify.intro') }}</p>
    <ul class="dg-verify-checks">
        @foreach ($pfChecks as $pfName => $pfBad)
            <li @class(['is-ok' => $pfBad === [], 'is-bad' => $pfBad !== []])>
                @include('partials.finder.icon', ['name' => $pfBad === [] ? 'circle-check' : 'circle-x', 'size' => 14])
                {{ __('flows.verify.checks.' . $pfName) }}
            </li>
        @endforeach
    </ul>
    <div class="dg-verify-table" tabindex="0">
        <table>
            <thead><tr><th scope="col">{{ __('flows.verify.node') }}</th><th scope="col">{{ __('flows.verify.evidence') }}</th></tr></thead>
            <tbody>
                @foreach ($dgSpec['nodes'] as $pfId => $pfNode)
                    <tr>
                        <td>{{ implode(' ', $dgL['nodes'][$pfId]['title']) }}</td>
                        <td>@foreach ($pfNode[5] as $pfRef)<code>{{ $pfRef }}</code> @endforeach</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p><a href="{{ route('docs.flows.json') }}#{{ $diagram }}">@include('partials.finder.icon', ['name' => 'braces', 'size' => 14]) {{ __('flows.verify.json') }}</a></p>
</details>
