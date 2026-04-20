<?php

namespace CharosEMR\Application\Shared\Interfaces;

interface PasswordHasherInterface
{
    public function hash(string $password): string;
    public function verify(string $password, string $hash): bool;
}
