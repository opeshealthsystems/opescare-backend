<?php $__env->startSection('title', __('leads.demo.page_title')); ?>
<?php $__env->startSection('meta_description', __('leads.demo.meta_description')); ?>

<?php $__env->startSection('head_scripts'); ?>
<style>
.demo-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (max-width: 600px) { .demo-form-row { grid-template-columns: 1fr; } }
.demo-input { width:100%;padding:0.625rem 0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;font-size:0.875rem;color:var(--color-text-primary);background:var(--color-bg);outline:none;box-sizing:border-box;font-family:inherit; }
.demo-label { display:block;font-size:0.8125rem;font-weight:700;color:var(--color-text-primary);margin-bottom:0.375rem; }
.demo-req { color:#DC2626; }
.demo-err { color:#DC2626;font-size:0.75rem;margin-top:4px; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<section class="content-header" style="background:linear-gradient(135deg,#0F2744 0%,#0F4C81 100%);padding:4rem 0 3rem;color:#fff;">
    <div class="container" style="text-align:center;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:2rem;padding:0.35rem 1rem;font-size:0.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#BAE6FD;margin-bottom:1.5rem;">
            <i data-lucide="calendar" style="width:0.875rem;height:0.875rem;"></i>
            <?php echo e(__('leads.demo.badge')); ?>

        </div>
        <h1 style="font-size:clamp(2rem,4vw,3rem);font-weight:900;margin:0 0 1rem;"><?php echo e(__('leads.demo.heading')); ?></h1>
        <p style="font-size:1.125rem;color:#BAE6FD;max-width:560px;margin:0 auto;"><?php echo e(__('leads.demo.subheading')); ?></p>
    </div>
</section>

<section style="padding:4rem 0;">
    <div class="container" style="max-width:680px;">

        <?php if(session('demo_success')): ?>
        <div style="text-align:center;background:var(--color-surface);border:1px solid #86EFAC;border-radius:1rem;padding:3rem 2.5rem;">
            <div style="width:3.5rem;height:3.5rem;border-radius:50%;background:#F0FDF4;display:inline-flex;align-items:center;justify-content:center;margin-bottom:1.25rem;">
                <i data-lucide="check-circle" style="width:1.75rem;height:1.75rem;color:#16A34A;"></i>
            </div>
            <h2 style="font-size:1.35rem;font-weight:800;color:var(--color-text-primary);margin:0 0 0.75rem;"><?php echo e(__('leads.demo.success_title')); ?></h2>
            <p style="font-size:0.95rem;color:var(--color-text-muted);margin:0 0 1.75rem;line-height:1.6;"><?php echo e(__('leads.demo.success_body')); ?></p>
            <a href="<?php echo e(route('public.pricing')); ?>" class="btn btn-primary"><i data-lucide="arrow-left" style="width:1rem;height:1rem;"></i> <?php echo e(__('leads.demo.success_cta')); ?></a>
        </div>
        <?php else: ?>
        <div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:1rem;padding:2.5rem;">
            <h2 style="font-size:1.25rem;font-weight:800;color:var(--color-text-primary);margin:0 0 0.5rem;"><?php echo e(__('leads.demo.form_title')); ?></h2>
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin:0 0 2rem;"><?php echo e(__('leads.demo.form_subtitle')); ?></p>

            <form method="POST" action="<?php echo e(route('public.request-demo.store')); ?>" novalidate>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="source" value="<?php echo e(request('source', 'request_demo')); ?>">

                <div class="demo-form-row">
                    <div>
                        <label class="demo-label"><?php echo e(__('leads.demo.field_org_name')); ?> <span class="demo-req">*</span></label>
                        <input type="text" name="organization_name" value="<?php echo e(old('organization_name')); ?>" required
                               class="demo-input" placeholder="<?php echo e(__('leads.demo.field_org_name_ph')); ?>">
                        <?php $__errorArgs = ['organization_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="demo-err"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="demo-label"><?php echo e(__('leads.demo.field_org_type')); ?> <span class="demo-req">*</span></label>
                        <select name="organization_type" required class="demo-input">
                            <option value=""><?php echo e(__('leads.demo.field_org_type_ph')); ?></option>
                            <?php $__currentLoopData = \App\Models\Lead::ORGANIZATION_TYPES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($type); ?>" <?php if(old('organization_type', $preselectedType)===$type): echo 'selected'; endif; ?>><?php echo e(__('leads.org_types.'.$type)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['organization_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="demo-err"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="demo-form-row">
                    <div>
                        <label class="demo-label"><?php echo e(__('leads.demo.field_name')); ?> <span class="demo-req">*</span></label>
                        <input type="text" name="name" value="<?php echo e(old('name')); ?>" required
                               class="demo-input" placeholder="<?php echo e(__('leads.demo.field_name_ph')); ?>">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="demo-err"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="demo-label"><?php echo e(__('leads.demo.field_email')); ?> <span class="demo-req">*</span></label>
                        <input type="email" name="email" value="<?php echo e(old('email')); ?>" required
                               class="demo-input" placeholder="<?php echo e(__('leads.demo.field_email_ph')); ?>">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="demo-err"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label class="demo-label"><?php echo e(__('leads.demo.field_phone')); ?></label>
                    <input type="text" name="phone" value="<?php echo e(old('phone')); ?>"
                           class="demo-input" placeholder="<?php echo e(__('leads.demo.field_phone_ph')); ?>">
                    <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="demo-err"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div style="margin-bottom:2rem;">
                    <label class="demo-label"><?php echo e(__('leads.demo.field_message')); ?></label>
                    <textarea name="message" rows="5" class="demo-input" style="resize:vertical;"
                              placeholder="<?php echo e(__('leads.demo.field_message_ph')); ?>"><?php echo e(old('message')); ?></textarea>
                    <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="demo-err"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit" class="btn btn-primary" style="justify-content:center;">
                    <i data-lucide="send" style="width:1rem;height:1rem;"></i>
                    <?php echo e(__('leads.demo.submit')); ?>

                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\request-demo.blade.php ENDPATH**/ ?>