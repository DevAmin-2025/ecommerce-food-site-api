<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * Display a success json response.
     */
    protected function successResponse(
        $data,
        string $message = '',
        int $code = 200
    ): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Display an error json response.
     */
    protected function errorResponse(
        string|array $message,
        $data = null,
        int $code = 404
    ): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'data' => $data,
            'message' => $message,
        ], $code);
    }
}
