@extends('layouts.portal')
@section('title', __('public.adm_legal_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.legal') }}">{{ __('public.adm_legal_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_legal_breadcrumb_docs') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_legal_h2') }}</h2>
    <div class="page-head__spacer"></div>
    <button type="button" onclick="opOpenModal('newDocModal')" class="btn btn-primary btn-sm">
        <i data-lucide="plus"></i> {{ __('public.adm_legal_btn_new') }}
    </button>
</div>

<p class="td-muted mb-6">{{ __('public.adm_legal_subtitle') }}</p>

{{-- KPI Strip --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="file-text"></i><span class="stat-card__label">{{ __('public.adm_legal_stat_documents') }}</span></div>
        <div class="stat-card__value">{{ $stats['total_documents'] }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="layers"></i><span class="stat-card__label">{{ __('public.adm_legal_stat_versions') }}</span></div>
        <div class="stat-card__value">{{ $stats['total_versions'] }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle"></i><span class="stat-card__label">{{ __('public.adm_legal_stat_user_acceptances') }}</span></div>
        <div class="stat-card__value">{{ number_format($stats['user_acceptances']) }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="handshake"></i><span class="stat-card__label">{{ __('public.adm_legal_stat_partner_agreements') }}</span></div>
        <div class="stat-card__value">{{ number_format($stats['partner_acceptances']) }}</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="scale"></i> {{ __('public.adm_legal_panel_title') }}</h3>
        <div class="row-actions-inline">
            <a href="{{ route('portals.admin.legal.closures') }}" class="btn btn-secondary btn-sm">{{ __('public.adm_legal_btn_closures') }}</a>
            <a href="{{ route('portals.admin.legal.complaints') }}" class="btn btn-secondary btn-sm">{{ __('public.adm_legal_btn_complaints') }}</a>
            <a href="{{ route('portals.admin.legal.minor_transitions') }}" class="btn btn-secondary btn-sm">{{ __('public.adm_legal_btn_minor_transitions') }}</a>
            <a href="{{ route('public.legal') }}" target="_blank" class="btn btn-secondary btn-sm">
                <i data-lucide="external-link"></i> {{ __('public.adm_legal_btn_public_view') }}
            </a>
        </div>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_col_title') }}</th><th>{{ __('public.adm_legal_col_type') }}</th><th>{{ __('public.adm_legal_col_language') }}</th><th>{{ __('public.adm_legal_col_current_version') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th class="row-actions"></th></tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    @php $ver = $doc->versions->first(); @endphp
                    <tr>
                        <td data-label="{{ __('public.adm_legal_col_title') }}" class="td-strong">{{ $doc->title }}</td>
                        <td data-label="{{ __('public.adm_legal_col_type') }}">
                            <span class="badge badge-primary badge-sm">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_language') }}">{{ strtoupper($doc->language) }}</td>
                        <td data-label="{{ __('public.adm_legal_col_current_version') }}">
                            @if($ver)
                                <span class="mono kv-strong">v{{ $ver->version }}</span>
                                @if($ver->requires_reacceptance)
                                    <span class="badge badge-warning badge-sm">{{ __('public.adm_legal_reaccept_req') }}</span>
                                @endif
                            @else
                                <span class="td-muted">{{ __('public.adm_legal_no_version') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="badge {{ $doc->is_active ? 'badge-success' : 'badge-neutral' }} badge-sm">
                                {{ $doc->is_active ? __('public.adm_legal_status_active') : __('public.adm_legal_status_inactive') }}
                            </span>
                        </td>
                        <td class="row-actions">
                            <a href="{{ route('portals.admin.legal.show', $doc) }}" class="btn btn-secondary btn-sm">{{ __('public.adm_legal_btn_manage') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">
                        {{ __('public.adm_legal_empty_docs') }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- New Document Modal --}}
<div id="newDocModal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--lg">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.adm_legal_modal_new_title') }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="opCloseModal('newDocModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="{{ route('portals.admin.legal.store') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label form-label-required">{{ __('public.adm_legal_modal_slug_label') }}</label>
                <input type="text" name="slug" class="form-control" placeholder="terms-of-use" required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label form-label-required">{{ __('public.adm_legal_modal_title_label') }}</label>
                <input type="text" name="title" class="form-control" placeholder="{{ __('public.aria_ph_legal_title') }}" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label form-label-required">{{ __('public.adm_legal_modal_type_label') }}</label>
                <select name="document_type" class="form-control" required>
                    <option value="terms">{{ __('public.adm_legal_modal_type_terms') }}</option>
                    <option value="privacy">{{ __('public.adm_legal_modal_type_privacy') }}</option>
                    <option value="consent">{{ __('public.adm_legal_modal_type_consent') }}</option>
                    <option value="dpa">{{ __('public.adm_legal_modal_type_dpa') }}</option>
                    <option value="facility_agreement">{{ __('public.adm_legal_modal_type_facility') }}</option>
                    <option value="api_terms">{{ __('public.adm_legal_modal_type_api') }}</option>
                </select>
            </div>
            <div class="modal__footer">
                <button type="button" onclick="opCloseModal('newDocModal')" class="btn btn-ghost">{{ __('public.adm_legal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_legal_btn_create') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).classList.add('open'); }
function opCloseModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-fixed').forEach(function(m){
    m.addEventListener('click', function(e){ if(e.target===m) m.classList.remove('open'); });
});
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-fixed').forEach(function(m){ m.classList.remove('open'); }); }
});
</script>
@endsection
