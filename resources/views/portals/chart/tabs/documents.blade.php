{{-- A listing only. The PDF routes belong to the patient (rendered on demand,
     never stored), so the chart names the document and does not open it. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="files"></i> {{ __('chart.tabs.documents') }}</h2>
    </div>
    <div class="panel-body">
        @if($documents->isEmpty())
            <p class="td-muted">{{ __('chart.documents_none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>{{ __('chart.col_issued') }}</th>
                        <th>{{ __('chart.col_document') }}</th>
                        <th>{{ __('chart.col_type') }}</th>
                        <th>{{ __('chart.col_number') }}</th>
                        <th>{{ __('chart.col_facility') }}</th>
                        <th>{{ __('chart.col_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($documents as $d)
                        <tr>
                            <td class="td-muted">{{ $d->issued_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="td-strong">{{ $d->title ?: '—' }}</td>
                            <td>@if($d->document_type)@enum($d->document_type)@else —@endif</td>
                            <td class="td-muted">{{ $d->document_number ?: '—' }}</td>
                            <td>{{ $d->facility?->name ?: '—' }}</td>
                            <td><span class="badge badge-success">@enum($d->status)</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
