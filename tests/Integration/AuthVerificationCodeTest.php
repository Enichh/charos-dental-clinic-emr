<?php

namespace CharosEMR\Tests\Integration;

use CharosEMR\Domain\Shared\Entities\VerificationCode;
use CharosEMR\Domain\Shared\Repositories\VerificationCodeRepositoryInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Application\Shared\Services\VerificationCodeService;
use PHPUnit\Framework\TestCase;

class AuthVerificationCodeTest extends TestCase
{
    private VerificationCodeRepositoryInterface $verificationCodeRepository;
    private UserRepositoryInterface $userRepository;
    private VerificationCodeService $verificationCodeService;
    private \PDO $pdo;

    protected function setUp(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();

        $container = require __DIR__ . '/../../config/container.php';

        $this->verificationCodeRepository = $container->get(VerificationCodeRepositoryInterface::class);
        $this->userRepository = $container->get(UserRepositoryInterface::class);
        $this->verificationCodeService = $container->get(VerificationCodeService::class);

        $this->pdo = $container->get(\PDO::class);

        // Clean up test data
        $this->pdo->exec("DELETE FROM verification_codes WHERE email = 'enocjastor@gmail.com'");
    }

    protected function tearDown(): void
    {
        $this->pdo->exec("DELETE FROM verification_codes WHERE email = 'enocjastor@gmail.com'");
    }

    public function test_generate_and_store_verification_code(): void
    {
        $email = 'enocjastor@gmail.com';
        $code = $this->verificationCodeService->generateCode();
        $expiresAt = $this->verificationCodeService->getExpiryTime();

        $verificationCode = new VerificationCode(
            null,
            $email,
            $code,
            'signup',
            $expiresAt
        );

        $this->verificationCodeRepository->save($verificationCode);

        $sent = $this->verificationCodeService->sendVerificationCode($email, $code, 'signup');
        $this->assertTrue($sent, 'Email should be sent successfully');

        $retrievedCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        $this->assertNotNull($retrievedCode);
        $this->assertEquals($email, $retrievedCode->getEmail());
        $this->assertEquals($code, $retrievedCode->getCode());
        $this->assertEquals('signup', $retrievedCode->getPurpose());
        $this->assertTrue($retrievedCode->isValid());
    }

    public function test_verification_code_expires(): void
    {
        $email = 'enocjastor@gmail.com';
        $code = '123456';
        $expiresAt = (new \DateTime())->modify('-1 minute');

        $verificationCode = new VerificationCode(
            null,
            $email,
            $code,
            'login',
            $expiresAt
        );

        $this->verificationCodeRepository->save($verificationCode);

        $retrievedCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        $this->assertNotNull($retrievedCode);
        $this->assertTrue($retrievedCode->isExpired());
        $this->assertFalse($retrievedCode->isValid());
    }

    public function test_verification_code_can_be_marked_as_used(): void
    {
        $email = 'enocjastor@gmail.com';
        $code = '654321';
        $expiresAt = $this->verificationCodeService->getExpiryTime();

        $verificationCode = new VerificationCode(
            null,
            $email,
            $code,
            'signup',
            $expiresAt
        );

        $this->verificationCodeRepository->save($verificationCode);

        $sent = $this->verificationCodeService->sendVerificationCode($email, $code, 'signup');
        $this->assertTrue($sent, 'Email should be sent successfully');

        $verificationCode->markAsUsed();
        $this->verificationCodeRepository->save($verificationCode);

        $retrievedCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        $this->assertNotNull($retrievedCode);
        $this->assertTrue($retrievedCode->isUsed());
        $this->assertFalse($retrievedCode->isValid());
    }

    public function test_invalidate_previous_codes(): void
    {
        $email = 'enocjastor@gmail.com';

        $code1 = '111111';
        $code2 = '222222';
        $expiresAt = $this->verificationCodeService->getExpiryTime();

        $verificationCode1 = new VerificationCode(
            null,
            $email,
            $code1,
            'login',
            $expiresAt
        );

        $verificationCode2 = new VerificationCode(
            null,
            $email,
            $code2,
            'login',
            $expiresAt
        );

        $this->verificationCodeRepository->save($verificationCode1);
        $this->verificationCodeRepository->save($verificationCode2);

        $this->verificationCodeRepository->invalidatePreviousCodes($email, 'login');

        $retrievedCode1 = $this->verificationCodeRepository->findByEmailAndCode($email, $code1);
        $retrievedCode2 = $this->verificationCodeRepository->findByEmailAndCode($email, $code2);

        $this->assertTrue($retrievedCode1->isUsed());
        $this->assertTrue($retrievedCode2->isUsed());
    }
}
