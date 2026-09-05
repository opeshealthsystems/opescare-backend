<?php $__env->startSection('title', __('professional.page_title', [], app()->getLocale()) ?: 'My Profile'); ?>
<?php $__env->startSection('breadcrumb_home', __('professional.page_title', [], app()->getLocale()) ?: 'My Profile'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('professional.profile')); ?>
<?php $__env->startSection('content'); ?>




<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('professional.page_title', [], app()->getLocale()) ?: 'My Profile'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('professional.page_subtitle', [], app()->getLocale()) ?: 'Manage your details and professional qualifications.'); ?></p>
    </div>
    <div class="page-actions" aria-label="<?php echo e(__('professional.verification_status', [], app()->getLocale()) ?: 'Verification status'); ?>">
        <?php if($verifiedCount > 0): ?>
            <span class="badge badge-success">
                <i data-lucide="badge-check"></i>
                <?php echo e(__('professional.status_verified', [], app()->getLocale()) ?: 'Verified professional'); ?>

            </span>
        <?php elseif($pendingCount > 0): ?>
            <span class="badge badge-warning">
                <i data-lucide="clock"></i>
                <?php echo e(__('professional.status_pending', [], app()->getLocale()) ?: 'Verification pending'); ?>

            </span>
        <?php else: ?>
            <span class="badge badge-neutral">
                <i data-lucide="circle-dashed"></i>
                <?php echo e(__('professional.status_none', [], app()->getLocale()) ?: 'No qualifications yet'); ?>

            </span>
        <?php endif; ?>
    </div>
</div>




<?php if($pendingCount > 0): ?>
<div class="alert alert-info mb-6" role="status">
    <i data-lucide="info"></i>
    <div><?php echo e(__('professional.pending_banner', [], app()->getLocale()) ?: 'Your qualifications are awaiting review by an administrator. You will keep full access to your profile in the meantime.'); ?></div>
</div>
<?php endif; ?>




<?php if(session('success')): ?>
<div class="alert alert-success mb-6" role="alert">
    <i data-lucide="check-circle"></i>
    <div><?php echo e(session('success')); ?></div>
</div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-circle"></i>
    <div><?php echo e(session('error')); ?></div>
</div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-triangle"></i>
    <div>
        <ul class="alert-list">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
</div>
<?php endif; ?>




<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="user"></i> <?php echo e(__('professional.personal_section', [], app()->getLocale()) ?: 'Personal details'); ?></h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('professional.profile.update')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="first_name"><?php echo e(__('professional.first_name', [], app()->getLocale()) ?: 'First name'); ?></label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo e(old('first_name', $profile->first_name)); ?>" maxlength="100" required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="last_name"><?php echo e(__('professional.last_name', [], app()->getLocale()) ?: 'Last name'); ?></label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo e(old('last_name', $profile->last_name)); ?>" maxlength="100" required autocomplete="family-name">
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label" for="email"><?php echo e(__('professional.email', [], app()->getLocale()) ?: 'Email'); ?></label>
                    <input type="email" id="email" class="form-control" value="<?php echo e($profile->email); ?>" readonly autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone"><?php echo e(__('professional.phone', [], app()->getLocale()) ?: 'Phone'); ?></label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e(old('phone', $profile->phone)); ?>" maxlength="30" autocomplete="tel">
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label" for="job_title"><?php echo e(__('professional.job_title', [], app()->getLocale()) ?: 'Job title'); ?></label>
                    <input type="text" id="job_title" name="job_title" class="form-control" value="<?php echo e(old('job_title', $profile->job_title)); ?>" maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department"><?php echo e(__('professional.department', [], app()->getLocale()) ?: 'Department'); ?></label>
                    <input type="text" id="department" name="department" class="form-control" value="<?php echo e(old('department', $profile->department)); ?>" maxlength="120">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <?php echo e(__('professional.save_profile', [], app()->getLocale()) ?: 'Save changes'); ?></button>
            </div>
        </form>
    </div>
</div>




<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="award"></i> <?php echo e(__('professional.quals_section', [], app()->getLocale()) ?: 'Qualifications & licences'); ?></h3>
    </div>

    <?php if($credentials->isEmpty()): ?>
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="file-badge"></i></div>
                <p><?php echo e(__('professional.no_quals', [], app()->getLocale()) ?: 'You have not added any qualifications yet.'); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('professional.credential_type', [], app()->getLocale()) ?: 'Type'); ?></th>
                        <th><?php echo e(__('professional.issuing_body', [], app()->getLocale()) ?: 'Issuing body'); ?></th>
                        <th><?php echo e(__('professional.credential_number', [], app()->getLocale()) ?: 'Licence / certificate number'); ?></th>
                        <th><?php echo e(__('professional.issued_label', [], app()->getLocale()) ?: 'Issued'); ?></th>
                        <th><?php echo e(__('professional.expires_label', [], app()->getLocale()) ?: 'Expires'); ?></th>
                        <th><?php echo e(__('professional.verification_status', [], app()->getLocale()) ?: 'Verification status'); ?></th>
                        <th class="row-actions"><?php echo e(__('professional.remove', [], app()->getLocale()) ?: 'Remove'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $credentials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $credential): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $ctKey   = 'professional.ct_' . $credential->credential_type;
                        $ctLabel = __($ctKey, [], app()->getLocale());
                        if ($ctLabel === $ctKey) {
                            $ctLabel = __('professional.ct_other', [], app()->getLocale()) ?: 'Other';
                        }
                        $isExpired = $credential->expiry_date && $credential->expiry_date->isPast();
                    ?>
                    <tr>
                        <td data-label="<?php echo e(__('professional.credential_type', [], app()->getLocale()) ?: 'Type'); ?>"><span class="td-strong"><?php echo e($ctLabel); ?></span></td>
                        <td data-label="<?php echo e(__('professional.issuing_body', [], app()->getLocale()) ?: 'Issuing body'); ?>"><?php echo e($credential->issuing_body); ?></td>
                        <td data-label="<?php echo e(__('professional.credential_number', [], app()->getLocale()) ?: 'Licence / certificate number'); ?>"><?php echo e($credential->credential_number); ?></td>
                        <td data-label="<?php echo e(__('professional.issued_label', [], app()->getLocale()) ?: 'Issued'); ?>" class="td-muted"><?php echo e($credential->issued_date ? $credential->issued_date->format('d M Y') : '—'); ?></td>
                        <td data-label="<?php echo e(__('professional.expires_label', [], app()->getLocale()) ?: 'Expires'); ?>" class="td-muted"><?php echo e($credential->expiry_date ? $credential->expiry_date->format('d M Y') : (__('professional.expiry_none', [], app()->getLocale()) ?: 'No expiry')); ?></td>
                        <td data-label="<?php echo e(__('professional.verification_status', [], app()->getLocale()) ?: 'Verification status'); ?>">
                            <?php if($isExpired): ?>
                                <span class="badge badge-danger"><i data-lucide="calendar-x"></i> <?php echo e(__('professional.badge_expired', [], app()->getLocale()) ?: 'Expired'); ?></span>
                            <?php elseif($credential->verified_at): ?>
                                <span class="badge badge-success"><i data-lucide="badge-check"></i> <?php echo e(__('professional.badge_verified', [], app()->getLocale()) ?: 'Verified'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i data-lucide="clock"></i> <?php echo e(__('professional.badge_pending', [], app()->getLocale()) ?: 'Pending review'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="row-actions" data-label="<?php echo e(__('professional.remove', [], app()->getLocale()) ?: 'Remove'); ?>">
                            <?php if($credential->verified_at): ?>
                                <span class="td-muted" title="<?php echo e(__('professional.locked_note', [], app()->getLocale()) ?: 'Verified — managed by an administrator'); ?>">
                                    <i data-lucide="lock"></i> <?php echo e(__('professional.locked_note', [], app()->getLocale()) ?: 'Verified — managed by an administrator'); ?>

                                </span>
                            <?php else: ?>
                                <form method="POST" action="<?php echo e(route('professional.qualifications.destroy', $credential->id)); ?>" class="inline-form" onsubmit="return confirm('<?php echo e(__('professional.remove_confirm', [], app()->getLocale()) ?: 'Remove this qualification?'); ?>')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" aria-label="<?php echo e(__('professional.remove', [], app()->getLocale()) ?: 'Remove'); ?>">
                                        <i data-lucide="trash-2"></i> <?php echo e(__('professional.remove', [], app()->getLocale()) ?: 'Remove'); ?>

                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>




<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="plus-circle"></i> <?php echo e(__('professional.add_qual', [], app()->getLocale()) ?: 'Add a qualification'); ?></h3>
    </div>
    <div class="panel-body">
        <p class="page-subtitle mb-6"><?php echo e(__('professional.quals_intro', [], app()->getLocale()) ?: 'Add each licence or certification you hold. An administrator verifies them before the verified badge appears.'); ?></p>

        <form method="POST" action="<?php echo e(route('professional.qualifications.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="credential_type"><?php echo e(__('professional.credential_type', [], app()->getLocale()) ?: 'Type'); ?></label>
                    <select id="credential_type" name="credential_type" class="form-control" required>
                        <?php $__currentLoopData = $credentialTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $optKey   = 'professional.ct_' . $type;
                                $optLabel = __($optKey, [], app()->getLocale());
                                if ($optLabel === $optKey) {
                                    $optLabel = __('professional.ct_other', [], app()->getLocale()) ?: 'Other';
                                }
                            ?>
                            <option value="<?php echo e($type); ?>" <?php if(old('credential_type') === $type): echo 'selected'; endif; ?>><?php echo e($optLabel); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="issuing_body"><?php echo e(__('professional.issuing_body', [], app()->getLocale()) ?: 'Issuing body'); ?></label>
                    <input type="text" id="issuing_body" name="issuing_body" class="form-control" value="<?php echo e(old('issuing_body')); ?>" maxlength="255" required>
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label form-label-required" for="credential_number"><?php echo e(__('professional.credential_number', [], app()->getLocale()) ?: 'Licence / certificate number'); ?></label>
                    <input type="text" id="credential_number" name="credential_number" class="form-control" value="<?php echo e(old('credential_number')); ?>" maxlength="100" required>
                </div>
            </div>

            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label form-label-required" for="issued_date"><?php echo e(__('professional.issued_date', [], app()->getLocale()) ?: 'Issued on'); ?></label>
                    <input type="date" id="issued_date" name="issued_date" class="form-control" value="<?php echo e(old('issued_date')); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="expiry_date"><?php echo e(__('professional.expiry_date', [], app()->getLocale()) ?: 'Expires on'); ?></label>
                    <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="<?php echo e(old('expiry_date')); ?>">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> <?php echo e(__('professional.submit_qual', [], app()->getLocale()) ?: 'Add qualification'); ?></button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/professional/profile.blade.php ENDPATH**/ ?>