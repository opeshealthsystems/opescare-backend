@extends('layouts.finder')

@section('title', __('finder_story.redirect.titles.security'))
@section('description', __('finder_story.redirect.meta_description'))

{{-- Platform-story route kept alive for old links; the content honestly
     hands institutional visitors to opescare.cloud and patients to the
     finders — plus a pointer to Finder's own privacy policy, since some
     visitors land here worried about their data, not about compliance. --}}
@push('head')
    @include('public.partials._od_pages_css')
@endpush

@section('content')
    @include('public.partials._platform_redirect', ['pageKey' => 'security', 'showPrivacy' => true])
@endsection
