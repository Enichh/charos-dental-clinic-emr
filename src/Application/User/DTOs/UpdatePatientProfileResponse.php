<?php

namespace CharosEMR\Application\User\DTOs;

class UpdatePatientProfileResponse
{
    public function __construct(
        public readonly int $patientId,
        public readonly string $message
    ) {}

    public function toArray(): array
    {
        return [
            'patient_id' => $this->patientId,
            'message' => $this->message
        ];
    }
}
