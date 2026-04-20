<?php

namespace CharosEMR\Application\Appointment\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class ScheduleAppointmentRequest
{
    public function __construct(
        public readonly int $patientId,
        public readonly int $adminId,
        public readonly \DateTime $appointmentDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly ?string $notes = null
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'patient_id' => 'required|integer',
            'admin_id' => 'required|integer',
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'notes' => 'max:1000'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            (int) $data['patient_id'],
            (int) $data['admin_id'],
            new \DateTime($data['appointment_date']),
            $data['start_time'],
            $data['end_time'],
            $data['notes'] ?? null
        );
    }
}
