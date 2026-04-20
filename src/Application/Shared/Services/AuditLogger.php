<?php

namespace CharosEMR\Application\Shared\Services;

use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use CharosEMR\Domain\Shared\Repositories\AuditLogRepositoryInterface;
use CharosEMR\Domain\Shared\Entities\AuditLog;

class AuditLogger
{
    private array $sensitiveFields = ['patient_data', 'medical_history', 'diagnosis', 'treatment', 'prescription', 'ssn', 'credit_card'];

    public function __construct(
        private LoggerInterface $logger,
        private AuditLogRepositoryInterface $auditLogRepository,
        private ?DataEncryption $encryption = null
    ) {}

    /**
     * Log an audit event with HIPAA-compliant metadata
     * @param string $action Action being performed
     * @param string|null $userId User ID (falls back to session)
     * @param array $details Additional context
     * @param bool $success Whether the action succeeded
     */
    public function log(string $action, ?string $userId = null, array $details = [], bool $success = true): void
    {
        $sessionUserId = isset($_SESSION) ? ($_SESSION['user_id'] ?? null) : null;
        $sessionEmail = isset($_SESSION) ? ($_SESSION['user_email'] ?? null) : null;
        $sessionRole = isset($_SESSION) ? ($_SESSION['user_role'] ?? null) : null;

        // Encrypt sensitive details before storage
        $sanitizedDetails = $this->sanitizeDetails($details);

        $auditLog = new AuditLog(
            null,
            new \DateTime(),
            $userId ?? $sessionUserId,
            $sessionEmail,
            $sessionRole,
            $action,
            $details['resource_type'] ?? null,
            $details['resource_id'] ?? null,
            isset($_SERVER) ? ($_SERVER['REMOTE_ADDR'] ?? 'CLI') : 'CLI',
            isset($_SERVER) ? ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') : 'CLI',
            $sanitizedDetails,
            $success
        );

        $this->auditLogRepository->save($auditLog);

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId ?? $sessionUserId,
            'action' => $action,
            'ip_address' => isset($_SERVER) ? ($_SERVER['REMOTE_ADDR'] ?? 'CLI') : 'CLI',
            'user_agent' => isset($_SERVER) ? ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') : 'CLI',
            'details' => $sanitizedDetails,
            'success' => $success
        ];

        $this->logger->info('AUDIT: ' . $action, $logEntry);
    }

    /**
     * Sanitize details by encrypting sensitive fields
     * @param array $details Original details
     * @return array Sanitized details with sensitive data encrypted
     */
    private function sanitizeDetails(array $details): array
    {
        if ($this->encryption === null) {
            return $details;
        }

        $sanitized = [];
        foreach ($details as $key => $value) {
            if (in_array(strtolower($key), $this->sensitiveFields) && is_string($value)) {
                try {
                    $sanitized[$key] = $this->encryption->encrypt($value);
                } catch (\Exception $e) {
                    error_log("Failed to encrypt sensitive field {$key}: " . $e->getMessage());
                    $sanitized[$key] = '[ENCRYPTED]';
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log login attempt for audit trail
     * @param string $userId User ID
     * @param bool $success Whether login succeeded
     */
    public function logLogin(string $userId, bool $success): void
    {
        $this->log('LOGIN_ATTEMPT', $userId, [
            'success' => $success
        ], $success);
    }

    /**
     * Log logout event for audit trail
     * @param string $userId User ID
     */
    public function logLogout(string $userId): void
    {
        $this->log('LOGOUT', $userId, [], true);
    }

    /**
     * Log data access event for HIPAA compliance
     * @param string $userId User ID accessing data
     * @param string $resourceType Type of resource accessed
     * @param string $resourceId ID of resource accessed
     */
    public function logDataAccess(string $userId, string $resourceType, string $resourceId): void
    {
        $this->log('DATA_ACCESS', $userId, [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId
        ], true);
    }

    /**
     * Log data modification event for HIPAA compliance
     * @param string $userId User ID modifying data
     * @param string $resourceType Type of resource modified
     * @param string $resourceId ID of resource modified
     * @param array $changes Changes made to resource
     */
    public function logDataModification(string $userId, string $resourceType, string $resourceId, array $changes): void
    {
        $this->log('DATA_MODIFICATION', $userId, [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changes' => $changes
        ], true);
    }

    /**
     * Log unauthorized access attempt
     * @param string $resourceType Type of resource accessed
     * @param string $resourceId ID of resource accessed
     */
    public function logUnauthorizedAccess(string $resourceType, string $resourceId): void
    {
        $this->log('UNAUTHORIZED_ACCESS', null, [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId
        ], false);
    }

    /**
     * Log MFA setup
     * @param string $userId User ID
     */
    public function logMfaSetup(string $userId): void
    {
        $this->log('MFA_SETUP', $userId, [], true);
    }

    /**
     * Log MFA verification
     * @param string $userId User ID
     * @param bool $success Whether verification succeeded
     */
    public function logMfaVerification(string $userId, bool $success): void
    {
        $this->log('MFA_VERIFICATION', $userId, [
            'success' => $success
        ], $success);
    }

    /**
     * Clean up old audit logs (6-year retention per HIPAA)
     * Should be called by a scheduled job
     */
    public function cleanupOldLogs(): int
    {
        $cutoffDate = new \DateTime('-6 years');
        return $this->auditLogRepository->deleteOldLogs($cutoffDate);
    }

    /**
     * Get security events for monitoring
     * @param int $limit Number of events to retrieve
     * @return array Array of AuditLog entities
     */
    public function getSecurityEvents(int $limit = 100): array
    {
        return $this->auditLogRepository->findSecurityEvents($limit);
    }
}
