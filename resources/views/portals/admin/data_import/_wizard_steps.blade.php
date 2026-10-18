@php
$steps = [
    1 => __('public.stf_import_wizard_upload'),
    2 => __('public.stf_import_wizard_map'),
    3 => __('public.stf_import_wizard_preview'),
    4 => __('public.stf_import_wizard_approve'),
];
@endphp
<nav class="od-tabs oda-gb-steps" aria-label="{{ __('staff_data.title_import') }}">
    @foreach($steps as $num => $label)
        @php
            $active = $step === $num;
            $done   = $step > $num;
        @endphp
        <span class="od-tab {{ $active ? 'is-active' : '' }} {{ $done ? 'oda-gb-step--done' : '' }}" @if($active) aria-current="step" @endif>
            <span class="oda-gb-step__num">
                @if($done)
                    <i data-lucide="check"></i>
                @else
                    {{ $num }}
                @endif
            </span>
            {{ $label }}
        </span>
    @endforeach
</nav>
