@extends('layouts.portal')

@section('title', __('public.portal.profile_page_title', [], app()->getLocale()) ?: 'My Profile — OpesCare Patient Portal')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.profile_breadcrumb', [], app()->getLocale()) ?: 'Profile')

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="pf pf--wide">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.portal.profile_page_heading', [], $l) ?: 'My Profile' }}</h1>
            <p class="pf-sub">{{ __('public.portal.profile_page_subtitle', [], $l) ?: 'Manage your contact details, clinical information, and privacy preferences.' }}</p>
        </div>
    </div>

    @if(!$patient)
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.portal.profile_no_profile_h3', [], $l) ?: 'No Patient Profile Found' }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('public.portal.profile_no_profile_p', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
    @else

    <form method="POST" action="{{ route('portals.patient.profile.update') }}">
    @csrf

    <div class="pf-cols">

        {{-- ── LEFT COLUMN ─────────────────────────────────────────────── --}}
        <div class="pf-stack" style="gap:16px;">

            {{-- Contact details --}}
            <div class="f-card">
                <h2 class="pf-card-head">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 18])
                    {{ __('public.portal.panel_contact_details', [], $l) ?: 'Contact Details' }}
                </h2>

                <div class="f-field">
                    <label class="f-label" for="phone_number">{{ __('public.portal.field_phone_number', [], $l) ?: 'Phone Number' }}</label>
                    <input type="text" id="phone_number" name="phone_number" class="f-input" inputmode="tel" autocomplete="tel"
                           value="{{ old('phone_number', $patient->phone_number) }}">
                    @error('phone_number')<p class="f-error">{{ $message }}</p>@enderror
                </div>

                <div class="f-field">
                    <label class="f-label" for="email">{{ __('public.portal.field_email', [], $l) ?: 'Email' }}</label>
                    <input type="email" id="email" name="email" class="f-input" autocomplete="email"
                           value="{{ old('email', $patient->email) }}">
                    @error('email')<p class="f-error">{{ $message }}</p>@enderror
                </div>

                <div class="f-field">
                    <label class="f-label" for="address">{{ __('public.portal.field_address', [], $l) ?: 'Address' }}</label>
                    <textarea id="address" name="address" rows="2" class="f-input">{{ old('address', $patient->address) }}</textarea>
                    @error('address')<p class="f-error">{{ $message }}</p>@enderror
                </div>

                <div class="f-field" style="margin-bottom:0;">
                    <label class="f-label" for="date_of_birth">{{ __('public.portal.field_date_of_birth', [], $l) ?: 'Date of Birth' }}</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="f-input"
                        value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}"
                        max="{{ now()->subDay()->format('Y-m-d') }}">
                    @if($patient->date_of_birth)
                        <p class="f-hint">{{ __('public.portal.lbl_age', [], $l) ?: 'Age' }}: {{ $patient->date_of_birth->diffInYears(now()) }} {{ __('public.portal.lbl_years', [], $l) ?: 'years' }}</p>
                    @endif
                    @error('date_of_birth')<p class="f-error">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Clinical information --}}
            <div class="f-card">
                <h2 class="pf-card-head">
                    @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18])
                    {{ __('public.portal.panel_clinical_info', [], $l) ?: 'Clinical Information' }}
                </h2>

                <div class="f-field">
                    <label class="f-label" for="blood_group">{{ __('public.portal.field_blood_group', [], $l) ?: 'Blood Group' }}</label>
                    <select id="blood_group" name="blood_group" class="f-input" style="max-width:220px;">
                        <option value="">{{ __('public.portal.opt_not_set', [], $l) ?: '— Not set —' }}</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $patient->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                    <p class="f-hint">{{ __('public.portal.hint_blood_group', [], $l) ?: 'Used in emergency profiles and clinical summaries shared with providers.' }}</p>
                    @error('blood_group')<p class="f-error">{{ $message }}</p>@enderror
                </div>

                {{-- Allergies (read-only) --}}
                <div class="f-field">
                    <span class="f-label">{{ __('public.portal.lbl_active_allergies', [], $l) ?: 'Active Allergies' }}</span>
                    @if($allergies->isEmpty())
                        <p class="f-muted f-small" style="display:flex;align-items:center;gap:6px;margin:4px 0 0;">
                            @include('partials.finder.icon', ['name' => 'check', 'size' => 15])
                            {{ __('public.portal.lbl_no_allergies', [], $l) ?: 'No allergies on record.' }}
                        </p>
                    @else
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            @foreach($allergies as $allergy)
                            @php
                                $sevCls = match(strtolower($allergy->severity ?? '')) {
                                    'life-threatening','severe','high' => 'f-badge--danger',
                                    'moderate','medium'                => 'f-badge--warn',
                                    default                            => 'pf-badge--muted',
                                };
                            @endphp
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--f-line);">
                                <span style="font-weight:650;">{{ $allergy->substance }}</span>
                                <span class="f-badge {{ $sevCls }}">@enum($allergy->severity ?? 'unknown', 'severity')</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                    <p class="f-hint">{{ __('public.portal.hint_allergies_readonly', [], $l) ?: 'Allergies are maintained by your healthcare providers. Contact your facility to add or update.' }}</p>
                </div>

                {{-- Conditions (read-only) --}}
                <div class="f-field" style="margin-bottom:0;">
                    <span class="f-label">{{ __('public.portal.lbl_active_conditions', [], $l) ?: 'Active Conditions' }}</span>
                    @if($conditions->isEmpty())
                        <p class="f-muted f-small" style="display:flex;align-items:center;gap:6px;margin:4px 0 0;">
                            @include('partials.finder.icon', ['name' => 'check', 'size' => 15])
                            {{ __('public.portal.lbl_no_conditions', [], $l) ?: 'No active conditions on record.' }}
                        </p>
                    @else
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            @foreach($conditions as $condition)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--f-line);">
                                <span style="font-weight:650;">{{ $condition->display_name ?? $condition->code ?? '—' }}</span>
                                <span class="f-badge">@enum($condition->status)</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                    <p class="f-hint">{{ __('public.portal.hint_conditions_readonly', [], $l) ?: 'Conditions are recorded by your healthcare providers and cannot be edited here.' }}</p>
                </div>
            </div>

            {{-- Emergency contact --}}
            <div class="f-card">
                <h2 class="pf-card-head">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 18])
                    {{ __('public.portal.panel_emergency_contact', [], $l) ?: 'Emergency Contact' }}
                </h2>
                <div class="f-field">
                    <label class="f-label" for="ec_name">{{ __('public.portal.field_ec_name', [], $l) ?: 'Name' }}</label>
                    <input type="text" id="ec_name" name="emergency_contact[name]" class="f-input" value="{{ old('emergency_contact.name', ($patient->emergency_contact ?? [])['name'] ?? '') }}">
                </div>
                <div class="f-field">
                    <label class="f-label" for="ec_phone">{{ __('public.portal.field_ec_phone', [], $l) ?: 'Phone' }}</label>
                    <input type="text" id="ec_phone" name="emergency_contact[phone]" class="f-input" inputmode="tel" value="{{ old('emergency_contact.phone', ($patient->emergency_contact ?? [])['phone'] ?? '') }}">
                </div>
                <div class="f-field" style="margin-bottom:0;">
                    <label class="f-label" for="ec_rel">{{ __('public.portal.field_ec_relationship', [], $l) ?: 'Relationship' }}</label>
                    <input type="text" id="ec_rel" name="emergency_contact[relationship]" class="f-input" value="{{ old('emergency_contact.relationship', ($patient->emergency_contact ?? [])['relationship'] ?? '') }}">
                </div>
            </div>

        </div>

        {{-- ── RIGHT COLUMN ────────────────────────────────────────────── --}}
        <div class="pf-stack" style="gap:16px;">

            {{-- Identity (read-only) --}}
            <div class="f-card">
                <h2 class="pf-card-head">
                    @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 18])
                    {{ __('public.portal.panel_identity', [], $l) ?: 'Identity' }}
                </h2>
                <dl class="pf-kv">
                    <div>
                        <dt>{{ __('public.portal.lbl_health_id', [], $l) ?: 'Health ID' }}</dt>
                        <dd style="font-family:ui-monospace,Menlo,Consolas,monospace;">{{ $patient->health_id }}</dd>
                    </div>
                    @if($patient->cnamgs_id)
                    <div>
                        <dt>{{ __('public.portal.lbl_cnamgs_id', [], $l) ?: 'CNAMGS ID' }}</dt>
                        <dd style="font-family:ui-monospace,Menlo,Consolas,monospace;">{{ $patient->cnamgs_id }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt>{{ __('public.portal.lbl_status', [], $l) ?: 'Status' }}</dt>
                        <dd>
                            @php
                                $portalStatus = $patient->verification_status ?? $patient->identity_status ?? 'Active';
                                $portalStatus = $portalStatus instanceof \BackedEnum ? $portalStatus->value : $portalStatus;
                            @endphp
                            @enum($portalStatus, 'verification')
                        </dd>
                    </div>
                </dl>
                <p class="f-hint">{{ __('public.portal.hint_identity_managed', [], $l) ?: 'Identity fields are managed by OpesCare. To update, contact your registered facility.' }}</p>
            </div>

            {{-- Privacy settings --}}
            <div class="f-card">
                <h2 class="pf-card-head">
                    @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 18])
                    {{ __('public.portal.panel_privacy_settings', [], $l) ?: 'Privacy Settings' }}
                </h2>

                <label class="pf-check">
                    <input type="hidden" name="privacy_require_consent" value="0">
                    <input type="checkbox" name="privacy_require_consent" value="1"
                        {{ ($patient->privacy_preferences['require_consent_for_full_record'] ?? true) ? 'checked' : '' }}>
                    <span>
                        <span style="display:block;font-weight:700;">{{ __('public.portal.chk_require_consent_lbl', [], $l) ?: 'Require Consent for Full Record' }}</span>
                        <span class="f-small f-muted">{{ __('public.portal.chk_require_consent_hint', [], $l) ?: 'Providers can only see a masked preview without your explicit consent.' }}</span>
                    </span>
                </label>

                <label class="pf-check">
                    <input type="hidden" name="privacy_emergency_access" value="0">
                    <input type="checkbox" name="privacy_emergency_access" value="1"
                        {{ ($patient->privacy_preferences['emergency_access_allowed'] ?? true) ? 'checked' : '' }}>
                    <span>
                        <span style="display:block;font-weight:700;">{{ __('public.portal.chk_emergency_access_lbl', [], $l) ?: 'Allow Emergency Access' }}</span>
                        <span class="f-small f-muted">{{ __('public.portal.chk_emergency_access_hint', [], $l) ?: 'Permit audited break-glass access during emergencies without standard consent.' }}</span>
                    </span>
                </label>

                <p class="f-hint" style="border-top:1px solid var(--f-line);padding-top:12px;margin-top:8px;">{{ __('public.portal.data_export_hint', [], $l) ?: 'Download a copy of all your personal health data (Law No. 2010/012).' }}</p>
            </div>

            {{-- Save --}}
            <button type="submit" class="f-btn f-btn--primary f-btn--block" style="min-height:48px;">
                @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                {{ __('public.portal.btn_save_changes_form', [], $l) ?: 'Save Changes' }}
            </button>

        </div>

    </div>
    </form>
    @endif
</div>

@endsection
