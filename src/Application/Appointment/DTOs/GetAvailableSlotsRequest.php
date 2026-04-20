<?php

namespace CharosEMR\Application\Appointment\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class GetAvailableSlotsRequest
{
    public function __construct(
        public readonly int $adminId,
        public readonly \DateTime $date
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'admin_id' => 'required|integer',
            'date' => 'required|date'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            (int) $data['admin_id'],
            new \DateTime($data['date'])
        );
    }
}
