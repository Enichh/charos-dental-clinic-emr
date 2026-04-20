<?php

namespace CharosEMR\Infrastructure\Services;

use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class SymfonyMailerAdapter implements MailerInterface
{
    private Mailer $mailer;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        ?string $dsn = null,
        string $fromEmail = 'noreply@charosdental.com',
        string $fromName = 'Charos Dental Clinic'
    ) {
        $dsn = $dsn ?? $_ENV['MAILER_DSN'] ?? 'smtp://localhost:25';
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $email = (new Email())
                ->from("{$this->fromName} <{$this->fromEmail}>")
                ->to($to)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
            return false;
        }
    }
}
