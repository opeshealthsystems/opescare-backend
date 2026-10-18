{{-- Status pill. $tone: ok | low | reorder | expired | info | teal | neutral. $label: already translated. --}}
<span class="os-pill os-pill--{{ $tone ?? 'neutral' }}">{{ $label }}</span>
