<?php $__env->startSection('title', __('public.sla.page_title')); ?>
<?php $__env->startSection('meta_description', __('public.sla.meta_description')); ?>

<?php $__env->startSection('content'); ?>

    
    <header class="content-header" style="padding:3rem 0 2.5rem;">
        <div class="container">
            <h1><?php echo e(__('public.sla.hero_title')); ?></h1>
            <p class="text-muted"><?php echo e(__('public.sla.hero_subtitle')); ?></p>
            <p style="font-size:.8125rem;color:#94a3b8;margin-top:.5rem;"><?php echo e(__('public.sla.effective')); ?> <?php echo e(now()->startOfMonth()->format('d M Y')); ?> · <?php echo e(__('public.sla.scope')); ?></p>
        </div>
    </header>

    <section class="content-body">
        <div class="container" style="max-width:820px;">

            
            <div style="background:linear-gradient(135deg,#0F4C81,#1565a8);border-radius:1.25rem;padding:2rem;margin-bottom:2.5rem;color:#fff;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                <div style="font-size:3rem;font-weight:800;line-height:1;"><?php echo e(__('public.sla.uptime_value')); ?></div>
                <div style="flex:1;min-width:220px;">
                    <div style="font-weight:700;font-size:1.125rem;margin-bottom:.25rem;"><?php echo e(__('public.sla.uptime_title')); ?></div>
                    <div style="font-size:.875rem;opacity:.9;"><?php echo e(__('public.sla.uptime_desc')); ?></div>
                </div>
            </div>

            
            <h2 style="font-size:1.25rem;margin-bottom:1rem;"><?php echo e(__('public.sla.severity_title')); ?></h2>
            <div style="border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;margin-bottom:2.5rem;">
                <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">
                    <div style="padding:.75rem 1rem;"><?php echo e(__('public.sla.col_level')); ?></div>
                    <div style="padding:.75rem 1rem;"><?php echo e(__('public.sla.col_response')); ?></div>
                    <div style="padding:.75rem 1rem;"><?php echo e(__('public.sla.col_resolution')); ?></div>
                </div>
                <?php
                $rows = [
                    ['#ef4444', __('public.sla.sev1_name'), __('public.sla.sev1_desc'), __('public.sla.sev1_response'), __('public.sla.sev1_resolution')],
                    ['#f59e0b', __('public.sla.sev2_name'), __('public.sla.sev2_desc'), __('public.sla.sev2_response'), __('public.sla.sev2_resolution')],
                    ['#3b82f6', __('public.sla.sev3_name'), __('public.sla.sev3_desc'), __('public.sla.sev3_response'), __('public.sla.sev3_resolution')],
                    ['#64748b', __('public.sla.sev4_name'), __('public.sla.sev4_desc'), __('public.sla.sev4_response'), __('public.sla.sev4_resolution')],
                ];
                ?>
                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr;<?php echo e($i < count($rows)-1 ? 'border-bottom:1px solid #e2e8f0;' : ''); ?>">
                    <div style="padding:.9rem 1rem;">
                        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:.875rem;color:#0F2744;"><span style="width:.5rem;height:.5rem;border-radius:50%;background:<?php echo e($r[0]); ?>;"></span><?php echo e($r[1]); ?></div>
                        <div style="font-size:.75rem;color:#64748b;margin-top:.2rem;"><?php echo e($r[2]); ?></div>
                    </div>
                    <div style="padding:.9rem 1rem;font-size:.875rem;color:#0F2744;align-self:center;"><?php echo e($r[3]); ?></div>
                    <div style="padding:.9rem 1rem;font-size:.875rem;color:#0F2744;align-self:center;"><?php echo e($r[4]); ?></div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            
            <h2 style="font-size:1.25rem;margin-bottom:.75rem;"><?php echo e(__('public.sla.maintenance_title')); ?></h2>
            <p style="color:#475569;font-size:.9375rem;margin-bottom:2.5rem;"><?php echo e(__('public.sla.maintenance_desc')); ?>

                <a href="<?php echo e(route('public.status')); ?>" style="color:#0F4C81;font-weight:600;"><?php echo e(__('public.sla.maintenance_link')); ?></a>.</p>

            
            <h2 style="font-size:1.25rem;margin-bottom:.75rem;"><?php echo e(__('public.sla.credits_title')); ?></h2>
            <p style="color:#475569;font-size:.9375rem;margin-bottom:1rem;"><?php echo e(__('public.sla.credits_desc')); ?></p>
            <div style="border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;margin-bottom:2.5rem;">
                <?php
                $credits = [
                    [__('public.sla.credit_1_range'), __('public.sla.credit_1_value')],
                    [__('public.sla.credit_2_range'), __('public.sla.credit_2_value')],
                    [__('public.sla.credit_3_range'), __('public.sla.credit_3_value')],
                ];
                ?>
                <?php $__currentLoopData = $credits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.85rem 1.25rem;<?php echo e($i < count($credits)-1 ? 'border-bottom:1px solid #e2e8f0;' : ''); ?>">
                    <span style="font-size:.875rem;color:#475569;"><?php echo e($c[0]); ?></span>
                    <span style="font-weight:700;color:#0F4C81;"><?php echo e($c[1]); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            
            <h2 style="font-size:1.25rem;margin-bottom:.75rem;"><?php echo e(__('public.sla.exclusions_title')); ?></h2>
            <ul style="color:#475569;font-size:.9375rem;line-height:1.8;margin-bottom:2.5rem;padding-left:1.25rem;">
                <li><?php echo e(__('public.sla.excl_sandbox')); ?></li>
                <li><?php echo e(__('public.sla.excl_maintenance')); ?></li>
                <li><?php echo e(__('public.sla.excl_client')); ?></li>
                <li><?php echo e(__('public.sla.excl_force_majeure')); ?></li>
            </ul>

            
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:1.25rem;padding:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <i data-lucide="life-buoy" style="width:1.5rem;height:1.5rem;color:#2563EB;flex-shrink:0;"></i>
                <div style="flex:1;min-width:200px;">
                    <div style="font-weight:700;color:#1E40AF;margin-bottom:.2rem;"><?php echo e(__('public.sla.support_title')); ?></div>
                    <div style="font-size:.8125rem;color:#3B82F6;"><?php echo e(__('public.sla.support_desc')); ?></div>
                </div>
                <a href="<?php echo e(route('public.status')); ?>" class="btn btn-primary" style="font-size:.875rem;padding:.625rem 1.25rem;flex-shrink:0;"><?php echo e(__('public.sla.btn_status')); ?></a>
            </div>

        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/public/sla.blade.php ENDPATH**/ ?>