<?php

namespace App\Http\Requests\Connect;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Consent Request — Form Request
 *
 * Validates the B2B consent initiation endpoint.
 * Scope items are individually validated (string, max 80 chars each) to prevent
 * injection of arbitrary values into the consent record.
 */
class RequestConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'health_id'        => ['required', 'string', 'max:30'],
            'purpose'          => ['required', 'string', 'max:200'],
            'requested_scope'  => ['required', 'array', 'min:1', 'max:20'],
            'requested_scope.*'=> ['required', 'string', 'max:80'],
            'expires_in_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'health_id.required'       => 'Patient Health ID is required.',
            'purpose.required'         => 'Access purpose is required.',
            'requested_scope.required' => 'At least one access scope must be requested.',
            'requested_scope.array'    => 'requested_scope must be an array of scope strings.',
            'requested_scope.*.string' => 'Each scope item must be a string.',
            'requested_scope.*.max'    => 'Each scope item must not exceed 80 characters.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): never
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'status'     => 'invalid',
            'error_code' => 'INVALID_PAYLOAD',
            'message'    => $validator->errors()->first(),
            'errors'     => $validator->errors()->toArray(),
        ], 422));
    }
}
