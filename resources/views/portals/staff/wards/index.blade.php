@extends('layouts.portal')
@section('title', __('public.staff_wards.page_title', [], app()->getLocale()) ?: 'Ward & Bed Management')
@section('breadcrumb_home', __('public.portal.nav_dashboard', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_wards.breadcrumb', [], app()->getLocale()) ?: 'Wards')
@php $l = app()->getLocale(); @endphp

@section('content')
<div class="page-head">
    <h2>{{ __('public.staff_wards.page_heading', [], $l) ?: 'Ward & Bed Management' }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.wards.admissions') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="bed"></i> {{ __('public.staff_wards.btn_admissions', [], $l) ?: 'Admissions' }}
    </a>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCreateWardModal()">
        <i data-lucide="plus"></i> {{ __('public.staff_wards.btn_add_ward', [], $l) ?: 'Add Ward' }}
    </button>
</div>
<p class="page-subtitle mb-6">{{ __('public.staff_wards.page_subtitle', [], $l) ?: 'Live bed map, occupancy overview, and ward administration.' }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Occupancy KPIs --}}
@php
    $occRate = $summary['occupancy_rate'];
    $occMod  = $occRate >= 85 ? 'stat-card--danger' : ($occRate >= 70 ? 'stat-card--warning' : 'stat-card--success');
    $kpis = [
        [__('public.staff_wards.stat_total_beds', [], app()->getLocale()) ?: 'Total Beds',      $summary['total_beds'],      'bed',          ''],
        [__('public.staff_wards.stat_occupied', [], app()->getLocale()) ?: 'Occupied',          $summary['total_occupied'],  'user-check',   'stat-card--danger'],
        [__('public.staff_wards.stat_available', [], app()->getLocale()) ?: 'Available',        $summary['total_available'], 'check-circle', 'stat-card--success'],
        [__('public.staff_wards.stat_occupancy_rate', [], app()->getLocale()) ?: 'Occupancy Rate', $occRate.'%',             'percent',      $occMod],
    ];
@endphp
<div class="stat-grid mb-6">
    @foreach($kpis as [$label, $val, $icon, $mod])
    <div class="stat-card {{ $mod }}">
        <div class="stat-card__head"><i data-lucide="{{ $icon }}"></i></div>
        <div class="stat-card__value">{{ $val }}</div>
        <div class="stat-card__label">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Ward cards with bed map --}}
@if($wards->isEmpty())
<div class="empty-state">
    <div class="empty-state-icon"><i data-lucide="building-2"></i></div>
    <h3>{{ __('public.staff_wards.no_wards_title', [], $l) ?: 'No active wards' }}</h3>
    <p>{{ __('public.staff_wards.no_wards_desc', [], $l) ?: 'Create a ward to start managing bed allocations.' }}</p>
    <button type="button" class="btn btn-primary btn-sm mt-6" onclick="openCreateWardModal()">{{ __('public.staff_wards.btn_add_first_ward', [], $l) ?: 'Add First Ward' }}</button>
</div>
@else
@foreach($wards as $ward)
@php
    $occupied  = $ward->beds->where('status','occupied')->count();
    $available = $ward->beds->where('status','available')->count();
    $total     = $ward->beds->count();
    $occ       = $total > 0 ? round(($occupied / $total) * 100) : 0;
    $barFill   = $occ >= 90 ? 'breakdown__fill--danger' : ($occ >= 70 ? 'breakdown__fill--warning' : '');
    $occBadge  = $occ >= 90 ? 'badge-danger' : ($occ >= 70 ? 'badge-warning' : 'badge-success');
@endphp
<div class="panel mb-6">
    <div class="panel-header">
        <span class="section-head">
            <h3 class="panel-title">{{ $ward->name }}</h3>
            <span class="badge badge-neutral badge-sm">{{ \App\Models\Ward::wardTypes()[$ward->ward_type] ?? $ward->ward_type }}</span>
            @if($ward->floor)<span class="td-muted">{{ __('staff_clinical.lbl_floor_n', ['n' => $ward->floor], $l) ?: ('Floor ' . $ward->floor) }}</span>@endif
        </span>
        <span class="section-head">
            <span class="td-muted">{{ $occupied }}/{{ $total }} {{ __('public.stf_wards_idx_occupied_label') }}</span>
            <span class="badge {{ $occBadge }}">{{ $occ }}%</span>
        </span>
    </div>
    <div class="panel-body">
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
            @foreach($ward->beds->sortBy('bed_number') as $bed)
            @php
                $bedColor = match($bed->status) {
                    'occupied'    => ['bg'=>'rgba(239,68,68,.12)', 'border'=>'rgba(239,68,68,.4)', 'text'=>'var(--p-danger)'],
                    'maintenance' => ['bg'=>'rgba(245,158,11,.12)', 'border'=>'rgba(245,158,11,.4)', 'text'=>'var(--p-warning)'],
                    'reserved'    => ['bg'=>'rgba(59,130,246,.12)', 'border'=>'rgba(59,130,246,.4)', 'text'=>'var(--p-primary)'],
                    default       => ['bg'=>'rgba(34,197,94,.1)',   'border'=>'rgba(34,197,94,.4)',  'text'=>'var(--p-success)'],
                };
                $patient = $bed->activeAdmission?->patient;
            @endphp
            <div title="{{ $bed->bed_number }} — @enum($bed->status){{ $patient ? ' — '.$patient->health_id : '' }}"
                 style="background:{{ $bedColor['bg'] }};border:1px solid {{ $bedColor['border'] }};border-radius:6px;
                        padding:.3rem .55rem;min-width:50px;text-align:center;cursor:default;">
                <div class="bed-label" style="color:{{ $bedColor['text'] }};">{{ $bed->bed_number }}</div>
                @if($patient)
                    <div class="bed-caption text-muted">{{ $patient->health_id }}</div>
                @else
                    <div class="bed-status" style="color:{{ $bedColor['text'] }};">@enum($bed->status)</div>
                @endif
                @if($bed->has_oxygen)
                    <div class="bed-o2">O₂</div>
                @endif
            </div>
            @endforeach
        </div>
        {{-- Legend --}}
        <div style="display:flex;gap:1rem;margin-top:.75rem;font-size:.72rem;color:var(--p-text-muted);">
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(34,197,94,.3);border-radius:2px;margin-right:3px;"></span>{{ __('public.staff_wards.legend_available', [], $l) ?: 'Available' }} ({{ $available }})</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(239,68,68,.3);border-radius:2px;margin-right:3px;"></span>{{ __('public.staff_wards.legend_occupied', [], $l) ?: 'Occupied' }} ({{ $occupied }})</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(245,158,11,.3);border-radius:2px;margin-right:3px;"></span>{{ __('public.staff_wards.legend_maintenance', [], $l) ?: 'Maintenance' }}</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:rgba(59,130,246,.3);border-radius:2px;margin-right:3px;"></span>{{ __('public.staff_wards.legend_reserved', [], $l) ?: 'Reserved' }}</span>
        </div>
    </div>
</div>
@endforeach
@endif

{{-- Create Ward Modal --}}
<div id="create-ward-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal modal--md" role="dialog" aria-modal="true">
        <h3 class="modal__title"><i data-lucide="building-2"></i> {{ __('public.staff_wards.modal_create_title', [], $l) ?: 'Create Ward' }}</h3>
        <form method="POST" action="{{ route('portals.staff.wards.store') }}">
            @csrf
            <div class="modal__body">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.staff_wards.field_ward_name', [], $l) ?: 'Ward Name' }}</label>
                    <input type="text" name="name" class="form-control" required maxlength="100" placeholder="{{ __('staff_clinical.ph_ward_name', [], $l) ?: 'e.g. General Ward A' }}">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.staff_wards.field_ward_type', [], $l) ?: 'Ward Type' }}</label>
                        <select name="ward_type" class="form-control" required>
                            @foreach(\App\Models\Ward::wardTypes() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required">{{ __('public.staff_wards.field_num_beds', [], $l) ?: 'Number of Beds' }}</label>
                        <input type="number" name="total_beds" class="form-control" required min="1" max="200" value="10">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('public.staff_wards.field_floor', [], $l) ?: 'Floor' }}</label>
                        <input type="text" name="floor" class="form-control" maxlength="20" placeholder="{{ __('staff_clinical.ph_floor', [], $l) ?: 'e.g. 2' }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('public.staff_wards.field_building', [], $l) ?: 'Building' }}</label>
                        <input type="text" name="building" class="form-control" maxlength="50" placeholder="{{ __('staff_clinical.ph_building', [], $l) ?: 'e.g. Block A' }}">
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeCreateWardModal()">{{ __('public.staff_wards.btn_cancel', [], $l) ?: 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('public.staff_wards.btn_create_ward', [], $l) ?: 'Create Ward' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openCreateWardModal()  { document.getElementById('create-ward-modal').removeAttribute('hidden'); }
function closeCreateWardModal() { document.getElementById('create-ward-modal').setAttribute('hidden',''); }
document.getElementById('create-ward-modal').addEventListener('click', function(e) { if(e.target===this) closeCreateWardModal(); });
</script>
@endsection
