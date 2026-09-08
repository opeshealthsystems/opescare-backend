<?php

namespace App\Enums;

/**
 * How far a pre-consult questionnaire has got.
 *
 * Only two states, on purpose. There is no `expired`, no `overdue` and no
 * `missed`: those words describe a patient who has failed at something, and a
 * patient who does not answer this has failed at nothing. An appointment with
 * no submission at all is the normal case, not an exception state.
 *
 * Compared with match()/->value per platform law — never `=== 'draft'`.
 */
enum PreconsultStatus: string
{
    /** Started and saved, still the patient's to change. */
    case Draft = 'draft';

    /** Handed to the facility. Answers are frozen; the patient can still read them. */
    case Submitted = 'submitted';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** May the patient still edit the answers? */
    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft     => true,
            self::Submitted => false,
        };
    }

    /** Is this visible to the clinician on the chart? */
    public function isVisibleToClinician(): bool
    {
        return match ($this) {
            self::Draft     => false,
            self::Submitted => true,
        };
    }

    /** Translation key for the badge. Resolved through lang/{locale}/preconsult.php. */
    public function labelKey(): string
    {
        return 'preconsult.status.' . $this->value;
    }
}
