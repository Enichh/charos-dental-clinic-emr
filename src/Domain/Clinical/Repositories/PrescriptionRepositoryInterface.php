<?php

namespace CharosEMR\Domain\Clinical\Repositories;

use CharosEMR\Domain\Clinical\Entities\Prescription;

interface PrescriptionRepositoryInterface
{
    public function save(Prescription $prescription): void;
    public function findById(int $id): ?Prescription;
    public function findByPatientId(int $patientId): array;
    public function findAll(): array;
    public function delete(int $id): void;
}
