@extends('layouts.portal')
@section('title', __('public.adm_patients_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_patients_idx_breadcrumb'))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.patients.index') }}">{{ __('public.adm_patients_idx_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_patients_idx_breadcrumb_registry') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_patients_idx_title') }}</h2>
    <div class="page-head__spacer"></div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<form method="GET" action="{{ route('admin.patients.index') }}" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('public.adm_patients_idx_ph_search') }}" aria-label="{{ __('public.adm_patients_idx_ph_search') }}">
    </label>
    <select name="identity_status" class="filter-select" aria-label="{{ __('public.aria_identity_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.adm_patients_idx_filter_all') }}</option>
        @foreach(['provisional','verified','flagged','deceased'] as $s)<option value="{{ $s }}" {{ request('identity_status')===$s?'selected':'' }}>@enum($s)</option>@endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('public.adm_patients_idx_btn_filter') }}</button>
    <a href="{{ route('admin.patients.index') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_patients_idx_btn_reset') }}</a>
</form>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> {{ $patients->total() }} {{ __('public.adm_patients_idx_breadcrumb') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.adm_patients_idx_col_health_id') }}</th>
                    <th>{{ __('public.adm_patients_idx_col_name') }}</th>
                    <th>{{ __('public.adm_patients_idx_col_dob') }}</th>
                    <th>{{ __('public.adm_patients_idx_col_sex') }}</th>
                    <th>{{ __('public.adm_patients_idx_col_identity') }}</th>
                    <th>{{ __('public.adm_patients_idx_col_created') }}</th>
                    <th class="row-actions">{{ __('public.adm_patients_idx_col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($patients as $patient)
                @php $ist=$patient->identity_status??'provisional'; @endphp
                <tr>
                    <td data-label="{{ __('public.adm_patients_idx_col_health_id') }}"><span class="td-mono">{{ $patient->health_id }}</span></td>
                    <td data-label="{{ __('public.adm_patients_idx_col_name') }}"><span class="td-strong">{{ $patient->first_name }} {{ $patient->last_name }}</span></td>
                    <td data-label="{{ __('public.adm_patients_idx_col_dob') }}" class="td-muted">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d M Y') : '—' }}</td>
                    <td data-label="{{ __('public.adm_patients_idx_col_sex') }}">{{ ucfirst($patient->sex??'') }}</td>
                    <td data-label="{{ __('public.adm_patients_idx_col_identity') }}">
                        @if($ist==='verified')<span class="badge badge-success">{{ __('public.adm_patients_idx_badge_verified') }}</span>
                        @elseif($ist==='flagged')<span class="badge badge-danger">{{ __('public.adm_patients_idx_badge_flagged') }}</span>
                        @elseif($ist==='deceased')<span class="badge badge-neutral">{{ __('public.adm_patients_idx_badge_deceased') }}</span>
                        @else<span class="badge badge-warning">{{ __('public.adm_patients_idx_badge_provisional') }}</span>@endif
                    </td>
                    <td data-label="{{ __('public.adm_patients_idx_col_created') }}" class="td-muted">{{ $patient->created_at?->format('d M Y') }}</td>
                    <td class="row-actions" data-label="{{ __('public.adm_patients_idx_col_actions') }}">
                        <a href="{{ route('admin.patients.show', $patient->id) }}" class="icon-btn" aria-label="{{ __('public.aria_view_patient') }}" title="{{ __('admin_extra.title_view', [], app()->getLocale()) ?: 'View' }}"><i data-lucide="eye"></i></a>
                        @if($ist!=='verified')
                        <form method="POST" action="{{ route('admin.patients.activate',$patient->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.adm_patients_idx_aria_verify') }}" title="{{ __('public.adm_patients_idx_aria_verify') }}"><i data-lucide="check-circle"></i></button>
                        </form>
                        @endif
                        @if($ist!=='flagged')
                        <form method="POST" action="{{ route('admin.patients.suspend',$patient->id) }}" class="inline-form">@csrf
                            <button type="submit" class="icon-btn" aria-label="{{ __('public.adm_patients_idx_aria_flag') }}" title="{{ __('public.adm_patients_idx_aria_flag') }}"><i data-lucide="flag"></i></button>
                        </form>
                        @endif
                        <button type="button" class="icon-btn" aria-label="{{ __('public.adm_patients_idx_aria_delete') }}" title="{{ __('public.adm_patients_idx_aria_delete') }}" onclick="opOpenModal('delete-patient-{{ $patient->id }}')"><i data-lucide="trash-2"></i></button>
                        <div id="delete-patient-{{ $patient->id }}" class="modal-backdrop mt-6" hidden>
                            <div class="modal" role="dialog" aria-modal="true">
                                <h3 class="modal__title"><i data-lucide="alert-triangle"></i> {{ __('public.adm_patients_idx_modal_delete_title') }}</h3>
                                <form method="POST" action="{{ route('admin.patients.destroy',$patient->id) }}">@csrf @method('DELETE')
                                    <div class="modal__body"><p>{{ __('public.adm_patients_idx_modal_delete_body_before') }} <strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong>? {{ __('public.adm_patients_idx_cannot_undo') }}</p></div>
                                    <div class="modal__footer">
                                        <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-patient-{{ $patient->id }}')">{{ __('public.adm_patients_idx_btn_cancel') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ __('public.adm_patients_idx_btn_delete') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="td-muted empty-cell">{{ __('public.adm_patients_idx_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $patients->links() }}</div>
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
