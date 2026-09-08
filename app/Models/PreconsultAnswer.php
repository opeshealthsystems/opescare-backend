<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer to one pre-consult question.
 *
 * PROVENANCE IS THE POINT. `provenance` is `patient_reported` on every row and
 * this model offers no way to write anything else — it is not fillable, and
 * the constant below is the only value the service passes. A reader that
 * selects this row cannot end up holding the answer without also holding the
 * fact that a patient typed it.
 *
 * The question TEXT is not here. Only `question_key`, which resolves against
 * config/preconsult.php and lang/{en,fr}/preconsult.php, so the same stored
 * answer renders in the reader's own language.
 */
class PreconsultAnswer extends Model
{
    use HasUuids;

    /** The only provenance a row in this table may ever carry. */
    public const PROVENANCE = 'patient_reported';

    protected $table = 'preconsult_answers';

    /** `provenance` is intentionally NOT fillable. */
    protected $fillable = [
        'submission_id',
        'question_key',
        'value_text',
        'value_choice',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(PreconsultSubmission::class, 'submission_id');
    }

    /** The answer as given, whichever column holds it. Null means unanswered. */
    public function value(): ?string
    {
        return $this->value_choice ?? $this->value_text;
    }
}
