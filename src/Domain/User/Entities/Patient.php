<?php

namespace CharosEMR\Domain\User\Entities;

use CharosEMR\Domain\User\Enums\Gender;

class Patient
{
    private ?int $id;
    private string $name;
    private string $email;
    private string $passwordHash;
    private Gender $gender;
    private ?string $phoneNumber;
    private ?string $address;
    private ?\DateTime $dateOfBirth;

    public function __construct(
        ?int $id,
        string $name,
        string $email,
        string $passwordHash,
        Gender $gender,
        ?string $phoneNumber = null,
        ?string $address = null,
        ?\DateTime $dateOfBirth = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
        $this->address = $address;
        $this->dateOfBirth = $dateOfBirth;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
