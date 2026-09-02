<?php $__env->startSection('title', __('public.developer_portal.page_onboard', [], app()->getLocale()) ?: 'Developer Portal — Set Up Your Account'); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="form-panel">
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="user-plus"></i> <?php echo e(__('public.developer_portal.panel_complete_account', [], $l) ?: 'Complete your developer account'); ?></h3>
        </div>
        <div class="panel-body">
            <p class="td-muted mb-6">
                <?php echo e(__('public.developer_portal.onboard_intro', ['email' => $email], $l) ?: 'A developer account for ' . $email . ' was not found. Complete setup to access the OpesCare Developer Portal and obtain sandbox API credentials.'); ?>

            </p>
            <form method="POST" action="<?php echo e(route('portals.developer.onboard.store')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="email" value="<?php echo e($email); ?>">

                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.developer_portal.fld_display_name', [], $l) ?: 'Display name'); ?></label>
                    <input type="text" name="display_name" value="<?php echo e(old('display_name')); ?>" required class="form-control">
                    <?php $__errorArgs = ['display_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.developer_portal.fld_company', [], $l) ?: 'Company / organisation (optional)'); ?></label>
                    <input type="text" name="company_name" value="<?php echo e(old('company_name')); ?>" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.developer_portal.fld_website', [], $l) ?: 'Website (optional)'); ?></label>
                    <input type="url" name="website_url" value="<?php echo e(old('website_url')); ?>" placeholder="https://example.com" class="form-control">
                </div>

                <div class="alert alert-info mb-6">
                    <i data-lucide="info"></i>
                    <div><strong><?php echo e(__('public.developer_portal.info_api_terms_title', [], $l) ?: 'API terms of use:'); ?></strong> <?php echo e(__('public.developer_portal.info_api_terms_body', [], $l) ?: 'By creating a developer account you agree to the'); ?>

                    <a href="<?php echo e(route('public.legal', 'api-developer-terms')); ?>" target="_blank"><?php echo e(__('public.developer_portal.lnk_api_terms', [], $l) ?: 'API / Developer Terms'); ?></a>.
                    <?php echo e(__('public.developer_portal.info_sandbox_note', [], $l) ?: 'All API access is sandbox-only until a production access request is approved.'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="api_terms_accepted" value="1" required>
                        <?php echo e(__('public.developer_portal.chk_api_terms', [], $l) ?: 'I have read and agree to the API / Developer Terms of Use'); ?>

                    </label>
                    <?php $__errorArgs = ['api_terms_accepted'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block"><i data-lucide="user-plus"></i> <?php echo e(__('public.developer_portal.btn_create_account', [], $l) ?: 'Create developer account'); ?></button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/developer/onboard.blade.php ENDPATH**/ ?>