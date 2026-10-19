@extends('layouts.portal')
@section('title', __('public.adm_legal_complaints_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gbPill = fn ($c) => match ($c) { 'warning' => 'pending', 'info' => 'normal', 'success' => 'ready', 'danger' => 'urgent', default => 'neutral' };
@endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_legal_complaints_h2') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_legal_complaints_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_legal_complaints_breadcrumb_span') }}</h2>
        <span class="od-card__count">{{ method_exists($complaints, 'total') ? $complaints->total() : $complaints->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_complaints_col_complainant') }}</th><th>{{ __('public.adm_legal_complaints_col_type') }}</th><th>{{ __('public.adm_legal_complaints_col_description') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_complaints_col_received') }}</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($complaints as $c)
                    @php $cname = $c->complainant_name ?: ($c->patient?->first_name . ' ' . $c->patient?->last_name) ?: '—'; @endphp
                    <tr>
                        <td data-label="{{ __('public.adm_legal_complaints_col_complainant') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $cname) % 6] }}">{{ mb_strtoupper(mb_substr(trim((string) $cname), 0, 1)) }}</span>
                                <div>
                                    <div class="od-table__name">{{ $cname }}</div>
                                    @if($c->complainant_email)
                                        <div class="od-table__sub oda-mono">{{ $c->complainant_email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.adm_legal_complaints_col_type') }}">
                            <span class="od-pill od-pill--pending">{{ ucwords(str_replace('_', ' ', $c->complaint_type)) }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_complaints_col_description') }}">{{ \Illuminate\Support\Str::limit($c->description, 80) }}</td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="od-pill od-pill--{{ $gbPill($c->statusColor()) }}">@enum($c->status)</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_complaints_col_received') }}"><span class="od-table__sub">{{ $c->created_at->translatedFormat('d M Y') }}</span></td>
                        {{--
                            Empty cell, same as the closures table: the column,
                            the controller action and the button string all
                            existed with nothing rendering a control.

                            Resolving takes WORDS, not a click — the controller
                            requires 10 characters minimum — so this is a
                            disclosure with a textarea rather than a bare
                            button. A privacy complaint closed with no account
                            of what was done is the one thing a regulator will
                            ask to see and the one thing that would not be
                            there.

                            Already-resolved rows get no form: `resolved_at`
                            and the resolution text are the record, and a
                            second submission would quietly overwrite the first
                            with no history of the change.
                        --}}
                        <td>
                            @if(! in_array($c->status, ['resolved', 'closed'], true))
                                <details class="oda-gb-disclosure">
                                    <summary class="od-btn od-btn--row">
                                        <i data-lucide="check-circle"></i> {{ __('public.adm_legal_complaints_btn_resolve') }}
                                    </summary>
                                    <form method="POST" action="{{ route('portals.admin.legal.complaints.resolve', $c->id) }}" class="od-stack oda-gb-disclosure__body"
                                          data-confirm="{{ __('public.adm_legal_complaints_confirm_resolve') }}">
                                        @csrf
                                        <label class="oda-label" for="resolution-{{ $c->id }}">
                                            <span class="oda-label__text">{{ __('public.adm_legal_complaints_resolution_label') }}</span>
                                            <textarea id="resolution-{{ $c->id }}" name="resolution" class="oda-textarea" rows="4"
                                                      minlength="10" maxlength="1000" required></textarea>
                                        </label>
                                        <p class="oda-help">{{ __('public.adm_legal_complaints_resolution_hint') }}</p>
                                        <div class="od-row">
                                            <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                                                <i data-lucide="check"></i> {{ __('public.adm_legal_complaints_btn_resolve') }}
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            @else
                                <span class="od-table__sub">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="od-empty">
                            <div class="od-empty__icon"><i data-lucide="message-square-warning"></i></div>
                            <p class="od-empty__text">{{ __('public.adm_legal_complaints_empty') }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</section>
<div class="oda-gb-pager">{{ $complaints->links() }}</div>
</div>
@endsection
