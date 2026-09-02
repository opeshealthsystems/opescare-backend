@extends('layouts.portal')
@section('title', __('public.stf_supply_po_title') . ' — Supply Chain')
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_po_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_po_subtitle') }}</p>
        </div>
        <button class="btn btn--primary" onclick="openModal('createModal')">
            <i data-lucide="plus"></i> {{ __('public.stf_supply_po_btn_new') }}
        </button>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>@endif

    {{-- Filters --}}
    <form method="GET" class="filter-bar">
        <select name="status" class="filter-select">
            <option value="">{{ __('public.stf_supply_po_filter_all_statuses') }}</option>
            @foreach(['draft','submitted','approved','sent','partial','received','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="supplier" class="filter-select">
            <option value="">{{ __('public.stf_supply_po_filter_all_suppliers') }}</option>
            @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" {{ request('supplier') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> {{ __('public.stf_supply_po_btn_filter') }}</button>
        <a href="{{ route('portals.staff.supply.purchase_orders') }}" class="btn btn-ghost btn-sm">{{ __('public.stf_supply_po_btn_reset') }}</a>
    </form>

    <div class="portal-card">
        <div class="portal-card__body panel-body--flush">
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_supply_po_col_po_number') }}</th>
                        <th>{{ __('public.stf_supply_po_col_supplier') }}</th>
                        <th>{{ __('public.stf_supply_po_col_lines') }}</th>
                        <th>{{ __('public.stf_supply_po_col_total') }}</th>
                        <th>{{ __('public.stf_supply_po_col_status') }}</th>
                        <th>{{ __('public.stf_supply_po_col_order_date') }}</th>
                        <th>{{ __('public.stf_supply_po_col_expected') }}</th>
                        <th>{{ __('public.stf_supply_po_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td data-label="{{ __('public.stf_supply_po_col_po_number') }}">
                                <div class="td-strong">{{ $po->po_number }}</div>
                                @if($po->notes)
                                    <div class="td-muted">{{ Str::limit($po->notes, 40) }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('public.stf_supply_po_col_supplier') }}">{{ $po->supplier->name ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_po_col_lines') }}">{{ __('public.stf_supply_po_lines_count', ['count' => $po->items->count()]) }}</td>
                            <td data-label="{{ __('public.stf_supply_po_col_total') }}" class="td-strong">
                                {{ number_format($po->total_amount, 2) }}
                            </td>
                            <td data-label="{{ __('public.stf_supply_po_col_status') }}">
                                @php
                                    $statusColor = match($po->status) {
                                        'draft'     => 'default',
                                        'submitted' => 'info',
                                        'approved'  => 'success',
                                        'sent'      => 'info',
                                        'partial'   => 'warning',
                                        'received'  => 'success',
                                        'cancelled' => 'danger',
                                        default     => 'default',
                                    };
                                @endphp
                                <span class="badge badge--{{ $statusColor }}">{{ $po->status }}</span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_po_col_order_date') }}" class="td-muted">{{ $po->order_date?->format('d M Y') ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_po_col_expected') }}" class="td-muted">{{ $po->expected_delivery_date?->format('d M Y') ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_po_col_actions') }}">
                                @if(in_array($po->status, ['draft','submitted']))
                                    <form method="POST" action="{{ route('portals.staff.supply.purchase_orders.approve', $po->id) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn--sm btn--success"
                                                onclick="return confirm('{{ __('public.stf_supply_po_confirm_approve', ['number' => $po->po_number]) }}')">
                                            <i data-lucide="check"></i>
                                            {{ __('public.stf_supply_po_btn_approve') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="file-text"></i></div>
                                <p>{{ __('public.stf_supply_po_empty') }}</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($purchaseOrders->hasPages())<div class="panel-footer">{{ $purchaseOrders->links() }}</div>@endif
    </div>

</div>

{{-- Create Purchase Order Modal --}}
<div id="createModal" class="modal-backdrop mt-6" hidden onclick="if(event.target===this)closeModal('createModal')">
    <div class="modal modal--lg" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="file-plus"></i> {{ __('public.stf_supply_po_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.supply.purchase_orders.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-row mb-6">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_po_label_supplier') }}</label>
                        <select name="supplier_id" class="form-control" required>
                            <option value="">{{ __('public.stf_supply_po_select_supplier') }}</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_po_label_expected') }}</label>
                        <input type="date" name="expected_delivery_date" class="form-control">
                    </div>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label">{{ __('public.stf_supply_po_label_notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('public.stf_supply_po_ph_notes') }}"></textarea>
                </div>

                {{-- Line Items --}}
                <div class="flex-between mb-6">
                    <strong>{{ __('public.stf_supply_po_order_lines') }}</strong>
                    <button type="button" class="btn btn--sm btn--outline" onclick="addPOLine()">
                        <i data-lucide="plus"></i> {{ __('public.stf_supply_po_btn_add_line') }}
                    </button>
                </div>
                <div id="po-lines">
                    <div class="po-line" style="display:grid;grid-template-columns:3fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;">
                        <select name="items[0][inventory_item_id]" class="form-control" required>
                            <option value="">{{ __('public.stf_supply_po_select_item') }}</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <input type="number" name="items[0][quantity_ordered]" class="form-control" placeholder="{{ __('public.stf_supply_po_ph_qty') }}" min="1" required onchange="recalcTotal()">
                        <input type="number" name="items[0][unit_price]" class="form-control" placeholder="{{ __('public.stf_supply_po_ph_unit_price') }}" min="0" step="0.0001" onchange="recalcTotal()">
                        <button type="button" class="btn btn--sm btn--danger" onclick="removePOLine(this)" disabled title="{{ __('public.stf_supply_po_title_at_least_one') }}">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
                <p class="td-muted mt-6">
                    {{ __('public.stf_supply_po_estimated_total') }} <strong id="po-total" class="td-strong">0.00</strong>
                </p>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal('createModal')">{{ __('public.stf_supply_po_btn_cancel') }}</button>
                <button type="submit" class="btn btn--primary">{{ __('public.stf_supply_po_btn_create') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id){ document.getElementById(id).removeAttribute('hidden'); lucide.createIcons(); }
function closeModal(id){ document.getElementById(id).setAttribute('hidden',''); }

let poLineCount = 1;
const itemOptions = `@foreach($items as $item)<option value="{{ $item->id }}">{{ addslashes($item->name) }} ({{ $item->unit }})</option>@endforeach`;

function addPOLine(){
    const container = document.getElementById('po-lines');
    const idx = poLineCount++;
    const div = document.createElement('div');
    div.className = 'po-line';
    div.style.cssText = 'display:grid;grid-template-columns:3fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;';
    div.innerHTML = `
        <select name="items[${idx}][inventory_item_id]" class="form-control" required>
            <option value="">{{ __('public.stf_supply_po_select_item') }}</option>
            ${itemOptions}
        </select>
        <input type="number" name="items[${idx}][quantity_ordered]" class="form-control" placeholder="{{ __('public.stf_supply_po_ph_qty') }}" min="1" required onchange="recalcTotal()">
        <input type="number" name="items[${idx}][unit_price]" class="form-control" placeholder="{{ __('public.stf_supply_po_ph_unit_price') }}" min="0" step="0.0001" onchange="recalcTotal()">
        <button type="button" class="btn btn--sm btn--danger" onclick="removePOLine(this)">
            <i data-lucide="x"></i>
        </button>
    `;
    container.appendChild(div);
    // enable remove buttons when more than 1 line
    updateRemoveButtons();
    lucide.createIcons();
}

function removePOLine(btn){
    const line = btn.closest('.po-line');
    line.remove();
    updateRemoveButtons();
    recalcTotal();
}

function updateRemoveButtons(){
    const lines = document.querySelectorAll('#po-lines .po-line');
    lines.forEach((line, i) => {
        const btn = line.querySelector('button');
        btn.disabled = lines.length === 1;
    });
}

function recalcTotal(){
    let total = 0;
    document.querySelectorAll('#po-lines .po-line').forEach(line => {
        const qty = parseFloat(line.querySelector('input[name$="[quantity_ordered]"]')?.value) || 0;
        const price = parseFloat(line.querySelector('input[name$="[unit_price]"]')?.value) || 0;
        total += qty * price;
    });
    document.getElementById('po-total').textContent = total.toFixed(2);
}
</script>
@endsection
