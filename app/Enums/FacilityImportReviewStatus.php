<?php

namespace App\Enums;

/**
 * What a human decided about an import candidate the machine refused to act on.
 *
 * DB column: facility_import_reviews.status (string, backed value) — the
 * lifecycle OsmFacilityImporter parks candidates in when a match is uncertain,
 * a name is generic or missing, or a type conflicts.
 *
 *   pending → imported   (it is a real, distinct facility — add it)
 *           → merged     (it is the matched listing under another name)
 *           → rejected   (it is not a facility, or not one we list)
 *           → deferred   (this one needs something the reviewer does not have
 *                         yet — a phone call, a site visit, a colleague)
 *
 * `deferred` is NOT a decision. It exists so a reviewer working a 439-row
 * queue can clear the ones they cannot settle today without either guessing or
 * leaving them to block the queue forever. A deferred row keeps its place in
 * the lifecycle: it can still become imported, merged or rejected. Only those
 * three are terminal, which is why `isOpen()` and not `isPending()` is what the
 * service guards on.
 */
enum FacilityImportReviewStatus: string
{
    case Pending  = 'pending';
    case Deferred = 'deferred';
    case Imported = 'imported';
    case Merged   = 'merged';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Awaiting decision',
            self::Deferred => 'Deferred',
            self::Imported => 'Added to directory',
            self::Merged   => 'Merged into existing listing',
            self::Rejected => 'Rejected',
        };
    }

    /** The i18n key under the facility_review namespace. */
    public function translationKey(): string
    {
        return 'facility_review.status_' . $this->value;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /** Still awaiting a human decision — pending or parked, but not settled. */
    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Deferred;
    }

    /** Terminal. A decided row is never re-decided and the importer skips it. */
    public function isDecided(): bool
    {
        return ! $this->isOpen();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
