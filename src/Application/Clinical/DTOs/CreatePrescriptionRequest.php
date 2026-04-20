<?php

namespace CharosEMR\Application\Clinical\DTOs;

class CreatePrescriptionRequest
{
    public function __construct(
        public readonly int $patientId,
        public readonly int $dentistId,
        public readonly string $medication,
        public readonly string $dosage,
        public readonly ?string $instructions = null
    ) {}
}
