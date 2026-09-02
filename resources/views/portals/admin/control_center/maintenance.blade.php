@extends('layouts.portal')
@section('title', __('public.adm_cc_mnt_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_mnt_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_mnt_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_mnt_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_mnt_subtitle') }}</p>
    </div>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCreateModal()">
        <i data-lucide="plus"></i> {{ __('public.adm_cc_mnt_btn_schedule') }}
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Active Window Banner --}}
@php $active = $windows->firstWhere('is_active', true); @endphp
@if($active && $active->isCurrentlyActive())
<div class="banner banner--danger">
    <i data-lucide="wrench"></i>
    <div>
        <strong>{{ __('public.adm_cc_mnt_banner_active') }}</strong> {{ $active->title }}
        <span class="td-muted text-sm">Until {{ \Carbon\Carbon::parse($active->ends_at)->format('M d, Y H:i') }}</span>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($windows->count() === 0)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="wrench"></i></div>
                <h3>{{ __('public.adm_cc_mnt_empty_heading') }}</h3>
                <p>{{ __('public.adm_cc_mnt_empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_mnt_col_title') }}</th><th>{{ __('public.adm_cc_mnt_col_starts') }}</th><th>{{ __('public.adm_cc_mnt_col_ends') }}</th><th>{{ __('public.adm_cc_mnt_col_status') }}</th><th>{{ __('public.adm_cc_mnt_col_emergency') }}</th><th>{{ __('public.adm_cc_mnt_col_created_by') }}</th><th>{{ __('public.adm_cc_mnt_col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($windows as $win)
                    @php
                        $now = now();
                        $starts = \Carbon\Carbon::parse($win->starts_at);
                        $ends   = \Carbon\Carbon::parse($win->ends_at);
                        $live   = $win->is_active && $now->between($starts, $ends);
                        $upcoming = $win->is_active && $now->lt($starts);
                    @endphp
                    <tr>
                        <td data-label="Title" class="td-strong">
                            {{ $win->title }}
                            @if($win->message)
                                <div class="td-muted text-sm">{{ Str::limit($win->message, 60) }}</div>
                            @endif
                        </td>
                        <td data-label="Starts">{{ $starts->format('M d, Y H:i') }}</td>
                        <td data-label="Ends">{{ $ends->format('M d, Y H:i') }}</td>
                        <td data-label="Status">
                            @if($live)
                                <span class="badge badge-danger badge-sm">{{ __('public.adm_cc_mnt_badge_live') }}</span>
                            @elseif($upcoming)
                                <span class="badge badge-warning badge-sm">{{ __('public.adm_cc_mnt_badge_upcoming') }}</span>
                            @elseif(!$win->is_active)
                                <span class="badge badge-neutral badge-sm">{{ __('public.adm_cc_mnt_badge_inactive') }}</span>
                            @else
                                <span class="badge badge-neutral badge-sm">{{ __('public.adm_cc_mnt_badge_expired') }}</span>
                            @endif
                        </td>
                        <td data-label="Emergency Access">
                            @if($win->allow_emergency_access)
                                <span class="badge badge-success badge-sm">{{ __('public.adm_cc_mnt_badge_allowed') }}</span>
                            @else
                                <span class="badge badge-neutral badge-sm">{{ __('public.adm_cc_mnt_badge_blocked') }}</span>
                            @endif
                        </td>
                        <td data-label="Created By" class="td-muted">{{ $win->created_by ?? '—' }}</td>
                        <td class="row-actions">
                            <form method="POST" action="{{ route('portals.admin.cc.maintenance.toggle', $win->id) }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="active" value="{{ $win->is_active ? '0' : '1' }}">
                                <label class="switch">
                                    <input type="checkbox" {{ $win->is_active ? 'checked' : '' }} onchange="this.form.submit()" aria-label="Toggle {{ $win->title }}">
                                    <span class="switch__track"></span>
                                </label>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Create Modal --}}
<div id="create-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.adm_cc_mnt_modal_heading') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="closeCreateModal()"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="{{ route('portals.admin.cc.maintenance.store') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label form-label-required">{{ __('public.adm_cc_mnt_modal_lbl_title') }}</label>
                <input type="text" name="title" class="form-control" required maxlength="150" placeholder="{{ __('public.adm_cc_mnt_modal_ph_title') }}">
            </div>
            <div class="form-group mb-3">
                <label class="form-label">{{ __('public.adm_cc_mnt_modal_lbl_message') }} <span class="td-muted">{{ __('public.adm_cc_mnt_modal_lbl_message_hint') }}</span></label>
                <textarea name="message" class="form-control" rows="2" maxlength="500" placeholder="{{ __('public.adm_cc_mnt_modal_ph_message') }}"></textarea>
            </div>
            <div class="form-row mb-3">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_cc_mnt_modal_lbl_start') }}</label>
                    <input type="datetime-local" name="starts_at" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_cc_mnt_modal_lbl_end') }}</label>
                    <input type="datetime-local" name="ends_at" class="form-control" required>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="form-check">
                    <input type="checkbox" name="allow_emergency_access" value="1">
                    {{ __('public.adm_cc_mnt_modal_allow_emergency') }}
                </label>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeCreateModal()">{{ __('public.adm_cc_mnt_modal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.adm_cc_mnt_modal_btn_schedule') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openCreateModal()  { document.getElementById('create-modal').classList.add('open'); }
    function closeCreateModal() { document.getElementById('create-modal').classList.remove('open'); }
    document.getElementById('create-modal').addEventListener('click', function(e) { if (e.target === this) closeCreateModal(); });
</script>
@endsection
