@extends('layouts.portal')
@section('title', __('developer_ext.qs_page_title'))
@php
    $l = app()->getLocale();
    $missingFhirScopes = $client ? array_values(array_diff($fhirScopes, $clientScopes)) : $fhirScopes;
    $clientId = $client?->client_id ?? 'YOUR_CLIENT_ID';

    $curlToken = "curl -X POST {$tokenUrl} \\\n"
        . "  -H 'Content-Type: application/json' \\\n"
        . "  -d '{\"grant_type\":\"client_credentials\",\"client_id\":\"{$clientId}\",\"client_secret\":\"YOUR_CLIENT_SECRET\"}'";

    $curlMetadata = "curl {$fhirBase}/metadata \\\n  -H 'Accept: application/fhir+json'";

    $curlObservation = "curl -X POST {$fhirBase}/Observation \\\n"
        . "  -H 'Authorization: Bearer ACCESS_TOKEN' \\\n"
        . "  -H 'Content-Type: application/fhir+json' \\\n"
        . "  -H \"Idempotency-Key: \$(uuidgen)\" \\\n"
        . "  -d '{\n"
        . "    \"resourceType\": \"Observation\",\n"
        . "    \"status\": \"final\",\n"
        . "    \"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\", \"code\": \"vital-signs\"}]}],\n"
        . "    \"code\": {\"coding\": [{\"system\": \"http://loinc.org\", \"code\": \"8867-4\", \"display\": \"Heart rate\"}]},\n"
        . "    \"subject\": {\"identifier\": {\"system\": \"https://opescare.com/fhir/health-id\", \"value\": \"CM-HID-XXXX-XXXX-XXXX\"}},\n"
        . "    \"effectiveDateTime\": \"" . now()->toIso8601String() . "\",\n"
        . "    \"valueQuantity\": {\"value\": 72, \"unit\": \"/min\", \"system\": \"http://unitsofmeasure.org\", \"code\": \"/min\"}\n"
        . "  }'";
@endphp

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pro.css') }}?v={{ @filemtime(public_path('css/od-pro.css')) ?: '1' }}">
@endsection

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('developer_ext.qs_page_title') }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('developer_ext.qs_page_title') }}</h2>
        <p class="portal-page-subtitle">{{ __('developer_ext.qs_subtitle') }}</p>
        <div class="page-head__spacer"></div>
        @if($clients->count() > 1)
        <form method="GET" class="filter-bar filter-bar--flush">
            <select name="app" class="filter-select" aria-label="{{ __('developer_ext.qs_lbl_app') }}" data-autosubmit>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ $client && $client->id === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    @if(!$client)
        <div class="panel mb-6">
            <div class="empty-state">
                <i data-lucide="app-window" class="empty-state-icon"></i>
                <p>{{ __('developer_ext.qs_no_apps') }}</p>
                <a href="{{ route('portals.developer.apps.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('developer_ext.qs_btn_create_app') }}</a>
            </div>
        </div>
    @else
        <div class="panel mb-6">
            <div class="panel-body">
                <table class="kv-table">
                    <tr><th>{{ __('developer_ext.qs_lbl_app') }}</th><td><a href="{{ route('portals.developer.apps.show', $client->id) }}">{{ $client->name }}</a></td></tr>
                    <tr><th>{{ __('public.developer_portal.lbl_client_id', [], $l) }}</th><td><span class="code-token" data-copy="{{ $client->client_id }}">{{ $client->client_id }}</span></td></tr>
                    <tr><th>{{ __('developer_ext.qs_lbl_environment') }}</th><td>@enum($client->environment ?? 'sandbox', 'environment')</td></tr>
                    <tr><th>{{ __('developer_ext.qs_lbl_your_scopes') }}</th><td>@forelse($clientScopes as $s)<span class="code-token">{{ $s }}</span> @empty —@endforelse</td></tr>
                    <tr><th>{{ __('developer_ext.qs_lbl_fhir_scopes') }}</th><td>@foreach($fhirScopes as $s)<span class="code-token">{{ $s }}</span> @endforeach</td></tr>
                </table>
            </div>
        </div>

        @if(!$client->facility_id)
        <div class="alert alert-warning mb-6">
            <i data-lucide="building-2"></i>
            <div><strong>{{ __('developer_ext.qs_warn_facility_title') }}</strong><br>{{ __('developer_ext.qs_warn_facility_body') }}</div>
        </div>
        @endif

        @if(!empty($missingFhirScopes))
        <div class="alert alert-warning mb-6">
            <i data-lucide="key-round"></i>
            <div><strong>{{ __('developer_ext.qs_warn_scopes_title') }}</strong><br>{{ __('developer_ext.qs_warn_scopes_body') }}
                <div class="mt-2">@foreach($missingFhirScopes as $s)<span class="code-token">{{ $s }}</span> @endforeach</div>
            </div>
        </div>
        @endif
    @endif

    {{-- Step 1 --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> {{ __('developer_ext.qs_step1_title') }}</h3></div>
        <div class="panel-body">
            <p class="mb-6">{{ __('developer_ext.qs_step1_body') }}</p>
            <pre class="code-block mono ods-d07753c">{{ $curlToken }}</pre>
            <button type="button" class="btn btn-secondary btn-sm mt-2" data-copy-text="{{ $curlToken }}"><i data-lucide="copy"></i> {{ __('developer_ext.qs_copy') }}</button>
        </div>
    </div>

    {{-- Step 2 --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="file-search"></i> {{ __('developer_ext.qs_step2_title') }}</h3></div>
        <div class="panel-body">
            <p class="mb-6">{{ __('developer_ext.qs_step2_body') }}</p>
            <pre class="code-block mono ods-d07753c">{{ $curlMetadata }}</pre>
            <button type="button" class="btn btn-secondary btn-sm mt-2" data-copy-text="{{ $curlMetadata }}"><i data-lucide="copy"></i> {{ __('developer_ext.qs_copy') }}</button>
        </div>
    </div>

    {{-- Step 3 --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="upload"></i> {{ __('developer_ext.qs_step3_title') }}</h3></div>
        <div class="panel-body">
            <p class="mb-6">{{ __('developer_ext.qs_step3_body') }}</p>
            <pre class="code-block mono ods-d07753c">{{ $curlObservation }}</pre>
            <button type="button" class="btn btn-secondary btn-sm mt-2" data-copy-text="{{ $curlObservation }}"><i data-lucide="copy"></i> {{ __('developer_ext.qs_copy') }}</button>
            <p class="td-muted mt-2"><a href="{{ route('portals.developer.activity') }}"><i data-lucide="activity"></i> {{ __('developer_ext.act_page_title') }}</a></p>
        </div>
    </div>

    {{-- Reference --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="book-open"></i> {{ __('developer_ext.qs_docs_title') }}</h3></div>
        <div class="panel-body">
            <ul class="link-list">
                <li><a href="{{ route('docs.index') }}" target="_blank" rel="noopener"><i data-lucide="book"></i> {{ __('developer_ext.qs_lnk_docs') }}</a></li>
                <li><a href="{{ route('docs.authentication') }}" target="_blank" rel="noopener"><i data-lucide="lock"></i> {{ __('developer_ext.qs_lnk_auth') }}</a></li>
                <li><a href="{{ url('/openapi.json') }}" target="_blank" rel="noopener"><i data-lucide="file-json"></i> {{ __('developer_ext.qs_lnk_openapi') }}</a></li>
                <li><a href="{{ url('/opescare.postman_collection.json') }}" target="_blank" rel="noopener"><i data-lucide="send"></i> {{ __('developer_ext.qs_lnk_postman') }}</a></li>
                <li><a href="{{ route('docs.sdk') }}" target="_blank" rel="noopener"><i data-lucide="package"></i> {{ __('developer_ext.qs_lnk_sdk') }}</a></li>
                <li><a href="{{ route('docs.webhooks') }}" target="_blank" rel="noopener"><i data-lucide="webhook"></i> {{ __('developer_ext.qs_lnk_webhooks') }}</a></li>
                <li><a href="{{ route('docs.errors') }}" target="_blank" rel="noopener"><i data-lucide="alert-octagon"></i> {{ __('developer_ext.qs_lnk_errors') }}</a></li>
            </ul>
        </div>
    </div>

@endsection
@section('scripts')
<script @cspNonce>
document.addEventListener('click', function(e){
    var b = e.target.closest('[data-copy-text], .code-token[data-copy]');
    if(!b) return;
    var text = b.getAttribute('data-copy-text') || b.getAttribute('data-copy');
    navigator.clipboard && navigator.clipboard.writeText(text);
    var prev = b.innerHTML; b.textContent = @json(__('developer_ext.copied')); setTimeout(function(){ b.innerHTML = prev; if (window.lucide) lucide.createIcons(); }, 1200);
});
</script>
@endsection
