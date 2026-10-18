@extends('layouts.portal')
@section('title', __('admin_desk.mpi_pair_detail'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('mpi.title'))
@section('breadcrumb_home_url', route('portals.admin.mpi.index'))
@section('breadcrumb_section', __('admin_desk.mpi_pair_detail'))

@section('content')

@php $tabFor = match ($c->status) { 'merged' => 'merged', 'rejected' => 'rejected', default => 'pending' }; @endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('admin_desk.mpi_pair_detail') }}</h1>
        <p class="page-subtitle">{{ __('admin_desk.mpi_pair_subtitle') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.admin.mpi.index', ['tab' => $tabFor]) }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('admin_desk.back_to_list') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif

@if($c->status === 'pending_review')
    <div class="alert alert-warning mb-6">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('mpi.merge_warning') }}</div>
    </div>
@endif

@include('portals.admin.mpi._candidate', ['c' => $c, 'footprint' => $footprint])

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> {{ __('admin_desk.details') }}</h3></div>
    <div class="panel-body">
        <table class="kv-table">
            <tr><td>{{ __('admin_desk.reference') }}</td><td class="kv-strong adm-mono">{{ $c->id }}</td></tr>
            <tr><td>{{ __('mpi.f_status') }}</td><td class="kv-strong">{{ __('mpi.tab_' . $tabFor) }}</td></tr>
            <tr><td>{{ __('admin_desk.created_at') }}</td><td class="kv-strong">{{ $c->created_at?->isoFormat('lll') ?? '—' }}</td></tr>
            <tr><td>{{ __('admin_desk.reviewed_by') }}</td><td class="kv-strong">{{ $c->reviewer?->name ?? '—' }}</td></tr>
            <tr><td>{{ __('admin_desk.reviewed_at') }}</td><td class="kv-strong">{{ $c->reviewed_at?->isoFormat('lll') ?? '—' }}</td></tr>
        </table>
    </div>
</div>

@endsection
