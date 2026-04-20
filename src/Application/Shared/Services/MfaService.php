<?php

namespace CharosEMR\Application\Shared\Services;

class MfaService
{
    private string $issuer = 'Charos Dental Clinic EMR';

    /**
     * Generate a new TOTP secret for a user
     * @return string Base32-encoded secret
     */
    public function generateSecret(): string
    {
        $secret = random_bytes(20);
        return $this->base32Encode($secret);
    }

    /**
     * Generate QR code URI for Google Authenticator
     * @param string $secret TOTP secret
     * @param string $email User email
     * @return string QR code URI
     */
    public function generateQrCodeUri(string $secret, string $email): string
    {
        $encodedEmail = urlencode($email);
        $encodedIssuer = urlencode($this->issuer);
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA256&digits=6&period=30',
            $encodedIssuer,
            $encodedEmail,
            $secret,
            $encodedIssuer
        );
    }

    /**
     * Verify TOTP code
     * @param string $secret TOTP secret
     * @param string $code 6-digit code from authenticator
     * @param int $window Time window in seconds (default: 30)
     * @param int $skew Number of time windows to check before/after (default: 3 for clock drift tolerance)
     * @return bool True if code is valid
     */
    public function verifyCode(string $secret, string $code, int $window = 30, int $skew = 3): bool
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $time = time();
        $timeSteps = range($time - ($window * $skew), $time + ($window * $skew), $window);

        foreach ($timeSteps as $timeStep) {
            $expectedCode = $this->generateCode($secret, $timeStep);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code for a specific timestamp
     * @param string $secret Base32-encoded secret
     * @param int $timestamp Unix timestamp
     * @return string 6-digit code
     */
    private function generateCode(string $secret, int $timestamp): string
    {
        $secretBytes = $this->base32Decode($secret);
        $timeBytes = pack('N*', $timestamp / 30);
        $timeBytes = substr($timeBytes, -4);

        $hash = hash_hmac('sha256', $timeBytes, $secretBytes, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $code = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Encode binary data to Base32
     * @param string $data Binary data
     * @return string Base32-encoded string
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $bits = 0;
        $buffer = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $output .= $alphabet[($buffer >> ($bits - 5)) & 0x1F];
                $bits -= 5;
            }
        }

        if ($bits > 0) {
            $output .= $alphabet[($buffer << (5 - $bits)) & 0x1F];
        }

        return $output;
    }

    /**
     * Decode Base32 string to binary data
     * @param string $data Base32-encoded string
     * @return string Binary data
     */
    private function base32Decode(string $data): string
    {
        $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data));
        $output = '';
        $bits = 0;
        $buffer = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 5) | $alphabet[$data[$i]];
            $bits += 5;

            if ($bits >= 8) {
                $output .= chr(($buffer >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $output;
    }

    /**
     * Generate backup codes for MFA recovery
     * @param int $count Number of backup codes to generate
     * @return array Array of backup codes
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = bin2hex(random_bytes(4));
            $codes[] = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        }
        return $codes;
    }

    /**
     * Validate backup code format
     * @param string $code Backup code to validate
     * @return bool True if format is valid
     */
    public function validateBackupCodeFormat(string $code): bool
    {
        return preg_match('/^[a-f0-9]{4}-[a-f0-9]{4}$/i', $code) === 1;
    }
}
