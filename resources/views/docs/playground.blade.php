@extends('layouts.docs')
@section('title', 'Interactive Playground')

@section('head')
@include('public.partials._od_pages_css')
<style>
  /* Override content padding for full-height Redoc */
  .docs-content {
    max-width: 100%;
    padding: 0;
    margin-left: var(--docs-sidebar-w);
  }
  .redoc-wrap {
    height: calc(100vh - 56px);
    overflow: auto;
  }
  @media (max-width: 768px) {
    .docs-content { margin-left: 0; }
  }
</style>
@endsection

@section('content')
{{-- Redoc renders the reference itself, so the document had no heading. --}}
<h1 class="odp-sr">OpesCare Connect API — interactive reference</h1>
<div class="redoc-wrap">
    <redoc
        spec-url='{{ asset("openapi.json") }}'
        hide-download-btn="false"
        expand-responses="200,201"
        required-props-first="true"
        sort-props-alphabetically="false"
        theme='{
            "colors": {
                "primary": { "main": "#0F4C81" },
                "success": { "main": "#22C55E" },
                "warning": { "main": "#F59E0B" },
                "error": { "main": "#EF4444" }
            },
            "typography": {
                "fontFamily": "Inter, -apple-system, BlinkMacSystemFont, sans-serif",
                "headings": { "fontWeight": "700" },
                "code": {
                    "fontFamily": "Fira Code, Consolas, monospace",
                    "fontSize": "13px"
                }
            },
            "sidebar": {
                "backgroundColor": "#1E293B",
                "textColor": "#E2E8F0"
            },
            "logo": {
                "gutter": "20px"
            }
        }'
    ></redoc>

    <script src="{{ asset('js/redoc.standalone.js') }}"></script>
</div>
@endsection
