<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
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
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
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
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $l       = app()->getLocale();
    $modalId = 'hrm-' . md5($action . $label);
    $confirmText = $confirmLabel ?? (__('public.portal.confirm', [], $l) ?: 'Confirm');
    $cancelText  = $cancelLabel  ?? (__('public.portal.cancel', [], $l)  ?: 'Cancel');
    $borderColor = $color === 'danger' ? 'var(--p-danger)' : 'var(--p-warning)';
    $iconColor   = $color === 'danger' ? 'var(--p-danger)' : 'var(--p-warning)';
?>

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


<button type="button"
        class="<?php echo e($buttonClass); ?>"
        onclick="document.getElementById('<?php echo e($modalId); ?>').classList.add('hrm-open')"
        aria-haspopup="dialog"
        aria-controls="<?php echo e($modalId); ?>">
    <i data-lucide="<?php echo e($icon); ?>" class="hrm-trigger-icon"></i>
    <?php echo e($label); ?>

</button>


<div id="<?php echo e($modalId); ?>"
     class="hrm-backdrop"
     role="dialog"
     aria-modal="true"
     aria-labelledby="<?php echo e($modalId); ?>-title">
    <div class="hrm-dialog hrm-dialog-<?php echo e($color); ?>">

        <div class="hrm-header">
            <div class="hrm-icon hrm-icon-<?php echo e($color); ?>">
                <i data-lucide="triangle-alert" class="hrm-icon-lg"></i>
            </div>
            <div>
                <h3 id="<?php echo e($modalId); ?>-title" class="hrm-title">
                    <?php echo e($title); ?>

                </h3>
                <p class="hrm-desc">
                    <?php echo e($description); ?>

                </p>
            </div>
        </div>

        <form method="POST" action="<?php echo e($action); ?>">
            <?php echo csrf_field(); ?>
            <?php if(strtoupper($method) !== 'POST'): ?>
                <?php echo method_field($method); ?>
            <?php endif; ?>
            <?php $__currentLoopData = $extraFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($value); ?>">
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($requireReason): ?>
            <div class="hrm-reason-wrap">
                <label class="hrm-reason-label">
                    <?php echo e(__('public.portal.reason_required', [], $l) ?: 'Reason (required)'); ?>

                </label>
                <textarea name="reason"
                          required
                          minlength="10"
                          maxlength="500"
                          rows="3"
                          placeholder="<?php echo e(__('public.portal.reason_placeholder', [], $l) ?: 'Explain why this action is necessary…'); ?>"
                          class="hrm-reason-textarea"></textarea>
            </div>
            <?php endif; ?>

            <div class="hrm-actions">
                <button type="button"
                        onclick="document.getElementById('<?php echo e($modalId); ?>').classList.remove('hrm-open')"
                        class="btn btn-ghost btn-sm">
                    <?php echo e($cancelText); ?>

                </button>
                <button type="submit" class="btn btn-<?php echo e($color); ?> btn-sm">
                    <i data-lucide="<?php echo e($icon); ?>" class="hrm-confirm-icon"></i>
                    <?php echo e($confirmText); ?>

                </button>
            </div>
        </form>

    </div>
</div>


<script>
(function() {
    var el = document.getElementById('<?php echo e($modalId); ?>');
    if (el) {
        el.addEventListener('click', function(e) {
            if (e.target === el) el.classList.remove('hrm-open');
        });
    }
})();
</script>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\components\high-risk-confirm.blade.php ENDPATH**/ ?>