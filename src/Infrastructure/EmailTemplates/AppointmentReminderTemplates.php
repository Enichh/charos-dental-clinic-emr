<?php

namespace CharosEMR\Infrastructure\EmailTemplates;

class AppointmentReminderTemplates
{
    private string $clinicName;
    private string $clinicEmail;
    private string $clinicPhone;
    private string $clinicAddress;
    private string $basePath;

    public function __construct()
    {
        $this->clinicName = $_ENV['CLINIC_NAME'] ?? 'Charos Dental Clinic';
        $this->clinicEmail = $_ENV['CLINIC_EMAIL'] ?? 'clinic@charosdental.com';
        $this->clinicPhone = $_ENV['CLINIC_PHONE'] ?? '+1 (555) 123-4567';
        $this->clinicAddress = $_ENV['CLINIC_ADDRESS'] ?? '123 Dental Street, City, State 12345';
        $this->basePath = $_ENV['BASE_PATH'] ?? '';
    }

    public function get48HourReminder(array $appointment, array $patient): string
    {
        $appointmentDate = $appointment['appointment_date'];
        $startTime = $appointment['start_time'];
        $endTime = $appointment['end_time'];
        $patientName = $patient['first_name'] ?? 'Patient';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reminder - {$this->clinicName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">{$this->clinicName}</h1>
            <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #667eea; font-size: 24px;">Appointment Reminder (48 Hours)</h2>
            
            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear {$patientName},</p>
            
            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">This is a friendly reminder about your upcoming dental appointment in 48 hours. Please review the details below:</p>

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
                If you need to reschedule or cancel, please do so at least 24 hours before your appointment to avoid any fees.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$this->basePath}/appointments" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">Manage Appointment</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                <strong>{$this->clinicName}</strong><br>
                {$this->clinicAddress}<br>
                Contact: {$this->clinicEmail}<br>
                Phone: {$this->clinicPhone}
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

    public function get24HourReminder(array $appointment, array $patient): string
    {
        $appointmentDate = $appointment['appointment_date'];
        $startTime = $appointment['start_time'];
        $endTime = $appointment['end_time'];
        $patientName = $patient['first_name'] ?? 'Patient';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reminder - {$this->clinicName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">{$this->clinicName}</h1>
            <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #667eea; font-size: 24px;">Appointment Tomorrow (24 Hours)</h2>
            
            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear {$patientName},</p>
            
            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">Your dental appointment is tomorrow! Here are the details:</p>

            <!-- Appointment Details Box -->
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 25px; margin: 25px 0; border-radius: 4px;">
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #856404; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Appointment Details</p>
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
                <strong style="color: #ffc107;">Please arrive 15 minutes before your scheduled time</strong> to complete any necessary paperwork.
            </p>

            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">
                If you need to reschedule or cancel, please contact us as soon as possible.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$this->basePath}/appointments" style="display: inline-block; background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">Manage Appointment</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                <strong>{$this->clinicName}</strong><br>
                {$this->clinicAddress}<br>
                Contact: {$this->clinicEmail}<br>
                Phone: {$this->clinicPhone}
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

    public function get2HourReminder(array $appointment, array $patient): string
    {
        $appointmentDate = $appointment['appointment_date'];
        $startTime = $appointment['start_time'];
        $endTime = $appointment['end_time'];
        $patientName = $patient['first_name'] ?? 'Patient';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Reminder - {$this->clinicName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">{$this->clinicName}</h1>
            <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #667eea; font-size: 24px;">Appointment in 2 Hours</h2>
            
            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear {$patientName},</p>
            
            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">Your dental appointment is coming up very soon! Here are the details:</p>

            <!-- Appointment Details Box -->
            <div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 25px; margin: 25px 0; border-radius: 4px;">
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #155724; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Appointment Details</p>
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
                <strong style="color: #28a745;">Please arrive 15 minutes before your scheduled time</strong> to complete any necessary paperwork.
            </p>

            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">
                We're looking forward to seeing you! If you're running late or need to reschedule, please call us immediately.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="tel:{$this->clinicPhone}" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">Call Us: {$this->clinicPhone}</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                <strong>{$this->clinicName}</strong><br>
                {$this->clinicAddress}<br>
                Contact: {$this->clinicEmail}<br>
                Phone: {$this->clinicPhone}
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

    public function getAdminCancellation(array $appointment, array $patient, string $reason = ''): string
    {
        $appointmentDate = $appointment['appointment_date'];
        $startTime = $appointment['start_time'];
        $endTime = $appointment['end_time'];
        $patientName = $patient['first_name'] ?? 'Patient';

        $reasonHtml = '';
        if ($reason) {
            $reasonHtml = '<div style="margin: 0 0 15px 0;"><span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Reason:</span><span style="color: #333333;">' . htmlspecialchars($reason) . '</span></div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Cancelled - {$this->clinicName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">{$this->clinicName}</h1>
            <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #dc3545; font-size: 24px;">Appointment Cancelled by Clinic</h2>
            
            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear {$patientName},</p>
            
            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">We sincerely apologize, but your dental appointment has been cancelled by our clinic.</p>

            <!-- Cancelled Appointment Details Box -->
            <div style="background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 25px; margin: 25px 0; border-radius: 4px;">
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #721c24; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Cancelled Appointment Details</p>
                <div style="margin: 0 0 15px 0;">
                    <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Date:</span>
                    <span style="color: #333333;">{$appointmentDate}</span>
                </div>
                <div style="margin: 0 0 15px 0;">
                    <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Time:</span>
                    <span style="color: #333333;">{$startTime} - {$endTime}</span>
                </div>
                {$reasonHtml}
            </div>

            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">
                We understand this is inconvenient and apologize for any disruption to your schedule.
            </p>

            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">
                Please book a new appointment at your earliest convenience. We will do our best to accommodate your preferred time.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$this->basePath}/appointments/create" style="display: inline-block; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">Book New Appointment</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                <strong>{$this->clinicName}</strong><br>
                {$this->clinicAddress}<br>
                Contact: {$this->clinicEmail}<br>
                Phone: {$this->clinicPhone}
            </p>
            <p style="margin: 15px 0 0 0; font-size: 12px; color: #999999;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function getUserCancellation(array $appointment, array $patient): string
    {
        $appointmentDate = $appointment['appointment_date'];
        $startTime = $appointment['start_time'];
        $endTime = $appointment['end_time'];
        $patientName = $patient['first_name'] ?? 'Patient';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Cancelled - {$this->clinicName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">{$this->clinicName}</h1>
            <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #667eea; font-size: 24px;">Appointment Cancelled</h2>
            
            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear {$patientName},</p>
            
            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">Your appointment has been successfully cancelled as requested.</p>

            <!-- Cancelled Appointment Details Box -->
            <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 25px; margin: 25px 0; border-radius: 4px;">
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #666666; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Cancelled Appointment Details</p>
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
                We hope to see you again soon. Feel free to book a new appointment whenever you're ready.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$this->basePath}/appointments/create" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">Book New Appointment</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                <strong>{$this->clinicName}</strong><br>
                {$this->clinicAddress}<br>
                Contact: {$this->clinicEmail}<br>
                Phone: {$this->clinicPhone}
            </p>
            <p style="margin: 15px 0 0 0; font-size: 12px; color: #999999;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function getUserRescheduling(array $appointment, array $patient, array $newAppointment): string
    {
        $oldDate = $appointment['appointment_date'];
        $oldStartTime = $appointment['start_time'];
        $oldEndTime = $appointment['end_time'];

        $newDate = $newAppointment['appointment_date'];
        $newStartTime = $newAppointment['start_time'];
        $newEndTime = $newAppointment['end_time'];

        $patientName = $patient['first_name'] ?? 'Patient';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Rescheduled - {$this->clinicName}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">{$this->clinicName}</h1>
            <p style="margin: 10px 0 0 0; color: #e0e0e0; font-size: 16px;">Your Smile, Our Priority</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #28a745; font-size: 24px;">Appointment Rescheduled Successfully</h2>
            
            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">Dear {$patientName},</p>
            
            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">Your appointment has been rescheduled as requested. Here are the details:</p>

            <!-- Old Appointment Details Box -->
            <div style="background-color: #f8f9fa; border-left: 4px solid #6c757d; padding: 25px; margin: 25px 0; border-radius: 4px;">
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Previous Appointment</p>
                <div style="margin: 0 0 15px 0;">
                    <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Date:</span>
                    <span style="color: #333333; text-decoration: line-through;">{$oldDate}</span>
                </div>
                <div style="margin: 0 0 15px 0;">
                    <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Time:</span>
                    <span style="color: #333333; text-decoration: line-through;">{$oldStartTime} - {$oldEndTime}</span>
                </div>
            </div>

            <!-- New Appointment Details Box -->
            <div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 25px; margin: 25px 0; border-radius: 4px;">
                <p style="margin: 0 0 12px 0; font-size: 14px; color: #155724; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">New Appointment</p>
                <div style="margin: 0 0 15px 0;">
                    <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Date:</span>
                    <span style="color: #333333; font-weight: 600;">{$newDate}</span>
                </div>
                <div style="margin: 0 0 15px 0;">
                    <span style="display: inline-block; width: 80px; font-weight: 600; color: #333333;">Time:</span>
                    <span style="color: #333333; font-weight: 600;">{$newStartTime} - {$newEndTime}</span>
                </div>
            </div>

            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6;">
                <strong style="color: #28a745;">Please arrive 15 minutes before your scheduled time</strong> to complete any necessary paperwork.
            </p>

            <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.6;">
                If you need to make any further changes, please contact us at least 24 hours before your appointment.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$this->basePath}/appointments" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px;">View Appointments</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666;">
                <strong>{$this->clinicName}</strong><br>
                {$this->clinicAddress}<br>
                Contact: {$this->clinicEmail}<br>
                Phone: {$this->clinicPhone}
            </p>
            <p style="margin: 15px 0 0 0; font-size: 12px; color: #999999;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
