@extends('layouts.portal')
@section('title', __('public.adm_cc_set_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_set_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_set_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>
@endif

@forelse($groups as $group => $settings)
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ ucwords($group) }} <span class="od-card__count">({{ count($settings) }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_set_col_key') }}</th><th>{{ __('public.adm_cc_set_col_value') }}</th><th>{{ __('public.adm_cc_set_col_type') }}</th><th>{{ __('public.adm_cc_set_col_description') }}</th><th class="od-table__actions"></th>
                </tr></thead>
                <tbody>
                    @foreach($settings as $setting)
                    <tr>
                        <td data-label="Key"><span class="oda-mono">{{ $setting['key'] }}</span></td>
                        <td data-label="Value"><span class="od-table__name">{{ $setting['value'] }}</span></td>
                        <td data-label="Type"><span class="od-pill od-pill--neutral">{{ $setting['value_type'] }}</span></td>
                        <td data-label="Description" class="oda-ga-muted">{{ $setting['description'] ?? '—' }}</td>
                        <td class="od-table__actions">
                            <button type="button" class="od-btn od-btn--row od-btn--sm"
                                data-call="openEditModal" data-args="{{ json_encode([$setting['key'], $setting['value']]) }}" data-show="edit-modal" data-display="flex">
                                <i data-lucide="pencil"></i> {{ __('public.adm_cc_set_btn_edit') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@empty
<section class="od-card">
    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="sliders-horizontal"></i></span>
        <h3 class="od-empty__title">{{ __('public.adm_cc_set_empty_heading') }}</h3>
        <p class="od-empty__text">{{ __('public.adm_cc_set_empty_body') }}</p>
    </div>
</section>
@endforelse
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="modal-fixed">
    <div class="od-card oda-modal">
        <div class="od-card__head">
            <h3 class="od-card__title">{{ __('public.adm_cc_set_edit_modal_heading') }}</h3>
            <button type="button" class="od-btn od-btn--ghost od-btn--icon od-btn--sm oda-modal__close" aria-label="{{ __('public.aria_close') }}" data-hide="edit-modal"><i data-lucide="x"></i></button>
        </div>
        <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.cc.settings.update') }}" class="oda-form">
                @csrf
                <label class="oda-label oda-form__full">
                    <span class="oda-label__text">{{ __('public.adm_cc_set_edit_modal_lbl_key') }}</span>
                    <input type="text" id="edit-key" name="key" class="od-input oda-mono" readonly>
                </label>
                <label class="oda-label oda-form__full">
                    <span class="oda-label__text">{{ __('public.adm_cc_set_edit_modal_lbl_value') }} *</span>
                    <input type="text" id="edit-value" name="value" class="od-input" required maxlength="500">
                </label>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-hide="edit-modal">{{ __('public.adm_cc_set_edit_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('public.adm_cc_set_edit_modal_btn_save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
    function openEditModal(key, value) {
        document.getElementById('edit-key').value = key;
        document.getElementById('edit-value').value = value;
    }
</script>
@include('portals.admin._modal')
@endsection
