@extends('layouts.portal')

@section('title', __('public.appt_book_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.appt_book_title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('content')

<div class="pf pf--form" id="pf-book">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.appt_book_title') }}</h1>
            <p class="pf-sub">{{ __('public.appt_book_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.appointments') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}
        </a>
    </div>

    @if($errors->any())
    <div class="pf-alert pf-alert--danger" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <p>{{ $errors->first() }}</p>
    </div>
    @endif

    <form method="POST" action="{{ route('portals.patient.appointments.book.store') }}" id="pf-book-form">
        @csrf

        {{-- Step progress (JS-enhanced; hidden without JS) --}}
        <p class="pf-step-label" id="pf-step-label" aria-live="polite"></p>
        <div class="pf-steps" aria-hidden="true">
            <span data-dot="1"></span><span data-dot="2"></span><span data-dot="3"></span><span data-dot="4"></span>
        </div>

        {{-- Step 1 — facility --}}
        <section class="f-card pf-step" data-step="1" data-title="{{ __('finder_patient.step_facility') }}">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'building-2', 'size' => 18])
                {{ __('public.appt_book_facility') }}
            </h2>
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="facility_id">{{ __('finder_patient.step_facility') }}</label>
                <select id="facility_id" name="facility_id" class="f-input" required>
                    <option value="">{{ __('finder_patient.choose_facility') }}</option>
                    @foreach($facilities as $f)
                        <option value="{{ $f->id }}" @selected(old('facility_id', $defaultFacility) === $f->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
                <p class="f-hint">{{ __('finder_patient.facility_help') }}</p>
                <p class="f-error" data-step-error hidden>{{ __('finder_patient.step_required') }}</p>
            </div>
        </section>

        {{-- Step 2 — type --}}
        <section class="f-card pf-step" data-step="2" data-title="{{ __('finder_patient.step_type') }}" style="margin-top:12px;">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18])
                {{ __('public.appt_book_type') }}
            </h2>
            <p class="f-hint" style="margin:-6px 0 10px;">{{ __('finder_patient.type_help') }}</p>
            <div class="pf-choices" role="radiogroup" aria-label="{{ __('public.appt_book_type') }}">
                @foreach($types as $t)
                <label class="pf-choice">
                    <input type="radio" name="appointment_type" value="{{ $t }}" required
                           @checked(old('appointment_type', $loop->first ? $t : null) === $t)
                           data-label="{{ __('public.appt_type_' . $t) }}">
                    {{ __('public.appt_type_' . $t) }}
                    <span class="pf-choice__check">@include('partials.finder.icon', ['name' => 'check', 'size' => 18])</span>
                </label>
                @endforeach
            </div>
            <p class="f-error" data-step-error hidden>{{ __('finder_patient.step_required') }}</p>
        </section>

        {{-- Step 3 — date & time (+ optional reason) --}}
        <section class="f-card pf-step" data-step="3" data-title="{{ __('finder_patient.step_time') }}" style="margin-top:12px;">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'calendar', 'size' => 18])
                {{ __('public.appt_book_when') }}
            </h2>
            <div class="f-field">
                <label class="f-label" for="scheduled_at">{{ __('finder_patient.step_time') }}</label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="f-input"
                       value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                <p class="f-hint">{{ __('finder_patient.time_help') }}</p>
                <p class="f-error" data-step-error hidden>{{ __('finder_patient.step_required') }}</p>
            </div>
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="reason">{{ __('public.appt_book_reason') }}</label>
                <textarea id="reason" name="reason" class="f-input" rows="3" maxlength="500"
                          placeholder="{{ __('public.appt_book_reason_ph') }}">{{ old('reason') }}</textarea>
            </div>
        </section>

        {{-- Step 4 — confirm --}}
        <section class="pf-step" data-step="4" data-title="{{ __('finder_patient.step_confirm') }}" style="margin-top:12px;">
            <div class="f-card" id="pf-confirm-summary" hidden>
                <h2 class="pf-card-head">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                    {{ __('finder_patient.confirm_title') }}
                </h2>
                <p class="f-hint" style="margin:-6px 0 10px;">{{ __('finder_patient.confirm_check') }}</p>
                <dl class="pf-summary">
                    <div><dt>{{ __('finder_patient.confirm_facility') }}</dt><dd id="pf-sum-facility">—</dd></div>
                    <div><dt>{{ __('finder_patient.confirm_type') }}</dt><dd id="pf-sum-type">—</dd></div>
                    <div><dt>{{ __('finder_patient.confirm_time') }}</dt><dd id="pf-sum-time">—</dd></div>
                    <div><dt>{{ __('finder_patient.confirm_reason') }}</dt><dd id="pf-sum-reason">{{ __('finder_patient.not_provided') }}</dd></div>
                </dl>
            </div>

            <div class="pf-alert" style="margin-top:12px;">
                @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                <p>{{ __('finder_patient.lifecycle_note') }}</p>
            </div>

            <button type="submit" class="f-btn f-btn--primary f-btn--block pf-nojs-only" style="min-height:48px;">
                {{ __('public.appt_book_submit') }}
            </button>
        </section>

        {{-- JS wizard navigation --}}
        <div class="pf-wizard-nav">
            <button type="button" class="f-btn" id="pf-back" style="min-height:48px;">
                @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
                {{ __('finder_patient.btn_back') }}
            </button>
            <button type="button" class="f-btn f-btn--primary" id="pf-next" style="flex:1;min-height:48px;">
                {{ __('finder_patient.btn_continue') }}
                @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])
            </button>
            <button type="submit" class="f-btn f-btn--primary" id="pf-submit" style="flex:1;min-height:48px;" hidden>
                {{ __('public.appt_book_submit') }}
            </button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
(function () {
    var root = document.getElementById('pf-book');
    var form = document.getElementById('pf-book-form');
    if (!root || !form) return;

    var steps    = Array.prototype.slice.call(form.querySelectorAll('.pf-step'));
    var dots     = Array.prototype.slice.call(form.querySelectorAll('.pf-steps span'));
    var label    = document.getElementById('pf-step-label');
    var btnBack  = document.getElementById('pf-back');
    var btnNext  = document.getElementById('pf-next');
    var btnSend  = document.getElementById('pf-submit');
    var summary  = document.getElementById('pf-confirm-summary');
    var stepsTpl = @json(__('finder_patient.steps_label', ['current' => ':c', 'total' => ':t']));
    var notGiven = @json(__('finder_patient.not_provided'));
    var locale   = @json(app()->getLocale());
    var current  = 0;

    root.classList.add('pf-js');

    function validate(i) {
        var step = steps[i];
        var ok = true;
        if (i === 0) ok = !!form.facility_id.value;
        if (i === 1) ok = !!form.querySelector('input[name="appointment_type"]:checked');
        if (i === 2) ok = !!form.scheduled_at.value;
        var err = step.querySelector('[data-step-error]');
        if (err) err.hidden = ok;
        return ok;
    }

    function fillSummary() {
        var fac = form.facility_id;
        document.getElementById('pf-sum-facility').textContent =
            fac.selectedIndex > 0 ? fac.options[fac.selectedIndex].text : '—';
        var type = form.querySelector('input[name="appointment_type"]:checked');
        document.getElementById('pf-sum-type').textContent = type ? type.getAttribute('data-label') : '—';
        var when = form.scheduled_at.value;
        var text = '—';
        if (when) {
            var d = new Date(when);
            if (!isNaN(d)) {
                text = d.toLocaleString(locale === 'fr' ? 'fr-FR' : 'en-GB', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            } else { text = when; }
        }
        document.getElementById('pf-sum-time').textContent = text;
        var reason = (form.reason.value || '').trim();
        document.getElementById('pf-sum-reason').textContent = reason || notGiven;
    }

    function show(i) {
        current = i;
        steps.forEach(function (s, idx) { s.hidden = idx !== i; });
        dots.forEach(function (d, idx) { d.classList.toggle('is-done', idx <= i); });
        if (label) {
            label.textContent = stepsTpl.replace(':c', i + 1).replace(':t', steps.length)
                + ' — ' + (steps[i].getAttribute('data-title') || '');
        }
        btnBack.hidden = i === 0;
        btnNext.hidden = i === steps.length - 1;
        btnSend.hidden = i !== steps.length - 1;
        if (i === steps.length - 1) { fillSummary(); summary.hidden = false; }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    btnNext.addEventListener('click', function () {
        if (!validate(current)) return;
        show(Math.min(current + 1, steps.length - 1));
    });
    btnBack.addEventListener('click', function () { show(Math.max(current - 1, 0)); });

    show(0);
})();
</script>
@endsection
