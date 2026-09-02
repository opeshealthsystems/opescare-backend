<?php $__env->startSection('title', __('public.medical_id.health_id', [], app()->getLocale()) . ' — OpesCare Patient Portal'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>

<?php $__env->startSection('content'); ?>

<?php if($patient): ?>

<!-- Health ID Card -->
<div class="health-id-card mb-8" style="background:#0F2744;">
    <div class="flex-between" style="align-items:flex-start;gap:var(--p-space-5);">
        <div style="flex:1;">
            <div class="flex-between" style="justify-content:flex-start;gap:var(--p-space-3);margin-bottom:var(--p-space-5);">
                <div style="padding:var(--p-space-2);background:rgba(255,255,255,.15);border-radius:var(--p-radius);">
                    <i data-lucide="fingerprint"></i>
                </div>
                <span class="health-id-label"><?php echo e(__('public.medical_id.health_id', [], app()->getLocale()) ?: 'OpesCare Health ID'); ?></span>
            </div>

            <div class="health-id-value"><?php echo e($patient->health_id); ?></div>

            <div class="health-id-meta">
                <div>
                    <div class="health-id-meta-label"><?php echo e(__('public.portal.status', [], app()->getLocale()) ?: 'Status'); ?></div>
                    <div class="health-id-meta-value">
                        <?php echo e(\App\Support\Enums::label(($patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : $patient->verification_status) ?? 'Active', 'verification')); ?>
                    </div>
                </div>
                <div>
                    <div class="health-id-meta-label"><?php echo e(__('public.portal.country', [], app()->getLocale()) ?: 'Country'); ?></div>
                    <div class="health-id-meta-value"><?php echo e($patient->country_code ?? '—'); ?></div>
                </div>
                <div>
                    <div class="health-id-meta-label"><?php echo e(__('public.portal.registered', [], app()->getLocale()) ?: 'Registered'); ?></div>
                    <div class="health-id-meta-value"><?php echo e($patient->created_at?->format('M Y') ?? '—'); ?></div>
                </div>
            </div>
        </div>

        <!-- Static QR (server-rendered) -->
        <div class="health-id-qr" aria-label="<?php echo e(__('public.medical_id.scan_qr', [], app()->getLocale()) ?: 'Scan QR'); ?>">
            <?php if($staticQrDataUri): ?>
                <img src="<?php echo e($staticQrDataUri); ?>"
                     alt="<?php echo e(__('public.medical_id.health_id_qr_alt', [], app()->getLocale()) ?: 'Health ID QR Code'); ?>"
                     style="width:5rem;height:5rem;border-radius:var(--p-radius-sm);" />
            <?php else: ?>
                <div style="width:5rem;height:5rem;display:flex;align-items:center;justify-content:center;background:var(--p-surface-2);border-radius:var(--p-radius-sm);">
                    <i data-lucide="qr-code" style="width:3rem;height:3rem;"></i>
                </div>
            <?php endif; ?>
            <span><?php echo e(__('public.medical_id.scan_qr', [], app()->getLocale()) ?: 'Scan QR'); ?></span>
        </div>
    </div>
</div>

<?php if(isset($onboarding)): ?>
    <?php $obDone = collect($onboarding)->where('done', true)->count(); $obTotal = count($onboarding); ?>
    <?php if($obTotal && $obDone < $obTotal): ?>
    <div class="panel mb-8">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="rocket"></i> <?php echo e(__('public.pat_onb_title')); ?></h3>
            <span class="badge badge-info"><?php echo e(__('public.pat_onb_progress', ['done' => $obDone, 'total' => $obTotal])); ?></span>
        </div>
        <div class="panel-body">
            <p class="page-subtitle" style="margin:0 0 1rem;"><?php echo e(__('public.pat_onb_subtitle')); ?></p>
            <div style="height:6px;background:var(--color-border);border-radius:3px;overflow:hidden;margin-bottom:1.25rem;">
                <div style="height:100%;width:<?php echo e(round($obDone / $obTotal * 100)); ?>%;background:#0F4C81;border-radius:3px;"></div>
            </div>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.65rem;">
                <?php $__currentLoopData = $onboarding; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li style="display:flex;align-items:center;gap:.75rem;">
                        <span style="flex-shrink:0;width:1.65rem;height:1.65rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?php echo e($step['done'] ? '#0F4C81' : 'rgba(15,76,129,.08)'); ?>;color:<?php echo e($step['done'] ? '#fff' : '#0F4C81'); ?>;">
                            <i data-lucide="<?php echo e($step['done'] ? 'check' : $step['icon']); ?>" style="width:.9rem;height:.9rem;"></i>
                        </span>
                        <span style="flex:1;<?php echo e($step['done'] ? 'text-decoration:line-through;color:var(--color-text-secondary);' : ''); ?>"><?php echo e(__('public.pat_onb_' . $step['key'])); ?></span>
                        <?php if($step['done']): ?>
                            <span class="badge badge-success"><?php echo e(__('public.pat_onb_done')); ?></span>
                        <?php elseif($step['url']): ?>
                            <a href="<?php echo e($step['url']); ?>" class="btn btn-secondary btn-sm" aria-label="<?php echo e(__('public.pat_onb_' . $step['key'])); ?>"><i data-lucide="arrow-right" style="width:1rem;height:1rem;"></i></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Quick Actions -->
<div class="quick-actions mb-8">
    <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="calendar-check-2"></i></div>
        <span class="quick-action-label"><?php echo e(__('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments'); ?></span>
    </a>
        <a href="<?php echo e(route('portals.patient.prescriptions')); ?>" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="pill"></i></div>
        <span class="quick-action-label"><?php echo e(__('public.portal.nav_prescriptions', [], app()->getLocale()) ?: 'Prescriptions'); ?></span>
    </a>
                <a href="<?php echo e(route('portals.patient.profile')); ?>" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="user-cog"></i></div>
        <span class="quick-action-label"><?php echo e(__('public.portal.nav_profile', [], app()->getLocale()) ?: 'My Profile'); ?></span>
    </a>
                <a href="<?php echo e(route('public.care-map')); ?>" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="map-pin"></i></div>
        <span class="quick-action-label"><?php echo e(__('public.portal.nav_care_map', [], app()->getLocale()) ?: 'Care Map'); ?></span>
    </a>
    <a href="<?php echo e(route('portals.patient.family')); ?>" class="quick-action-btn">
        <div class="quick-action-icon"><i data-lucide="users"></i></div>
        <span class="quick-action-label"><?php echo e(__('public.portal.nav_family', [], app()->getLocale()) ?: 'Family'); ?></span>
    </a>
    <?php if (\Illuminate\Support\Facades\Blade::check('feature', 'insurance_marketplace')): ?>
        <?php endif; ?>
</div>

<?php if(isset($upcomingAppointments) && $upcomingAppointments->isNotEmpty()): ?>
<div class="panel mb-6">
  <div class="panel-header">
    <h3 class="panel-title"><i data-lucide="calendar-clock"></i> <?php echo e(__('public.portal.upcoming_appointments', [], app()->getLocale()) ?: 'Upcoming Appointments'); ?></h3>
    <a href="<?php echo e(route('portals.patient.appointments')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.portal.view_all', [], app()->getLocale()) ?: 'View all'); ?></a>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead><tr><th><?php echo e(__('public.portal.col_date', [], app()->getLocale()) ?: 'Date'); ?></th><th><?php echo e(__('public.portal.col_doctor', [], app()->getLocale()) ?: 'Doctor'); ?></th><th><?php echo e(__('public.portal.col_facility', [], app()->getLocale()) ?: 'Facility'); ?></th><th><?php echo e(__('public.portal.col_type', [], app()->getLocale()) ?: 'Type'); ?></th></tr></thead>
      <tbody>
      <?php $__currentLoopData = $upcomingAppointments->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td data-label="Date"><?php echo e($appt->scheduled_at?->format('d M Y, H:i') ?? '—'); ?></td>
        <td data-label="Doctor"><?php echo e($appt->provider?->name ?? '—'); ?></td>
        <td data-label="Facility"><?php echo e($appt->facility?->name ?? '—'); ?></td>
        <td data-label="Type"><span class="badge badge-info"><?php echo e(\App\Support\Enums::label($appt->appointment_type ?? 'consultation')); ?></span></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Clinical Safety Banner -->
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="shield-alert"></i> <?php echo e(__('public.portal.clinical_safety_summary', [], app()->getLocale()) ?: 'Clinical Safety Summary'); ?>

        </h2>
    </div>
    <div class="panel-body">
        <div class="stat-grid">

            
            <div class="stat-card stat-card--danger">
                <div class="stat-card__label"><?php echo e(__('public.portal.blood_group', [], app()->getLocale()) ?: 'Blood Group'); ?></div>
                <div class="stat-card__value"><?php echo e($patient->blood_group ?? '—'); ?></div>
                <?php if(!$patient->blood_group): ?>
                    <a href="<?php echo e(route('portals.patient.profile')); ?>" class="stat-card__hint"><?php echo e(__('public.portal.lnk_add_in_profile', [], app()->getLocale()) ?: 'Add in profile →'); ?></a>
                <?php endif; ?>
            </div>

            
            <div class="stat-card stat-card--danger">
                <div class="stat-card__label"><?php echo e(__('public.portal.critical_allergies', [], app()->getLocale()) ?: 'Critical Allergies'); ?></div>
                <?php if(($criticalAllergies ?? collect())->isEmpty()): ?>
                    <div class="stat-card__value text-success"><?php echo e(__('public.portal.none_on_record', [], app()->getLocale()) ?: 'None on record'); ?></div>
                <?php else: ?>
                    <div class="stat-card__value">
                        <?php $__currentLoopData = ($criticalAllergies ?? collect())->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="text-sm"><span class="badge badge-danger"><i data-lucide="alert-triangle"></i> <?php echo e($a->substance); ?></span></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php if(($criticalAllergies ?? collect())->count() > 3): ?>
                                            <?php endif; ?>
                <?php endif; ?>
            </div>

            
            <div class="stat-card <?php echo e(($activeAllergies ?? collect())->isEmpty() ? '' : 'stat-card--warning'); ?>">
                <div class="stat-card__label"><?php echo e(__('public.portal.active_allergies', [], app()->getLocale()) ?: 'Active Allergies'); ?></div>
                <div class="stat-card__value"><?php echo e(($activeAllergies ?? collect())->count()); ?></div>
                            </div>

            
            <div class="stat-card">
                <div class="stat-card__label"><?php echo e(__('public.portal.active_conditions', [], app()->getLocale()) ?: 'Active Conditions'); ?></div>
                <div class="stat-card__value"><?php echo e(($activeConditions ?? collect())->count()); ?></div>
                <?php if(($activeConditions ?? collect())->isNotEmpty()): ?>
                                    <?php endif; ?>
            </div>

        </div>

        <?php if(($activeAllergies ?? collect())->isNotEmpty() || ($activeConditions ?? collect())->isNotEmpty()): ?>
        <div class="alert alert-warning mt-3">
            <i data-lucide="alert-triangle"></i>
            <div>
                <?php echo e(__('public.portal.allergy_safety_notice', [], app()->getLocale()) ?: 'Ensure your care team has your up-to-date allergy and condition list before any procedure or prescription.'); ?>

                .
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Main Two-Column Layout -->
<div class="grid-main-side">

    <!-- Left: Temp QR Generator -->
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i data-lucide="qr-code"></i>
                <?php echo e(__('public.medical_id.temporary_access_qr', [], app()->getLocale()) ?: 'Temporary Access QR'); ?>

            </h2>
        </div>
        <div class="panel-body" style="text-align:center;">
            <p class="text-muted mb-6">
                <?php echo e(__('public.portal.temp_qr_desc', [], app()->getLocale()) ?: 'Generate a secure, time-limited QR code to share with a new healthcare provider. Expires in 1 hour.'); ?>

            </p>

            <button id="generate-temp-qr" class="btn btn-primary">
                <i data-lucide="refresh-cw"></i>
                <?php echo e(__('public.portal.generate_qr', [], app()->getLocale()) ?: 'Generate Temporary QR'); ?>

            </button>

            <div id="temp-qr-container" class="mt-6" style="display:none;flex-direction:column;align-items:center;" aria-live="polite">
                <div style="padding:var(--p-space-3);border-radius:var(--p-radius);border:1px solid var(--p-border);background:var(--p-surface);">
                    <img id="temp-qr-img" src="" alt="<?php echo e(__('public.portal.temp_qr_alt', [], app()->getLocale()) ?: 'Temporary Access QR Code'); ?>"
                         style="width:8rem;height:8rem;border-radius:var(--p-radius-sm);display:none;" />
                    <div id="temp-qr-placeholder" style="width:8rem;height:8rem;display:flex;align-items:center;justify-content:center;background:var(--p-surface-2);border-radius:var(--p-radius-sm);">
                        <i data-lucide="qr-code" style="width:4rem;height:4rem;"></i>
                    </div>
                </div>
                <p class="mt-3 text-sm" style="color:var(--p-warning);font-weight:700;display:flex;align-items:center;gap:var(--p-space-2);">
                    <i data-lucide="clock"></i>
                    <?php echo e(__('public.portal.expires_in', [], app()->getLocale()) ?: 'Expires in'); ?>: <span id="countdown">60:00</span>
                </p>
            </div>

            <div class="alert alert-info mt-6" style="text-align:left;">
                <i data-lucide="info"></i>
                <div><?php echo e(__('public.portal.qr_audit_notice', [], app()->getLocale()) ?: 'Each QR scan is audited. You can view access history in your Access Logs.'); ?></div>
            </div>
        </div>
    </div>

    <!-- Right: Profile prompt + Disclaimer -->
    <div style="display:flex;flex-direction:column;gap:var(--p-space-5);">

        <!-- Profile prompt -->
        <div class="panel">
            <div class="panel-body" style="text-align:center;">
                <p class="text-sm text-muted mb-3"><?php echo e(__('public.portal.profile_prompt_desc', [], app()->getLocale()) ?: 'Manage your privacy preferences, contact details and emergency contacts in your profile.'); ?></p>
                <a href="<?php echo e(route('portals.patient.profile')); ?>" class="btn btn-primary btn-sm">
                    <i data-lucide="user-cog"></i> <?php echo e(__('public.portal.profile_prompt_btn', [], app()->getLocale()) ?: 'Go to Profile'); ?>

                </a>
            </div>
        </div>

        <!-- Clinical Safety Disclaimer -->
        <div class="panel">
            <div class="panel-body">
                <div class="alert alert-warning">
                    <i data-lucide="alert-triangle"></i>
                    <div>
                        <?php echo e(__('onboarding.brand.clinical_disclaimer', [], app()->getLocale()) ?: 'OpesCare facilitates access to your health records but is not a substitute for clinical advice. Always consult a licensed healthcare provider for medical decisions.'); ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php else: ?>
<!-- No patient profile -->
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon">
            <i data-lucide="alert-circle"></i>
        </div>
        <h3><?php echo e(__('public.portal.no_profile_title', [], app()->getLocale()) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.no_profile_desc', [], app()->getLocale()) ?: 'Your patient profile could not be loaded. Please contact support if this problem persists.'); ?></p>
        <a href="<?php echo e(route('public.help')); ?>" class="btn btn-primary">
            <i data-lucide="help-circle"></i>
            <?php echo e(__('public.portal.nav_help', [], app()->getLocale()) ?: 'Get Help'); ?>

        </a>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var lblGenerating   = <?php echo json_encode(__('public.portal.generating', [], app()->getLocale()) ?: 'Generating…') ?>;
    var lblRegenerateQr = <?php echo json_encode(__('public.portal.regenerate_qr', [], app()->getLocale()) ?: 'Regenerate QR') ?>;
    var lblQrExpired    = <?php echo json_encode(__('public.portal.lbl_qr_expired', [], app()->getLocale()) ?: 'Expired') ?>;
    var countdownInterval = null;

    var btnGen = document.getElementById('generate-temp-qr');
    if (btnGen) {
        btnGen.addEventListener('click', async function () {
            btnGen.disabled = true;
            btnGen.innerHTML = '<i data-lucide="loader" style="width:1rem;height:1rem;"></i> ' + lblGenerating;
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                var response = await fetch("<?php echo e(route('portals.patient.qr')); ?>", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
                });
                if (!response.ok) throw new Error('QR generation failed (' + response.status + ')');
                var data = await response.json();
                if (data.qr_image) {
                    var img         = document.getElementById('temp-qr-img');
                    var placeholder = document.getElementById('temp-qr-placeholder');
                    var container   = document.getElementById('temp-qr-container');
                    img.src         = data.qr_image;
                    img.style.display       = 'block';
                    placeholder.style.display = 'none';
                    container.style.display = 'flex';
                    startCountdown(data.expires_in || 3600);
                }
            } catch (e) {
                console.error('Temp QR error:', e);
            } finally {
                btnGen.disabled = false;
                btnGen.innerHTML = '<i data-lucide="refresh-cw" style="width:1rem;height:1rem;"></i> ' + lblRegenerateQr;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    function startCountdown(seconds) {
        if (countdownInterval) clearInterval(countdownInterval);
        var el = document.getElementById('countdown');
        countdownInterval = setInterval(function () {
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                if (el) el.textContent = lblQrExpired;
                return;
            }
            seconds--;
            var m = Math.floor(seconds / 60).toString().padStart(2, '0');
            var s = (seconds % 60).toString().padStart(2, '0');
            if (el) el.textContent = m + ':' + s;
        }, 1000);
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/index.blade.php ENDPATH**/ ?>