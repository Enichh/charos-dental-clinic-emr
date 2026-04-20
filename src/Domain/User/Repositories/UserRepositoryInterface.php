<?php

namespace CharosEMR\Domain\User\Repositories;

use CharosEMR\Domain\User\Entities\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByRole(string $role): array;
    public function findActive(): array;
    public function findAll(): array;
    public function delete(int $id): void;
}
