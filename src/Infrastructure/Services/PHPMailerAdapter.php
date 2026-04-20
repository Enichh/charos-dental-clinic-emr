<?php

namespace CharosEMR\Infrastructure\Services;

use CharosEMR\Application\Shared\Interfaces\MailerInterface;

class PHPMailerAdapter implements MailerInterface
{
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        string $fromEmail = 'noreply@charosdental.com',
        string $fromName = 'Charos Dental Clinic'
    ) {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Content-Type' => 'text/html; charset=UTF-8',
            'MIME-Version' => '1.0'
        ];

        $headersString = '';
        foreach ($headers as $key => $value) {
            $headersString .= "$key: $value\r\n";
        }

        return mail($to, $subject, $body, $headersString);
    }
}
