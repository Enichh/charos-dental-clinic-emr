<?php

namespace CharosEMR\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentRequest;
use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentResponse;
use CharosEMR\Application\Appointment\Events\AppointmentBookedEvent;
use CharosEMR\Application\Shared\Events\EventDispatcherInterface;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;

class ScheduleAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function execute(ScheduleAppointmentRequest $request): ScheduleAppointmentResponse
    {
        $appointment = new Appointment(
            null,
            $request->patientId,
            $request->dentistId,
            $request->scheduledDateTime,
            AppointmentStatus::SCHEDULED,
            $request->notes
        );

        $this->appointmentRepository->save($appointment);

        $this->eventDispatcher->dispatch(new AppointmentBookedEvent($appointment));

        return new ScheduleAppointmentResponse(
            $appointment->getId(),
            'Appointment scheduled successfully'
        );
    }
}
