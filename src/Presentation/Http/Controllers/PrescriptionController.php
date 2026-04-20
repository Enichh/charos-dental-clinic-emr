<?php

namespace CharosEMR\Presentation\Http\Controllers;

use CharosEMR\Application\Clinical\DTOs\CreatePrescriptionRequest;
use CharosEMR\Application\Clinical\UseCases\CreatePrescriptionUseCase;

class PrescriptionController
{
    public function __construct(private CreatePrescriptionUseCase $useCase) {}

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $requestDto = new CreatePrescriptionRequest(
                $input['patient_id'],
                $input['dentist_id'],
                $input['medication'],
                $input['dosage'],
                $input['instructions'] ?? null
            );

            $responseDto = $this->useCase->execute($requestDto);

            header('Content-Type: application/json');
            echo json_encode($responseDto->toArray());
        } catch (\Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
