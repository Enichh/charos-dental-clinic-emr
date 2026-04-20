<?php

namespace CharosEMR\Infrastructure\Listeners;

use CharosEMR\Application\Appointment\Events\AppointmentReminderEvent;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;

class SendAppointmentReminderEmailListener
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepositoryInterface $userRepository
    ) {}

    public function __invoke(AppointmentReminderEvent $event): void
    {
        $appointment = $event->getAppointment();
        $patient = $this->userRepository->findById($appointment->getPatientId());

        if (!$patient) {
            return;
        }

        $subject = 'Appointment Reminder - Charos Dental Clinic';
        $body = $this->generateEmailBody($appointment, $patient);

        $this->mailer->send($patient->getEmail(), $subject, $body);
    }

    private function generateEmailBody($appointment, $patient): string
    {
        $appointmentDate = $appointment->getAppointmentDate()->format('l, F j, Y');
        $startTime = $appointment->getStartTime();
        $endTime = $appointment->getEndTime();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Appointment Reminder - Charos Dental Clinic</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">Charos Dental Clinic</h1>
                    <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
                </div>

                <!-- Content -->
                <div style="padding: 40px 30px;">
                    <h2 style="margin: 0 0 20px 0; color: #667eea; font-size: 24px;">Appointment Reminder</h2>
                    
                    <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear Patient,</p>
                    
                    <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">This is a friendly reminder about your upcoming dental appointment. Please review the details below:</p>

                    <!-- Appointment Details Box -->
                    <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 25px; margin: 25px 0; border-radius: 4px;">
                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #666666; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Appointment Details</p>
                        <div style="margin: 0 0 15px 0;">
                            <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Date:</span>
                            <span style="color: #333333;">{$appointmentDate}</span>
                        </div>
                        <div style="margin: 0 0 15px 0;">
                            <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Time:</span>
                            <span style="color: #333333;">{$startTime} - {$endTime}</span>
                        </div>
                    </div>

                    <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">
                        <strong style="color: #667eea;">Please arrive 15 minutes before your scheduled time</strong> to complete any necessary paperwork.
                    </p>

                    <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">
                        Please reschedule or cancel on our site if you need to change your appointment time.
                    </p>

                    <!-- CTA Button -->
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="#" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">Visit Our Site</a>
                    </div>
                </div>

                <!-- Footer -->
                <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                        <strong>Charos Dental Clinic</strong><br>
                        Contact: clinic@charosdental.com<br>
                        Phone: +1 (555) 123-4567
                    </p>
                    <p style="margin: 15px 0 0 0; font-size: 12px; color: #999999;">
                        This is an automated reminder. Please do not reply to this email.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
