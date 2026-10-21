@extends('layouts.auth')

@section('title', __('onboarding.org.title'))
@section('auth_bare', '1')
@section('body_class', 'ob-page')

@push('head')
<link rel="stylesheet" href="{{ asset('css/onboarding.css') }}?v={{ @filemtime(public_path('css/onboarding.css')) ?: '1' }}">
@endpush

@php
    // Split-layout facility onboarding (design 2026-10-21). Field names,
    // validation and the POST target are unchanged — see
    // PublicPageController@submitOrganizationRegister.
    $obSteps = ['type', 'details', 'person', 'services', 'documents', 'review'];
    $obTotal = count($obSteps);

    $obTypes = [
        'hospital'      => 'building-2',
        'clinic'        => 'stethoscope',
        'pharmacy'      => 'pill',
        'laboratory'    => 'flask-conical',
        'imaging'       => 'scan-line',
        'blood_bank'    => 'droplet',
        'public_health' => 'globe',
    ];
    $obSelectedType = old('org_type', request('type'));
    if (! array_key_exists((string) $obSelectedType, $obTypes)) {
        $obSelectedType = 'hospital';
    }

    $obTypeMsg = [
        'hospital'      => __('onboarding.org.variants.hospital_msg'),
        'clinic'        => __('onboarding.org.variants.clinic_msg'),
        'pharmacy'      => __('onboarding.org.variants.pharmacy_msg'),
        'laboratory'    => __('onboarding.org.variants.laboratory_msg'),
        'imaging'       => __('onboarding_flow.services.imaging_msg'),
        'blood_bank'    => __('onboarding_flow.services.blood_bank_msg'),
        'public_health' => __('onboarding.org.variants.public_health_msg'),
    ];
    $obTypeNotice = [
        'hospital'      => __('onboarding.org.variants.hospital_notice'),
        'clinic'        => __('onboarding.org.variants.clinic_notice'),
        'pharmacy'      => __('onboarding.org.variants.pharmacy_notice'),
        'laboratory'    => __('onboarding.org.variants.laboratory_notice'),
        'imaging'       => __('onboarding_flow.services.imaging_notice'),
        'blood_bank'    => __('onboarding_flow.services.blood_bank_notice'),
        'public_health' => __('onboarding.org.variants.public_health_notice'),
    ];

    // Services per type. Values are the ones the form always sent.
    $obServices = [
        'clinical'   => ['types' => 'hospital clinic public_health', 'items' => [
            'outpatient' => __('ui_sweep.register.svc_outpatient'),
            'emergency'  => __('ui_sweep.register.svc_emergency'),
            'inpatient'  => __('ui_sweep.register.svc_inpatient'),
            'laboratory' => __('ui_sweep.register.type_laboratory'),
            'pharmacy'   => __('ui_sweep.register.type_pharmacy'),
            'radiology'  => __('ui_sweep.register.svc_radiology'),
        ]],
        'pharmacy'   => ['types' => 'pharmacy', 'items' => [
            'dispensing'   => __('ui_sweep.register.svc_dispensing'),
            'availability' => __('ui_sweep.register.svc_availability'),
            'reservation'  => __('ui_sweep.register.svc_reservation'),
        ]],
        'laboratory' => ['types' => 'laboratory', 'items' => [
            'collection'  => __('ui_sweep.register.svc_collection'),
            'entry'       => __('ui_sweep.register.svc_entry'),
            'validation'  => __('ui_sweep.register.svc_validation'),
            'integration' => __('ui_sweep.register.svc_integration'),
        ]],
        'imaging'    => ['types' => 'imaging', 'items' => [
            'radiology' => __('ui_sweep.register.svc_radiology'),
            'reporting' => __('onboarding_flow.services.svc_reporting'),
        ]],
        'blood_bank' => ['types' => 'blood_bank', 'items' => [
            'blood_stock'    => __('onboarding_flow.services.svc_blood_stock'),
            'blood_requests' => __('onboarding_flow.services.svc_blood_requests'),
        ]],
    ];
    $obOldServices = (array) old('services', []);

    // Help line from config only — never invented.
    $obHelpParts = array_values(array_filter(array_map("trim", [
        (string) config('opescare.support_phone', ''),
        (string) config('mail.support_address', ''),
    ]), fn ($v) => $v !== '' && ! preg_match('/no-?reply/i', $v)));

    // Phones are typed without the +237 the form adds on submit.
    $obLocalPhone = fn ($v) => trim((string) preg_replace('/^\+?237\s*/', '', (string) $v));
@endphp

@section('content')
<div class="ob" data-ob
     data-ob-total="{{ $obTotal }}"
     data-ob-step-of="{{ __('onboarding_flow.step_of') }}"
     data-ob-required="{{ __('onboarding_flow.foot.required') }}"
     data-ob-saved="{{ __('onboarding_flow.foot.saved') }}"
     data-ob-not-saved="{{ __('onboarding_flow.foot.not_saved') }}"
     data-ob-not-given="{{ __('onboarding_flow.review.not_given') }}"
     data-ob-files="{{ __('onboarding_flow.review.files_count') }}"
     @isset($success_application) data-ob-success="1" @endisset>

    {{-- ===== Left rail ===== --}}
    <aside class="ob-rail">
        <a href="{{ route('public.landing') }}" class="ob-logo" aria-label="{{ __('finder_shell.a11y.home') }}">
            <img src="{{ asset('images/health-id/seal.webp') }}" alt="" width="30" height="30">OpesCare
        </a>
        <h2>{{ __('onboarding_flow.rail.title') }}</h2>
        <p class="ob-lead">{{ __('onboarding_flow.rail.lead') }}</p>

        <ol class="ob-steps" aria-label="{{ __('onboarding_flow.rail.steps_label') }}">
            @foreach($obSteps as $i => $key)
                @php $n = $i + 1; $done = isset($success_application) || $n < 1; $now = ! isset($success_application) && $n === 1; @endphp
                <li data-ob-rail="{{ $n }}" @class(['is-done' => $done, 'is-now' => $now, 'is-todo' => ! $done && ! $now]) @if($now) aria-current="step" @endif>
                    <div class="ob-dot"><span class="ob-num">{{ $n }}</span>@include('partials.finder.icon', ['name' => 'check', 'size' => 14])</div>
                    <div><b>{{ __('onboarding_flow.steps.' . $key . '.title') }}</b><span class="ob-step-hint" @if($key === 'type') data-ob-type-hint @endif>{{ __('onboarding_flow.steps.' . $key . '.hint') }}</span></div>
                </li>
            @endforeach
        </ol>

        <div class="ob-rail-foot">
            <div>@include('partials.finder.icon', ['name' => 'shield-check', 'size' => 16]){{ __('onboarding_flow.rail.trust_review') }}</div>
            <div>@include('partials.finder.icon', ['name' => 'lock', 'size' => 16]){{ __('onboarding_flow.rail.trust_private') }}</div>
            <div>@include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                @if(count($obHelpParts))
                    <span>{{ __('onboarding_flow.rail.help', ['contact' => implode(' · ', $obHelpParts)]) }}</span>
                @else
                    <a href="{{ route('public.contact') }}">{{ __('onboarding_flow.rail.help_page') }}</a>
                @endif
            </div>
        </div>
    </aside>

    {{-- ===== Main pane ===== --}}
    <section class="ob-main">

        {{-- Phone header (shown <768px) --}}
        <div class="ob-mhead">
            <div class="ob-mhead__row">
                <div>
                    <b data-ob-mtitle>{{ isset($success_application) ? __('onboarding_flow.success.eyebrow') : __('onboarding_flow.steps.type.title') }}</b>
                    <div class="ob-mhead__count" data-ob-count>{{ __('onboarding_flow.step_of', ['current' => isset($success_application) ? $obTotal : 1, 'total' => $obTotal]) }}</div>
                </div>
                <nav class="ob-lang" aria-label="{{ __('finder_shell.a11y.language') }}">
                    <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) lang="en" hreflang="en">EN</a>
                    <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) lang="fr" hreflang="fr">FR</a>
                </nav>
            </div>
            <div class="ob-segs" aria-hidden="true">
                @for($n = 1; $n <= $obTotal; $n++)
                    <i data-ob-seg="{{ $n }}" @class(['is-done' => isset($success_application), 'is-now' => ! isset($success_application) && $n === 1])></i>
                @endfor
            </div>
        </div>

        {{-- Desktop top bar --}}
        <div class="ob-top">
            <div class="ob-meta">
                <span data-ob-count>{{ __('onboarding_flow.step_of', ['current' => isset($success_application) ? $obTotal : 1, 'total' => $obTotal]) }}</span>
                <div class="ob-bar" role="progressbar" aria-label="{{ __('onboarding_flow.progress') }}" aria-valuemin="0" aria-valuemax="{{ $obTotal }}" aria-valuenow="{{ isset($success_application) ? $obTotal : 1 }}" data-ob-bar>
                    <i style="width:{{ isset($success_application) ? 100 : round(100 / $obTotal) }}%"></i>
                </div>
            </div>
            <nav class="ob-lang" aria-label="{{ __('finder_shell.a11y.language') }}">
                <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) lang="en" hreflang="en">EN</a>
                <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) lang="fr" hreflang="fr">FR</a>
            </nav>
        </div>

        @if(isset($success_application))
            {{-- ===== Success ===== --}}
            <div class="ob-body ob-success" id="f-main-content">
                <div class="ob-success__badge">@include('partials.finder.icon', ['name' => 'circle-check', 'size' => 28])</div>
                <div class="ob-eyebrow">{{ __('onboarding_flow.success.eyebrow') }}</div>
                <h1>{{ __('onboarding.org.success.title') }}</h1>
                <p class="ob-sub">{{ __('onboarding.org.success.desc') }}</p>

                <dl class="ob-review">
                    <div><dt>{{ __('ui_sweep.register.org_name') }}</dt><dd>{{ $legal_name }}</dd></div>
                    <div><dt>{{ __('ui_sweep.register.reference_id') }}</dt><dd>{{ $ref_code }}</dd></div>
                    <div><dt>{{ __('ui_sweep.register.submission_date') }}</dt><dd>{{ now()->format('Y-m-d H:i') }}</dd></div>
                    <div><dt>{{ __('ui_sweep.register.current_status') }}</dt><dd>{{ __('ui_sweep.register.under_review') }}</dd></div>
                </dl>

                <a href="{{ route('public.landing') }}" class="ob-btn ob-btn--primary">
                    @include('partials.finder.icon', ['name' => 'arrow-left', 'size' => 18]){{ __('onboarding.org.success.cta') }}
                </a>
            </div>
        @else
        <form action="{{ route('register.organization.submit') }}" method="POST" enctype="multipart/form-data" class="ob-form" data-ob-form novalidate>
            @csrf
            <div class="ob-body">

                @if($errors->any())
                    <div class="ob-alert" role="alert">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                        <div>{{ __('onboarding_flow.foot.errors') }}
                            <ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                        </div>
                    </div>
                @endif

                <div class="ob-alert" role="alert" data-ob-invalid hidden>
                    @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                    <div>{{ __('onboarding_flow.foot.required') }}</div>
                </div>

                {{-- ===== 1. Facility type ===== --}}
                <div class="ob-pane is-active" data-ob-pane="1" data-ob-title="{{ __('onboarding_flow.steps.type.title') }}" tabindex="-1">
                    <div class="ob-eyebrow">{{ __('onboarding_flow.type.eyebrow') }}</div>
                    <h1>{{ __('onboarding_flow.type.h1') }}</h1>
                    <p class="ob-sub">{{ __('onboarding_flow.type.sub') }}</p>

                    <fieldset>
                        <legend class="ob-sr">{{ __('onboarding_flow.type.legend') }}</legend>
                        <div class="ob-types">
                            @foreach($obTypes as $value => $icon)
                                <label class="ob-type">
                                    <input type="radio" name="org_type" value="{{ $value }}" required
                                           data-ob-msg="{{ $obTypeMsg[$value] }}" data-ob-notice="{{ $obTypeNotice[$value] }}"
                                           data-ob-label="{{ __('onboarding_flow.type.cards.' . $value . '.title') }}"
                                           @checked($obSelectedType === $value)>
                                    <span class="ob-type__card">
                                        <span class="ob-ic">@include('partials.finder.icon', ['name' => $icon, 'size' => 22])</span>
                                        <b>{{ __('onboarding_flow.type.cards.' . $value . '.title') }}</b>
                                        <span class="ob-type__desc">{{ __('onboarding_flow.type.cards.' . $value . '.desc') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="ob-note" data-ob-type-msg>@include('partials.finder.icon', ['name' => 'info', 'size' => 18])<div>{{ $obTypeMsg[$obSelectedType] }}</div></div>

                    <div class="ob-paths">
                        <a class="ob-path" href="{{ route('public.care-map') }}">
                            <span class="ob-ic">@include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])</span>
                            <span><b>{{ __('onboarding_flow.type.claim_title') }}</b><span class="ob-path__desc">{{ __('onboarding_flow.type.claim_desc') }}</span></span>
                        </a>
                        <a class="ob-path" href="{{ route('public.solutions.request', ['kind' => 'donation']) }}">
                            <span class="ob-ic">@include('partials.finder.icon', ['name' => 'gift', 'size' => 18])</span>
                            <span><b>{{ __('onboarding_flow.type.free_title') }}</b><span class="ob-path__desc">{{ __('onboarding_flow.type.free_desc') }}</span></span>
                        </a>
                    </div>
                </div>

                {{-- ===== 2. Facility details ===== --}}
                <div class="ob-pane" data-ob-pane="2" data-ob-title="{{ __('onboarding_flow.steps.details.title') }}" tabindex="-1">
                    <div class="ob-eyebrow">{{ __('onboarding_flow.details.eyebrow') }}</div>
                    <h1>{{ __('onboarding_flow.details.h1') }}</h1>
                    <p class="ob-sub">{{ __('onboarding_flow.details.sub') }}</p>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.details.identity') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.details.identity_help') }}</p>
                        <div class="ob-grid">
                            <div class="ob-full">
                                <label class="ob-label" for="legal_name">{{ __('onboarding.org.legal_name') }} <em>*</em></label>
                                <input type="text" id="legal_name" name="legal_name" class="ob-input" maxlength="200" required autocomplete="organization" value="{{ old('legal_name') }}">
                                @error('legal_name')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="ob-full">
                                <label class="ob-label" for="trade_name">{{ __('onboarding.org.trade_name') }}</label>
                                <input type="text" id="trade_name" name="trade_name" class="ob-input" maxlength="200" value="{{ old('trade_name') }}">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.details.registration') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.details.registration_help') }}</p>
                        <div class="ob-grid">
                            <div>
                                <label class="ob-label" for="license_number">{{ __('onboarding.org.license_number') }} <em>*</em></label>
                                <input type="text" id="license_number" name="license_number" class="ob-input" maxlength="80" required value="{{ old('license_number') }}" aria-describedby="license_help">
                                <div class="ob-help" id="license_help">{{ __('onboarding_flow.details.license_help') }}</div>
                                @error('license_number')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="ob-label" for="reg_number">{{ __('onboarding.org.reg_number') }} <em>*</em></label>
                                <input type="text" id="reg_number" name="reg_number" class="ob-input" maxlength="80" required value="{{ old('reg_number') }}">
                                @error('reg_number')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.details.contact') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.details.contact_help') }}</p>
                        <div class="ob-grid">
                            <div class="ob-full">
                                <label class="ob-label" for="address">{{ __('onboarding.org.address') }} <em>*</em></label>
                                <input type="text" id="address" name="address" class="ob-input" maxlength="300" required autocomplete="street-address" placeholder="{{ __('onboarding_flow.details.address_ph') }}" value="{{ old('address') }}">
                                @error('address')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="ob-label" for="main_phone">{{ __('onboarding.org.main_phone') }} <em>*</em></label>
                                <div class="ob-prefix"><span>+237</span><input type="tel" id="main_phone" name="main_phone" class="ob-input" maxlength="25" required inputmode="tel" data-ob-phone placeholder="6XX XX XX XX" value="{{ $obLocalPhone(old('main_phone')) }}" aria-describedby="main_phone_help"></div>
                                <div class="ob-help" id="main_phone_help">{{ __('onboarding_flow.details.phone_help') }}</div>
                                @error('main_phone')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="ob-label" for="main_email">{{ __('onboarding.org.main_email') }} <em>*</em></label>
                                <input type="email" id="main_email" name="main_email" class="ob-input" maxlength="180" required autocomplete="email" value="{{ old('main_email') }}">
                                @error('main_email')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <div class="ob-note">@include('partials.finder.icon', ['name' => 'info', 'size' => 18])<div>{{ __('onboarding_flow.details.match_note') }}</div></div>
                </div>

                {{-- ===== 3. Responsible person ===== --}}
                <div class="ob-pane" data-ob-pane="3" data-ob-title="{{ __('onboarding_flow.steps.person.title') }}" tabindex="-1">
                    <div class="ob-eyebrow">{{ __('onboarding_flow.person.eyebrow') }}</div>
                    <h1>{{ __('onboarding_flow.person.h1') }}</h1>
                    <p class="ob-sub">{{ __('onboarding_flow.person.sub') }}</p>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.person.legend') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.person.legend_help') }}</p>
                        <div class="ob-grid">
                            <div>
                                <label class="ob-label" for="contact_name">{{ __('onboarding.org.contact_name') }} <em>*</em></label>
                                <input type="text" id="contact_name" name="contact_name" class="ob-input" maxlength="120" required autocomplete="name" value="{{ old('contact_name') }}">
                                @error('contact_name')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="ob-label" for="contact_role">{{ __('onboarding.org.contact_role') }} <em>*</em></label>
                                <input type="text" id="contact_role" name="contact_role" class="ob-input" maxlength="100" required autocomplete="organization-title" placeholder="{{ __('onboarding_flow.person.role_ph') }}" value="{{ old('contact_role') }}">
                                @error('contact_role')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="ob-label" for="contact_email">{{ __('onboarding.org.contact_email') }} <em>*</em></label>
                                <input type="email" id="contact_email" name="contact_email" class="ob-input" maxlength="180" required autocomplete="email" value="{{ old('contact_email') }}">
                                @error('contact_email')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="ob-label" for="contact_phone">{{ __('onboarding.org.contact_phone') }} <em>*</em></label>
                                <div class="ob-prefix"><span>+237</span><input type="tel" id="contact_phone" name="contact_phone" class="ob-input" maxlength="25" required inputmode="tel" autocomplete="tel-national" data-ob-phone placeholder="6XX XX XX XX" value="{{ $obLocalPhone(old('contact_phone')) }}"></div>
                                @error('contact_phone')<div class="ob-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>
                </div>

                {{-- ===== 4. Services (+ integration context) ===== --}}
                <div class="ob-pane" data-ob-pane="4" data-ob-title="{{ __('onboarding_flow.steps.services.title') }}" tabindex="-1">
                    <div class="ob-eyebrow">{{ __('onboarding_flow.services.eyebrow') }}</div>
                    <h1>{{ __('onboarding_flow.services.h1') }}</h1>
                    <p class="ob-sub">{{ __('onboarding_flow.services.sub') }}</p>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.services.legend') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.services.legend_help') }}</p>
                        @foreach($obServices as $group => $block)
                            <div class="ob-checks" data-ob-services="{{ $block['types'] }}" @unless(in_array($obSelectedType, explode(' ', $block['types']), true)) hidden @endunless>
                                @foreach($block['items'] as $val => $label)
                                    <label class="ob-check"><input type="checkbox" name="services[]" value="{{ $val }}" data-ob-svc-label="{{ $label }}" @checked(in_array($val, $obOldServices, true))> <span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        @endforeach
                    </fieldset>

                    <div class="ob-note ob-note--warn" data-ob-type-notice>@include('partials.finder.icon', ['name' => 'shield-alert', 'size' => 18])<div>{{ $obTypeNotice[$obSelectedType] }}</div></div>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.services.systems') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.services.systems_help') }}</p>
                        <div class="ob-grid">
                            <div>
                                <span class="ob-label" id="has_software_lbl">{{ __('onboarding.org.software_sync_lbl') }}</span>
                                <div class="ob-seg" role="radiogroup" aria-labelledby="has_software_lbl">
                                    <label><input type="radio" name="has_software" value="yes" @checked(old('has_software') === 'yes')> {{ __('ui_sweep.register.yes') }}</label>
                                    <label><input type="radio" name="has_software" value="no" @checked(old('has_software', 'no') === 'no')> {{ __('ui_sweep.register.no') }}</label>
                                </div>
                            </div>
                            <div>
                                <span class="ob-label" id="need_api_lbl">{{ __('onboarding.org.need_api_lbl') }}</span>
                                <div class="ob-seg" role="radiogroup" aria-labelledby="need_api_lbl">
                                    <label><input type="radio" name="need_api" value="yes" @checked(old('need_api') === 'yes')> {{ __('ui_sweep.register.yes') }}</label>
                                    <label><input type="radio" name="need_api" value="no" @checked(old('need_api', 'no') === 'no')> {{ __('ui_sweep.register.no') }}</label>
                                </div>
                            </div>
                            <div class="ob-full">
                                <label class="ob-label" for="current_software">{{ __('onboarding.org.software_name') }}</label>
                                <input type="text" id="current_software" name="current_software" class="ob-input" maxlength="200" placeholder="{{ __('onboarding_flow.services.software_ph') }}" value="{{ old('current_software') }}">
                            </div>
                            <div>
                                <label class="ob-label" for="est_users">{{ __('onboarding.org.est_users') }} <em>*</em></label>
                                <input type="number" id="est_users" name="est_users" class="ob-input" min="1" required value="{{ old('est_users', 25) }}">
                            </div>
                            <div>
                                <label class="ob-label" for="est_patients">{{ __('onboarding.org.est_patients') }} <em>*</em></label>
                                <input type="number" id="est_patients" name="est_patients" class="ob-input" min="0" required value="{{ old('est_patients', 500) }}">
                            </div>
                            <div class="ob-full">
                                <label class="ob-check"><input type="checkbox" id="sync_stock" name="sync_stock" value="1" @checked(old('sync_stock'))> <span>{{ __('ui_sweep.register.bridge_sync') }}</span></label>
                            </div>
                        </div>
                    </fieldset>
                </div>

                {{-- ===== 5. Documents ===== --}}
                <div class="ob-pane" data-ob-pane="5" data-ob-title="{{ __('onboarding_flow.steps.documents.title') }}" tabindex="-1">
                    <div class="ob-eyebrow">{{ __('onboarding_flow.documents.eyebrow') }}</div>
                    <h1>{{ __('onboarding_flow.documents.h1') }}</h1>
                    <p class="ob-sub">{{ __('onboarding_flow.documents.sub') }}</p>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.documents.legend') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.documents.legend_help') }}</p>
                        <div class="ob-grid">
                            <div class="ob-full">
                                <label class="ob-label" for="doc_business">{{ __('onboarding.org.doc_business') }} <em>*</em></label>
                                <div class="ob-file"><input type="file" id="doc_business" name="doc_business" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-ob-file></div>
                                <div class="ob-help">{{ __('onboarding.common.file_hint') }}</div>
                            </div>
                            <div class="ob-full">
                                <label class="ob-label" for="doc_license">{{ __('onboarding.org.doc_license') }} <em>*</em></label>
                                <div class="ob-file"><input type="file" id="doc_license" name="doc_license" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-ob-file></div>
                                <div class="ob-help">{{ __('onboarding.common.file_hint') }}</div>
                            </div>
                        </div>
                    </fieldset>

                    <div class="ob-note">@include('partials.finder.icon', ['name' => 'lock', 'size' => 18])<div>{{ __('onboarding_flow.documents.not_saved') }}</div></div>
                </div>

                {{-- ===== 6. Review and submit ===== --}}
                <div class="ob-pane" data-ob-pane="6" data-ob-title="{{ __('onboarding_flow.steps.review.title') }}" tabindex="-1">
                    <div class="ob-eyebrow">{{ __('onboarding_flow.review.eyebrow') }}</div>
                    <h1>{{ __('onboarding_flow.review.h1') }}</h1>
                    <p class="ob-sub">{{ __('onboarding_flow.review.sub') }}</p>

                    <fieldset data-ob-js-only>
                        <legend>{{ __('onboarding_flow.review.summary') }}</legend>
                        <dl class="ob-review" data-ob-review>
                            <div><dt>{{ __('onboarding_flow.review.l_type') }}</dt><dd data-ob-r="type"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_name') }}</dt><dd data-ob-r="legal_name"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_trade') }}</dt><dd data-ob-r="trade_name"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_licence') }}</dt><dd data-ob-r="license_number"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_reg') }}</dt><dd data-ob-r="reg_number"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_address') }}</dt><dd data-ob-r="address"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_phone') }}</dt><dd data-ob-r="main_phone"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_email') }}</dt><dd data-ob-r="main_email"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_person') }}</dt><dd data-ob-r="person"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_contact') }}</dt><dd data-ob-r="person_contact"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_services') }}</dt><dd data-ob-r="services"></dd></div>
                            <div><dt>{{ __('onboarding_flow.review.l_documents') }}</dt><dd data-ob-r="documents"></dd></div>
                        </dl>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('onboarding_flow.review.agree') }}</legend>
                        <p class="ob-legend-sub">{{ __('onboarding_flow.review.agree_help') }}</p>
                        <div class="ob-checks ob-checks--one">
                            <label class="ob-check"><input type="checkbox" id="accuracy_confirm" name="accuracy_confirm" required> <span>{{ __('onboarding.org.terms_accuracy') }} <em class="ob-req">*</em></span></label>
                            <label class="ob-check"><input type="checkbox" id="review_confirm" name="review_confirm" required> <span>{{ __('onboarding.org.terms_review') }} <em class="ob-req">*</em></span></label>
                            <label class="ob-check"><input type="checkbox" id="terms_agree" name="terms_agree" required> <span>{{ __('onboarding.common.accept_terms') }} <em class="ob-req">*</em></span></label>
                        </div>
                    </fieldset>

                    <div class="ob-note">@include('partials.finder.icon', ['name' => 'clock', 'size' => 18])<div>{{ __('onboarding_flow.review.timing') }}</div></div>
                </div>
            </div>

            {{-- Sticky footer --}}
            <div class="ob-foot">
                <span class="ob-saved" data-ob-saved-line aria-live="polite"></span>
                <div class="ob-foot__btns">
                    <button type="button" class="ob-btn" data-ob-prev hidden>
                        @include('partials.finder.icon', ['name' => 'arrow-left', 'size' => 18]){{ __('onboarding_flow.foot.back') }}
                    </button>
                    <button type="button" class="ob-btn ob-btn--primary" data-ob-next>
                        {{ __('onboarding_flow.foot.continue') }}@include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 18])
                    </button>
                    <button type="submit" class="ob-btn ob-btn--primary" data-ob-submit>
                        @include('partials.finder.icon', ['name' => 'check', 'size' => 18]){{ __('onboarding_flow.foot.submit') }}
                    </button>
                </div>
            </div>
        </form>
        @endif
    </section>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/onboarding.js') }}?v={{ @filemtime(public_path('js/onboarding.js')) ?: '1' }}" @cspNonce defer></script>
@endsection
