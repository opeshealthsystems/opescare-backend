<?php $__env->startSection('title', __('caremap_claim.review_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('caremap_claim.review_title')); ?>

<?php $__env->startSection('head'); ?>
<style>
  .dr-tabs{display:flex;gap:.5rem;border-bottom:1px solid var(--p-border);margin-bottom:var(--p-space-5)}
  .dr-tab{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1rem;font-size:.875rem;font-weight:600;color:var(--p-text-muted);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px}
  .dr-tab.active{color:var(--p-primary);border-bottom-color:var(--p-primary)}
  .dr-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;padding:0 .35rem;height:1.35rem;border-radius:999px;background:var(--p-surface-2);font-size:.7rem;font-weight:700}
  .dr-tab.active .dr-tab-count{background:rgba(15,76,129,.12);color:var(--p-primary)}
  .dr-inline-form{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
  .dr-inline-form .form-control{min-width:9rem;padding:.3rem .55rem;font-size:.78rem}
  .dr-tags{font-family:'Courier New',monospace;font-size:.72rem;color:var(--p-text-muted);white-space:pre-wrap;word-break:break-word;max-width:34rem}
  .dr-note{font-size:.8rem;color:var(--p-text-muted);margin-bottom:var(--p-space-4)}
  .dr-reasonbar{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:var(--p-space-4)}
  .dr-reason-chip{padding:.3rem .7rem;border:1px solid var(--p-border);border-radius:999px;font-size:.75rem;font-weight:600;color:var(--p-text-2);text-decoration:none}
  .dr-reason-chip.active{background:var(--p-primary);border-color:var(--p-primary);color:#fff}
  /* Side-by-side: a duplicate cannot be judged from one record. */
  .dr-card{border:1px solid var(--p-border);border-radius:var(--p-radius,8px);margin-bottom:var(--p-space-4);overflow:hidden}
  .dr-card-head{display:flex;align-items:flex-start;gap:.75rem;flex-wrap:wrap;padding:.75rem 1rem;background:var(--p-surface-2);border-bottom:1px solid var(--p-border)}
  .dr-card-title{font-weight:700;font-size:.95rem}
  .dr-card-sub{font-size:.75rem;color:var(--p-text-muted)}
  .dr-compare{display:grid;grid-template-columns:1fr 1fr;gap:0}
  .dr-col{padding:.85rem 1rem}
  .dr-col + .dr-col{border-left:1px solid var(--p-border)}
  .dr-col-head{font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;font-weight:700;color:var(--p-text-muted);margin-bottom:.5rem}
  .dr-col--candidate .dr-col-head{color:var(--p-primary)}
  .dr-field{display:flex;gap:.5rem;font-size:.8rem;padding:.18rem 0}
  .dr-field dt{flex:0 0 6.5rem;color:var(--p-text-muted)}
  .dr-field dd{margin:0;font-weight:600;word-break:break-word}
  .dr-field dd.is-missing{font-weight:400;color:var(--p-text-muted);font-style:italic}
  .dr-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.7rem 1rem;border-top:1px solid var(--p-border)}
  .dr-attrib{font-size:.75rem;color:var(--p-text-muted);margin-bottom:var(--p-space-4)}
  @media (max-width:860px){.dr-compare{grid-template-columns:1fr}.dr-col + .dr-col{border-left:0;border-top:1px solid var(--p-border)}}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('caremap_claim.review_title')); ?></h2>
    <div class="page-head__spacer"></div>
</div>
<p class="dr-note"><?php echo e(__('caremap_claim.review_subtitle')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="dr-tabs">
    <a href="<?php echo e(route('admin.care-map.review', ['tab' => 'claims'])); ?>" class="dr-tab <?php echo e($tab === 'claims' ? 'active' : ''); ?>">
        <i data-lucide="badge-check"></i>
        <span><?php echo e(__('caremap_claim.tab_claims')); ?></span>
        <span class="dr-tab-count"><?php echo e($pendingClaimCount); ?></span>
    </a>
    <a href="<?php echo e(route('admin.care-map.review', ['tab' => 'imports'])); ?>" class="dr-tab <?php echo e($tab === 'imports' ? 'active' : ''); ?>">
        <i data-lucide="git-pull-request-arrow"></i>
        <span><?php echo e(__('caremap_claim.tab_imports')); ?></span>
        <span class="dr-tab-count"><?php echo e($pendingImportCount); ?></span>
    </a>
</div>

<?php if($tab === 'claims'): ?>

    
    <div class="alert alert-warning mb-6">
        <i data-lucide="shield-alert"></i>
        <div><?php echo e(__('caremap_claim.approve_warning')); ?></div>
    </div>

    <?php if($pendingClaims->isEmpty()): ?>
        <div class="panel">
            <div class="panel-body">
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
                    <p><?php echo e(__('caremap_claim.empty_claims_queue')); ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="clock"></i> <?php echo e($pendingClaims->total()); ?></h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('caremap_claim.col_facility')); ?></th>
                            <th><?php echo e(__('caremap_claim.claim_col_claimant')); ?></th>
                            <th><?php echo e(__('caremap_claim.claim_col_contact')); ?></th>
                            <th><?php echo e(__('caremap_claim.col_submitted')); ?></th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $pendingClaims; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $claim): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $listing = $claim->careFacility; ?>
                        <tr>
                            <td data-label="<?php echo e(__('caremap_claim.col_facility')); ?>">
                                <span class="td-strong"><?php echo e($listing?->facility_name ?? $claim->facility?->name ?? '—'); ?></span>
                                <?php if($listing): ?>
                                    <div class="td-muted"><?php echo e($listing->city); ?><?php echo e($listing->region ? ', ' . $listing->region : ''); ?></div>
                                    <a href="<?php echo e(route('public.care-map.profile', $listing->id)); ?>" class="td-muted" target="_blank" rel="noopener">
                                        <?php echo e(__('caremap_claim.view_listing')); ?>

                                    </a>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.claim_col_claimant')); ?>">
                                <span class="td-strong"><?php echo e($claim->claimant_name ?? $claim->claimant?->name ?? '—'); ?></span>
                                <div class="td-muted"><?php echo e($claim->claimant_role ?? '—'); ?></div>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.claim_col_contact')); ?>" class="td-muted">
                                <?php echo e($claim->claimant_email ?? $claim->claimant?->email ?? '—'); ?>

                                <?php if($claim->claimant_phone): ?><div><?php echo e($claim->claimant_phone); ?></div><?php endif; ?>
                                <?php if($claim->claim_reason): ?><div><?php echo e(Str::limit($claim->claim_reason, 120)); ?></div><?php endif; ?>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.col_submitted')); ?>" class="td-muted">
                                <?php echo e(optional($claim->submitted_at ?? $claim->created_at)->format('Y-m-d') ?? '—'); ?>

                            </td>
                            <td class="row-actions">
                                <form method="POST" action="<?php echo e(route('admin.care-map.review.claims.reject', $claim->id)); ?>" class="dr-inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="text" name="review_notes" class="form-control" placeholder="<?php echo e(__('caremap_claim.notes_placeholder')); ?>" maxlength="1000">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i data-lucide="x"></i> <?php echo e(__('caremap_claim.btn_reject')); ?>

                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.care-map.review.claims.approve', $claim->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i data-lucide="check"></i> <?php echo e(__('caremap_claim.btn_approve')); ?>

                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mb-6"><?php echo e($pendingClaims->links()); ?></div>
    <?php endif; ?>

    <?php if($decidedClaims->isNotEmpty()): ?>
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="history"></i> <?php echo e(__('caremap_claim.recent_decisions')); ?></h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('caremap_claim.col_facility')); ?></th>
                            <th><?php echo e(__('caremap_claim.claim_col_claimant')); ?></th>
                            <th><?php echo e(__('caremap_claim.col_status')); ?></th>
                            <th><?php echo e(__('caremap_claim.col_reviewed')); ?></th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $decidedClaims; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $claim): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $badge = match($claim->claim_status) {
                                \App\Enums\FacilityClaimStatus::Approved => 'badge-success',
                                \App\Enums\FacilityClaimStatus::Rejected => 'badge-danger',
                                default                                  => 'badge-neutral',
                            };
                        ?>
                        <tr>
                            <td data-label="<?php echo e(__('caremap_claim.col_facility')); ?>">
                                <span class="td-strong"><?php echo e($claim->careFacility?->facility_name ?? '—'); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.claim_col_claimant')); ?>" class="td-muted">
                                <?php echo e($claim->claimant_name ?? $claim->claimant?->name ?? '—'); ?>

                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.col_status')); ?>">
                                <span class="badge <?php echo e($badge); ?>"><?php echo e(__('caremap_claim.status_' . $claim->claim_status->value)); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.col_reviewed')); ?>" class="td-muted">
                                <?php echo e(optional($claim->reviewed_at)->format('Y-m-d') ?? '—'); ?>

                            </td>
                            <td class="row-actions">
                                <?php if($claim->claim_status === \App\Enums\FacilityClaimStatus::Approved): ?>
                                    <form method="POST" action="<?php echo e(route('admin.care-map.review.claims.revoke', $claim->id)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-ghost btn-sm">
                                            <i data-lucide="undo-2"></i> <?php echo e(__('caremap_claim.btn_revoke')); ?>

                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>

    

    
    <?php if($osmAttribution): ?>
        <p class="dr-attrib">
            <i data-lucide="scale"></i>
            <?php echo e(__('facility_review.attribution', ['attribution' => $osmAttribution])); ?>

            — <?php echo e(__('facility_review.attribution_note')); ?>

        </p>
    <?php endif; ?>

    
    <div class="dr-reasonbar">
        <?php $__currentLoopData = \App\Enums\FacilityImportReviewStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.care-map.review', ['tab' => 'imports', 'status' => $case->value])); ?>"
               class="dr-reason-chip <?php echo e($activeStatus === $case ? 'active' : ''); ?>">
                <?php echo e(__($case->translationKey())); ?> (<?php echo e($statusCounts[$case->value] ?? 0); ?>)
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="dr-reasonbar">
        <a href="<?php echo e(route('admin.care-map.review', ['tab' => 'imports', 'status' => $activeStatus->value])); ?>"
           class="dr-reason-chip <?php echo e($activeReason ? '' : 'active'); ?>"><?php echo e(__('caremap_claim.filter_all_reasons')); ?></a>
        <?php $__currentLoopData = $reasonCounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason => $total): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.care-map.review', ['tab' => 'imports', 'status' => $activeStatus->value, 'reason' => $reason])); ?>"
               class="dr-reason-chip <?php echo e($activeReason === $reason ? 'active' : ''); ?>">
                <?php echo e(__('caremap_claim.reason_' . $reason)); ?> (<?php echo e($total); ?>)
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if($importReviews->isEmpty()): ?>
        <div class="panel">
            <div class="panel-body">
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
                    <p><?php echo e(__('caremap_claim.empty_imports_queue')); ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="import"></i> <?php echo e($importReviews->total()); ?></h3>
            </div>
            <div class="panel-body">
                <p class="dr-note"><?php echo e(__('facility_review.never_verified')); ?></p>

                <?php
                    // Shown only when the value is real. 692 directory rows carry the
                    // literal string 'N/A' in phone_primary; printing that next to a
                    // candidate's phone number would read as a difference that isn't one.
                    $drCoords = static fn ($lat, $lng) => ($lat !== null && $lng !== null)
                        ? number_format((float) $lat, 5) . ', ' . number_format((float) $lng, 5)
                        : null;
                    $drReal = static fn ($v) => (filled($v) && strcasecmp((string) $v, 'N/A') !== 0) ? $v : null;
                ?>

                <?php $__currentLoopData = $importReviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $name    = $review->displayName();
                        $listing = $review->matchedFacility;
                        $cluster = $review->matched_facility_id ? ($clusterCounts[$review->matched_facility_id] ?? 1) : 1;

                        $candidateFields = [
                            'field_name'   => $name !== '' ? $name : null,
                            'field_type'   => $review->candidate_type,
                            'field_city'   => $review->candidate_city,
                            'field_region' => $review->candidate_region,
                            'field_coords' => $drCoords($review->latitude, $review->longitude),
                            'field_phone'  => $drReal($review->payload['phone'] ?? $review->payload['contact:phone'] ?? null),
                            'field_source' => $review->source_system . ' / ' . $review->source_ref,
                        ];

                        $existingFields = $listing ? [
                            'field_name'   => $listing->facility_name,
                            'field_type'   => $listing->facility_type,
                            'field_city'   => $listing->city,
                            'field_region' => $listing->region,
                            'field_coords' => $drCoords($listing->latitude, $listing->longitude),
                            'field_phone'  => $drReal($listing->phone_primary),
                            'field_status' => $listing->listing_status,
                        ] : [];
                    ?>

                    <div class="dr-card">
                        <div class="dr-card-head">
                            <div>
                                <div class="dr-card-title"><?php echo e($name !== '' ? $name : __('facility_review.value_missing')); ?></div>
                                <div class="dr-card-sub">
                                    <?php echo e(__('caremap_claim.reason_' . $review->reason)); ?>

                                    <?php if($review->match_score !== null): ?>
                                        &bull; <?php echo e(__('facility_review.match_score', ['score' => number_format($review->match_score, 2)])); ?>

                                    <?php endif; ?>
                                    <?php if($review->match_distance_m !== null): ?>
                                        &bull; <?php echo e(__('facility_review.match_distance', ['metres' => $review->match_distance_m])); ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="flex:1"></div>
                            <span class="badge badge-neutral"><?php echo e(__($review->status->translationKey())); ?></span>
                        </div>

                        <?php if($cluster > 1): ?>
                            <div class="alert alert-warning" style="margin:.75rem 1rem 0">
                                <i data-lucide="copy"></i>
                                <div><?php echo e(__('facility_review.cluster_warning', ['count' => $cluster])); ?></div>
                            </div>
                        <?php endif; ?>

                        
                        <div class="dr-compare">
                            <div class="dr-col dr-col--candidate">
                                <div class="dr-col-head"><?php echo e(__('facility_review.compare_candidate')); ?></div>
                                <dl>
                                    <?php $__currentLoopData = $candidateFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="dr-field">
                                            <dt><?php echo e(__('facility_review.' . $key)); ?></dt>
                                            <dd class="<?php echo e(filled($value) ? '' : 'is-missing'); ?>"><?php echo e(filled($value) ? $value : __('facility_review.value_missing')); ?></dd>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </dl>
                                <?php if($name === ''): ?>
                                    
                                    <p class="td-muted"><i data-lucide="alert-triangle"></i> <?php echo e(__('caremap_claim.no_name_warning')); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($review->payload)): ?>
                                    <details>
                                        <summary class="td-muted"><?php echo e(__('caremap_claim.raw_tags')); ?></summary>
                                        <div class="dr-tags"><?php echo e(json_encode($review->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></div>
                                    </details>
                                <?php endif; ?>
                            </div>

                            <div class="dr-col">
                                <div class="dr-col-head"><?php echo e(__('facility_review.compare_existing')); ?></div>
                                <?php if($listing): ?>
                                    <dl>
                                        <?php $__currentLoopData = $existingFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="dr-field">
                                                <dt><?php echo e(__('facility_review.' . $key)); ?></dt>
                                                <dd class="<?php echo e(filled($value) ? '' : 'is-missing'); ?>"><?php echo e(filled($value) ? $value : __('facility_review.value_missing')); ?></dd>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </dl>
                                    <a href="<?php echo e(route('public.care-map.profile', $listing->id)); ?>" class="td-muted" target="_blank" rel="noopener">
                                        <i data-lucide="external-link"></i> <?php echo e(__('caremap_claim.view_listing')); ?>

                                    </a>
                                <?php else: ?>
                                    <p class="td-muted"><?php echo e(__('facility_review.no_match')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($review->reviewed_at): ?>
                            <div class="dr-card-sub" style="padding:0 1rem .5rem">
                                <?php echo e(__('facility_review.' . ($review->status === \App\Enums\FacilityImportReviewStatus::Deferred ? 'deferred_meta' : 'decided_meta'), [
                                    'name' => $review->reviewer?->name ?? '—',
                                    'date' => $review->reviewed_at->format('Y-m-d'),
                                ])); ?>

                                <?php if($review->review_notes): ?> &bull; <?php echo e($review->review_notes); ?> <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($review->status->isOpen()): ?>
                            <div class="dr-actions">
                                <form method="POST" action="<?php echo e(route('admin.care-map.review.imports.accept', $review->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-primary btn-sm" <?php if($name === ''): echo 'disabled'; endif; ?>>
                                        <i data-lucide="plus"></i> <?php echo e(__('caremap_claim.btn_accept')); ?>

                                    </button>
                                </form>
                                <?php if($review->matched_facility_id): ?>
                                    <form method="POST" action="<?php echo e(route('admin.care-map.review.imports.merge', $review->id)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <i data-lucide="merge"></i> <?php echo e(__('caremap_claim.btn_merge')); ?>

                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if($review->status->isPending()): ?>
                                    <form method="POST" action="<?php echo e(route('admin.care-map.review.imports.defer', $review->id)); ?>" class="dr-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="text" name="review_notes" class="form-control" placeholder="<?php echo e(__('facility_review.defer_reason')); ?>" maxlength="1000">
                                        <button type="submit" class="btn btn-ghost btn-sm" title="<?php echo e(__('facility_review.defer_hint')); ?>">
                                            <i data-lucide="clock"></i> <?php echo e(__('facility_review.btn_defer')); ?>

                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo e(route('admin.care-map.review.imports.reject', $review->id)); ?>" class="dr-inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="text" name="review_notes" class="form-control" placeholder="<?php echo e(__('caremap_claim.notes_placeholder')); ?>" maxlength="1000">
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <i data-lucide="x"></i> <?php echo e(__('caremap_claim.btn_reject')); ?>

                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div class="mb-6"><?php echo e($importReviews->links()); ?></div>
    <?php endif; ?>

<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/directory_review.blade.php ENDPATH**/ ?>