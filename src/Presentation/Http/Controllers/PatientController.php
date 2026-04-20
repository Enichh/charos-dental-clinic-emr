<?php

namespace CharosEMR\Presentation\Http\Controllers;

use CharosEMR\Application\Appointment\DTOs\GetAvailableSlotsRequest;
use CharosEMR\Application\Appointment\DTOs\ScheduleAppointmentRequest;
use CharosEMR\Application\Appointment\DTOs\CancelAppointmentRequest;
use CharosEMR\Application\Appointment\DTOs\ViewAppointmentStatusRequest;
use CharosEMR\Application\Appointment\UseCases\GetAvailableSlotsUseCase;
use CharosEMR\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use CharosEMR\Application\Appointment\UseCases\CancelAppointmentUseCase;
use CharosEMR\Application\Appointment\UseCases\ViewAppointmentStatusUseCase;
use CharosEMR\Application\User\DTOs\UpdatePatientProfileRequest;
use CharosEMR\Application\User\UseCases\UpdatePatientProfileUseCase;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;
use CharosEMR\Domain\Appointment\Repositories\AppointmentRepositoryInterface;
use CharosEMR\Presentation\Http\Responses\ViewRenderer;
use CharosEMR\Application\Shared\Services\CsrfProtectionService;
use CharosEMR\Application\Shared\Validation\ValidatorInterface;
use CharosEMR\Application\Shared\Validation\ValidationResult;

class PatientController
{
    public function __construct(
        private PatientRepositoryInterface $patientRepository,
        private AppointmentRepositoryInterface $appointmentRepository,
        private GetAvailableSlotsUseCase $getAvailableSlotsUseCase,
        private ScheduleAppointmentUseCase $scheduleAppointmentUseCase,
        private CancelAppointmentUseCase $cancelAppointmentUseCase,
        private ViewAppointmentStatusUseCase $viewAppointmentStatusUseCase,
        private UpdatePatientProfileUseCase $updatePatientProfileUseCase,
        private ViewRenderer $viewRenderer,
        private CsrfProtectionService $csrfService,
        private ValidatorInterface $validator
    ) {}

    public function dashboard()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            header('Location: /auth/login');
            exit;
        }

        $appointments = $this->appointmentRepository->findByPatientId($patient->getId());
        $upcomingAppointments = array_filter($appointments, function ($appointment) {
            return $appointment->getAppointmentDate() >= new \DateTime();
        });

        $pastAppointments = array_filter($appointments, function ($appointment) {
            return $appointment->getAppointmentDate() < new \DateTime();
        });

        $this->viewRenderer->render('patient/dashboard', [
            'layout' => 'main',
            'title' => 'Patient Dashboard - Charos Dental Clinic',
            'patient' => $patient,
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,
            'csrfToken' => $this->csrfService->generateToken()
        ]);
    }

    public function bookAppointment()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            header('Location: /auth/login');
            exit;
        }

        $this->viewRenderer->render('patient/book-appointment', [
            'layout' => 'main',
            'title' => 'Book Appointment - Charos Dental Clinic',
            'patient' => $patient,
            'csrfToken' => $this->csrfService->generateToken()
        ]);
    }

    public function getAvailableSlots()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $adminId = $input['admin_id'] ?? null;
        $date = $input['date'] ?? null;
        $csrfToken = $input['csrf_token'] ?? null;

        if (!$adminId || !$date) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'admin_id and date are required']);
            return;
        }

        if (!$this->csrfService->validateToken($csrfToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        try {
            $formattedDate = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$formattedDate) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid date format. Expected format: Y-m-d']);
                return;
            }

            $requestDto = new GetAvailableSlotsRequest(
                $adminId,
                $formattedDate
            );

            $responseDto = $this->getAvailableSlotsUseCase->execute($requestDto);

            header('Content-Type: application/json');
            echo json_encode($responseDto->toArray());
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function scheduleAppointment()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Patient not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $input['csrf_token'] ?? null;

        if (!$this->csrfService->validateToken($csrfToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $input['patient_id'] = $patient->getId();

        $requestDto = ScheduleAppointmentRequest::fromArray($input, $this->validator);

        if ($requestDto instanceof ValidationResult) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['errors' => $requestDto->getErrors()]);
            return;
        }

        try {
            $responseDto = $this->scheduleAppointmentUseCase->execute($requestDto);

            header('Content-Type: application/json');
            echo json_encode($responseDto->toArray());
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function cancelAppointment()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Patient not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $appointmentId = $input['appointment_id'] ?? null;
        $csrfToken = $input['csrf_token'] ?? null;

        if (!$appointmentId) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'appointment_id is required']);
            return;
        }

        if (!$this->csrfService->validateToken($csrfToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        try {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment || $appointment->getPatientId() !== $patient->getId()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden - you can only cancel your own appointments']);
                return;
            }

            $requestDto = new CancelAppointmentRequest($appointmentId);
            $this->cancelAppointmentUseCase->execute($requestDto);

            header('Content-Type: application/json');
            echo json_encode(['message' => 'Appointment cancelled successfully']);
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function appointmentStatus()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            header('Location: /auth/login');
            exit;
        }

        $appointmentId = $_GET['id'] ?? null;
        if (!$appointmentId) {
            header('Location: /patient/dashboard');
            exit;
        }

        try {
            $requestDto = new ViewAppointmentStatusRequest((int)$appointmentId);
            $responseDto = $this->viewAppointmentStatusUseCase->execute($requestDto);

            if ($responseDto->patientId !== $patient->getId()) {
                header('Location: /patient/dashboard');
                exit;
            }

            $this->viewRenderer->render('patient/appointment-status', [
                'layout' => 'main',
                'title' => 'Appointment Status - Charos Dental Clinic',
                'appointment' => $responseDto,
                'csrfToken' => $this->csrfService->generateToken()
            ]);
        } catch (\Exception $e) {
            header('Location: /patient/dashboard');
            exit;
        }
    }

    public function profile()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            header('Location: /auth/login');
            exit;
        }

        $this->viewRenderer->render('patient/profile', [
            'layout' => 'main',
            'title' => 'My Profile - Charos Dental Clinic',
            'patient' => $patient,
            'csrfToken' => $this->csrfService->generateToken()
        ]);
    }

    public function updateProfile()
    {
        $userId = $_SESSION['user_id'];
        $patient = $this->patientRepository->findByUserId((int)$userId);
        if (!$patient) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Patient not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $input['csrf_token'] ?? null;

        if (!$this->csrfService->validateToken($csrfToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        try {
            $requestDto = new UpdatePatientProfileRequest(
                (int)$userId,
                $input['first_name'] ?? $patient->getFirstName(),
                $input['last_name'] ?? $patient->getLastName(),
                $input['gender'] ?? $patient->getGender()->value,
                $input['phone'] ?? $patient->getPhoneNumber(),
                $input['address'] ?? $patient->getAddress(),
                $input['date_of_birth'] ?? ($patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null)
            );

            $responseDto = $this->updatePatientProfileUseCase->execute($requestDto, (int)$userId);

            header('Content-Type: application/json');
            echo json_encode($responseDto->toArray());
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
