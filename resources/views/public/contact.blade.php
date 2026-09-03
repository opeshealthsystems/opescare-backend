@extends('layouts.finder')

@section('title', __('finder_story.contact.page_title'))
@section('description', __('finder_story.contact.meta_description'))

@section('content')

    {{--
        Restyled shell only — the form wiring is untouched on purpose:
        POST route('public.contact.submit'), fields name / email / organisation /
        subject / message with the same option values, old() repopulation,
        @error blocks and the session('contact_success') flash all match
        PublicPageController::contactSubmit exactly. Form labels keep their
        existing bilingual public.contact.* keys; the page framing (questions
        AND listing corrections) is the Finder story.
    --}}
    <style>
        .fs-contact { display: grid; grid-template-columns: 1fr; gap: 20px; align-items: start; }
        @media (min-width: 960px) { .fs-contact { grid-template-columns: 2fr 3fr; } }
        .fs-aside-card { display: flex; align-items: flex-start; gap: 0.75rem; }
        .fs-aside-card > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-brand); }
        .fs-aside-card + .fs-aside-card { margin-top: 1rem; }
        .fs-form-row { display: grid; grid-template-columns: 1fr; gap: 0 16px; }
        @media (min-width: 640px) { .fs-form-row { grid-template-columns: 1fr 1fr; } }
        .fs-flash { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.875rem 1rem; border-radius: var(--f-r); background: var(--f-good-wash); color: var(--f-good); margin-bottom: 1.25rem; }
        .fs-flash > svg { flex-shrink: 0; margin-top: 0.125rem; }
        .fs-narrow { max-width: 720px; }
    </style>

    {{-- 01 · Hero --}}
    <section class="f-section f-section--surface f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.contact.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.contact.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.contact.lead') }}</p>
            </div>
        </div>
    </section>

    {{-- 02 · Channels + form --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-contact">

                <div>
                    <div class="f-card fs-aside-card">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 20])
                        <div>
                            <h2 class="f-small">{{ __('finder_story.contact.report_title') }}</h2>
                            <p class="f-small f-muted">{{ __('finder_story.contact.report_p') }}</p>
                        </div>
                    </div>

                    <div class="f-card fs-aside-card">
                        @include('partials.finder.icon', ['name' => 'external-link', 'size' => 20])
                        <div>
                            <h2 class="f-small">{{ __('finder_story.contact.email_title') }}</h2>
                            <p class="f-small f-muted">{{ __('finder_story.contact.email_p') }}</p>
                            <p class="f-small"><a href="mailto:support@opescare.com">support@opescare.com</a></p>
                        </div>
                    </div>

                    <div class="f-card fs-aside-card">
                        @include('partials.finder.icon', ['name' => 'shield-check', 'size' => 20])
                        <div>
                            <h2 class="f-small">{{ __('finder_story.contact.privacy_title') }}</h2>
                            <p class="f-small f-muted">{{ __('finder_story.contact.privacy_p') }}</p>
                        </div>
                    </div>
                </div>

                <div class="f-card">
                    <h2>{{ __('public.contact.form_title') }}</h2>

                    @if (session('contact_success'))
                        <div class="fs-flash" role="status">
                            @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                            <span>{{ __('public.contact.success_message') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.contact.submit') }}" novalidate>
                        @csrf

                        <div class="fs-form-row">
                            <div class="f-field">
                                <label class="f-label" for="fs-name">{{ __('public.contact.field_name') }} *</label>
                                <input class="f-input @error('name') f-input--invalid @enderror" id="fs-name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
                                @error('name')<div class="f-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="f-field">
                                <label class="f-label" for="fs-email">{{ __('public.contact.field_email') }} *</label>
                                <input class="f-input @error('email') f-input--invalid @enderror" id="fs-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')<div class="f-error">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="f-field">
                            <label class="f-label" for="fs-organisation">{{ __('public.contact.field_organisation') }}</label>
                            <input class="f-input" id="fs-organisation" type="text" name="organisation" value="{{ old('organisation') }}" autocomplete="organization">
                        </div>

                        <div class="f-field">
                            <label class="f-label" for="fs-subject">{{ __('public.contact.field_subject') }} *</label>
                            <select class="f-input @error('subject') f-input--invalid @enderror" id="fs-subject" name="subject" required>
                                <option value="">{{ __('public.contact.field_subject_placeholder') }}</option>
                                <option value="technical" @selected(old('subject') === 'technical')>{{ __('public.contact.subject_technical') }}</option>
                                <option value="onboarding" @selected(old('subject') === 'onboarding')>{{ __('public.contact.subject_onboarding') }}</option>
                                <option value="partnership" @selected(old('subject') === 'partnership')>{{ __('public.contact.subject_partnership') }}</option>
                                <option value="billing" @selected(old('subject') === 'billing')>{{ __('public.contact.subject_billing') }}</option>
                                <option value="privacy" @selected(old('subject') === 'privacy')>{{ __('public.contact.subject_privacy') }}</option>
                                <option value="other" @selected(old('subject') === 'other')>{{ __('public.contact.subject_other') }}</option>
                            </select>
                            @error('subject')<div class="f-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="f-field">
                            <label class="f-label" for="fs-message">{{ __('public.contact.field_message') }} *</label>
                            <textarea class="f-input @error('message') f-input--invalid @enderror" id="fs-message" name="message" rows="6" required>{{ old('message') }}</textarea>
                            @error('message')<div class="f-error">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="f-btn f-btn--primary">
                            {{ __('public.contact.form_submit') }}
                            @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

@endsection
