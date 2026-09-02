<?php $__env->startSection('title', __('referral.title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('referral.title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('referral.title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('referral.subtitle')); ?></p>
    </div>
</div>


<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="gift"></i> <?php echo e(__('referral.your_link')); ?></h3>
        <span class="badge badge-info"><?php echo e(__('referral.your_code')); ?>: <?php echo e($code); ?></span>
    </div>
    <div class="panel-body">
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
            <input id="refer-link" type="text" class="form-control" readonly
                   value="<?php echo e($link); ?>" style="flex:1;min-width:240px;" onclick="this.select();">
            <button type="button" class="btn btn-primary" id="refer-copy-btn"
                    data-copied="<?php echo e(__('referral.copied')); ?>"
                    onclick="opesCopyReferLink()">
                <i data-lucide="copy"></i> <span><?php echo e(__('referral.copy')); ?></span>
            </button>
        </div>
    </div>
</div>


<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="sparkles"></i> <?php echo e(__('referral.how_title')); ?></h3>
    </div>
    <div class="panel-body">
        <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.75rem;">
            <li style="display:flex;align-items:flex-start;gap:.6rem;">
                <i data-lucide="share-2" style="width:1.1rem;height:1.1rem;color:#0F4C81;flex:none;margin-top:.15rem;"></i>
                <span><?php echo e(__('referral.how_step1')); ?></span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:.6rem;">
                <i data-lucide="user-plus" style="width:1.1rem;height:1.1rem;color:#0F4C81;flex:none;margin-top:.15rem;"></i>
                <span><?php echo e(__('referral.how_step2')); ?></span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:.6rem;">
                <i data-lucide="party-popper" style="width:1.1rem;height:1.1rem;color:#0F4C81;flex:none;margin-top:.15rem;"></i>
                <span><?php echo e(__('referral.how_step3', ['referrer' => $referrerDays, 'referee' => $refereeDays])); ?></span>
            </li>
        </ul>
    </div>
</div>


<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> <?php echo e(__('referral.stats_title')); ?></h3>
    </div>
    <div class="panel-body">
        <div style="display:flex;flex-wrap:wrap;gap:2rem;">
            <div>
                <div style="font-size:1.75rem;font-weight:700;color:#0F4C81;"><?php echo e($joinedCount); ?></div>
                <div class="page-subtitle"><?php echo e(__('referral.stat_invites')); ?></div>
            </div>
            <div>
                <div style="font-size:1.75rem;font-weight:700;color:#0F4C81;"><?php echo e($rewardedCount); ?></div>
                <div class="page-subtitle"><?php echo e(__('referral.stat_rewarded')); ?></div>
            </div>
            <div>
                <div style="font-size:1.75rem;font-weight:700;color:#0F4C81;"><?php echo e($rewardedDays); ?></div>
                <div class="page-subtitle"><?php echo e(__('referral.stat_days_earned')); ?></div>
            </div>
        </div>
    </div>
</div>


<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="history"></i> <?php echo e(__('referral.invites_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($invites->isEmpty()): ?>
            <p class="page-subtitle" style="margin:0;"><?php echo e(__('referral.no_invites')); ?></p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo e(__('referral.col_when')); ?></th>
                        <th><?php echo e(__('referral.col_email')); ?></th>
                        <th><?php echo e(__('referral.col_status')); ?></th>
                        <th><?php echo e(__('referral.col_reward')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $invites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invite): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($invite->created_at?->isoFormat('LL')); ?></td>
                            <td><?php echo e($invite->referee_email ?? '—'); ?></td>
                            <td><span class="badge"><?php echo e(__('referral.status_' . $invite->status)); ?></span></td>
                            <td>
                                <?php if($invite->status === 'rewarded'): ?>
                                    <?php echo e(__('referral.days', ['count' => $invite->referrer_reward_days])); ?>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function opesCopyReferLink() {
    var input = document.getElementById('refer-link');
    var btn = document.getElementById('refer-copy-btn');
    if (!input || !btn) return;
    input.select();
    input.setSelectionRange(0, 99999);
    var done = function () {
        var span = btn.querySelector('span');
        if (span) {
            var original = span.textContent;
            span.textContent = btn.getAttribute('data-copied');
            setTimeout(function () { span.textContent = original; }, 1800);
        }
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
    } else {
        document.execCommand('copy');
        done();
    }
}
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\refer.blade.php ENDPATH**/ ?>