<?php

namespace CharosEMR\Presentation\Http\Responses;

class JsonResponseFormatter
{
    public static function success($data = null, string $message = 'Success', int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);

        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }

    public static function error(string $message = 'Error', int $status = 400, $errors = null): void
    {
        header('Content-Type: application/json');
        http_response_code($status);

        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }
}
