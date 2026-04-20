<?php

namespace CharosEMR\Presentation\Routes;

use CharosEMR\Presentation\Http\Controllers\AuthController;
use CharosEMR\Presentation\Http\Controllers\AppointmentController;
use CharosEMR\Presentation\Http\Controllers\PrescriptionController;
use CharosEMR\Presentation\Http\Middlewares\AuthMiddleware;
use CharosEMR\Presentation\Http\Middlewares\RoleMiddleware;

return [
    // API Routes
    'GET /api/csrf-token' => ['controller' => [AuthController::class, 'getCsrfToken']],
    'POST /api/auth/send-login-code' => ['controller' => [AuthController::class, 'sendLoginCode']],
    'POST /api/auth/verify-login' => ['controller' => [AuthController::class, 'verifyAndLogin']],
    'POST /api/auth/send-signup-code' => ['controller' => [AuthController::class, 'sendSignupCode']],
    'POST /api/auth/verify-signup' => ['controller' => [AuthController::class, 'verifyAndRegister']],
    'POST /api/auth/logout' => ['controller' => [AuthController::class, 'logout']],

    'POST /api/appointments' => [
        'controller' => [AppointmentController::class, 'store'],
        'middleware' => [AuthMiddleware::class, RoleMiddleware::class]
    ],
    'POST /api/appointments/cancel' => [
        'controller' => [AppointmentController::class, 'cancel'],
        'middleware' => [AuthMiddleware::class, RoleMiddleware::class]
    ],

    'POST /api/prescriptions' => [
        'controller' => [PrescriptionController::class, 'store'],
        'middleware' => [AuthMiddleware::class, RoleMiddleware::class]
    ],

    // HTML View Routes
    'GET /auth/login' => [AuthController::class, 'showLogin'],
    'GET /auth/register' => [AuthController::class, 'showLogin'],
    'GET /auth/verify' => [AuthController::class, 'showVerify'],
    'GET /patient/dashboard' => [AuthController::class, 'showPatientDashboard'],
    'GET /appointments' => [AppointmentController::class, 'index'],
    'GET /appointments/create' => [AppointmentController::class, 'create'],
];
