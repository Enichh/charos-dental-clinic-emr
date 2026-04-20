<?php

namespace CharosEMR\Application\Appointment\DTOs;

class ScheduleAppointmentResponse
{
    public function __construct(
        public readonly int $appointmentId,
        public readonly string $message
    ) {}

    public function toArray(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'message' => $this->message
        ];
    }
}
