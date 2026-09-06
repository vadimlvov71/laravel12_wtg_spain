<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_reference' => 'required|string|max:255|unique:reservations',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'client_reference.required' => 'Client reference is required',
            'client_reference.unique' => 'This client reference already exists',
            'customer_name.required' => 'Customer name is required',
            'customer_email.required' => 'Customer email is required',
            'customer_email.email' => 'Customer email must be a valid email',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
