@extends('layouts.portal')

@section('title', __('public.portal.profile_page_title', [], app()->getLocale()) ?: 'My Profile — OpesCare Patient Portal')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.profile_breadcrumb', [], app()->getLocale()) ?: 'Profile')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.profile_page_heading', [], $l) ?: 'My Profile' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.profile_page_subtitle', [], $l) ?: 'Manage your contact details, clinical information, and privacy preferences.' }}</p>
    </div>
</div>

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.profile_no_profile_h3', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.profile_no_profile_p', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@else

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

<form method="POST" action="{{ route('portals.patient.profile.update') }}">
@csrf

<div class="grid-main-side">

    {{-- ── LEFT COLUMN ──────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:var(--p-space-5);">

        {{-- Contact Details --}}
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="user"></i> {{ __('public.portal.panel_contact_details', [], $l) ?: 'Contact Details' }}</h2></div>
            <div class="panel-body">

                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_phone_number', [], $l) ?: 'Phone Number' }}</label>
                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $patient->phone_number) }}">
                    @error('phone_number')<div class="form-hint">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_email', [], $l) ?: 'Email' }}</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $patient->email) }}">
                    @error('email')<div class="form-hint">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_address', [], $l) ?: 'Address' }}</label>
                    <textarea name="address" rows="2" class="form-control">{{ old('address', $patient->address) }}</textarea>
                    @error('address')<div class="form-hint">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_date_of_birth', [], $l) ?: 'Date of Birth' }}</label>
                    <input type="date" name="date_of_birth" class="form-control"
                        value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}"
                        max="{{ now()->subDay()->format('Y-m-d') }}">
                    @if($patient->date_of_birth)
                        <div class="form-hint">{{ __('public.portal.lbl_age', [], $l) ?: 'Age' }}: {{ $patient->date_of_birth->diffInYears(now()) }} {{ __('public.portal.lbl_years', [], $l) ?: 'years' }}</div>
                    @endif
                    @error('date_of_birth')<div class="form-hint">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        {{-- Clinical Information --}}
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="heart-pulse"></i> {{ __('public.portal.panel_clinical_info', [], $l) ?: 'Clinical Information' }}</h2></div>
            <div class="panel-body">

                {{-- Blood Group --}}
                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_blood_group', [], $l) ?: 'Blood Group' }}</label>
                    <select name="blood_group" class="form-control">
                        <option value="">{{ __('public.portal.opt_not_set', [], $l) ?: '— Not set —' }}</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group', $patient->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">{{ __('public.portal.hint_blood_group', [], $l) ?: 'Used in emergency profiles and clinical summaries shared with providers.' }}</div>
                    @error('blood_group')<div class="form-hint">{{ $message }}</div>@enderror
                </div>

                {{-- Active Allergies (read-only summary + link) --}}
                <div class="form-group">
                    <div class="flex-between mb-3">
                        <label class="form-label">{{ __('public.portal.lbl_active_allergies', [], $l) ?: 'Active Allergies' }}</label>
                        <a href="{{ route('portals.patient.allergies') }}" class="link-action">
                            <i data-lucide="external-link"></i> {{ __('public.portal.link_view_manage', [], $l) ?: 'View / manage →' }}
                        </a>
                    </div>
                    @if($allergies->isEmpty())
                        <div class="empty-state">
                            <i data-lucide="check-circle"></i> {{ __('public.portal.lbl_no_allergies', [], $l) ?: 'No allergies on record.' }}
                        </div>
                    @else
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            @foreach($allergies as $allergy)
                            @php
                                $sevCls = match(strtolower($allergy->severity ?? '')) {
                                    'life-threatening','severe','high' => 'badge-danger',
                                    'moderate','medium'               => 'badge-warning',
                                    default                           => 'badge-neutral',
                                };
                            @endphp
                            <div class="list-row">
                                <span class="list-row__main">
                                    @if(in_array(strtolower($allergy->severity ?? ''), ['life-threatening','severe','high']))
                                        <i data-lucide="alert-triangle"></i>
                                    @endif
                                    {{ $allergy->substance }}
                                </span>
                                <span class="badge {{ $sevCls }}">@enum($allergy->severity ?? 'unknown', 'severity')</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="form-hint">{{ __('public.portal.hint_allergies_readonly', [], $l) ?: 'Allergies are maintained by your healthcare providers. Contact your facility to add or update.' }}</div>
                </div>

                {{-- Active Conditions (read-only summary + link) --}}
                <div class="form-group">
                    <div class="flex-between mb-3">
                        <label class="form-label">{{ __('public.portal.lbl_active_conditions', [], $l) ?: 'Active Conditions' }}</label>
                        <a href="{{ route('portals.patient.clinical') }}" class="link-action">
                            <i data-lucide="external-link"></i> {{ __('public.portal.link_view_all', [], $l) ?: 'View all →' }}
                        </a>
                    </div>
                    @if($conditions->isEmpty())
                        <div class="empty-state">
                            <i data-lucide="check-circle"></i> {{ __('public.portal.lbl_no_conditions', [], $l) ?: 'No active conditions on record.' }}
                        </div>
                    @else
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            @foreach($conditions as $condition)
                            <div class="list-row">
                                <span class="list-row__main">{{ $condition->display_name ?? $condition->code ?? '—' }}</span>
                                <span class="badge {{ $condition->status === 'chronic' ? 'badge-teal' : 'badge-primary' }}">@enum($condition->status)</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="form-hint">{{ __('public.portal.hint_conditions_readonly', [], $l) ?: 'Conditions are recorded by your healthcare providers and cannot be edited here.' }}</div>
                </div>

            </div>
        </div>

        {{-- Emergency Contact --}}
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="phone-call"></i> {{ __('public.portal.panel_emergency_contact', [], $l) ?: 'Emergency Contact' }}</h2></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_ec_name', [], $l) ?: 'Name' }}</label>
                    <input type="text" name="emergency_contact[name]" class="form-control" value="{{ old('emergency_contact.name', ($patient->emergency_contact ?? [])['name'] ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_ec_phone', [], $l) ?: 'Phone' }}</label>
                    <input type="text" name="emergency_contact[phone]" class="form-control" value="{{ old('emergency_contact.phone', ($patient->emergency_contact ?? [])['phone'] ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.field_ec_relationship', [], $l) ?: 'Relationship' }}</label>
                    <input type="text" name="emergency_contact[relationship]" class="form-control" value="{{ old('emergency_contact.relationship', ($patient->emergency_contact ?? [])['relationship'] ?? '') }}">
                </div>
            </div>
        </div>

    </div>

    {{-- ── RIGHT COLUMN ─────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:var(--p-space-5);">

        {{-- Identity (read-only) --}}
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="fingerprint"></i> {{ __('public.portal.panel_identity', [], $l) ?: 'Identity' }}</h2></div>
            <div class="panel-body">
                <div class="kv-table">
                    <div class="kv-strong">{{ __('public.portal.lbl_health_id', [], $l) ?: 'Health ID' }}</div>
                    <div class="mono">{{ $patient->health_id }}</div>
                    @if($patient->cnamgs_id)
                    <div class="kv-strong">{{ __('public.portal.lbl_cnamgs_id', [], $l) ?: 'CNAMGS ID' }}</div>
                    <div class="mono">{{ $patient->cnamgs_id }}</div>
                    @endif
                    <div class="kv-strong">{{ __('public.portal.lbl_status', [], $l) ?: 'Status' }}</div>
                    <div>
                        @php
                            $portalStatus = $patient->verification_status ?? $patient->identity_status ?? 'Active';
                            $portalStatus = $portalStatus instanceof \BackedEnum ? $portalStatus->value : $portalStatus;
                        @endphp
                        @enum($portalStatus, 'verification')
                    </div>
                </div>
                <div class="form-hint mt-3">{{ __('public.portal.hint_identity_managed', [], $l) ?: 'Identity fields are managed by OpesCare. To update, contact your registered facility.' }}</div>
            </div>
        </div>

        {{-- Privacy Settings --}}
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title"><i data-lucide="shield-check"></i> {{ __('public.portal.panel_privacy_settings', [], $l) ?: 'Privacy Settings' }}</h2></div>
            <div class="panel-body">

                <label class="form-check">
                    <input type="hidden" name="privacy_require_consent" value="0">
                    <input type="checkbox" name="privacy_require_consent" value="1"
                        {{ ($patient->privacy_preferences['require_consent_for_full_record'] ?? true) ? 'checked' : '' }}>
                    <div>
                        <div class="td-strong">{{ __('public.portal.chk_require_consent_lbl', [], $l) ?: 'Require Consent for Full Record' }}</div>
                        <div class="text-sm text-muted">{{ __('public.portal.chk_require_consent_hint', [], $l) ?: 'Providers can only see a masked preview without your explicit consent.' }}</div>
                    </div>
                </label>

                <label class="form-check">
                    <input type="hidden" name="privacy_emergency_access" value="0">
                    <input type="checkbox" name="privacy_emergency_access" value="1"
                        {{ ($patient->privacy_preferences['emergency_access_allowed'] ?? true) ? 'checked' : '' }}>
                    <div>
                        <div class="td-strong">{{ __('public.portal.chk_emergency_access_lbl', [], $l) ?: 'Allow Emergency Access' }}</div>
                        <div class="text-sm text-muted">{{ __('public.portal.chk_emergency_access_hint', [], $l) ?: 'Permit audited break-glass access during emergencies without standard consent.' }}</div>
                    </div>
                </label>

                <hr style="margin:1rem 0;border:none;border-top:1px solid var(--color-border);">
                <a href="{{ route('portals.patient.data-rights.export') }}" class="btn btn-secondary">
                    <i data-lucide="download"></i> {{ __('public.portal.btn_download_my_data', [], $l) ?: 'Download my data' }}
                </a>
                <p class="text-sm text-muted" style="margin-top:.5rem;">{{ __('public.portal.data_export_hint', [], $l) ?: 'Download a copy of all your personal health data (Law No. 2010/012).' }}</p>

            </div>
        </div>

        {{-- Save --}}
        <div>
            <button type="submit" class="btn btn-primary btn-block">
                <i data-lucide="save"></i> {{ __('public.portal.btn_save_changes_form', [], $l) ?: 'Save Changes' }}
            </button>
        </div>

    </div>

</div>
</form>
@endif

@endsection
