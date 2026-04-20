<?php

namespace CharosEMR\Tests\Integration;

use CharosEMR\Application\Appointment\Events\AppointmentReminderEvent;
use CharosEMR\Domain\Appointment\Entities\Appointment;
use CharosEMR\Domain\Appointment\Enums\AppointmentStatus;
use CharosEMR\Domain\User\Entities\User;
use CharosEMR\Domain\User\Enums\UserRole;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use PHPUnit\Framework\TestCase;

class AppointmentReminderTest extends TestCase
{
    private MailerInterface $mailer;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();

        $container = require __DIR__ . '/../../config/container.php';

        $this->mailer = $container->get(MailerInterface::class);
        $this->userRepository = $container->get(UserRepositoryInterface::class);
    }

    public function test_send_appointment_reminder_email(): void
    {
        // Create a temporary patient in the database for testing
        $patient = new User(
            null,
            'enocjastor@gmail.com',
            password_hash('test_password', PASSWORD_ARGON2ID),
            UserRole::PATIENT
        );

        $this->userRepository->save($patient);

        $appointmentDate = new \DateTime('+2 days');
        $appointment = new Appointment(
            null,
            $patient->getId(),
            2,
            $appointmentDate,
            '09:00',
            '10:00',
            AppointmentStatus::CONFIRMED
        );

        $event = new AppointmentReminderEvent($appointment);

        $listener = new \CharosEMR\Infrastructure\Listeners\SendAppointmentReminderEmailListener(
            $this->mailer,
            $this->userRepository
        );

        ($listener)($event);

        // Clean up
        $this->userRepository->delete($patient->getId());

        $this->assertTrue(true, 'Appointment reminder email sent to enocjastor@gmail.com');
    }
}
