<?php

namespace CharosEMR\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentRequest;
use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentResponse;
use CharosEMR\Application\Appointment\Events\AppointmentBookedEvent;
use CharosEMR\Application\Shared\Events\EventDispatcherInterface;
use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use InvalidArgumentException;

class ScheduleAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * Schedules a new appointment after validating business rules and availability.
     *
     * @param ScheduleAppointmentRequest $request The appointment request
     * @return ScheduleAppointmentResponse Response with appointment ID
     * @throws InvalidArgumentException If business rules are violated or slot is unavailable
     */
    public function execute(ScheduleAppointmentRequest $request): ScheduleAppointmentResponse
    {
        $this->logger->info('Appointment scheduling attempt', [
            'patient_id' => $request->patientId,
            'admin_id' => $request->adminId,
            'appointment_date' => $request->appointmentDate->format('Y-m-d'),
            'start_time' => $request->startTime,
            'end_time' => $request->endTime
        ]);

        $this->validateBusinessRules($request);

        $conflictingAppointments = $this->appointmentRepository->findConflictingAppointments(
            $request->adminId,
            $request->appointmentDate,
            $request->startTime,
            $request->endTime
        );

        if (!empty($conflictingAppointments)) {
            $this->logger->warning('Appointment scheduling failed - slot already booked', [
                'patient_id' => $request->patientId,
                'admin_id' => $request->adminId,
                'appointment_date' => $request->appointmentDate->format('Y-m-d'),
                'start_time' => $request->startTime,
                'end_time' => $request->endTime,
                'conflicting_count' => count($conflictingAppointments)
            ]);
            throw new InvalidArgumentException('This time slot is already booked');
        }

        $appointment = new Appointment(
            null,
            $request->patientId,
            $request->adminId,
            $request->appointmentDate,
            $request->startTime,
            $request->endTime,
            AppointmentStatus::PENDING,
            $request->notes
        );

        $this->appointmentRepository->save($appointment);

        $this->logger->info('Appointment scheduled successfully', [
            'appointment_id' => $appointment->getId(),
            'patient_id' => $request->patientId,
            'admin_id' => $request->adminId,
            'appointment_date' => $request->appointmentDate->format('Y-m-d'),
            'start_time' => $request->startTime,
            'end_time' => $request->endTime,
            'status' => $appointment->getStatus()->value
        ]);

        $this->eventDispatcher->dispatch(new AppointmentBookedEvent($appointment));

        return new ScheduleAppointmentResponse(
            $appointment->getId(),
            'Appointment scheduled successfully'
        );
    }

    /**
     * Validates business rules for appointment scheduling.
     *
     * @param ScheduleAppointmentRequest $request The appointment request
     * @throws InvalidArgumentException If business rules are violated
     */
    private function validateBusinessRules(ScheduleAppointmentRequest $request): void
    {
        $today = new \DateTime('today');
        if ($request->appointmentDate < $today) {
            throw new InvalidArgumentException('Appointment date must be in the future');
        }

        $start = new \DateTime($request->startTime);
        $end = new \DateTime($request->endTime);
        if ($start >= $end) {
            throw new InvalidArgumentException('Start time must be before end time');
        }
    }
}
