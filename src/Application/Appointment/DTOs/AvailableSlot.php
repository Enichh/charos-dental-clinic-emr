<?php

namespace CharosEMR\Application\Appointment\DTOs;

class AvailableSlot
{
    public function __construct(
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly bool $isAvailable
    ) {}

    public function toArray(): array
    {
        return [
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'is_available' => $this->isAvailable
        ];
    }
}
