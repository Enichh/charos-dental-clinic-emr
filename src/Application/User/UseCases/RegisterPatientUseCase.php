<?php

namespace CharosEMR\Application\User\UseCases;

use CharosEMR\Application\User\DTOs\RegisterPatientRequest;
use CharosEMR\Application\User\DTOs\RegisterPatientResponse;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;
use CharosEMR\Domain\User\Entities\User;
use CharosEMR\Domain\User\Entities\Patient;
use CharosEMR\Domain\User\Enums\UserRole;
use CharosEMR\Domain\User\Enums\Gender;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;

class RegisterPatientUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PatientRepositoryInterface $patientRepository,
        private PasswordHasherInterface $passwordHasher
    ) {}

    /** Register a new patient with user account and profile */
    public function execute(RegisterPatientRequest $request): RegisterPatientResponse
    {
        $existingUser = $this->userRepository->findByEmail($request->email);
        if ($existingUser !== null) {
            throw new \InvalidArgumentException('Registration failed');
        }

        try {
            $dateOfBirth = new \DateTime($request->dateOfBirth);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format');
        }

        $passwordHash = $this->passwordHasher->hash($request->password);

        try {
            $user = new User(
                null,
                $request->email,
                $passwordHash,
                UserRole::PATIENT,
                true
            );

            $this->userRepository->save($user);

            $patient = new Patient(
                null,
                $user->getId(),
                $request->firstName,
                $request->lastName,
                $dateOfBirth,
                Gender::from($request->gender),
                $request->phoneNumber,
                $request->address,
                $request->bloodType,
                $request->allergies
            );

            $this->patientRepository->save($patient);

            return new RegisterPatientResponse(
                $user->getId(),
                $patient->getId(),
                'Registration successful'
            );
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                throw new \InvalidArgumentException('Registration failed');
            }
            throw $e;
        }
    }
}
