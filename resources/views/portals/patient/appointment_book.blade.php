@extends('layouts.portal')

@section('title', __('public.appt_book_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.appt_book_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.appt_book_title') }}</h1>
        <p class="page-subtitle">{{ __('public.appt_book_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.patient.appointments') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel" style="max-width:640px;">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.patient.appointments.book.store') }}">
            @csrf

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="facility_id">{{ __('public.appt_book_facility') }}</label>
                <select id="facility_id" name="facility_id" class="form-control" required>
                    <option value="">—</option>
                    @foreach($facilities as $f)
                        <option value="{{ $f->id }}" @selected(old('facility_id', $defaultFacility) === $f->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="appointment_type">{{ __('public.appt_book_type') }}</label>
                <select id="appointment_type" name="appointment_type" class="form-control" required>
                    @foreach($types as $t)
                        <option value="{{ $t }}" @selected(old('appointment_type') === $t)>{{ __('public.appt_type_' . $t) }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="scheduled_at">{{ __('public.appt_book_when') }}</label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control"
                       value="{{ old('scheduled_at') }}" required>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="reason">{{ __('public.appt_book_reason') }}</label>
                <textarea id="reason" name="reason" class="form-control" rows="3" maxlength="500"
                          placeholder="{{ __('public.appt_book_reason_ph') }}">{{ old('reason') }}</textarea>
            </div>

            <p class="page-subtitle" style="font-size:.82rem;margin:0 0 1rem;">{{ __('public.appt_book_note') }}</p>

            <button type="submit" class="btn btn-primary">
                <i data-lucide="calendar-plus"></i> {{ __('public.appt_book_submit') }}
            </button>
        </form>
    </div>
</div>

@endsection
