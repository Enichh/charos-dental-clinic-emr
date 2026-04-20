<?php

namespace CharosEMR\Domain\Clinical\Repositories;

use CharosEMR\Domain\Clinical\Entities\DentalChartEntry;

interface DentalChartEntryRepositoryInterface
{
    public function save(DentalChartEntry $entry): void;
    public function findById(int $id): ?DentalChartEntry;
    public function findByDentalVisitId(int $dentalVisitId): array;
    public function findByToothNumber(int $dentalVisitId, string $toothNumber): array;
    public function findAll(): array;
    public function delete(int $id): void;
}
