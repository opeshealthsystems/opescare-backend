@extends('layouts.portal')
@section('title', __('doc_ex_cert.desk_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('doc_ex_cert.desk_title') }}</h1>
        <p class="od-page__sub">{{ __('doc_ex_cert.desk_subtitle') }}</p>
    </div>
    <div>
        <a href="{{ route('portals.certificates.create', ['type' => 'medical_certificate']) }}" class="od-btn od-btn--primary"><i data-lucide="file-badge"></i> <span>{{ __('doc_ex_cert.new_certificate') }}</span></a>
        <a href="{{ route('portals.certificates.create', ['type' => 'sick_note']) }}" class="od-btn od-btn--secondary"><i data-lucide="bed"></i> <span>{{ __('doc_ex_cert.new_sick_note') }}</span></a>
    </div>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('doc_ex_cert.issued_list') }} <span class="od-card__count">({{ $documents->count() }})</span></h2>
    </header>
    <div class="od-card__body">
        @if($documents->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="file-badge"></i></span>
                <div class="od-empty__title">{{ __('doc_ex_cert.empty') }}</div>
                <p class="od-empty__text">{{ __('doc_ex_cert.empty_desc') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('doc_ex_cert.col_number') }}</th>
                    <th>{{ __('doc_ex_cert.col_type') }}</th>
                    <th>{{ __('doc_ex_cert.col_patient') }}</th>
                    <th>{{ __('doc_ex_cert.col_issued') }}</th>
                    <th>{{ __('doc_ex_cert.col_pdf') }}</th>
                </tr></thead>
                <tbody>
                @foreach($documents as $doc)
                    @php $p = $patients[$doc->patient_id] ?? null; @endphp
                    <tr>
                        <td><span class="od-table__name">{{ $doc->document_number }}</span></td>
                        <td>{{ __('doc_ex_cert.title.' . $doc->document_type) }}</td>
                        <td>
                            <span class="od-table__name">{{ $p ? trim($p->first_name . ' ' . $p->last_name) : '—' }}</span>
                            @if($p?->health_id)<span class="od-table__sub">{{ $p->health_id }}</span>@endif
                        </td>
                        <td>{{ $doc->issued_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td><a class="od-btn od-btn--secondary od-btn--sm" href="{{ route('portals.certificates.pdf', $doc->id) }}"><i data-lucide="download"></i> <span>{{ __('doc_ex_cert.download') }}</span></a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

</div>
@endsection
