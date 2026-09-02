@extends('layouts.portal')
@section('title', $facility->name)
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_fac_breadcrumb'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.facilities.index') }}">{{ __('public.adm_fac_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $facility->name }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="building-2"></i></div>
    <h2 class="entity-head__title">{{ $facility->name }}</h2>
    @if(($facility->status ?? '') === 'active')<span class="badge badge-success">{{ __('public.adm_fac_badge_active') }}</span>
    @elseif(($facility->status ?? '') === 'suspended')<span class="badge badge-danger">{{ __('public.adm_fac_badge_suspended') }}</span>
    @elseif(($facility->status ?? '') === 'pending_approval')<span class="badge badge-warning">{{ __('public.adm_fac_badge_pending') }}</span>
    @else<span class="badge badge-neutral">@enum($facility->status ?? 'pending')</span>@endif
    <div class="entity-head__spacer"></div>
    <a href="#edit" class="btn btn-secondary"><i data-lucide="pencil"></i> {{ __('public.adm_fac_show_btn_edit') }}</a>
    @if(($facility->status ?? '') === 'pending_approval' || ($facility->status ?? '') === 'suspended')
    <form method="POST" action="{{ route('admin.facilities.approve', $facility->id) }}" class="inline-form">@csrf
        <button type="submit" class="btn btn-success"><i data-lucide="check-circle"></i> {{ __('public.adm_fac_show_btn_approve') }}</button>
    </form>
    @endif
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="tabs">
    <span class="tab active">{{ __('public.adm_fac_show_tab_overview') }}</span>
</div>

<div class="field-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_type') }}</div>
        <div class="stat-card__value">{{ ucfirst($facility->type ?? '—') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_status') }}</div>
        <div class="stat-card__value">@enum($facility->status ?? 'pending')</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_license') }}</div>
        <div class="stat-card__value">{{ $facility->license_number ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_region') }}</div>
        <div class="stat-card__value">{{ $facility->region ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_staff') }}</div>
        <div class="stat-card__value">{{ number_format($staffCount ?? 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_patients') }}</div>
        <div class="stat-card__value">{{ number_format($patientCount ?? 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_fac_show_lbl_created') }}</div>
        <div class="stat-card__value">{{ $facility->created_at?->format('d M Y') ?? '—' }}</div>
    </div>
</div>

<div class="panel mb-6" id="edit">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="pencil"></i> {{ __('public.adm_fac_show_edit_title') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.facilities.update', $facility->id) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_fac_show_lbl_name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $facility->name) }}" required>
                    @error('name')<div class="form-hint">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_fac_show_lbl_type') }}</label>
                    <select name="type" class="form-control" required>
                        @foreach(['hospital','clinic','pharmacy','laboratory','radiology','specialist','other'] as $t)
                        <option value="{{ $t }}" @selected(old('type',$facility->type)===$t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_fac_show_lbl_region') }}</label>
                    <input type="text" name="region" class="form-control" value="{{ old('region', $facility->region) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_fac_show_lbl_country') }}</label>
                    <input type="text" name="country_code" class="form-control" value="{{ old('country_code', $facility->country_code) }}" maxlength="3">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('public.adm_fac_show_btn_save') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_fac_show_danger_title') }}</h3></div>
    <div class="panel-body">
        <div class="page-head">
            @if(($facility->status ?? '') !== 'suspended')
            <button type="button" class="btn btn-warning" onclick="opOpenModal('suspend-modal')"><i data-lucide="pause-circle"></i> {{ __('public.adm_fac_show_btn_suspend') }}</button>
            @endif
            <button type="button" class="btn btn-danger" onclick="opOpenModal('delete-modal')"><i data-lucide="trash-2"></i> {{ __('public.adm_fac_show_btn_delete') }}</button>
        </div>
    </div>
</div>

<div id="suspend-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <h3 class="modal__title" id="suspend-modal-title"><i data-lucide="pause-circle"></i> {{ __('public.adm_fac_show_modal_suspend_title') }}</h3>
        <form method="POST" action="{{ route('admin.facilities.suspend', $facility->id) }}">
            @csrf
            <div class="modal__body">
                <p>{{ __('public.adm_fac_show_modal_suspend_body', ['name' => $facility->name]) }}</p>
                <textarea name="reason" class="form-control" rows="3" placeholder="{{ __('public.adm_fac_show_modal_suspend_ph') }}" required></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('suspend-modal')">{{ __('public.adm_fac_show_btn_cancel') }}</button>
                <button type="submit" class="btn btn-warning">{{ __('public.adm_fac_show_btn_suspend_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <h3 class="modal__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_fac_show_modal_delete_title') }}</h3>
        <form method="POST" action="{{ route('admin.facilities.destroy', $facility->id) }}">
            @csrf @method('DELETE')
            <div class="modal__body">
                <p>{{ __('public.adm_fac_show_modal_delete_body', ['name' => $facility->name]) }}</p>
                <textarea name="reason" class="form-control" rows="3" placeholder="{{ __('public.adm_fac_show_modal_delete_ph') }}" required></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-modal')">{{ __('public.adm_fac_show_btn_cancel') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.adm_fac_show_btn_delete_confirm') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
