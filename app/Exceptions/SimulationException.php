<?php

namespace App\Exceptions;

use Exception;
use App\Http\Responses\ApiResponse;
use App\Enums\ResponseStatusEnum;
use Illuminate\Http\JsonResponse;

class SimulationException extends Exception
{
    public function __construct(
        string $message = 'Simulation operation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            status: ResponseStatusEnum::INTERNAL_SERVER_ERROR
        );
    }
}