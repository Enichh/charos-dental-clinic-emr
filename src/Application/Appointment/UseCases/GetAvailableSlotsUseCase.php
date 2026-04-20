<?php

namespace CharosEMR\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\AvailableSlot;
use CharosEMR\Application\Appointment\DTOs\GetAvailableSlotsRequest;
use CharosEMR\Application\Appointment\DTOs\GetAvailableSlotsResponse;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AdminAvailabilityRepositoryInterface;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use InvalidArgumentException;

class GetAvailableSlotsUseCase
{
    private const DEFAULT_SLOT_DURATION_MINUTES = 30;

    public function __construct(
        private AdminAvailabilityRepositoryInterface $availabilityRepository,
        private AppointmentRepositoryInterface $appointmentRepository
    ) {}

    /**
     * Retrieves available time slots for a given admin and date.
     *
     * @param GetAvailableSlotsRequest $request The request containing admin ID and date
     * @return GetAvailableSlotsResponse Response with available slots
     */
    public function execute(GetAvailableSlotsRequest $request): GetAvailableSlotsResponse
    {
        $availability = $this->availabilityRepository->findByAdminAndDate(
            $request->adminId,
            $request->date
        );

        if (!$availability || !$availability->isActive()) {
            return new GetAvailableSlotsResponse(
                $request->adminId,
                $request->date->format('Y-m-d'),
                []
            );
        }

        $existingAppointments = $this->appointmentRepository->findByAdminAndDate(
            $request->adminId,
            $request->date
        );

        $availableSlots = $this->generateSlots(
            $availability->getStartTime(),
            $availability->getEndTime(),
            $availability->getSlotDurationMinutes() ?? self::DEFAULT_SLOT_DURATION_MINUTES,
            $existingAppointments
        );

        return new GetAvailableSlotsResponse(
            $request->adminId,
            $request->date->format('Y-m-d'),
            $availableSlots
        );
    }

    private function generateSlots(string $startTime, string $endTime, int $slotDurationMinutes, array $existingAppointments): array
    {
        if ($slotDurationMinutes <= 0) {
            throw new InvalidArgumentException('Slot duration must be positive');
        }

        $slots = [];
        $currentStart = new \DateTime($startTime);
        $endDateTime = new \DateTime($endTime);

        while ($currentStart < $endDateTime) {
            $currentEnd = clone $currentStart;
            $currentEnd->modify("+{$slotDurationMinutes} minutes");

            if ($currentEnd > $endDateTime) {
                $currentEnd = clone $endDateTime;
            }

            $isAvailable = !$this->hasConflict($currentStart, $currentEnd, $existingAppointments);

            $slots[] = new AvailableSlot(
                $currentStart->format('H:i'),
                $currentEnd->format('H:i'),
                $isAvailable
            );

            $currentStart = clone $currentEnd;
        }

        return $slots;
    }

    private function hasConflict(\DateTime $slotStart, \DateTime $slotEnd, array $existingAppointments): bool
    {
        foreach ($existingAppointments as $appointment) {
            if (in_array($appointment->getStatus(), [AppointmentStatus::CANCELLED, AppointmentStatus::NO_SHOW])) {
                continue;
            }

            $apptStart = new \DateTime($appointment->getStartTime());
            $apptEnd = new \DateTime($appointment->getEndTime());

            if (($slotStart < $apptEnd) && ($slotEnd > $apptStart)) {
                return true;
            }
        }

        return false;
    }
}
