<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Clinical\Entities\DentalVisit;
use CharosEMR\Domain\Clinical\Repositories\DentalVisitRepositoryInterface;
use PDO;

class PDODentalVisitRepository implements DentalVisitRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(DentalVisit $visit): void
    {
        if ($visit->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO dental_visits (patient_id, appointment_id, notes) 
                 VALUES (:patient_id, :appointment_id, :notes)"
            );
            $stmt->execute([
                ':patient_id' => $visit->getPatientId(),
                ':appointment_id' => $visit->getAppointmentId(),
                ':notes' => $visit->getNotes()
            ]);
            $visit->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE dental_visits SET patient_id = :patient_id, appointment_id = :appointment_id, 
                 notes = :notes WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $visit->getId(),
                ':patient_id' => $visit->getPatientId(),
                ':appointment_id' => $visit->getAppointmentId(),
                ':notes' => $visit->getNotes()
            ]);
        }
    }

    public function findById(int $id): ?DentalVisit
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dental_visits WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateDentalVisit($data);
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dental_visits WHERE patient_id = :patient_id");
        $stmt->execute([':patient_id' => $patientId]);
        return $this->hydrateDentalVisits($stmt->fetchAll());
    }

    public function findByAppointmentId(int $appointmentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dental_visits WHERE appointment_id = :appointment_id");
        $stmt->execute([':appointment_id' => $appointmentId]);
        return $this->hydrateDentalVisits($stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM dental_visits");
        return $this->hydrateDentalVisits($stmt->fetchAll());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM dental_visits WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function hydrateDentalVisit(array $data): DentalVisit
    {
        return new DentalVisit(
            (int) $data['id'],
            (int) $data['patient_id'],
            (int) $data['appointment_id'],
            $data['notes'] ?? null,
            new \DateTime($data['created_at'])
        );
    }

    private function hydrateDentalVisits(array $dataArray): array
    {
        return array_map([$this, 'hydrateDentalVisit'], $dataArray);
    }
}
