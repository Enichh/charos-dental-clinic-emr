<?php

namespace CharosEMR\Tests\Unit\Application\Shared\Services;

use CharosEMR\Application\Shared\Services\VerificationCodeService;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class VerificationCodeServiceTest extends TestCase
{
    private VerificationCodeService $service;
    private MailerInterface $mailer;

    protected function setUp(): void
    {
        $this->mailer = Mockery::mock(MailerInterface::class);
        $this->service = new VerificationCodeService($this->mailer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_generate_code_returns_6_digits(): void
    {
        $code = $this->service->generateCode();

        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_generate_code_is_numeric(): void
    {
        $code = $this->service->generateCode();

        $this->assertIsNumeric($code);
    }

    public function test_get_expiry_time_returns_future_datetime(): void
    {
        $expiryTime = $this->service->getExpiryTime();
        $now = new \DateTime();

        $this->assertInstanceOf(\DateTime::class, $expiryTime);
        $this->assertGreaterThan($now, $expiryTime);
    }

    public function test_get_expiry_time_is_15_minutes_ahead(): void
    {
        $expiryTime = $this->service->getExpiryTime();
        $now = new \DateTime();
        $interval = $now->diff($expiryTime);

        $this->assertGreaterThanOrEqual(14, $interval->i);
        $this->assertLessThanOrEqual(16, $interval->i);
    }

    public function test_send_verification_code_for_signup(): void
    {
        $email = 'test@example.com';
        $code = '123456';
        $purpose = 'signup';

        $this->mailer->shouldReceive('send')
            ->once()
            ->with($email, \Mockery::pattern('/Verify Your Email/'), \Mockery::type('string'))
            ->andReturn(true);

        $result = $this->service->sendVerificationCode($email, $code, $purpose);

        $this->assertTrue($result);
    }

    public function test_send_verification_code_for_login(): void
    {
        $email = 'test@example.com';
        $code = '123456';
        $purpose = 'login';

        $this->mailer->shouldReceive('send')
            ->once()
            ->with($email, \Mockery::pattern('/Your Login Code/'), \Mockery::type('string'))
            ->andReturn(true);

        $result = $this->service->sendVerificationCode($email, $code, $purpose);

        $this->assertTrue($result);
    }

    public function test_send_verification_code_returns_false_on_failure(): void
    {
        $email = 'test@example.com';
        $code = '123456';
        $purpose = 'signup';

        $this->mailer->shouldReceive('send')
            ->once()
            ->andReturn(false);

        $result = $this->service->sendVerificationCode($email, $code, $purpose);

        $this->assertFalse($result);
    }
}
