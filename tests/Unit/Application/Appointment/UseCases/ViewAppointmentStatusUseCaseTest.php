<?php

namespace CharosEMR\Tests\Unit\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\ViewAppointmentStatusRequest;
use CharosEMR\Application\Appointment\DTOs\ViewAppointmentStatusResponse;
use CharosEMR\Application\Appointment\UseCases\ViewAppointmentStatusUseCase;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class ViewAppointmentStatusUseCaseTest extends TestCase
{
    private AppointmentRepositoryInterface $appointmentRepository;
    private ViewAppointmentStatusUseCase $useCase;

    protected function setUp(): void
    {
        $this->appointmentRepository = m::mock(AppointmentRepositoryInterface::class);
        $this->useCase = new ViewAppointmentStatusUseCase($this->appointmentRepository);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_returns_appointment_status_successfully(): void
    {
        $request = new ViewAppointmentStatusRequest(
            appointmentId: 1
        );

        $appointment = new Appointment(
            1,
            1,
            2,
            new \DateTime('2025-01-15'),
            '10:00',
            '10:30',
            AppointmentStatus::CONFIRMED,
            'Regular checkup'
        );

        $this->appointmentRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($appointment);

        $response = $this->useCase->execute($request);

        $this->assertEquals(1, $response->appointmentId);
        $this->assertEquals(1, $response->patientId);
        $this->assertEquals(2, $response->adminId);
        $this->assertEquals('2025-01-15', $response->appointmentDate);
        $this->assertEquals('10:00', $response->startTime);
        $this->assertEquals('10:30', $response->endTime);
        $this->assertEquals('confirmed', $response->status);
        $this->assertEquals('Regular checkup', $response->notes);
    }

    public function test_throws_exception_when_appointment_not_found(): void
    {
        $request = new ViewAppointmentStatusRequest(
            appointmentId: 999
        );

        $this->appointmentRepository->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment not found');

        $this->useCase->execute($request);
    }

    public function test_returns_pending_status(): void
    {
        $request = new ViewAppointmentStatusRequest(
            appointmentId: 1
        );

        $appointment = new Appointment(
            1,
            1,
            2,
            new \DateTime('2025-01-15'),
            '10:00',
            '10:30',
            AppointmentStatus::PENDING
        );

        $this->appointmentRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($appointment);

        $response = $this->useCase->execute($request);

        $this->assertEquals('pending', $response->status);
    }

    public function test_returns_completed_status(): void
    {
        $request = new ViewAppointmentStatusRequest(
            appointmentId: 1
        );

        $appointment = new Appointment(
            1,
            1,
            2,
            new \DateTime('2025-01-15'),
            '10:00',
            '10:30',
            AppointmentStatus::COMPLETED
        );

        $this->appointmentRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($appointment);

        $response = $this->useCase->execute($request);

        $this->assertEquals('completed', $response->status);
    }

    public function test_returns_cancelled_status(): void
    {
        $request = new ViewAppointmentStatusRequest(
            appointmentId: 1
        );

        $appointment = new Appointment(
            1,
            1,
            2,
            new \DateTime('2025-01-15'),
            '10:00',
            '10:30',
            AppointmentStatus::CANCELLED
        );

        $this->appointmentRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($appointment);

        $response = $this->useCase->execute($request);

        $this->assertEquals('cancelled', $response->status);
    }
}
