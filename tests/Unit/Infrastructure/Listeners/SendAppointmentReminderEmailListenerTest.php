<?php

namespace CharosEMR\Tests\Unit\Infrastructure\Listeners;

use CharosEMR\Infrastructure\Listeners\SendAppointmentReminderEmailListener;
use CharosEMR\Application\Appointment\Events\AppointmentReminderEvent;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\User\Entities\User;
use CharosEMR\Domain\User\Enums\UserRole;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class SendAppointmentReminderEmailListenerTest extends TestCase
{
    private SendAppointmentReminderEmailListener $listener;
    private MailerInterface $mailer;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->mailer = Mockery::mock(MailerInterface::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->listener = new SendAppointmentReminderEmailListener($this->mailer, $this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_sends_reminder_email(): void
    {
        $appointmentDate = new \DateTime('2025-05-01');
        $appointment = new Appointment(
            null,
            1,
            2,
            $appointmentDate,
            '09:00',
            '10:00',
            AppointmentStatus::CONFIRMED
        );

        $patient = new User(
            1,
            'patient@example.com',
            'hashed_password',
            UserRole::PATIENT
        );

        $event = new AppointmentReminderEvent($appointment);

        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($patient);

        $this->mailer->shouldReceive('send')
            ->once()
            ->with('patient@example.com', \Mockery::pattern('/Appointment Reminder/'), \Mockery::type('string'))
            ->andReturn(true);

        ($this->listener)($event);

        $this->assertTrue(true);
    }

    public function test_does_not_send_email_if_patient_not_found(): void
    {
        $appointmentDate = new \DateTime('2025-05-01');
        $appointment = new Appointment(
            null,
            1,
            2,
            $appointmentDate,
            '09:00',
            '10:00',
            AppointmentStatus::CONFIRMED
        );

        $event = new AppointmentReminderEvent($appointment);

        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(null);

        $this->mailer->shouldNotReceive('send');

        ($this->listener)($event);

        $this->assertTrue(true);
    }

    public function test_email_body_contains_appointment_details(): void
    {
        $appointmentDate = new \DateTime('2025-05-01');
        $appointment = new Appointment(
            null,
            1,
            2,
            $appointmentDate,
            '09:00',
            '10:00',
            AppointmentStatus::CONFIRMED
        );

        $patient = new User(
            1,
            'patient@example.com',
            'hashed_password',
            UserRole::PATIENT
        );

        $event = new AppointmentReminderEvent($appointment);

        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($patient);

        $this->mailer->shouldReceive('send')
            ->once()
            ->with('patient@example.com', \Mockery::type('string'), \Mockery::on(function ($body) {
                return str_contains($body, 'May 1, 2025') && str_contains($body, '09:00');
            }))
            ->andReturn(true);

        ($this->listener)($event);

        $this->assertTrue(true);
    }
}
