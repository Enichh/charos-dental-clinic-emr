<?php

namespace CharosEMR\Domain\Clinical\Entities;

class DentalVisit
{
    private ?int $id;
    private int $patientId;
    private int $appointmentId;
    private ?string $notes;
    private \DateTime $createdAt;

    public function __construct(
        ?int $id,
        int $patientId,
        int $appointmentId,
        ?string $notes = null,
        ?\DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->patientId = $patientId;
        $this->appointmentId = $appointmentId;
        $this->notes = $notes;
        $this->createdAt = $createdAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getAppointmentId(): int
    {
        return $this->appointmentId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
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
