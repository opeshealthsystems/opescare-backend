@extends('layouts.portal')
@section('title', __('terminology.gaps_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.gaps_title'))

{{--
    Terminology reconciliation report.

    A READING SCREEN WITH EXACTLY ONE ACTION, AND THAT ACTION IS NOT A FIX.

    The only POST on this page raises a suggestion as a PENDING
    `CodeSystemMapping` — a proposal a reviewer then approves or rejects on the
    terminology queue, and which only an approved mapping's separate apply step
    could ever put onto a record. Nothing here writes to `medicines`,
    `diagnoses`, `lab_results` or `immunization_records`, and nothing here ever
    may: clinical records on this platform are amended by an accountable
    person, never rewritten in bulk from a report. If a control appears on this
    page that changes a clinical value — however guarded, however confirmed —
    it is a bug.

    Findings that cannot become a proposal say so, in words, where the button
    would otherwise be. A missing control whose absence the reader cannot
    explain is indistinguishable from one somebody forgot to build.
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
    <div>
        <div>{{ __('terminology.gaps_readonly_notice') }}</div>
        <div class="mt-2">{{ __('terminology.propose_intro') }}</div>
        {{-- Said out loud rather than discovered later: a proposal for an ATC
             or CVX code is a decision that gets recorded and kept, not a code
             that will land on a row. Only laboratory rows carrying LOINC have
             a target column the applier may write. --}}
        <div class="mt-2 text-sm">{{ __('terminology.propose_review_only') }}</div>
    </div>
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
                                @php $already = $existingProposals[$f['key']][$u['value']] ?? null; @endphp

                                @if($already)
                                    {{-- One live proposal per value, stated ONCE and above the
                                         suggestions rather than repeated beside each of them.
                                         A second proposal would put two contradictory mappings
                                         in front of the same approver, so every button for this
                                         value becomes a way to read the first one instead. --}}
                                    <a href="{{ route('portals.admin.terminology.show', $already['id']) }}" class="btn btn-ghost btn-xs mb-2">
                                        <i data-lucide="external-link"></i>
                                        {{ __('terminology.propose_existing', ['code' => $already['code'], 'status' => __('terminology.status_' . $already['status'])]) }}
                                    </a>
                                @endif

                                @forelse($u['suggestions'] ?? [] as $s)
                                    <div class="mb-1">
                                        <code>{{ $s['code'] }}</code> {{ $s['display'] }}
                                        <span class="pill pill--muted">{{ __('terminology.gaps_basis_' . $s['basis']) }}</span>

                                        @if(($proposable[$f['key']] ?? false) && ! $already)
                                            <form method="POST" action="{{ route('portals.admin.terminology.propose') }}" class="inline-form">
                                                @csrf
                                                <input type="hidden" name="key" value="{{ $f['key'] }}">
                                                <input type="hidden" name="value" value="{{ $u['value'] }}">
                                                <input type="hidden" name="code" value="{{ $s['code'] }}">
                                                <button type="submit" class="btn btn-ghost btn-xs">
                                                    <i data-lucide="git-pull-request"></i>
                                                    {{ __('terminology.propose_button') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse

                                @unless($proposable[$f['key']] ?? false)
                                    {{-- Not a missing button. There is no mapping to write down
                                         for this column, and the reason is on the page rather
                                         than in somebody's head. --}}
                                    <div class="text-muted text-sm">
                                        <i data-lucide="info"></i>
                                        {{ __('terminology.propose_not_here') }}
                                        {{ __($notProposable[$f['key']] ?? 'terminology.propose_why_unknown') }}
                                    </div>
                                @endunless
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
                {{-- And there is deliberately no button on any of them: a
                     duplicate pair has no local-code-means-standard-code
                     sentence to record, so a proposal could not express it. --}}
                <p class="text-muted text-sm">
                    <i data-lucide="info"></i> {{ __('terminology.propose_duplicates_note') }}
                </p>
            </div>
            @endif
        @endif
    </div>
</div>
@endforeach

<p class="text-muted text-sm">{{ __('terminology.gaps_footer') }}</p>
@endsection
