<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\Shared\Entities\VerificationCode;
use CharosEMR\Domain\Shared\Repositories\VerificationCodeRepositoryInterface;
use PDO;

class PDOVerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(VerificationCode $code): void
    {
        if ($code->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO verification_codes (email, code, purpose, expires_at, used_at) 
                 VALUES (:email, :code, :purpose, :expires_at, :used_at)"
            );
            $stmt->execute([
                ':email' => $code->getEmail(),
                ':code' => $code->getCode(),
                ':purpose' => $code->getPurpose(),
                ':expires_at' => $code->getExpiresAt()->format('Y-m-d H:i:s'),
                ':used_at' => $code->getUsedAt()?->format('Y-m-d H:i:s')
            ]);
            $code->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE verification_codes 
                 SET used_at = :used_at, updated_at = NOW() 
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $code->getId(),
                ':used_at' => $code->getUsedAt()?->format('Y-m-d H:i:s')
            ]);
        }
    }

    public function findByEmailAndCode(string $email, string $code): ?VerificationCode
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM verification_codes 
             WHERE email = :email AND code = :code 
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':code' => $code]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateVerificationCode($data);
    }

    public function invalidatePreviousCodes(string $email, string $purpose): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE verification_codes 
             SET used_at = NOW() 
             WHERE email = :email AND purpose = :purpose AND used_at IS NULL"
        );
        $stmt->execute([':email' => $email, ':purpose' => $purpose]);
    }

    public function deleteExpired(): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM verification_codes WHERE expires_at < NOW()"
        );
        $stmt->execute();
    }

    private function hydrateVerificationCode(array $data): VerificationCode
    {
        return new VerificationCode(
            (int) $data['id'],
            $data['email'],
            $data['code'],
            $data['purpose'],
            new \DateTime($data['expires_at']),
            $data['used_at'] ? new \DateTime($data['used_at']) : null,
            new \DateTime($data['created_at']),
            new \DateTime($data['updated_at'])
        );
    }
}
