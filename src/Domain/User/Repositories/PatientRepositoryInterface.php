<?php

namespace CharosEMR\Domain\User\Repositories;

use CharosEMR\Domain\User\Entities\Patient;

interface PatientRepositoryInterface
{
    public function save(Patient $patient): Patient;
    public function findByUserId(int $userId): ?Patient;
    public function findByEmail(string $email): ?Patient;
    public function findById(int $id): ?Patient;
}
