<?php

namespace CharosEMR\Application\Appointment\DTOs;

class ViewAppointmentStatusResponse
{
    public function __construct(
        public readonly ?int $appointmentId,
        public readonly int $patientId,
        public readonly int $adminId,
        public readonly string $appointmentDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?string $cancelledBy,
        public readonly ?string $cancellationReason,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'patient_id' => $this->patientId,
            'admin_id' => $this->adminId,
            'appointment_date' => $this->appointmentDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'status' => $this->status,
            'notes' => $this->notes,
            'cancelled_by' => $this->cancelledBy,
            'cancellation_reason' => $this->cancellationReason,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
