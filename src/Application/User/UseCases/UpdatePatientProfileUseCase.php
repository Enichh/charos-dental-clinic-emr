<?php

namespace CharosEMR\Application\User\UseCases;

use CharosEMR\Application\User\DTOs\UpdatePatientProfileRequest;
use CharosEMR\Application\User\DTOs\UpdatePatientProfileResponse;
use CharosEMR\Domain\User\Entities\Patient;
use CharosEMR\Domain\User\Enums\Gender;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;

class UpdatePatientProfileUseCase
{
    public function __construct(
        private PatientRepositoryInterface $patientRepository
    ) {}

    /** Update patient profile with authorization check */
    public function execute(UpdatePatientProfileRequest $request, int $authenticatedUserId): UpdatePatientProfileResponse
    {
        if ($authenticatedUserId !== $request->userId) {
            throw new \InvalidArgumentException('Unauthorized');
        }

        $patient = $this->patientRepository->findByUserId($request->userId);
        if ($patient === null) {
            throw new \InvalidArgumentException('Patient not found');
        }

        try {
            $dateOfBirth = $request->dateOfBirth ? new \DateTime($request->dateOfBirth) : null;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format');
        }

        $patient->updateProfile(
            $request->firstName,
            $request->lastName,
            $dateOfBirth,
            Gender::from($request->gender),
            $request->phoneNumber,
            $request->address
        );

        $this->patientRepository->save($patient);

        return new UpdatePatientProfileResponse(
            $patient->getId(),
            'Profile updated successfully'
        );
    }
}
