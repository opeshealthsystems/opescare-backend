@extends('layouts.docs')
@section('title', __('docs_ui.arch.title'))
@section('meta_description', __('docs_ui.arch.meta'))

@section('content')
<div class="dx-hero">
    <img src="{{ asset('images/health-id/seal.webp') }}" alt="" width="72" height="72" class="dx-hero-seal" decoding="async">
    <div>
        <p class="dx-eyebrow">{{ __('docs_ui.nav.architecture_flows') }}</p>
        <h1>{{ __('docs_ui.arch.title') }}</h1>
        <p class="docs-lead">{{ __('docs_ui.arch.lead', ['host' => parse_url($docsBase, PHP_URL_HOST)]) }}</p>
    </div>
</div>

<h2 id="overview">{{ __('docs_ui.arch.h_overview') }}</h2>
@include('docs.diagrams.overview')

<h2 id="layers">{{ __('docs_ui.arch.h_layers') }}</h2>
<div class="dx-layers">
    @foreach (['clients', 'host', 'gates', 'services', 'data'] as $layer)
        <div class="dx-layer dx-layer-{{ $layer }}">
            <h3>{{ __('docs_ui.arch.layer.'.$layer.'.t') }}</h3>
            <p>{{ __('docs_ui.arch.layer.'.$layer.'.b') }}</p>
        </div>
    @endforeach
</div>

<h2 id="flows">{{ __('docs_ui.arch.h_flows') }}</h2>
<div class="dx-flow-cards">
    @foreach (\App\Support\Docs\DocsDiagrams::FLOWS as $f)
        <a class="dx-flow-card" href="{{ route('docs.flows', $f) }}">
            <i data-lucide="{{ \App\Support\Docs\DocsDiagrams::ICONS[$f] }}" class="dx-ico"></i>
            <strong>{{ __('docs_ui.dg.'.$f.'.title') }}</strong>
            <span>{{ __('docs_ui.dg.'.$f.'.summary') }}</span>
        </a>
    @endforeach
</div>

<h2 id="principles">{{ __('docs_ui.arch.h_principles') }}</h2>
<ul>
    @foreach (['p1', 'p2', 'p3', 'p4', 'p5'] as $k)
        <li>{{ __('docs_ui.arch.'.$k) }}</li>
    @endforeach
</ul>
@endsection
