<?php

namespace CharosEMR\Application\Clinical\UseCases;

use CharosEMR\Domain\Clinical\Entities\DentalChartEntry;
use CharosEMR\Domain\Clinical\Repositories\PrescriptionRepositoryInterface;

class AddDentalChartEntryUseCase
{
    public function __construct(
        private PrescriptionRepositoryInterface $repository
    ) {}

    public function execute(
        int $patientId,
        int $dentistId,
        string $toothNumber,
        string $condition,
        ?string $treatment = null,
        ?string $notes = null
    ): DentalChartEntry {
        $entry = new DentalChartEntry(
            null,
            $patientId,
            $dentistId,
            $toothNumber,
            $condition,
            $treatment,
            null,
            $notes
        );

        return $entry;
    }
}
