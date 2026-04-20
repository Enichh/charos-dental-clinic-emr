<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use PDO;

class PDOAppointmentRepository implements AppointmentRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger
    ) {}

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
            $this->logger->info('Appointment created', [
                'appointment_id' => $appointment->getId(),
                'patient_id' => $appointment->getPatientId(),
                'admin_id' => $appointment->getAdminId(),
                'date' => $appointment->getAppointmentDate()->format('Y-m-d'),
                'start_time' => $appointment->getStartTime(),
                'end_time' => $appointment->getEndTime(),
                'status' => $appointment->getStatus()->value
            ]);
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
            $this->logger->info('Appointment updated', [
                'appointment_id' => $appointment->getId(),
                'patient_id' => $appointment->getPatientId(),
                'admin_id' => $appointment->getAdminId(),
                'status' => $appointment->getStatus()->value
            ]);
        }
    }

    public function findById(int $id): ?Appointment
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            $this->logger->info('Appointment not found', ['appointment_id' => $id]);
            return null;
        }

        $appointment = $this->hydrateAppointment($data);
        $this->logger->info('Appointment retrieved', ['appointment_id' => $id]);
        return $appointment;
    }

    public function findByPatientId(int $patientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE patient_id = :patient_id");
        $stmt->execute([':patient_id' => $patientId]);
        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Retrieved appointments by patient', ['patient_id' => $patientId, 'count' => count($appointments)]);
        return $appointments;
    }

    public function findByAdminId(int $adminId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE admin_id = :admin_id");
        $stmt->execute([':admin_id' => $adminId]);
        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Retrieved appointments by admin', ['admin_id' => $adminId, 'count' => count($appointments)]);
        return $appointments;
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
        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Retrieved appointments by date range', [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'count' => count($appointments)
        ]);
        return $appointments;
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE status = :status");
        $stmt->execute([':status' => $status]);
        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Retrieved appointments by status', ['status' => $status, 'count' => count($appointments)]);
        return $appointments;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM appointments");
        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Retrieved all appointments', ['count' => count($appointments)]);
        return $appointments;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $this->logger->info('Appointment deleted', ['appointment_id' => $id]);
    }

    public function findConflictingAppointments(int $adminId, \DateTime $date, string $startTime, string $endTime): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointments 
             WHERE admin_id = :admin_id 
             AND appointment_date = :appointment_date
             AND status NOT IN ('cancelled', 'completed')
             AND (
                 (start_time < :end_time AND end_time > :start_time)
             )"
        );
        $stmt->execute([
            ':admin_id' => $adminId,
            ':appointment_date' => $date->format('Y-m-d'),
            ':start_time' => $startTime,
            ':end_time' => $endTime
        ]);

        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Checked for conflicting appointments', [
            'admin_id' => $adminId,
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'conflicts_found' => count($appointments)
        ]);

        return $appointments;
    }

    public function findByAdminAndDate(int $adminId, \DateTime $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointments 
             WHERE admin_id = :admin_id 
             AND appointment_date = :appointment_date
             ORDER BY start_time ASC"
        );
        $stmt->execute([
            ':admin_id' => $adminId,
            ':appointment_date' => $date->format('Y-m-d')
        ]);

        $appointments = $this->hydrateAppointments($stmt->fetchAll());
        $this->logger->info('Retrieved appointments by admin and date', [
            'admin_id' => $adminId,
            'date' => $date->format('Y-m-d'),
            'count' => count($appointments)
        ]);

        return $appointments;
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
            $data['cancelled_by'] ?? null,
            $data['cancellation_reason'] ?? null,
            new \DateTime($data['created_at']),
            new \DateTime($data['updated_at'])
        );
    }

    private function hydrateAppointments(array $dataArray): array
    {
        return array_map([$this, 'hydrateAppointment'], $dataArray);
    }
}
