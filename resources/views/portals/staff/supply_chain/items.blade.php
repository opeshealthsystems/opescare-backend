@extends('layouts.portal')
@section('title', __('public.stf_supply_items_title') . (__('staff_inv.title_suffix', [], app()->getLocale()) ?: ' — Supply Chain'))
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_items_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_items_subtitle') }}</p>
        </div>
        <button class="btn btn--primary" onclick="openModal('createModal')">
            <i data-lucide="plus"></i> {{ __('public.stf_supply_items_btn_add') }}
        </button>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>@endif

    {{-- Filters --}}
    <form method="GET" class="filter-bar">
        <label class="filter-search">
            <i data-lucide="search"></i>
            <input type="text" name="search" placeholder="{{ __('public.stf_supply_items_ph_search') }}" value="{{ request('search') }}">
        </label>
        <select name="category" class="filter-select">
            <option value="">{{ __('public.stf_supply_items_filter_all_cats') }}</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="filter-select">
            <option value="">{{ __('public.stf_supply_items_filter_all_statuses') }}</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('public.stf_supply_items_filter_active') }}</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('public.stf_supply_items_filter_inactive') }}</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_supply_items_btn_filter') }}</button>
        <a href="{{ route('portals.staff.supply.items') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_supply_items_btn_reset') }}</a>
    </form>

    <div class="portal-card">
        <div class="portal-card__body panel-body--flush">
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_supply_items_col_name') }}</th>
                        <th>{{ __('public.stf_supply_items_col_code') }}</th>
                        <th>{{ __('public.stf_supply_items_col_category') }}</th>
                        <th>{{ __('public.stf_supply_items_col_unit') }}</th>
                        <th>{{ __('public.stf_supply_items_col_reorder') }}</th>
                        <th>{{ __('public.stf_supply_items_col_track_expiry') }}</th>
                        <th>{{ __('public.stf_supply_items_col_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td data-label="{{ __('public.stf_supply_items_col_name') }}">
                                <div class="td-strong">{{ $item->name }}</div>
                                @if($item->description)
                                    <div class="td-muted">{{ Str::limit($item->description,50) }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_items_col_code') }}"><span class="mono">{{ $item->code ?: '—' }}</span></td>
                            <td data-label="{{ __('public.stf_supply_items_col_category') }}">{{ $categories[$item->category] ?? ucfirst($item->category) }}</td>
                            <td data-label="{{ __('public.stf_supply_items_col_unit') }}">{{ $item->unit }}</td>
                            <td data-label="{{ __('public.stf_supply_items_col_reorder') }}" class="td-strong">{{ $item->reorder_level }}</td>
                            <td data-label="{{ __('public.stf_supply_items_col_track_expiry') }}">
                                @if($item->track_expiry)
                                    <span class="badge badge-success">{{ __('public.stf_supply_items_badge_yes') }}</span>
                                @else
                                    <span class="td-muted">{{ __('staff_inv.badge_no', [], app()->getLocale()) ?: 'No' }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_items_col_status') }}"><span class="badge badge--{{ $item->status === 'active' ? 'success' : 'warning' }}">{{ $item->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="list"></i></div>
                                <p>{{ __('public.stf_supply_items_empty') }}</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($items->hasPages())<div class="panel-footer">{{ $items->links() }}</div>@endif
    </div>
</div>

{{-- Create Item Modal --}}
<div id="createModal" class="modal-backdrop mt-6" hidden onclick="if(event.target===this)closeModal('createModal')">
    <div class="modal modal--lg" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="plus-circle"></i> {{ __('public.stf_supply_items_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.supply.items.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_items_label_name') }}</label>
                        <input type="text" name="name" class="form-control" required maxlength="150" value="{{ old('name') }}" placeholder="{{ __('public.stf_supply_items_ph_name') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_items_label_code') }}</label>
                        <input type="text" name="code" class="form-control" maxlength="50" value="{{ old('code') }}" placeholder="{{ __('public.stf_supply_items_ph_code') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_items_label_unit') }}</label>
                        <input type="text" name="unit" class="form-control" required value="{{ old('unit','unit') }}" placeholder="{{ __('public.stf_supply_items_ph_unit') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_items_label_category') }}</label>
                        <select name="category" class="form-control" required>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_items_label_reorder') }}</label>
                        <input type="number" name="reorder_level" class="form-control" min="0" value="{{ old('reorder_level',0) }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_items_label_unit_cost') }}</label>
                        <input type="number" name="unit_cost" class="form-control" min="0" step="0.0001" value="{{ old('unit_cost') }}" placeholder="0.00">
                    </div>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="track_expiry" value="1" {{ old('track_expiry') ? 'checked' : '' }}> {{ __('public.stf_supply_items_check_track_expiry') }}
                </label>
                <label class="form-check">
                    <input type="checkbox" name="track_batch" value="1" {{ old('track_batch') ? 'checked' : '' }}> {{ __('public.stf_supply_items_check_track_batch') }}
                </label>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_supply_items_label_description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal('createModal')">{{ __('public.stf_supply_items_btn_cancel') }}</button>
                <button type="submit" class="btn btn--primary">{{ __('public.stf_supply_items_btn_add_submit') }}</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())<script>document.addEventListener('DOMContentLoaded',()=>openModal('createModal'));</script>@endif
<script>
function openModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function closeModal(id){ document.getElementById(id).setAttribute('hidden',''); }
</script>
@endsection
