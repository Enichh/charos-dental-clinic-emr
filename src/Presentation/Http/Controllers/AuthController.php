<?php

namespace CharosEMR\Presentation\Http\Controllers;

use CharosEMR\Application\Shared\Services\VerificationCodeService;
use CharosEMR\Domain\Shared\Entities\VerificationCode;
use CharosEMR\Domain\Shared\Repositories\VerificationCodeRepositoryInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;

class AuthController
{
    public function __construct(
        private VerificationCodeService $verificationCodeService,
        private VerificationCodeRepositoryInterface $verificationCodeRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher
    ) {}

    public function sendLoginCode()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address']);
            return;
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $code = $this->verificationCodeService->generateCode();
        $expiresAt = $this->verificationCodeService->getExpiryTime();

        $this->verificationCodeRepository->invalidatePreviousCodes($email, 'login');

        $verificationCode = new VerificationCode(
            null,
            $email,
            $code,
            'login',
            $expiresAt
        );

        $this->verificationCodeRepository->save($verificationCode);

        $sent = $this->verificationCodeService->sendVerificationCode($email, $code, 'login');

        if ($sent) {
            echo json_encode(['message' => 'Verification code sent', 'expires_in' => '15 minutes']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send verification code']);
        }
    }

    public function verifyAndLogin()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $code = $input['code'] ?? '';

        if (empty($email) || empty($code)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and code are required']);
            return;
        }

        $verificationCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        if (!$verificationCode || !$verificationCode->isValid()) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired verification code']);
            return;
        }

        $verificationCode->markAsUsed();
        $this->verificationCodeRepository->save($verificationCode);

        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $user->updateLastLogin();
            $this->userRepository->save($user);
        }

        echo json_encode([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->value
            ]
        ]);
    }

    public function sendSignupCode()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $name = $input['name'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email address']);
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters']);
            return;
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already registered']);
            return;
        }

        $code = $this->verificationCodeService->generateCode();
        $expiresAt = $this->verificationCodeService->getExpiryTime();

        $this->verificationCodeRepository->invalidatePreviousCodes($email, 'signup');

        $verificationCode = new VerificationCode(
            null,
            $email,
            $code,
            'signup',
            $expiresAt
        );

        $this->verificationCodeRepository->save($verificationCode);

        $sent = $this->verificationCodeService->sendVerificationCode($email, $code, 'signup');

        if ($sent) {
            echo json_encode(['message' => 'Verification code sent', 'expires_in' => '15 minutes']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send verification code']);
        }
    }

    public function verifyAndRegister()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $code = $input['code'] ?? '';
        $password = $input['password'] ?? '';
        $name = $input['name'] ?? '';

        if (empty($email) || empty($code) || empty($password) || empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required']);
            return;
        }

        $verificationCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        if (!$verificationCode || !$verificationCode->isValid()) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired verification code']);
            return;
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already registered']);
            return;
        }

        $passwordHash = $this->passwordHasher->hash($password);

        $user = new \CharosEMR\Domain\User\Entities\User(
            null,
            $email,
            $passwordHash,
            \CharosEMR\Domain\User\Enums\UserRole::PATIENT
        );

        $this->userRepository->save($user);

        $verificationCode->markAsUsed();
        $this->verificationCodeRepository->save($verificationCode);

        echo json_encode([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->value
            ]
        ]);
    }

    public function logout()
    {
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Logout endpoint']);
    }
}
