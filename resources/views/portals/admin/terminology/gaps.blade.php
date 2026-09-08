@extends('layouts.portal')
@section('title', __('terminology.gaps_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.gaps_title'))

{{--
    Terminology reconciliation report.

    A READING SCREEN. There is not one form, not one button and not one POST on
    this page, and that is the design rather than an omission: everything below
    is a question for a person who is accountable for the answer, and clinical
    records on this platform are amended by that person through the mapping
    desk, never rewritten in bulk from a report. If a "fix all" control ever
    appears here, it is a bug.
--}}

@section('content')
<div class="page-head">
    <h2>{{ __('terminology.gaps_title') }}</h2>
    <div class="flex gap-2">
        <a href="{{ route('portals.admin.terminology.index') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="list-checks"></i> {{ __('terminology.dict_nav_queue') }}
        </a>
        <a href="{{ route('portals.admin.terminology.dictionary') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="library"></i> {{ __('terminology.dict_title') }}
        </a>
    </div>
</div>
<p class="page-subtitle mb-6">{{ __('terminology.gaps_subtitle') }}</p>

<div class="alert alert-info mb-6">
    <i data-lucide="eye"></i>
    <div>{{ __('terminology.gaps_readonly_notice') }}</div>
</div>

{{-- What is loaded to check against. A report that found nothing because no
     vocabulary exists reads exactly like a clean bill of health unless this
     is on the page. --}}
<p class="text-muted text-sm mb-6">
    {{ __('terminology.gaps_vocabularies') }}
    @forelse($vocabularies as $v)
        <span class="pill pill--muted">{{ $v['system'] }} · {{ number_format($v['active']) }}</span>
    @empty
        <span class="pill pill--warning">{{ __('terminology.gaps_no_vocabularies') }}</span>
    @endforelse
</p>

@foreach($findings as $f)
<div class="card mb-4">
    <div class="card-head">
        <h3 class="card-title">
            <code>{{ $f['table'] }}.{{ $f['column'] }}</code>
        </h3>

        @switch($f['state'])
            @case(\App\Services\Terminology\TerminologyReconciler::STATE_COLUMN_ABSENT)
                <span class="pill pill--muted">{{ __('terminology.gaps_state_absent') }}</span>
                @break
            @case(\App\Services\Terminology\TerminologyReconciler::STATE_EMPTY)
                <span class="pill pill--muted">{{ __('terminology.gaps_state_empty') }}</span>
                @break
            @case(\App\Services\Terminology\TerminologyReconciler::STATE_VOCABULARY_ABSENT)
                <span class="pill pill--warning">{{ __('terminology.gaps_state_unbacked') }}</span>
                @break
            @default
                @if(count($f['unresolved']) === 0 && count($f['duplicates']) === 0)
                    <span class="pill pill--success">{{ __('terminology.gaps_state_clean') }}</span>
                @else
                    <span class="pill pill--warning">{{ __('terminology.gaps_state_review') }}</span>
                @endif
        @endswitch
    </div>

    <div class="card-body">
        <p class="text-muted text-sm">
            {{ __('terminology.gaps_expected', ['systems' => implode(' · ', $f['systems'])]) }}
        </p>

        @if($f['state'] === \App\Services\Terminology\TerminologyReconciler::STATE_COLUMN_ABSENT)
            {{-- Absent, empty and unbacked are three different facts and the
                 screen must never let them read as one. --}}
            <p>{{ __('terminology.gaps_absent_body') }}</p>

        @elseif($f['state'] === \App\Services\Terminology\TerminologyReconciler::STATE_EMPTY)
            <p>{{ __('terminology.gaps_empty_body', ['rows' => number_format($f['rows'])]) }}</p>

        @else
            @if($f['state'] === \App\Services\Terminology\TerminologyReconciler::STATE_VOCABULARY_ABSENT)
                <p>{{ __('terminology.gaps_unbacked_body', [
                    'distinct' => number_format($f['distinct']),
                    'rows' => number_format($f['filled']),
                ]) }}</p>
            @else
                <p>{{ __('terminology.gaps_checked_body', [
                    'system' => $f['system'],
                    'resolved' => number_format($f['resolved_values']),
                    'distinct' => number_format($f['distinct']),
                    'resolved_rows' => number_format($f['resolved_rows']),
                    'rows' => number_format($f['filled']),
                ]) }}</p>
            @endif

            @if($f['state'] === \App\Services\Terminology\TerminologyReconciler::STATE_CHECKED && count($f['unresolved']) === 0)
                <p class="text-success">
                    <i data-lucide="check"></i> {{ __('terminology.gaps_all_resolve') }}
                </p>
            @endif

            @php
                $rows = $f['state'] === \App\Services\Terminology\TerminologyReconciler::STATE_VOCABULARY_ABSENT
                    ? $f['values']
                    : $f['unresolved'];
            @endphp

            @if(! empty($rows))
            <div class="table-wrap mt-4">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('terminology.gaps_col_value') }}</th>
                            <th>{{ __('terminology.gaps_col_rows') }}</th>
                            <th>{{ __('terminology.gaps_col_suggestions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $u)
                        <tr>
                            <td>
                                <code>{{ $u['value'] }}</code>
                                @if(! empty($u['heading_only']))
                                    <div class="text-muted text-sm">{{ __('terminology.gaps_heading_only') }}</div>
                                @endif
                            </td>
                            <td>{{ number_format($u['rows']) }}</td>
                            <td>
                                @forelse($u['suggestions'] ?? [] as $s)
                                    <div>
                                        <code>{{ $s['code'] }}</code> {{ $s['display'] }}
                                        <span class="pill pill--muted">{{ __('terminology.gaps_basis_' . $s['basis']) }}</span>
                                    </div>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @foreach($f['whitespace'] as $w)
                <p class="text-warning text-sm">
                    <i data-lucide="pilcrow"></i>
                    {{ __('terminology.gaps_padded', ['value' => $w['value'], 'rows' => number_format($w['rows'])]) }}
                </p>
            @endforeach

            @if(! empty($f['duplicates']))
            <div class="mt-4">
                <h4 class="text-sm mb-2">{{ __('terminology.gaps_duplicates_heading') }}</h4>
                @foreach($f['duplicates'] as $d)
                    <p class="text-sm">
                        <i data-lucide="git-compare"></i>
                        @foreach($d['members'] as $m)<code>{{ $m['value'] }}</code> ({{ number_format($m['rows']) }}){{ ! $loop->last ? ' · ' : '' }}@endforeach
                        {{-- Which rule decided this, spelled out. A curated
                             synonym and a one-character edit are not the same
                             kind of claim and the reader has to be able to
                             tell them apart before acting. --}}
                        <span class="pill pill--muted">{{ __('terminology.gaps_rule_' . $d['rule']) }}</span>
                    </p>
                @endforeach
                <p class="text-muted text-sm">{{ __('terminology.gaps_duplicates_caveat') }}</p>
            </div>
            @endif
        @endif
    </div>
</div>
@endforeach

<p class="text-muted text-sm">{{ __('terminology.gaps_footer') }}</p>
@endsection
