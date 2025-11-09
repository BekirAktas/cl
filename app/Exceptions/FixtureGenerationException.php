<?php

namespace App\Exceptions;

use Exception;
use App\Http\Responses\ApiResponse;
use App\Enums\ResponseStatusEnum;
use Illuminate\Http\JsonResponse;

class FixtureGenerationException extends Exception
{
    public function __construct(
        string $message = 'Failed to generate fixtures',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            status: ResponseStatusEnum::BAD_REQUEST
        );
    }
}