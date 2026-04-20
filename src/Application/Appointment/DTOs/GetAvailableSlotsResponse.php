<?php

namespace CharosEMR\Application\Appointment\DTOs;

class GetAvailableSlotsResponse
{
    public function __construct(
        public readonly int $adminId,
        public readonly string $date,
        public readonly array $availableSlots
    ) {}

    public function toArray(): array
    {
        return [
            'admin_id' => $this->adminId,
            'date' => $this->date,
            'available_slots' => array_map(fn($slot) => $slot->toArray(), $this->availableSlots)
        ];
    }
}
