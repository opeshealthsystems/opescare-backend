@extends('layouts.auth')

@section('title', __('onboarding.developer.title'))

@section('content')
    <div class="auth-card">

        <a href="{{ route('register') }}" class="back-link" style="display:inline-flex;margin-bottom:1.5rem;">
            <i data-lucide="arrow-left"></i>
            {{ __('onboarding.common.back') }}
        </a>

        <div class="auth-step-head">
            <div class="auth-step-icon">
                <i data-lucide="code-2"></i>
            </div>
            <h1 class="auth-step-title">{{ __('onboarding.developer.title') }}</h1>
            <p class="auth-step-sub">{{ __('onboarding.developer.subtitle') }}</p>
        </div>

        @if(session('success'))
            <div class="auth-alert auth-alert-success">
                <i data-lucide="badge-check"></i>
                <div>{{ session('success') }}</div>
            </div>
            <div style="text-align:center;margin-top:2rem;">
                <a href="{{ route('public.landing') }}" class="auth-btn auth-btn-primary">
                    <i data-lucide="arrow-left"></i>
                    <span>{{ __('onboarding.common.back_to_home') }}</span>
                </a>
            </div>
        @else
            @if(session('error'))
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="triangle-alert"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="auth-alert auth-alert-danger">
                    <i data-lucide="triangle-alert"></i>
                    <ul style="margin:0;padding-left:1.25rem;">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.developer.submit') }}" method="POST" class="auth-form">
                @csrf

                <!-- Section 1: Contact Information -->
                <div class="form-section">
                    <div class="form-section-title">{{ __('onboarding.org.contact_sec') }}</div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="name" class="auth-label">{{ __('onboarding.common.full_name') }} *</label>
                            <input type="text" id="name" name="name"
                                   class="auth-input{{ $errors->has('name') ? ' auth-input-error' : '' }}" required>
                            @error('name')<div class="auth-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="auth-form-group">
                            <label for="organization" class="auth-label">{{ __('onboarding.developer.org_lbl') }} *</label>
                            <input type="text" id="organization" name="organization"
                                   class="auth-input{{ $errors->has('organization') ? ' auth-input-error' : '' }}" required>
                            @error('organization')<div class="auth-field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="role" class="auth-label">{{ __('onboarding.developer.role_lbl') }} *</label>
                            <input type="text" id="role" name="role"
                                   class="auth-input{{ $errors->has('role') ? ' auth-input-error' : '' }}"
                                   required placeholder="Lead Interoperability Engineer, Product Owner...">
                            @error('role')<div class="auth-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="auth-form-group">
                            <label for="country" class="auth-label">{{ __('onboarding.patient.country') }} *</label>
                            <input type="text" id="country" name="country"
                                   class="auth-input{{ $errors->has('country') ? ' auth-input-error' : '' }}"
                                   required value="{{ old('country', 'Canada') }}">
                            @error('country')<div class="auth-field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="email" class="auth-label">{{ __('onboarding.common.email') }} *</label>
                            <input type="email" id="email" name="email"
                                   class="auth-input{{ $errors->has('email') ? ' auth-input-error' : '' }}" required>
                            @error('email')<div class="auth-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="auth-form-group">
                            <label for="phone" class="auth-label">{{ __('onboarding.common.phone') }} *</label>
                            <input type="tel" id="phone" name="phone"
                                   class="auth-input{{ $errors->has('phone') ? ' auth-input-error' : '' }}" required>
                            @error('phone')<div class="auth-field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: Technical Parameters -->
                <div class="form-section">
                    <div class="form-section-title">{{ __('onboarding.developer.sec_vendor') }}</div>

                    <div class="auth-form-group">
                        <label for="system_type" class="auth-label">{{ __('onboarding.developer.system_type_lbl') }} *</label>
                        <select id="system_type" name="system_type"
                                class="auth-input{{ $errors->has('system_type') ? ' auth-input-error' : '' }}" required>
                            <option value="" disabled selected>{{ __('onboarding.common.select_option') }}</option>
                            <option value="HIS">Hospital Information System (HIS)</option>
                            <option value="LIS">Laboratory Information System (LIS)</option>
                            <option value="PHARMA">Pharmacy Stock / Dispensing System</option>
                            <option value="INSURANCE">Insurance Claims Management Engine</option>
                            <option value="BLOOD">Blood Bank Registry Platform</option>
                            <option value="MOBILE">Consumer Health Mobile App</option>
                            <option value="OTHER">Other Health Interoperability System</option>
                        </select>
                        @error('system_type')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="auth-form-group">
                        <label for="data_flow" class="auth-label">{{ __('onboarding.developer.expected_flow_lbl') }} *</label>
                        <select id="data_flow" name="data_flow"
                                class="auth-input{{ $errors->has('data_flow') ? ' auth-input-error' : '' }}" required>
                            <option value="" disabled selected>{{ __('onboarding.common.select_option') }}</option>
                            <option value="PULL_SUMMARY">Pull patient CCDA/FHIR summaries</option>
                            <option value="PUSH_ENCOUNTERS">Push clinical encounters</option>
                            <option value="PUSH_RESULTS">Push validated lab results</option>
                            <option value="PUSH_PRESCRIPTIONS">Push validated prescriptions</option>
                            <option value="SYNC_INVENTORY">Sync pharmacy/medicine stock</option>
                            <option value="SYNC_BLOOD">Sync blood bank availability logs</option>
                            <option value="WEBHOOKS">Receive webhook dispatch events</option>
                        </select>
                        @error('data_flow')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="auth-form-group">
                        <label for="integration_purpose" class="auth-label">{{ __('onboarding.developer.purpose_lbl') }} *</label>
                        <textarea id="integration_purpose" name="integration_purpose"
                                  class="auth-input{{ $errors->has('integration_purpose') ? ' auth-input-error' : '' }}"
                                  style="min-height:80px;resize:vertical;" required
                                  placeholder="Briefly describe the clinical use case or data synchronizations required..."></textarea>
                        @error('integration_purpose')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label class="auth-label">{{ __('onboarding.developer.sandbox_lbl') }}</label>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="sandbox" value="yes" checked> Yes</label>
                                <label class="radio-label"><input type="radio" name="sandbox" value="no"> No</label>
                            </div>
                        </div>
                        <div class="auth-form-group">
                            <label class="auth-label">{{ __('onboarding.developer.production_lbl') }}</label>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="production" value="yes"> Yes</label>
                                <label class="radio-label"><input type="radio" name="production" value="no" checked> No</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Security Notice -->
                <div class="notice-api">
                    <i data-lucide="shield-check"></i>
                    <p>{{ __('onboarding.developer.safety_notice') }}</p>
                </div>

                <div class="auth-checkbox-group">
                    <input type="checkbox" id="accept_vendor_terms" name="accept_vendor_terms" required>
                    <label for="accept_vendor_terms" class="auth-checkbox-label">{{ __('onboarding.developer.terms_label') }} *</label>
                </div>
                @error('accept_vendor_terms')<div class="auth-field-error">{{ $message }}</div>@enderror

                <button type="submit" class="auth-btn auth-btn-primary">
                    <i data-lucide="code-2"></i>
                    <span>{{ __('onboarding.developer.cta_btn') }}</span>
                </button>
            </form>
        @endif
    </div>
@endsection
