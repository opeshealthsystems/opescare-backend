@extends('layouts.portal')

@section('title', __('triage.title'))

@php
    // TriageAcuity::severity() speaks critical|warning|info|neutral; the house
    // badge palette spells the top one "danger". One map, here, so the queue
    // and the record page cannot drift apart.
    $badge = fn (?string $level) => 'od-pill od-pill--' . match (\App\Support\TriageAcuity::severity($level)) {
        'critical' => 'urgent',
        'warning'  => 'pending',
        'info'     => 'normal',
        default    => 'neutral',
    };
    $odInitials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $odTone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('triage.queue_title') }}</h1>
        <p class="od-page__sub">{{ __('triage.queue_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.triage.create') }}" class="od-btn od-btn--primary">
        <i data-lucide="plus"></i> {{ __('triage.new_triage') }}
    </a>
</div>

@if(session('success'))<div class="odc-note odc-note--ready"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--urgent"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('triage.queue_title') }} <span class="od-card__count">{{ __('hospital_desk.c_count_paren', ['count' => $records->total()]) }}</span></h2></header>
    @if($records->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="siren"></i></div>
                <div class="od-empty__title">{{ __('triage.empty_queue') }}</div>
                <p class="od-empty__text">{{ __('hospital_desk.empty_triage_hint') }}</p>
                <a href="{{ route('portals.triage.create') }}" class="od-btn od-btn--primary od-btn--sm odc-mt"><i data-lucide="plus"></i> {{ __('hospital_desk.empty_triage_action') }}</a>
            </div>
        </div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('triage.col_patient') }}</th>
                        <th>{{ __('triage.col_complaint') }}</th>
                        <th>{{ __('triage.col_priority') }}</th>
                        <th>{{ __('triage.col_assessed_by') }}</th>
                        <th>{{ __('triage.col_assessed_at') }}</th>
                        <th class="od-table__actions">{{ __('triage.col_open') }}</th>
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
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $odTone($tName !== '' ? $tName : (string) $record->id) }}" aria-hidden="true">{{ $odInitials($tPatient?->first_name, $tPatient?->last_name) }}</span>
                                <div><a href="{{ route('portals.triage.show', $record->id) }}" class="od-table__name">{{ $tName !== '' ? $tName : '—' }}</a>@if($tPatient?->health_id)<span class="od-table__sub">{{ $tPatient->health_id }}</span>@endif</div>
                            </div>
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
                                <span class="od-pill od-pill--neutral">{{ __('triage.acuity_legacy', ['value' => $priority]) }}</span>
                            @else
                                <span class="od-muted">{{ __('triage.acuity_none') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('triage.col_assessed_by') }}" class="od-muted">
                            {{ $record->nurse?->name ?? '—' }}
                        </td>
                        <td data-label="{{ __('triage.col_assessed_at') }}" class="od-muted">
                            {{ $record->created_at?->translatedFormat('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="od-table__actions" data-label="{{ __('triage.col_open') }}">
                            <a href="{{ route('portals.triage.show', $record->id) }}" class="od-btn od-btn--secondary od-btn--sm">
                                <i data-lucide="eye"></i> {{ __('triage.open_record') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="od-card__body">{{ $records->links() }}</div>
        @endif
    @endif
</div>

</div>
@endsection
