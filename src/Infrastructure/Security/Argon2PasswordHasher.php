<?php

namespace CharosEMR\Infrastructure\Security;

use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;

class Argon2PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
