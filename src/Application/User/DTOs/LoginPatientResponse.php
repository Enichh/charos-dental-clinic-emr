<?php

namespace CharosEMR\Application\User\DTOs;

class LoginPatientResponse
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $role,
        public readonly string $message
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'role' => $this->role,
            'message' => $this->message
        ];
    }
}
