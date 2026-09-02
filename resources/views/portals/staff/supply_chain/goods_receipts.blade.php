@extends('layouts.portal')
@section('title', __('public.stf_supply_gr_title') . ' — Supply Chain')
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_gr_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_gr_subtitle') }}</p>
        </div>
        <button class="btn btn--primary" onclick="openModal('createModal')">
            <i data-lucide="package-check"></i> {{ __('public.stf_supply_gr_btn_record') }}
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
                        <th>{{ __('public.stf_supply_gr_col_gr_number') }}</th>
                        <th>{{ __('public.stf_supply_gr_col_po_ref') }}</th>
                        <th>{{ __('public.stf_supply_gr_col_supplier') }}</th>
                        <th>{{ __('public.stf_supply_gr_col_received_by') }}</th>
                        <th>{{ __('public.stf_supply_gr_col_receipt_date') }}</th>
                        <th>{{ __('public.stf_supply_gr_col_lines') }}</th>
                        <th>{{ __('public.stf_supply_gr_col_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($goodsReceipts as $gr)
                        <tr>
                            <td data-label="{{ __('public.stf_supply_gr_col_gr_number') }}">
                                <div class="td-strong">{{ $gr->receipt_number ?: '—' }}</div>
                            </td>
                            <td data-label="{{ __('public.stf_supply_gr_col_po_ref') }}">
                                {{ $gr->purchaseOrder->po_number ?? '—' }}
                            </td>
                            <td data-label="{{ __('public.stf_supply_gr_col_supplier') }}">
                                {{ $gr->purchaseOrder->supplier->name ?? '—' }}
                            </td>
                            <td data-label="{{ __('public.stf_supply_gr_col_received_by') }}" class="td-muted">{{ $gr->received_by ?: '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_gr_col_receipt_date') }}">{{ $gr->received_date?->format('d M Y') ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_gr_col_lines') }}">{{ __('public.stf_supply_gr_lines_count', ['count' => $gr->items->count()]) }}</td>
                            <td data-label="{{ __('public.stf_supply_gr_col_status') }}">
                                @php
                                    $grColor = match($gr->status) {
                                        'pending'  => 'warning',
                                        'verified' => 'success',
                                        'rejected' => 'danger',
                                        default    => 'default',
                                    };
                                @endphp
                                <span class="badge badge--{{ $grColor }}">{{ $gr->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="package-check"></i></div>
                                <p>{{ __('public.stf_supply_gr_empty') }}</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($goodsReceipts->hasPages())<div class="panel-footer">{{ $goodsReceipts->links() }}</div>@endif
    </div>

</div>

{{-- Create Goods Receipt Modal --}}
<div id="createModal" class="modal-backdrop mt-6" hidden onclick="if(event.target===this)closeModal('createModal')">
    <div class="modal modal--lg" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="package-check"></i> {{ __('public.stf_supply_gr_modal_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.supply.goods_receipts.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_gr_label_po') }}</label>
                        <select name="purchase_order_id" class="form-control" required>
                            <option value="">{{ __('public.stf_supply_gr_select_po') }}</option>
                            @foreach($openPOs as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.stf_supply_gr_label_date') }}</label>
                        <input type="date" name="received_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_gr_label_receipt_num') }}</label>
                        <input type="text" name="receipt_number" class="form-control" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.stf_supply_gr_label_received_by') }}</label>
                        <input type="text" name="received_by" class="form-control" maxlength="100">
                    </div>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label">{{ __('public.stf_supply_gr_label_notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                {{-- Receipt Lines --}}
                <div class="flex-between mb-6">
                    <strong>{{ __('public.stf_supply_gr_items_received') }}</strong>
                    <button type="button" class="btn btn--sm btn--outline" onclick="addGRLine()">
                        <i data-lucide="plus"></i> {{ __('public.stf_supply_gr_btn_add_line') }}
                    </button>
                </div>
                <div class="td-muted mb-6">{{ __('public.stf_supply_gr_line_hint') }}</div>
                <div id="gr-lines">
                    <div class="gr-line" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;">
                        <select name="lines[0][inventory_item_id]" class="form-control" required>
                            <option value="">{{ __('public.stf_supply_gr_select_item') }}</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="lines[0][quantity_received]" class="form-control" placeholder="{{ __('public.stf_supply_gr_ph_qty') }}" min="1" required>
                        <input type="text" name="lines[0][batch_number]" class="form-control" placeholder="{{ __('public.stf_supply_gr_ph_batch') }}" maxlength="80">
                        <input type="date" name="lines[0][expiry_date]" class="form-control">
                        <button type="button" class="btn btn--sm btn--danger" onclick="removeGRLine(this)" disabled>
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeModal('createModal')">{{ __('public.stf_supply_gr_btn_cancel') }}</button>
                <button type="submit" class="btn btn--primary">{{ __('public.stf_supply_gr_btn_post') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id){ document.getElementById(id).removeAttribute('hidden'); lucide.createIcons(); }
function closeModal(id){ document.getElementById(id).setAttribute('hidden',''); }

let grLineCount = 1;
const grItemOptions = `@foreach($items as $item)<option value="{{ $item->id }}">{{ addslashes($item->name) }}</option>@endforeach`;

function addGRLine(){
    const container = document.getElementById('gr-lines');
    const idx = grLineCount++;
    const div = document.createElement('div');
    div.className = 'gr-line';
    div.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;';
    div.innerHTML = `
        <select name="lines[${idx}][inventory_item_id]" class="form-control" required>
            <option value="">{{ __('public.stf_supply_gr_select_item') }}</option>
            ${grItemOptions}
        </select>
        <input type="number" name="lines[${idx}][quantity_received]" class="form-control" placeholder="{{ __('public.stf_supply_gr_ph_qty') }}" min="1" required>
        <input type="text" name="lines[${idx}][batch_number]" class="form-control" placeholder="{{ __('public.stf_supply_gr_ph_batch') }}" maxlength="80">
        <input type="date" name="lines[${idx}][expiry_date]" class="form-control">
        <button type="button" class="btn btn--sm btn--danger" onclick="removeGRLine(this)">
            <i data-lucide="x"></i>
        </button>
    `;
    container.appendChild(div);
    updateGRRemoveButtons();
    lucide.createIcons();
}

function removeGRLine(btn){
    btn.closest('.gr-line').remove();
    updateGRRemoveButtons();
}

function updateGRRemoveButtons(){
    const lines = document.querySelectorAll('#gr-lines .gr-line');
    lines.forEach(line => {
        const btn = line.querySelector('button');
        btn.disabled = lines.length === 1;
    });
}
</script>
@endsection
