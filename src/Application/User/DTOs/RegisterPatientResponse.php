<?php

namespace CharosEMR\Application\User\DTOs;

class RegisterPatientResponse
{
    public function __construct(
        public readonly int $userId,
        public readonly int $patientId,
        public readonly string $message
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'patient_id' => $this->patientId,
            'message' => $this->message
        ];
    }
}
