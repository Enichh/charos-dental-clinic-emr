<?php

namespace CharosEMR\Application\Shared\Services;

class RateLimiter
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = __DIR__ . '/../../../../storage/rate_limit';
        $this->ensureStorageDirectory();
    }

    private function ensureStorageDirectory(): void
    {
        if (!is_dir($this->storagePath)) {
            if (!mkdir($this->storagePath, 0755, true)) {
                error_log("Failed to create rate limit directory: " . $this->storagePath);
            }
        }
    }

    private function getStorageFile(string $identifier): string
    {
        // Hash with secret to prevent identifier enumeration
        $secret = $_ENV['RATE_LIMIT_SECRET'] ?? 'default-secret-change-in-production';
        return $this->storagePath . '/' . md5($secret . $identifier) . '.json';
    }

    private function loadAttempts(string $identifier): array
    {
        $file = $this->getStorageFile($identifier);
        if (!file_exists($file)) {
            return [];
        }

        $fp = fopen($file, 'r');
        if (!$fp) {
            error_log("Failed to open rate limit file for reading: " . $file);
            return [];
        }

        if (flock($fp, LOCK_SH)) {
            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            $data = json_decode($content, true);
            return $data['attempts'] ?? [];
        }

        fclose($fp);
        return [];
    }

    private function saveAttempts(string $identifier, array $attempts): void
    {
        $file = $this->getStorageFile($identifier);
        $data = [
            'attempts' => $attempts,
            'last_updated' => time()
        ];

        $fp = fopen($file, 'c');
        if (!$fp) {
            error_log("Failed to open rate limit file for writing: " . $file);
            return;
        }

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data));
            flock($fp, LOCK_UN);
        }

        fclose($fp);
    }

    private function cleanupOldAttempts(array $attempts, int $window): array
    {
        $cutoff = time() - $window;
        return array_filter($attempts, fn($timestamp) => $timestamp > $cutoff);
    }

    /**
     * Check if rate limit allows the request
     * @param string $identifier Unique identifier for rate limiting (e.g., email, IP)
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $window Time window in seconds
     * @return bool True if request is allowed, false if limit exceeded
     */
    public function checkLimit(string $identifier, int $maxAttempts = 5, int $window = 300): bool
    {
        $attempts = $this->loadAttempts($identifier);
        $attempts = $this->cleanupOldAttempts($attempts, $window);

        if (count($attempts) >= $maxAttempts) {
            return false;
        }

        $attempts[] = time();
        $this->saveAttempts($identifier, $attempts);
        return true;
    }

    /**
     * Get remaining attempts before rate limit is reached
     * @param string $identifier Unique identifier
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $window Time window in seconds
     * @return int Number of remaining attempts
     */
    public function getRemainingAttempts(string $identifier, int $maxAttempts = 5, int $window = 300): int
    {
        $attempts = $this->loadAttempts($identifier);
        $attempts = $this->cleanupOldAttempts($attempts, $window);
        return max(0, $maxAttempts - count($attempts));
    }

    /**
     * Get seconds until rate limit resets
     * @param string $identifier Unique identifier
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $window Time window in seconds
     * @return int|null Seconds until reset, or null if not rate limited
     */
    public function getRetryAfter(string $identifier, int $maxAttempts = 5, int $window = 300): ?int
    {
        $attempts = $this->loadAttempts($identifier);
        $attempts = $this->cleanupOldAttempts($attempts, $window);

        if (count($attempts) < $maxAttempts) {
            return null;
        }

        $oldestAttempt = min($attempts);
        $retryAfter = $oldestAttempt + $window - time();
        return max(0, $retryAfter);
    }

    /**
     * Reset rate limit for identifier
     * @param string $identifier Unique identifier
     */
    public function reset(string $identifier): void
    {
        $file = $this->getStorageFile($identifier);
        if (file_exists($file)) {
            if (!unlink($file)) {
                error_log("Failed to delete rate limit file: " . $file);
            }
        }
    }
}
