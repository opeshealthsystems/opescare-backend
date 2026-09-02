@extends('layouts.portal')
@section('title', __('public.stf_supply_stock_title') . ' — Supply Chain')
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_stock_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_stock_subtitle') }}</p>
        </div>
        <button class="btn btn--primary" onclick="openModal('receiveModal')">
            <i data-lucide="plus"></i> {{ __('public.stf_supply_stock_btn_receive') }}
        </button>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>@endif

    {{-- Filters --}}
    <form method="GET" class="filter-bar">
        <select name="item" class="filter-select">
            <option value="">{{ __('public.stf_supply_stock_filter_all_items') }}</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" {{ request('item') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
            @endforeach
        </select>
        <select name="status" class="filter-select">
            <option value="">{{ __('public.stf_supply_stock_filter_all_statuses') }}</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('public.stf_supply_stock_filter_active') }}</option>
            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('public.stf_supply_stock_filter_expired') }}</option>
            <option value="quarantine" {{ request('status') == 'quarantine' ? 'selected' : '' }}>{{ __('public.stf_supply_stock_filter_quarantine') }}</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_supply_stock_btn_filter') }}</button>
        <a href="{{ route('portals.staff.supply.stock') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_supply_stock_btn_reset') }}</a>
    </form>

    <div class="portal-card">
        <div class="portal-card__body panel-body--flush">
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_supply_stock_col_item') }}</th>
                        <th>{{ __('public.stf_supply_stock_col_batch') }}</th>
                        <th>{{ __('public.stf_supply_stock_col_location') }}</th>
                        <th>{{ __('public.stf_supply_stock_col_available') }}</th>
                        <th>{{ __('public.stf_supply_stock_col_expiry') }}</th>
                        <th>{{ __('public.stf_supply_stock_col_status') }}</th>
                        <th>{{ __('public.stf_supply_stock_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        @php
                            $available = $batch->availableQty();
                            $availBadge = $available <= 0 ? 'badge-danger' : ($available <= ($batch->item->reorder_level ?? 0) ? 'badge-warning' : 'badge-neutral');
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.stf_supply_stock_col_item') }}">
                                <div class="td-strong">{{ $batch->item->name ?? '—' }}</div>
                                <div class="td-muted">{{ $batch->item->code ?? '' }} · {{ $batch->item->unit ?? '' }}</div>
                            </td>
                            <td data-label="{{ __('public.stf_supply_stock_col_batch') }}">
                                {{ $batch->batch_number ?: '—' }}
                                @if($batch->lot_number)
                                    <div class="td-muted">{{ __('public.stf_supply_stock_lot_label', ['number' => $batch->lot_number]) }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_stock_col_location') }}">{{ $batch->location->name ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_stock_col_available') }}">
                                <span class="badge {{ $availBadge }}">{{ $available }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_stock_col_expiry') }}">
                                @if($batch->expiry_date)
                                    <span class="badge {{ $batch->isExpired() ? 'badge-danger' : ($batch->isExpiringSoon() ? 'badge-warning' : 'badge-neutral') }}">
                                        {{ $batch->expiry_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="td-muted">N/A</span>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_stock_col_status') }}">
                                <span class="badge badge--{{ $batch->status === 'active' ? 'success' : ($batch->status === 'expired' ? 'danger' : 'warning') }}">
                                    {{ $batch->status }}
                                </span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_stock_col_actions') }}">
                                @if($batch->status === 'active')
                                    <button class="btn btn--sm btn--outline"
                                            onclick="openAdjust('{{ $batch->id }}', {{ $batch->availableQty() }})">
                                        <i data-lucide="edit-3"></i>
                                        {{ __('public.stf_supply_stock_btn_adjust') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="boxes"></i></div>
                                <p>{{ __('public.stf_supply_stock_empty') }}</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($batches->hasPages())<div class="panel-footer">{{ $batches->links() }}</div>@endif
    </div>

</div>

{{-- Receive Stock Modal --}}
<div id="receiveModal" class="modal-backdrop mt-6" hidden onclick="if(event.target===this)closeModal('receiveModal')">
    <div class="modal modal--md" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="package-plus"></i> {{ __('public.stf_supply_stock_receive_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.supply.stock.receive') }}">
            @csrf
            <div class="modal__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_stock_label_item') }}</label>
                        <select name="inventory_item_id" class="form-control" required>
                            <option value="">{{ __('public.stf_supply_stock_select_item') }}</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_stock_label_qty') }}</label>
                        <input type="number" name="quantity" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_stock_label_location') }}</label>
                        <select name="location_id" class="form-control">
                            <option value="">{{ __('public.stf_supply_stock_loc_default') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_stock_label_batch') }}</label>
                        <input type="text" name="batch_number" class="form-control" maxlength="80">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_stock_label_expiry') }}</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_stock_label_unit_cost') }}</label>
                        <input type="number" name="unit_cost" class="form-control" step="0.0001" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_stock_label_supplier') }}</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">{{ __('public.stf_supply_stock_select_none') }}</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal('receiveModal')">{{ __('public.stf_supply_stock_btn_cancel') }}</button>
                <button type="submit" class="btn btn--primary">{{ __('public.stf_supply_stock_btn_receive_submit') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Adjust Stock Modal --}}
<div id="adjustModal" class="modal-backdrop mt-6" hidden onclick="if(event.target===this)closeModal('adjustModal')">
    <div class="modal" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="edit-3"></i> {{ __('public.stf_supply_stock_adjust_modal_title') }}</h3>
        <form method="POST" id="adjustForm" action="">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_supply_stock_label_current_qty') }}</label>
                    <input type="text" id="currentQty" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.stf_supply_stock_label_new_qty') }}</label>
                    <input type="number" name="new_quantity" class="form-control" required min="0">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.stf_supply_stock_label_reason') }}</label>
                    <input type="text" name="reason" class="form-control" required placeholder="{{ __('public.stf_supply_stock_ph_reason') }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal('adjustModal')">{{ __('public.stf_supply_stock_btn_cancel') }}</button>
                <button type="submit" class="btn btn--warning">{{ __('public.stf_supply_stock_btn_apply') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function closeModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function openAdjust(batchId, currentQty){
    document.getElementById('currentQty').value = currentQty;
    document.getElementById('adjustForm').action = '/portals/staff/supply/stock/' + batchId + '/adjust';
    openModal('adjustModal');
}
</script>
@endsection
