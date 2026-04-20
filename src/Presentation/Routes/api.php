<?php

namespace CharosEMR\Presentation\Routes;

use CharosEMR\Presentation\Http\Controllers\AuthController;
use CharosEMR\Presentation\Http\Controllers\AppointmentController;
use CharosEMR\Presentation\Http\Controllers\PrescriptionController;
use CharosEMR\Presentation\Http\Middlewares\AuthMiddleware;
use CharosEMR\Presentation\Http\Middlewares\RoleMiddleware;

return [
    // API Routes
    'POST /api/auth/send-login-code' => [AuthController::class, 'sendLoginCode'],
    'POST /api/auth/verify-login' => [AuthController::class, 'verifyAndLogin'],
    'POST /api/auth/send-signup-code' => [AuthController::class, 'sendSignupCode'],
    'POST /api/auth/verify-signup' => [AuthController::class, 'verifyAndRegister'],
    'POST /api/auth/logout' => [AuthController::class, 'logout'],

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
    'GET /appointments' => [AppointmentController::class, 'index'],
    'GET /appointments/create' => [AppointmentController::class, 'create'],
];
