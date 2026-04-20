<?php

namespace CharosEMR\Domain\Appointment\Repositories;

use CharosEMR\Domain\Appointment\Entities\Appointment;

interface AppointmentRepositoryInterface
{
    public function save(Appointment $appointment): void;
    public function findById(int $id): ?Appointment;
    public function findByPatientId(int $patientId): array;
    public function findByAdminId(int $adminId): array;
    public function findByDateRange(\DateTime $start, \DateTime $end): array;
    public function findByStatus(string $status): array;
    public function findAll(): array;
    public function delete(int $id): void;
    public function findConflictingAppointments(int $adminId, \DateTime $date, string $startTime, string $endTime): array;
    public function findByAdminAndDate(int $adminId, \DateTime $date): array;
}
