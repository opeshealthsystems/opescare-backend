<?php $__env->startSection('title', __('referral.title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('referral.title')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('referral.title')); ?></h1>
            <p class="pf-sub"><?php echo e(__('referral.subtitle')); ?></p>
        </div>
    </div>

    
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'gift', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('referral.your_link')); ?>

            <span class="f-badge"><?php echo e(__('referral.your_code')); ?>: <?php echo e($code); ?></span>
        </h2>
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <input id="refer-link" type="text" class="f-input" readonly
                   value="<?php echo e($link); ?>" style="flex:1;min-width:240px;" onclick="this.select();">
            <button type="button" class="f-btn f-btn--primary" id="refer-copy-btn" style="min-height:48px;"
                    data-copied="<?php echo e(__('referral.copied')); ?>"
                    onclick="pfCopyReferLink()">
                <span><?php echo e(__('referral.copy')); ?></span>
            </button>
        </div>
    </div>

    
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('referral.how_title')); ?>

        </h2>
        <ul style="list-style:none;display:grid;gap:12px;">
            <li style="display:flex;align-items:flex-start;gap:10px;">
                <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;"><?php echo $__env->make('partials.finder.icon', ['name' => 'external-link', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <span><?php echo e(__('referral.how_step1')); ?></span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:10px;">
                <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;"><?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <span><?php echo e(__('referral.how_step2')); ?></span>
            </li>
            <li style="display:flex;align-items:flex-start;gap:10px;">
                <span class="pf-qa__icon" style="width:30px;height:30px;border-radius:50%;"><?php echo $__env->make('partials.finder.icon', ['name' => 'gift', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <span><?php echo e(__('referral.how_step3', ['referrer' => $referrerDays, 'referee' => $refereeDays])); ?></span>
            </li>
        </ul>
    </div>

    
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('referral.stats_title')); ?>

        </h2>
        <div class="pf-tiles" style="grid-template-columns:repeat(3,1fr);">
            <div>
                <div class="pf-tile__value"><?php echo e($joinedCount); ?></div>
                <div class="pf-tile__label"><?php echo e(__('referral.stat_invites')); ?></div>
            </div>
            <div>
                <div class="pf-tile__value"><?php echo e($rewardedCount); ?></div>
                <div class="pf-tile__label"><?php echo e(__('referral.stat_rewarded')); ?></div>
            </div>
            <div>
                <div class="pf-tile__value"><?php echo e($rewardedDays); ?></div>
                <div class="pf-tile__label"><?php echo e(__('referral.stat_days_earned')); ?></div>
            </div>
        </div>
    </div>

    
    <div class="f-card">
        <h2 class="pf-card-head">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('referral.invites_title')); ?>

        </h2>
        <?php if($invites->isEmpty()): ?>
            <p class="f-muted f-small" style="margin:0;"><?php echo e(__('referral.no_invites')); ?></p>
        <?php else: ?>
            <div class="pf-table-wrap">
                <table class="pf-table">
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
                                <td><span class="f-badge <?php echo e($invite->status === 'rewarded' ? 'f-badge--good' : 'pf-badge--muted'); ?>"><?php echo e(__('referral.status_' . $invite->status)); ?></span></td>
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
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function pfCopyReferLink() {
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