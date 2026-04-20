<?php

namespace CharosEMR\Application\User\DTOs;

use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class LoginPatientRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}

    public static function fromArray(array $data, ValidatorInterface $validator): self|ValidationResult
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8'
        ];

        $validationResult = $validator->validate($data, $rules);

        if ($validationResult->hasErrors()) {
            return $validationResult;
        }

        return new self(
            $data['email'],
            $data['password']
        );
    }
}
