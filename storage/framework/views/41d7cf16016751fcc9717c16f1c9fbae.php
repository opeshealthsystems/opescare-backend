<?php $__env->startSection('title', __('public.developer_portal.page_create_prod_req', [], app()->getLocale()) ?: 'Request Production Access'); ?>
<?php $__env->startSection('sidebar_nav'); ?> <?php echo $__env->make('portals.developer._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>
<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

    <div class="breadcrumb">
        <a href="<?php echo e(route('portals.developer.production_requests')); ?>"><?php echo e(__('public.developer_portal.nav_prod_access', [], $l) ?: 'Production requests'); ?></a>
        <i data-lucide="chevron-right"></i>
        <span><?php echo e(__('public.developer_portal.page_create_prod_req', [], $l) ?: 'Request production access'); ?></span>
    </div>

    <div class="page-head">
        <h2><?php echo e(__('public.developer_portal.page_create_prod_req', [], $l) ?: 'Request production access'); ?></h2>
    </div>

    <div class="panel form-panel">
        <div class="panel-body">

            <div class="alert alert-warning mb-6">
                <i data-lucide="alert-triangle"></i>
                <div><?php echo e(__('public.developer_portal.warn_prod_real_data', [], $l) ?: 'Production integrations access real patient data. Approval requires a security review. Do not attempt to access production without prior approval — violations result in immediate revocation.'); ?></div>
            </div>

            <form method="POST" action="<?php echo e(route('portals.developer.production_requests.store')); ?>">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.developer_portal.fld_app', [], $l) ?: 'App (sandbox integration client)'); ?></label>
                    <select name="integration_client_id" required class="form-control">
                        <option value=""><?php echo e(__('public.developer_portal.ph_select_app', [], $l) ?: 'Select app…'); ?></option>
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($client->client_id); ?>" <?php echo e(old('integration_client_id') === $client->client_id ? 'selected' : ''); ?>>
                            <?php echo e($client->name ?? $client->client_id); ?>

                        </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['integration_client_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.developer_portal.col_use_case', [], $l) ?: 'Use case'); ?></label>
                    <input type="text" name="use_case" value="<?php echo e(old('use_case')); ?>" required class="form-control"
                           placeholder="<?php echo e(__('dev_extra.ph_use_case', [], $l) ?: 'e.g. Hospital Information System integration for patient record sync'); ?>">
                    <?php $__errorArgs = ['use_case'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.developer_portal.fld_tech_desc', [], $l) ?: 'Technical description'); ?> <span class="td-muted"><?php echo e(__('public.developer_portal.fld_tech_desc_min', [], $l) ?: '(min 50 characters)'); ?></span></label>
                    <textarea name="technical_description" rows="4" required minlength="50" class="form-control"
                              placeholder="<?php echo e(__('public.developer_portal.ph_tech_desc', [], $l) ?: 'Describe the integration architecture, data flows, security measures, and how you handle patient data…'); ?>"><?php echo e(old('technical_description')); ?></textarea>
                    <?php $__errorArgs = ['technical_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.developer_portal.fld_requested_scopes', [], $l) ?: 'Requested scopes'); ?> <span class="td-muted"><?php echo e(__('public.developer_portal.fld_all_apply', [], $l) ?: '(select all that apply)'); ?></span></label>
                    <div class="form-row">
                        <?php $__currentLoopData = $scopeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scope): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="form-check">
                            <input type="checkbox" name="requested_scopes[]" value="<?php echo e($scope); ?>"
                                   <?php echo e(in_array($scope, old('requested_scopes', [])) ? 'checked' : ''); ?>>
                            <span class="mono"><?php echo e($scope); ?></span>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php $__errorArgs = ['requested_scopes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo e(__('public.developer_portal.fld_est_daily_req', [], $l) ?: 'Estimated daily requests'); ?></label>
                        <select name="estimated_daily_requests" class="form-control">
                            <option value=""><?php echo e(__('public.developer_portal.ph_select_range', [], $l) ?: 'Select range…'); ?></option>
                            <option value="< 100"><?php echo e(__('public.developer_portal.opt_less_100', [], $l) ?: 'Less than 100'); ?></option>
                            <option value="100–1 000"><?php echo e(__('public.developer_portal.opt_100_1000', [], $l) ?: '100–1 000'); ?></option>
                            <option value="1 000–10 000"><?php echo e(__('public.developer_portal.opt_1000_10000', [], $l) ?: '1 000–10 000'); ?></option>
                            <option value="10 000–100 000"><?php echo e(__('public.developer_portal.opt_10000_100000', [], $l) ?: '10 000–100 000'); ?></option>
                            <option value="> 100 000"><?php echo e(__('public.developer_portal.opt_more_100000', [], $l) ?: 'More than 100 000'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo e(__('public.developer_portal.fld_data_residency', [], $l) ?: 'Data residency region'); ?></label>
                        <input type="text" name="data_residency_region" value="<?php echo e(old('data_residency_region')); ?>" class="form-control"
                               placeholder="<?php echo e(__('public.developer_portal.ph_data_residency', [], $l) ?: 'e.g. West Africa, EU, US'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="handles_patient_data" value="1" <?php echo e(old('handles_patient_data') ? 'checked' : ''); ?>>
                        <?php echo e(__('public.developer_portal.chk_handles_patient_data', [], $l) ?: 'This integration handles identifiable patient data'); ?>

                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="security_review_done" value="1" <?php echo e(old('security_review_done') ? 'checked' : ''); ?>>
                        <?php echo e(__('public.developer_portal.chk_security_review', [], $l) ?: 'We have completed an internal security review of this integration'); ?>

                    </label>
                </div>

                <div class="alert alert-danger mb-6">
                    <i data-lucide="shield-alert"></i>
                    <div><?php echo e(__('public.developer_portal.warn_compliance', [], $l) ?: 'By submitting this request you confirm this integration complies with all applicable data protection laws and the'); ?>

                    <a href="<?php echo e(route('public.legal', 'api-developer-terms')); ?>" target="_blank"><?php echo e(__('public.developer_portal.lnk_api_dev_terms', [], $l) ?: 'OpesCare API Developer Terms'); ?></a>.</div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="terms_accepted" value="1" required <?php echo e(old('terms_accepted') ? 'checked' : ''); ?>>
                        <?php echo e(__('public.developer_portal.chk_terms', [], $l) ?: 'I confirm compliance with OpesCare API Developer Terms and applicable data protection law'); ?>

                    </label>
                    <?php $__errorArgs = ['terms_accepted'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-hint"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-actions-end">
                    <a href="<?php echo e(route('portals.developer.production_requests')); ?>" class="btn btn-ghost"><?php echo e(__('public.developer_portal.btn_cancel', [], $l) ?: 'Cancel'); ?></a>
                    <button type="submit" class="btn btn-primary"><i data-lucide="rocket"></i> <?php echo e(__('public.developer_portal.btn_submit_request', [], $l) ?: 'Submit request'); ?></button>
                </div>
            </form>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\developer\create_production_request.blade.php ENDPATH**/ ?>