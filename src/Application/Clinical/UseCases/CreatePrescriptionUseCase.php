<?php

namespace CharosEMR\Application\Clinical\UseCases;

use CharosEMR\Application\Clinical\DTOs\CreatePrescriptionRequest;
use CharosEMR\Application\Clinical\DTOs\CreatePrescriptionResponse;
use CharosEMR\Domain\Clinical\Entities\Prescription;
use CharosEMR\Domain\Clinical\Repositories\PrescriptionRepositoryInterface;

class CreatePrescriptionUseCase
{
    public function __construct(
        private PrescriptionRepositoryInterface $repository
    ) {}

    public function execute(CreatePrescriptionRequest $request): CreatePrescriptionResponse
    {
        $prescription = new Prescription(
            null,
            $request->patientId,
            $request->dentistId,
            $request->medication,
            $request->dosage,
            $request->instructions
        );

        if ($prescription->isDosageDangerous()) {
            throw new \Exception("Dosage exceeds safe limits.");
        }

        $this->repository->save($prescription);

        return new CreatePrescriptionResponse(
            $prescription->getId(),
            'Success'
        );
    }
}
