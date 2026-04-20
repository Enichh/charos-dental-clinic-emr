<?php

namespace CharosEMR\Domain\Shared\Entities;

class VerificationCode
{
    private ?int $id;
    private string $email;
    private string $code;
    private string $purpose;
    private \DateTime $expiresAt;
    private ?\DateTime $usedAt;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        ?int $id,
        string $email,
        string $code,
        string $purpose,
        \DateTime $expiresAt,
        ?\DateTime $usedAt = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->code = $code;
        $this->purpose = $purpose;
        $this->expiresAt = $expiresAt;
        $this->usedAt = $usedAt;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function isExpired(): bool
    {
        return new \DateTime() > $this->expiresAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    public function markAsUsed(): void
    {
        $this->usedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
