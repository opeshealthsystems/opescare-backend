@extends('layouts.portal')
@section('title', __('public.adm_legal_complaints_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_legal_complaints_breadcrumb_span') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_legal_complaints_h2') }}</h2>
</div>
<p class="td-muted mb-6">{{ __('public.adm_legal_complaints_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_complaints_col_complainant') }}</th><th>{{ __('public.adm_legal_complaints_col_type') }}</th><th>{{ __('public.adm_legal_complaints_col_description') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_complaints_col_received') }}</th><th class="row-actions"></th></tr>
            </thead>
            <tbody>
                @forelse($complaints as $c)
                    <tr>
                        <td data-label="{{ __('public.adm_legal_complaints_col_complainant') }}" class="td-strong">
                            {{ $c->complainant_name ?: ($c->patient?->first_name . ' ' . $c->patient?->last_name) ?: '—' }}
                            @if($c->complainant_email)
                                <div class="td-muted code-muted">{{ $c->complainant_email }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_complaints_col_type') }}">
                            <span class="badge badge-warning badge-sm">{{ ucwords(str_replace('_', ' ', $c->complaint_type)) }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_complaints_col_description') }}">{{ \Illuminate\Support\Str::limit($c->description, 80) }}</td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="badge badge--{{ $c->statusColor() }} badge-sm">@enum($c->status)</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_complaints_col_received') }}" class="td-muted">{{ $c->created_at->translatedFormat('d M Y') }}</td>
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
                        <td class="row-actions">
                            @if(! in_array($c->status, ['resolved', 'closed'], true))
                                <details class="inline-disclosure">
                                    <summary class="btn btn-secondary btn-sm">
                                        <i data-lucide="check-circle"></i> {{ __('public.adm_legal_complaints_btn_resolve') }}
                                    </summary>
                                    <form method="POST" action="{{ route('portals.admin.legal.complaints.resolve', $c->id) }}" class="stack-form"
                                          data-confirm="{{ __('public.adm_legal_complaints_confirm_resolve') }}">
                                        @csrf
                                        <label class="form-label" for="resolution-{{ $c->id }}">{{ __('public.adm_legal_complaints_resolution_label') }}</label>
                                        <textarea id="resolution-{{ $c->id }}" name="resolution" class="form-control" rows="4"
                                                  minlength="10" maxlength="1000" required></textarea>
                                        <p class="form-hint">{{ __('public.adm_legal_complaints_resolution_hint') }}</p>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i data-lucide="check"></i> {{ __('public.adm_legal_complaints_btn_resolve') }}
                                        </button>
                                    </form>
                                </details>
                            @else
                                <span class="td-muted">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_legal_complaints_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-6">{{ $complaints->links() }}</div>

@endsection
