<?php

namespace CharosEMR\Domain\Appointment\Repositories;

use CharosEMR\Domain\Appointment\Entities\AdminAvailability;

interface AdminAvailabilityRepositoryInterface
{
    public function save(AdminAvailability $availability): void;
    public function findById(int $id): ?AdminAvailability;
    public function findByAdminId(int $adminId): array;
    public function findByAdminAndDate(int $adminId, \DateTime $date): ?AdminAvailability;
    public function findActiveByDateRange(\DateTime $start, \DateTime $end): array;
    public function findAll(): array;
    public function delete(int $id): void;
}
