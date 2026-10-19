@extends('layouts.portal')
@section('title', __('admin_desk.mpi_pair_detail'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')

@php $tabFor = match ($c->status) { 'merged' => 'merged', 'rejected' => 'rejected', default => 'pending' }; @endphp

<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('admin_desk.mpi_pair_detail') }}</h1>
        <p class="od-page__sub">{{ __('admin_desk.mpi_pair_subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.mpi.index', ['tab' => $tabFor]) }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
    </div>
</div>

@if(session('success'))<div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="alert-circle"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="alert-circle"></i><div class="oda-ga-alert__body">{{ $errors->first() }}</div></div>@endif

@if($c->status === 'pending_review')
    <div class="oda-ga-alert oda-ga-alert--warn">
        <i data-lucide="shield-alert"></i>
        <div class="oda-ga-alert__body">{{ __('mpi.merge_warning') }}</div>
    </div>
@endif

<div class="od-grid od-grid--main">
    <div>
        @include('portals.admin.mpi._candidate', ['c' => $c, 'footprint' => $footprint])
    </div>

    <section class="od-card">
        <div class="od-card__head"><h2 class="od-card__title">{{ __('admin_desk.details') }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt class="oda-dl__k">{{ __('admin_desk.reference') }}</dt><dd class="oda-dl__v oda-mono">{{ $c->id }}</dd>
                <dt class="oda-dl__k">{{ __('mpi.f_status') }}</dt><dd class="oda-dl__v">{{ __('mpi.tab_' . $tabFor) }}</dd>
                <dt class="oda-dl__k">{{ __('admin_desk.created_at') }}</dt><dd class="oda-dl__v">{{ $c->created_at?->isoFormat('lll') ?? '—' }}</dd>
                <dt class="oda-dl__k">{{ __('admin_desk.reviewed_by') }}</dt><dd class="oda-dl__v">{{ $c->reviewer?->name ?? '—' }}</dd>
                <dt class="oda-dl__k">{{ __('admin_desk.reviewed_at') }}</dt><dd class="oda-dl__v">{{ $c->reviewed_at?->isoFormat('lll') ?? '—' }}</dd>
            </dl>
        </div>
    </section>
</div>
</div>

@endsection
