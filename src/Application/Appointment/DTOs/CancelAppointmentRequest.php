<?php

namespace CharosEMR\Application\Appointment\DTOs;

class CancelAppointmentRequest
{
    public function __construct(
        public readonly int $appointmentId
    ) {}
}
