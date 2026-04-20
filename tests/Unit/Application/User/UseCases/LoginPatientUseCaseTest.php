<?php

namespace CharosEMR\Tests\Unit\Application\User\UseCases;

use CharosEMR\Application\User\DTOs\LoginPatientRequest;
use CharosEMR\Application\User\DTOs\LoginPatientResponse;
use CharosEMR\Application\User\UseCases\LoginPatientUseCase;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;
use CharosEMR\Domain\User\Entities\User;
use CharosEMR\Domain\User\Enums\UserRole;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class LoginPatientUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private PasswordHasherInterface $passwordHasher;
    private LoginPatientUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepository = m::mock(UserRepositoryInterface::class);
        $this->passwordHasher = m::mock(PasswordHasherInterface::class);
        $this->useCase = new LoginPatientUseCase($this->userRepository, $this->passwordHasher);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_logs_in_patient_successfully(): void
    {
        $request = new LoginPatientRequest(
            email: 'patient@test.com',
            password: 'password123'
        );

        $user = new User(
            1,
            'patient@test.com',
            'hashed_password',
            UserRole::PATIENT,
            true
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('patient@test.com')
            ->andReturn($user);

        $this->passwordHasher->shouldReceive('verify')
            ->once()
            ->with('password123', 'hashed_password')
            ->andReturn(true);

        $this->userRepository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($u) {
                return $u instanceof User && $u->getLastLogin() !== null;
            }));

        $response = $this->useCase->execute($request);

        $this->assertEquals(1, $response->userId);
        $this->assertEquals('patient@test.com', $response->email);
        $this->assertEquals('patient', $response->role);
        $this->assertEquals('Login successful', $response->message);
    }

    public function test_throws_exception_when_user_not_found(): void
    {
        $request = new LoginPatientRequest(
            email: 'nonexistent@test.com',
            password: 'password123'
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('nonexistent@test.com')
            ->andReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->useCase->execute($request);
    }

    public function test_throws_exception_when_user_inactive(): void
    {
        $request = new LoginPatientRequest(
            email: 'inactive@test.com',
            password: 'password123'
        );

        $user = new User(
            1,
            'inactive@test.com',
            'hashed_password',
            UserRole::PATIENT,
            false
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('inactive@test.com')
            ->andReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->useCase->execute($request);
    }

    public function test_throws_exception_when_password_invalid(): void
    {
        $request = new LoginPatientRequest(
            email: 'patient@test.com',
            password: 'wrongpassword'
        );

        $user = new User(
            1,
            'patient@test.com',
            'hashed_password',
            UserRole::PATIENT,
            true
        );

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('patient@test.com')
            ->andReturn($user);

        $this->passwordHasher->shouldReceive('verify')
            ->once()
            ->with('wrongpassword', 'hashed_password')
            ->andReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->useCase->execute($request);
    }
}
