@extends('layouts.auth')

@section('title', __('auth.register.hospital.title'))

@section('content')
    <div class="auth-card">

        <a href="{{ route('register') }}" class="back-link" style="display:inline-flex;margin-bottom:1.5rem;">
            <i data-lucide="arrow-left"></i>
            {{ __('onboarding.common.back') }}
        </a>

        <div class="auth-step-head">
            <div class="auth-step-icon">
                <i data-lucide="hospital"></i>
            </div>
            <h1 class="auth-step-title">{{ __('auth.register.hospital.title') }}</h1>
            <p class="auth-step-sub">Onboard your facility to the OpesCare interoperability network.</p>
        </div>

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

        <form action="#" method="POST" class="auth-form">
            @csrf

            <div class="form-section">
                <div class="form-section-title">Facility Details</div>

                <div class="auth-form-row">
                    <div class="auth-form-group">
                        <label for="facility_name" class="auth-label">{{ __('auth.register.hospital.name') }} *</label>
                        <input type="text" id="facility_name" name="facility_name"
                               class="auth-input{{ $errors->has('facility_name') ? ' auth-input-error' : '' }}"
                               value="{{ old('facility_name') }}" required>
                        @error('facility_name')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="auth-form-group">
                        <label for="facility_type" class="auth-label">{{ __('auth.register.hospital.type') }} *</label>
                        <select id="facility_type" name="facility_type"
                                class="auth-input{{ $errors->has('facility_type') ? ' auth-input-error' : '' }}" required>
                            <option value="hospital" {{ old('facility_type') == 'hospital' ? 'selected' : '' }}>Hospital</option>
                            <option value="clinic"   {{ old('facility_type') == 'clinic'   ? 'selected' : '' }}>Clinic</option>
                            <option value="lab"      {{ old('facility_type') == 'lab'      ? 'selected' : '' }}>Laboratory</option>
                            <option value="pharmacy" {{ old('facility_type') == 'pharmacy' ? 'selected' : '' }}>Pharmacy</option>
                        </select>
                        @error('facility_type')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="license_number" class="auth-label">{{ __('auth.register.hospital.license') }} *</label>
                    <input type="text" id="license_number" name="license_number"
                           class="auth-input{{ $errors->has('license_number') ? ' auth-input-error' : '' }}"
                           value="{{ old('license_number') }}" required>
                    @error('license_number')<div class="auth-field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">Administrator Contact</div>

                <div class="auth-form-group">
                    <label for="admin_name" class="auth-label">{{ __('auth.register.hospital.admin_name') }} *</label>
                    <input type="text" id="admin_name" name="admin_name"
                           class="auth-input{{ $errors->has('admin_name') ? ' auth-input-error' : '' }}"
                           value="{{ old('admin_name') }}" required>
                    @error('admin_name')<div class="auth-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="auth-form-row">
                    <div class="auth-form-group">
                        <label for="admin_email" class="auth-label">{{ __('auth.register.hospital.admin_email') }} *</label>
                        <input type="email" id="admin_email" name="admin_email"
                               class="auth-input{{ $errors->has('admin_email') ? ' auth-input-error' : '' }}"
                               value="{{ old('admin_email') }}" required>
                        @error('admin_email')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="auth-form-group">
                        <label for="admin_phone" class="auth-label">{{ __('auth.register.hospital.admin_phone') }} *</label>
                        <input type="tel" id="admin_phone" name="admin_phone"
                               class="auth-input{{ $errors->has('admin_phone') ? ' auth-input-error' : '' }}"
                               value="{{ old('admin_phone') }}" required>
                        @error('admin_phone')<div class="auth-field-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="auth-btn auth-btn-primary">
                <i data-lucide="hospital"></i>
                <span>{{ __('auth.register.hospital.submit') }}</span>
            </button>
        </form>
    </div>
@endsection
