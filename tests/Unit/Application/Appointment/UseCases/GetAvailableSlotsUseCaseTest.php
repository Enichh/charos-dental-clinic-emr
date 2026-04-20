<?php

namespace CharosEMR\Tests\Unit\Application\Appointment\UseCases;

use CharosEMR\Application\Appointment\DTOs\AvailableSlot;
use CharosEMR\Application\Appointment\DTOs\GetAvailableSlotsRequest;
use CharosEMR\Application\Appointment\DTOs\GetAvailableSlotsResponse;
use CharosEMR\Application\Appointment\UseCases\GetAvailableSlotsUseCase;
use CharosEMR\Domain\Appointment\Entities\AdminAvailability;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\Appointment\Repositories\AdminAvailabilityRepositoryInterface;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class GetAvailableSlotsUseCaseTest extends TestCase
{
    private AdminAvailabilityRepositoryInterface $availabilityRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private GetAvailableSlotsUseCase $useCase;

    protected function setUp(): void
    {
        $this->availabilityRepository = m::mock(AdminAvailabilityRepositoryInterface::class);
        $this->appointmentRepository = m::mock(AppointmentRepositoryInterface::class);
        $this->useCase = new GetAvailableSlotsUseCase($this->availabilityRepository, $this->appointmentRepository);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_returns_empty_slots_when_no_availability(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->with(1, m::on(function ($date) {
                return $date instanceof \DateTime && $date->format('Y-m-d') === '2025-01-15';
            }))
            ->andReturn(null);

        $response = $this->useCase->execute($request);

        $this->assertEquals(1, $response->adminId);
        $this->assertEquals('2025-01-15', $response->date);
        $this->assertEmpty($response->availableSlots);
    }

    public function test_returns_empty_slots_when_availability_inactive(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $availability = new AdminAvailability(
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:00',
            '17:00',
            30,
            false
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn($availability);

        $response = $this->useCase->execute($request);

        $this->assertEmpty($response->availableSlots);
    }

    public function test_generates_available_slots(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $availability = new AdminAvailability(
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:00',
            '10:00',
            30,
            true
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn($availability);

        $this->appointmentRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->with(1, m::type(\DateTime::class))
            ->andReturn([]);

        $response = $this->useCase->execute($request);

        $this->assertCount(2, $response->availableSlots);
        $this->assertEquals('09:00', $response->availableSlots[0]->startTime);
        $this->assertEquals('09:30', $response->availableSlots[0]->endTime);
        $this->assertEquals('09:30', $response->availableSlots[1]->startTime);
        $this->assertEquals('10:00', $response->availableSlots[1]->endTime);
        $this->assertTrue($response->availableSlots[0]->isAvailable);
        $this->assertTrue($response->availableSlots[1]->isAvailable);
    }

    public function test_marks_conflicting_slots_as_unavailable(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $availability = new AdminAvailability(
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:00',
            '10:00',
            30,
            true
        );

        $existingAppointment = new Appointment(
            1,
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:30',
            '10:00',
            AppointmentStatus::CONFIRMED
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn($availability);

        $this->appointmentRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn([$existingAppointment]);

        $response = $this->useCase->execute($request);

        $this->assertCount(2, $response->availableSlots);
        $this->assertTrue($response->availableSlots[0]->isAvailable);
        $this->assertFalse($response->availableSlots[1]->isAvailable);
    }

    public function test_ignores_cancelled_appointments_for_conflict_check(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $availability = new AdminAvailability(
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:00',
            '10:00',
            30,
            true
        );

        $cancelledAppointment = new Appointment(
            1,
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:30',
            '10:00',
            AppointmentStatus::CANCELLED
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn($availability);

        $this->appointmentRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn([$cancelledAppointment]);

        $response = $this->useCase->execute($request);

        $this->assertCount(2, $response->availableSlots);
        $this->assertTrue($response->availableSlots[0]->isAvailable);
        $this->assertTrue($response->availableSlots[1]->isAvailable);
    }

    public function test_throws_exception_when_slot_duration_is_zero(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $availability = new AdminAvailability(
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:00',
            '10:00',
            0,
            true
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn($availability);

        $this->appointmentRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Slot duration must be positive');

        $this->useCase->execute($request);
    }

    public function test_throws_exception_when_slot_duration_is_negative(): void
    {
        $request = new GetAvailableSlotsRequest(
            adminId: 1,
            date: new \DateTime('2025-01-15')
        );

        $availability = new AdminAvailability(
            1,
            1,
            new \DateTime('2025-01-15'),
            '09:00',
            '10:00',
            -30,
            true
        );

        $this->availabilityRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn($availability);

        $this->appointmentRepository->shouldReceive('findByAdminAndDate')
            ->once()
            ->andReturn([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Slot duration must be positive');

        $this->useCase->execute($request);
    }
}
