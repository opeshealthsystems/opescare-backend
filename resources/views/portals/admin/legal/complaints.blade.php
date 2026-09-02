@extends('layouts.portal')
@section('title', __('public.adm_legal_complaints_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.legal') }}">{{ __('public.adm_legal_closures_breadcrumb') }}</a>
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
                        <td data-label="{{ __('public.adm_legal_complaints_col_received') }}" class="td-muted">{{ $c->created_at->format('d M Y') }}</td>
                        <td class="row-actions">
                            @if($c->status === 'open' || $c->status === 'under_review')
                            <form method="POST" action="{{ route('portals.admin.legal.complaints.resolve', $c) }}"
                                  onsubmit="return confirm('{{ __('public.adm_legal_complaints_confirm_resolve') }}')">
                                @csrf
                                <input type="hidden" name="resolution" value="Reviewed and resolved by OpesCare compliance team.">
                                <button type="submit" class="btn btn-success btn-sm">{{ __('public.adm_legal_complaints_btn_resolve') }}</button>
                            </form>
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
