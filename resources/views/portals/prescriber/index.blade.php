@extends('layouts.portal')
@section('title', __('prescriber.title'))
@section('breadcrumb_home', __('prescriber.title'))
@section('breadcrumb_home_url', route('portals.prescriber.index'))
@section('breadcrumb_section', __('prescriber.nav'))

@php
    // The statuses a prescription can actually carry, in the order a prescriber
    // scans them. Labels come from prescriber.st_*; colours follow the house
    // badge palette (info = live, success = done, warning = part-way, neutral = closed).
    $rxStatuses = ['active', 'dispensed', 'partially_dispensed', 'voided', 'expired', 'cancelled'];
    $rxColor = fn ($s) => match ($s) {
        'active'              => 'info',
        'dispensed'           => 'success',
        'partially_dispensed' => 'warning',
        default               => 'neutral',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('prescriber.title') }}</h1>
        <p class="page-subtitle">{{ __('prescriber.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.prescriber.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('prescriber.new') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="filter-bar">
    <a href="{{ route('portals.prescriber.index') }}"
       class="btn btn-sm {{ $status ? 'btn-secondary' : 'btn-primary' }}"
       @if(! $status) aria-current="page" @endif>{{ __('prescriber.filter_all') }}</a>
    @foreach($rxStatuses as $rxStatus)
        <a href="{{ route('portals.prescriber.index', ['status' => $rxStatus]) }}"
           class="btn btn-sm {{ $status === $rxStatus ? 'btn-primary' : 'btn-secondary' }}"
           @if($status === $rxStatus) aria-current="page" @endif>{{ __('prescriber.st_' . $rxStatus) }}</a>
    @endforeach
</div>

<div class="panel">
    @if($prescriptions->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="file-text"></i></div>
                <h3>{{ __('prescriber.empty') }}</h3>
                <p>{{ __('prescriber.empty_desc') }}</p>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('prescriber.col_patient') }}</th>
                        <th>{{ __('prescriber.col_items') }}</th>
                        <th>{{ __('prescriber.col_status') }}</th>
                        <th>{{ __('prescriber.col_issued') }}</th>
                        <th class="row-actions">{{ __('prescriber.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($prescriptions as $rx)
                    @php
                        $rxPatient = $rx->patient;
                        $rxName    = trim(($rxPatient->first_name ?? '') . ' ' . ($rxPatient->last_name ?? ''));
                        $rxFirst   = $rx->items->first();
                        $rxExtra   = max($rx->items->count() - 1, 0);
                    @endphp
                    <tr>
                        <td data-label="{{ __('prescriber.col_patient') }}">
                            <span class="td-strong">{{ $rxName !== '' ? $rxName : '—' }}</span>
                            @if($rxPatient?->health_id)
                                <div class="td-muted">{{ $rxPatient->health_id }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('prescriber.col_items') }}">
                            @if($rxFirst)
                                <div>{{ $rxFirst->drug_name }}</div>
                                @if($rxExtra > 0)
                                    <div class="td-muted">+{{ $rxExtra }} {{ __('public.pharmacy_portal.lbl_items') }}</div>
                                @endif
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('prescriber.col_status') }}">
                            <span class="badge badge-{{ $rxColor($rx->status) }}">
                                @if(\Illuminate\Support\Facades\Lang::has('prescriber.st_' . $rx->status))
                                    {{ __('prescriber.st_' . $rx->status) }}
                                @else
                                    @enum($rx->status)
                                @endif
                            </span>
                        </td>
                        <td data-label="{{ __('prescriber.col_issued') }}" class="td-muted">
                            {{ $rx->prescribed_at?->translatedFormat('d M Y') ?? '—' }}
                            @if(filled($rx->reference))
                                <div class="td-muted" data-rx-code>{{ $rx->reference }}</div>
                            @endif
                        </td>
                        <td class="row-actions" data-label="{{ __('prescriber.view') }}">
                            <a href="{{ route('portals.prescriber.show', $rx->id) }}" class="btn btn-secondary btn-sm">
                                <i data-lucide="eye"></i> {{ __('prescriber.view') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($prescriptions->hasPages())
            <div class="panel-body">{{ $prescriptions->links() }}</div>
        @endif
    @endif
</div>

@endsection
