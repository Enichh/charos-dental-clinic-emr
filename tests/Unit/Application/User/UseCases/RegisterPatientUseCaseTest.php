<?php

namespace CharosEMR\Tests\Unit\Application\User\UseCases;

use CharosEMR\Application\User\DTOs\RegisterPatientRequest;
use CharosEMR\Application\User\DTOs\RegisterPatientResponse;
use CharosEMR\Application\User\UseCases\RegisterPatientUseCase;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;
use CharosEMR\Domain\User\Entities\User;
use CharosEMR\Domain\User\Entities\Patient;
use CharosEMR\Domain\User\Enums\UserRole;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class RegisterPatientUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private PatientRepositoryInterface $patientRepository;
    private PasswordHasherInterface $passwordHasher;
    private RegisterPatientUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepository = m::mock(UserRepositoryInterface::class);
        $this->patientRepository = m::mock(PatientRepositoryInterface::class);
        $this->passwordHasher = m::mock(PasswordHasherInterface::class);
        $this->useCase = new RegisterPatientUseCase($this->userRepository, $this->patientRepository, $this->passwordHasher);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_registers_patient_successfully(): void
    {
        $request = new RegisterPatientRequest(
            email: 'patient@test.com',
            password: 'password123',
            name: 'John Doe',
            gender: 'male',
            phoneNumber: '+1234567890',
            address: '123 Test St',
            dateOfBirth: '1990-01-15'
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('patient@test.com')
            ->andReturn(null);

        $this->passwordHasher->shouldReceive('hash')
            ->once()
            ->with('password123')
            ->andReturn('hashed_password');

        $this->userRepository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($user) {
                return $user instanceof User
                    && $user->getEmail() === 'patient@test.com'
                    && $user->getRole() === UserRole::PATIENT
                    && $user->isActive() === true;
            }))
            ->andReturnUsing(function ($user) {
                $user->setId(1);
            });

        $this->patientRepository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($patient) {
                return $patient instanceof Patient
                    && $patient->getName() === 'John Doe'
                    && $patient->getUserId() === 1;
            }))
            ->andReturnUsing(function ($patient) {
                $patient->setId(1);
                return $patient;
            });

        $response = $this->useCase->execute($request);

        $this->assertEquals(1, $response->userId);
        $this->assertEquals(1, $response->patientId);
        $this->assertEquals('Registration successful', $response->message);
    }

    public function test_throws_exception_when_email_already_registered(): void
    {
        $request = new RegisterPatientRequest(
            email: 'existing@test.com',
            password: 'password123',
            name: 'John Doe',
            gender: 'male'
        );

        $existingUser = new User(
            1,
            'existing@test.com',
            'hashed_password',
            UserRole::PATIENT
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('existing@test.com')
            ->andReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Registration failed');

        $this->useCase->execute($request);
    }

    public function test_handles_optional_fields(): void
    {
        $request = new RegisterPatientRequest(
            email: 'patient@test.com',
            password: 'password123',
            name: 'Jane Doe',
            gender: 'female'
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('patient@test.com')
            ->andReturn(null);

        $this->passwordHasher->shouldReceive('hash')
            ->once()
            ->with('password123')
            ->andReturn('hashed_password');

        $this->userRepository->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($user) {
                $user->setId(2);
            });

        $this->patientRepository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($patient) {
                return $patient instanceof Patient
                    && $patient->getName() === 'Jane Doe'
                    && $patient->getUserId() === 2
                    && $patient->getPhoneNumber() === null
                    && $patient->getAddress() === null
                    && $patient->getDateOfBirth() === null;
            }))
            ->andReturnUsing(function ($patient) {
                $patient->setId(2);
                return $patient;
            });

        $response = $this->useCase->execute($request);

        $this->assertEquals(2, $response->userId);
        $this->assertEquals(2, $response->patientId);
    }
}
