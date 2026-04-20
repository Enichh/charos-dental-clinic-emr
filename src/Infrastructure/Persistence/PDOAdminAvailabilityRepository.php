<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Appointment\Entities\AdminAvailability;
use CharosEMR\Domain\Appointment\Repositories\AdminAvailabilityRepositoryInterface;
use PDO;

class PDOAdminAvailabilityRepository implements AdminAvailabilityRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(AdminAvailability $availability): void
    {
        if ($availability->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO admin_availabilities (admin_id, available_date, start_time, end_time, slot_duration_minutes, is_active) 
                 VALUES (:admin_id, :available_date, :start_time, :end_time, :slot_duration_minutes, :is_active)"
            );
            $stmt->execute([
                ':admin_id' => $availability->getAdminId(),
                ':available_date' => $availability->getAvailableDate()->format('Y-m-d'),
                ':start_time' => $availability->getStartTime(),
                ':end_time' => $availability->getEndTime(),
                ':slot_duration_minutes' => $availability->getSlotDurationMinutes(),
                ':is_active' => $availability->isActive() ? 1 : 0
            ]);
            $availability->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE admin_availabilities SET admin_id = :admin_id, available_date = :available_date, 
                 start_time = :start_time, end_time = :end_time, slot_duration_minutes = :slot_duration_minutes, 
                 is_active = :is_active WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $availability->getId(),
                ':admin_id' => $availability->getAdminId(),
                ':available_date' => $availability->getAvailableDate()->format('Y-m-d'),
                ':start_time' => $availability->getStartTime(),
                ':end_time' => $availability->getEndTime(),
                ':slot_duration_minutes' => $availability->getSlotDurationMinutes(),
                ':is_active' => $availability->isActive() ? 1 : 0
            ]);
        }
    }

    public function findById(int $id): ?AdminAvailability
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admin_availabilities WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateAdminAvailability($data);
    }

    public function findByAdminId(int $adminId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admin_availabilities WHERE admin_id = :admin_id");
        $stmt->execute([':admin_id' => $adminId]);
        return $this->hydrateAdminAvailabilities($stmt->fetchAll());
    }

    public function findByAdminAndDate(int $adminId, \DateTime $date): ?AdminAvailability
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM admin_availabilities WHERE admin_id = :admin_id AND available_date = :available_date"
        );
        $stmt->execute([
            ':admin_id' => $adminId,
            ':available_date' => $date->format('Y-m-d')
        ]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateAdminAvailability($data);
    }

    public function findActiveByDateRange(\DateTime $start, \DateTime $end): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM admin_availabilities WHERE available_date BETWEEN :start AND :end AND is_active = 1"
        );
        $stmt->execute([
            ':start' => $start->format('Y-m-d'),
            ':end' => $end->format('Y-m-d')
        ]);
        return $this->hydrateAdminAvailabilities($stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM admin_availabilities");
        return $this->hydrateAdminAvailabilities($stmt->fetchAll());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admin_availabilities WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function hydrateAdminAvailability(array $data): AdminAvailability
    {
        return new AdminAvailability(
            (int) $data['id'],
            (int) $data['admin_id'],
            new \DateTime($data['available_date']),
            $data['start_time'],
            $data['end_time'],
            (int) $data['slot_duration_minutes'],
            (bool) $data['is_active'],
            new \DateTime($data['created_at'])
        );
    }

    private function hydrateAdminAvailabilities(array $dataArray): array
    {
        return array_map([$this, 'hydrateAdminAvailability'], $dataArray);
    }
}
