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
            adminId: 2,
            appointmentDate: new \DateTime('+1 week'),
            startTime: '10:00',
            endTime: '10:30',
            notes: 'Regular checkup'
        );

        $this->repository->shouldReceive('findConflictingAppointments')
            ->once()
            ->with(2, m::type(\DateTime::class), '10:00', '10:30')
            ->andReturn([]);

        $this->repository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($appointment) {
                return $appointment instanceof Appointment
                    && $appointment->getPatientId() === 1
                    && $appointment->getAdminId() === 2
                    && $appointment->getStatus() === AppointmentStatus::PENDING;
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

    public function test_sets_default_status_to_pending(): void
    {
        $request = new ScheduleAppointmentRequest(
            patientId: 1,
            adminId: 2,
            appointmentDate: new \DateTime('+1 week'),
            startTime: '10:00',
            endTime: '10:30'
        );

        $this->repository->shouldReceive('findConflictingAppointments')
            ->once()
            ->with(2, m::type(\DateTime::class), '10:00', '10:30')
            ->andReturn([]);

        $this->repository->shouldReceive('save')
            ->once()
            ->with(m::on(function ($appointment) {
                return $appointment->getStatus() === AppointmentStatus::PENDING;
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

    public function test_throws_exception_when_slot_already_booked(): void
    {
        $request = new ScheduleAppointmentRequest(
            patientId: 1,
            adminId: 2,
            appointmentDate: new \DateTime('+1 week'),
            startTime: '10:00',
            endTime: '10:30'
        );

        $this->repository->shouldReceive('findConflictingAppointments')
            ->once()
            ->with(2, m::type(\DateTime::class), '10:00', '10:30')
            ->andReturn([m::mock(Appointment::class)]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This time slot is already booked');

        $this->useCase->execute($request);
    }

    public function test_throws_exception_when_appointment_date_is_in_past(): void
    {
        $request = new ScheduleAppointmentRequest(
            patientId: 1,
            adminId: 2,
            appointmentDate: new \DateTime('-1 day'),
            startTime: '10:00',
            endTime: '10:30'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment date must be in the future');

        $this->useCase->execute($request);
    }

    public function test_throws_exception_when_start_time_after_end_time(): void
    {
        $request = new ScheduleAppointmentRequest(
            patientId: 1,
            adminId: 2,
            appointmentDate: new \DateTime('+1 week'),
            startTime: '11:00',
            endTime: '10:30'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start time must be before end time');

        $this->useCase->execute($request);
    }
}
