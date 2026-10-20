@extends('layouts.portal')
@section('title', __('leads.conversion.page_title'))
@include('portals.admin.control_center._sidebar')

{{--
    Lead → tenant (LeadAdminController@convertForm / @convert, P-6).
    One form: the facility type, the name (prefilled from the lead), the
    city / region, then EITHER one of a short list of unlinked listings of
    that type in that city to link, OR the street address for a new
    self-reported listing. The manager role follows the type and is shown,
    not chosen. A converted lead shows its tenant and no form.
--}}

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('leads.conversion.heading') }}</h1>
        <p class="od-page__sub">{{ __('leads.conversion.description') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.leads') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('leads.conversion.back') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-gc-flash oda-gc-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if(session('invite_link'))
<div class="oda-gc-flash {{ session('mail_status_ok') ? 'oda-gc-flash--ok' : 'oda-gc-flash--warn' }}">
    <i data-lucide="mail"></i>
    <div>
        <div>{{ session('mail_status') }}</div>
        <div>{{ __('leads.conversion.invite_link_for', ['email' => session('invite_link_for')]) }}</div>
        <div class="oda-mono oda-gc-break">{{ session('invite_link') }}</div>
    </div>
</div>
@endif

<div class="od-stack">
{{-- The lead --}}
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="user-plus"></i> {{ __('leads.conversion.section_lead') }}</h2></div>
    <div class="od-card__body">
        <dl class="oda-gc-facts">
            <div><dt>{{ __('leads.admin.col_organization') }}</dt><dd>{{ $lead->organization_name ?: '—' }}</dd></div>
            <div><dt>{{ __('leads.admin.col_type') }}</dt><dd>{{ $lead->organization_type ? __('leads.org_types.' . $lead->organization_type) : '—' }}</dd></div>
            <div><dt>{{ __('leads.admin.col_contact') }}</dt><dd>{{ $lead->name }}<br><span class="oda-gc-muted">{{ $lead->email ?: '—' }}</span>@if($lead->phone)<br><span class="oda-gc-muted">{{ $lead->phone }}</span>@endif</dd></div>
            <div><dt>{{ __('leads.admin.col_status') }}</dt><dd>{{ __('leads.statuses.' . $lead->status) }}</dd></div>
        </dl>
        @if($lead->message)<p class="oda-gc-note">{{ $lead->message }}</p>@endif
    </div>
</section>

@if($lead->isConverted())
{{-- Already a tenant: show it, no second conversion. --}}
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="check-circle-2"></i> {{ __('leads.conversion.converted_title') }}</h2></div>
    <div class="od-card__body od-stack">
        <p>{{ __('leads.conversion.converted_body', ['date' => $lead->converted_at?->translatedFormat('d M Y H:i') ?? '—']) }}</p>
        <dl class="oda-gc-facts">
            <div><dt>{{ __('leads.conversion.converted_tenant') }}</dt><dd>{{ $tenant?->name ?? '—' }}</dd></div>
            <div><dt>{{ __('leads.conversion.label_type') }}</dt><dd>{{ $tenant?->type ? \App\Support\FacilityTypeLabel::label($tenant->type) : '—' }}</dd></div>
            <div><dt>{{ __('leads.conversion.converted_id') }}</dt><dd class="oda-mono oda-gc-break">{{ $lead->converted_facility_id }}</dd></div>
        </dl>
    </div>
</section>
@else

@if(! $lead->email)
<div class="oda-gc-flash oda-gc-flash--err"><i data-lucide="alert-circle"></i><div>{{ __('leads.conversion.error_no_email') }}</div></div>
@endif

{{-- Step 1: type, name, place — GET, refreshes the candidate list --}}
<form method="GET" action="{{ route('portals.admin.leads.convert', $lead) }}" class="od-card">
    <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="search"></i> {{ __('leads.conversion.section_lookup') }}</h2></div>
    <div class="od-card__body od-stack">
        <p class="oda-gc-note">{{ __('leads.conversion.lookup_intro') }}</p>
        <div class="oda-form oda-form--3">
            <label class="oda-label" for="type">
                <span class="oda-label__text">{{ __('leads.conversion.label_type') }} *</span>
                <select id="type" name="type" class="od-select" required>
                    <option value="">{{ __('leads.conversion.type_placeholder') }}</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" @selected($type === $t)>{{ __('finder_directory.types.' . $t) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oda-label" for="q">
                <span class="oda-label__text">{{ __('leads.conversion.label_name') }}</span>
                <input type="text" id="q" name="q" class="od-input" maxlength="150" value="{{ $name }}">
            </label>
            <label class="oda-label" for="city">
                <span class="oda-label__text">{{ __('leads.conversion.label_city') }}</span>
                <input type="text" id="city" name="city" class="od-input" maxlength="120" value="{{ $city }}">
            </label>
            <div class="oda-form__actions">
                <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="search"></i> {{ __('leads.conversion.btn_lookup') }}</button>
            </div>
        </div>
    </div>
</form>

@if($type !== '')
{{-- Step 2: confirm and convert — POST --}}
<form method="POST" action="{{ route('portals.admin.leads.convert.store', $lead) }}" class="od-card">
    @csrf
    <input type="hidden" name="facility_type" value="{{ $type }}">
    <div class="od-card__head">
        <h2 class="od-card__title"><i data-lucide="building-2"></i> {{ __('leads.conversion.section_confirm') }}</h2>
    </div>
    <div class="od-card__body od-stack">
        <div class="oda-form oda-form--3">
            <label class="oda-label" for="name">
                <span class="oda-label__text">{{ __('leads.conversion.label_name') }} *</span>
                <input type="text" id="name" name="name" class="od-input" maxlength="150" required value="{{ old('name', $name) }}">
                @error('name')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label" for="city2">
                <span class="oda-label__text">{{ __('leads.conversion.label_city') }} *</span>
                <input type="text" id="city2" name="city" class="od-input" maxlength="120" required value="{{ old('city', $city) }}">
                @error('city')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
            <label class="oda-label" for="region">
                <span class="oda-label__text">{{ __('leads.conversion.label_region') }}</span>
                <input type="text" id="region" name="region" class="od-input" maxlength="120" value="{{ old('region') }}">
                @error('region')<span class="oda-error">{{ $message }}</span>@enderror
            </label>
        </div>

        <h3 class="od-card__title"><i data-lucide="link"></i> {{ __('leads.conversion.section_listing') }}</h3>
        <p class="oda-gc-note">{{ __('leads.conversion.listing_intro', ['type' => __('finder_directory.types.' . $type), 'city' => $city !== '' ? $city : '—']) }}</p>

        <div class="oda-gc-choices">
            <label class="oda-gc-choice">
                <input type="radio" name="listing_id" value="" @checked(old('listing_id', '') === '')>
                <span><strong>{{ __('leads.conversion.create_new') }}</strong><small>{{ __('leads.conversion.create_new_hint') }}</small></span>
            </label>
            @forelse($candidates as $candidate)
            <label class="oda-gc-choice">
                <input type="radio" name="listing_id" value="{{ $candidate->id }}" @checked(old('listing_id') === $candidate->id)>
                <span>
                    <strong>{{ $candidate->facility_name }}</strong>
                    <small>{{ $candidate->address }}, {{ $candidate->city }}@if($candidate->region) — {{ $candidate->region }}@endif · {{ $candidate->source_system ?: '—' }} · {{ $candidate->verification_status }}</small>
                </span>
            </label>
            @empty
            <p class="oda-gc-note">{{ __('leads.conversion.no_candidates') }}</p>
            @endforelse
        </div>
        @error('listing_id')<span class="oda-error">{{ $message }}</span>@enderror

        <label class="oda-label" for="address">
            <span class="oda-label__text">{{ __('leads.conversion.label_address') }}</span>
            <input type="text" id="address" name="address" class="od-input" maxlength="255" value="{{ old('address') }}">
            <span class="oda-help">{{ __('leads.conversion.hint_address') }}</span>
            @error('address')<span class="oda-error">{{ $message }}</span>@enderror
        </label>

        <h3 class="od-card__title"><i data-lucide="mail"></i> {{ __('leads.conversion.section_invite') }}</h3>
        <p>
            {{ __('leads.conversion.invite_body', ['email' => $lead->email ?: '—']) }}
            <span class="od-pill od-pill--normal"><i data-lucide="shield-check"></i> {{ $roleLabel }}</span>
        </p>
        <p class="oda-help">{{ __('leads.conversion.invite_note') }}</p>

        <div class="oda-form__actions">
            <button type="submit" class="od-btn od-btn--primary" @disabled(! $lead->email)><i data-lucide="building-2"></i> {{ __('leads.conversion.btn_convert') }}</button>
        </div>
    </div>
</form>
@endif

@endif
</div>

</div>
@endsection
