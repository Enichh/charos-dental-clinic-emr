<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\User\Entities\Patient;
use CharosEMR\Domain\User\Enums\Gender;
use CharosEMR\Domain\User\Repositories\PatientRepositoryInterface;
use CharosEMR\Application\Shared\Services\DataEncryption;
use PDO;

class PDOPatientRepository implements PatientRepositoryInterface
{
    private DataEncryption $encryption;

    public function __construct(
        private PDO $pdo,
        DataEncryption $encryption
    ) {
        $this->encryption = $encryption;
    }

    public function save(Patient $patient): Patient
    {
        if ($patient->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone_number, address, blood_type, allergies) 
                 VALUES (:first_name, :last_name, :date_of_birth, :gender, :phone_number, :address, :blood_type, :allergies)"
            );
            $stmt->execute([
                ':first_name' => $this->extractFirstName($patient->getName()),
                ':last_name' => $this->extractLastName($patient->getName()),
                ':date_of_birth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                ':gender' => $patient->getGender()->value,
                ':phone_number' => $this->encrypt($patient->getPhoneNumber()),
                ':address' => $this->encrypt($patient->getAddress()),
                ':blood_type' => null,
                ':allergies' => null
            ]);
            $patient->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE patients SET first_name = :first_name, last_name = :last_name, 
                 date_of_birth = :date_of_birth, gender = :gender, phone_number = :phone_number, 
                 address = :address, blood_type = :blood_type, allergies = :allergies WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $patient->getId(),
                ':first_name' => $this->extractFirstName($patient->getName()),
                ':last_name' => $this->extractLastName($patient->getName()),
                ':date_of_birth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                ':gender' => $patient->getGender()->value,
                ':phone_number' => $this->encrypt($patient->getPhoneNumber()),
                ':address' => $this->encrypt($patient->getAddress()),
                ':blood_type' => null,
                ':allergies' => null
            ]);
        }

        return $patient;
    }

    public function findByUserId(int $userId): ?Patient
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydratePatient($data);
    }

    public function findByEmail(string $email): ?Patient
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.* FROM patients p 
             INNER JOIN users u ON p.user_id = u.id 
             WHERE u.email = :email"
        );
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydratePatient($data);
    }

    public function findById(int $id): ?Patient
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydratePatient($data);
    }

    private function hydratePatient(array $data): Patient
    {
        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);

        return new Patient(
            (int) $data['id'],
            $fullName,
            '', // Email is stored in users table, not patients
            '', // Password is stored in users table, not patients
            Gender::from($data['gender']),
            $this->decrypt($data['phone_number']),
            $this->decrypt($data['address']),
            $data['date_of_birth'] ? new \DateTime($data['date_of_birth']) : null
        );
    }

    private function encrypt(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }
        try {
            return $this->encryption->encrypt($data);
        } catch (\Exception $e) {
            error_log("Encryption failed: " . $e->getMessage());
            return null;
        }
    }

    private function decrypt(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }
        try {
            return $this->encryption->decrypt($data);
        } catch (\Exception $e) {
            error_log("Decryption failed: " . $e->getMessage());
            return null;
        }
    }

    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        array_shift($parts); // Remove first name
        return implode(' ', $parts);
    }
}
