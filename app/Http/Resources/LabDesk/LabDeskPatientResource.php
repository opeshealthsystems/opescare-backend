<?php

namespace App\Http\Resources\LabDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The patient block on a lab desk row — who the sample belongs to.
 *
 * `brief()` drops dob / sex for lists that do not need them (test requests):
 * the desk discloses what the task in hand requires and nothing more.
 */
class LabDeskPatientResource extends ApiResource
{
    private bool $brief = false;

    public function brief(): static
    {
        $this->brief = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $row = [
            'id'           => $this->id,
            'health_id'    => $this->health_id,
            'display_name' => trim(((string) $this->first_name) . ' ' . ((string) $this->last_name)),
        ];

        if ($this->brief) {
            return $row;
        }

        return $row + [
            'dob' => $this->dateOfBirth(),
            'sex' => $this->sex,
        ];
    }

    /**
     * `patients.date_of_birth` is encrypted; a row written under another
     * APP_KEY throws on read, and that is a missing date, not a failed list.
     */
    private function dateOfBirth(): ?string
    {
        try {
            $dob = $this->resource->date_of_birth;
        } catch (\Throwable) {
            return null;
        }

        return $dob ? $dob->toDateString() : null;
    }
}
