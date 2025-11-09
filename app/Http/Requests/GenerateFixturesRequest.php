<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;

class GenerateFixturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teams_min_count' => 'sometimes|integer|min:2',
            'force_regenerate' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'teams_min_count.min' => 'At least 2 teams required to generate fixtures.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors())
        );
    }
}
