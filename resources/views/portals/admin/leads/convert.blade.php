@extends('layouts.portal')
@section('title', __('leads.conversion.page_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('leads.admin.breadcrumb'))

{{--
    Lead → tenant (LeadAdminController@convertForm / @convert, P-6).
    One form: the facility type, the name (prefilled from the lead), the
    city / region, then EITHER one of a short list of unlinked listings of
    that type in that city to link, OR the street address for a new
    self-reported listing. The manager role follows the type and is shown,
    not chosen. A converted lead shows its tenant and no form.
--}}

@section('head')
<style>
  .lc-lead{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--p-space-3);font-size:.85rem}
  .lc-lead dt{color:var(--p-text-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em}
  .lc-lead dd{margin:0;color:var(--p-text);font-weight:600}
  .lc-candidates{display:flex;flex-direction:column;gap:.5rem;margin-top:var(--p-space-3)}
  .lc-candidate{display:flex;gap:.6rem;align-items:flex-start;padding:.6rem .75rem;border:1px solid var(--f-line,#E2E8F0);border-radius:10px;cursor:pointer}
  .lc-candidate:has(input:checked){border-color:#0F4C81;background:rgba(15,76,129,.06)}
  .lc-candidate input{margin-top:.2rem}
  .lc-candidate small{display:block;color:var(--p-text-muted)}
  .lc-role{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .7rem;border-radius:999px;background:rgba(15,76,129,.1);color:#0F4C81;font-weight:700;font-size:.78rem}
  .lc-role i,.lc-role svg{width:14px;height:14px}
  .lc-link{word-break:break-all;font-family:ui-monospace,monospace;font-size:.8rem}
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="building-2"></i> {{ __('leads.conversion.heading') }}</h1>
        <p class="page-subtitle">{{ __('leads.conversion.description') }}</p>
    </div>
    <a href="{{ route('portals.admin.leads') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('leads.conversion.back') }}</a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if(session('invite_link'))
<div class="alert {{ session('mail_status_ok') ? 'alert-success' : 'alert-warning' }} mb-6">
    <i data-lucide="mail"></i>
    <div>
        <div>{{ session('mail_status') }}</div>
        <div class="mt-2">{{ __('leads.conversion.invite_link_for', ['email' => session('invite_link_for')]) }}</div>
        <div class="lc-link">{{ session('invite_link') }}</div>
    </div>
</div>
@endif

{{-- The lead --}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="user-plus"></i> {{ __('leads.conversion.section_lead') }}</h3></div>
    <div class="panel-body">
        <dl class="lc-lead">
            <div><dt>{{ __('leads.admin.col_organization') }}</dt><dd>{{ $lead->organization_name ?: '—' }}</dd></div>
            <div><dt>{{ __('leads.admin.col_type') }}</dt><dd>{{ $lead->organization_type ? __('leads.org_types.' . $lead->organization_type) : '—' }}</dd></div>
            <div><dt>{{ __('leads.admin.col_contact') }}</dt><dd>{{ $lead->name }}<br><span class="td-muted">{{ $lead->email ?: '—' }}</span>@if($lead->phone)<br><span class="td-muted">{{ $lead->phone }}</span>@endif</dd></div>
            <div><dt>{{ __('leads.admin.col_status') }}</dt><dd>{{ __('leads.statuses.' . $lead->status) }}</dd></div>
        </dl>
        @if($lead->message)<p class="td-muted mt-3" style="font-size:.85rem">{{ $lead->message }}</p>@endif
    </div>
</div>

@if($lead->isConverted())
{{-- Already a tenant: show it, no second conversion. --}}
<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="check-circle-2"></i> {{ __('leads.conversion.converted_title') }}</h3></div>
    <div class="panel-body">
        <p>{{ __('leads.conversion.converted_body', ['date' => $lead->converted_at?->format('d M Y H:i') ?? '—']) }}</p>
        <dl class="lc-lead mt-3">
            <div><dt>{{ __('leads.conversion.converted_tenant') }}</dt><dd>{{ $tenant?->name ?? '—' }}</dd></div>
            <div><dt>{{ __('leads.conversion.label_type') }}</dt><dd>{{ $tenant?->type ? (__('finder_directory.types.' . $tenant->type) !== 'finder_directory.types.' . $tenant->type ? __('finder_directory.types.' . $tenant->type) : $tenant->type) : '—' }}</dd></div>
            <div><dt>{{ __('leads.conversion.converted_id') }}</dt><dd class="lc-link">{{ $lead->converted_facility_id }}</dd></div>
        </dl>
    </div>
</div>
@else

@if(! $lead->email)
<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ __('leads.conversion.error_no_email') }}</div></div>
@endif

{{-- Step 1: type, name, place — GET, refreshes the candidate list --}}
<form method="GET" action="{{ route('portals.admin.leads.convert', $lead) }}" class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="search"></i> {{ __('leads.conversion.section_lookup') }}</h3></div>
    <div class="panel-body">
        <p class="lst-note td-muted" style="font-size:.85rem">{{ __('leads.conversion.lookup_intro') }}</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label form-label-required" for="type">{{ __('leads.conversion.label_type') }}</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="">{{ __('leads.conversion.type_placeholder') }}</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" @selected($type === $t)>{{ __('finder_directory.types.' . $t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="q">{{ __('leads.conversion.label_name') }}</label>
                <input type="text" id="q" name="q" class="form-control" maxlength="150" value="{{ $name }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="city">{{ __('leads.conversion.label_city') }}</label>
                <input type="text" id="city" name="city" class="form-control" maxlength="120" value="{{ $city }}">
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="search"></i> {{ __('leads.conversion.btn_lookup') }}</button>
        </div>
    </div>
</form>

@if($type !== '')
{{-- Step 2: confirm and convert — POST --}}
<form method="POST" action="{{ route('portals.admin.leads.convert.store', $lead) }}" class="panel">
    @csrf
    <input type="hidden" name="facility_type" value="{{ $type }}">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> {{ __('leads.conversion.section_confirm') }}</h3>
    </div>
    <div class="panel-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label form-label-required" for="name">{{ __('leads.conversion.label_name') }}</label>
                <input type="text" id="name" name="name" class="form-control" maxlength="150" required value="{{ old('name', $name) }}">
                @error('name')<div class="form-hint form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label form-label-required" for="city2">{{ __('leads.conversion.label_city') }}</label>
                <input type="text" id="city2" name="city" class="form-control" maxlength="120" required value="{{ old('city', $city) }}">
                @error('city')<div class="form-hint form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="region">{{ __('leads.conversion.label_region') }}</label>
                <input type="text" id="region" name="region" class="form-control" maxlength="120" value="{{ old('region') }}">
                @error('region')<div class="form-hint form-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <h4 class="panel-title mt-5"><i data-lucide="link"></i> {{ __('leads.conversion.section_listing') }}</h4>
        <p class="td-muted" style="font-size:.85rem">{{ __('leads.conversion.listing_intro', ['type' => __('finder_directory.types.' . $type), 'city' => $city !== '' ? $city : '—']) }}</p>

        <div class="lc-candidates">
            <label class="lc-candidate">
                <input type="radio" name="listing_id" value="" @checked(old('listing_id', '') === '')>
                <span><strong>{{ __('leads.conversion.create_new') }}</strong><small>{{ __('leads.conversion.create_new_hint') }}</small></span>
            </label>
            @forelse($candidates as $candidate)
            <label class="lc-candidate">
                <input type="radio" name="listing_id" value="{{ $candidate->id }}" @checked(old('listing_id') === $candidate->id)>
                <span>
                    <strong>{{ $candidate->facility_name }}</strong>
                    <small>{{ $candidate->address }}, {{ $candidate->city }}@if($candidate->region) — {{ $candidate->region }}@endif · {{ $candidate->source_system ?: '—' }} · {{ $candidate->verification_status }}</small>
                </span>
            </label>
            @empty
            <p class="td-muted" style="font-size:.85rem">{{ __('leads.conversion.no_candidates') }}</p>
            @endforelse
        </div>
        @error('listing_id')<div class="form-hint form-error">{{ $message }}</div>@enderror

        <div class="form-group mt-4">
            <label class="form-label" for="address">{{ __('leads.conversion.label_address') }}</label>
            <input type="text" id="address" name="address" class="form-control" maxlength="255" value="{{ old('address') }}">
            <div class="form-hint">{{ __('leads.conversion.hint_address') }}</div>
            @error('address')<div class="form-hint form-error">{{ $message }}</div>@enderror
        </div>

        <h4 class="panel-title mt-5"><i data-lucide="mail"></i> {{ __('leads.conversion.section_invite') }}</h4>
        <p style="font-size:.85rem">
            {{ __('leads.conversion.invite_body', ['email' => $lead->email ?: '—']) }}
            <span class="lc-role"><i data-lucide="shield-check"></i> {{ $roleLabel }}</span>
        </p>
        <p class="td-muted" style="font-size:.8rem">{{ __('leads.conversion.invite_note') }}</p>

        <div class="mt-5">
            <button type="submit" class="btn btn-primary" @disabled(! $lead->email)><i data-lucide="building-2"></i> {{ __('leads.conversion.btn_convert') }}</button>
        </div>
    </div>
</form>
@endif

@endif
@endsection
