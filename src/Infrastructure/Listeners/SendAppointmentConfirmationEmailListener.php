<?php

namespace CharosEMR\Infrastructure\Listeners;

use CharosEMR\Application\Appointment\Events\AppointmentBookedEvent;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;

class SendAppointmentConfirmationEmailListener
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(AppointmentBookedEvent $event): void
    {
        $appointment = $event->getAppointment();

        $this->logger->info('Sending appointment confirmation email', [
            'appointment_id' => $appointment->getId(),
            'patient_id' => $appointment->getPatientId()
        ]);

        $patient = $this->userRepository->findById($appointment->getPatientId());

        if (!$patient) {
            $this->logger->error('Patient not found for appointment confirmation email', [
                'appointment_id' => $appointment->getId(),
                'patient_id' => $appointment->getPatientId()
            ]);
            return;
        }

        $subject = 'Appointment Confirmation - Charos Dental Clinic';
        $body = $this->generateEmailBody($appointment, $patient);

        try {
            $this->mailer->send($patient->getEmail(), $subject, $body);
            $this->logger->info('Appointment confirmation email sent successfully', [
                'appointment_id' => $appointment->getId(),
                'patient_id' => $appointment->getPatientId(),
                'patient_email' => $patient->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send appointment confirmation email', [
                'appointment_id' => $appointment->getId(),
                'patient_id' => $appointment->getPatientId(),
                'patient_email' => $patient->getEmail(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
