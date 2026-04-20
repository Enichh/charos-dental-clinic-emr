<?php

namespace CharosEMR\Application\Appointment\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class ScheduleAppointmentRequest
{
    public function __construct(
        public readonly int $patientId,
        public readonly int $dentistId,
        public readonly \DateTime $scheduledDateTime,
        public readonly ?string $notes = null
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'patient_id' => 'required|integer',
            'dentist_id' => 'required|integer',
            'scheduled_datetime' => 'required|date',
            'notes' => 'max:1000'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            (int) $data['patient_id'],
            (int) $data['dentist_id'],
            new \DateTime($data['scheduled_datetime']),
            $data['notes'] ?? null
        );
    }
}
