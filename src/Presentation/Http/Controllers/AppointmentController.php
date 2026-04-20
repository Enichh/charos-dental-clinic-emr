<?php

namespace CharosEMR\Presentation\Http\Controllers;

use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentRequest;
use CharosEMR\Application\Appointment\DTOs\CancelAppointmentRequest;
use CharosEMR\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use CharosEMR\Application\Appointment\UseCases\CancelAppointmentUseCase;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use CharosEMR\Presentation\Http\Responses\ViewRenderer;
use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class AppointmentController
{
    public function __construct(
        private ScheduleAppointmentUseCase $scheduleUseCase,
        private CancelAppointmentUseCase $cancelUseCase,
        private AppointmentRepositoryInterface $appointmentRepository,
        private ViewRenderer $viewRenderer,
        private ValidatorInterface $validator
    ) {}

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $requestDto = ScheduleAppointmentRequest::fromArray($input, $this->validator);

        if ($requestDto instanceof ValidationResult) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['errors' => $requestDto->getErrors()]);
            return;
        }

        try {
            $responseDto = $this->scheduleUseCase->execute($requestDto);

            header('Content-Type: application/json');
            echo json_encode($responseDto->toArray());
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function cancel()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $requestDto = new CancelAppointmentRequest($input['appointment_id']);
            $this->cancelUseCase->execute($requestDto);

            header('Content-Type: application/json');
            echo json_encode(['message' => 'Appointment cancelled successfully']);
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function index()
    {
        $appointments = $this->appointmentRepository->findAll();
        $this->viewRenderer->render('appointments/index', [
            'layout' => 'main',
            'title' => 'Appointments - Charos Dental Clinic',
            'appointments' => $appointments
        ]);
    }

    public function create()
    {
        $this->viewRenderer->render('appointments/create', [
            'layout' => 'main',
            'title' => 'Schedule Appointment - Charos Dental Clinic'
        ]);
    }
}
