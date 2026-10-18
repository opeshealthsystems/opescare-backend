@extends('layouts.portal')

@section('title', __('triage.title'))
@section('breadcrumb_home', __('triage.title'))
@section('breadcrumb_home_url', route('portals.triage.index'))
@section('breadcrumb_section', __('triage.queue_title'))

@php
    // TriageAcuity::severity() speaks critical|warning|info|neutral; the house
    // badge palette spells the top one "danger". One map, here, so the queue
    // and the record page cannot drift apart.
    $badge = fn (?string $level) => 'badge badge-' . match (\App\Support\TriageAcuity::severity($level)) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'info'     => 'info',
        default    => 'neutral',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('triage.queue_title') }}</h1>
        <p class="page-subtitle">{{ __('triage.queue_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.triage.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('triage.new_triage') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="panel">
    @if($records->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="siren"></i></div>
                <h3>{{ __('triage.empty_queue') }}</h3>
                <p>{{ __('hospital_desk.empty_triage_hint') }}</p>
                <a href="{{ route('portals.triage.create') }}" class="btn btn-primary btn-sm mt-6"><i data-lucide="plus"></i> {{ __('hospital_desk.empty_triage_action') }}</a>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('triage.col_patient') }}</th>
                        <th>{{ __('triage.col_complaint') }}</th>
                        <th>{{ __('triage.col_priority') }}</th>
                        <th>{{ __('triage.col_assessed_by') }}</th>
                        <th>{{ __('triage.col_assessed_at') }}</th>
                        <th class="row-actions">{{ __('triage.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    @php
                        $record   = $row['record'];
                        $priority = $row['priority'];
                        $tPatient = $record->visit?->patient;
                        $tName    = trim(($tPatient->first_name ?? '') . ' ' . ($tPatient->last_name ?? ''));
                        $tKey     = \App\Support\TriageAcuity::labelKey($priority);
                    @endphp
                    <tr>
                        <td data-label="{{ __('triage.col_patient') }}">
                            <span class="td-strong">{{ $tName !== '' ? $tName : '—' }}</span>
                            @if($tPatient?->health_id)
                                <div class="td-muted">{{ $tPatient->health_id }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('triage.col_complaint') }}">
                            {{ \Illuminate\Support\Str::limit((string) $record->presenting_complaint, 90) ?: '—' }}
                        </td>
                        <td data-label="{{ __('triage.col_priority') }}">
                            @if($tKey)
                                <span class="{{ $badge($priority) }}">{{ __($tKey) }}</span>
                            @elseif($priority)
                                {{-- A legacy value from before this desk existed. Shown as
                                     what it literally is, never mapped onto a level. --}}
                                <span class="badge badge-neutral">{{ __('triage.acuity_legacy', ['value' => $priority]) }}</span>
                            @else
                                <span class="td-muted">{{ __('triage.acuity_none') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('triage.col_assessed_by') }}" class="td-muted">
                            {{ $record->nurse?->name ?? '—' }}
                        </td>
                        <td data-label="{{ __('triage.col_assessed_at') }}" class="td-muted">
                            {{ $record->created_at?->translatedFormat('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="row-actions" data-label="{{ __('triage.col_open') }}">
                            <a href="{{ route('portals.triage.show', $record->id) }}" class="btn btn-secondary btn-sm">
                                <i data-lucide="eye"></i> {{ __('triage.open_record') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="panel-body">{{ $records->links() }}</div>
        @endif
    @endif
</div>

@endsection
