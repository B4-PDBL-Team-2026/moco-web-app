<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;

abstract class Controller
{
    protected function successResponse(mixed $data = null, string $message = 'Success', int $status = 200): ApiResponse
    {
        return ApiResponse::success(data: $data, message: $message, status: $status);
    }

    protected function errorResponse(string $message = 'Error', int $status = 400, mixed $errors = null): ApiResponse
    {
        return ApiResponse::error(errors: $errors, message: $message, status: $status);
    }
}
