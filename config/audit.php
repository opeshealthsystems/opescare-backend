<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Archive Storage
    |--------------------------------------------------------------------------
    |
    | Disk that ArchiveAuditLogs writes cold audit archives to. In production
    | this MUST point at an immutable, write-once store — e.g. an S3 bucket with
    | Object Lock (WORM) + versioning enabled — so archived audit records cannot
    | be altered or deleted within the legal retention window (7 years, per
    | Cameroon Law No. 2010/012 Art. 28). Locally it defaults to 'local'.
    |
    | Each monthly archive object is written ONCE (never appended/mutated) and
    | accompanied by a SHA-256 digest sidecar so tampering is detectable.
    |
    */

    'archive_disk' => env('AUDIT_ARCHIVE_DISK', 'local'),

    /*
    | Hot-table retention in months before rows are archived and purged.
    */
    'retention_months' => (int) env('AUDIT_RETENTION_MONTHS', 12),

];
