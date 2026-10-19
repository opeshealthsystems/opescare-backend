@extends('layouts.finder')

{{-- OpesCare facts (/about/facts): a citable reference for answer engines and
     journalists. Facts only, all strings in lang/{en,fr}/facts.php. --}}

@section('title', __('facts.page_title'))
@section('description', __('facts.meta_description'))

@push('schema')
    @includeIf('partials.seo.entity-schema')
@endpush

@section('content')
@php $factsUpdated = \Illuminate\Support\Carbon::create(2026, 10, 19); @endphp
<section class="f-section">
    <div class="f-container">
        <div class="fs-narrow">
            <p class="f-small f-muted">{{ __('facts.eyebrow') }}</p>
            <h1>{{ __('facts.heading') }}</h1>
            <p>{{ __('facts.intro') }}</p>
            <p class="f-small f-muted"><time datetime="{{ $factsUpdated->toDateString() }}">{{ __('facts.updated', ['date' => $factsUpdated->translatedFormat('j F Y')]) }}</time></p>

            <h2>{{ __('facts.definition_title') }}</h2>
            <p>{{ __('facts.definition') }}</p>
            <p>{{ __('facts.definition_2') }}</p>

            <h2>{{ __('facts.table_title') }}</h2>
            <table class="f-table">
                <tbody>
                    <tr><th scope="row">{{ __('facts.row_name') }}</th><td>OpesCare</td></tr>
                    <tr><th scope="row">{{ __('facts.row_operator') }}</th><td>{{ __('facts.row_operator_v') }}</td></tr>
                    <tr><th scope="row">{{ __('facts.row_url') }}</th><td><a href="https://opescare.com">https://opescare.com</a></td></tr>
                    <tr><th scope="row">{{ __('facts.row_country') }}</th><td>{{ __('facts.row_country_v') }}</td></tr>
                    <tr><th scope="row">{{ __('facts.row_languages') }}</th><td>{{ __('facts.row_languages_v') }}</td></tr>
                    <tr><th scope="row">{{ __('facts.row_platforms') }}</th><td>{{ __('facts.row_platforms_v') }}</td></tr>
                    <tr><th scope="row">{{ __('facts.row_price') }}</th><td>{{ __('facts.row_price_v') }}</td></tr>
                    <tr><th scope="row">{{ __('facts.row_standards') }}</th><td>{{ __('facts.row_standards_v') }}</td></tr>
                    <tr><th scope="row">{{ __('facts.row_contact') }}</th><td><a href="mailto:support@opescare.com">support@opescare.com</a></td></tr>
                </tbody>
            </table>

            <h2>{{ __('facts.modules_title') }}</h2>
            <ul>
                <li><a href="{{ route('public.network.medicine-finder') }}">{{ __('facts.mod_drug') }}</a></li>
                <li><a href="{{ route('public.network.blood-finder') }}">{{ __('facts.mod_blood') }}</a></li>
                <li><a href="{{ route('public.network.lab-test-finder') }}">{{ __('facts.mod_lab') }}</a></li>
                <li><a href="{{ route('portals.patient.appointments.book') }}">{{ __('facts.mod_booking') }}</a></li>
                <li><a href="{{ route('public.care-map') }}">{{ __('facts.mod_directory') }}</a></li>
                <li>{{ __('facts.mod_health_id') }}</li>
                <li>{{ __('facts.mod_records') }}</li>
                <li>{{ __('facts.mod_payments') }}</li>
            </ul>

            <h2>{{ __('facts.not_title') }}</h2>
            <ul>
                <li>{{ __('facts.not_1') }}</li>
                <li>{{ __('facts.not_2') }}</li>
                <li>{{ __('facts.not_3') }}</li>
                <li>{{ __('facts.not_4') }}</li>
                <li>{{ __('facts.not_5') }}</li>
            </ul>

            <h2>{{ __('facts.join_title') }}</h2>
            <ul>
                <li><a href="{{ route('register.patient') }}">{{ __('facts.join_patients') }}</a></li>
                <li><a href="{{ route('register.organization') }}">{{ __('facts.join_facilities') }}</a></li>
                <li><a href="{{ route('public.developers') }}">{{ __('facts.join_developers') }}</a></li>
            </ul>

            <h2>{{ __('facts.privacy_title') }}</h2>
            <p>{{ __('facts.privacy_p') }} <a href="{{ route('public.privacy') }}">{{ __('finder_shell.footer.privacy') }}</a></p>

            <h2>{{ __('facts.glossary_title') }}</h2>
            <dl>
                @foreach (['health_id', 'mpi', 'consent', 'fhir', 'drug', 'blood', 'directory'] as $term)
                    <dt><strong>{{ __('facts.g_' . $term) }}</strong></dt>
                    <dd>{{ __('facts.g_' . $term . '_d') }}</dd>
                @endforeach
            </dl>

            <h2>{{ __('facts.contact_title') }}</h2>
            <ul>
                <li>{{ __('facts.contact_support') }}: <a href="mailto:support@opescare.com">support@opescare.com</a></li>
                <li>{{ __('facts.contact_privacy') }}: <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a></li>
                <li><a href="{{ route('public.contact') }}">{{ __('facts.contact_form') }}</a></li>
            </ul>
        </div>
    </div>
</section>
@endsection
