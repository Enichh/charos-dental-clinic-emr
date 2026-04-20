<?php

namespace CharosEMR\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\ViewAppointmentStatusRequest;
use CharosEMR\Application\Appointment\DTOs\ViewAppointmentStatusResponse;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use InvalidArgumentException;

class ViewAppointmentStatusUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    /**
     * Retrieves the status and details of a specific appointment.
     *
     * @param ViewAppointmentStatusRequest $request The request containing appointment ID
     * @return ViewAppointmentStatusResponse Response with appointment details
     * @throws InvalidArgumentException If appointment not found
     */
    public function execute(ViewAppointmentStatusRequest $request): ViewAppointmentStatusResponse
    {
        $appointment = $this->appointmentRepository->findById($request->appointmentId);

        if (!$appointment) {
            throw new InvalidArgumentException('Appointment not found');
        }

        $cancelledBy = null;
        $cancellationReason = null;

        if ($appointment->getStatus()->value === 'cancelled') {
            $cancelledBy = $appointment->getCancelledBy();
            $cancellationReason = $appointment->getCancellationReason();
        }

        return new ViewAppointmentStatusResponse(
            $appointment->getId(),
            $appointment->getPatientId(),
            $appointment->getAdminId(),
            $appointment->getAppointmentDate()->format('Y-m-d'),
            $appointment->getStartTime(),
            $appointment->getEndTime(),
            $appointment->getStatus()->value,
            $appointment->getNotes(),
            $cancelledBy,
            $cancellationReason,
            $appointment->getCreatedAt()->format('Y-m-d H:i:s'),
            $appointment->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}
