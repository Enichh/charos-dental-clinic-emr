<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container = require __DIR__ . '/config/container.php';

// Get command from CLI arguments
$command = $argv[1] ?? null;

if (!$command) {
    echo "Available commands:\n";
    echo "  cleanup:verification-codes  - Delete expired verification codes\n";
    echo "  send:appointment-reminders  - Send appointment reminder emails\n";
    echo "Usage: php console.php <command>\n";
    exit(1);
}

switch ($command) {
    case 'cleanup:verification-codes':
        $verificationCodeRepository = $container->get(
            CharosEMR\Domain\Shared\Repositories\VerificationCodeRepositoryInterface::class
        );
        $verificationCodeRepository->deleteExpired();
        echo "Expired verification codes cleaned up successfully.\n";
        break;

    case 'send:appointment-reminders':
        $reminderTemplates = new \CharosEMR\Infrastructure\EmailTemplates\AppointmentReminderTemplates();
        $mailer = $container->get(\CharosEMR\Application\Shared\Interfaces\MailerInterface::class);
        $userRepository = $container->get(\CharosEMR\Domain\User\Repositories\UserRepositoryInterface::class);

        $pdo = $container->get(\PDO::class);

        $now = new \DateTime();
        $hours48 = $now->modify('+48 hours')->format('Y-m-d H:i:s');
        $now = new \DateTime();
        $hours24 = $now->modify('+24 hours')->format('Y-m-d H:i:s');
        $now = new \DateTime();
        $hours2 = $now->modify('+2 hours')->format('Y-m-d H:i:s');

        // Query appointments that need reminders
        $stmt = $pdo->prepare("
            SELECT a.*, p.first_name, p.last_name, p.email 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            WHERE a.status IN ('pending', 'confirmed')
            AND (
                CONCAT(a.appointment_date, ' ', a.start_time) BETWEEN :now48 AND :now48_end
                OR CONCAT(a.appointment_date, ' ', a.start_time) BETWEEN :now24 AND :now24_end
                OR CONCAT(a.appointment_date, ' ', a.start_time) BETWEEN :now2 AND :now2_end
            )
        ");

        $now = new \DateTime();
        $stmt->execute([
            ':now48' => $now->format('Y-m-d H:i:s'),
            ':now48_end' => $now->modify('+1 hour')->format('Y-m-d H:i:s'),
            ':now24' => $now->modify('-47 hours')->format('Y-m-d H:i:s'),
            ':now24_end' => $now->modify('+1 hour')->format('Y-m-d H:i:s'),
            ':now2' => $now->modify('-22 hours')->format('Y-m-d H:i:s'),
            ':now2_end' => $now->modify('+1 hour')->format('Y-m-d H:i:s'),
        ]);

        $appointments = $stmt->fetchAll();

        foreach ($appointments as $appointment) {
            $appointmentDateTime = new \DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
            $interval = $now->diff($appointmentDateTime);
            $hoursUntil = $interval->h + ($interval->days * 24);

            $patient = [
                'first_name' => $appointment['first_name'],
                'last_name' => $appointment['last_name'],
                'email' => $appointment['email']
            ];

            $appointmentData = [
                'appointment_date' => $appointmentDateTime->format('l, F j, Y'),
                'start_time' => $appointment['start_time'],
                'end_time' => $appointment['end_time']
            ];

            $subject = 'Appointment Reminder - Charos Dental Clinic';

            if ($hoursUntil >= 47 && $hoursUntil <= 49) {
                $body = $reminderTemplates->get48HourReminder($appointmentData, $patient);
                $subject = 'Appointment Reminder (48 Hours) - Charos Dental Clinic';
            } elseif ($hoursUntil >= 23 && $hoursUntil <= 25) {
                $body = $reminderTemplates->get24HourReminder($appointmentData, $patient);
                $subject = 'Appointment Tomorrow (24 Hours) - Charos Dental Clinic';
            } elseif ($hoursUntil >= 1 && $hoursUntil <= 3) {
                $body = $reminderTemplates->get2HourReminder($appointmentData, $patient);
                $subject = 'Appointment in 2 Hours - Charos Dental Clinic';
            } else {
                continue;
            }

            try {
                $mailer->send($patient['email'], $subject, $body);
                echo "Sent reminder to {$patient['email']} for appointment at {$appointmentData['appointment_date']}\n";
            } catch (\Exception $e) {
                echo "Failed to send reminder to {$patient['email']}: {$e->getMessage()}\n";
            }
        }

        echo "Appointment reminders sent successfully.\n";
        break;

    default:
        echo "Unknown command: $command\n";
        echo "Available commands:\n";
        echo "  cleanup:verification-codes  - Delete expired verification codes\n";
        echo "  send:appointment-reminders  - Send appointment reminder emails\n";
        exit(1);
}
