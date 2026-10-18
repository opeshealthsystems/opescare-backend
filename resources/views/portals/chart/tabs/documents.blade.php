{{-- A listing only. The PDF routes belong to the patient (rendered on demand,
     never stored), so the chart names the document and does not open it. --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.tabs.documents') }}</h2>
    </div>
    <div class="od-card__body">
        @if($documents->isEmpty())
            <p class="od-muted">{{ __('chart.documents_none') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
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
                            <td class="od-muted">{{ $d->issued_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="odc-strong">{{ $d->title ?: '—' }}</td>
                            <td>@if($d->document_type)@enum($d->document_type)@else —@endif</td>
                            <td class="od-muted">{{ $d->document_number ?: '—' }}</td>
                            <td>{{ $d->facility?->name ?: '—' }}</td>
                            <td><span class="od-pill od-pill--ready">@enum($d->status)</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
