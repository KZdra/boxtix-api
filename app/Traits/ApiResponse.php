<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Return a success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message, $statusCode = 500)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $statusCode);
    }

    /**
     * Return a custom response.
     *
     * @param bool $success
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function customResponse($success, $data, $message, $statusCode)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}