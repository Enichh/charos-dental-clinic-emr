<?php

namespace CharosEMR\Presentation\Http\Middlewares;

class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = ['admin', 'dentist'])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(callable $next): void
    {
        // Get user role from token/session
        $userRole = $_SERVER['HTTP_X_USER_ROLE'] ?? '';

        if (!in_array($userRole, $this->allowedRoles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $next();
    }
}
