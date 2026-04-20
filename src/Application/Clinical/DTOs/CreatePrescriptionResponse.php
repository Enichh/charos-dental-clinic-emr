<?php

namespace CharosEMR\Application\Clinical\DTOs;

class CreatePrescriptionResponse
{
    public function __construct(
        public readonly int $prescriptionId,
        public readonly string $message
    ) {}

    public function toArray(): array
    {
        return [
            'prescription_id' => $this->prescriptionId,
            'message' => $this->message
        ];
    }
}
