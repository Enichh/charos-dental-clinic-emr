<?php

namespace CharosEMR\Application\Shared\Services;

class DataEncryption
{
    private string $key;
    private string $method = 'AES-256-CBC';

    public function __construct()
    {
        $this->key = $_ENV['ENCRYPTION_KEY'] ?? $this->generateDefaultKey();
    }

    private function generateDefaultKey(): string
    {
        $keyFile = dirname(__DIR__, 4) . '/config/encryption.key';

        if (file_exists($keyFile)) {
            return trim(file_get_contents($keyFile));
        }

        $key = bin2hex(random_bytes(32));
        $this->ensureStorageDirectory();
        file_put_contents($keyFile, $key, 0600);

        return $key;
    }

    private function ensureStorageDirectory(): void
    {
        $dir = dirname(__DIR__, 4) . '/config';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Encrypt sensitive data using AES-256-CBC
     * @param string $data Data to encrypt
     * @return string Base64-encoded encrypted data with IV
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $ivLength = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->method, $this->key, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     * @param string $encryptedData Base64-encoded encrypted data with IV
     * @return string Decrypted data
     */
    public function decrypt(string $encryptedData): string
    {
        if (empty($encryptedData)) {
            return '';
        }

        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length($this->method);

        if (strlen($data) < $ivLength) {
            throw new \RuntimeException('Invalid encrypted data');
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $this->method, $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Encrypt array of fields
     * @param array $data Data with fields to encrypt
     * @param array $fields Field names to encrypt
     * @return array Data with encrypted fields
     */
    public function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Decrypt array of fields
     * @param array $data Data with fields to decrypt
     * @param array $fields Field names to decrypt
     * @return array Data with decrypted fields
     */
    public function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                try {
                    $data[$field] = $this->decrypt($data[$field]);
                } catch (\RuntimeException $e) {
                    error_log("Decryption failed for field {$field}: " . $e->getMessage());
                }
            }
        }
        return $data;
    }

    /**
     * Hash data for integrity verification
     * @param string $data Data to hash
     * @return string HMAC-SHA256 hash
     */
    public function hash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->key);
    }

    /**
     * Verify data integrity
     * @param string $data Original data
     * @param string $hash Hash to verify against
     * @return bool True if hash matches
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($this->hash($data), $hash);
    }
}
