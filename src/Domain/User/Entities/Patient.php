<?php

namespace CharosEMR\Domain\User\Entities;

use CharosEMR\Domain\User\Enums\Gender;

class Patient
{
    private ?int $id;
    private ?int $userId;
    private string $firstName;
    private string $lastName;
    private \DateTime $dateOfBirth;
    private Gender $gender;
    private ?string $phoneNumber;
    private ?string $address;
    private ?string $bloodType;
    private ?string $allergies;

    public function __construct(
        ?int $id,
        ?int $userId,
        string $firstName,
        string $lastName,
        \DateTime $dateOfBirth,
        Gender $gender,
        ?string $phoneNumber = null,
        ?string $address = null,
        ?string $bloodType = null,
        ?string $allergies = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
        $this->address = $address;
        $this->bloodType = $bloodType;
        $this->allergies = $allergies;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getDateOfBirth(): \DateTime
    {
        return $this->dateOfBirth;
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

    public function getBloodType(): ?string
    {
        return $this->bloodType;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /** Update patient profile fields in-place */
    public function updateProfile(
        string $firstName,
        string $lastName,
        \DateTime $dateOfBirth,
        Gender $gender,
        ?string $phoneNumber = null,
        ?string $address = null,
        ?string $bloodType = null,
        ?string $allergies = null
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->dateOfBirth = $dateOfBirth;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
        $this->address = $address;
        $this->bloodType = $bloodType;
        $this->allergies = $allergies;
    }
}
