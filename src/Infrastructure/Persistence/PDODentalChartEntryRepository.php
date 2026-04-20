<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Clinical\Entities\DentalChartEntry;
use CharosEMR\Domain\Clinical\Repositories\DentalChartEntryRepositoryInterface;
use PDO;

class PDODentalChartEntryRepository implements DentalChartEntryRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(DentalChartEntry $entry): void
    {
        if ($entry->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO dental_chart_entries (dental_visit_id, tooth_number, surface, procedure_type, material, diagnosis, treatment_notes) 
                 VALUES (:dental_visit_id, :tooth_number, :surface, :procedure_type, :material, :diagnosis, :treatment_notes)"
            );
            $stmt->execute([
                ':dental_visit_id' => $entry->getDentalVisitId(),
                ':tooth_number' => $entry->getToothNumber(),
                ':surface' => $entry->getSurface(),
                ':procedure_type' => $entry->getProcedureType(),
                ':material' => $entry->getMaterial(),
                ':diagnosis' => $entry->getDiagnosis(),
                ':treatment_notes' => $entry->getTreatmentNotes()
            ]);
            $entry->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE dental_chart_entries SET dental_visit_id = :dental_visit_id, tooth_number = :tooth_number, 
                 surface = :surface, procedure_type = :procedure_type, material = :material, 
                 diagnosis = :diagnosis, treatment_notes = :treatment_notes WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $entry->getId(),
                ':dental_visit_id' => $entry->getDentalVisitId(),
                ':tooth_number' => $entry->getToothNumber(),
                ':surface' => $entry->getSurface(),
                ':procedure_type' => $entry->getProcedureType(),
                ':material' => $entry->getMaterial(),
                ':diagnosis' => $entry->getDiagnosis(),
                ':treatment_notes' => $entry->getTreatmentNotes()
            ]);
        }
    }

    public function findById(int $id): ?DentalChartEntry
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dental_chart_entries WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateDentalChartEntry($data);
    }

    public function findByDentalVisitId(int $dentalVisitId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dental_chart_entries WHERE dental_visit_id = :dental_visit_id");
        $stmt->execute([':dental_visit_id' => $dentalVisitId]);
        return $this->hydrateDentalChartEntries($stmt->fetchAll());
    }

    public function findByToothNumber(int $dentalVisitId, string $toothNumber): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM dental_chart_entries WHERE dental_visit_id = :dental_visit_id AND tooth_number = :tooth_number"
        );
        $stmt->execute([
            ':dental_visit_id' => $dentalVisitId,
            ':tooth_number' => $toothNumber
        ]);
        return $this->hydrateDentalChartEntries($stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM dental_chart_entries");
        return $this->hydrateDentalChartEntries($stmt->fetchAll());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM dental_chart_entries WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function hydrateDentalChartEntry(array $data): DentalChartEntry
    {
        return new DentalChartEntry(
            (int) $data['id'],
            (int) $data['dental_visit_id'],
            $data['tooth_number'],
            $data['procedure_type'],
            $data['surface'] ?? null,
            $data['material'] ?? null,
            $data['diagnosis'] ?? null,
            $data['treatment_notes'] ?? null,
            new \DateTime($data['created_at'])
        );
    }

    private function hydrateDentalChartEntries(array $dataArray): array
    {
        return array_map([$this, 'hydrateDentalChartEntry'], $dataArray);
    }
}
