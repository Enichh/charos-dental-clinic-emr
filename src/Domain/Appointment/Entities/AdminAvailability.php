<?php

namespace CharosEMR\Domain\Appointment\Entities;

class AdminAvailability
{
    private ?int $id;
    private int $adminId;
    private \DateTime $availableDate;
    private string $startTime;
    private string $endTime;
    private ?int $slotDurationMinutes;
    private bool $isActive;
    private \DateTime $createdAt;

    public function __construct(
        ?int $id,
        int $adminId,
        \DateTime $availableDate,
        string $startTime,
        string $endTime,
        ?int $slotDurationMinutes = 30,
        bool $isActive = true,
        ?\DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->adminId = $adminId;
        $this->availableDate = $availableDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->slotDurationMinutes = $slotDurationMinutes;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminId(): int
    {
        return $this->adminId;
    }

    public function getAvailableDate(): \DateTime
    {
        return $this->availableDate;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function getSlotDurationMinutes(): ?int
    {
        return $this->slotDurationMinutes;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
