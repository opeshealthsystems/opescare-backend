@extends('layouts.docs')
@section('title', __('docs_ui.dg.'.$flow.'.title'))
@section('meta_description', __('docs_ui.dg.'.$flow.'.summary'))

@section('content')
@php
    $flows = \App\Support\Docs\DocsDiagrams::FLOWS;
    $i     = array_search($flow, $flows, true);
    $prev  = $i > 0 ? $flows[$i - 1] : null;
    $next  = $i < count($flows) - 1 ? $flows[$i + 1] : null;
@endphp
<div class="dx-hero">
    <span class="dx-hero-icon"><i data-lucide="{{ \App\Support\Docs\DocsDiagrams::ICONS[$flow] }}" class="dx-ico"></i></span>
    <div>
        <p class="dx-eyebrow"><a href="{{ route('docs.architecture') }}">{{ __('docs_ui.nav.architecture_flows') }}</a></p>
        <h1>{{ __('docs_ui.dg.'.$flow.'.title') }}</h1>
        <p class="docs-lead">{{ __('docs_ui.dg.'.$flow.'.lead') }}</p>
    </div>
</div>

<h2 id="sequence">{{ __('docs_ui.flow.h_sequence') }}</h2>
<p class="dx-hint">{{ __('docs_ui.flow.hint') }}</p>
@include('docs.diagrams.sequence', ['flow' => $flow])

<h2 id="guarantees">{{ __('docs_ui.flow.h_guarantees') }}</h2>
<ul>
    @foreach (['g1', 'g2', 'g3'] as $g)
        <li>{{ __('docs_ui.dg.'.$flow.'.'.$g) }}</li>
    @endforeach
</ul>

<h2 id="in-the-code">{{ __('docs_ui.flow.h_code') }}</h2>
<ul class="dx-code-refs">
    @foreach (\App\Support\Docs\DocsDiagrams::CODE[$flow] as $ref)
        <li><code>{{ $ref }}</code></li>
    @endforeach
</ul>

<nav class="dx-pager" aria-label="{{ __('docs_ui.flow.pager') }}">
    @if ($prev)
        <a href="{{ route('docs.flows', $prev) }}" class="dx-pager-prev"><span>{{ __('docs_ui.flow.previous') }}</span>{{ __('docs_ui.dg.'.$prev.'.nav') }}</a>
    @else
        <a href="{{ route('docs.architecture') }}" class="dx-pager-prev"><span>{{ __('docs_ui.flow.previous') }}</span>{{ __('docs_ui.nav.platform_overview') }}</a>
    @endif
    @if ($next)
        <a href="{{ route('docs.flows', $next) }}" class="dx-pager-next"><span>{{ __('docs_ui.flow.next') }}</span>{{ __('docs_ui.dg.'.$next.'.nav') }}</a>
    @endif
</nav>
@endsection
