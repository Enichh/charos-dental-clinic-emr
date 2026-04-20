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

    /**
     * Requires patients table with columns: id, user_id, first_name, last_name,
     * date_of_birth, gender, phone_number, address, blood_type, allergies
     */
    public function __construct(
        private PDO $pdo,
        DataEncryption $encryption
    ) {
        $this->encryption = $encryption;
    }

    public function save(Patient $patient): Patient
    {
        try {
            if ($patient->getId() === null) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, phone_number, address, blood_type, allergies)
                     VALUES (:user_id, :first_name, :last_name, :date_of_birth, :gender, :phone_number, :address, :blood_type, :allergies)"
                );
                $stmt->execute([
                    ':user_id' => $patient->getUserId(),
                    ':first_name' => $patient->getFirstName(),
                    ':last_name' => $patient->getLastName(),
                    ':date_of_birth' => $patient->getDateOfBirth()->format('Y-m-d'),
                    ':gender' => $patient->getGender()->value,
                    ':phone_number' => $this->encrypt($patient->getPhoneNumber()),
                    ':address' => $this->encrypt($patient->getAddress()),
                    ':blood_type' => $this->encrypt($patient->getBloodType()),
                    ':allergies' => $this->encrypt($patient->getAllergies())
                ]);
                $patient->setId((int) $this->pdo->lastInsertId());
            } else {
                $stmt = $this->pdo->prepare(
                    "UPDATE patients SET user_id = :user_id, first_name = :first_name, last_name = :last_name,
                     date_of_birth = :date_of_birth, gender = :gender, phone_number = :phone_number,
                     address = :address, blood_type = :blood_type, allergies = :allergies WHERE id = :id"
                );
                $stmt->execute([
                    ':id' => $patient->getId(),
                    ':user_id' => $patient->getUserId(),
                    ':first_name' => $patient->getFirstName(),
                    ':last_name' => $patient->getLastName(),
                    ':date_of_birth' => $patient->getDateOfBirth()->format('Y-m-d'),
                    ':gender' => $patient->getGender()->value,
                    ':phone_number' => $this->encrypt($patient->getPhoneNumber()),
                    ':address' => $this->encrypt($patient->getAddress()),
                    ':blood_type' => $this->encrypt($patient->getBloodType()),
                    ':allergies' => $this->encrypt($patient->getAllergies())
                ]);
            }

            return $patient;
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column')) {
                throw new \RuntimeException('Database schema mismatch: patients table is missing required columns');
            }
            throw $e;
        }
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
        return new Patient(
            (int) $data['id'],
            (int) $data['user_id'],
            $data['first_name'],
            $data['last_name'],
            new \DateTime($data['date_of_birth']),
            Gender::from($data['gender']),
            $this->decrypt($data['phone_number']),
            $this->decrypt($data['address']),
            $this->decrypt($data['blood_type']),
            $this->decrypt($data['allergies'])
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
            throw new \RuntimeException('Failed to encrypt sensitive data');
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
            throw new \RuntimeException('Failed to decrypt sensitive data');
        }
    }
}
