<?php

namespace CharosEMR\Domain\Shared\ValueObjects;

class PhoneNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }
}
