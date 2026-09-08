@php
$steps = [
    1 => __('public.stf_import_wizard_upload'),
    2 => __('public.stf_import_wizard_map'),
    3 => __('public.stf_import_wizard_preview'),
    4 => __('public.stf_import_wizard_approve'),
];
@endphp
<div class="stepper">
    @foreach($steps as $num => $label)
        @php
            $active = $step === $num;
            $done   = $step > $num;
        @endphp
        <div class="stepper__step {{ $active ? 'active' : '' }} {{ $done ? 'done' : '' }}">
            <div class="stepper__dot">
                @if($done)
                    <i data-lucide="check"></i>
                @else
                    {{ $num }}
                @endif
            </div>
            <div class="stepper__label">{{ $label }}</div>
        </div>
    @endforeach
</div>
