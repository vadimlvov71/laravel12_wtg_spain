<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchPropertiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city' => 'required|string|max:255',
            'check_in' => 'required|date_format:Y-m-d',
            'check_out' => 'required|date_format:Y-m-d|after:check_in',
            'guests' => 'required|integer|min:1|max:20',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'city.required' => 'Город обязателен',
            'check_in.required' => 'Дата заезда обязательна',
            'check_in.date_format' => 'Дата заезда должна быть в формате Y-m-d',
            'check_out.required' => 'Дата выезда обязательна',
            'check_out.date_format' => 'Дата выезда должна быть в формате Y-m-d',
            'check_out.after' => 'Дата выезда должна быть позже даты заезда',
            'guests.required' => 'Количество гостей обязательно',
            'guests.min' => 'Количество гостей должно быть минимум 1',
            'guests.max' => 'Количество гостей не может быть больше 20',
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
