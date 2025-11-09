<?php

namespace App\Http\Responses;

use App\Enums\ResponseStatusEnum;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null, 
        string $message = 'Success', 
        ResponseStatusEnum $status = ResponseStatusEnum::OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status->value);
    }

    public static function error(
        string $message = 'An error occurred',
        mixed $errors = null,
        ResponseStatusEnum $status = ResponseStatusEnum::BAD_REQUEST
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status->value);
    }

    public static function noContent(string $message = 'No content'): JsonResponse
    {
        return self::success(
            data: null,
            message: $message,
            status: ResponseStatusEnum::NO_CONTENT
        );
    }

    public static function created(mixed $data, string $message = 'Created successfully'): JsonResponse
    {
        return self::success(
            data: $data,
            message: $message,
            status: ResponseStatusEnum::CREATED
        );
    }

    public static function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error(
            message: $message,
            errors: $errors,
            status: ResponseStatusEnum::UNPROCESSABLE_ENTITY
        );
    }
}