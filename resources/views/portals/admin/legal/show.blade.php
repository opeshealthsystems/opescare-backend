@extends('layouts.portal')
@section('title', $document->title)
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.legal') }}">{{ __('public.adm_legal_closures_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $document->title }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="scale"></i></div>
    <div>
        <h2 class="entity-head__title">{{ $document->title }}</h2>
        <div class="entity-head__sub">
            <span class="badge badge-primary badge-sm">{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</span>
            <span class="td-muted text-sm">{{ strtoupper($document->language) }}</span>
        </div>
    </div>
    <div class="entity-head__spacer"></div>
    <button type="button" onclick="opOpenModal('publishModal')" class="btn btn-primary btn-sm">
        <i data-lucide="upload"></i> {{ __('public.adm_legal_show_btn_publish') }}
    </button>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Versions table --}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="layers"></i> {{ __('public.adm_legal_show_versions_title') }}</h3></div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_show_col_version') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_show_col_reaccept') }}</th><th>{{ __('public.adm_legal_show_col_published') }}</th><th>{{ __('public.adm_legal_show_col_effective') }}</th><th>{{ __('public.adm_legal_show_col_change_summary') }}</th></tr>
            </thead>
            <tbody>
                @forelse($versions as $ver)
                    <tr class="{{ $ver->is_current ? 'row-emergency' : '' }}">
                        <td data-label="{{ __('public.adm_legal_show_col_version') }}">
                            <span class="mono kv-strong">v{{ $ver->version }}</span>
                            @if($ver->is_current)
                                <span class="badge badge-success badge-sm">{{ __('public.adm_legal_show_status_current') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="badge {{ $ver->isEffective() ? 'badge-success' : 'badge-warning' }} badge-sm">
                                {{ $ver->isEffective() ? __('public.adm_legal_show_status_live') : __('public.adm_legal_show_status_scheduled') }}
                            </span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_reaccept') }}">
                            @if($ver->requires_reacceptance)
                                <span class="badge badge-danger badge-sm">{{ __('admin_extra.yes', [], app()->getLocale()) ?: 'Yes' }}</span>
                            @else
                                <span class="td-muted">{{ __('admin_extra.no', [], app()->getLocale()) ?: 'No' }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_published') }}" class="td-muted">
                            {{ $ver->published_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_effective') }}" class="td-muted">
                            {{ $ver->effective_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td data-label="{{ __('public.adm_legal_show_col_change_summary') }}">{{ $ver->change_summary ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td-muted empty-cell">{{ __('public.adm_legal_show_empty_versions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Current version preview --}}
@php $current = $versions->where('is_current', true)->first(); @endphp
@if($current)
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('public.adm_legal_show_content_title') }} (v{{ $current->version }})</h3>
        <a href="{{ route('public.legal.show', $document->slug) }}" target="_blank" class="btn btn-secondary btn-sm">
            <i data-lucide="external-link"></i> {{ __('public.adm_legal_show_btn_public_view') }}
        </a>
    </div>
    <div class="panel-body">
        <div class="legal-preview">
            {!! $current->content_html !!}
        </div>
    </div>
</div>
@endif

{{-- Publish New Version Modal --}}
<div id="publishModal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title">{{ __('public.adm_legal_show_modal_title') }} — {{ $document->title }}</h3>
            <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="opCloseModal('publishModal')"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="{{ route('portals.admin.legal.publish_version', $document) }}">
            @csrf
            <div class="form-row mb-3">
                <div class="form-group">
                    <label class="form-label form-label-required">{{ __('public.adm_legal_show_modal_version_label') }}</label>
                    <input type="text" name="version" class="form-control" placeholder="1.0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.adm_legal_show_modal_effective_label') }}</label>
                    <input type="date" name="effective_at" class="form-control">
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">{{ __('public.adm_legal_show_modal_summary_label') }}</label>
                <input type="text" name="change_summary" class="form-control" placeholder="{{ __('admin_extra.ph_change_summary', [], app()->getLocale()) ?: 'What changed in this version?' }}">
            </div>
            <div class="form-group mb-3">
                <label class="form-label form-label-required">{{ __('public.adm_legal_show_modal_content_label') }}</label>
                <textarea name="content_html" rows="12" class="form-control mono" required placeholder="<h1>{{ __('admin_extra.legal_terms_of_use', [], app()->getLocale()) ?: 'Terms of Use' }}</h1><p>...</p>"></textarea>
            </div>
            <div class="form-group mb-4">
                <label class="form-check">
                    <input type="checkbox" id="reaccept" name="requires_reacceptance" value="1">
                    {{ __('public.adm_legal_show_modal_reaccept_label') }}
                </label>
            </div>
            <div class="modal__footer">
                <button type="button" onclick="opCloseModal('publishModal')" class="btn btn-ghost">{{ __('public.adm_legal_btn_cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('public.adm_legal_show_btn_publish_version') }}</button>
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
