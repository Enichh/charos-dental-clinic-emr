<?php

namespace CharosEMR\Infrastructure\Listeners;

use CharosEMR\Application\Appointment\Events\AppointmentBookedEvent;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;

class SendAppointmentConfirmationEmailListener
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(AppointmentBookedEvent $event): void
    {
        $appointment = $event->getAppointment();
        $patient = $this->userRepository->findById($appointment->getPatientId());

        if (!$patient) {
            return;
        }

        $subject = 'Appointment Confirmation - Charos Dental Clinic';
        $body = $this->generateEmailBody($appointment, $patient);

        $this->mailer->send($patient->getEmail(), $subject, $body);
    }

    private function generateEmailBody($appointment, $patient): string
    {
        $dateTime = $appointment->getScheduledDateTime()->format('F j, Y \a\t g:i A');

        return <<<HTML
        <html>
        <body>
            <h2>Appointment Confirmation</h2>
            <p>Dear {$patient->getName()},</p>
            <p>Your appointment has been successfully scheduled.</p>
            <p><strong>Date & Time:</strong> {$dateTime}</p>
            <p><strong>Status:</strong> {$appointment->getStatus()->value}</p>
            <p>Please arrive 15 minutes before your scheduled time.</p>
            <p>If you need to reschedule, please call us.</p>
            <br>
            <p>Best regards,<br>Charos Dental Clinic</p>
        </body>
        </html>
        HTML;
    }
}
