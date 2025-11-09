<?php

namespace App\Exceptions;

use Exception;
use App\Http\Responses\ApiResponse;
use App\Enums\ResponseStatusEnum;
use Illuminate\Http\JsonResponse;

class RedisConnectionException extends Exception
{
    public function __construct(
        string $message = 'Redis connection failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Service temporarily unavailable. Please try again later.',
            status: ResponseStatusEnum::SERVICE_UNAVAILABLE
        );
    }
}