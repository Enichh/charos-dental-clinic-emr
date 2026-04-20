<?php

namespace CharosEMR\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\CancelAppointmentRequest;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;

class CancelAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    public function execute(CancelAppointmentRequest $request): void
    {
        $appointment = $this->appointmentRepository->findById($request->appointmentId);

        if ($appointment === null) {
            throw new \RuntimeException('Appointment not found');
        }

        $appointment->cancel();
        $this->appointmentRepository->save($appointment);
    }
}
