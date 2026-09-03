@extends('layouts.finder')

@section('title', __('finder_story.redirect.titles.public_health'))
@section('description', __('finder_story.redirect.meta_description'))

{{-- Platform-story route kept alive for old links; the content honestly
     hands institutional visitors to opescare.cloud and patients to the
     finders. See public/partials/_platform_redirect. --}}
@section('content')
    @include('public.partials._platform_redirect', ['pageKey' => 'public_health'])
@endsection
