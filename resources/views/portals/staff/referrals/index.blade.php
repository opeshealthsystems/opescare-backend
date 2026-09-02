@extends('layouts.portal')

@section('title', __('public.stf_ref_index_title'))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.stf_ref_index_breadcrumb'))

@section('content')

<div class="page-head">
    <h2>{{ __('public.stf_ref_index_page_heading') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.referrals.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i>
        {{ __('public.stf_ref_index_btn_new') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.stf_ref_index_page_subtitle') }}</p>

<!-- Filters -->
<div class="panel mb-6">
    <form method="GET" action="{{ route('portals.staff.referrals') }}" class="panel-body">
        <div class="filter-bar">
            <label class="filter-search">
                <i data-lucide="search"></i>
                <input type="text" name="patient_id" placeholder="{{ __('public.stf_ref_index_filter_patient') }}" value="{{ request('patient_id') }}" aria-label="{{ __('public.stf_ref_index_filter_patient_aria') }}">
            </label>
            <div class="form-group">
                <select name="status" class="filter-select" aria-label="{{ __('public.stf_ref_index_filter_status_aria') }}">
                    <option value="">{{ __('public.stf_ref_index_filter_all_status') }}</option>
                    <option value="draft"     @selected(request('status')==='draft')>{{ __('public.stf_ref_status_draft') }}</option>
                    <option value="sent"      @selected(request('status')==='sent')>{{ __('public.stf_ref_status_sent') }}</option>
                    <option value="accepted"  @selected(request('status')==='accepted')>{{ __('public.stf_ref_status_accepted') }}</option>
                    <option value="rejected"  @selected(request('status')==='rejected')>{{ __('public.stf_ref_status_rejected') }}</option>
                    <option value="completed" @selected(request('status')==='completed')>{{ __('public.stf_ref_status_completed') }}</option>
                    <option value="cancelled" @selected(request('status')==='cancelled')>{{ __('public.stf_ref_status_cancelled') }}</option>
                    <option value="expired"   @selected(request('status')==='expired')>{{ __('public.stf_ref_status_expired') }}</option>
                </select>
            </div>
            <div class="form-group">
                <select name="priority" class="filter-select" aria-label="{{ __('public.stf_ref_index_filter_priority_aria') }}">
                    <option value="">{{ __('public.stf_ref_index_filter_all_priority') }}</option>
                    <option value="routine"   @selected(request('priority')==='routine')>{{ __('public.stf_ref_priority_routine') }}</option>
                    <option value="urgent"    @selected(request('priority')==='urgent')>{{ __('public.stf_ref_priority_urgent') }}</option>
                    <option value="emergency" @selected(request('priority')==='emergency')>{{ __('public.stf_ref_priority_emergency') }}</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_ref_index_filter_btn') }}</button>
            <a href="{{ route('portals.staff.referrals') }}" class="btn btn-secondary btn-sm"><i data-lucide="x"></i> {{ __('public.stf_ref_index_filter_clear') }}</a>
        </div>
    </form>
</div>

<!-- Referrals Table -->
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="send"></i> {{ __('public.stf_ref_index_panel_title') }}</h2>
        <span class="badge badge-primary">{{ count($referrals) }}</span>
    </div>

    @if(count($referrals) === 0)
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="send"></i></div>
            <h3>{{ __('public.stf_ref_index_empty_heading') }}</h3>
            <p>{{ __('public.stf_ref_index_empty_body') }}</p>
            <a href="{{ route('portals.staff.referrals.create') }}" class="btn btn-primary">
                <i data-lucide="plus"></i> {{ __('public.stf_ref_index_btn_new') }}
            </a>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table" aria-label="{{ __('public.stf_ref_index_panel_title') }}">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_ref_index_col_id') }}</th>
                        <th>{{ __('public.stf_ref_index_col_patient') }}</th>
                        <th>{{ __('public.stf_ref_index_col_priority') }}</th>
                        <th>{{ __('public.stf_ref_index_col_facility') }}</th>
                        <th>{{ __('public.stf_ref_index_col_specialty') }}</th>
                        <th>{{ __('public.stf_ref_index_col_status') }}</th>
                        <th>{{ __('public.stf_ref_index_col_created') }}</th>
                        <th class="row-actions"><span class="sr-only">{{ __('public.stf_ref_index_col_actions_sr') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($referrals as $referral)
                    <tr>
                        <td data-label="{{ __('public.stf_ref_index_col_id') }}">
                            <span class="td-mono">{{ substr($referral->id, 0, 8) }}…</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_patient') }}">
                            <span class="td-mono">{{ $referral->patient_id }}</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_priority') }}">
                            @php
                                $prCls = match($referral->urgency ?? 'routine') {
                                    'emergency' => 'badge-critical',
                                    'urgent'    => 'badge-danger',
                                    default     => 'badge-neutral',
                                };
                            @endphp
                            <span class="badge {{ $prCls }}">@enum($referral->urgency ?? 'routine', 'urgency')</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_facility') }}">
                            <span class="td-muted">{{ $referral->referring_facility_id ?? '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_specialty') }}">
                            <span class="td-muted">{{ $referral->receiving_specialty ?? '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_status') }}">
                            @php
                                $stCls = match($referral->status ?? 'draft') {
                                    'accepted'  => 'badge-success',
                                    'completed' => 'badge-teal',
                                    'sent'      => 'badge-primary',
                                    'rejected'  => 'badge-danger',
                                    'cancelled' => 'badge-neutral',
                                    'expired'   => 'badge-neutral',
                                    default     => 'badge-warning',
                                };
                            @endphp
                            <span class="badge {{ $stCls }}">@enum($referral->status ?? 'draft')</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_created') }}">
                            <span class="td-muted">{{ $referral->created_at?->format('d M Y') ?? '—' }}</span>
                        </td>
                        <td data-label="{{ __('public.stf_ref_index_col_actions_sr') }}" class="row-actions">
                            <a href="{{ route('portals.staff.referrals.show', $referral->id) }}" class="btn btn-ghost btn-sm">
                                <i data-lucide="eye"></i> {{ __('public.stf_ref_index_btn_view') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
