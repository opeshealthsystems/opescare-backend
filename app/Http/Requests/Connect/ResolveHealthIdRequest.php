<?php

namespace App\Http\Requests\Connect;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Health ID Resolution — Form Request
 *
 * Validates the "find or create" resolution endpoint used by external HIS systems.
 * Caller must supply either a Health ID or full demographics (name + DOB).
 * Auto-creation requires explicit consent_acknowledged = true.
 */
class ResolveHealthIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth.bearer middleware handles bearer token validation
    }

    public function rules(): array
    {
        return [
            'health_id'            => ['nullable', 'string', 'max:30'],
            'first_name'           => ['nullable', 'string', 'max:100'],
            'last_name'            => ['nullable', 'string', 'max:100'],
            'date_of_birth'        => ['nullable', 'date_format:Y-m-d'],
            'country_code'         => ['nullable', 'string', 'size:2'],
            'sex'                  => ['nullable', 'string', 'in:male,female'],
            'phone_number'         => ['nullable', 'string', 'max:30'],
            'purpose'              => ['required', 'string', 'max:100'],
            'external_reference'   => ['nullable', 'string', 'max:200'],
            'consent_acknowledged' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'purpose.required'    => 'The access purpose is required for audit compliance.',
            'date_of_birth.date_format' => 'Date of birth must be in YYYY-MM-DD format.',
            'country_code.size'   => 'Country code must be exactly 2 characters (e.g. CM).',
            'sex.in'              => 'Sex must be one of: male, female, other, unknown.',
        ];
    }

    /**
     * After base validation passes, enforce the "at least one lookup criterion" rule.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $hasHealthId     = filled($this->input('health_id'));
            $hasDemographics = filled($this->input('first_name'))
                && filled($this->input('last_name'))
                && filled($this->input('date_of_birth'));

            if (! $hasHealthId && ! $hasDemographics) {
                $v->errors()->add('lookup', 'Provide either health_id OR (first_name, last_name, date_of_birth).');
            }
        });
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): never
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'status'     => 'error',
            'error_code' => 'INVALID_PAYLOAD',
            'message'    => $validator->errors()->first(),
            'errors'     => $validator->errors()->toArray(),
        ], 422));
    }
}
