<?php

namespace CharosEMR\Domain\Shared\Repositories;

use CharosEMR\Domain\Shared\Entities\AuditLog;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void;
    public function findById(int $id): ?AuditLog;
    public function findByUserId(string $userId, int $limit = 100): array;
    public function findByAction(string $action, int $limit = 100): array;
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, int $limit = 100): array;
    public function findSecurityEvents(int $limit = 100): array;
    public function deleteOldLogs(\DateTime $cutoffDate): int;
}
