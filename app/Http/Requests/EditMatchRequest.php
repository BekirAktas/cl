<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;

class EditMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week' => 'required|integer|min:1',
            'match_index' => 'required|integer|min:0',
            'home_score' => 'required|integer|min:0|max:20',
            'away_score' => 'required|integer|min:0|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'week.required' => 'Week number is required',
            'week.min' => 'Week must be at least 1',
            'match_index.required' => 'Match index is required',
            'home_score.required' => 'Home team score is required',
            'home_score.max' => 'Score cannot exceed 20',
            'away_score.required' => 'Away team score is required',
            'away_score.max' => 'Score cannot exceed 20',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors())
        );
    }
}