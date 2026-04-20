<?php

namespace CharosEMR\Tests\Unit\Application\User\UseCases;

use CharosEMR\Application\User\DTOs\UpdatePatientProfileRequest;
use CharosEMR\Application\User\DTOs\UpdatePatientProfileResponse;
use CharosEMR\Application\User\UseCases\UpdatePatientProfileUseCase;
use CharosEMR\Domain\User\Entities\Patient;
use CharosEMR\Domain\User\Enums\Gender;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class UpdatePatientProfileUseCaseTest extends TestCase
{
    private PatientRepositoryInterface $patientRepository;
    private UpdatePatientProfileUseCase $useCase;

    protected function setUp(): void
    {
        $this->patientRepository = m::mock(PatientRepositoryInterface::class);
        $this->useCase = new UpdatePatientProfileUseCase($this->patientRepository);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_updates_patient_profile_successfully(): void
    {
        $request = new UpdatePatientProfileRequest(
            userId: 1,
            name: 'John Updated',
            gender: 'male',
            phoneNumber: '+9876543210',
            address: '456 New St',
            dateOfBirth: '1985-05-20'
        );

        $existingPatient = new Patient(
            1,
            1,
            'John Doe',
            'patient@test.com',
            'hashed_password',
            Gender::MALE,
            '+1234567890',
            '123 Old St',
            new \DateTime('1990-01-15')
        );

        $this->patientRepository->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($existingPatient);

        $this->patientRepository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($patient) {
                return $patient instanceof Patient
                    && $patient->getName() === 'John Updated'
                    && $patient->getPhoneNumber() === '+9876543210'
                    && $patient->getAddress() === '456 New St'
                    && $patient->getDateOfBirth()->format('Y-m-d') === '1985-05-20'
                    && $patient->getEmail() === 'patient@test.com'
                    && $patient->getPasswordHash() === 'hashed_password';
            }))
            ->andReturnUsing(function ($patient) {
                return $patient;
            });

        $response = $this->useCase->execute($request, 1);

        $this->assertEquals(1, $response->patientId);
        $this->assertEquals('Profile updated successfully', $response->message);
    }

    public function test_throws_exception_when_patient_not_found(): void
    {
        $request = new UpdatePatientProfileRequest(
            userId: 999,
            name: 'John Doe',
            gender: 'male'
        );

        $this->patientRepository->shouldReceive('findByUserId')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Patient not found');

        $this->useCase->execute($request, 999);
    }

    public function test_handles_optional_fields(): void
    {
        $request = new UpdatePatientProfileRequest(
            userId: 1,
            name: 'Jane Updated',
            gender: 'female'
        );

        $existingPatient = new Patient(
            1,
            1,
            'Jane Doe',
            'patient@test.com',
            'hashed_password',
            Gender::FEMALE,
            '+1234567890',
            '123 Old St',
            new \DateTime('1990-01-15')
        );

        $this->patientRepository->shouldReceive('findByUserId')
            ->once()
            ->with(1)
            ->andReturn($existingPatient);

        $this->patientRepository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($patient) {
                return $patient instanceof Patient
                    && $patient->getName() === 'Jane Updated'
                    && $patient->getPhoneNumber() === null
                    && $patient->getAddress() === null
                    && $patient->getDateOfBirth() === null
                    && $patient->getEmail() === 'patient@test.com'
                    && $patient->getPasswordHash() === 'hashed_password';
            }))
            ->andReturnUsing(function ($patient) {
                return $patient;
            });

        $response = $this->useCase->execute($request, 1);

        $this->assertEquals(1, $response->patientId);
    }

    public function test_throws_exception_when_unauthorized(): void
    {
        $request = new UpdatePatientProfileRequest(
            userId: 1,
            name: 'John Updated',
            gender: 'male'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->useCase->execute($request, 999);
    }
}
