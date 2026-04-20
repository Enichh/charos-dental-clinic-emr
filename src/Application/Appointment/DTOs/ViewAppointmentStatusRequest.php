<?php

namespace CharosEMR\Application\Appointment\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class ViewAppointmentStatusRequest
{
    public function __construct(
        public readonly int $appointmentId
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'appointment_id' => 'required|integer'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            (int) $data['appointment_id']
        );
    }
}
