<?php $__env->startSection('title', __('public.contact.page_title')); ?>
<?php $__env->startSection('meta_description', __('public.contact.meta_description')); ?>

<?php $__env->startSection('head_scripts'); ?>
<style>
.contact-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 3rem; align-items: start; }
.contact-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (max-width: 900px) { .contact-layout { grid-template-columns: 1fr; gap: 2rem; } }
@media (max-width: 600px) { .contact-form-row { grid-template-columns: 1fr; } }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<section class="content-header" style="background:linear-gradient(135deg,#0F2744 0%,#0F4C81 100%);padding:4rem 0 3rem;color:#fff;">
    <div class="container" style="text-align:center;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:2rem;padding:0.35rem 1rem;font-size:0.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#BAE6FD;margin-bottom:1.5rem;">
            <i data-lucide="headset" style="width:0.875rem;height:0.875rem;"></i>
            <?php echo e(__('public.contact.badge')); ?>

        </div>
        <h1 style="font-size:clamp(2rem,4vw,3rem);font-weight:900;margin:0 0 1rem;"><?php echo e(__('public.contact.heading')); ?></h1>
        <p style="font-size:1.125rem;color:#BAE6FD;max-width:560px;margin:0 auto;"><?php echo e(__('public.contact.subheading')); ?></p>
    </div>
</section>

<section style="padding:4rem 0;">
    <div class="container" style="max-width:1100px;">
        <div class="contact-layout">

            <!-- Contact channels -->
            <div>
                <h2 style="font-size:1.125rem;font-weight:800;color:var(--color-text-primary);margin:0 0 1.5rem;"><?php echo e(__('public.contact.channels_title')); ?></h2>

                <div style="display:flex;flex-direction:column;gap:1.25rem;">
                    <div style="display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:0.75rem;">
                        <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:#EFF6FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="mail" style="width:1.125rem;height:1.125rem;color:#0F4C81;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:0.875rem;color:var(--color-text-primary);"><?php echo e(__('public.contact.channel_email_title')); ?></div>
                            <div style="font-size:0.8125rem;color:var(--color-text-muted);margin-top:0.25rem;">support@opescare.com</div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.25rem;"><?php echo e(__('public.contact.channel_email_note')); ?></div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:0.75rem;">
                        <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:#F0FDF4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="message-circle" style="width:1.125rem;height:1.125rem;color:#16A34A;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:0.875rem;color:var(--color-text-primary);"><?php echo e(__('public.contact.channel_chat_title')); ?></div>
                            <div style="font-size:0.8125rem;color:var(--color-text-muted);margin-top:0.25rem;"><?php echo e(__('public.contact.channel_chat_hours')); ?></div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.25rem;"><?php echo e(__('public.contact.channel_chat_note')); ?></div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:0.75rem;">
                        <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:#FFF7ED;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="building-2" style="width:1.125rem;height:1.125rem;color:#C2410C;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:0.875rem;color:var(--color-text-primary);"><?php echo e(__('public.contact.channel_partner_title')); ?></div>
                            <div style="font-size:0.8125rem;color:var(--color-text-muted);margin-top:0.25rem;">partners@opescare.com</div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.25rem;"><?php echo e(__('public.contact.channel_partner_note')); ?></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:2rem;padding:1.25rem;background:#F0FDF9;border:1px solid #99F6E4;border-radius:0.75rem;">
                    <div style="font-size:0.75rem;font-weight:700;color:#0F766E;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.4rem;">
                        <i data-lucide="shield-check" style="width:0.875rem;height:0.875rem;"></i>
                        <?php echo e(__('public.contact.privacy_note_title')); ?>

                    </div>
                    <p style="font-size:0.8rem;color:#0F766E;margin:0;line-height:1.5;"><?php echo e(__('public.contact.privacy_note_body')); ?></p>
                </div>
            </div>

            <!-- Contact form -->
            <div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:1rem;padding:2.5rem;">
                <h2 style="font-size:1.25rem;font-weight:800;color:var(--color-text-primary);margin:0 0 0.5rem;"><?php echo e(__('public.contact.form_title')); ?></h2>
                <p style="font-size:0.875rem;color:var(--color-text-muted);margin:0 0 2rem;"><?php echo e(__('public.contact.form_subtitle')); ?></p>

                <?php if(session('contact_success')): ?>
                <div style="display:flex;align-items:center;gap:0.75rem;background:#F0FDF4;border:1px solid #86EFAC;border-radius:0.5rem;padding:1rem;margin-bottom:1.5rem;font-size:0.875rem;color:#166534;">
                    <i data-lucide="check-circle" style="width:1.25rem;height:1.25rem;flex-shrink:0;"></i>
                    <?php echo e(__('public.contact.success_message')); ?>

                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('public.contact.submit')); ?>" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="contact-form-row">
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:700;color:var(--color-text-primary);margin-bottom:0.375rem;">
                                <?php echo e(__('public.contact.field_name')); ?> <span style="color:#DC2626;">*</span>
                            </label>
                            <input type="text" name="name" value="<?php echo e(old('name')); ?>" required
                                   style="width:100%;padding:0.625rem 0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;font-size:0.875rem;color:var(--color-text-primary);background:var(--color-bg);outline:none;box-sizing:border-box;"
                                   placeholder="<?php echo e(__('public.contact.field_name_placeholder')); ?>">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="color:#DC2626;font-size:0.75rem;margin-top:4px;"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.8125rem;font-weight:700;color:var(--color-text-primary);margin-bottom:0.375rem;">
                                <?php echo e(__('public.contact.field_email')); ?> <span style="color:#DC2626;">*</span>
                            </label>
                            <input type="email" name="email" value="<?php echo e(old('email')); ?>" required
                                   style="width:100%;padding:0.625rem 0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;font-size:0.875rem;color:var(--color-text-primary);background:var(--color-bg);outline:none;box-sizing:border-box;"
                                   placeholder="you@institution.org">
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="color:#DC2626;font-size:0.75rem;margin-top:4px;"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:700;color:var(--color-text-primary);margin-bottom:0.375rem;">
                            <?php echo e(__('public.contact.field_organisation')); ?>

                        </label>
                        <input type="text" name="organisation" value="<?php echo e(old('organisation')); ?>"
                               style="width:100%;padding:0.625rem 0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;font-size:0.875rem;color:var(--color-text-primary);background:var(--color-bg);outline:none;box-sizing:border-box;"
                               placeholder="<?php echo e(__('public.contact.field_organisation_placeholder')); ?>">
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:700;color:var(--color-text-primary);margin-bottom:0.375rem;">
                            <?php echo e(__('public.contact.field_subject')); ?> <span style="color:#DC2626;">*</span>
                        </label>
                        <select name="subject" required style="width:100%;padding:0.625rem 0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;font-size:0.875rem;color:var(--color-text-primary);background:var(--color-bg);outline:none;box-sizing:border-box;">
                            <option value=""><?php echo e(__('public.contact.field_subject_placeholder')); ?></option>
                            <option value="technical" <?php if(old('subject')==='technical'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_technical')); ?></option>
                            <option value="onboarding" <?php if(old('subject')==='onboarding'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_onboarding')); ?></option>
                            <option value="partnership" <?php if(old('subject')==='partnership'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_partnership')); ?></option>
                            <option value="billing" <?php if(old('subject')==='billing'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_billing')); ?></option>
                            <option value="privacy" <?php if(old('subject')==='privacy'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_privacy')); ?></option>
                            <option value="other" <?php if(old('subject')==='other'): echo 'selected'; endif; ?>><?php echo e(__('public.contact.subject_other')); ?></option>
                        </select>
                        <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="color:#DC2626;font-size:0.75rem;margin-top:4px;"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div style="margin-bottom:2rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:700;color:var(--color-text-primary);margin-bottom:0.375rem;">
                            <?php echo e(__('public.contact.field_message')); ?> <span style="color:#DC2626;">*</span>
                        </label>
                        <textarea name="message" rows="6" required
                                  style="width:100%;padding:0.625rem 0.875rem;border:1px solid var(--color-border);border-radius:0.5rem;font-size:0.875rem;color:var(--color-text-primary);background:var(--color-bg);outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                                  placeholder="<?php echo e(__('public.contact.field_message_placeholder')); ?>"><?php echo e(old('message')); ?></textarea>
                        <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="color:#DC2626;font-size:0.75rem;margin-top:4px;"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <button type="submit" style="display:inline-flex;align-items:center;gap:0.5rem;background:#0F4C81;color:#fff;font-size:0.875rem;font-weight:700;border:none;border-radius:0.5rem;padding:0.75rem 1.75rem;cursor:pointer;transition:background .2s;" onmouseover="this.style.background='#0E3F6C'" onmouseout="this.style.background='#0F4C81'">
                        <i data-lucide="send" style="width:1rem;height:1rem;"></i>
                        <?php echo e(__('public.contact.form_submit')); ?>

                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/contact.blade.php ENDPATH**/ ?>