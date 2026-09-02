<?php $__env->startSection('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Drug Inventory'); ?>
<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge">
    <i data-lucide="pill"></i>
    <?php echo e(__('public.pharmacy_portal.role_badge', [], app()->getLocale()) ?: 'Pharmacy'); ?>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist'); ?>
<?php $__env->startSection('sidebar_nav'); ?>
<?php echo $__env->make('portals.pharmacy._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Pharmacy Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.pharmacy.dashboard')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_stock', [], app()->getLocale()) ?: 'Public Availability'); ?>
<?php
    $l = app()->getLocale();
    $badgeFor = fn ($v) => match ($v) {
        'in_stock'     => 'badge-success',
        'low_stock'    => 'badge-warning',
        'out_of_stock' => 'badge-danger',
        default        => 'badge-neutral',
    };
?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('public.pharmacy_portal.page_heading_stock', [], $l)); ?></h2>
    <p class="page-subtitle"><?php echo e(__('public.pharmacy_portal.page_subtitle_stock', [], $l)); ?></p>
    <div class="page-head__spacer"></div>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'inventory_ops')): ?>
    <a href="<?php echo e(route('portals.staff.inventory.pharmacy')); ?>" class="btn btn-secondary btn-sm">
        <i data-lucide="settings"></i> <?php echo e(__('public.pharmacy_portal.btn_manage_stock', [], $l) ?: 'Manage stock'); ?>

    </a>
    <?php endif; ?>
</div>

<?php if($errors->any()): ?>
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div>
        <ul class="alert-list">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
</div>
<?php endif; ?>


<?php $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($issue === 'unlinked'): ?>
    <div class="banner banner--danger">
        <i data-lucide="unlink"></i>
        <div>
            <strong><?php echo e(__('public.pharmacy_portal.stock_warn_unlinked_title', [], $l)); ?></strong><br>
            <?php echo e(__('public.pharmacy_portal.stock_warn_unlinked_body', [], $l)); ?>

        </div>
    </div>
    <?php elseif($issue === 'no_coordinates'): ?>
    <div class="banner banner--warning">
        <i data-lucide="map-pin-off"></i>
        <div>
            <strong><?php echo e(__('public.pharmacy_portal.stock_warn_no_coords_title', [], $l)); ?></strong><br>
            <?php echo e(__('public.pharmacy_portal.stock_warn_no_coords_body', [], $l)); ?>

        </div>
    </div>
    <?php elseif($issue === 'not_listed'): ?>
    <div class="banner banner--warning">
        <i data-lucide="eye-off"></i>
        <div>
            <strong><?php echo e(__('public.pharmacy_portal.stock_warn_not_listed_title', [], $l)); ?></strong><br>
            <?php echo e(__('public.pharmacy_portal.stock_warn_not_listed_body', [], $l)); ?>

        </div>
    </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if($listing): ?>
<div class="banner banner--info">
    <i data-lucide="search"></i>
    <div>
        <strong><?php echo e($listing->facility_name); ?></strong> —
        <?php echo e(__('public.pharmacy_portal.stock_cov_summary', [
            'reported' => $coverage['reported'],
            'total'    => $coverage['total'],
        ], $l)); ?>

    </div>
</div>


<details class="panel" <?php if(old('medicine_id')): ?> open <?php endif; ?>>
    <summary class="panel-header" style="cursor:pointer;">
        <i data-lucide="plus-circle"></i>
        <?php echo e(__('public.pharmacy_portal.stock_add_title', [], $l)); ?>

    </summary>
    <div class="panel-body">
        <?php if($catalog->isEmpty()): ?>
            <p class="td-muted"><?php echo e(__('public.pharmacy_portal.stock_catalog_empty', [], $l)); ?></p>
        <?php else: ?>
        <form method="POST" action="<?php echo e(route('portals.pharmacy.stock.report')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add-medicine"><?php echo e(__('public.pharmacy_portal.stock_add_medicine', [], $l)); ?></label>
                    <select name="medicine_id" id="add-medicine" class="form-control" required>
                        <option value=""><?php echo e(__('public.pharmacy_portal.stock_add_choose', [], $l)); ?></option>
                        <?php $__currentLoopData = $catalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($m->id); ?>" <?php echo e(old('medicine_id') === $m->id ? 'selected' : ''); ?>>
                            <?php echo e($m->name); ?><?php if($m->form): ?> · <?php echo e($m->form); ?><?php endif; ?>
                        </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-status"><?php echo e(__('public.pharmacy_portal.stock_col_status', [], $l)); ?></label>
                    <select name="stock_status" id="add-status" class="form-control" required>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s->value); ?>" <?php echo e($s->value === 'in_stock' ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($s->value, 'stock_status')); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add-packs"><?php echo e(__('public.pharmacy_portal.stock_col_packs', [], $l)); ?></label>
                    <input type="number" name="packs_available" id="add-packs" class="form-control" min="0" max="1000000" value="<?php echo e(old('packs_available')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-pack-size"><?php echo e(__('public.pharmacy_portal.stock_lbl_pack_size', [], $l)); ?></label>
                    <input type="text" name="pack_size" id="add-pack-size" class="form-control" maxlength="60" value="<?php echo e(old('pack_size')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-price"><?php echo e(__('public.pharmacy_portal.stock_col_price', [], $l)); ?></label>
                    <input type="number" name="unit_price" id="add-price" class="form-control" min="0" step="0.01" value="<?php echo e(old('unit_price')); ?>">
                </div>
            </div>
            <label class="form-check">
                <input type="checkbox" name="reservation_enabled" value="1" checked>
                <?php echo e(__('public.pharmacy_portal.stock_col_reserve', [], $l)); ?>

            </label>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="megaphone"></i> <?php echo e(__('public.pharmacy_portal.stock_btn_report', [], $l)); ?>

                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</details>

<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" placeholder="<?php echo e(__('public.pharmacy_portal.ph_search_drug', [], $l) ?: 'Drug or generic name…'); ?>" value="<?php echo e(request('search')); ?>" aria-label="<?php echo e(__('public.aria_search_drugs')); ?>">
    </label>
    <select name="stock_status" class="filter-select" aria-label="<?php echo e(__('public.aria_stock_status')); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.pharmacy_portal.filter_all_stock', [], $l) ?: 'All stock'); ?></option>
        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($s->value); ?>" <?php echo e(request('stock_status') === $s->value ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($s->value, 'stock_status')); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> <?php echo e(__('public.pharmacy_portal.btn_filter', [], $l) ?: 'Filter'); ?></button>
    <?php if(request()->hasAny(['stock_status','search'])): ?>
        <a href="<?php echo e(route('portals.pharmacy.stock')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.pharmacy_portal.btn_clear', [], $l) ?: 'Clear'); ?></a>
    <?php endif; ?>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_medicine', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_status', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_packs', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_price', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_reserve', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_source', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.stock_col_reported', [], $l)); ?></th>
                    <th><?php echo e(__('public.pharmacy_portal.col_action', [], $l) ?: 'Action'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $med = $stock->medicine; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_medicine', [], $l)); ?>">
                        <span class="td-strong"><?php echo e($med?->name ?? '—'); ?></span>
                        <div class="td-muted"><?php echo e($med?->generic_name); ?> <?php echo e($med?->strength); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_status', [], $l)); ?>">
                        <span class="badge <?php echo e($badgeFor($stock->stock_status->value)); ?>"><?php echo e(\App\Support\Enums::label($stock->stock_status->value, 'stock_status')); ?></span>
                        <select name="stock_status" form="stock-<?php echo e($stock->id); ?>" class="form-control mt-6" required aria-label="<?php echo e(__('public.pharmacy_portal.stock_col_status', [], $l)); ?>">
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->value); ?>" <?php echo e($stock->stock_status === $s ? 'selected' : ''); ?>><?php echo e(\App\Support\Enums::label($s->value, 'stock_status')); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_packs', [], $l)); ?>">
                        <input type="number" name="packs_available" form="stock-<?php echo e($stock->id); ?>" class="form-control" min="0" max="1000000" value="<?php echo e($stock->packs_available); ?>" aria-label="<?php echo e(__('public.pharmacy_portal.stock_col_packs', [], $l)); ?>">
                        <input type="text" name="pack_size" form="stock-<?php echo e($stock->id); ?>" class="form-control mt-6" maxlength="60" value="<?php echo e($stock->pack_size); ?>" placeholder="<?php echo e(__('public.pharmacy_portal.stock_lbl_pack_size', [], $l)); ?>" aria-label="<?php echo e(__('public.pharmacy_portal.stock_lbl_pack_size', [], $l)); ?>">
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_price', [], $l)); ?>">
                        <input type="number" name="unit_price" form="stock-<?php echo e($stock->id); ?>" class="form-control" min="0" step="0.01" value="<?php echo e($stock->unit_price); ?>" aria-label="<?php echo e(__('public.pharmacy_portal.stock_col_price', [], $l)); ?>">
                        <div class="td-muted"><?php echo e($stock->currency); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_reserve', [], $l)); ?>">
                        <label class="form-check">
                            <input type="checkbox" name="reservation_enabled" form="stock-<?php echo e($stock->id); ?>" value="1" <?php echo e($stock->reservation_enabled ? 'checked' : ''); ?>>
                            <span class="td-muted"><?php echo e(__('public.pharmacy_portal.stock_reserve_hint', [], $l)); ?></span>
                        </label>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_source', [], $l)); ?>">
                        <?php if($stock->source_system === $portalSource): ?>
                            <span class="badge badge-success"><?php echo e(__('public.pharmacy_portal.stock_src_portal', [], $l)); ?></span>
                        <?php else: ?>
                            <span class="badge badge-neutral"><?php echo e(__('public.pharmacy_portal.stock_src_other', ['source' => $stock->source_system ?: '—'], $l)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.stock_col_reported', [], $l)); ?>" class="td-muted">
                        <?php echo e($stock->last_reported_at?->diffForHumans() ?? __('public.pharmacy_portal.stock_never', [], $l)); ?>

                    </td>
                    <td data-label="<?php echo e(__('public.pharmacy_portal.col_action', [], $l) ?: 'Action'); ?>">
                        <form method="POST" action="<?php echo e(route('portals.pharmacy.stock.report')); ?>" id="stock-<?php echo e($stock->id); ?>" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="medicine_id" value="<?php echo e($stock->medicine_id); ?>">
                            <input type="hidden" name="search" value="<?php echo e(request('search')); ?>">
                            <input type="hidden" name="stock_status_filter" value="<?php echo e(request('stock_status')); ?>">
                            <button type="submit" class="btn btn-primary btn-xs">
                                <i data-lucide="save"></i> <?php echo e(__('public.pharmacy_portal.stock_btn_save', [], $l)); ?>

                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="td-muted empty-cell"><?php echo e(__('public.pharmacy_portal.stock_none', [], $l)); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($stocks?->links()); ?></div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/pharmacy/inventory.blade.php ENDPATH**/ ?>