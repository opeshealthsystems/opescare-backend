@extends('layouts.portal')
@section('title', __('public.adm_cc_set_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_set_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_set_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_set_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_set_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

@forelse($groups as $group => $settings)
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="sliders-horizontal"></i> {{ ucwords($group) }}</h3>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_set_col_key') }}</th><th>{{ __('public.adm_cc_set_col_value') }}</th><th>{{ __('public.adm_cc_set_col_type') }}</th><th>{{ __('public.adm_cc_set_col_description') }}</th><th class="row-actions"></th>
                </tr></thead>
                <tbody>
                    @foreach($settings as $setting)
                    <tr>
                        <td data-label="Key"><span class="code-token">{{ $setting['key'] }}</span></td>
                        <td data-label="Value" class="td-strong">{{ $setting['value'] }}</td>
                        <td data-label="Type"><span class="badge badge-neutral badge-sm">{{ $setting['value_type'] }}</span></td>
                        <td data-label="Description" class="td-muted">{{ $setting['description'] ?? '—' }}</td>
                        <td class="row-actions">
                            <button type="button" class="btn btn-ghost btn-sm"
                                onclick="openEditModal('{{ $setting['key'] }}', '{{ addslashes($setting['value']) }}')">
                                <i data-lucide="pencil"></i> {{ __('public.adm_cc_set_btn_edit') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@empty
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="sliders-horizontal"></i></div>
        <h3>{{ __('public.adm_cc_set_empty_heading') }}</h3>
        <p>{{ __('public.adm_cc_set_empty_body') }}</p>
    </div>
@endforelse

{{-- Edit Modal --}}
<div id="edit-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--sm">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.adm_cc_set_edit_modal_heading') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="closeEditModal()"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="{{ route('portals.admin.cc.settings.update') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">{{ __('public.adm_cc_set_edit_modal_lbl_key') }}</label>
                <input type="text" id="edit-key" name="key" class="form-control" readonly>
            </div>
            <div class="form-group mb-4">
                <label class="form-label form-label-required">{{ __('public.adm_cc_set_edit_modal_lbl_value') }}</label>
                <input type="text" id="edit-value" name="value" class="form-control" required maxlength="500">
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditModal()">{{ __('public.adm_cc_set_edit_modal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.adm_cc_set_edit_modal_btn_save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditModal(key, value) {
        document.getElementById('edit-key').value = key;
        document.getElementById('edit-value').value = value;
        document.getElementById('edit-modal').classList.add('open');
    }
    function closeEditModal() { document.getElementById('edit-modal').classList.remove('open'); }
    document.getElementById('edit-modal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
</script>
@endsection
