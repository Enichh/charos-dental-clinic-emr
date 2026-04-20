<?php

namespace CharosEMR\Application\Shared\Services;

class CsrfProtectionService
{
    /**
     * Generate or return existing CSRF token from session
     * @return string 64-character hexadecimal CSRF token
     */
    public function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (\Exception $e) {
                error_log("Failed to generate CSRF token: " . $e->getMessage());
                // Fallback to less secure method if random_bytes fails
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token against session token using timing-safe comparison
     * @param string $token Token to validate
     * @return bool True if token matches, false otherwise
     */
    public function validateToken(string $token): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerate CSRF token (use after sensitive operations)
     * @return string New 64-character hexadecimal CSRF token
     */
    public function regenerateToken(): string
    {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            error_log("Failed to regenerate CSRF token: " . $e->getMessage());
            // Fallback to less secure method if random_bytes fails
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
