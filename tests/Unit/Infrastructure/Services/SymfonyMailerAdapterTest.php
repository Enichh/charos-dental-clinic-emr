<?php

namespace CharosEMR\Tests\Unit\Infrastructure\Services;

use CharosEMR\Infrastructure\Services\SymfonyMailerAdapter;
use PHPUnit\Framework\TestCase;

class SymfonyMailerAdapterTest extends TestCase
{
    private SymfonyMailerAdapter $mailer;

    protected function setUp(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../');
        $dotenv->load();

        $appPassword = $_ENV['APP_PASSWORD'] ?? '';
        $dsn = "gmail://charosdental:" . rawurlencode($appPassword) . "@default";

        $this->mailer = new SymfonyMailerAdapter(
            dsn: $dsn,
            fromEmail: 'charosdental@gmail.com',
            fromName: 'Charos Dental Clinic'
        );
    }

    public function test_send_email_via_gmail_smtp(): void
    {
        $to = 'enocjastor@gmail.com';
        $subject = 'Test Email from Charos Dental Clinic';
        $body = '<h1>Test Email</h1><p>This is a test email sent via Gmail SMTP using Symfony Mailer.</p>';

        $result = $this->mailer->send($to, $subject, $body);

        $this->assertTrue($result, 'Email should be sent successfully via Gmail SMTP');
    }
}
