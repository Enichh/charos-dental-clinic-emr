<?php

namespace CharosEMR\Presentation\Http\Middlewares;

use CharosEMR\Application\Shared\Services\CsrfProtectionService;

class AuthMiddleware
{
    public function __construct(
        private CsrfProtectionService $csrfService,
        private ?string $requiredRole = null,
        private bool $requireCsrf = false
    ) {}

    public function handle(callable $next): void
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($token)) {
            // Fallback to session-based authentication
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            // Check role-based access
            if ($this->requiredRole && $_SESSION['user_role'] !== $this->requiredRole) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden - insufficient permissions']);
                exit;
            }

            // Validate CSRF token for state-changing requests
            if ($this->requireCsrf && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                $input = file_get_contents('php://input');
                $parsedInput = json_decode($input, true) ?? [];
                $csrfToken = $parsedInput['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

                // Rewind input stream for controller to read
                file_put_contents('php://input', $input);

                if (!$this->csrfService->validateToken($csrfToken)) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                }
            }
        } else {
            // Token-based authentication (for API clients)
            // TODO: Implement JWT validation when token-based auth is needed
            // For now, we'll accept the token format
            if (!str_starts_with($token, 'Bearer ')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid token format']);
                exit;
            }
        }

        $next();
    }
}
