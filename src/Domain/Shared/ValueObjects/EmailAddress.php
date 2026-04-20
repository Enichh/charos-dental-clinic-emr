<?php

namespace CharosEMR\Domain\Shared\ValueObjects;

class EmailAddress
{
    private string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(EmailAddress $other): bool
    {
        return $this->value === $other->value;
    }
}
