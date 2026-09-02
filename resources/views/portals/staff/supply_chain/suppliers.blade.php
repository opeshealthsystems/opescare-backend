@extends('layouts.portal')
@section('title', __('public.stf_supply_sup_title') . ' — Supply Chain')
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_sup_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_sup_subtitle') }}</p>
        </div>
        <button class="btn btn--primary" onclick="openModal('createModal')">
            <i data-lucide="plus"></i> {{ __('public.stf_supply_sup_btn_add') }}
        </button>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>@endif

    <div class="portal-card">
        <div class="portal-card__body panel-body--flush">
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_supply_sup_col_supplier') }}</th>
                        <th>{{ __('public.stf_supply_sup_col_contact') }}</th>
                        <th>{{ __('public.stf_supply_sup_col_phone_email') }}</th>
                        <th>{{ __('public.stf_supply_sup_col_status') }}</th>
                        <th>{{ __('public.stf_supply_sup_col_added') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td data-label="{{ __('public.stf_supply_sup_col_supplier') }}">
                                <div class="td-strong">{{ $supplier->name }}</div>
                                @if($supplier->code)
                                    <span class="mono">{{ $supplier->code }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_sup_col_contact') }}">{{ $supplier->contact_person ?: '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_sup_col_phone_email') }}">
                                @if($supplier->phone)<div>{{ $supplier->phone }}</div>@endif
                                @if($supplier->email)<div class="td-muted">{{ $supplier->email }}</div>@endif
                                @if(!$supplier->phone && !$supplier->email)—@endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_sup_col_status') }}">
                                <span class="badge badge--{{ $supplier->status === 'active' ? 'success' : ($supplier->status === 'blacklisted' ? 'danger' : 'warning') }}">
                                    {{ $supplier->status }}
                                </span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_sup_col_added') }}" class="td-muted">{{ $supplier->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="truck"></i></div>
                                <p>{{ __('public.stf_supply_sup_empty') }}</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($suppliers->hasPages())<div class="panel-footer">{{ $suppliers->links() }}</div>@endif
    </div>
</div>

{{-- Create Supplier Modal --}}
<div id="createModal" class="modal-backdrop mt-6" hidden onclick="if(event.target===this)closeModal('createModal')">
    <div class="modal modal--md" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="truck"></i> {{ __('public.stf_supply_sup_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.supply.suppliers.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_sup_label_name') }}</label>
                        <input type="text" name="name" class="form-control" required maxlength="150" value="{{ old('name') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_sup_label_code') }}</label>
                        <input type="text" name="code" class="form-control" maxlength="50" value="{{ old('code') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_sup_label_contact') }}</label>
                        <input type="text" name="contact_person" class="form-control" maxlength="100" value="{{ old('contact_person') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_sup_label_phone') }}</label>
                        <input type="text" name="phone" class="form-control" maxlength="30" value="{{ old('phone') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_sup_label_email') }}</label>
                        <input type="email" name="email" class="form-control" maxlength="100" value="{{ old('email') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_supply_sup_label_address') }}</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal('createModal')">{{ __('public.stf_supply_sup_btn_cancel') }}</button>
                <button type="submit" class="btn btn--primary">{{ __('public.stf_supply_sup_btn_add_submit') }}</button>
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
