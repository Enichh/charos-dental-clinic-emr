<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Shared\Entities\AuditLog;
use CharosEMR\Domain\Shared\Repositories\AuditLogRepositoryInterface;
use PDO;

class PDOAuditLogRepository implements AuditLogRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(AuditLog $auditLog): void
    {
        $sql = "INSERT INTO audit_logs (
            timestamp, user_id, user_email, user_role, action,
            resource_type, resource_id, ip_address, user_agent, details, success
        ) VALUES (
            :timestamp, :user_id, :user_email, :user_role, :action,
            :resource_type, :resource_id, :ip_address, :user_agent, :details, :success
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':timestamp' => $auditLog->getTimestamp()->format('Y-m-d H:i:s'),
            ':user_id' => $auditLog->getUserId(),
            ':user_email' => $auditLog->getUserEmail(),
            ':user_role' => $auditLog->getUserRole(),
            ':action' => $auditLog->getAction(),
            ':resource_type' => $auditLog->getResourceType(),
            ':resource_id' => $auditLog->getResourceId(),
            ':ip_address' => $auditLog->getIpAddress(),
            ':user_agent' => $auditLog->getUserAgent(),
            ':details' => json_encode($auditLog->getDetails()),
            ':success' => $auditLog->isSuccess() ? 1 : 0
        ]);

        $auditLog->setId((int)$this->pdo->lastInsertId());
    }

    public function findById(int $id): ?AuditLog
    {
        $sql = "SELECT * FROM audit_logs WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByUserId(string $userId, int $limit = 100): array
    {
        $sql = "SELECT * FROM audit_logs WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByAction(string $action, int $limit = 100): array
    {
        $sql = "SELECT * FROM audit_logs WHERE action = :action ORDER BY timestamp DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, int $limit = 100): array
    {
        $sql = "SELECT * FROM audit_logs
                WHERE timestamp BETWEEN :start_date AND :end_date
                ORDER BY timestamp DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findSecurityEvents(int $limit = 100): array
    {
        $sql = "SELECT * FROM audit_logs
                WHERE success = 0 OR action IN ('LOGIN_ATTEMPT', 'UNAUTHORIZED_ACCESS', 'DATA_BREACH_ATTEMPT')
                ORDER BY timestamp DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $rows);
    }

    public function deleteOldLogs(\DateTime $cutoffDate): int
    {
        $sql = "DELETE FROM audit_logs WHERE timestamp < :cutoff_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')]);

        return $stmt->rowCount();
    }

    private function hydrate(array $row): AuditLog
    {
        return new AuditLog(
            $row['id'],
            new \DateTime($row['timestamp']),
            $row['user_id'],
            $row['user_email'],
            $row['user_role'],
            $row['action'],
            $row['resource_type'],
            $row['resource_id'],
            $row['ip_address'],
            $row['user_agent'],
            json_decode($row['details'], true),
            (bool)$row['success']
        );
    }
}
