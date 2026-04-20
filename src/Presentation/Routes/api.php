<?php

namespace CharosEMR\Presentation\Routes;

use CharosEMR\Presentation\Http\Controllers\AuthController;
use CharosEMR\Presentation\Http\Controllers\AppointmentController;
use CharosEMR\Presentation\Http\Controllers\PrescriptionController;
use CharosEMR\Presentation\Http\Controllers\PatientController;
use CharosEMR\Presentation\Http\Controllers\HomeController;
use CharosEMR\Presentation\Http\Middlewares\AuthMiddleware;

/**
 * API Routes Configuration
 *
 * Middleware Configuration:
 * - Routes can specify middleware as an array of container entry names
 * - Pre-configured middleware instances are defined in config/container.php:
 *   - 'middleware.auth.patient': AuthMiddleware with requiredRole='patient', requireCsrf=true
 *   - 'middleware.auth.admin': AuthMiddleware with requiredRole='admin', requireCsrf=true
 * - To add new middleware configurations, add them to container.php and reference by name here
 */
return [
    // Root route
    'GET /' => [HomeController::class, 'index'],
    'GET /index.php' => [HomeController::class, 'index'],

    // API Routes
    'GET /api/csrf-token' => ['controller' => [AuthController::class, 'getCsrfToken']],
    'POST /api/auth/send-login-code' => ['controller' => [AuthController::class, 'sendLoginCode']],
    'POST /api/auth/verify-login' => ['controller' => [AuthController::class, 'verifyAndLogin']],
    'POST /api/auth/send-signup-code' => ['controller' => [AuthController::class, 'sendSignupCode']],
    'POST /api/auth/verify-signup' => ['controller' => [AuthController::class, 'verifyAndRegister']],
    'POST /api/auth/logout' => ['controller' => [AuthController::class, 'logout']],
    'POST /api/auth/setup-mfa' => ['controller' => [AuthController::class, 'setupMfa']],
    'POST /api/auth/enable-mfa' => ['controller' => [AuthController::class, 'enableMfa']],
    'POST /api/auth/verify-mfa' => ['controller' => [AuthController::class, 'verifyMfa']],
    'POST /api/auth/disable-mfa' => ['controller' => [AuthController::class, 'disableMfa']],

    'POST /api/appointments' => [
        'controller' => [AppointmentController::class, 'store'],
        'middleware' => ['middleware.auth.patient']
    ],
    'POST /api/appointments/cancel' => [
        'controller' => [AppointmentController::class, 'cancel'],
        'middleware' => ['middleware.auth.patient']
    ],

    'POST /api/prescriptions' => [
        'controller' => [PrescriptionController::class, 'store'],
        'middleware' => ['middleware.auth.admin']
    ],

    // Patient Routes
    'GET /patient/dashboard' => [
        'controller' => [PatientController::class, 'dashboard'],
        'middleware' => ['middleware.auth.patient']
    ],
    'GET /patient/book-appointment' => [
        'controller' => [PatientController::class, 'bookAppointment'],
        'middleware' => ['middleware.auth.patient']
    ],
    'GET /patient/appointment-status' => [
        'controller' => [PatientController::class, 'appointmentStatus'],
        'middleware' => ['middleware.auth.patient']
    ],
    'GET /patient/profile' => [
        'controller' => [PatientController::class, 'profile'],
        'middleware' => ['middleware.auth.patient']
    ],
    'POST /api/patient/appointments/available-slots' => [
        'controller' => [PatientController::class, 'getAvailableSlots'],
        'middleware' => ['middleware.auth.patient']
    ],
    'POST /api/patient/appointments/schedule' => [
        'controller' => [PatientController::class, 'scheduleAppointment'],
        'middleware' => ['middleware.auth.patient']
    ],
    'POST /api/patient/appointments/cancel' => [
        'controller' => [PatientController::class, 'cancelAppointment'],
        'middleware' => ['middleware.auth.patient']
    ],
    'POST /api/patient/profile' => [
        'controller' => [PatientController::class, 'updateProfile'],
        'middleware' => ['middleware.auth.patient']
    ],

    // HTML View Routes
    'GET /auth/login' => [AuthController::class, 'showLogin'],
    'GET /auth/register' => [AuthController::class, 'showLogin'],
    'GET /auth/verify' => [AuthController::class, 'showVerify'],
    'GET /appointments' => [AppointmentController::class, 'index'],
    'GET /appointments/create' => [AppointmentController::class, 'create'],
];
