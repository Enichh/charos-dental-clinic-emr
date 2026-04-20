<?php

use DI\ContainerBuilder;
use CharosEMR\Infrastructure\Database\PDOConnectionFactory;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use CharosEMR\Domain\Clinical\Repositories\PrescriptionRepositoryInterface;
use CharosEMR\Infrastructure\Persistence\PDOUserRepository;
use CharosEMR\Infrastructure\Persistence\PDOAppointmentRepository;
use CharosEMR\Infrastructure\Persistence\PDOPrescriptionRepository;
use CharosEMR\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use CharosEMR\Application\Appointment\UseCases\CancelAppointmentUseCase;
use CharosEMR\Application\Clinical\UseCases\CreatePrescriptionUseCase;
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
use CharosEMR\Application\Shared\Events\EventDispatcherInterface;
use CharosEMR\Application\Shared\Events\EventDispatcher;
use CharosEMR\Application\Appointment\Events\AppointmentBookedEvent;
use CharosEMR\Application\Appointment\Events\AppointmentReminderEvent;
use CharosEMR\Infrastructure\Listeners\SendAppointmentConfirmationEmailListener;
use CharosEMR\Infrastructure\Listeners\SendAppointmentReminderEmailListener;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    \PDO::class => function () {
        $factory = new PDOConnectionFactory();
        return $factory->create();
    },

    // Repository Interface Bindings
    UserRepositoryInterface::class => \DI\autowire(PDOUserRepository::class),
    AppointmentRepositoryInterface::class => \DI\autowire(PDOAppointmentRepository::class),
    PrescriptionRepositoryInterface::class => \DI\autowire(PDOPrescriptionRepository::class),
    VerificationCodeRepositoryInterface::class => \DI\autowire(PDOVerificationCodeRepository::class),

    // Use Case Bindings
    ScheduleAppointmentUseCase::class => \DI\autowire(ScheduleAppointmentUseCase::class),
    CancelAppointmentUseCase::class => \DI\autowire(CancelAppointmentUseCase::class),
    CreatePrescriptionUseCase::class => \DI\autowire(CreatePrescriptionUseCase::class),

    // Shared Interface Bindings
    PasswordHasherInterface::class => \DI\autowire(Argon2PasswordHasher::class),
    MailerInterface::class => \DI\autowire(SymfonyMailerAdapter::class),
    VerificationCodeService::class => \DI\autowire(VerificationCodeService::class),

    // View Renderer
    ViewRenderer::class => \DI\autowire(ViewRenderer::class),

    // Validation
    ValidatorInterface::class => \DI\autowire(Validator::class),

    // Event System
    EventDispatcherInterface::class => \DI\autowire(EventDispatcher::class),
    SendAppointmentConfirmationEmailListener::class => \DI\autowire(SendAppointmentConfirmationEmailListener::class),
    SendAppointmentReminderEmailListener::class => \DI\autowire(SendAppointmentReminderEmailListener::class),
]);

$container = $containerBuilder->build();

// Register event listeners
$eventDispatcher = $container->get(EventDispatcherInterface::class);
$emailListener = $container->get(SendAppointmentConfirmationEmailListener::class);
$eventDispatcher->addListener(AppointmentBookedEvent::class, $emailListener);

return $container;
