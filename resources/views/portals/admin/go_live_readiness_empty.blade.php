@extends('layouts.portal')
@section('title', __('public.adm_golive_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <span>{{ __('public.adm_golive_breadcrumb') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_golive_title') }}</h2>
</div>

<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="rocket"></i></div>
        <h3>{{ __('public.adm_golive_empty_h3') }}</h3>
        <p>{{ __('public.adm_golive_empty_p') }}</p>
    </div>
</div>

@endsection
