<?php

use DI\ContainerBuilder;
use CharosEMR\Infrastructure\Database\PDOConnectionFactory;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use CharosEMR\Domain\Appointment\Repositories\AdminAvailabilityRepositoryInterface;
use CharosEMR\Domain\Clinical\Repositories\PrescriptionRepositoryInterface;
use CharosEMR\Domain\Clinical\Repositories\DentalVisitRepositoryInterface;
use CharosEMR\Domain\Clinical\Repositories\DentalChartEntryRepositoryInterface;
use CharosEMR\Infrastructure\Persistence\PDOUserRepository;
use CharosEMR\Infrastructure\Persistence\PDOPatientRepository;
use CharosEMR\Infrastructure\Persistence\PDOAppointmentRepository;
use CharosEMR\Infrastructure\Persistence\PDOAdminAvailabilityRepository;
use CharosEMR\Infrastructure\Persistence\PDOPrescriptionRepository;
use CharosEMR\Infrastructure\Persistence\PDODentalVisitRepository;
use CharosEMR\Infrastructure\Persistence\PDODentalChartEntryRepository;
use CharosEMR\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use CharosEMR\Application\Appointment\UseCases\CancelAppointmentUseCase;
use CharosEMR\Application\Clinical\UseCases\CreatePrescriptionUseCase;
use CharosEMR\Application\User\UseCases\RegisterPatientUseCase;
use CharosEMR\Application\User\UseCases\LoginPatientUseCase;
use CharosEMR\Application\User\UseCases\UpdatePatientProfileUseCase;
use CharosEMR\Application\Shared\Interfaces\PasswordHasherInterface;
use CharosEMR\Infrastructure\Security\Argon2PasswordHasher;
use CharosEMR\Application\Shared\Interfaces\MailerInterface;
use CharosEMR\Infrastructure\Services\SymfonyMailerAdapter;
use CharosEMR\Application\Shared\Services\VerificationCodeService;
use CharosEMR\Domain\Shared\Repositories\VerificationCodeRepositoryInterface;
use CharosEMR\Infrastructure\Persistence\PDOVerificationCodeRepository;
use CharosEMR\Presentation\Http\Responses\ViewRenderer;
use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\Validator;
use CharosEMR\Application\Shared\Validation\SchemaValidator;
use CharosEMR\Application\Shared\Events\EventDispatcherInterface;
use CharosEMR\Application\Shared\Events\EventDispatcher;
use CharosEMR\Application\Appointment\Events\AppointmentBookedEvent;
use CharosEMR\Application\Appointment\Events\AppointmentReminderEvent;
use CharosEMR\Infrastructure\Listeners\SendAppointmentConfirmationEmailListener;
use CharosEMR\Infrastructure\Listeners\SendAppointmentReminderEmailListener;
use CharosEMR\Application\Shared\Services\CsrfProtectionService;
use CharosEMR\Application\Shared\Services\AuditLogger;
use CharosEMR\Application\Shared\Services\RateLimiter;
use CharosEMR\Application\Shared\Services\DataEncryption;
use CharosEMR\Application\Shared\Services\MfaService;
use CharosEMR\Application\Shared\Interfaces\LoggerInterface;
use CharosEMR\Infrastructure\Logging\CentralizedLogger;
use CharosEMR\Domain\Shared\Repositories\AuditLogRepositoryInterface;
use CharosEMR\Infrastructure\Persistence\PDOAuditLogRepository;
use CharosEMR\Presentation\Http\Controllers\AuthController;
use CharosEMR\Presentation\Http\Controllers\AppointmentController;
use CharosEMR\Presentation\Http\Controllers\PrescriptionController;
use CharosEMR\Presentation\Http\Controllers\PatientController;
use CharosEMR\Presentation\Http\Controllers\HomeController;
use CharosEMR\Presentation\Http\Middlewares\AuthMiddleware;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    \PDO::class => function () {
        $factory = new PDOConnectionFactory();
        return $factory->create();
    },

    // Repository Interface Bindings
    UserRepositoryInterface::class => \DI\autowire(PDOUserRepository::class),
    PatientRepositoryInterface::class => \DI\autowire(PDOPatientRepository::class),
    AppointmentRepositoryInterface::class => \DI\autowire(PDOAppointmentRepository::class),
    AdminAvailabilityRepositoryInterface::class => \DI\autowire(PDOAdminAvailabilityRepository::class),
    PrescriptionRepositoryInterface::class => \DI\autowire(PDOPrescriptionRepository::class),
    DentalVisitRepositoryInterface::class => \DI\autowire(PDODentalVisitRepository::class),
    DentalChartEntryRepositoryInterface::class => \DI\autowire(PDODentalChartEntryRepository::class),
    VerificationCodeRepositoryInterface::class => \DI\autowire(PDOVerificationCodeRepository::class),
    AuditLogRepositoryInterface::class => \DI\autowire(PDOAuditLogRepository::class),

    // Use Case Bindings
    ScheduleAppointmentUseCase::class => \DI\autowire(ScheduleAppointmentUseCase::class),
    CancelAppointmentUseCase::class => \DI\autowire(CancelAppointmentUseCase::class),
    CreatePrescriptionUseCase::class => \DI\autowire(CreatePrescriptionUseCase::class),
    RegisterPatientUseCase::class => \DI\autowire(RegisterPatientUseCase::class),
    LoginPatientUseCase::class => \DI\autowire(LoginPatientUseCase::class),
    UpdatePatientProfileUseCase::class => \DI\autowire(UpdatePatientProfileUseCase::class),

    // Shared Interface Bindings
    PasswordHasherInterface::class => \DI\autowire(Argon2PasswordHasher::class),
    MailerInterface::class => \DI\autowire(SymfonyMailerAdapter::class),
    VerificationCodeService::class => \DI\autowire(VerificationCodeService::class),
    LoggerInterface::class => \DI\autowire(CentralizedLogger::class),

    // Security Services
    CsrfProtectionService::class => \DI\autowire(CsrfProtectionService::class),
    AuditLogger::class => \DI\autowire(AuditLogger::class)->constructorParameter('encryption', \DI\get(DataEncryption::class)),
    RateLimiter::class => \DI\autowire(RateLimiter::class),
    DataEncryption::class => \DI\autowire(DataEncryption::class),
    MfaService::class => \DI\autowire(MfaService::class),

    // View Renderer
    ViewRenderer::class => \DI\autowire(ViewRenderer::class),

    // Validation
    ValidatorInterface::class => \DI\autowire(Validator::class),
    SchemaValidator::class => \DI\autowire(SchemaValidator::class),

    // Event System
    EventDispatcherInterface::class => \DI\autowire(EventDispatcher::class),
    SendAppointmentConfirmationEmailListener::class => \DI\autowire(SendAppointmentConfirmationEmailListener::class),
    SendAppointmentReminderEmailListener::class => \DI\autowire(SendAppointmentReminderEmailListener::class),

    // Controllers
    AuthController::class => \DI\autowire(AuthController::class),
    AppointmentController::class => \DI\autowire(AppointmentController::class),
    PrescriptionController::class => \DI\autowire(PrescriptionController::class),
    PatientController::class => \DI\autowire(PatientController::class),
    HomeController::class => \DI\autowire(HomeController::class),

    // Middlewares
    AuthMiddleware::class => \DI\autowire(AuthMiddleware::class),

    // Configured middleware instances with role requirements
    'middleware.auth.patient' => \DI\autowire(AuthMiddleware::class)
        ->constructorParameter('requiredRole', 'patient')
        ->constructorParameter('requireCsrf', true),

    'middleware.auth.admin' => \DI\autowire(AuthMiddleware::class)
        ->constructorParameter('requiredRole', 'admin')
        ->constructorParameter('requireCsrf', true),
]);

$container = $containerBuilder->build();

// Register event listeners
$eventDispatcher = $container->get(EventDispatcherInterface::class);
$emailListener = $container->get(SendAppointmentConfirmationEmailListener::class);
$eventDispatcher->addListener(AppointmentBookedEvent::class, $emailListener);
$reminderListener = $container->get(SendAppointmentReminderEmailListener::class);
$eventDispatcher->addListener(AppointmentReminderEvent::class, $reminderListener);

return $container;
