<?php

namespace CharosEMR\Application\Appointment\Events;

use CharosEMR\Application\Shared\Events\Event;
use CharosEMR\Domain\Appointment\Entities\Appointment;

class AppointmentReminderEvent extends Event
{
    public function __construct(
        private readonly Appointment $appointment
    ) {
        parent::__construct();
    }

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }
}
