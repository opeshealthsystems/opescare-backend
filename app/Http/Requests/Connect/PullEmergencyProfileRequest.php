<?php

namespace App\Http\Requests\Connect;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Emergency Profile Pull — Form Request
 *
 * Validates the B2B emergency access request.
 * MINSANTE Law No. 2010/012: emergency access must always include a stated reason
 * of at least 10 characters so the audit trail is meaningful.
 */
class PullEmergencyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled upstream by auth.bearer middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'health_id' => ['required', 'string', 'max:30'],
            'reason'    => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'health_id.required' => 'A Health ID is required for emergency profile access.',
            'reason.required'    => 'An emergency reason is required by Cameroon Law No. 2010/012.',
            'reason.min'         => 'The emergency reason must be at least 10 characters so it is meaningful for audit.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): never
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'status'     => 'invalid',
            'error_code' => 'INVALID_PAYLOAD',
            'message'    => $validator->errors()->first(),
            'errors'     => $validator->errors()->toArray(),
        ], 400));
    }
}
