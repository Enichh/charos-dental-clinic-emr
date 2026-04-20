<?php

namespace CharosEMR\Presentation\Http\Controllers;

use CharosEMR\Application\Shared\Services\VerificationCodeService;
use CharosEMR\Application\Shared\Services\AuditLogger;
use CharosEMR\Application\Shared\Services\RateLimiter;
use CharosEMR\Application\Shared\Services\CsrfProtectionService;
use CharosEMR\Application\Shared\Services\MfaService;
use CharosEMR\Domain\Shared\Entities\VerificationCode;
use CharosEMR\Domain\Shared\Repositories\VerificationCodeRepositoryInterface;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;
use CharosEMR\Domain\User\Enums\Gender;

class AuthController
{
    public function __construct(
        private VerificationCodeService $verificationCodeService,
        private VerificationCodeRepositoryInterface $verificationCodeRepository,
        private UserRepositoryInterface $userRepository,
        private PatientRepositoryInterface $patientRepository,
        private PasswordHasherInterface $passwordHasher,
        private RateLimiter $rateLimiter,
        private AuditLogger $auditLogger,
        private CsrfProtectionService $csrfService,
        private MfaService $mfaService
    ) {}

    public function getCsrfToken()
    {
        header('Content-Type: application/json');
        echo json_encode(['csrf_token' => $this->csrfService->generateToken()]);
    }

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

        // Rate limiting: max 5 requests per 5 minutes per email
        if (!$this->rateLimiter->checkLimit('login_code_' . $email, 5, 300)) {
            $retryAfter = $this->rateLimiter->getRetryAfter('login_code_' . $email, 300);
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests', 'retry_after' => $retryAfter]);
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
            // Rate limiting: max 5 failed attempts per 5 minutes
            if (!$this->rateLimiter->checkLimit('login_attempt_' . $email, 5, 300)) {
                $retryAfter = $this->rateLimiter->getRetryAfter('login_attempt_' . $email, 300);
                http_response_code(429);
                echo json_encode(['error' => 'Too many failed attempts', 'retry_after' => $retryAfter]);
                return;
            }

            $this->auditLogger->logLogin($email, false);
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

            // Reset rate limiter on successful login
            $this->rateLimiter->reset('login_attempt_' . $email);

            // Set session data
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_email'] = $user->getEmail();
            $_SESSION['user_role'] = $user->getRole()->value;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['last_activity'] = time();

            $this->auditLogger->logLogin((string)$user->getId(), true);
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

        // Create User record
        $user = new \CharosEMR\Domain\User\Entities\User(
            null,
            $email,
            $passwordHash,
            \CharosEMR\Domain\User\Enums\UserRole::PATIENT
        );

        $this->userRepository->save($user);

        // Create Patient record
        $patient = new \CharosEMR\Domain\User\Entities\Patient(
            null,
            $name,
            $email,
            $passwordHash,
            Gender::OTHER, // Default gender, can be updated later
            null,
            null,
            null
        );

        // Set the user_id for the patient
        $patient->setId($user->getId());
        $this->patientRepository->save($patient);

        // Set session data
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole()->value;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();

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

    public function showLogin()
    {
        $title = 'Login - Charos Dental Clinic EMR';

        ob_start();
        require __DIR__ . '/../../Views/auth/login.php';
        $content = ob_get_clean();

        require __DIR__ . '/../../Views/layouts/main.php';
    }

    public function showVerify()
    {
        $title = 'Verify Email - Charos Dental Clinic EMR';

        ob_start();
        require __DIR__ . '/../../Views/auth/verify.php';
        $content = ob_get_clean();

        require __DIR__ . '/../../Views/layouts/main.php';
    }

    public function showPatientDashboard()
    {
        // TODO: Get patient data from session or repository
        $patientName = $_SESSION['user_name'] ?? 'Patient';
        $patientEmail = $_SESSION['user_email'] ?? '';
        $patientPhone = $_SESSION['user_phone'] ?? '';
        $patientDob = $_SESSION['user_dob'] ?? '';
        $bloodType = $_SESSION['blood_type'] ?? '';
        $allergies = $_SESSION['allergies'] ?? '';
        $lastVisit = $_SESSION['last_visit'] ?? '';
        $nextVisit = $_SESSION['next_visit'] ?? '';

        // TODO: Fetch upcoming appointments and prescriptions from repositories
        $upcomingAppointments = [];
        $recentPrescriptions = [];

        $title = 'Patient Dashboard - Charos Dental Clinic EMR';
        $customCss = '/css/patient-dashboard.css';

        ob_start();
        require __DIR__ . '/../../Views/patient/dashboard.php';
        $content = ob_get_clean();

        require __DIR__ . '/../../Views/layouts/main.php';
    }

    public function logout()
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;

        // Destroy session
        session_unset();
        session_destroy();

        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        if ($userId) {
            $this->auditLogger->logLogout((string)$userId);
        }

        echo json_encode(['message' => 'Logout successful']);
    }

    public function setupMfa()
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $user = $this->userRepository->findById((int)$userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $secret = $this->mfaService->generateSecret();
        $qrCodeUri = $this->mfaService->generateQrCodeUri($secret, $user->getEmail());
        $backupCodes = $this->mfaService->generateBackupCodes();

        // Store MFA secret temporarily in session for verification
        $_SESSION['mfa_setup_secret'] = $secret;
        $_SESSION['mfa_backup_codes'] = $backupCodes;

        $this->auditLogger->logMfaSetup((string)$userId);

        echo json_encode([
            'secret' => $secret,
            'qr_code_uri' => $qrCodeUri,
            'backup_codes' => $backupCodes
        ]);
    }

    public function enableMfa()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $secret = $_SESSION['mfa_setup_secret'] ?? null;
        if (!$secret) {
            http_response_code(400);
            echo json_encode(['error' => 'MFA setup not initiated']);
            return;
        }

        if (!$this->mfaService->verifyCode($secret, $code)) {
            $this->auditLogger->logMfaVerification((string)$userId, false);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid verification code']);
            return;
        }

        // In a real implementation, you would store the MFA secret in the database
        // For now, we'll store it in session (not production-ready)
        $_SESSION['mfa_enabled'] = true;
        $_SESSION['mfa_secret'] = $secret;
        $_SESSION['mfa_backup_codes'] = $_SESSION['mfa_backup_codes'] ?? [];

        // Clear temporary setup data
        unset($_SESSION['mfa_setup_secret']);

        $this->auditLogger->logMfaVerification((string)$userId, true);

        echo json_encode(['message' => 'MFA enabled successfully']);
    }

    public function verifyMfa()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $secret = $_SESSION['mfa_secret'] ?? null;
        if (!$secret) {
            http_response_code(400);
            echo json_encode(['error' => 'MFA not enabled']);
            return;
        }

        // Check if it's a backup code
        if ($this->mfaService->validateBackupCodeFormat($code)) {
            $backupCodes = $_SESSION['mfa_backup_codes'] ?? [];
            if (in_array(strtolower($code), array_map('strtolower', $backupCodes))) {
                // Remove used backup code
                $backupCodes = array_filter($backupCodes, fn($c) => strtolower($c) !== strtolower($code));
                $_SESSION['mfa_backup_codes'] = array_values($backupCodes);

                $this->auditLogger->logMfaVerification((string)$userId, true);
                echo json_encode(['message' => 'Backup code verified']);
                return;
            }
        }

        // Verify TOTP code
        if (!$this->mfaService->verifyCode($secret, $code)) {
            $this->auditLogger->logMfaVerification((string)$userId, false);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid verification code']);
            return;
        }

        $this->auditLogger->logMfaVerification((string)$userId, true);
        echo json_encode(['message' => 'MFA verified successfully']);
    }

    public function disableMfa()
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        unset($_SESSION['mfa_enabled']);
        unset($_SESSION['mfa_secret']);
        unset($_SESSION['mfa_backup_codes']);

        $this->auditLogger->log('MFA_DISABLED', (string)$userId, [], true);

        echo json_encode(['message' => 'MFA disabled successfully']);
    }
}
