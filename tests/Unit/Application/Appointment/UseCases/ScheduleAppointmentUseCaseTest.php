<?php

namespace CharosEMR\Tests\Unit\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentRequest;
use CharosEMR\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use CharosEMR\Application\Shared\Events\EventDispatcherInterface;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class ScheduleAppointmentUseCaseTest extends TestCase
{
    private AppointmentRepositoryInterface $repository;
    private EventDispatcherInterface $eventDispatcher;
    private ScheduleAppointmentUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = m::mock(AppointmentRepositoryInterface::class);
        $this->eventDispatcher = m::mock(EventDispatcherInterface::class);
        $this->useCase = new ScheduleAppointmentUseCase($this->repository, $this->eventDispatcher);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_schedules_appointment_successfully(): void
    {
        $request = new ScheduleAppointmentRequest(
            patientId: 1,
            dentistId: 2,
            scheduledDateTime: new \DateTime('2025-01-15 10:00:00'),
            notes: 'Regular checkup'
        );

        $this->repository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($appointment) {
                return $appointment instanceof Appointment
                    && $appointment->getPatientId() === 1
                    && $appointment->getDentistId() === 2
                    && $appointment->getStatus() === AppointmentStatus::SCHEDULED;
            }))
            ->andReturnUsing(function ($appointment) {
                $appointment->setId(123);
            });

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(m::type(\CharosEMR\Application\Appointment\Events\AppointmentBookedEvent::class));

        $response = $this->useCase->execute($request);

        $this->assertEquals(123, $response->appointmentId);
        $this->assertEquals('Appointment scheduled successfully', $response->message);
    }

    public function test_sets_default_status_to_scheduled(): void
    {
        $request = new ScheduleAppointmentRequest(
            patientId: 1,
            dentistId: 2,
            scheduledDateTime: new \DateTime('2025-01-15 10:00:00')
        );

        $this->repository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($appointment) {
                return $appointment->getStatus() === AppointmentStatus::SCHEDULED;
            }))
            ->andReturnUsing(function ($appointment) {
                $appointment->setId(123);
            });

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(m::type(\CharosEMR\Application\Appointment\Events\AppointmentBookedEvent::class));

        $response = $this->useCase->execute($request);
        $this->assertEquals(123, $response->appointmentId);
    }
}
