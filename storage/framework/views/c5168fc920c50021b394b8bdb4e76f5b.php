<?php $__env->startSection('title', __('finder_story.contact.page_title')); ?>
<?php $__env->startSection('description', __('finder_story.contact.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <style>
        .fs-contact { display: grid; grid-template-columns: 1fr; gap: 20px; align-items: start; }
        @media (min-width: 960px) { .fs-contact { grid-template-columns: 2fr 3fr; } }
        .fs-aside-card { display: flex; align-items: flex-start; gap: 0.75rem; }
        .fs-aside-card > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-brand); }
        .fs-aside-card + .fs-aside-card { margin-top: 1rem; }
        .fs-form-row { display: grid; grid-template-columns: 1fr; gap: 0 16px; }
        @media (min-width: 640px) { .fs-form-row { grid-template-columns: 1fr 1fr; } }
        .fs-flash { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.875rem 1rem; border-radius: var(--f-r); background: var(--f-good-wash); color: var(--f-good); margin-bottom: 1.25rem; }
        .fs-flash > svg { flex-shrink: 0; margin-top: 0.125rem; }
        .fs-narrow { max-width: 720px; }
    </style>

    
    <section class="f-section f-section--surface f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow"><?php echo e(__('finder_story.contact.eyebrow')); ?></p>
                <h1 class="f-h1"><?php echo e(__('finder_story.contact.title')); ?></h1>
                <p class="f-lead"><?php echo e(__('finder_story.contact.lead')); ?></p>
            </div>
        </div>
    </section>

    
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-contact">

                <div>
                    <div class="f-card fs-aside-card">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div>
                            <h2 class="f-small"><?php echo e(__('finder_story.contact.report_title')); ?></h2>
                            <p class="f-small f-muted"><?php echo e(__('finder_story.contact.report_p')); ?></p>
                        </div>
                    </div>

                    <div class="f-card fs-aside-card">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'external-link', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div>
                            <h2 class="f-small"><?php echo e(__('finder_story.contact.email_title')); ?></h2>
                            <p class="f-small f-muted"><?php echo e(__('finder_story.contact.email_p')); ?></p>
                            <p class="f-small"><a href="mailto:support@opescare.com">support@opescare.com</a></p>
                        </div>
                    </div>

                    <div class="f-card fs-aside-card">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div>
                            <h2 class="f-small"><?php echo e(__('finder_story.contact.privacy_title')); ?></h2>
                            <p class="f-small f-muted"><?php echo e(__('finder_story.contact.privacy_p')); ?></p>
                        </div>
                    </div>
                </div>

                <div class="f-card">
                    <h2><?php echo e(__('public.contact.form_title')); ?></h2>

                    <?php if(session('contact_success')): ?>
                        <div class="fs-flash" role="status">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <span><?php echo e(__('public.contact.success_message')); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('public.contact.submit')); ?>" novalidate>
                        <?php echo csrf_field(); ?>

                        <div class="fs-form-row">
                            <div class="f-field">
                                <label class="f-label" for="fs-name"><?php echo e(__('public.contact.field_name')); ?> *</label>
                                <input class="f-input <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> f-input--invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="fs-name" type="text" name="name" value="<?php echo e(old('name')); ?>" required autocomplete="name">
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="f-field">
                                <label class="f-label" for="fs-email"><?php echo e(__('public.contact.field_email')); ?> *</label>
                                <input class="f-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> f-input--invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="fs-email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autocomplete="email">
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="f-field">
                            <label class="f-label" for="fs-organisation"><?php echo e(__('public.contact.field_organisation')); ?></label>
                            <input class="f-input" id="fs-organisation" type="text" name="organisation" value="<?php echo e(old('organisation')); ?>" autocomplete="organization">
                        </div>

                        <div class="f-field">
                            <label class="f-label" for="fs-subject"><?php echo e(__('public.contact.field_subject')); ?> *</label>
                            <select class="f-input <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> f-input--invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="fs-subject" name="subject" required>
                                <option value=""><?php echo e(__('public.contact.field_subject_placeholder')); ?></option>
                                <option value="technical" <?php if(old('subject') === 'technical'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_technical')); ?></option>
                                <option value="onboarding" <?php if(old('subject') === 'onboarding'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_onboarding')); ?></option>
                                <option value="partnership" <?php if(old('subject') === 'partnership'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_partnership')); ?></option>
                                <option value="billing" <?php if(old('subject') === 'billing'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_billing')); ?></option>
                                <option value="privacy" <?php if(old('subject') === 'privacy'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_privacy')); ?></option>
                                <option value="other" <?php if(old('subject') === 'other'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_other')); ?></option>
                            </select>
                            <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="f-field">
                            <label class="f-label" for="fs-message"><?php echo e(__('public.contact.field_message')); ?> *</label>
                            <textarea class="f-input <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> f-input--invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="fs-message" name="message" rows="6" required><?php echo e(old('message')); ?></textarea>
                            <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <button type="submit" class="f-btn f-btn--primary">
                            <?php echo e(__('public.contact.form_submit')); ?>

                            <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\public\contact.blade.php ENDPATH**/ ?>