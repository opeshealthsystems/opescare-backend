
<!DOCTYPE html>
<html lang="<?php echo e($locale ?? app()->getLocale()); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php echo $__env->make('partials.finder.schema.facility-meta', ['facility' => $facility], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.finder.schema.facility', ['facility' => $facility], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.finder.schema.breadcrumbs', ['facility' => $facility], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/finder.css')); ?>">

<?php
  use App\Models\CareFacility;
  use Illuminate\Support\Facades\Lang;

  \Illuminate\Support\Carbon::setLocale(app()->getLocale());

  $slug = strtolower(str_replace(' ', '_', $facility->facility_type ?? 'other'));
  $typeLabel = Lang::has("finder_directory.types.{$slug}")
      ? __("finder_directory.types.{$slug}")
      : ucwords(str_replace('_', ' ', $slug));
  $typeIcon = ['pharmacy' => 'pill', 'laboratory' => 'droplet', 'blood_bank' => 'droplet'][$slug] ?? 'building-2';

  $phone   = $facility->dialablePhone();
  $email   = CareFacility::realValue($facility->email);
  $website = CareFacility::realValue($facility->website);
  $websiteHref = $website && !preg_match('#^https?://#i', $website) ? 'https://' . $website : $website;

  $placeLine = collect([$facility->city, $facility->region])->filter()->unique()->implode(', ');
  $dirUrl = ($facility->latitude && $facility->longitude)
      ? 'https://www.google.com/maps/dir/?api=1&destination=' . $facility->latitude . ',' . $facility->longitude
      : null;

  $backUrl = route('public.care-map', array_filter(['lang' => request('lang')]));

  // Open / closed — from this facility's own reported hours only.
  $currentDay  = (int) now()->format('w');
  $currentTime = now()->format('H:i');
  $isOpen = null; $is24h = false;
  if ($facility->hours && $facility->hours->isNotEmpty()) {
      $todayH = $facility->hours->first(fn ($h) => (int) $h->day_of_week === $currentDay);
      if ($todayH) {
          if ($todayH->is_24_hours)   { $isOpen = true; $is24h = true; }
          elseif ($todayH->is_closed) { $isOpen = false; }
          elseif ($todayH->opens_at && $todayH->closes_at) {
              $isOpen = $currentTime >= substr($todayH->opens_at, 0, 5)
                     && $currentTime <= substr($todayH->closes_at, 0, 5);
          }
      }
  }

  $hasHours    = ($facility->hours?->count() ?? 0) > 0;
  $hasServices = ($facility->services?->count() ?? 0) > 0;
  $hasIns      = ($facility->insurances?->count() ?? 0) > 0;
  $stockRows   = $facility->pharmacyStock ?? collect();
  $bloodRows   = $facility->bloodAvailability ?? collect();
  $labRows     = $facility->labTests ?? collect();
  $hasAvail    = $stockRows->isNotEmpty() || $bloodRows->isNotEmpty() || $labRows->isNotEmpty();

  $statusLabel = fn ($s) => Lang::has('finder_directory.statuses.' . $s)
      ? __('finder_directory.statuses.' . $s)
      : ucfirst(str_replace('_', ' ', (string) $s));
  $statusClass = fn ($s) => match ($s) {
      'in_stock', 'available'        => 'f-badge--good',
      'low_stock', 'limited'         => 'f-badge--warn',
      'out_of_stock', 'unavailable'  => 'f-badge--danger',
      default                        => '',
  };
  $reportedLine = fn ($row) => $row->last_updated_at
      ? __('finder_directory.reported_time', ['time' => \Illuminate\Support\Carbon::parse($row->last_updated_at)->diffForHumans()])
      : __('finder_directory.not_dated');

  $provenance = trim((string) $facility->source_attribution) !== ''
      ? __('finder_directory.provenance_source', ['source' => $facility->source_attribution])
      : __('finder_directory.provenance_registry');

  $langParam = fn (string $l) => request()->fullUrlWithQuery(['lang' => $l]);
?>

<style>
/* Page-specific layer on top of finder.css — tokens only, no new colors. */
.p-wrap { max-width: 760px; margin-inline: auto; padding: 16px 16px 48px; }
.p-stack { display: flex; flex-direction: column; gap: 14px; }

.p-head .f-badge { margin-bottom: 10px; }
.p-name { font-size: clamp(1.35rem, 4vw + 0.6rem, 1.9rem); line-height: 1.2; font-weight: 800; letter-spacing: -0.015em; margin: 0 0 6px; }
.p-place { display: flex; align-items: center; gap: 6px; color: var(--f-muted); font-size: 0.9rem; margin: 0; }

.p-actions { display: flex; flex-wrap: wrap; gap: 10px; }
.p-actions .f-btn { flex: 1 1 auto; }
@media (min-width: 640px) { .p-actions .f-btn { flex: 0 1 auto; } }

.p-card-title { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 750; letter-spacing: 0.06em; text-transform: uppercase; color: var(--f-muted); margin: 0 0 12px; }
.p-row { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--f-line); font-size: 0.9rem; }
.p-row:last-child { border-bottom: none; }
.p-row-label { color: var(--f-muted); font-size: 0.8rem; min-width: 78px; flex-shrink: 0; padding-top: 1px; }
.p-row-value { flex: 1; min-width: 0; overflow-wrap: anywhere; margin: 0; }
.p-row-value a { text-decoration: none; font-weight: 600; }
.p-row-value a:hover { text-decoration: underline; }

.p-hours-row { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px solid var(--f-line); font-size: 0.9rem; }
.p-hours-row:last-child { border-bottom: none; }
.p-hours-row.is-today { font-weight: 750; color: var(--f-brand-ink); }
.p-hours-closed { color: var(--f-muted); font-style: italic; }
.p-hours-24 { color: var(--f-good); font-weight: 650; }

.p-svc { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--f-line); font-size: 0.9rem; }
.p-svc:last-child { border-bottom: none; }

.p-avail-item { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 10px; padding: 10px 0; border-bottom: 1px solid var(--f-line); }
.p-avail-item:last-child { border-bottom: none; }
.p-avail-name { font-weight: 650; font-size: 0.92rem; }
.p-avail-sub { width: 100%; display: flex; flex-wrap: wrap; gap: 6px 12px; align-items: center; }

.p-chips { display: flex; flex-wrap: wrap; gap: 8px; }

.p-note { display: flex; gap: 8px; align-items: flex-start; background: var(--f-warn-wash); border: 1px solid var(--f-line); border-radius: var(--f-r); padding: 10px 12px; font-size: 0.85rem; color: var(--f-warn); line-height: 1.55; }
.p-note .f-icon { flex-shrink: 0; margin-top: 2px; }
.p-provenance { font-size: 0.85rem; color: var(--f-ink-soft); line-height: 1.6; margin: 0 0 8px; }
.p-claim { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--f-line); font-size: 0.88rem; }
.p-back { display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 650; font-size: 0.9rem; color: var(--f-brand); white-space: nowrap; }
.p-emergency { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; background: var(--f-danger-wash); color: var(--f-danger); font-weight: 750; font-size: 0.8rem; text-decoration: none; white-space: nowrap; }
</style>
</head>
<body>

<header class="f-header">
  <div class="f-container f-header__row">
    <a class="p-back" href="<?php echo e($backUrl); ?>">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.back_to_directory')); ?>

    </a>
    <div class="f-header__actions">
      <nav class="f-lang" aria-label="EN / FR">
        <a href="<?php echo e($langParam('en')); ?>" <?php if(app()->getLocale() === 'en'): ?> class="is-active" aria-current="true" <?php endif; ?>>EN</a>
        <a href="<?php echo e($langParam('fr')); ?>" <?php if(app()->getLocale() === 'fr'): ?> class="is-active" aria-current="true" <?php endif; ?>>FR</a>
      </nav>
      <a class="p-emergency" href="<?php echo e(route('public.care-map.emergency', array_filter(['lang' => request('lang')]))); ?>">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 13], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_directory.emergency')); ?>

      </a>
    </div>
  </div>
</header>

<main class="p-wrap p-stack">

  
  <section class="f-card p-head">
    <span class="f-badge">
      <?php echo $__env->make('partials.finder.icon', ['name' => $typeIcon, 'size' => 13], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e($typeLabel); ?>

    </span>
    <?php if($is24h): ?>
      <span class="f-badge f-badge--good"><?php echo e(__('finder_directory.open_24h')); ?></span>
    <?php elseif($isOpen === true): ?>
      <span class="f-badge f-badge--good"><?php echo e(__('finder_directory.open_now')); ?></span>
    <?php elseif($isOpen === false): ?>
      <span class="f-badge f-badge--danger"><?php echo e(__('finder_directory.closed_now')); ?></span>
    <?php endif; ?>

    <h1 class="p-name"><?php echo e($facility->facility_name); ?></h1>
    <?php if($placeLine): ?>
      <p class="p-place">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e($placeLine); ?>

      </p>
    <?php endif; ?>
  </section>

  
  <?php if($phone || $dirUrl || $bookable): ?>
  <div class="p-actions">
    <?php if($phone): ?>
      <a class="f-btn f-btn--primary" href="tel:<?php echo e($phone); ?>">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_directory.call')); ?>

      </a>
    <?php endif; ?>
    <?php if($bookable): ?>
      <a class="f-btn <?php echo e($phone ? '' : 'f-btn--primary'); ?>" href="<?php echo e(route('portals.patient.appointments.book')); ?>">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'calendar', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_directory.book_appointment')); ?>

      </a>
    <?php endif; ?>
    <?php if($dirUrl): ?>
      <a class="f-btn" href="<?php echo e($dirUrl); ?>" target="_blank" rel="noopener noreferrer">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'navigation', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e(__('finder_directory.directions')); ?>

      </a>
    <?php endif; ?>
  </div>
  <?php if($bookable): ?>
    <p class="f-small f-muted" style="margin:-4px 0 0 2px;"><?php echo e(__('finder_directory.booking_hint')); ?></p>
  <?php endif; ?>
  <?php endif; ?>

  
  <?php if(trim((string) $facility->description) !== ''): ?>
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.about_heading')); ?>

    </h2>
    <p style="margin:0; font-size:0.92rem; line-height:1.65;"><?php echo e($facility->description); ?></p>
  </section>
  <?php endif; ?>

  
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.contact_heading')); ?>

    </h2>

    <?php if(trim((string) $facility->address) !== ''): ?>
      <div class="p-row">
        <span class="p-row-label"><?php echo e(__('finder_directory.address_label')); ?></span>
        <span class="p-row-value"><?php echo e(collect([$facility->address, $facility->city, $facility->region])->map(fn ($v) => trim((string) $v))->filter()->unique(fn ($v) => mb_strtolower($v))->implode(', ')); ?></span>
      </div>
    <?php elseif($placeLine): ?>
      <div class="p-row">
        <span class="p-row-label"><?php echo e(__('finder_directory.region_label')); ?></span>
        <span class="p-row-value"><?php echo e($placeLine); ?></span>
      </div>
    <?php endif; ?>

    <?php if($phone): ?>
      <div class="p-row">
        <span class="p-row-label"><?php echo e(__('finder_directory.phone_label')); ?></span>
        <span class="p-row-value"><a href="tel:<?php echo e($phone); ?>"><?php echo e($phone); ?></a></span>
      </div>
    <?php endif; ?>

    <?php if($email): ?>
      <div class="p-row">
        <span class="p-row-label"><?php echo e(__('finder_directory.email_label')); ?></span>
        <span class="p-row-value"><a href="mailto:<?php echo e($email); ?>"><?php echo e($email); ?></a></span>
      </div>
    <?php endif; ?>

    <?php if($website): ?>
      <div class="p-row">
        <span class="p-row-label"><?php echo e(__('finder_directory.website_label')); ?></span>
        <span class="p-row-value">
          <a href="<?php echo e($websiteHref); ?>" target="_blank" rel="noopener noreferrer">
            <?php echo e($website); ?>

            <?php echo $__env->make('partials.finder.icon', ['name' => 'external-link', 'size' => 12], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          </a>
        </span>
      </div>
    <?php endif; ?>
  </section>

  
  <?php if($hasHours): ?>
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.hours_heading')); ?>

    </h2>
    <?php $__currentLoopData = $facility->hours->sortBy('day_of_week'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="p-hours-row <?php echo e((int) $h->day_of_week === $currentDay ? 'is-today' : ''); ?>">
        <span><?php echo e(__('finder_directory.days.' . (int) $h->day_of_week)); ?></span>
        <?php if($h->is_24_hours): ?>
          <span class="p-hours-24"><?php echo e(__('finder_directory.open_24h')); ?></span>
        <?php elseif($h->is_closed): ?>
          <span class="p-hours-closed"><?php echo e(__('finder_directory.closed_day')); ?></span>
        <?php elseif($h->opens_at && $h->closes_at): ?>
          <span><?php echo e(substr($h->opens_at, 0, 5)); ?> – <?php echo e(substr($h->closes_at, 0, 5)); ?></span>
        <?php else: ?>
          <span class="p-hours-closed">—</span>
        <?php endif; ?>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </section>
  <?php endif; ?>

  
  <?php if($hasServices): ?>
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.services_heading')); ?>

    </h2>
    <?php $__currentLoopData = $facility->services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="p-svc">
        <span><?php echo e($svc->service_name); ?></span>
        <?php if($svc->appointment_required): ?>
          <span class="f-badge f-badge--warn"><?php echo e(__('finder_directory.appointment_required')); ?></span>
        <?php endif; ?>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </section>
  <?php endif; ?>

  
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.availability_heading')); ?>

    </h2>

    <?php if(!$hasAvail): ?>
      <p class="f-small f-muted" style="margin:0; line-height:1.6;"><?php echo e(__('finder_directory.no_availability')); ?></p>
    <?php else: ?>

      <?php if($stockRows->isNotEmpty()): ?>
        <h3 class="p-card-title" style="margin-top:6px;">
          <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 13], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php echo e(__('finder_directory.pharmacy_stock_heading')); ?>

        </h3>
        <?php $__currentLoopData = $stockRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="p-avail-item">
            <span class="p-avail-name"><?php echo e($row->medicine_name); ?></span>
            <?php if(collect([$row->strength, $row->form])->filter()->isNotEmpty()): ?>
              <span class="f-small f-muted"><?php echo e(collect([$row->strength, $row->form])->filter()->implode(' · ')); ?></span>
            <?php endif; ?>
            <span class="p-avail-sub">
              <span class="f-badge <?php echo e($statusClass($row->availability_status)); ?>"><?php echo e($statusLabel($row->availability_status)); ?></span>
              <span class="f-small f-muted"><?php echo e($reportedLine($row)); ?></span>
            </span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>

      <?php if($bloodRows->isNotEmpty()): ?>
        <h3 class="p-card-title" style="margin-top:6px;">
          <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 13], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php echo e(__('finder_directory.blood_heading')); ?>

        </h3>
        <?php $__currentLoopData = $bloodRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="p-avail-item">
            <span class="p-avail-name"><?php echo e($row->blood_group); ?></span>
            <?php if($row->component_type): ?>
              <span class="f-small f-muted"><?php echo e(ucfirst(str_replace('_', ' ', $row->component_type))); ?></span>
            <?php endif; ?>
            <span class="p-avail-sub">
              <span class="f-badge <?php echo e($statusClass($row->availability_status)); ?>"><?php echo e($statusLabel($row->availability_status)); ?></span>
              <span class="f-small f-muted"><?php echo e($reportedLine($row)); ?></span>
            </span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>

      <?php if($labRows->isNotEmpty()): ?>
        <h3 class="p-card-title" style="margin-top:6px;">
          <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 13], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php echo e(__('finder_directory.lab_tests_heading')); ?>

        </h3>
        <?php $__currentLoopData = $labRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="p-avail-item">
            <span class="p-avail-name"><?php echo e($row->test_name); ?></span>
            <?php if($row->turnaround_time): ?>
              <span class="f-small f-muted"><?php echo e(__('finder_directory.turnaround_label', ['time' => $row->turnaround_time])); ?></span>
            <?php endif; ?>
            <span class="p-avail-sub">
              <span class="f-badge <?php echo e($statusClass($row->availability_status)); ?>"><?php echo e($statusLabel($row->availability_status)); ?></span>
              <?php if($row->requires_doctor_order): ?>
                <span class="f-badge f-badge--warn"><?php echo e(__('finder_directory.requires_doctor_order')); ?></span>
              <?php endif; ?>
              <span class="f-small f-muted"><?php echo e($reportedLine($row)); ?></span>
            </span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>

    <?php endif; ?>
  </section>

  
  <?php if($hasIns): ?>
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.insurance_heading')); ?>

    </h2>
    <div class="p-chips">
      <?php $__currentLoopData = $facility->insurances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ins): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span class="f-chip" style="pointer-events:none;"><?php echo e($ins->insurance_name); ?></span>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </section>
  <?php endif; ?>

  
  <section class="f-card">
    <h2 class="p-card-title">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo e(__('finder_directory.provenance_heading')); ?>

    </h2>
    <p class="p-provenance"><?php echo e($provenance); ?></p>
    <div class="p-note" role="note">
      <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <span><?php echo e(__('finder_directory.disclaimer')); ?></span>
    </div>
    <?php if (! ($facility->isClaimed())): ?>
      <div class="p-claim">
        <span class="f-muted"><?php echo e(__('finder_directory.claim_question')); ?></span>
        <a class="p-back" href="<?php echo e(route('public.care-map.claim', array_filter(['id' => $facility->id, 'lang' => request('lang')]))); ?>">
          <?php echo e(__('finder_directory.claim_cta')); ?>

          <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </a>
      </div>
    <?php endif; ?>
  </section>

</main>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\profile.blade.php ENDPATH**/ ?>