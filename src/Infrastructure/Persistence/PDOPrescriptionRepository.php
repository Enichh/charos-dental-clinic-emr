<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Clinical\Entities\Prescription;
use CharosEMR\Domain\Clinical\Repositories\PrescriptionRepositoryInterface;
use PDO;

class PDOPrescriptionRepository implements PrescriptionRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(Prescription $prescription): void
    {
        if ($prescription->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO prescriptions (patient_id, dentist_id, medication, dosage, instructions, is_active) 
                 VALUES (:patient_id, :dentist_id, :medication, :dosage, :instructions, :is_active)"
            );
            $stmt->execute([
                ':patient_id' => $prescription->getPatientId(),
                ':dentist_id' => $prescription->getDentistId(),
                ':medication' => $prescription->getMedication(),
                ':dosage' => $prescription->getDosage(),
                ':instructions' => $prescription->getInstructions(),
                ':is_active' => $prescription->isActive() ? 1 : 0
            ]);
            $prescription->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE prescriptions SET patient_id = :patient_id, dentist_id = :dentist_id, 
                 medication = :medication, dosage = :dosage, instructions = :instructions, is_active = :is_active 
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $prescription->getId(),
                ':patient_id' => $prescription->getPatientId(),
                ':dentist_id' => $prescription->getDentistId(),
                ':medication' => $prescription->getMedication(),
                ':dosage' => $prescription->getDosage(),
                ':instructions' => $prescription->getInstructions(),
                ':is_active' => $prescription->isActive() ? 1 : 0
            ]);
        }
    }

    public function findById(int $id): ?Prescription
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescriptions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydratePrescription($data);
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescriptions WHERE patient_id = :patient_id");
        $stmt->execute([':patient_id' => $patientId]);
        return array_map([$this, 'hydratePrescription'], $stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM prescriptions");
        return array_map([$this, 'hydratePrescription'], $stmt->fetchAll());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM prescriptions WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function hydratePrescription(array $data): Prescription
    {
        return new Prescription(
            (int) $data['id'],
            (int) $data['patient_id'],
            (int) $data['dentist_id'],
            $data['medication'],
            $data['dosage'],
            $data['instructions'] ?? null,
            new \DateTime($data['prescribed_at']),
            (bool) $data['is_active']
        );
    }
}
