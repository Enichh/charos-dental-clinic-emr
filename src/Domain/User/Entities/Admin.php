<?php

namespace CharosEMR\Domain\User\Entities;

use CharosEMR\Domain\User\Enums\Gender;

class Admin
{
    private ?int $id;
    private string $name;
    private string $email;
    private string $passwordHash;
    private Gender $gender;
    private ?string $phoneNumber;

    public function __construct(
        ?int $id,
        string $name,
        string $email,
        string $passwordHash,
        Gender $gender,
        ?string $phoneNumber = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
