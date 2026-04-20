<?php

namespace CharosEMR\Application\User\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class UpdatePatientProfileRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $gender,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $address = null,
        public readonly ?string $dateOfBirth = null
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'user_id' => 'required|integer',
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'gender' => 'required|in:male,female,other',
            'phone_number' => 'max:20',
            'address' => 'max:255',
            'date_of_birth' => 'date'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            (int) $data['user_id'],
            $data['first_name'],
            $data['last_name'],
            $data['gender'],
            $data['phone_number'] ?? null,
            $data['address'] ?? null,
            $data['date_of_birth'] ?? null
        );
    }
}
