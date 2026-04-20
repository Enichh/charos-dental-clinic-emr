<?php

namespace CharosEMR\Application\User\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class RegisterPatientRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $dateOfBirth,
        public readonly string $gender,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $address = null,
        public readonly ?string $bloodType = null,
        public readonly ?string $allergies = null
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone_number' => 'max:20',
            'address' => 'max:500',
            'blood_type' => 'max:5',
            'allergies' => 'max:1000'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            $data['email'],
            $data['password'],
            $data['first_name'],
            $data['last_name'],
            $data['date_of_birth'],
            $data['gender'],
            $data['phone_number'] ?? null,
            $data['address'] ?? null,
            $data['blood_type'] ?? null,
            $data['allergies'] ?? null
        );
    }
}
