@extends('layouts.portal')
@section('title', __('public.developer_portal.page_prod_requests', [], app()->getLocale()) ?: 'Production Access Requests')
@section('sidebar_nav') @include('portals.developer._sidebar') @endsection
@php $l = app()->getLocale(); @endphp

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.developer_portal.page_heading', [], $l) ?: 'Developer Portal' }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ __('public.developer_portal.nav_prod_access', [], $l) ?: 'Production Access' }}</span>
    </div>

    <div class="page-head">
        <h2>{{ __('public.developer_portal.page_prod_requests', [], $l) ?: 'Production Access Requests' }}</h2>
        <div class="page-head__spacer"></div>
        <a href="{{ route('portals.developer.production_requests.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('public.developer_portal.btn_new_request', [], $l) ?: 'New request' }}</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

    <div class="alert alert-info mb-6">
        <i data-lucide="info"></i>
        <div>{{ __('public.developer_portal.prod_review_info', [], $l) ?: 'Production access requests are reviewed by the OpesCare team within 3–5 business days. All production integrations must pass the' }}
        <a href="{{ route('portals.admin.certifications.index') }}">{{ __('public.developer_portal.lnk_integration_cert', [], $l) ?: 'Integration Certification' }}</a> {{ __('public.developer_portal.prod_review_suffix', [], $l) ?: 'checklist before going live.' }}</div>
    </div>

    @if($requests->isEmpty())
    <div class="panel">
        <div class="empty-state">
            <i data-lucide="rocket" class="empty-state-icon"></i>
            <p>{{ __('public.developer_portal.no_prod_requests', [], $l) ?: 'No production access requests yet.' }}</p>
            <a href="{{ route('portals.developer.production_requests.create') }}" class="btn btn-primary btn-sm"><i data-lucide="rocket"></i> {{ __('public.developer_portal.btn_request_prod', [], $l) ?: 'Request production access' }}</a>
        </div>
    </div>
    @else
    <div class="panel">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.developer_portal.col_use_case', [], $l) ?: 'Use case' }}</th>
                    <th>{{ __('public.developer_portal.col_scopes', [], $l) ?: 'Scopes requested' }}</th>
                    <th>{{ __('public.developer_portal.col_patient_data', [], $l) ?: 'Patient data' }}</th>
                    <th>{{ __('public.developer_portal.col_status', [], $l) ?: 'Status' }}</th>
                    <th>{{ __('public.developer_portal.col_submitted', [], $l) ?: 'Submitted' }}</th>
                    <th>{{ __('public.developer_portal.col_reviewed', [], $l) ?: 'Reviewed' }}</th>
                </tr></thead>
                <tbody>
                @foreach($requests as $req)
                <tr>
                    <td data-label="{{ __('public.developer_portal.col_use_case', [], $l) ?: 'Use case' }}">
                        <span class="td-strong">{{ Str::limit($req->use_case, 50) }}</span>
                        @if($req->integration_client_id)
                        <div class="mono">{{ Str::limit($req->integration_client_id, 24) }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.developer_portal.col_scopes', [], $l) ?: 'Scopes' }}" class="td-muted">{{ count((array)$req->requested_scopes) }} {{ __('dev_extra.scopes_suffix', [], $l) ?: 'scopes' }}</td>
                    <td data-label="{{ __('public.developer_portal.col_patient_data', [], $l) ?: 'Patient data' }}">
                        @if($req->handles_patient_data)
                        <span class="badge badge-warning">{{ __('public.developer_portal.lbl_yes', [], $l) ?: 'Yes' }}</span>
                        @else
                        <span class="badge badge-neutral">{{ __('public.developer_portal.lbl_no', [], $l) ?: 'No' }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.developer_portal.col_status', [], $l) ?: 'Status' }}"><span class="{{ $req->statusBadgeClass() }}">@enum($req->status)</span></td>
                    <td data-label="{{ __('public.developer_portal.col_submitted', [], $l) ?: 'Submitted' }}" class="td-muted">{{ $req->created_at->format('d M Y') }}</td>
                    <td data-label="{{ __('public.developer_portal.col_reviewed', [], $l) ?: 'Reviewed' }}" class="td-muted">
                        {{ $req->reviewed_at?->format('d M Y') ?? '—' }}
                        @if($req->review_notes)
                        <div class="td-muted">{{ Str::limit($req->review_notes, 40) }}</div>
                        @endif
                        @if($req->rejected_reason)
                        <div class="badge badge-danger">{{ Str::limit($req->rejected_reason, 40) }}</div>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

@endsection
