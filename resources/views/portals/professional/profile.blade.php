@extends('layouts.portal')
@section('title', __('professional.page_title', [], app()->getLocale()))
@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pro.css') }}?v={{ @filemtime(public_path('css/od-pro.css')) ?: '1' }}">
@endsection

@section('content')

{{-- ============================================================= --}}
{{-- 1. Page header + verification-status chip                     --}}
{{-- ============================================================= --}}
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('professional.page_title', [], app()->getLocale()) }}</h1>
        <p class="page-subtitle">{{ __('professional.page_subtitle', [], app()->getLocale()) }}</p>
    </div>
    <div class="page-actions" aria-label="{{ __('professional.verification_status', [], app()->getLocale()) }}">
        @if($verifiedCount > 0)
            <span class="badge badge-success">
                <i data-lucide="badge-check"></i>
                {{ __('professional.status_verified', [], app()->getLocale()) }}
            </span>
        @elseif($pendingCount > 0)
            <span class="badge badge-warning">
                <i data-lucide="clock"></i>
                {{ __('professional.status_pending', [], app()->getLocale()) }}
            </span>
        @else
            <span class="badge badge-neutral">
                <i data-lucide="circle-dashed"></i>
                {{ __('professional.status_none', [], app()->getLocale()) }}
            </span>
        @endif
    </div>
</div>

{{-- ============================================================= --}}
{{-- 2. Pending-review info banner                                 --}}
{{-- ============================================================= --}}
@if($pendingCount > 0)
<div class="alert alert-info mb-6" role="status">
    <i data-lucide="info"></i>
    <div>{{ __('professional.pending_banner', [], app()->getLocale()) }}</div>
</div>
@endif

{{-- ============================================================= --}}
{{-- 3. Flash messages + validation errors                         --}}
{{-- ============================================================= --}}
@if(session('success'))
<div class="alert alert-success mb-6" role="alert">
    <i data-lucide="check-circle"></i>
    <div>{{ session('success') }}</div>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-circle"></i>
    <div>{{ session('error') }}</div>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-triangle"></i>
    <div>
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- ============================================================= --}}
{{-- 4. Personal details                                           --}}
{{-- ============================================================= --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="user"></i> {{ __('professional.personal_section', [], app()->getLocale()) }}</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('professional.profile.update') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="first_name">{{ __('professional.first_name', [], app()->getLocale()) }}</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="{{ old('first_name', $profile->first_name) }}" maxlength="100" required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="last_name">{{ __('professional.last_name', [], app()->getLocale()) }}</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="{{ old('last_name', $profile->last_name) }}" maxlength="100" required autocomplete="family-name">
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label" for="email">{{ __('professional.email', [], app()->getLocale()) }}</label>
                    <input type="email" id="email" class="form-control" value="{{ $profile->email }}" readonly autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">{{ __('professional.phone', [], app()->getLocale()) }}</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="{{ old('phone', $profile->phone) }}" maxlength="30" autocomplete="tel">
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label" for="job_title">{{ __('professional.job_title', [], app()->getLocale()) }}</label>
                    <input type="text" id="job_title" name="job_title" class="form-control" value="{{ old('job_title', $profile->job_title) }}" maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department">{{ __('professional.department', [], app()->getLocale()) }}</label>
                    <input type="text" id="department" name="department" class="form-control" value="{{ old('department', $profile->department) }}" maxlength="120">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('professional.save_profile', [], app()->getLocale()) }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================= --}}
{{-- 5. Qualifications list                                        --}}
{{-- ============================================================= --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="award"></i> {{ __('professional.quals_section', [], app()->getLocale()) }}</h3>
    </div>

    @if($credentials->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="file-badge"></i></div>
                <p>{{ __('professional.no_quals', [], app()->getLocale()) }}</p>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('professional.credential_type', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.issuing_body', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.credential_number', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.issued_label', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.expires_label', [], app()->getLocale()) }}</th>
                        <th>{{ __('professional.verification_status', [], app()->getLocale()) }}</th>
                        <th class="row-actions">{{ __('professional.remove', [], app()->getLocale()) }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($credentials as $credential)
                    @php
                        $ctKey   = 'professional.ct_' . $credential->credential_type;
                        $ctLabel = __($ctKey, [], app()->getLocale());
                        if ($ctLabel === $ctKey) {
                            $ctLabel = __('professional.ct_other', [], app()->getLocale());
                        }
                        $isExpired = $credential->expiry_date && $credential->expiry_date->isPast();
                    @endphp
                    <tr>
                        <td data-label="{{ __('professional.credential_type', [], app()->getLocale()) }}"><span class="td-strong">{{ $ctLabel }}</span></td>
                        <td data-label="{{ __('professional.issuing_body', [], app()->getLocale()) }}">{{ $credential->issuing_body }}</td>
                        <td data-label="{{ __('professional.credential_number', [], app()->getLocale()) }}">{{ $credential->credential_number }}</td>
                        <td data-label="{{ __('professional.issued_label', [], app()->getLocale()) }}" class="td-muted">{{ $credential->issued_date ? $credential->issued_date->translatedFormat('d M Y') : '—' }}</td>
                        <td data-label="{{ __('professional.expires_label', [], app()->getLocale()) }}" class="td-muted">{{ $credential->expiry_date ? $credential->expiry_date->translatedFormat('d M Y') : (__('professional.expiry_none', [], app()->getLocale())) }}</td>
                        <td data-label="{{ __('professional.verification_status', [], app()->getLocale()) }}">
                            @if($isExpired)
                                <span class="badge badge-danger"><i data-lucide="calendar-x"></i> {{ __('professional.badge_expired', [], app()->getLocale()) }}</span>
                            @elseif($credential->verified_at)
                                <span class="badge badge-success"><i data-lucide="badge-check"></i> {{ __('professional.badge_verified', [], app()->getLocale()) }}</span>
                            @else
                                <span class="badge badge-warning"><i data-lucide="clock"></i> {{ __('professional.badge_pending', [], app()->getLocale()) }}</span>
                            @endif
                        </td>
                        <td class="row-actions" data-label="{{ __('professional.remove', [], app()->getLocale()) }}">
                            @if($credential->verified_at)
                                <span class="td-muted" title="{{ __('professional.locked_note', [], app()->getLocale()) }}">
                                    <i data-lucide="lock"></i> {{ __('professional.locked_note', [], app()->getLocale()) }}
                                </span>
                            @else
                                <form method="POST" action="{{ route('professional.qualifications.destroy', $credential->id) }}" class="inline-form" data-confirm="{{ __('professional.remove_confirm', [], app()->getLocale()) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" aria-label="{{ __('professional.remove', [], app()->getLocale()) }}">
                                        <i data-lucide="trash-2"></i> {{ __('professional.remove', [], app()->getLocale()) }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============================================================= --}}
{{-- 6. Add a qualification                                        --}}
{{-- ============================================================= --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="plus-circle"></i> {{ __('professional.add_qual', [], app()->getLocale()) }}</h3>
    </div>
    <div class="panel-body">
        <p class="page-subtitle mb-6">{{ __('professional.quals_intro', [], app()->getLocale()) }}</p>

        <form method="POST" action="{{ route('professional.qualifications.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="credential_type">{{ __('professional.credential_type', [], app()->getLocale()) }}</label>
                    <select id="credential_type" name="credential_type" class="form-control" required>
                        @foreach($credentialTypes as $type)
                            @php
                                $optKey   = 'professional.ct_' . $type;
                                $optLabel = __($optKey, [], app()->getLocale());
                                if ($optLabel === $optKey) {
                                    $optLabel = __('professional.ct_other', [], app()->getLocale());
                                }
                            @endphp
                            <option value="{{ $type }}" @selected(old('credential_type') === $type)>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="issuing_body">{{ __('professional.issuing_body', [], app()->getLocale()) }}</label>
                    <input type="text" id="issuing_body" name="issuing_body" class="form-control" value="{{ old('issuing_body') }}" maxlength="255" required>
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label form-label-required" for="credential_number">{{ __('professional.credential_number', [], app()->getLocale()) }}</label>
                    <input type="text" id="credential_number" name="credential_number" class="form-control" value="{{ old('credential_number') }}" maxlength="100" required>
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label form-label-required" for="issued_date">{{ __('professional.issued_date', [], app()->getLocale()) }}</label>
                    <input type="date" id="issued_date" name="issued_date" class="form-control" value="{{ old('issued_date') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="expiry_date">{{ __('professional.expiry_date', [], app()->getLocale()) }}</label>
                    <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('professional.submit_qual', [], app()->getLocale()) }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
