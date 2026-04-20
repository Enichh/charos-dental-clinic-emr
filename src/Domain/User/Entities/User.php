<?php

namespace CharosEMR\Domain\User\Entities;

use CharosEMR\Domain\User\Enums\UserRole;

class User
{
    private ?int $id;
    private string $email;
    private string $passwordHash;
    private UserRole $role;
    private bool $isActive;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;
    private ?\DateTime $lastLogin;

    public function __construct(
        ?int $id,
        string $email,
        string $passwordHash,
        UserRole $role,
        bool $isActive = true,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null,
        ?\DateTime $lastLogin = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
        $this->lastLogin = $lastLogin;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
        $this->updatedAt = new \DateTime();
    }

    public function updateLastLogin(): void
    {
        $this->lastLogin = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
