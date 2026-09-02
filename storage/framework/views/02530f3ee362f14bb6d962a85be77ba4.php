<?php $__env->startSection('title', __('public.developer_portal.page_create_app', [], app()->getLocale()) ?: 'New App'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

    <div class="breadcrumb">
        <a href="<?php echo e(route('portals.developer.apps')); ?>"><?php echo e(__('public.developer_portal.lnk_my_apps', [], $l) ?: 'My apps'); ?></a>
        <i data-lucide="chevron-right"></i>
        <span><?php echo e(__('public.developer_portal.page_create_app', [], $l) ?: 'New app'); ?></span>
    </div>

    <div class="page-head">
        <h2><?php echo e(__('public.developer_portal.page_create_app', [], $l) ?: 'New app'); ?></h2>
    </div>

    <div class="panel form-panel">
        <div class="panel-body">

            <div class="alert alert-info mb-6">
                <i data-lucide="info"></i>
                <div><?php echo e(__('public.developer_portal.info_sandbox_only', [], $l) ?: 'New apps receive sandbox credentials only. To access production data, submit a'); ?>

                <strong><?php echo e(__('public.developer_portal.badge_sandbox', [], $l) ?: 'sandbox credentials'); ?></strong> <?php echo e(__('public.developer_portal.info_sandbox_only', [], $l) ? '' : 'only.'); ?>

                <a href="<?php echo e(route('portals.developer.production_requests.create')); ?>"><?php echo e(__('public.developer_portal.info_sandbox_link_text', [], $l) ?: 'production access request'); ?></a>
                <?php echo e(__('public.developer_portal.info_sandbox_only_suffix', [], $l) ?: 'after your app is ready.'); ?></div>
            </div>

            <form method="POST" action="<?php echo e(route('portals.developer.apps.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.developer_portal.fld_app_name', [], $l) ?: 'App name'); ?></label>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" required
                           placeholder="<?php echo e(__('public.developer_portal.ph_app_name', [], $l) ?: 'e.g. MyHospital Connector'); ?>" class="form-control">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.developer_portal.fld_description', [], $l) ?: 'Description'); ?></label>
                    <textarea name="description" rows="3" class="form-control"
                              placeholder="<?php echo e(__('public.developer_portal.ph_description', [], $l) ?: 'Brief description of what your integration does…'); ?>"><?php echo e(old('description')); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.developer_portal.fld_website_url', [], $l) ?: 'Website / redirect URL (optional)'); ?></label>
                    <input type="url" name="website_url" value="<?php echo e(old('website_url')); ?>"
                           placeholder="<?php echo e(__('public.developer_portal.ph_website_url', [], $l) ?: 'https://yourapp.example.com'); ?>" class="form-control">
                </div>

                <div class="alert alert-warning mb-6">
                    <i data-lucide="alert-triangle"></i>
                    <div><?php echo e(__('public.developer_portal.warn_secret_once', [], $l) ?: 'Your sandbox client secret will be shown once after creation. Store it securely.'); ?></div>
                </div>

                <div class="form-actions-end">
                    <a href="<?php echo e(route('portals.developer.apps')); ?>" class="btn btn-ghost"><?php echo e(__('public.developer_portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
                    <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> <?php echo e(__('public.developer_portal.btn_create_app', [], $l) ?: 'Create app'); ?></button>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\create_app.blade.php ENDPATH**/ ?>