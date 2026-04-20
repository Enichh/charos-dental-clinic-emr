<?php

namespace CharosEMR\Domain\Appointment\Entities;

use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;

class Appointment
{
    private ?int $id;
    private int $patientId;
    private int $adminId;
    private \DateTime $appointmentDate;
    private string $startTime;
    private string $endTime;
    private AppointmentStatus $status;
    private ?string $notes;
    private ?string $cancelledBy;
    private ?string $cancellationReason;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        ?int $id,
        int $patientId,
        int $adminId,
        \DateTime $appointmentDate,
        string $startTime,
        string $endTime,
        AppointmentStatus $status,
        ?string $notes = null,
        ?string $cancelledBy = null,
        ?string $cancellationReason = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->patientId = $patientId;
        $this->adminId = $adminId;
        $this->appointmentDate = $appointmentDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->status = $status;
        $this->notes = $notes;
        $this->cancelledBy = $cancelledBy;
        $this->cancellationReason = $cancellationReason;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getAdminId(): int
    {
        return $this->adminId;
    }

    public function getAppointmentDate(): \DateTime
    {
        return $this->appointmentDate;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCancelledBy(): ?string
    {
        return $this->cancelledBy;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setStatus(AppointmentStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTime();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function cancel(?string $cancelledBy = null, ?string $cancellationReason = null): void
    {
        $this->status = AppointmentStatus::CANCELLED;
        $this->cancelledBy = $cancelledBy;
        $this->cancellationReason = $cancellationReason;
        $this->updatedAt = new \DateTime();
    }

    public function confirm(): void
    {
        $this->status = AppointmentStatus::CONFIRMED;
        $this->updatedAt = new \DateTime();
    }

    public function complete(): void
    {
        $this->status = AppointmentStatus::COMPLETED;
        $this->updatedAt = new \DateTime();
    }
}
