<?php

namespace CharosEMR\Domain\Clinical\Repositories;

use CharosEMR\Domain\Clinical\Entities\DentalVisit;

interface DentalVisitRepositoryInterface
{
    public function save(DentalVisit $visit): void;
    public function findById(int $id): ?DentalVisit;
    public function findByPatientId(int $patientId): array;
    public function findByAppointmentId(int $appointmentId): array;
    public function findAll(): array;
    public function delete(int $id): void;
}
