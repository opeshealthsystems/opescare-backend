<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Record — <?php echo e($patient->first_name); ?> <?php echo e($patient->last_name); ?></title>
    <style>
        body  { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; padding: 20px; }
        h1    { font-size: 18px; color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 6px; }
        h2    { font-size: 13px; color: #2563eb; margin-top: 18px; margin-bottom: 4px; border-left: 4px solid #2563eb; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th    { background: #e8f0fe; text-align: left; padding: 5px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
        td    { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .label { color: #6b7280; font-weight: 600; width: 140px; }
        .badge-active   { color: #065f46; background: #d1fae5; padding: 1px 6px; border-radius: 999px; }
        .badge-inactive { color: #7f1d1d; background: #fee2e2; padding: 1px 6px; border-radius: 999px; }
        .footer { margin-top: 30px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>

<h1>OpesCare — Medical Record</h1>

<h2>Patient Demographics</h2>
<table>
    <tr><td class="label">Full Name</td><td><?php echo e($patient->first_name); ?> <?php echo e($patient->last_name); ?></td></tr>
    <tr><td class="label">Health ID</td><td><?php echo e($patient->health_id ?? $patient->id); ?></td></tr>
    <tr><td class="label">Date of Birth</td><td><?php echo e($patient->date_of_birth?->format('d M Y') ?? '—'); ?></td></tr>
    <tr><td class="label">Gender</td><td><?php echo e(ucfirst($patient->sex ?? '—')); ?></td></tr>
    <tr><td class="label">Phone</td><td><?php echo e($patient->phone_number ?? '—'); ?></td></tr>
    <tr><td class="label">Record Generated</td><td><?php echo e(now()->format('d M Y H:i')); ?></td></tr>
</table>

<?php if($patient->allergies && $patient->allergies->isNotEmpty()): ?>
<h2>Allergies</h2>
<table>
    <tr><th>Allergen</th><th>Reaction</th><th>Severity</th></tr>
    <?php $__currentLoopData = $patient->allergies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allergy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($allergy->substance ?? $allergy->allergen ?? $allergy->name ?? '—'); ?></td>
        <td><?php echo e($allergy->reaction ?? '—'); ?></td>
        <td><?php echo e(ucfirst($allergy->severity ?? '—')); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php endif; ?>

<?php if($options['include_diagnoses'] && $patient->diagnoses && $patient->diagnoses->isNotEmpty()): ?>
<h2>Active Diagnoses</h2>
<table>
    <tr><th>Diagnosis</th><th>Code</th><th>Status</th></tr>
    <?php $__currentLoopData = $patient->diagnoses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($dx->display_name ?? $dx->code ?? '—'); ?></td>
        <td><?php echo e($dx->code ?? '—'); ?></td>
        <td><span class="badge-active"><?php echo e(ucfirst($dx->status ?? 'active')); ?></span></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php endif; ?>

<?php if($options['include_medications'] && $patient->prescriptions && $patient->prescriptions->isNotEmpty()): ?>
<h2>Current Medications</h2>
<table>
    <tr><th>Prescription</th><th>Status</th><th>Notes</th></tr>
    <?php $__currentLoopData = $patient->prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($rx->id); ?></td>
        <td><?php echo e(ucfirst($rx->status ?? 'active')); ?></td>
        <td><?php echo e($rx->notes ?? '—'); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php endif; ?>

<?php if($options['include_vitals'] && $patient->vitals && $patient->vitals->isNotEmpty()): ?>
<h2>Recent Vitals (Last 3 Records)</h2>
<table>
    <tr><th>Date</th><th>BP</th><th>Pulse</th><th>Temp (°C)</th><th>SpO2 (%)</th><th>Weight (kg)</th></tr>
    <?php $__currentLoopData = $patient->vitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($v->recorded_at?->format('d M Y') ?? '—'); ?></td>
        <td><?php echo e(isset($v->systolic_bp, $v->diastolic_bp) ? "{$v->systolic_bp}/{$v->diastolic_bp}" : '—'); ?></td>
        <td><?php echo e($v->pulse_rate ?? '—'); ?></td>
        <td><?php echo e($v->temperature ?? '—'); ?></td>
        <td><?php echo e($v->oxygen_saturation ?? '—'); ?></td>
        <td><?php echo e($v->weight_kg ?? '—'); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php endif; ?>

<?php if($options['include_labs'] && $patient->labResults && $patient->labResults->isNotEmpty()): ?>
<h2>Recent Lab Results (Last 10)</h2>
<table>
    <tr><th>Test</th><th>Result</th><th>Unit</th><th>Reference</th><th>Date</th><th>Flag</th></tr>
    <?php $__currentLoopData = $patient->labResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($lr->parameter_name ?? $lr->test_name ?? '—'); ?></td>
        <td><?php echo e($lr->value ?? $lr->result_value ?? '—'); ?></td>
        <td><?php echo e($lr->unit ?? $lr->result_unit ?? '—'); ?></td>
        <td><?php echo e($lr->reference_range ?? '—'); ?></td>
        <td><?php echo e(($lr->resulted_at ?? $lr->collected_at)?->format('d M Y') ?? '—'); ?></td>
        <td><?php echo e($lr->flag ?? $lr->abnormal_flag ?? ''); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php endif; ?>

<?php if($options['include_immunizations'] && $patient->immunizations && $patient->immunizations->isNotEmpty()): ?>
<h2>Immunization History</h2>
<table>
    <tr><th>Vaccine</th><th>Date Given</th><th>Dose</th><th>Lot Number</th></tr>
    <?php $__currentLoopData = $patient->immunizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($imm->vaccine_name ?? '—'); ?></td>
        <td><?php echo e($imm->administered_at?->format('d M Y') ?? '—'); ?></td>
        <td><?php echo e($imm->dose_number ?? '—'); ?></td>
        <td><?php echo e($imm->lot_number ?? '—'); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php endif; ?>

<div class="footer">
    This document is confidential and intended solely for the named patient.<br>
    Generated by OpesCare &mdash; <?php echo e(now()->format('d M Y H:i:s')); ?>

</div>

</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\exports\medical-record.blade.php ENDPATH**/ ?>