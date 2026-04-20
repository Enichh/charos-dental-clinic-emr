<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use PDO;

class PDOAppointmentRepository implements AppointmentRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(Appointment $appointment): void
    {
        if ($appointment->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO appointments (patient_id, admin_id, appointment_date, start_time, end_time, status, notes) 
                 VALUES (:patient_id, :admin_id, :appointment_date, :start_time, :end_time, :status, :notes)"
            );
            $stmt->execute([
                ':patient_id' => $appointment->getPatientId(),
                ':admin_id' => $appointment->getAdminId(),
                ':appointment_date' => $appointment->getAppointmentDate()->format('Y-m-d'),
                ':start_time' => $appointment->getStartTime(),
                ':end_time' => $appointment->getEndTime(),
                ':status' => $appointment->getStatus()->value,
                ':notes' => $appointment->getNotes()
            ]);
            $appointment->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE appointments SET patient_id = :patient_id, admin_id = :admin_id, 
                 appointment_date = :appointment_date, start_time = :start_time, end_time = :end_time, 
                 status = :status, notes = :notes WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $appointment->getId(),
                ':patient_id' => $appointment->getPatientId(),
                ':admin_id' => $appointment->getAdminId(),
                ':appointment_date' => $appointment->getAppointmentDate()->format('Y-m-d'),
                ':start_time' => $appointment->getStartTime(),
                ':end_time' => $appointment->getEndTime(),
                ':status' => $appointment->getStatus()->value,
                ':notes' => $appointment->getNotes()
            ]);
        }
    }

    public function findById(int $id): ?Appointment
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateAppointment($data);
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE patient_id = :patient_id");
        $stmt->execute([':patient_id' => $patientId]);
        return $this->hydrateAppointments($stmt->fetchAll());
    }

    public function findByAdminId(int $adminId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE admin_id = :admin_id");
        $stmt->execute([':admin_id' => $adminId]);
        return $this->hydrateAppointments($stmt->fetchAll());
    }

    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointments WHERE appointment_date BETWEEN :start AND :end"
        );
        $stmt->execute([
            ':start' => $start->format('Y-m-d'),
            ':end' => $end->format('Y-m-d')
        ]);
        return $this->hydrateAppointments($stmt->fetchAll());
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE status = :status");
        $stmt->execute([':status' => $status]);
        return $this->hydrateAppointments($stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM appointments");
        return $this->hydrateAppointments($stmt->fetchAll());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function hydrateAppointment(array $data): Appointment
    {
        return new Appointment(
            (int) $data['id'],
            (int) $data['patient_id'],
            (int) $data['admin_id'],
            new \DateTime($data['appointment_date']),
            $data['start_time'],
            $data['end_time'],
            AppointmentStatus::from($data['status']),
            $data['notes'] ?? null,
            new \DateTime($data['created_at']),
            new \DateTime($data['updated_at'])
        );
    }

    private function hydrateAppointments(array $dataArray): array
    {
        return array_map([$this, 'hydrateAppointment'], $dataArray);
    }
}
