{{-- Generic renderer for HealthPassportBuilder sections (list / table / callout). Prints exactly what the builder returns. --}}
@foreach(($sections ?? []) as $section)
    <h2>{{ $section['title'] ?? '' }}</h2>
    @if(($section['layout'] ?? '') === 'table')
        @if(empty($section['rows']))
            <p class="empty">{{ is_string($section['empty'] ?? null) ? $section['empty'] : __('doc_new_docs.none_recorded') }}</p>
        @else
            <table class="data">
                <thead><tr>@foreach(($section['columns'] ?? []) as $c)<th>{{ is_scalar($c) ? $c : '' }}</th>@endforeach</tr></thead>
                @foreach($section['rows'] as $row)
                    <tr>@foreach((array) $row as $cell)<td>{{ is_array($cell) ? (is_scalar($cell['v'] ?? null) ? $cell['v'] : '') : (is_scalar($cell) ? $cell : '') }}</td>@endforeach</tr>
                @endforeach
            </table>
        @endif
    @else
        @if(is_string($section['body'] ?? null) && $section['body'] !== '')<div class="box">{{ $section['body'] }}</div>@endif
        @if(! empty($section['items']))
            <table class="kv">
                @foreach($section['items'] as $item)
                    <tr><td class="k">{{ is_scalar($item['label'] ?? null) ? $item['label'] : '' }}</td><td class="v">{{ is_scalar($item['value'] ?? null) && $item['value'] !== '' ? $item['value'] : __('doc_new_docs.not_recorded') }}</td></tr>
                @endforeach
            </table>
        @elseif(! is_string($section['body'] ?? null) || $section['body'] === '')
            <p class="empty">{{ is_string($section['empty'] ?? null) ? $section['empty'] : __('doc_new_docs.none_recorded') }}</p>
        @endif
    @endif
@endforeach
