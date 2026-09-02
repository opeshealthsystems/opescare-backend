@extends('layouts.portal')
@section('title', __('public.adm_legal_closures_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.legal') }}">{{ __('public.adm_legal_closures_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_legal_closures_breadcrumb_span') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_legal_closures_h2') }}</h2>
</div>
<p class="td-muted mb-6">{{ __('public.adm_legal_closures_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_closures_col_patient') }}</th><th>{{ __('public.adm_legal_closures_col_reason') }}</th><th>{{ __('public.adm_legal_closures_col_delete_req') }}</th><th>{{ __('public.adm_legal_closures_col_export_req') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_closures_col_requested') }}</th><th class="row-actions"></th></tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td data-label="{{ __('public.adm_legal_closures_col_patient') }}" class="td-strong">
                            {{ $req->patient?->first_name }} {{ $req->patient?->last_name }}
                            <div class="code-muted">{{ $req->patient?->health_id }}</div>
                        </td>
                        <td data-label="{{ __('public.adm_legal_closures_col_reason') }}">{{ \Illuminate\Support\Str::limit($req->reason, 60) ?: '—' }}</td>
                        <td data-label="{{ __('public.adm_legal_closures_col_delete_req') }}">
                            @if($req->data_delete_requested)<span class="badge badge-danger badge-sm">{{ __('admin_extra.yes', [], app()->getLocale()) ?: 'Yes' }}</span>@else<span class="td-muted">{{ __('admin_extra.no', [], app()->getLocale()) ?: 'No' }}</span>@endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_closures_col_export_req') }}">
                            @if($req->data_export_requested)<span class="badge badge-primary badge-sm">{{ __('admin_extra.yes', [], app()->getLocale()) ?: 'Yes' }}</span>@else<span class="td-muted">{{ __('admin_extra.no', [], app()->getLocale()) ?: 'No' }}</span>@endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="badge badge--{{ $req->statusColor() }} badge-sm">@enum($req->status)</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_closures_col_requested') }}" class="td-muted">{{ $req->created_at->format('d M Y') }}</td>
                        <td class="row-actions">
                            @if($req->isPending())
                            <div class="row-actions-inline">
                                <form method="POST" action="{{ route('portals.admin.legal.closures.review', $req) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm"
                                            onclick="return confirm('{{ __('public.adm_legal_closures_confirm_approve') }}')">{{ __('public.adm_legal_closures_btn_approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('portals.admin.legal.closures.review', $req) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-secondary btn-sm">{{ __('public.adm_legal_closures_btn_reject') }}</button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td-muted empty-cell">{{ __('public.adm_legal_closures_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-6">{{ $requests->links() }}</div>

@endsection
