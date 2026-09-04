<?php
// app/Http/Requests/StoreImportRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier' => 'required|string|exists:suppliers,code',
            'external_import_id' => 'required|string|max:255',
            'sent_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
            'offers' => 'required|array|min:1',
            'offers.*.external_id' => 'required|string|max:255',
            'offers.*.property' => 'required|array',
            'offers.*.property.code' => 'required|string|max:255',
            'offers.*.property.name' => 'required|string|max:255',
            'offers.*.property.city' => 'required|string|max:255',
            'offers.*.check_in' => 'required|date_format:Y-m-d',
            'offers.*.check_out' => 'required|date_format:Y-m-d|after:offers.*.check_in',
            'offers.*.max_guests' => 'required|integer|min:1',
            'offers.*.price' => 'required|integer|min:0',
            'offers.*.currency' => 'required|string|size:3',
            'offers.*.available_units' => 'required|integer|min:0',
            'offers.*.expires_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier.exists' => 'supplier doesnt exist',
            'offers.*.property.code.required' => 'property code is required',
            'offers.*.check_out.after' => 'the date of check_out have to be',
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