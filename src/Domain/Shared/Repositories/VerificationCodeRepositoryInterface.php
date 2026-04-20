<?php

namespace CharosEMR\Domain\Shared\Repositories;

use CharosEMR\Domain\Shared\Entities\VerificationCode;

interface VerificationCodeRepositoryInterface
{
    public function save(VerificationCode $code): void;
    public function findByEmailAndCode(string $email, string $code): ?VerificationCode;
    public function invalidatePreviousCodes(string $email, string $purpose): void;
    public function deleteExpired(): void;
}
