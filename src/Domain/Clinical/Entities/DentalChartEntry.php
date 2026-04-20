<?php

namespace CharosEMR\Domain\Clinical\Entities;

class DentalChartEntry
{
    private ?int $id;
    private int $dentalVisitId;
    private string $toothNumber;
    private ?string $surface;
    private string $procedureType;
    private ?string $material;
    private ?string $diagnosis;
    private ?string $treatmentNotes;
    private \DateTime $createdAt;

    public function __construct(
        ?int $id,
        int $dentalVisitId,
        string $toothNumber,
        string $procedureType,
        ?string $surface = null,
        ?string $material = null,
        ?string $diagnosis = null,
        ?string $treatmentNotes = null,
        ?\DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->dentalVisitId = $dentalVisitId;
        $this->toothNumber = $toothNumber;
        $this->surface = $surface;
        $this->procedureType = $procedureType;
        $this->material = $material;
        $this->diagnosis = $diagnosis;
        $this->treatmentNotes = $treatmentNotes;
        $this->createdAt = $createdAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDentalVisitId(): int
    {
        return $this->dentalVisitId;
    }

    public function getToothNumber(): string
    {
        return $this->toothNumber;
    }

    public function getSurface(): ?string
    {
        return $this->surface;
    }

    public function getProcedureType(): string
    {
        return $this->procedureType;
    }

    public function getMaterial(): ?string
    {
        return $this->material;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function getTreatmentNotes(): ?string
    {
        return $this->treatmentNotes;
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
