@props([
    'action',           // form action URL
    'method' => 'POST', // HTTP method
    'label',            // Button label shown to user
    'title',            // Modal dialog title
    'description',      // What the action does (warn clearly)
    'confirmLabel' => null,    // Override confirm button text
    'cancelLabel'  => null,    // Override cancel button text
    'icon'         => 'alert-triangle',
    'color'        => 'danger', // 'danger' | 'warning'
    'requireReason' => false,  // Whether to require a typed reason
    'buttonClass'  => 'btn btn-danger btn-sm',
    'extraFields'  => [],      // Extra hidden fields ['name' => 'value']
])
@php
    $l       = app()->getLocale();
    $modalId = 'hrm-' . md5($action . $label);
    $confirmText = $confirmLabel ?? (__('public.portal.confirm', [], $l) ?: 'Confirm');
    $cancelText  = $cancelLabel  ?? (__('public.portal.cancel', [], $l)  ?: 'Cancel');
    $borderColor = $color === 'danger' ? 'var(--p-danger)' : 'var(--p-warning)';
    $iconColor   = $color === 'danger' ? 'var(--p-danger)' : 'var(--p-warning)';
@endphp

<style>
.hrm-backdrop { display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;background:rgba(0,0,0,.55);padding:1rem; }
.hrm-backdrop.hrm-open { display:flex; }
.hrm-dialog { background:var(--p-surface);border-radius:var(--p-radius-lg);padding:2rem;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.4); }
.hrm-dialog-danger  { border:1.5px solid var(--p-danger); }
.hrm-dialog-warning { border:1.5px solid var(--p-warning); }
.hrm-header { display:flex;align-items:flex-start;gap:.9rem;margin-bottom:1.25rem; }
.hrm-icon { flex-shrink:0;margin-top:1px;width:1.5rem;height:1.5rem; }
.hrm-icon-danger  { color:var(--p-danger); }
.hrm-icon-warning { color:var(--p-warning); }
.hrm-title { font-size:1rem;font-weight:700;color:var(--p-text);margin:0 0 .4rem; }
.hrm-desc  { font-size:.875rem;color:var(--p-text-muted);margin:0;line-height:1.5; }
.hrm-reason-wrap { margin-bottom:1rem; }
.hrm-reason-label { display:block;font-size:.8125rem;font-weight:600;color:var(--p-text);margin-bottom:.4rem; }
.hrm-reason-textarea { width:100%;padding:.5rem .75rem;font-size:.875rem;border:1px solid var(--p-border);border-radius:var(--p-radius);background:var(--p-surface-2);color:var(--p-text);resize:vertical; }
.hrm-actions { display:flex;gap:.75rem;justify-content:flex-end; }
.hrm-trigger-icon { width:.9rem;height:.9rem;display:inline;vertical-align:middle;margin-right:4px; }
.hrm-confirm-icon { width:.85rem;height:.85rem;display:inline;vertical-align:middle;margin-right:4px; }
.hrm-icon-lg { width:1.5rem;height:1.5rem; }
</style>

{{-- Trigger button --}}
<button type="button"
        class="{{ $buttonClass }}"
        onclick="document.getElementById('{{ $modalId }}').classList.add('hrm-open')"
        aria-haspopup="dialog"
        aria-controls="{{ $modalId }}">
    <i data-lucide="{{ $icon }}" class="hrm-trigger-icon"></i>
    {{ $label }}
</button>

{{-- Modal overlay --}}
<div id="{{ $modalId }}"
     class="hrm-backdrop"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $modalId }}-title">
    <div class="hrm-dialog hrm-dialog-{{ $color }}">

        <div class="hrm-header">
            <div class="hrm-icon hrm-icon-{{ $color }}">
                <i data-lucide="triangle-alert" class="hrm-icon-lg"></i>
            </div>
            <div>
                <h3 id="{{ $modalId }}-title" class="hrm-title">
                    {{ $title }}
                </h3>
                <p class="hrm-desc">
                    {{ $description }}
                </p>
            </div>
        </div>

        <form method="POST" action="{{ $action }}">
            @csrf
            @if(strtoupper($method) !== 'POST')
                @method($method)
            @endif
            @foreach($extraFields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            @if($requireReason)
            <div class="hrm-reason-wrap">
                <label class="hrm-reason-label">
                    {{ __('public.portal.reason_required', [], $l) ?: 'Reason (required)' }}
                </label>
                <textarea name="reason"
                          required
                          minlength="10"
                          maxlength="500"
                          rows="3"
                          placeholder="{{ __('public.portal.reason_placeholder', [], $l) ?: 'Explain why this action is necessary…' }}"
                          class="hrm-reason-textarea"></textarea>
            </div>
            @endif

            <div class="hrm-actions">
                <button type="button"
                        onclick="document.getElementById('{{ $modalId }}').classList.remove('hrm-open')"
                        class="btn btn-ghost btn-sm">
                    {{ $cancelText }}
                </button>
                <button type="submit" class="btn btn-{{ $color }} btn-sm">
                    <i data-lucide="{{ $icon }}" class="hrm-confirm-icon"></i>
                    {{ $confirmText }}
                </button>
            </div>
        </form>

    </div>
</div>

{{-- Close on backdrop click --}}
<script>
(function() {
    var el = document.getElementById('{{ $modalId }}');
    if (el) {
        el.addEventListener('click', function(e) {
            if (e.target === el) el.classList.remove('hrm-open');
        });
    }
})();
</script>
