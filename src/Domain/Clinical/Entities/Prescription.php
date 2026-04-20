<?php

namespace CharosEMR\Domain\Clinical\Entities;

class Prescription
{
    private ?int $id;
    private int $patientId;
    private int $dentistId;
    private string $medication;
    private string $dosage;
    private ?string $instructions;
    private \DateTime $prescribedAt;
    private bool $isActive;

    public function __construct(
        ?int $id,
        int $patientId,
        int $dentistId,
        string $medication,
        string $dosage,
        ?string $instructions = null,
        ?\DateTime $prescribedAt = null,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->patientId = $patientId;
        $this->dentistId = $dentistId;
        $this->medication = $medication;
        $this->dosage = $dosage;
        $this->instructions = $instructions;
        $this->prescribedAt = $prescribedAt ?? new \DateTime();
        $this->isActive = $isActive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getDentistId(): int
    {
        return $this->dentistId;
    }

    public function getMedication(): string
    {
        return $this->medication;
    }

    public function getDosage(): string
    {
        return $this->dosage;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function getPrescribedAt(): \DateTime
    {
        return $this->prescribedAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function isDosageDangerous(): bool
    {
        // Business logic: Example dosage validation
        return $this->dosage > 1000;
    }
}
