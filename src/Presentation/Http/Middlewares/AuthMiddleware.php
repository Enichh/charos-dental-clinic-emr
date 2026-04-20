<?php

namespace CharosEMR\Presentation\Http\Middlewares;

class AuthMiddleware
{
    public function handle(callable $next): void
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($token)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Validate token logic here
        $next();
    }
}
