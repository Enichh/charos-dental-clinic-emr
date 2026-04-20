<?php

namespace CharosEMR\Presentation\Http\Controllers;

use CharosEMR\Application\Shared\Services\VerificationCodeService;
use CharosEMR\Application\Shared\Services\AuditLogger;
use CharosEMR\Application\Shared\Services\RateLimiter;
use CharosEMR\Application\Shared\Services\CsrfProtectionService;
use CharosEMR\Application\Shared\Services\MfaService;
use CharosEMR\Application\Shared\Validation\SchemaValidator;
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
        private MfaService $mfaService,
        private SchemaValidator $schemaValidator
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
        $password = $input['password'] ?? '';

        // Validate email and password using SchemaValidator
        $validationResult = $this->schemaValidator->validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        if ($validationResult->hasErrors()) {
            $this->auditLogger->log('LOGIN_VALIDATION_FAILED', $email, ['errors' => $validationResult->getErrors()], false);
            http_response_code(400);
            echo json_encode(['error' => implode(', ', $validationResult->getErrors()['password'] ?? $validationResult->getErrors()['email'] ?? ['Validation failed'])]);
            return;
        }

        // Rate limiting: max 5 requests per 5 minutes per email
        if (!$this->rateLimiter->checkLimit('login_code_' . $email, 5, 300)) {
            $retryAfter = $this->rateLimiter->getRetryAfter('login_code_' . $email, 300);
            $this->auditLogger->log('LOGIN_RATE_LIMIT_EXCEEDED', $email, ['retry_after' => $retryAfter], false);
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests', 'retry_after' => $retryAfter]);
            return;
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            $this->auditLogger->log('LOGIN_USER_NOT_FOUND', $email, [], false);
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        // Verify password against stored hash
        if (!$this->passwordHasher->verify($password, $user->getPasswordHash())) {
            $this->auditLogger->log('LOGIN_INVALID_PASSWORD', $email, ['user_id' => $user->getId()], false);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
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
            $this->auditLogger->log('LOGIN_CODE_SENT', $email, ['expires_at' => $expiresAt->format('Y-m-d H:i:s')], true);
            echo json_encode(['message' => 'Verification code sent', 'expires_in' => '15 minutes']);
        } else {
            $this->auditLogger->log('LOGIN_CODE_SEND_FAILED', $email, [], false);
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
        $password = $input['password'] ?? '';

        error_log("verifyAndLogin called: email=" . $email);

        if (empty($email) || empty($code) || empty($password)) {
            $this->auditLogger->log('LOGIN_MISSING_FIELDS', $email, [], false);
            http_response_code(400);
            echo json_encode(['error' => 'Email, code, and password are required']);
            return;
        }

        // Verify password first
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            $this->auditLogger->log('LOGIN_VERIFY_USER_NOT_FOUND', $email, [], false);
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        if (!$this->passwordHasher->verify($password, $user->getPasswordHash())) {
            $this->auditLogger->log('LOGIN_VERIFY_INVALID_PASSWORD', $email, ['user_id' => $user->getId()], false);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        $verificationCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        if (!$verificationCode || !$verificationCode->isValid()) {
            // Rate limiting: max 5 failed attempts per 5 minutes
            if (!$this->rateLimiter->checkLimit('login_attempt_' . $email, 5, 300)) {
                $retryAfter = $this->rateLimiter->getRetryAfter('login_attempt_' . $email, 300);
                $this->auditLogger->log('LOGIN_VERIFY_RATE_LIMIT_EXCEEDED', $email, ['retry_after' => $retryAfter], false);
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

        // User already fetched and verified above
        $user->updateLastLogin();
        $this->userRepository->save($user);

        // Reset rate limiter on successful login
        $this->rateLimiter->reset('login_attempt_' . $email);

        $this->auditLogger->log('LOGIN_SUCCESS', (string)$user->getId(), ['email' => $email], true);

        // Set session data
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole()->value;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();

        $this->auditLogger->logLogin((string)$user->getId(), true);

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
        $firstName = $input['first_name'] ?? '';
        $lastName = $input['last_name'] ?? '';
        $dateOfBirth = $input['date_of_birth'] ?? '';
        $gender = $input['gender'] ?? '';
        $phoneNumber = $input['phone_number'] ?? '';
        $address = $input['address'] ?? '';
        $bloodType = $input['blood_type'] ?? '';
        $allergies = $input['allergies'] ?? '';

        $this->auditLogger->log('SIGNUP_CODE_REQUEST', $email, ['email' => $email], true);

        // Validate required fields
        $validationResult = $this->schemaValidator->validate([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender
        ], [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'first_name' => 'required|min:2|max:100',
            'last_name' => 'required|min:2|max:100',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone_number' => 'max:20',
            'address' => 'max:500',
            'blood_type' => 'max:5',
            'allergies' => 'max:1000'
        ]);

        if ($validationResult->hasErrors()) {
            $this->auditLogger->log('SIGNUP_VALIDATION_FAILED', $email, ['errors' => $validationResult->getErrors()], false);
            http_response_code(400);
            echo json_encode(['error' => implode(', ', $validationResult->getErrors()[array_key_first($validationResult->getErrors())])]);
            return;
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            $this->auditLogger->log('SIGNUP_EMAIL_EXISTS', $email, ['user_id' => $existingUser->getId()], false);
            http_response_code(409);
            echo json_encode(['error' => 'This email is already registered. Please login instead or use a different email address.']);
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
            $this->auditLogger->log('SIGNUP_CODE_SENT', $email, ['expires_at' => $expiresAt->format('Y-m-d H:i:s')], true);
            echo json_encode(['message' => 'Verification code sent', 'expires_in' => '15 minutes']);
        } else {
            $this->auditLogger->log('SIGNUP_CODE_SEND_FAILED', $email, [], false);
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
        $firstName = $input['first_name'] ?? '';
        $lastName = $input['last_name'] ?? '';
        $dateOfBirth = $input['date_of_birth'] ?? '';
        $gender = $input['gender'] ?? '';
        $phoneNumber = $input['phone_number'] ?? '';
        $address = $input['address'] ?? '';
        $bloodType = $input['blood_type'] ?? '';
        $allergies = $input['allergies'] ?? '';

        $this->auditLogger->log('SIGNUP_VERIFY_REQUEST', $email, ['email' => $email], true);

        if (empty($email) || empty($code) || empty($password) || empty($firstName) || empty($lastName) || empty($dateOfBirth) || empty($gender)) {
            $this->auditLogger->log('SIGNUP_VERIFY_MISSING_FIELDS', $email, [], false);
            http_response_code(400);
            echo json_encode(['error' => 'All required fields must be provided']);
            return;
        }

        $verificationCode = $this->verificationCodeRepository->findByEmailAndCode($email, $code);

        if (!$verificationCode || !$verificationCode->isValid()) {
            $this->auditLogger->log('SIGNUP_VERIFY_INVALID_CODE', $email, [], false);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired verification code']);
            return;
        }

        try {
            $dateOfBirthObj = new \DateTime($dateOfBirth);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date of birth format']);
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
            $user->getId(),
            $firstName,
            $lastName,
            $dateOfBirthObj,
            Gender::from($gender),
            $phoneNumber ?: null,
            $address ?: null,
            $bloodType ?: null,
            $allergies ?: null
        );

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

        $this->auditLogger->log('SIGNUP_SUCCESS', (string)$user->getId(), ['email' => $email, 'role' => $user->getRole()->value], true);

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
